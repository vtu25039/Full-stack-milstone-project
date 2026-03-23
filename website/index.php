<?php
require_once __DIR__ . '/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(BASE_URL . '/home.php');
}

$activeTab = $_GET['tab'] ?? 'login';
$flashError   = $_SESSION['flash_error']   ?? null;
$flashSuccess  = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_error'], $_SESSION['flash_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — <?= SITE_NAME ?></title>
  <meta name="description" content="Sign in or create your account on QuizCert Pro — the automated quiz and certification platform.">
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="auth-bg">
  <div class="auth-card">

    <div class="auth-logo">
      <div class="auth-logo-text">⚡ <?= SITE_NAME ?></div>
      <div class="auth-logo-sub">Automated Quiz &amp; Certification Platform</div>
    </div>

    <?php if ($flashError): ?>
      <div class="alert alert-error">⚠️ <?= htmlspecialchars($flashError) ?></div>
    <?php endif; ?>
    <?php if ($flashSuccess): ?>
      <div class="alert alert-success">✅ <?= htmlspecialchars($flashSuccess) ?></div>
    <?php endif; ?>

    <!-- Tabs -->
    <div class="auth-tabs">
      <button class="auth-tab <?= $activeTab === 'login'    ? 'active' : '' ?>" onclick="switchTab('login')">Sign In</button>
      <button class="auth-tab <?= $activeTab === 'register' ? 'active' : '' ?>" onclick="switchTab('register')">Register</button>
    </div>

    <!-- Login Form -->
    <form id="loginForm" class="auth-form <?= $activeTab === 'login' ? 'active' : '' ?>" method="POST" action="auth.php">
      <input type="hidden" name="action" value="login">
      <div class="form-group">
        <label class="form-label" for="login_email">Email Address</label>
        <input id="login_email" class="form-control" type="email" name="email" placeholder="you@example.com" required>
      </div>
      <div class="form-group">
        <label class="form-label" for="login_password">Password</label>
        <input id="login_password" class="form-control" type="password" name="password" placeholder="Your password" required>
      </div>
      <button type="submit" class="btn btn-primary btn-full" style="margin-top:0.5rem">
        Sign In →
      </button>
    </form>

    <!-- Register Form -->
    <form id="registerForm" class="auth-form <?= $activeTab === 'register' ? 'active' : '' ?>" method="POST" action="auth.php">
      <input type="hidden" name="action" value="register">
      <div class="form-group">
        <label class="form-label" for="reg_name">Full Name</label>
        <input id="reg_name" class="form-control" type="text" name="name" placeholder="John Doe" required>
      </div>
      <div class="form-group">
        <label class="form-label" for="reg_email">Email Address</label>
        <input id="reg_email" class="form-control" type="email" name="email" placeholder="you@example.com" required>
      </div>
      <div class="form-group">
        <label class="form-label" for="reg_password">Password</label>
        <input id="reg_password" class="form-control" type="password" name="password" placeholder="Min 6 characters" required>
      </div>
      <div class="form-group">
        <label class="form-label" for="reg_confirm">Confirm Password</label>
        <input id="reg_confirm" class="form-control" type="password" name="confirm" placeholder="Repeat password" required>
      </div>
      <button type="submit" class="btn btn-primary btn-full" style="margin-top:0.5rem">
        Create Account →
      </button>
    </form>

  </div>
</div>

<script>
function switchTab(tab) {
  document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.auth-form').forEach(f => f.classList.remove('active'));
  document.querySelector(`.auth-tab:nth-child(${tab === 'login' ? 1 : 2})`).classList.add('active');
  document.getElementById(tab === 'login' ? 'loginForm' : 'registerForm').classList.add('active');
}
</script>
</body>
</html>
