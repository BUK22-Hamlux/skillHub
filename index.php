<?php
require_once __DIR__ . '/includes/helpers.php';

if (isLoggedIn()) {
    redirect(dashboardUrl($_SESSION['user_role']));
}

$pageTitle = 'Find Talent. Find Work.';
require_once __DIR__ . '/includes/header.php';
?>

<div class="hero">
    <h1>Work without <span>limits.</span></h1>
    <p>SkillHub connects world-class freelancers with clients who need exceptional work done — fast, securely, and fairly.</p>
    <div class="hero__cta">
        <a href="<?= url('auth/register.php') ?>" class="btn btn--accent btn--lg">Get started free →</a>
        <a href="<?= url('marketplace/index.php') ?>" class="btn btn--outline btn--lg">Browse services</a>
    </div>

    <div class="hero__stats">
        <div class="hero__stat"><strong>500+</strong><span>Services available</span></div>
        <div class="hero__stat"><strong>200+</strong><span>Active freelancers</span></div>
        <div class="hero__stat"><strong>98%</strong><span>Satisfaction rate</span></div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
