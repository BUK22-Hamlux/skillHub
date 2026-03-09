<?php
// includes/header.php (v2 — redesigned)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'SkillHub') ?> – SkillHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="<?= url('assets/css/main.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/services.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/marketplace.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/ui.css') ?>">
</head>
<body>

<header class="site-header">
    <div class="container">
        <a class="logo" href="<?= url() ?>">Skill<span>Hub</span></a>

        <!-- Hamburger toggle -->
        <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>

        <!-- Nav links -->
        <nav id="mainNav">
            <a href="<?= url('marketplace/index.php') ?>">Marketplace</a>

            <?php if (isLoggedIn()): ?>
                <a href="<?= url('orders/index.php') ?>">My Orders</a>
                <?php if ($_SESSION['user_role'] === 'freelancer'): ?>
                    <a href="<?= url('services/index.php') ?>">My Services</a>
                <?php endif; ?>
                <a href="<?= dashboardUrl($_SESSION['user_role']) ?>">
                    <span class="nav-avatar"><?= mb_strtoupper(mb_substr($_SESSION['user_name'], 0, 1)) ?></span>
                    <?= e(explode(' ', $_SESSION['user_name'])[0]) ?>
                </a>
                <a href="<?= url('auth/logout.php') ?>" class="btn btn--outline btn--sm">Log out</a>
            <?php else: ?>
                <a href="<?= url('auth/login.php') ?>">Log in</a>
                <a href="<?= url('auth/register.php') ?>" class="btn btn--accent btn--sm">Sign up free</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="container">
<?php renderFlash(); ?>
