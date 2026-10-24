<?php
require __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/admin-auth.php';
requireAdmin();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private, max-age=0');
header('X-Content-Type-Options: nosniff');

echo json_encode(analyticsLiveMapData(5), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
