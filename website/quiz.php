<?php
require_once __DIR__ . '/config.php';
requireLogin();
$quiz_id = (int)($_GET['quiz_id'] ?? 0);
if (!$quiz_id) redirect(BASE_URL . '/home.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quiz — <?= SITE_NAME ?></title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

<nav class="navbar">
  <a href="home.php" class="navbar-brand">⚡ <?= SITE_NAME ?></a>
  <div class="navbar-nav">
    <span id="nav-quiz-title" style="color:var(--text-secondary);font-size:.9rem"></span>
  </div>
</nav>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
  <div class="spinner"></div>
  <div class="loading-text">Loading quiz…</div>
</div>

<div class="quiz-wrapper" id="quizWrapper" style="display:none">

  <div class="quiz-header">
    <div class="quiz-title" id="quizTitle"></div>
    <div class="timer" id="timer">⏱ --:--</div>
  </div>

  <div class="progress-wrap">
    <div class="progress-meta">
      <span id="progLabel">Question 1 of N</span>
      <span id="answeredLabel">0 answered</span>
    </div>
    <div class="progress-bar"><div class="progress-fill" id="progFill" style="width:0%"></div></div>
  </div>

  <div id="questionArea"></div>

  <div class="quiz-controls">
    <button class="btn btn-secondary" id="btnPrev" onclick="navigate(-1)">← Prev</button>
    <button class="btn btn-primary" id="btnNext" onclick="navigate(1)">Next →</button>
    <button class="btn btn-gold hidden" id="btnSubmit" onclick="submitQuiz()">Submit Quiz 🚀</button>
  </div>

</div>

<script src="js/quiz.js"></script>
<script>
initQuiz(<?= $quiz_id ?>, '<?= BASE_URL ?>');
</script>
</body>
</html>
