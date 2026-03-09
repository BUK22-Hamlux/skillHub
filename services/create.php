<?php
// services/create.php – Add a new service

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/ServiceModel.php';
require_once __DIR__ . '/ImageUploader.php';

requireLogin('freelancer');

$freelancerId = (int) $_SESSION['user_id'];
$model        = new ServiceModel(getDB());
$categories   = $model->getCategories();

$errors   = [];
$formData = [
    'title'         => '',
    'category_id'   => '',
    'description'   => '',
    'price'         => '',
    'delivery_days' => '3',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ── 1. Collect & sanitize ─────────────────────────────────
    $formData['title']         = trim($_POST['title']         ?? '');
    $formData['category_id']   = trim($_POST['category_id']   ?? '');
    $formData['description']   = trim($_POST['description']   ?? '');
    $formData['price']         = trim($_POST['price']         ?? '');
    $formData['delivery_days'] = trim($_POST['delivery_days'] ?? '');

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

    // ── 3. Handle image upload (optional) ────────────────────
    $imagePath = null;
    $hasFile   = isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE;

    if ($hasFile) {
        try {
            $uploader  = new ImageUploader(
                __DIR__ . '/../assets/uploads/services',
                '/assets/uploads/services'
            );
            $imagePath = $uploader->handle($_FILES['image']);
        } catch (RuntimeException $e) {
            $errors['image'] = $e->getMessage();
        }
    }

    // ── 4. Persist ────────────────────────────────────────────
    if (empty($errors)) {
        $model->create([
            'freelancer_id' => $freelancerId,
            'category_id'   => $formData['category_id'] ?: null,
            'title'         => $formData['title'],
            'description'   => $formData['description'],
            'price'         => $price,
            'delivery_days' => $days,
            'image_path'    => $imagePath,
        ]);

        setFlash('success', 'Service "' . $formData['title'] . '" created successfully!');
        redirect(url('services/index.php'));
    }
}

$pageTitle = 'Add New Service';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Add New Service</h1>
        <p class="text-muted">Tell clients what you offer</p>
    </div>
    <a href="<?= url('services/index.php') ?>" class="btn btn--outline">← Back to Services</a>
</div>

<div class="form-layout">
<form id="serviceForm" method="post" action="<?= url('services/create.php') ?>" enctype="multipart/form-data" novalidate>

    <!-- Title -->
    <div class="form-group <?= isset($errors['title']) ? 'has-error' : '' ?>">
        <label for="title">Service Title <span class="req">*</span></label>
        <input type="text" id="svcTitle" name="title"
               value="<?= e($formData['title']) ?>"
               placeholder="e.g. I will build a responsive website in React"
               maxlength="120" required>
        <span class="field-hint">5–120 characters. Be specific and compelling.</span>
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
        <textarea id="description" name="description" rows="6"
                  placeholder="Describe what you'll deliver, your process, and what makes you the best choice…"
                  required><?= e($formData['description']) ?></textarea>
        <span class="field-hint">At least 20 characters. Markdown not supported.</span>
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
                       min="1" max="99999" step="0.01"
                       placeholder="0.00" required>
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

    <!-- Image upload -->
    <div class="form-group <?= isset($errors['image']) ? 'has-error' : '' ?>">
        <label for="image">Service Image <span class="field-hint">(optional)</span></label>
        <div class="upload-zone" id="uploadZone">
            <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">
            <div class="upload-zone__label">
                <span class="upload-zone__icon">📷</span>
                <p>Click to upload or drag & drop</p>
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
        <button type="submit" class="btn btn--primary">Publish Service</button>
    </div>

</form>
</div>

<script>
// Image preview
const zone     = document.getElementById('uploadZone');
const input    = document.getElementById('image');
const preview  = document.getElementById('imgPreview');
const img      = document.getElementById('previewImg');
const label    = zone.querySelector('.upload-zone__label');
const clearBtn = document.getElementById('clearImg');

function showPreview(file) {
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        img.src = e.target.result;
        label.hidden   = true;
        preview.hidden = false;
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

// Drag & drop
zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag-over'); });
zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
zone.addEventListener('drop', e => {
    e.preventDefault();
    zone.classList.remove('drag-over');
    const file = e.dataTransfer.files[0];
    if (file) {
        // Assign to file input via DataTransfer
        const dt = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;
        showPreview(file);
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
