<?php
// orders/detail.php – View a single order (client + freelancer)

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/OrderModel.php';

requireLogin();

$orderId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$orderId) {
    setFlash('error', 'Invalid order.');
    redirect(url('orders/index.php'));
}

$role  = $_SESSION['user_role'];
$uid   = (int) $_SESSION['user_id'];
$model = new OrderModel(getDB());
$order = $model->getOne($orderId, $uid, $role);

if (!$order) {
    setFlash('error', 'Order not found or access denied.');
    redirect(url('orders/index.php'));
}

// ── Handle POST actions ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'accept' && $role === 'freelancer') {
        $model->accept($orderId, $uid)
            ? setFlash('success', 'Order accepted! Get to work.')
            : setFlash('error', 'Could not accept order.');

    } elseif ($action === 'complete' && $role === 'freelancer') {
        $model->complete($orderId, $uid)
            ? setFlash('success', 'Order marked as completed!')
            : setFlash('error', 'Could not complete order.');

    } elseif ($action === 'cancel') {
        $model->cancel($orderId, $uid, $role)
            ? setFlash('success', 'Order cancelled.')
            : setFlash('error', 'Order cannot be cancelled at this stage.');
    }

    redirect(url('orders/detail.php') . '?id=' . $orderId);
}

// Reload after redirect
$order = $model->getOne($orderId, $uid, $role);

// Status display config
$statusConfig = [
    'pending'     => ['label' => 'Pending',     'class' => 'badge--warning',  'icon' => '⏳'],
    'accepted'    => ['label' => 'Accepted',     'class' => 'badge--info',     'icon' => '✅'],
    'in_progress' => ['label' => 'In Progress',  'class' => 'badge--primary',  'icon' => '🔧'],
    'completed'   => ['label' => 'Completed',    'class' => 'badge--success',  'icon' => '🎉'],
    'cancelled'   => ['label' => 'Cancelled',    'class' => 'badge--danger',   'icon' => '❌'],
];
$sc = $statusConfig[$order['status']] ?? $statusConfig['pending'];

$pageTitle = 'Order #' . $orderId;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Order #<?= $orderId ?></h1>
        <p class="text-muted">Placed <?= date('M j, Y \a\t g:i A', strtotime($order['created_at'])) ?></p>
    </div>
    <a href="<?= url('orders/index.php') ?>" class="btn btn--outline">← My Orders</a>
</div>

