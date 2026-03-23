<?php
// ============================================================
//  Database Configuration
// ============================================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');          // Change if your MySQL has a password
define('DB_NAME', 'quizcert_db');
define('SITE_NAME', 'QuizCert Pro');
define('BASE_URL', 'http://localhost/website');

function getDB(): mysqli {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            http_response_code(500);
            die(json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]));
        }
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}

function redirect(string $url): void {
    header("Location: $url");
    exit;
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        redirect(BASE_URL . '/index.php');
    }
}

session_start();
