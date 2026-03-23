<?php
require_once __DIR__ . '/config.php';
requireLogin();
$userName = htmlspecialchars($_SESSION['user_name']);
$flashSuccess = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Home — <?= SITE_NAME ?></title>
  <meta name="description" content="Browse available quizzes and start your certification journey on QuizCert Pro.">
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
  <a href="home.php" class="navbar-brand">⚡ <?= SITE_NAME ?></a>
  <div class="navbar-nav">
    <a href="home.php"      class="nav-link">🏠 Home</a>
    <a href="dashboard.php" class="nav-link">📊 Dashboard</a>
    <form method="POST" action="auth.php" style="margin:0">
      <input type="hidden" name="action" value="logout">
      <button type="submit" class="btn btn-secondary btn-sm">Sign Out</button>
    </form>
  </div>
</nav>

<div class="container">

  <?php if ($flashSuccess): ?>
    <div class="alert alert-success mt-2">✅ <?= htmlspecialchars($flashSuccess) ?></div>
  <?php endif; ?>

  <!-- Hero -->
  <section class="hero">
    <div class="hero-title">Welcome back,<br><?= $userName ?>! 👋</div>
    <div class="hero-sub">
      Choose a quiz below, test your knowledge, and earn your personalized certificate instantly.
    </div>
    <a href="dashboard.php" class="btn btn-secondary">View My Progress →</a>
  </section>

  <!-- Quiz Listing -->
  <section class="section">
    <div class="section-title">📚 Available Quizzes</div>

    <div id="quizGrid" class="grid-3">
      <!-- Loaded via JS -->
      <div class="empty-state" style="grid-column:1/-1">
        <div class="spinner" style="width:30px;height:30px;margin:0 auto 1rem;"></div>
        <div class="empty-state-text">Loading quizzes…</div>
      </div>
    </div>
  </section>

</div>

<script>
const BASE = '<?= BASE_URL ?>';

async function loadQuizzes() {
  const grid = document.getElementById('quizGrid');
  try {
    const res = await fetch(BASE + '/quiz_api.php?action=list');
    const quizzes = await res.json();

    if (!quizzes.length) {
      grid.innerHTML = '<div class="empty-state" style="grid-column:1/-1"><div class="empty-state-icon">📭</div><div>No quizzes available yet.</div></div>';
      return;
    }

    grid.innerHTML = quizzes.map(q => {
      const mins = Math.floor(q.time_limit / 60);
      return `
        <div class="quiz-card">
          <div>
            <div class="quiz-card-title">${escHtml(q.title)}</div>
            ${q.passed > 0 ? '<span class="badge badge-pass" style="margin-top:0.4rem">✓ Passed</span>' : ''}
          </div>
          <div class="quiz-card-desc">${escHtml(q.description)}</div>
          <div class="quiz-card-meta">
            <span>⏱ ${mins} min</span>
            <span>❓ ${q.question_count} questions</span>
            <span>🎯 Pass: ${q.pass_score}%</span>
          </div>
          <a href="${BASE}/quiz.php?quiz_id=${q.id}" class="btn btn-primary btn-sm">Start Quiz →</a>
        </div>`;
    }).join('');
  } catch(e) {
    grid.innerHTML = '<div class="alert alert-error" style="grid-column:1/-1">Failed to load quizzes. Please refresh.</div>';
  }
}

function escHtml(str) {
  const d = document.createElement('div');
  d.textContent = str;
  return d.innerHTML;
}

loadQuizzes();
</script>
</body>
</html>