<div class="order-detail-layout">

    <!-- ── Main info ─────────────────────────────────────────── -->
    <div class="order-detail-main">

        <!-- Status card -->
        <div class="order-status-card">
            <div class="order-status-card__icon"><?= $sc['icon'] ?></div>
            <div>
                <span class="badge <?= $sc['class'] ?>" style="font-size:.9rem;padding:.35rem 1rem;">
                    <?= $sc['label'] ?>
                </span>
                <p class="text-muted" style="margin-top:.35rem;font-size:.875rem;">
                    <?php
                    echo match($order['status']) {
                        'pending'     => $role === 'freelancer'
                                            ? 'Review this order and accept or decline.'
                                            : 'Waiting for the freelancer to accept.',
                        'accepted'    => $role === 'freelancer'
                                            ? 'You accepted this order. Deliver within ' . $order['delivery_days'] . ' days.'
                                            : 'Freelancer accepted. Work is starting soon.',
                        'in_progress' => 'Work is in progress.',
                        'completed'   => 'This order was completed on ' . date('M j, Y', strtotime($order['completed_at'])) . '.',
                        'cancelled'   => 'This order was cancelled on ' . date('M j, Y', strtotime($order['cancelled_at'])) . '.',
                        default       => ''
                    };
                    ?>
                </p>
            </div>
        </div>

        <!-- Service info -->
        <div class="card" style="margin-top:1.25rem;">
            <h2 class="card__title">Service</h2>
            <div class="order-service-row">
                <?php if ($order['service_image']): ?>
                    <img src="<?= e($order['service_image']) ?>" alt="" class="order-service-row__img">
                <?php else: ?>
                    <div class="order-service-row__img order-service-row__img--placeholder">🛠️</div>
                <?php endif; ?>
                <div>
                    <strong><?= e($order['service_title']) ?></strong>
                    <p class="text-muted" style="font-size:.875rem;margin-top:.25rem;">
                        🕐 Delivery: <?= (int)$order['delivery_days'] ?> day<?= $order['delivery_days'] > 1 ? 's' : '' ?>
                        &nbsp;·&nbsp;
                        💰 $<?= number_format((float)$order['amount'], 2) ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Requirements -->
        <?php if ($order['requirements']): ?>
        <div class="card" style="margin-top:1.25rem;">
            <h2 class="card__title">Client Requirements</h2>
            <div class="order-requirements"><?= nl2br(e($order['requirements'])) ?></div>
        </div>
        <?php endif; ?>

        <!-- Parties -->
        <div class="card" style="margin-top:1.25rem;">
            <h2 class="card__title">Order Parties</h2>
            <div class="order-parties">
                <div class="order-party">
                    <div class="order-party__avatar"><?= mb_strtoupper(mb_substr($order['client_name'], 0, 1)) ?></div>
                    <div>
                        <strong><?= e($order['client_name']) ?></strong>
                        <p class="text-muted" style="font-size:.8rem;"><?= e($order['client_email']) ?></p>
                        <span class="badge badge--client">Client</span>
                    </div>
                </div>
                <div class="order-party__divider">↔</div>
                <div class="order-party">
                    <div class="order-party__avatar"><?= mb_strtoupper(mb_substr($order['freelancer_name'], 0, 1)) ?></div>
                    <div>
                        <strong><?= e($order['freelancer_name']) ?></strong>
                        <p class="text-muted" style="font-size:.8rem;"><?= e($order['freelancer_email']) ?></p>
                        <span class="badge badge--freelancer">Freelancer</span>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /.order-detail-main -->

    <!-- ── Actions sidebar ───────────────────────────────────── -->
    <aside class="order-detail-aside">

        <div class="card">
            <h2 class="card__title">Order Summary</h2>
            <dl class="profile-list">
                <dt>Order ID</dt>    <dd>#<?= $orderId ?></dd>
                <dt>Amount</dt>      <dd><strong>$<?= number_format((float)$order['amount'], 2) ?></strong></dd>
                <dt>Delivery</dt>    <dd><?= (int)$order['delivery_days'] ?> days</dd>
                <dt>Placed</dt>      <dd><?= date('M j, Y', strtotime($order['created_at'])) ?></dd>
                <?php if ($order['accepted_at']): ?>
                <dt>Accepted</dt>    <dd><?= date('M j, Y', strtotime($order['accepted_at'])) ?></dd>
                <?php endif; ?>
                <?php if ($order['completed_at']): ?>
                <dt>Completed</dt>   <dd><?= date('M j, Y', strtotime($order['completed_at'])) ?></dd>
                <?php endif; ?>
            </dl>
        </div>

        <?php if ($role === 'freelancer' && $order['status'] === 'pending'): ?>
        <div class="card" style="margin-top:1rem;">
            <h2 class="card__title">Actions</h2>
            <div style="display:flex;flex-direction:column;gap:.6rem;">
                <form method="post">
                    <input type="hidden" name="action" value="accept">
                    <button class="btn btn--primary btn--full" type="submit"
                            onclick="return confirm('Accept this order?')">
                        ✅ Accept Order
                    </button>
                </form>
                <form method="post">
                    <input type="hidden" name="action" value="cancel">
                    <button class="btn btn--danger btn--full" type="submit"
                            onclick="return confirm('Decline and cancel this order?')">
                        ✕ Decline Order
                    </button>
                </form>
            </div>
        </div>

        <?php elseif ($role === 'freelancer' && in_array($order['status'], ['accepted','in_progress'])): ?>
        <div class="card" style="margin-top:1rem;">
            <h2 class="card__title">Actions</h2>
            <div style="display:flex;flex-direction:column;gap:.6rem;">
                <form method="post">
                    <input type="hidden" name="action" value="complete">
                    <button class="btn btn--primary btn--full" type="submit"
                            onclick="return confirm('Mark this order as completed?')">
                        🎉 Mark Completed
                    </button>
                </form>
                <form method="post">
                    <input type="hidden" name="action" value="cancel">
                    <button class="btn btn--danger btn--full" type="submit"
                            onclick="return confirm('Cancel this order?')">
                        ✕ Cancel Order
                    </button>
                </form>
            </div>
        </div>

        <?php elseif ($role === 'client' && in_array($order['status'], ['pending','accepted'])): ?>
        <div class="card" style="margin-top:1rem;">
            <h2 class="card__title">Actions</h2>
            <form method="post">
                <input type="hidden" name="action" value="cancel">
                <button class="btn btn--danger btn--full" type="submit"
                        onclick="return confirm('Cancel this order?')">
                    ✕ Cancel Order
                </button>
            </form>
        </div>

        <?php elseif ($order['status'] === 'completed'): ?>
        <div class="card" style="margin-top:1rem;text-align:center;padding:1.5rem;">
            <div style="font-size:2.5rem;margin-bottom:.5rem;">🎉</div>
            <strong>Order Complete!</strong>
            <p class="text-muted" style="font-size:.85rem;margin-top:.25rem;">Great work all round.</p>
        </div>
        <?php endif; ?>

        <div style="margin-top:1rem;">
            <a href="<?= url('marketplace/service.php') ?>?id=<?= (int)$order['service_id'] ?>"
               class="btn btn--outline btn--full">View Service Page</a>
        </div>
    </aside>

</div><!-- /.order-detail-layout -->

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
