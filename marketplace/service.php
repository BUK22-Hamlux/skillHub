<?php
// marketplace/service.php – Service detail + place order

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/MarketplaceModel.php';
require_once __DIR__ . '/../orders/OrderModel.php';

$serviceId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$serviceId) {
    setFlash('error', 'Invalid service.');
    redirect(url('marketplace/index.php'));
}

$mpModel  = new MarketplaceModel(getDB());
$service  = $mpModel->getService($serviceId);

if (!$service) {
    setFlash('error', 'Service not found or no longer available.');
    redirect(url('marketplace/index.php'));
}

$related  = $mpModel->getRelated($serviceId, (int)$service['freelancer_user_id']);
$skillTags = $service['freelancer_skills']
    ? array_map('trim', explode(',', $service['freelancer_skills']))
    : [];

// ── Handle order placement ────────────────────────────────────
$orderError   = '';
$orderSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {

    // Must be logged in as client
    if (!isLoggedIn()) {
        setFlash('info', 'Please log in to place an order.');
        redirect(url('auth/login.php'));
    }
    if ($_SESSION['user_role'] !== 'client') {
        $orderError = 'Only clients can place orders.';
    } elseif ((int)$service['freelancer_user_id'] === (int)$_SESSION['user_id']) {
        $orderError = 'You cannot order your own service.';
    } else {
        $orderModel   = new OrderModel(getDB());
        $requirements = trim($_POST['requirements'] ?? '');

        if (mb_strlen($requirements) > 2000) {
            $orderError = 'Requirements must be 2000 characters or fewer.';
        } elseif ($orderModel->hasActiveOrder((int)$_SESSION['user_id'], $serviceId)) {
            $orderError = 'You already have an active order for this service.';
        } else {
            $orderId = $orderModel->create([
                'service_id'    => $serviceId,
                'client_id'     => (int) $_SESSION['user_id'],
                'freelancer_id' => (int) $service['freelancer_user_id'],
                'service_title' => $service['title'],
                'amount'        => $service['price'],
                'delivery_days' => $service['delivery_days'],
                'requirements'  => $requirements ?: null,
            ]);
            setFlash('success', 'Order placed! The freelancer will review it shortly.');
            redirect(url('orders/detail.php') . '?id=' . $orderId);
        }
    }
}

$pageTitle = e($service['title']);
require_once __DIR__ . '/../includes/header.php';
?>

