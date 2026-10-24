<?php

declare(strict_types=1);

date_default_timezone_set('Asia/Kolkata');

require_once __DIR__ . '/../url.php';
require_once __DIR__ . '/product-storage.php';
require_once __DIR__ . '/business-settings.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/catalog-feed.php';

$seedProducts = require __DIR__ . '/../data/products.php';
$products = loadProductCatalog($seedProducts);
$business = loadBusinessSettings();
