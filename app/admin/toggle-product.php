<?php
require __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/admin-auth.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validCsrf()) {
    header('Location: index.php');
    exit;
}

$id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT) ?: 0;
if (isset($products[$id])) {
    $products[$id]['is_show'] = !($products[$id]['is_show'] ?? true);
    saveProductCatalog($products, ['last_action' => 'visibility_toggle', 'product_id' => $id]);
    $_SESSION['admin_flash'] = [
        'type' => 'success',
        'title' => 'Product updated',
        'message' => $products[$id]['short_name'] . ' is now ' . ($products[$id]['is_show'] ? 'visible.' : 'hidden.'),
    ];
}

header('Location: index.php');
exit;
