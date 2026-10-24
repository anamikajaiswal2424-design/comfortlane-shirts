<?php
require __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/admin-auth.php';
requireAdmin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validCsrf()) {
    header('Location: index.php');
    exit;
}
$_SESSION['admin_flash'] = ['type' => 'error', 'title' => 'Configuration is managed in setting.php',
    'message' => 'Edit upi_id, merchant_name, paytm_mid and paytm_env in the root setting.php file.'];
header('Location: index.php#upi-settings');
exit;
