<?php
// services/edit.php – Edit an existing service

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/ServiceModel.php';
require_once __DIR__ . '/ImageUploader.php';

requireLogin('freelancer');

$freelancerId = (int) $_SESSION['user_id'];
$serviceId    = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$model        = new ServiceModel(getDB());

if (!$serviceId) {
    setFlash('error', 'Invalid service ID.');
    redirect(url('services/index.php'));
}

// Fetch + verify ownership
$service = $model->getOne($serviceId, $freelancerId);
if (!$service) {
    setFlash('error', 'Service not found or access denied.');
    redirect(url('services/index.php'));
}

$categories = $model->getCategories();
$errors     = [];

// Pre-fill form from DB
$formData = [
    'title'         => $service['title'],
    'category_id'   => $service['category_id'],
    'description'   => $service['description'],
    'price'         => $service['price'],
    'delivery_days' => $service['delivery_days'],
    'status'        => $service['status'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ── 1. Collect ────────────────────────────────────────────
    $formData['title']         = trim($_POST['title']         ?? '');
    $formData['category_id']   = trim($_POST['category_id']   ?? '');
    $formData['description']   = trim($_POST['description']   ?? '');
    $formData['price']         = trim($_POST['price']         ?? '');
    $formData['delivery_days'] = trim($_POST['delivery_days'] ?? '');
    $formData['status']        = trim($_POST['status']        ?? 'active');

    // ── 2. Validate ───────────────────────────────────────────
    if (mb_strlen($formData['title']) < 5) {
        $errors['title'] = 'Title must be at least 5 characters.';
    } elseif (mb_strlen($formData['title']) > 120) {
        $errors['title'] = 'Title must be 120 characters or fewer.';
    }

    if (mb_strlen($formData['description']) < 20) {
        $errors['description'] = 'Description must be at least 20 characters.';
    }

    $price = filter_var($formData['price'], FILTER_VALIDATE_FLOAT);
    if ($price === false || $price < 1 || $price > 99999) {
        $errors['price'] = 'Enter a valid price between $1 and $99,999.';
    }

    $days = filter_var($formData['delivery_days'], FILTER_VALIDATE_INT);
    if ($days === false || $days < 1 || $days > 365) {
        $errors['delivery_days'] = 'Delivery days must be between 1 and 365.';
    }

    if (!in_array($formData['status'], ['active', 'paused'], true)) {
        $errors['status'] = 'Invalid status value.';
    }

    // ── 3. Handle image upload ────────────────────────────────
    $newImagePath = null;
    $hasFile      = isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE;
    $removeImage  = isset($_POST['remove_image']);

    if ($hasFile) {
        try {
            $uploader     = new ImageUploader(
                __DIR__ . '/../assets/uploads/services',
                '/assets/uploads/services'
            );
            $newImagePath = $uploader->handle($_FILES['image']);
        } catch (RuntimeException $e) {
            $errors['image'] = $e->getMessage();
        }
    }

    // ── 4. Update ─────────────────────────────────────────────
    if (empty($errors)) {
        $uploader = $uploader ?? new ImageUploader(
            __DIR__ . '/../assets/uploads/services',
            '/assets/uploads/services'
        );

        // Decide final image path
        $imageSql  = '';
        $finalPath = null;

        if ($newImagePath) {
            // New image uploaded: delete old one
            $uploader->delete((string)$service['image_path']);
            $imageSql  = ', image_path = :image_path';
            $finalPath = $newImagePath;
        } elseif ($removeImage && $service['image_path']) {
            // User clicked "Remove image"
            $uploader->delete((string)$service['image_path']);
            $imageSql  = ', image_path = NULL';
        }
        // else: no change to image

        $model->update($serviceId, $freelancerId, [
            'category_id'   => $formData['category_id'] ?: null,
            'title'         => $formData['title'],
            'description'   => $formData['description'],
            'price'         => $price,
            'delivery_days' => $days,
            'status'        => $formData['status'],
            'image_sql'     => $imageSql,
            'image_path'    => $finalPath,
        ]);

        setFlash('success', 'Service updated successfully.');
        redirect(url('services/index.php'));
    }
}

$pageTitle = 'Edit Service';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Edit Service</h1>
        <p class="text-muted">Update your service details</p>
    </div>
    <a href="<?= url('services/index.php') ?>" class="btn btn--outline">← Back to Services</a>
</div>

<div class="form-layout">
<form id="serviceForm" method="post" action="<?= url('services/edit.php') ?>?id=<?= $serviceId ?>" enctype="multipart/form-data" novalidate>

    <!-- Title -->
    <div class="form-group <?= isset($errors['title']) ? 'has-error' : '' ?>">
        <label for="title">Service Title <span class="req">*</span></label>
        <input type="text" id="svcTitle" name="title"
               value="<?= e($formData['title']) ?>"
               maxlength="120" required>
        <?php if (isset($errors['title'])): ?>
            <span class="field-error"><?= e($errors['title']) ?></span>
        <?php endif; ?>
    </div>

    <!-- Category -->
    <div class="form-group">
        <label for="category_id">Category</label>
        <select id="category_id" name="category_id">
            <option value="">— Select a category —</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= (int)$cat['id'] ?>"
                    <?= (int)$formData['category_id'] === (int)$cat['id'] ? 'selected' : '' ?>>
                    <?= e($cat['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Description -->
    <div class="form-group <?= isset($errors['description']) ? 'has-error' : '' ?>">
        <label for="description">Description <span class="req">*</span></label>
        <textarea id="description" name="description" rows="6" required><?= e($formData['description']) ?></textarea>
        <?php if (isset($errors['description'])): ?>
            <span class="field-error"><?= e($errors['description']) ?></span>
        <?php endif; ?>
    </div>

    <!-- Price & Delivery -->
    <div class="form-row">
        <div class="form-group <?= isset($errors['price']) ? 'has-error' : '' ?>">
            <label for="price">Price (USD) <span class="req">*</span></label>
            <div class="input-prefix">
                <span>$</span>
                <input type="number" id="price" name="price"
                       value="<?= e($formData['price']) ?>"
                       min="1" max="99999" step="0.01" required>
            </div>
            <?php if (isset($errors['price'])): ?>
                <span class="field-error"><?= e($errors['price']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-group <?= isset($errors['delivery_days']) ? 'has-error' : '' ?>">
            <label for="delivery_days">Delivery Time <span class="req">*</span></label>
            <div class="input-suffix">
                <input type="number" id="delivery_days" name="delivery_days"
                       value="<?= e($formData['delivery_days']) ?>"
                       min="1" max="365" required>
                <span>days</span>
            </div>
            <?php if (isset($errors['delivery_days'])): ?>
                <span class="field-error"><?= e($errors['delivery_days']) ?></span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Status -->
    <div class="form-group <?= isset($errors['status']) ? 'has-error' : '' ?>">
        <label for="status">Status</label>
        <select id="status" name="status">
            <option value="active"  <?= $formData['status'] === 'active'  ? 'selected' : '' ?>>Active – visible to clients</option>
            <option value="paused"  <?= $formData['status'] === 'paused'  ? 'selected' : '' ?>>Paused – hidden from clients</option>
        </select>
        <?php if (isset($errors['status'])): ?>
            <span class="field-error"><?= e($errors['status']) ?></span>
        <?php endif; ?>
    </div>

    <!-- Image -->
    <div class="form-group <?= isset($errors['image']) ? 'has-error' : '' ?>">
        <label>Service Image</label>

        <?php if ($service['image_path']): ?>
        <div class="current-image">
            <img src="<?= e($service['image_path']) ?>" alt="Current image">
            <div class="current-image__meta">
                <p>Current image</p>
                <label class="checkbox-label">
                    <input type="checkbox" name="remove_image" value="1" id="removeImage">
                    Remove current image
                </label>
            </div>
        </div>
        <?php endif; ?>

        <div class="upload-zone" id="uploadZone">
            <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">
            <div class="upload-zone__label">
                <span class="upload-zone__icon">📷</span>
                <p><?= $service['image_path'] ? 'Upload a replacement image' : 'Click to upload or drag & drop' ?></p>
                <small>JPG, PNG or WEBP · Max 2 MB · Max 4000×4000px</small>
            </div>
            <div class="upload-zone__preview" id="imgPreview" hidden>
                <img id="previewImg" src="" alt="Preview">
                <button type="button" id="clearImg">✕ Remove</button>
            </div>
        </div>
        <?php if (isset($errors['image'])): ?>
            <span class="field-error"><?= e($errors['image']) ?></span>
        <?php endif; ?>
    </div>

    <div class="form-actions">
        <a href="<?= url('services/index.php') ?>" class="btn btn--outline">Cancel</a>
        <button type="submit" class="btn btn--primary">Save Changes</button>
    </div>

</form>
</div>

<script>
const zone     = document.getElementById('uploadZone');
const input    = document.getElementById('image');
const preview  = document.getElementById('imgPreview');
const img      = document.getElementById('previewImg');
const label    = zone.querySelector('.upload-zone__label');
const clearBtn = document.getElementById('clearImg');
const removeCb = document.getElementById('removeImage');

function showPreview(file) {
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        img.src = e.target.result;
        label.hidden   = true;
        preview.hidden = false;
        if (removeCb) removeCb.checked = false; // uploading a new image cancels remove
    };
    reader.readAsDataURL(file);
}

input.addEventListener('change', () => showPreview(input.files[0]));

clearBtn.addEventListener('click', () => {
    input.value    = '';
    img.src        = '';
    label.hidden   = false;
    preview.hidden = true;
});

zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag-over'); });
zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
zone.addEventListener('drop', e => {
    e.preventDefault();
    zone.classList.remove('drag-over');
    const file = e.dataTransfer.files[0];
    if (file) {
        const dt = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;
        showPreview(file);
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
