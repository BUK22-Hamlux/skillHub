<?php
// config/app.php – Application-wide settings
// ─────────────────────────────────────────────────────────────
// BASE_PATH: the subfolder your app lives in under localhost.
// Examples:
//   localhost/skillhub/  →  '/skillhub'
//   localhost/           →  ''   (empty string)
// ─────────────────────────────────────────────────────────────
define('BASE_PATH', '/skillhub');

/**
 * Prepend BASE_PATH to any internal URL.
 * Usage: url('/auth/login.php')  →  '/skillhub/auth/login.php'
 */
function url(string $path = ''): string
{
    return BASE_PATH . '/' . ltrim($path, '/');
}
