<?php
require_once __DIR__ . '/config.php';

// Безопасный старт сессии — без дублирования
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/index.php');
        exit;
    }
}

function requireRole(string ...$roles): void {
    requireLogin();
    if (!in_array($_SESSION['role'], $roles)) {
        header('Location: ' . SITE_URL . '/dashboard.php');
        exit;
    }
}

function currentUser(): array {
    return [
        'id'        => $_SESSION['user_id'],
        'full_name' => $_SESSION['full_name'],
        'role'      => $_SESSION['role'],
        'email'     => $_SESSION['email'],
    ];
}

function login(string $email, string $password): bool {
    $pdo  = getPDO();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([trim($email)]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role']      = $user['role'];
        $_SESSION['email']     = $user['email'];
        return true;
    }
    return false;
}

function logout(): void {
    session_destroy();
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}