<div class="svc-detail-layout">

    <!-- ── LEFT: service info ─────────────────────────────────── -->
    <div class="svc-detail-main">

        <nav class="breadcrumb">
            <a href="<?= url('marketplace/index.php') ?>">Marketplace</a>
            <?php if ($service['category_name']): ?>
            <span>›</span>
            <a href="<?= url('marketplace/index.php') ?>?category_id=<?= (int)$service['category_id'] ?>"><?= e($service['category_name']) ?></a>
            <?php endif; ?>
            <span>›</span>
            <span><?= e(mb_strimwidth($service['title'], 0, 40, '…')) ?></span>
        </nav>

        <h1 class="svc-detail__title"><?= e($service['title']) ?></h1>

        <!-- Seller strip -->
        <div class="svc-detail__seller">
            <div class="svc-detail__avatar"><?= mb_strtoupper(mb_substr($service['freelancer_name'], 0, 1)) ?></div>
            <div>
                <strong><?= e($service['freelancer_name']) ?></strong>
                <?php if ($service['freelancer_headline']): ?>
                    <span class="text-muted"> · <?= e($service['freelancer_headline']) ?></span>
                <?php endif; ?>
            </div>
            <?php
            $avMap = ['available'=>['Available','badge--success'],'busy'=>['Busy','badge--warning'],'unavailable'=>['Unavailable','badge--danger']];
            $av = $avMap[$service['freelancer_availability'] ?? 'unavailable'] ?? ['Unavailable','badge--danger'];
            ?>
            <span class="badge <?= $av[1] ?>"><?= $av[0] ?></span>
        </div>

        <!-- Service image -->
        <?php if ($service['image_path']): ?>
        <div class="svc-detail__image">
            <img src="<?= e($service['image_path']) ?>" alt="<?= e($service['title']) ?>">
        </div>
        <?php endif; ?>

        <!-- Description -->
        <section class="svc-detail__section">
            <h2>About this service</h2>
            <div class="svc-detail__desc"><?= nl2br(e($service['description'])) ?></div>
        </section>

        <!-- Freelancer skills -->
        <?php if ($skillTags): ?>
        <section class="svc-detail__section">
            <h2>Freelancer skills</h2>
            <div><?php foreach ($skillTags as $tag): ?>
                <span class="skill-tag"><?= e($tag) ?></span>
            <?php endforeach; ?></div>
        </section>
        <?php endif; ?>

        <!-- Related services -->
        <?php if ($related): ?>
        <section class="svc-detail__section">
            <h2>More from <?= e($service['freelancer_name']) ?></h2>
            <div class="related-grid">
                <?php foreach ($related as $r): ?>
                <a href="<?= url('marketplace/service.php') ?>?id=<?= (int)$r['id'] ?>" class="related-card">
                    <div class="related-card__img">
                        <?php if ($r['image_path']): ?>
                            <img src="<?= e($r['image_path']) ?>" alt="">
                        <?php else: ?>
                            <div class="mp-card__no-image" style="height:100%">🛠️</div>
                        <?php endif; ?>
                    </div>
                    <div class="related-card__body">
                        <p><?= e(mb_strimwidth($r['title'], 0, 60, '…')) ?></p>
                        <strong>$<?= number_format((float)$r['price'], 2) ?></strong>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

    </div><!-- /.svc-detail-main -->

    <!-- ── RIGHT: order card ──────────────────────────────────── -->
    <aside class="svc-order-card">
        <div class="svc-order-card__price">
            $<?= number_format((float)$service['price'], 2) ?>
        </div>
        <ul class="svc-order-card__meta">
            <li>🕐 Delivered in <strong><?= (int)$service['delivery_days'] ?> day<?= $service['delivery_days'] > 1 ? 's' : '' ?></strong></li>
            <?php if ($service['category_name']): ?>
            <li>📂 <strong><?= e($service['category_name']) ?></strong></li>
            <?php endif; ?>
        </ul>

        <?php if (!isLoggedIn()): ?>
            <a href="<?= url('auth/login.php') ?>" class="btn btn--primary btn--full">Log in to Order</a>
            <p class="svc-order-card__note">New here? <a href="<?= url('auth/register.php') ?>">Create a free account</a></p>

        <?php elseif ($_SESSION['user_role'] === 'freelancer'): ?>
            <p class="svc-order-card__note text-muted" style="text-align:center;">Freelancer accounts cannot place orders.</p>

        <?php elseif ((int)$service['freelancer_user_id'] === (int)$_SESSION['user_id']): ?>
            <p class="svc-order-card__note text-muted" style="text-align:center;">This is your own service.</p>

        <?php else: ?>
            <?php if ($orderError): ?>
                <div class="flash flash--error"><?= e($orderError) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= url('marketplace/service.php') ?>?id=<?= $serviceId ?>">
                <div class="form-group">
                    <label for="requirements">Your requirements <span class="field-hint">(optional)</span></label>
                    <textarea id="requirements" name="requirements" rows="4"
                              placeholder="Describe what you need, include links, files, or any specific details…"
                              maxlength="2000"><?= e($_POST['requirements'] ?? '') ?></textarea>
                    <span class="field-hint" id="reqCount">0 / 2000</span>
                </div>
                <button type="submit" name="place_order" class="btn btn--primary btn--full">
                    Place Order · $<?= number_format((float)$service['price'], 2) ?>
                </button>
            </form>
            <p class="svc-order-card__note">You won't be charged until the freelancer accepts.</p>
        <?php endif; ?>

    </aside>

</div><!-- /.svc-detail-layout -->

<script>
const ta = document.getElementById('requirements');
const counter = document.getElementById('reqCount');
if (ta && counter) {
    const update = () => counter.textContent = ta.value.length + ' / 2000';
    ta.addEventListener('input', update);
    update();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
