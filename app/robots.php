<?php
header('Content-Type: text/plain; charset=UTF-8');
require_once __DIR__ . '/url.php';
$baseUrl = siteUrl();
?>User-agent: *
Allow: /
Disallow: /admin/
Disallow: /cart.php
Disallow: /address.php
Disallow: /payment.php
Disallow: /summary.php
Disallow: /processing.php
Disallow: /orders.php
Disallow: /success.php
Disallow: /cart-action.php
Disallow: /place-order.php

Sitemap: <?= $baseUrl ?>/sitemap.xml
