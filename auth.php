<?php
/**
 * Nournia Shop — Authentication Helper
 * Session management & role-based access control
 */

function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function isLoggedIn(): bool {
    startSession();
    return isset($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if ($_SESSION['user_role'] !== 'admin') {
        header('Location: index.php');
        exit;
    }
}

function isAdmin(): bool {
    startSession();
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function getCurrentUser(): array {
    startSession();
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['user_username'] ?? '',
        'email' => $_SESSION['user_email'] ?? '',
        'role' => $_SESSION['user_role'] ?? 'user',
    ];
}

function loginUser(array $user): void {
    startSession();
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_username'] = $user['username'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
}

function logout(): void {
    startSession();
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}
