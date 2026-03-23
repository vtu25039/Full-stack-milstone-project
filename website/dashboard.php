<?php
require_once __DIR__ . '/config.php';
requireLogin();
$userName = htmlspecialchars($_SESSION['user_name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — <?= SITE_NAME ?></title>
  <meta name="description" content="View your quiz history, scores, and certificates on QuizCert Pro dashboard.">
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

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

  <div class="page-header">
    <div class="page-title">📊 My Dashboard</div>
    <div class="page-subtitle">Hello, <?= $userName ?>! Here's your performance overview.</div>
  </div>

  <!-- Stats Cards -->
  <div class="grid-4 mb-3" id="statsGrid">
    <div class="stat-card"><div class="stat-icon">📋</div><div class="stat-value" id="s-total">—</div><div class="stat-label">Quizzes Taken</div></div>
    <div class="stat-card"><div class="stat-icon">📈</div><div class="stat-value" id="s-avg">—</div><div class="stat-label">Avg Score</div></div>
    <div class="stat-card"><div class="stat-icon">✅</div><div class="stat-value" id="s-passed">—</div><div class="stat-label">Passed</div></div>
    <div class="stat-card"><div class="stat-icon">🏆</div><div class="stat-value" id="s-certs">—</div><div class="stat-label">Certificates</div></div>
  </div>

  <!-- History Table -->
  <div class="section">
    <div class="section-title">🗂️ Attempt History</div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Quiz</th>
            <th>Score</th>
            <th>Status</th>
            <th>Date</th>
            <th>Certificate</th>
          </tr>
        </thead>
        <tbody id="historyBody">
          <tr><td colspan="6" style="text-align:center;padding:3rem;color:var(--text-muted)"><div class="spinner" style="width:24px;height:24px;margin:0 auto 0.5rem"></div>Loading…</td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="mt-3 mb-3">
    <a href="home.php" class="btn btn-secondary">← Browse Quizzes</a>
  </div>

</div>

<script>
const BASE = '<?= BASE_URL ?>';

async function loadStats() {
  const res = await fetch(BASE + '/dashboard_api.php?action=stats');
  const s = await res.json();
  animateNumber('s-total',  s.total_attempts);
  animateNumber('s-avg',    s.avg_score, '%');
  animateNumber('s-passed', s.passed_count);
  animateNumber('s-certs',  s.certificates);
}

function animateNumber(id, target, suffix='') {
  const el = document.getElementById(id);
  let cur = 0;
  const step = Math.ceil(Math.max(1, target / 40));
  const t = setInterval(() => {
    cur = Math.min(cur + step, Math.round(target));
    el.textContent = cur + suffix;
    if (cur >= Math.round(target)) clearInterval(t);
  }, 30);
}

async function loadHistory() {
  const tbody = document.getElementById('historyBody');
  try {
    const res = await fetch(BASE + '/dashboard_api.php?action=history');
    const rows = await res.json();
    if (!rows.length) {
      tbody.innerHTML = '<tr><td colspan="6"><div class="empty-state"><div class="empty-state-icon">📭</div><div class="empty-state-text">No attempts yet. <a href="' + BASE + '/home.php" style="color:var(--accent-2)">Take a quiz!</a></div></div></td></tr>';
      return;
    }
    tbody.innerHTML = rows.map((r, i) => {
      const date  = new Date(r.completed_at).toLocaleDateString('en-IN', {day:'2-digit',month:'short',year:'numeric'});
      const badge = r.passed
        ? '<span class="badge badge-pass">✅ Pass</span>'
        : '<span class="badge badge-fail">❌ Fail</span>';
      const cert  = r.cert_uuid
        ? `<a href="${BASE}/certificate_api.php?cert_uuid=${encodeURIComponent(r.cert_uuid)}" target="_blank" class="btn btn-gold btn-sm">⬇ PDF</a>`
        : '—';
      return `<tr>
        <td>${i+1}</td>
        <td><strong>${escHtml(r.quiz_title)}</strong></td>
        <td style="font-weight:700;color:${r.score >= r.pass_score ? 'var(--success)' : 'var(--danger)'}">${parseFloat(r.score).toFixed(1)}%</td>
        <td>${badge}</td>
        <td>${date}</td>
        <td>${cert}</td>
      </tr>`;
    }).join('');
  } catch(e) {
    tbody.innerHTML = '<tr><td colspan="6" class="alert alert-error">Failed to load history.</td></tr>';
  }
}

function escHtml(str) { const d=document.createElement('div'); d.textContent=str; return d.innerHTML; }

loadStats();
loadHistory();
</script>
</body>
</html>
