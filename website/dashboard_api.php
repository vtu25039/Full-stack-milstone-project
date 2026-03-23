<?php
require_once __DIR__ . '/config.php';
requireLogin();
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$uid    = (int)$_SESSION['user_id'];

switch ($action) {
    case 'history':
        getHistory($uid);
        break;
    case 'stats':
        getStats($uid);
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}

function getHistory(int $uid): void {
    $db = getDB();
    $sql = 'SELECT a.id, a.quiz_id, a.score, a.passed, a.completed_at,
                   q.title AS quiz_title, q.pass_score,
                   c.cert_uuid
            FROM attempts a
            JOIN quizzes q ON q.id = a.quiz_id
            LEFT JOIN certificates c ON c.attempt_id = a.id
            WHERE a.user_id = ?
            ORDER BY a.completed_at DESC';
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
}

function getStats(int $uid): void {
    $db = getDB();

    $stmt = $db->prepare(
        'SELECT COUNT(*) as total, AVG(score) as avg_score, SUM(passed) as passed_count
         FROM attempts WHERE user_id=? AND completed_at IS NOT NULL'
    );
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    $stmt = $db->prepare(
        'SELECT COUNT(*) as certs FROM certificates WHERE user_id=?'
    );
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $certRow = $stmt->get_result()->fetch_assoc();

    echo json_encode([
        'total_attempts'  => (int)$row['total'],
        'avg_score'       => round((float)$row['avg_score'], 1),
        'passed_count'    => (int)$row['passed_count'],
        'certificates'    => (int)$certRow['certs'],
    ]);
}
