<?php
declare(strict_types=1);
$root = dirname(__DIR__);
$path = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
if (PHP_SAPI === 'cli-server' && str_starts_with($path, '/assets/') && !str_contains($path, '..') && is_file($root . '/public' . $path)) return false;
if ($path === '/api/initiate-payment') $path = '/initiate-payment.php';
if ($path === '/api/verify-payment') $path = '/verify-payment.php';
if ($path === '/' || $path === '/index.php') $path = '/index.php';
if ($path === '/PaymentConfirmation') $path = '/thankyou.php';
if ($path === '/PaymentFailed') $path = '/payment-failed.php';
if ($path === '/admin' || $path === '/admin/') $path = '/admin/index.php';
if ($path === '/api/index.php') $path = '/index.php';
// An explicit allowlist prevents PHP source/configuration files being served.
$allowed = ['index.php','product.php','cart.php','cart-action.php','address.php','summary.php',
    'payment.php','payment-failed.php','initiate-payment.php','verify-payment.php','processing.php','place-order.php',
    'thankyou.php','PaymentConfirmation.php','PaymentFailed.php','success.php','orders.php','about.php','contact.php','shipping.php','returns.php',
    'payments.php','privacy.php','terms.php','cookies.php','disclaimer.php','sitemap.php',
    'google-products.php','meta-products.php','robots.php','track-location.php',
    'admin/index.php','admin/login.php','admin/logout.php','admin/import.php','admin/reorder-product.php',
    'admin/toggle-product.php','admin/tracking-settings.php','admin/business-settings.php',
    'admin/payment-settings.php','admin/payment-diagnostics.php','admin/live-visitors.php'];
$route = ltrim($path, '/');
if (!in_array($route, $allowed, true)) { http_response_code(404); exit('Page not found'); }
try {
    require_once $root . '/api/local-bootstrap.php';
    $root = localStoreBootstrap($root);
    $_SERVER['SCRIPT_NAME'] = '/' . $route;
    $_SERVER['PHP_SELF'] = '/' . $route;
    chdir($root . '/app');
    require $root . '/app/' . $route;
} catch (Throwable $error) {
    error_log('Store request failed: ' . get_class($error) . ' at ' . basename($error->getFile()) . ':' . $error->getLine());
    http_response_code(503);
    header('Cache-Control: no-store');
    if (session_status() === PHP_SESSION_ACTIVE) session_abort();
    exit('Store temporarily unavailable. Please retry later. If you already paid, do not pay again.');
}
