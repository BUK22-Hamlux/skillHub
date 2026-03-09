<?php
// orders/index.php – Order history (client + freelancer)

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/OrderModel.php';

requireLogin();

$uid   = (int) $_SESSION['user_id'];
$role  = $_SESSION['user_role'];
$model = new OrderModel(getDB());

$orders = $role === 'client'
    ? $model->getByClient($uid)
    : $model->getByFreelancer($uid);

$counts = $model->countByStatus($uid, $role);
$total  = array_sum($counts);

// Active tab filter
$tab    = $_GET['status'] ?? 'all';
$validTabs = ['all','pending','accepted','in_progress','completed','cancelled'];
if (!in_array($tab, $validTabs)) $tab = 'all';

$filtered = $tab === 'all'
    ? $orders
    : array_filter($orders, fn($o) => $o['status'] === $tab);

$statusConfig = [
    'pending'     => ['label' => 'Pending',     'class' => 'badge--warning', 'icon' => '⏳'],
    'accepted'    => ['label' => 'Accepted',     'class' => 'badge--info',    'icon' => '✅'],
    'in_progress' => ['label' => 'In Progress',  'class' => 'badge--primary', 'icon' => '🔧'],
    'completed'   => ['label' => 'Completed',    'class' => 'badge--success', 'icon' => '🎉'],
    'cancelled'   => ['label' => 'Cancelled',    'class' => 'badge--danger',  'icon' => '❌'],
];

$pageTitle = 'My Orders';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>My Orders</h1>
        <p class="text-muted"><?= $total ?> order<?= $total !== 1 ? 's' : '' ?> total</p>
    </div>
    <?php if ($role === 'client'): ?>
        <a href="<?= url('marketplace/index.php') ?>" class="btn btn--primary">🔍 Browse Services</a>
    <?php endif; ?>
</div>

<!-- Status tabs -->
<div class="order-tabs">
    <a href="?status=all"
       class="order-tab <?= $tab === 'all' ? 'order-tab--active' : '' ?>">
        All <span class="order-tab__count"><?= $total ?></span>
    </a>
    <?php foreach ($statusConfig as $key => $cfg): ?>
        <?php if ($counts[$key] > 0 || $tab === $key): ?>
        <a href="?status=<?= $key ?>"
           class="order-tab <?= $tab === $key ? 'order-tab--active' : '' ?>">
            <?= $cfg['icon'] ?> <?= $cfg['label'] ?>
            <span class="order-tab__count"><?= $counts[$key] ?></span>
        </a>
        <?php endif; ?>
    <?php endforeach; ?>
</div>

<?php if (empty($filtered)): ?>
<div class="empty-state">
    <div class="empty-state__icon"><?= $tab === 'all' ? '📦' : ($statusConfig[$tab]['icon'] ?? '📦') ?></div>
    <h2>No <?= $tab === 'all' ? '' : ($statusConfig[$tab]['label'] . ' ') ?>orders yet</h2>
    <?php if ($role === 'client'): ?>
        <p>Find a service and place your first order.</p>
        <a href="<?= url('marketplace/index.php') ?>" class="btn btn--primary">Browse Marketplace</a>
    <?php else: ?>
        <p>New orders will appear here once clients purchase your services.</p>
        <a href="<?= url('services/index.php') ?>" class="btn btn--outline">Manage Services</a>
    <?php endif; ?>
</div>

<?php else: ?>
<div class="list-search-bar">
    <input type="text" id="orderListSearch" placeholder="Search orders…" autocomplete="off">
</div>
<div class="order-list">
    <?php foreach ($filtered as $order):
        $sc = $statusConfig[$order['status']] ?? $statusConfig['pending'];
        $counterpart = $role === 'client' ? $order['freelancer_name'] : $order['client_name'];
        $counterpartLabel = $role === 'client' ? 'Freelancer' : 'Client';
    ?>
    <div class="order-row">

        <div class="order-row__thumb">
            <?php if ($order['service_image']): ?>
                <img src="<?= e($order['service_image']) ?>" alt="">
            <?php else: ?>
                <div class="order-row__thumb--placeholder">🛠️</div>
            <?php endif; ?>
        </div>

        <div class="order-row__info">
            <div class="order-row__title">
                <a href="<?= url('orders/detail.php') ?>?id=<?= (int)$order['id'] ?>">
                    <?= e($order['service_title']) ?>
                </a>
            </div>
            <div class="order-row__meta text-muted">
                <span><?= $counterpartLabel ?>: <strong><?= e($counterpart) ?></strong></span>
                <span>·</span>
                <span>Placed <?= date('M j, Y', strtotime($order['created_at'])) ?></span>
                <span>·</span>
                <span>🕐 <?= (int)$order['delivery_days'] ?>d delivery</span>
            </div>
        </div>

        <div class="order-row__right">
            <div class="order-row__amount">$<?= number_format((float)$order['amount'], 2) ?></div>
            <span class="badge <?= $sc['class'] ?>"><?= $sc['icon'] ?> <?= $sc['label'] ?></span>
            <a href="<?= url('orders/detail.php') ?>?id=<?= (int)$order['id'] ?>"
               class="btn btn--outline btn--sm">View</a>
        </div>

    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
