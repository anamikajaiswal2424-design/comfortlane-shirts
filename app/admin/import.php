<?php
require __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/admin-auth.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validCsrf()) {
    header('Location: index.php');
    exit;
}

try {
    $file = $_FILES['product_csv'] ?? null;
    if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Please choose a valid CSV file.');
    }
    if ((int) ($file['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new RuntimeException('The CSV is larger than the 5 MB limit.');
    }
    $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    if ($extension !== 'csv' || !is_uploaded_file((string) $file['tmp_name'])) {
        throw new RuntimeException('Only CSV files can be imported.');
    }
    $mode = ($_POST['mode'] ?? '') === 'merge' ? 'merge' : 'replace';
    $result = importProductCsv((string) $file['tmp_name'], $products, $mode);
    $_SESSION['admin_flash'] = [
        'type' => 'success',
        'title' => $result['imported'] . ' products imported',
        'message' => $result['skipped'] . ' rows skipped · ' . $result['total'] . ' products now in the catalogue.',
    ];
} catch (Throwable $error) {
    $_SESSION['admin_flash'] = ['type' => 'error', 'title' => 'Import failed', 'message' => $error->getMessage()];
}

header('Location: index.php');
exit;
