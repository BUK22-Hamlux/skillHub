<?php
// services/index.php – Freelancer: My Services list

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/ServiceModel.php';

requireLogin('freelancer');

$freelancerId = (int) $_SESSION['user_id'];
$model        = new ServiceModel(getDB());
$services     = $model->getByFreelancer($freelancerId);

$pageTitle = 'My Services';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>My Services</h1>
        <p class="text-muted"><?= count($services) ?> service<?= count($services) !== 1 ? 's' : '' ?> listed</p>
    </div>
    <a href="<?= url('services/create.php') ?>" class="btn btn--primary">＋ Add New Service</a>
</div>

<?php if (empty($services)): ?>
<div class="empty-state">
    <div class="empty-state__icon">🛠️</div>
    <h2>No services yet</h2>
    <p>Create your first service and start getting hired.</p>
    <a href="<?= url('services/create.php') ?>" class="btn btn--primary">Create Your First Service</a>
</div>

<?php else: ?>
<div class="list-search-bar">
    <input type="text" id="serviceListSearch" placeholder="Search your services…" autocomplete="off">
</div>
<div class="service-grid">
    <?php foreach ($services as $svc): ?>
    <div class="service-card <?= $svc['status'] === 'paused' ? 'service-card--paused' : '' ?>">

        <!-- Image -->
        <div class="service-card__image">
            <?php if ($svc['image_path']): ?>
                <img src="<?= e($svc['image_path']) ?>" alt="<?= e($svc['title']) ?>">
            <?php else: ?>
                <div class="service-card__placeholder">🖼️</div>
            <?php endif; ?>
            <span class="service-card__status badge badge--<?= $svc['status'] === 'active' ? 'success' : 'warning' ?>">
                <?= ucfirst(e($svc['status'])) ?>
            </span>
        </div>

        <!-- Body -->
        <div class="service-card__body">
            <?php if ($svc['category_name']): ?>
                <span class="service-card__category"><?= e($svc['category_name']) ?></span>
            <?php endif; ?>

            <h2 class="service-card__title"><?= e($svc['title']) ?></h2>

            <p class="service-card__desc">
                <?= e(mb_strimwidth($svc['description'], 0, 110, '…')) ?>
            </p>

            <div class="service-card__meta">
                <span class="service-card__price">$<?= number_format((float)$svc['price'], 2) ?></span>
                <span class="service-card__delivery">🕐 <?= (int)$svc['delivery_days'] ?> day<?= $svc['delivery_days'] > 1 ? 's' : '' ?></span>
            </div>
        </div>

        <!-- Actions -->
        <div class="service-card__actions">
            <a href="<?= url('services/edit.php') ?>?id=<?= $svc['id'] ?>" class="btn btn--outline btn--sm">✏️ Edit</a>

            <form method="post" action="<?= url('services/delete.php') ?>"
                  onsubmit="return confirm('Delete \'<?= e(addslashes($svc['title'])) ?>\'? This cannot be undone.')">
                <input type="hidden" name="service_id" value="<?= (int)$svc['id'] ?>">
                <button type="submit" class="btn btn--danger btn--sm">🗑 Delete</button>
            </form>
        </div>

    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
