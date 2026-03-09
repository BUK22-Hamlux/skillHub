<?php
// marketplace/index.php – Browse all services

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/MarketplaceModel.php';

// Marketplace is public — no login required to browse
$model = new MarketplaceModel(getDB());

// ── Collect filters ───────────────────────────────────────────
$filters = [
    'q'           => trim($_GET['q']           ?? ''),
    'category_id' => (int) ($_GET['category_id'] ?? 0),
    'max_price'   => (float) ($_GET['max_price']   ?? 0),
    'max_days'    => (int) ($_GET['max_days']    ?? 0),
    'sort'        => $_GET['sort'] ?? 'newest',
];
// Zero = no filter
if ($filters['category_id'] === 0)  unset($filters['category_id']);
if ($filters['max_price']   === 0.0) unset($filters['max_price']);
if ($filters['max_days']    === 0)  unset($filters['max_days']);

$page       = max(1, (int) ($_GET['page'] ?? 1));
$result     = $model->browse($filters, $page);
$categories = $model->getCategories();

// Build query string for pagination links (preserve all current filters)
function paginationUrl(int $p): string {
    $params = $_GET;
    $params['page'] = $p;
    return url('marketplace/index.php') . '?' . http_build_query($params);
}

$pageTitle = 'Browse Services';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mp-layout">

    <!-- ── Sidebar filters ───────────────────────────────────── -->
    <aside class="mp-sidebar">
        <form method="get" action="<?= url('marketplace/index.php') ?>" id="filterForm">

            <div class="mp-sidebar__section">
                <h3>Search</h3>
                <div class="mp-search-wrap">
                    <input type="text" name="q" placeholder="Keyword…"
                           value="<?= e($filters['q'] ?? '') ?>">
                    <button type="submit">🔍</button>
                </div>
            </div>

            <div class="mp-sidebar__section">
                <h3>Category</h3>
                <select name="category_id" onchange="this.form.submit()">
                    <option value="0">All categories</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int)$cat['id'] ?>"
                        <?= (int)($_GET['category_id'] ?? 0) === (int)$cat['id'] ? 'selected' : '' ?>>
                        <?= e($cat['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mp-sidebar__section">
                <h3>Max Price</h3>
                <div class="input-prefix">
                    <span>$</span>
                    <input type="number" name="max_price" min="1" step="1"
                           placeholder="Any" value="<?= e($_GET['max_price'] ?? '') ?>">
                </div>
            </div>

            <div class="mp-sidebar__section">
                <h3>Delivery (days ≤)</h3>
                <input type="number" name="max_days" min="1" max="365"
                       placeholder="Any" value="<?= e($_GET['max_days'] ?? '') ?>">
            </div>

            <div class="mp-sidebar__section">
                <h3>Sort By</h3>
                <select name="sort" onchange="this.form.submit()">
                    <option value="newest"     <?= ($_GET['sort'] ?? 'newest') === 'newest'     ? 'selected' : '' ?>>Newest</option>
                    <option value="price_asc"  <?= ($_GET['sort'] ?? '') === 'price_asc'  ? 'selected' : '' ?>>Price: Low → High</option>
                    <option value="price_desc" <?= ($_GET['sort'] ?? '') === 'price_desc' ? 'selected' : '' ?>>Price: High → Low</option>
                    <option value="delivery"   <?= ($_GET['sort'] ?? '') === 'delivery'   ? 'selected' : '' ?>>Fastest Delivery</option>
                </select>
            </div>

            <button type="submit" class="btn btn--primary btn--full">Apply Filters</button>

            <?php if (!empty(array_filter([$filters['q'] ?? '', $filters['category_id'] ?? '', $filters['max_price'] ?? '', $filters['max_days'] ?? '']))): ?>
            <a href="<?= url('marketplace/index.php') ?>" class="btn btn--outline btn--full" style="margin-top:.5rem;">✕ Clear Filters</a>
            <?php endif; ?>

        </form>
    </aside>

    <!-- ── Main results ──────────────────────────────────────── -->
    <div class="mp-main">

        <div class="mp-results-header">
            <p class="text-muted">
                <?= number_format($result['total']) ?> service<?= $result['total'] !== 1 ? 's' : '' ?> found
                <?= !empty($filters['q']) ? ' for "' . e($filters['q']) . '"' : '' ?>
            </p>
        </div>

        <?php if (empty($result['services'])): ?>
        <div class="empty-state">
            <div class="empty-state__icon">🔍</div>
            <h2>No services found</h2>
            <p>Try different keywords or remove some filters.</p>
            <a href="<?= url('marketplace/index.php') ?>" class="btn btn--outline">Clear all filters</a>
        </div>

        <?php else: ?>
        <div class="mp-grid">
            <?php foreach ($result['services'] as $svc): ?>
            <a href="<?= url('marketplace/service.php') ?>?id=<?= (int)$svc['id'] ?>" class="mp-card">

                <div class="mp-card__image">
                    <?php if ($svc['image_path']): ?>
                        <img src="<?= e($svc['image_path']) ?>" alt="<?= e($svc['title']) ?>">
                    <?php else: ?>
                        <div class="mp-card__no-image">🛠️</div>
                    <?php endif; ?>
                    <?php if ($svc['category_name']): ?>
                        <span class="mp-card__cat"><?= e($svc['category_name']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="mp-card__body">
                    <p class="mp-card__seller">
                        <span class="mp-card__avatar"><?= mb_strtoupper(mb_substr($svc['freelancer_name'], 0, 1)) ?></span>
                        <?= e($svc['freelancer_name']) ?>
                    </p>
                    <h3 class="mp-card__title"><?= e($svc['title']) ?></h3>
                </div>

                <div class="mp-card__footer">
                    <span class="mp-card__delivery">🕐 <?= (int)$svc['delivery_days'] ?>d</span>
                    <span class="mp-card__price">From <strong>$<?= number_format((float)$svc['price'], 2) ?></strong></span>
                </div>

            </a>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($result['pages'] > 1): ?>
        <nav class="pagination">
            <?php if ($result['page'] > 1): ?>
                <a href="<?= paginationUrl($result['page'] - 1) ?>" class="btn btn--outline btn--sm">← Prev</a>
            <?php endif; ?>

            <?php for ($p = max(1, $result['page'] - 2); $p <= min($result['pages'], $result['page'] + 2); $p++): ?>
                <a href="<?= paginationUrl($p) ?>"
                   class="btn btn--sm <?= $p === $result['page'] ? 'btn--primary' : 'btn--outline' ?>">
                    <?= $p ?>
                </a>
            <?php endfor; ?>

            <?php if ($result['page'] < $result['pages']): ?>
                <a href="<?= paginationUrl($result['page'] + 1) ?>" class="btn btn--outline btn--sm">Next →</a>
            <?php endif; ?>
        </nav>
        <?php endif; ?>

        <?php endif; ?>
    </div><!-- /.mp-main -->
</div><!-- /.mp-layout -->

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
