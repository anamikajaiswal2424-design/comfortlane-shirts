<?php
require __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/admin-auth.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validCsrf()) {
    header('Location: index.php');
    exit;
}

$settings = [
    'brand_name' => trim((string) ($_POST['brand_name'] ?? 'ComfyNest')),
    'legal_name' => trim((string) ($_POST['legal_name'] ?? '')),
    'store_url' => siteUrl(),
    'support_email' => strtolower(trim((string) ($_POST['support_email'] ?? ''))),
    'support_phone' => trim((string) ($_POST['support_phone'] ?? '')),
    'business_address' => trim((string) ($_POST['business_address'] ?? '')),
    'country' => trim((string) ($_POST['country'] ?? 'India')),
    'support_hours' => trim((string) ($_POST['support_hours'] ?? '')),
    'gstin' => strtoupper(trim((string) ($_POST['gstin'] ?? ''))),
    'return_window_days' => max(1, min(30, (int) ($_POST['return_window_days'] ?? 7))),
    'refund_timeline' => trim((string) ($_POST['refund_timeline'] ?? '')),
    'delivery_timeline' => trim((string) ($_POST['delivery_timeline'] ?? '')),
    'last_policy_update' => date('j F Y'),
];

$errors = [];
if ($settings['legal_name'] === '') $errors[] = 'Legal business name is required.';
if ($settings['support_email'] !== '' && !filter_var($settings['support_email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid support email.';
if ($settings['support_phone'] !== '' && strlen(preg_replace('/\D+/', '', $settings['support_phone'])) < 10) $errors[] = 'Enter a valid support phone number.';

if ($errors) {
    $_SESSION['admin_flash'] = ['type' => 'error', 'title' => 'Business details not saved', 'message' => implode(' ', $errors)];
} elseif (saveBusinessSettings($settings)) {
    $_SESSION['admin_flash'] = ['type' => 'success', 'title' => 'Business details saved', 'message' => businessReady($settings) ? 'Public policy pages now show your verified contact details.' : 'Add email, phone and address before submitting the site for advertising review.'];
} else {
    $_SESSION['admin_flash'] = ['type' => 'error', 'title' => 'Save failed', 'message' => 'Business details could not be written.'];
}

header('Location: index.php#business-details');
exit;
