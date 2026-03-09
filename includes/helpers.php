<?php
// includes/helpers.php

require_once __DIR__ . '/../config/app.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
    ]);
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id'], $_SESSION['user_role']);
}

function requireLogin(?string $role = null): void
{
    if (!isLoggedIn()) {
        redirect(url('auth/login.php'));
    }
    if ($role !== null && $_SESSION['user_role'] !== $role) {
        redirect(dashboardUrl($_SESSION['user_role']));
    }
}

function dashboardUrl(string $role): string
{
    $map = [
        'client'     => url('dashboard/client.php'),
        'freelancer' => url('dashboard/freelancer.php'),
    ];
    return $map[$role] ?? url('auth/login.php');
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function renderFlash(): void
{
    $flash = getFlash();
    if (!$flash) return;
    $type = e($flash['type']);
    $msg  = e($flash['message']);
    echo "<div class=\"flash flash--{$type}\" role=\"alert\">{$msg}</div>";
}
