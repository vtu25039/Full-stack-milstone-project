<?php
require_once __DIR__ . '/config.php';

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'register':
        handleRegister();
        break;
    case 'login':
        handleLogin();
        break;
    case 'logout':
        handleLogout();
        break;
    default:
        redirect(BASE_URL . '/index.php');
}

// ---- Register -------------------------------------------------------
function handleRegister(): void {
    $name     = trim($_POST['name']    ?? '');
    $email    = trim($_POST['email']   ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm'] ?? '');

    $errors = [];
    if (strlen($name) < 2)           $errors[] = 'Name must be at least 2 characters.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address.';
    if (strlen($password) < 6)       $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm)      $errors[] = 'Passwords do not match.';

    if ($errors) {
        $_SESSION['flash_error'] = implode(' ', $errors);
        redirect(BASE_URL . '/index.php?tab=register');
    }

    $db   = getDB();
    $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $_SESSION['flash_error'] = 'An account with this email already exists.';
        redirect(BASE_URL . '/index.php?tab=register');
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $db->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
    $stmt->bind_param('sss', $name, $email, $hash);
    $stmt->execute();

    $_SESSION['user_id']   = $db->insert_id;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_email']= $email;
    $_SESSION['flash_success'] = "Welcome, $name! Your account was created.";
    redirect(BASE_URL . '/home.php');
}

// ---- Login ----------------------------------------------------------
function handleLogin(): void {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$email || !$password) {
        $_SESSION['flash_error'] = 'Please fill in all fields.';
        redirect(BASE_URL . '/index.php?tab=login');
    }

    $db   = getDB();
    $stmt = $db->prepare('SELECT id, name, password FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $row  = $stmt->get_result()->fetch_assoc();

    if (!$row || !password_verify($password, $row['password'])) {
        $_SESSION['flash_error'] = 'Invalid email or password.';
        redirect(BASE_URL . '/index.php?tab=login');
    }

    $_SESSION['user_id']   = $row['id'];
    $_SESSION['user_name'] = $row['name'];
    $_SESSION['user_email']= $email;
    redirect(BASE_URL . '/home.php');
}

// ---- Logout ---------------------------------------------------------
function handleLogout(): void {
    session_destroy();
    redirect(BASE_URL . '/index.php');
}
