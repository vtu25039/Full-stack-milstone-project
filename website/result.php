<?php
require_once __DIR__ . '/config.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Result — <?= SITE_NAME ?></title>
  <link rel="stylesheet" href="css/style.css">
  <style>
    /* Confetti colours */
    .confetti-canvas { position:fixed;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:999; }
  </style>
</head>
<body>

<nav class="navbar">
  <a href="home.php" class="navbar-brand">⚡ <?= SITE_NAME ?></a>
  <div class="navbar-nav">
    <a href="home.php"      class="nav-link">🏠 Home</a>
    <a href="dashboard.php" class="nav-link">📊 Dashboard</a>
  </div>
</nav>

<canvas class="confetti-canvas" id="confettiCanvas"></canvas>

<div class="result-wrapper" id="resultWrapper" style="display:none">

  <div id="resultStatusBanner"></div>

  <!-- Score Ring -->
  <div class="score-ring-wrap">
    <svg class="score-ring-svg" width="200" height="200" viewBox="0 0 200 200">
      <circle class="score-ring-track" cx="100" cy="100" r="84" stroke-width="12"/>
      <circle class="score-ring-fill" id="scoreRingFill"
              cx="100" cy="100" r="84" stroke-width="12"
              stroke="#6c43ff"
              stroke-dasharray="527.79"
              stroke-dashoffset="527.79"/>
    </svg>
    <div class="score-ring-label">
      <div class="score-number" id="scoreNum">0</div>
      <div class="score-pct">Score</div>
    </div>
  </div>

  <div class="result-status" id="resultStatus"></div>
  <p id="resultMsg" style="color:var(--text-secondary);margin-bottom:1.5rem"></p>

  <div class="result-details" id="resultDetails"></div>

  <div class="result-actions" id="resultActions"></div>

</div>

<!-- No JS result data fallback -->
<div class="result-wrapper" id="noResult" style="display:none;text-align:center">
  <div class="alert alert-info">No result data found. <a href="home.php">← Take a quiz first</a></div>
</div>

<script>
const BASE = '<?= BASE_URL ?>';
const CIRCUMFERENCE = 527.79;  // 2 * π * 84

document.addEventListener('DOMContentLoaded', () => {
  const raw = sessionStorage.getItem('quizResult');
  if (!raw) { document.getElementById('noResult').style.display='block'; return; }
  sessionStorage.removeItem('quizResult');
  const r = JSON.parse(raw);
  renderResult(r);
});

function renderResult(r) {
  document.getElementById('resultWrapper').style.display = 'block';

  const passed = r.passed;
  const score  = parseFloat(r.score);

  // Status banner
  document.getElementById('resultStatus').textContent = passed ? '🎉 Congratulations! You Passed!' : '😔 Better Luck Next Time';
  document.getElementById('resultStatus').className   = 'result-status ' + (passed ? 'pass' : 'fail');
  document.getElementById('resultMsg').textContent    = passed
    ? `You scored ${score.toFixed(1)}% — above the passing threshold of ${r.pass_score}%. Your certificate is ready!`
    : `You scored ${score.toFixed(1)}% — the pass score is ${r.pass_score}%. Review and try again!`;

  // Animate score ring
  setTimeout(() => {
    const offset = CIRCUMFERENCE - (score / 100) * CIRCUMFERENCE;
    const fill   = document.getElementById('scoreRingFill');
    fill.style.strokeDashoffset = offset;
    fill.style.stroke = passed ? '#22c55e' : '#ef4444';
  }, 200);

  // Animate score number
  let cur = 0;
  const target = Math.round(score * 10) / 10;  // 1dp
  const step   = Math.max(0.3, target / 60);
  const t = setInterval(() => {
    cur = Math.min(cur + step, target);
    document.getElementById('scoreNum').textContent = cur.toFixed(1) + '%';
    if (cur >= target) clearInterval(t);
  }, 16);

  // Details grid
  document.getElementById('resultDetails').innerHTML = `
    <div><div class="result-detail-val">${r.correct} / ${r.total}</div><div class="result-detail-key">Correct</div></div>
    <div><div class="result-detail-val">${score.toFixed(1)}%</div><div class="result-detail-key">Score</div></div>
    <div><div class="result-detail-val">${passed ? '✅' : '❌'}</div><div class="result-detail-key">${passed ? 'Passed' : 'Failed'}</div></div>
  `;

  // Action buttons
  let actions = `<a href="${BASE}/home.php" class="btn btn-secondary">← Home</a>
                 <a href="${BASE}/dashboard.php" class="btn btn-secondary">📊 Dashboard</a>`;
  if (passed && r.cert_uuid) {
    actions += `<a href="${BASE}/certificate_api.php?cert_uuid=${encodeURIComponent(r.cert_uuid)}"
                   target="_blank" class="btn btn-gold btn-lg">⬇ Download Certificate</a>`;
  }
  document.getElementById('resultActions').innerHTML = actions;

  // Confetti on pass
  if (passed) launchConfetti();
}

/* ── Simple confetti engine ────────────────────────────────────── */
function launchConfetti() {
  const canvas = document.getElementById('confettiCanvas');
  const ctx    = canvas.getContext('2d');
  canvas.width  = window.innerWidth;
  canvas.height = window.innerHeight;

  const colors = ['#6c43ff','#00d4ff','#f4c842','#22c55e','#ec4899','#f97316'];
  const pieces = Array.from({length: 160}, () => ({
    x: Math.random() * canvas.width,
    y: Math.random() * -canvas.height,
    r: Math.random() * 8 + 4,
    d: Math.random() * 3 + 1,
    c: colors[Math.floor(Math.random() * colors.length)],
    tilt: Math.random() * 0.6 - 0.3,
    angle: Math.random() * Math.PI * 2,
  }));

  let frame = 0;
  function draw() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    pieces.forEach(p => {
      p.y  += p.d + Math.sin(p.angle) * 1.5;
      p.x  += Math.sin(p.angle) * 2;
      p.angle += 0.05;
      ctx.save();
      ctx.fillStyle = p.c;
      ctx.beginPath();
      ctx.ellipse(p.x, p.y, p.r, p.r / 2, p.tilt, 0, Math.PI * 2);
      ctx.fill();
      ctx.restore();
      if (p.y > canvas.height) { p.y = -10; p.x = Math.random() * canvas.width; }
    });
    frame++;
    if (frame < 300) requestAnimationFrame(draw);
    else ctx.clearRect(0,0,canvas.width,canvas.height);
  }
  draw();
}
</script>
</body>
</html>
