<?php

declare(strict_types=1);

date_default_timezone_set('Asia/Kolkata');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../url.php';
require_once __DIR__ . '/product-storage.php';
require_once __DIR__ . '/business-settings.php';
require_once __DIR__ . '/tracking-settings.php';
require_once __DIR__ . '/payment-settings.php';
require_once __DIR__ . '/analytics-storage.php';
require_once __DIR__ . '/order-storage.php';
$seedProducts = require __DIR__ . '/../data/products.php';
$products = loadProductCatalog($seedProducts);
$business = loadBusinessSettings();
$tracking = loadTrackingSettings();
require_once __DIR__ . '/payment-gateway.php';
$paymentSettings = gatewayPaymentSettings();
// UPI and Meta Pixel are controlled centrally by root setting.php.
$centralSettings = comfortlaneSettings();
$pixelValues = preg_split('/[\s,]+/', trim((string) ($centralSettings['pixel_id'] ?? ''))) ?: [];
$tracking['meta_pixel_ids'] = array_values(array_unique(array_filter($pixelValues,
    static fn(string $id): bool => preg_match('/^[0-9]{5,25}$/', $id) === 1)));
$tracking['meta_pixel_id'] = implode(',', $tracking['meta_pixel_ids']);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/catalog-feed.php';
