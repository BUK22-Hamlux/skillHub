<?php
// services/delete.php – Soft-delete a service (POST only)

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/ServiceModel.php';
require_once __DIR__ . '/ImageUploader.php';

requireLogin('freelancer');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlash('error', 'Invalid request.');
    redirect(url('services/index.php'));
}

$freelancerId = (int) $_SESSION['user_id'];
$serviceId    = filter_input(INPUT_POST, 'service_id', FILTER_VALIDATE_INT);

if (!$serviceId) {
    setFlash('error', 'Invalid service ID.');
    redirect(url('services/index.php'));
}

$model     = new ServiceModel(getDB());
$imagePath = $model->softDelete($serviceId, $freelancerId);

if ($imagePath === false) {
    setFlash('error', 'Service not found or you do not have permission to delete it.');
    redirect(url('services/index.php'));
}

// Clean up the physical image file (if any)
if ($imagePath !== '') {
    $uploader = new ImageUploader(
        __DIR__ . '/../assets/uploads/services',
        '/assets/uploads/services'
    );
    $uploader->delete($imagePath);
}

setFlash('success', 'Service deleted successfully.');
redirect(url('services/index.php'));
