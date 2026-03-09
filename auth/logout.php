<?php
// auth/logout.php

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/db.php';

if (isLoggedIn()) {
    // Log the logout event (non-critical)
    try {
        $pdo = getDB();
        $pdo->prepare(
            'INSERT INTO sessions_log (user_id, ip_address, user_agent, action)
             VALUES (?, ?, ?, ?)'
        )->execute([
            $_SESSION['user_id'],
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            'logout',
        ]);
    } catch (Throwable) { /* non-critical */ }
}

// ── Destroy session completely ────────────────────────────────
$_SESSION = [];

// Delete the session cookie from the browser
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

session_destroy();

// ── Redirect ──────────────────────────────────────────────────
header('Location: /auth/login.php');
exit;
