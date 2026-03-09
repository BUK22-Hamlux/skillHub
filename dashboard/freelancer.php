<?php
// dashboard/freelancer.php  (Phase 2 – with service stats)

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/ServiceModel.php';
require_once __DIR__ . '/../orders/OrderModel.php';

requireLogin('freelancer');

$pdo    = getDB();
$userId = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare(
    'SELECT u.full_name, u.email, u.created_at,
            fp.headline, fp.skills, fp.hourly_rate, fp.availability, fp.portfolio_url
     FROM users u
     LEFT JOIN freelancer_profiles fp ON fp.user_id = u.id
     WHERE u.id = ? LIMIT 1'
);
$stmt->execute([$userId]);
$profile = $stmt->fetch();

$model          = new ServiceModel($pdo);
$activeServices = $model->countActive($userId);
$allServices    = $model->getByFreelancer($userId);
$recentServices = array_slice($allServices, 0, 3);

$orderModel = new OrderModel($pdo);
$orderCounts = $orderModel->countByStatus($userId, 'freelancer');
$recentOrdersF = array_slice($orderModel->getByFreelancer($userId), 0, 3);
$stats = ['active_orders' => $orderCounts['pending'] + $orderCounts['accepted'] + $orderCounts['in_progress'], 'completed' => $orderCounts['completed'], 'total_earned' => '0.00'];

$availabilityLabel = [
    'available'   => ['label' => 'Available',  'class' => 'badge--success'],
    'busy'        => ['label' => 'Busy',        'class' => 'badge--warning'],
    'unavailable' => ['label' => 'Unavailable', 'class' => 'badge--danger'],
];
$avail = $availabilityLabel[$profile['availability'] ?? 'unavailable'];
$skillTags = $profile['skills'] ? array_map('trim', explode(',', $profile['skills'])) : [];

$pageTitle = 'Freelancer Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="dashboard">
    <div class="dashboard__banner">
        <div>
            <h1>Welcome back, <?= e($profile['full_name']) ?>!</h1>
            <p class="text-muted">Member since <?= date('F Y', strtotime($profile['created_at'])) ?><?= $profile['headline'] ? ' · ' . e($profile['headline']) : '' ?></p>
        </div>
        <div style="display:flex;gap:.5rem;align-items:center;">
            <span class="badge <?= $avail['class'] ?>"><?= $avail['label'] ?></span>
            <span class="badge badge--freelancer">Freelancer</span>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><span class="stat-card__number"><?= $activeServices ?></span><span class="stat-card__label">Active Services</span></div>
        <div class="stat-card"><span class="stat-card__number"><?= $stats['active_orders'] ?></span><span class="stat-card__label">Active Orders</span></div>
        <div class="stat-card"><span class="stat-card__number"><?= $stats['completed'] ?></span><span class="stat-card__label">Completed</span></div>
        <div class="stat-card"><span class="stat-card__number">$<?= number_format((float)$stats['total_earned'], 2) ?></span><span class="stat-card__label">Total Earned</span></div>
    </div>

    <div class="dashboard__grid">
        <section class="card">
            <h2 class="card__title">My Profile</h2>
            <dl class="profile-list">
                <dt>Name</dt><dd><?= e($profile['full_name']) ?></dd>
                <dt>Email</dt><dd><?= e($profile['email']) ?></dd>
                <dt>Rate</dt><dd><?= $profile['hourly_rate'] ? '$' . number_format((float)$profile['hourly_rate'], 2) . ' / hr' : '<em>Not set</em>' ?></dd>
                <dt>Skills</dt>
                <dd><?php if ($skillTags): foreach ($skillTags as $tag): ?><span class="skill-tag"><?= e($tag) ?></span><?php endforeach; else: ?><em>No skills listed</em><?php endif; ?></dd>
                <?php if ($profile['portfolio_url']): ?><dt>Portfolio</dt><dd><a href="<?= e($profile['portfolio_url']) ?>" target="_blank" rel="noopener"><?= e($profile['portfolio_url']) ?></a></dd><?php endif; ?>
            </dl>
        </section>
        <section class="card">
            <h2 class="card__title">Quick Actions</h2>
            <ul class="action-list">
                <li><a href="<?= url('services/index.php') ?>" class="btn btn--outline btn--full">🛠️ Manage My Services <span class="action-badge"><?= $activeServices ?></span></a></li>
                <li><a href="<?= url('services/create.php') ?>" class="btn btn--primary btn--full">＋ Add New Service</a></li>
                <li><a href="<?= url('orders/index.php') ?>" class="btn btn--outline btn--full">📦 My Orders <span class="action-badge"><?= array_sum($orderCounts) ?></span></a></li>
                <li><a href="<?= url('orders/index.php') ?>?status=pending" class="btn btn--outline btn--full">⏳ Pending Orders <span class="action-badge"><?= $orderCounts['pending'] ?></span></a></li>
            </ul>
        </section>
    </div>

    <?php if (!empty($recentServices)): ?>
    <section class="card" style="margin-top:1.25rem;">
        <div class="card__title-row">
            <h2 class="card__title">Recent Services</h2>
            <a href="<?= url('services/index.php') ?>" class="btn btn--outline btn--sm">View all</a>
        </div>
        <div class="mini-service-list">
            <?php foreach ($recentServices as $svc): ?>
            <div class="mini-service-row">
                <div class="mini-service-row__thumb"><?php if ($svc['image_path']): ?><img src="<?= e($svc['image_path']) ?>" alt=""><?php else: ?><div class="mini-service-row__placeholder">🖼️</div><?php endif; ?></div>
                <div class="mini-service-row__info"><strong><?= e($svc['title']) ?></strong><span class="text-muted"><?= e($svc['category_name'] ?? 'Uncategorized') ?></span></div>
                <div class="mini-service-row__right">
                    <span class="mini-service-row__price">$<?= number_format((float)$svc['price'], 2) ?></span>
                    <span class="badge badge--<?= $svc['status'] === 'active' ? 'success' : 'warning' ?>"><?= ucfirst($svc['status']) ?></span>
                    <a href="<?= url('services/edit.php') ?>?id=<?= $svc['id'] ?>" class="btn btn--outline btn--sm">Edit</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php else: ?>
    <div class="empty-state" style="margin-top:1.25rem;">
        <div class="empty-state__icon">🛠️</div>
        <h2>No services yet</h2>
        <p>Create your first service to start attracting clients.</p>
        <a href="<?= url('services/create.php') ?>" class="btn btn--primary">Add Your First Service</a>
    </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
