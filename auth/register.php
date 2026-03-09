<?php
// auth/register.php

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/db.php';

// Already logged in → go to dashboard
if (isLoggedIn()) {
    redirect(dashboardUrl($_SESSION['user_role']));
}

$errors   = [];
$formData = ['full_name' => '', 'email' => '', 'role' => 'client'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ── 1. Collect & sanitize input ──────────────────────────
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email']     ?? '');
    $password = $_POST['password']       ?? '';
    $confirm  = $_POST['confirm']        ?? '';
    $role     = $_POST['role']           ?? 'client';

    $formData = compact('fullName', 'email', 'role');

    // ── 2. Validate ──────────────────────────────────────────
    if ($fullName === '' || mb_strlen($fullName) < 2) {
        $errors['full_name'] = 'Full name must be at least 2 characters.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }

    if (mb_strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $errors['confirm'] = 'Passwords do not match.';
    }

    if (!in_array($role, ['client', 'freelancer'], true)) {
        $errors['role'] = 'Invalid role selected.';
    }

    // ── 3. Check for duplicate email ─────────────────────────
    if (empty($errors)) {
        $pdo  = getDB();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $errors['email'] = 'An account with this email already exists.';
        }
    }

    // ── 4. Insert user ───────────────────────────────────────
    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        $pdo->beginTransaction();
        try {
            $ins = $pdo->prepare(
                'INSERT INTO users (full_name, email, password, role)
                 VALUES (?, ?, ?, ?)'
            );
            $ins->execute([$fullName, $email, $hash, $role]);
            $userId = (int) $pdo->lastInsertId();

            // Create role-specific profile row
            if ($role === 'client') {
                $pdo->prepare(
                    'INSERT INTO client_profiles (user_id) VALUES (?)'
                )->execute([$userId]);
            } else {
                $pdo->prepare(
                    'INSERT INTO freelancer_profiles (user_id) VALUES (?)'
                )->execute([$userId]);
            }

            $pdo->commit();

            setFlash('success', 'Account created! Please log in.');
            redirect(url('auth/login.php'));

        } catch (Throwable $e) {
            $pdo->rollBack();
            error_log('[SkillHub Register] ' . $e->getMessage());
            $errors['general'] = 'Registration failed. Please try again.';
        }
    }
}

$pageTitle = 'Create Account';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-card">
    <div class="auth-card__header">
        <div class="auth-card__icon">✨</div>
        <h1>Join SkillHub</h1>
        <p class="auth-card__subtitle">Create your free account today</p>
    </div>

    <?php if (!empty($errors['general'])): ?>
        <p class="field-error"><?= e($errors['general']) ?></p>
    <?php endif; ?>

    <form method="post" action="<?= url('auth/register.php')?>" id="registerForm" novalidate>" novalidate>

        <div class="form-group <?= isset($errors['full_name']) ? 'has-error' : '' ?>">
            <label for="full_name">Full Name</label>
            <input
                type="text" id="regName" name="full_name"
                value="<?= e($formData['full_name'] ?? '') ?>"
                autocomplete="name" required
            >
            <?php if (isset($errors['full_name'])): ?>
                <span class="field-error"><?= e($errors['full_name']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-group <?= isset($errors['email']) ? 'has-error' : '' ?>">
            <label for="email">Email Address</label>
            <input
                type="email" id="regEmail" name="email"
                value="<?= e($formData['email'] ?? '') ?>"
                autocomplete="email" required
            >
            <?php if (isset($errors['email'])): ?>
                <span class="field-error"><?= e($errors['email']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-group <?= isset($errors['password']) ? 'has-error' : '' ?>">
            <label for="password">Password</label>
            <div class="input-password-wrap">
                <input type="password" id="regPassword" name="password" autocomplete="new-password" required>
            </div>
            <?php if (isset($errors['password'])): ?>
                <span class="field-error"><?= e($errors['password']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-group <?= isset($errors['confirm']) ? 'has-error' : '' ?>">
            <label for="confirm">Confirm Password</label>
            <div class="input-password-wrap">
                <input type="password" id="regConfirm" name="confirm" autocomplete="new-password" required>
            </div>
            <?php if (isset($errors['confirm'])): ?>
                <span class="field-error"><?= e($errors['confirm']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-group <?= isset($errors['role']) ? 'has-error' : '' ?>">
            <label>I want to</label>
            <div class="role-toggle">
                <label class="role-option">
                    <input
                        type="radio" name="role" value="client"
                        <?= ($formData['role'] ?? 'client') === 'client' ? 'checked' : '' ?>
                    >
                    <span>Hire Talent (Client)</span>
                </label>
                <label class="role-option">
                    <input
                        type="radio" name="role" value="freelancer"
                        <?= ($formData['role'] ?? '') === 'freelancer' ? 'checked' : '' ?>
                    >
                    <span>Find Work (Freelancer)</span>
                </label>
            </div>
        </div>

        <button type="submit" class="btn btn--primary btn--full">Create Account</button>
    </form>

    <p class="auth-switch">Already have an account? <a href="<?= url('auth/login.php') ?>">Log in</a></p>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
