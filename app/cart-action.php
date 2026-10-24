<?php
require __DIR__ . '/includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validCsrf()) {
    redirect('index.php');
}

$id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT) ?: 0;
$action = (string) ($_POST['action'] ?? 'add');
$selectedSize = strtoupper(trim((string) ($_POST['size'] ?? ($_SESSION['cart_sizes'][$id] ?? 'M'))));
$selectedSize = in_array($selectedSize, productSizes(), true) ? $selectedSize : 'M';

if (!isset($products[$id]) || !($products[$id]['is_show'] ?? true)) {
    redirect('index.php');
}

$_SESSION['cart'] ??= [];
$_SESSION['cart_sizes'] ??= [];
$current = (int) ($_SESSION['cart'][$id] ?? 0);
$isAjax = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';

if ($action === 'remove') {
    unset($_SESSION['cart'][$id]);
    unset($_SESSION['cart_sizes'][$id]);
    $_SESSION['flash'] = 'Item removed from your cart.';
} elseif ($action === 'update') {
    $quantity = max(0, min(8, (int) ($_POST['quantity'] ?? 1)));
    if ($quantity === 0) {
        unset($_SESSION['cart'][$id]);
        unset($_SESSION['cart_sizes'][$id]);
    } else {
        $_SESSION['cart'][$id] = $quantity;
    }
} else {
    $_SESSION['cart'][$id] = min(8, max(1, $current + 1));
    $_SESSION['cart_sizes'][$id] = $selectedSize;
    $_SESSION['flash'] = $products[$id]['short_name'] . ' added to cart.';
}

if ($isAjax) {
    $message = (string) ($_SESSION['flash'] ?? 'Cart updated.');
    unset($_SESSION['flash']);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'ok' => true,
        'cart_count' => cartCount(),
        'message' => $message,
    ], JSON_UNESCAPED_SLASHES);
    exit;
}

redirect($action === 'buy' ? 'address.php' : 'cart.php');
