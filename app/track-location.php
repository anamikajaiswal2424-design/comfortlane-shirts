<?php
require __DIR__ . '/includes/init.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validCsrf() || empty($tracking['analytics_enabled']) || empty($tracking['location_analytics_enabled'])) {
    http_response_code(400);
    echo json_encode(['ok' => false]);
    exit;
}

$updated = updatePageViewLocation(
    (string) ($_POST['page_view_id'] ?? ''),
    (string) ($_POST['city'] ?? ''),
    (string) ($_POST['state'] ?? ''),
    (string) ($_POST['country'] ?? ''),
    $_POST['latitude'] ?? null,
    $_POST['longitude'] ?? null,
    (string) ($_POST['source'] ?? '')
);
echo json_encode(['ok' => $updated]);
