<?php
declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../includes/admin-auth.php';
requireAdmin();
require_once __DIR__ . '/../includes/payment-settings.php';
require_once __DIR__ . '/../includes/payment-gateway.php';
header('Cache-Control: no-store');
function diagnosticEscape(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
$config = gatewayConfig();
$payment = gatewayPaymentSettings();
$checks = [
    ['PHP version', version_compare(PHP_VERSION, '8.1', '>='), PHP_VERSION],
    ['PHP cURL extension', function_exists('curl_init'), function_exists('curl_init') ? 'Enabled' : 'Enable PHP cURL in cPanel'],
    ['UPI receiver', upiPaymentsReady($payment), upiPaymentsReady($payment) ? 'Configured in root setting.php' : 'Set a valid upi_id in root setting.php'],
    ['Paytm MID', $config['PAYTM_MID'] !== '', $config['PAYTM_MID'] !== '' ? 'Configured (value hidden)' : 'Missing: set paytm_mid in root setting.php'],
    ['Paytm environment', null, $config['PAYTM_ENV'] === 'test' ? 'Staging' : 'Production'],
    ['Paytm verification', null, 'A real merchant-linked order is required for an end-to-end check. No live payment is made by this page.'],
    ['PhonePe native', null, 'Same as Kitchan: opens the app, without automatic payment verification or confirmation.'],
    ['Order storage', (is_file(__DIR__ . '/../data/orders.json') ? is_writable(__DIR__ . '/../data/orders.json') : is_writable(__DIR__ . '/../data')), 'data/orders.json must be writable by PHP. Payment confirmation no longer requires MongoDB.'],
];
?>
<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Payment diagnostics</title>
<style>body{font:16px system-ui;max-width:900px;margin:40px auto;padding:20px;background:#fafafa}article{background:white;border:1px solid #ddd;border-radius:10px;margin:12px 0;padding:16px}p{margin:8px 0}.pass{color:#087443}.fail{color:#b91c1c}</style>
<h1>Payment connection diagnostics</h1><p>Yeh checks isi hosting server ke web PHP runtime mein run hue hain.</p>
<?php foreach ($checks as [$name, $ok, $detail]): ?>
<article><strong class="<?= $ok === null ? '' : ($ok ? 'pass' : 'fail') ?>"><?= diagnosticEscape(($ok === null ? 'INFO' : ($ok ? 'PASS' : 'FAIL')) . ' — ' . $name) ?></strong><p><?= diagnosticEscape($detail) ?></p></article>
<?php endforeach; ?>
<p><a href="payment-diagnostics.php">Run checks again</a> · <a href="index.php">Admin panel</a></p></html>
