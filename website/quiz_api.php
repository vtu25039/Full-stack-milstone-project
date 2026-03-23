<?php
require_once __DIR__ . '/config.php';
requireLogin();
header('Content-Type: application/json');

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

switch ($action) {
    case 'list':
        listQuizzes();
        break;
    case 'get':
        getQuiz();
        break;
    case 'submit':
        submitQuiz();
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}

// ---- List all quizzes -----------------------------------------------
function listQuizzes(): void {
    $db = getDB();
    $uid = (int)$_SESSION['user_id'];
    $sql = 'SELECT q.id, q.title, q.description, q.time_limit, q.pass_score,
                   (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) AS question_count,
                   (SELECT COUNT(*) FROM attempts a WHERE a.quiz_id=q.id AND a.user_id=? AND a.passed=1) AS passed
            FROM quizzes q ORDER BY q.id';
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode($rows);
}

// ---- Get a single quiz with questions & options ---------------------
function getQuiz(): void {
    $quiz_id = (int)($_GET['quiz_id'] ?? 0);
    if (!$quiz_id) { http_response_code(400); echo json_encode(['error'=>'Missing quiz_id']); return; }

    $db = getDB();
    // Quiz info
    $stmt = $db->prepare('SELECT * FROM quizzes WHERE id=?');
    $stmt->bind_param('i', $quiz_id);
    $stmt->execute();
    $quiz = $stmt->get_result()->fetch_assoc();
    if (!$quiz) { http_response_code(404); echo json_encode(['error'=>'Quiz not found']); return; }

    // Questions
    $stmt = $db->prepare('SELECT id, question_text, question_type FROM questions WHERE quiz_id=? ORDER BY id');
    $stmt->bind_param('i', $quiz_id);
    $stmt->execute();
    $questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Options for each question
    foreach ($questions as &$q) {
        $stmt = $db->prepare('SELECT id, option_text FROM options WHERE question_id=? ORDER BY id');
        $stmt->bind_param('i', $q['id']);
        $stmt->execute();
        $q['options'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    $quiz['questions'] = $questions;
    echo json_encode($quiz);
}

// ---- Submit quiz answers & calculate score --------------------------
function submitQuiz(): void {
    $input   = json_decode(file_get_contents('php://input'), true);
    $quiz_id = (int)($input['quiz_id'] ?? 0);
    $answers = $input['answers'] ?? [];   // [question_id => selected_option_id]
    $uid     = (int)$_SESSION['user_id'];

    if (!$quiz_id || !$answers) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid submission']);
        return;
    }

    $db = getDB();

    // Get pass threshold
    $stmt = $db->prepare('SELECT pass_score, title FROM quizzes WHERE id=?');
    $stmt->bind_param('i', $quiz_id);
    $stmt->execute();
    $quiz = $stmt->get_result()->fetch_assoc();

    // Load correct answers
    $stmt = $db->prepare(
        'SELECT q.id AS qid, o.id AS oid
         FROM questions q
         JOIN options o ON o.question_id = q.id AND o.is_correct = 1
         WHERE q.quiz_id = ?'
    );
    $stmt->bind_param('i', $quiz_id);
    $stmt->execute();
    $correctMap = [];
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
        $correctMap[$row['qid']] = $row['oid'];
    }

    $total   = count($correctMap);
    $correct = 0;
    foreach ($correctMap as $qid => $correctOid) {
        if (isset($answers[$qid]) && (int)$answers[$qid] === $correctOid) {
            $correct++;
        }
    }
    $score  = $total > 0 ? round(($correct / $total) * 100, 2) : 0;
    $passed = $score >= $quiz['pass_score'] ? 1 : 0;

    // Save attempt
    $stmt = $db->prepare(
        'INSERT INTO attempts (user_id, quiz_id, score, passed, completed_at) VALUES (?,?,?,?,NOW())'
    );
    $stmt->bind_param('iidi', $uid, $quiz_id, $score, $passed);
    $stmt->execute();
    $attempt_id = $db->insert_id;

    // Save individual answers
    foreach ($answers as $qid => $oid) {
        $stmt = $db->prepare('INSERT INTO attempt_answers (attempt_id, question_id, selected_option_id) VALUES (?,?,?)');
        $stmt->bind_param('iii', $attempt_id, $qid, $oid);
        $stmt->execute();
    }

    // Generate certificate if passed
    $cert_uuid = null;
    if ($passed) {
        $cert_uuid = strtoupper(bin2hex(random_bytes(8)) . '-' . bin2hex(random_bytes(4)));
        $stmt = $db->prepare(
            'INSERT IGNORE INTO certificates (attempt_id, user_id, cert_uuid) VALUES (?,?,?)'
        );
        $stmt->bind_param('iis', $attempt_id, $uid, $cert_uuid);
        $stmt->execute();
    }

    echo json_encode([
        'attempt_id'  => $attempt_id,
        'score'       => $score,
        'correct'     => $correct,
        'total'       => $total,
        'passed'      => (bool)$passed,
        'pass_score'  => $quiz['pass_score'],
        'cert_uuid'   => $cert_uuid,
        'quiz_title'  => $quiz['title'],
    ]);
}
