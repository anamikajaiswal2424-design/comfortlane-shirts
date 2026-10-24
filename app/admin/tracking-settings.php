<?php
require __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/admin-auth.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validCsrf()) {
    header('Location: index.php');
    exit;
}

try {
    $settings = validateTrackingSettings($_POST, $tracking);
    if (!saveTrackingSettings($settings)) throw new RuntimeException('Tracking settings could not be saved.');
    $_SESSION['admin_flash'] = [
        'type' => 'success',
        'title' => 'Tracking settings saved',
        'message' => 'Validated IDs now receive catalogue-matched ecommerce events and the selected live-location preference after visitor consent.',
    ];
} catch (Throwable $error) {
    $_SESSION['admin_flash'] = ['type' => 'error', 'title' => 'Tracking settings not saved', 'message' => $error->getMessage()];
}

header('Location: index.php#tracking');
exit;
