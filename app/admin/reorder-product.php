<?php
require __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/admin-auth.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validCsrf()) {
    header('Location: index.php');
    exit;
}

$id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT) ?: 0;
$direction = (string) ($_POST['direction'] ?? '');
if (isset($products[$id]) && in_array($direction, ['up', 'down', 'top', 'bottom'], true)) {
    $products = reorderProductCatalog($products, $id, $direction);
    if (saveProductCatalog($products, ['last_action' => 'reorder', 'product_id' => $id, 'direction' => $direction])) {
        $_SESSION['admin_flash'] = [
            'type' => 'success',
            'title' => 'Home-page order updated',
            'message' => 'The product was moved ' . $direction . ' in the catalogue.',
        ];
    }
}

header('Location: index.php#catalogue');
exit;
