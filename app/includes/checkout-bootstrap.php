<?php
// Minimal checkout bootstrap: never fetch MongoDB settings during polling.
declare(strict_types=1);
date_default_timezone_set('Asia/Kolkata');
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../url.php';
require_once __DIR__ . '/product-storage.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/payment-settings.php';
require_once __DIR__ . '/payment-gateway.php';
$seedProducts = require __DIR__ . '/../data/products.php';
$products = loadProductCatalog($seedProducts);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
function checkoutJson(array $body, int $code = 200): void {
    http_response_code($code);
    echo json_encode($body, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    exit;
}
if (str_contains(strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? '')), 'application/json')) {
    $decoded = json_decode((string) file_get_contents('php://input'), true);
    if (!is_array($decoded)) checkoutJson(['success' => false, 'error' => 'Invalid JSON body'], 400);
    $_POST = $decoded;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    checkoutJson(['success' => false, 'error' => 'Method not allowed'], 405);
}
if (empty($_SESSION['csrf_token']) || !validCsrf()) checkoutJson(['success' => false, 'error' => 'Checkout session expired. Reload payment.'], 403);
if (!cartCount() || empty($_SESSION['address'])) checkoutJson(['success' => false, 'error' => 'Cart or address missing. Return to checkout.'], 409);
