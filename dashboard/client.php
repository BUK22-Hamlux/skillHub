<?php
// dashboard/client.php (Phase 3 – with live order stats)

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../orders/OrderModel.php';

requireLogin('client');

$pdo    = getDB();
$userId = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare(
    'SELECT u.full_name, u.email, u.created_at,
            cp.company_name, cp.website, cp.industry
     FROM users u
     LEFT JOIN client_profiles cp ON cp.user_id = u.id
     WHERE u.id = ? LIMIT 1'
);
$stmt->execute([$userId]);
$profile = $stmt->fetch();

$orderModel   = new OrderModel($pdo);
$counts       = $orderModel->countByStatus($userId, 'client');
$recentOrders = array_slice($orderModel->getByClient($userId), 0, 3);

$statusConfig = [
    'pending'     => ['label' => 'Pending',    'class' => 'badge--warning', 'icon' => '⏳'],
    'accepted'    => ['label' => 'Accepted',   'class' => 'badge--info',    'icon' => '✅'],
    'in_progress' => ['label' => 'In Progress','class' => 'badge--primary', 'icon' => '🔧'],
    'completed'   => ['label' => 'Completed',  'class' => 'badge--success', 'icon' => '🎉'],
    'cancelled'   => ['label' => 'Cancelled',  'class' => 'badge--danger',  'icon' => '❌'],
];

$pageTitle = 'Client Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="dashboard">
    <div class="dashboard__banner">
        <div>
            <h1>Welcome back, <?= e($profile['full_name']) ?>!</h1>
            <p class="text-muted">Member since <?= date('F Y', strtotime($profile['created_at'])) ?><?= $profile['company_name'] ? ' · ' . e($profile['company_name']) : '' ?></p>
        </div>
        <span class="badge badge--client">Client</span>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><span class="stat-card__number"><?= array_sum($counts) ?></span><span class="stat-card__label">Total Orders</span></div>
        <div class="stat-card"><span class="stat-card__number"><?= $counts['pending'] + $counts['accepted'] + $counts['in_progress'] ?></span><span class="stat-card__label">Active Orders</span></div>
        <div class="stat-card"><span class="stat-card__number"><?= $counts['completed'] ?></span><span class="stat-card__label">Completed</span></div>
    </div>

    <div class="dashboard__grid">
        <section class="card">
            <h2 class="card__title">My Profile</h2>
            <dl class="profile-list">
                <dt>Name</dt>    <dd><?= e($profile['full_name']) ?></dd>
                <dt>Email</dt>   <dd><?= e($profile['email']) ?></dd>
                <dt>Company</dt> <dd><?= $profile['company_name'] ? e($profile['company_name']) : '<em>Not set</em>' ?></dd>
                <dt>Industry</dt><dd><?= $profile['industry'] ? e($profile['industry']) : '<em>Not set</em>' ?></dd>
                <?php if ($profile['website']): ?>
                <dt>Website</dt><dd><a href="<?= e($profile['website']) ?>" target="_blank" rel="noopener"><?= e($profile['website']) ?></a></dd>
                <?php endif; ?>
            </dl>
        </section>
        <section class="card">
            <h2 class="card__title">Quick Actions</h2>
            <ul class="action-list">
                <li><a href="<?= url('marketplace/index.php') ?>" class="btn btn--primary btn--full">🔍 Browse Marketplace</a></li>
                <li><a href="<?= url('orders/index.php') ?>" class="btn btn--outline btn--full">📦 My Orders <span class="action-badge"><?= array_sum($counts) ?></span></a></li>
                <li><a href="<?= url('orders/index.php') ?>?status=pending" class="btn btn--outline btn--full">⏳ Pending Orders <span class="action-badge"><?= $counts['pending'] ?></span></a></li>
            </ul>
        </section>
    </div>

    <?php if (!empty($recentOrders)): ?>
    <section class="card" style="margin-top:1.25rem;">
        <div class="card__title-row">
            <h2 class="card__title">Recent Orders</h2>
            <a href="<?= url('orders/index.php') ?>" class="btn btn--outline btn--sm">View all</a>
        </div>
        <div class="mini-service-list">
            <?php foreach ($recentOrders as $o):
                $sc = $statusConfig[$o['status']] ?? $statusConfig['pending']; ?>
            <div class="mini-service-row">
                <div class="mini-service-row__thumb"><?php if ($o['service_image']): ?><img src="<?= e($o['service_image']) ?>" alt=""><?php else: ?><div class="mini-service-row__placeholder">🛠️</div><?php endif; ?></div>
                <div class="mini-service-row__info"><strong><?= e(mb_strimwidth($o['service_title'], 0, 50, '…')) ?></strong><span class="text-muted"><?= e($o['freelancer_name']) ?></span></div>
                <div class="mini-service-row__right">
                    <span class="mini-service-row__price">$<?= number_format((float)$o['amount'], 2) ?></span>
                    <span class="badge <?= $sc['class'] ?>"><?= $sc['icon'] ?> <?= $sc['label'] ?></span>
                    <a href="<?= url('orders/detail.php') ?>?id=<?= $o['id'] ?>" class="btn btn--outline btn--sm">View</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php else: ?>
    <div class="empty-state" style="margin-top:1.25rem;">
        <div class="empty-state__icon">🛒</div>
        <h2>No orders yet</h2>
        <p>Browse the marketplace and place your first order.</p>
        <a href="<?= url('marketplace/index.php') ?>" class="btn btn--primary">Browse Marketplace</a>
    </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
