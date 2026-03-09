<?php
// auth/login.php

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/db.php';

// Already logged in → go to dashboard
if (isLoggedIn()) {
    redirect(dashboardUrl($_SESSION['user_role']));
}

$errors  = [];
$email   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ── 1. Collect input ─────────────────────────────────────
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';

    // ── 2. Basic validation ───────────────────────────────────
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }
    if ($password === '') {
        $errors['password'] = 'Password is required.';
    }

    // ── 3. Look up user ───────────────────────────────────────
    if (empty($errors)) {
        $pdo  = getDB();
        $stmt = $pdo->prepare(
            'SELECT id, full_name, email, password, role, is_active
             FROM users
             WHERE email = ?
             LIMIT 1'
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Use a constant-time comparison path even for unknown email
        $hashToCheck = $user['password'] ?? '$2y$12$invalidhashpadding000000000000000000000000000000000000000';

        if (!$user || !password_verify($password, $hashToCheck)) {
            // Deliberate generic message – do not reveal whether email exists
            $errors['general'] = 'Invalid email or password.';
        } elseif ((int) $user['is_active'] !== 1) {
            $errors['general'] = 'Your account has been deactivated. Please contact support.';
        }
    }

    // ── 4. Start session ──────────────────────────────────────
    if (empty($errors)) {
        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);

        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role'];

        // Optional: log the login event
        try {
            $pdo->prepare(
                'INSERT INTO sessions_log (user_id, ip_address, user_agent, action)
                 VALUES (?, ?, ?, ?)'
            )->execute([
                $user['id'],
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
                'login',
            ]);
        } catch (Throwable) { /* non-critical – silently ignore */ }

        // Rehash password if bcrypt cost has changed
        if (password_needs_rehash($user['password'], PASSWORD_BCRYPT, ['cost' => 12])) {
            $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')
                ->execute([$newHash, $user['id']]);
        }

        setFlash('success', 'Welcome back, ' . $user['full_name'] . '!');
        redirect(dashboardUrl($user['role']));
    }
}

$pageTitle = 'Log In';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-card">
    <div class="auth-card__header">
        <div class="auth-card__icon">🔐</div>
        <h1>Welcome back</h1>
        <p class="auth-card__subtitle">Sign in to your SkillHub account</p>
    </div>

    <?php if (!empty($errors['general'])): ?>
        <p class="flash flash--error"><?= e($errors['general']) ?></p>
    <?php endif; ?>

    <form method="post" action="<?= url('auth/login.php')?>" id="loginForm" novalidate>" novalidate>

        <div class="form-group <?= isset($errors['email']) ? 'has-error' : '' ?>">
            <label for="email">Email Address</label>
            <input
                type="email" id="loginEmail" name="email"
                value="<?= e($email) ?>"
                autocomplete="email" required autofocus
            >
            <?php if (isset($errors['email'])): ?>
                <span class="field-error"><?= e($errors['email']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-group <?= isset($errors['password']) ? 'has-error' : '' ?>">
            <label for="password">Password</label>
            <div class="input-password-wrap">
                <input type="password" id="loginPassword" name="password" autocomplete="current-password" required>
            </div>
            <?php if (isset($errors['password'])): ?>
                <span class="field-error"><?= e($errors['password']) ?></span>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn btn--primary btn--full">Log In</button>
    </form>

    <p class="auth-switch">No account yet? <a href="<?= url('auth/register.php') ?>">Register</a></p>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
