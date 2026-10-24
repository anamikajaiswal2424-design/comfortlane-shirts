<?php
$pageTitle = $pageTitle ?? 'ComfyNest';
$pageDescription = $pageDescription ?? 'Comfort-forward home essentials delivered to your door.';
$layout = $layout ?? 'back';
$headerTitle = $headerTitle ?? $pageTitle;
$backHref = $backHref ?? 'index.php';
$pageClass = $pageClass ?? '';
$siteBaseUrl = siteUrl();
$socialImagePath = $socialImagePath ?? 'assets/images/og.png';
$socialImage = filter_var($socialImagePath, FILTER_VALIDATE_URL) ? $socialImagePath : $siteBaseUrl . '/' . ltrim($socialImagePath, '/');
$scriptName = basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'index.php'));
$pageCanonicalPath = $pageCanonicalPath ?? $scriptName;
$canonicalUrl = $siteBaseUrl . '/' . ltrim($pageCanonicalPath, '/');
$privatePages = ['cart.php', 'address.php', 'payment.php', 'summary.php', 'processing.php', 'orders.php', 'success.php', 'place-order.php', 'cart-action.php', 'thankyou.php', 'initiate-payment.php', 'verify-payment.php'];
$robotsContent = in_array($scriptName, $privatePages, true) ? 'noindex,nofollow,noarchive' : 'index,follow,max-image-preview:large';
$pageViewId = recordPageView($pageTitle, (bool) ($tracking['analytics_enabled'] ?? true));
$trackingClientConfig = [
    'googleTagId' => (string) ($tracking['google_tag_id'] ?? ''),
    'googleAdsPurchaseSendTo' => (string) ($tracking['google_ads_purchase_send_to'] ?? ''),
    'metaPixelIds' => array_values(array_unique(array_merge($tracking['meta_pixel_ids'] ?? [], array_filter(preg_split('/[\s,]+/', (string) ($tracking['meta_pixel_id'] ?? '')))))),
    'metaPixelId' => (string) ($tracking['meta_pixel_id'] ?? ''),
    'googleMapsApiKey' => (string) ($tracking['google_maps_api_key'] ?? ''),
    'locationEnabled' => (bool) ($tracking['location_analytics_enabled'] ?? false),
    'pageViewId' => $pageViewId,
    'csrfToken' => (string) ($_SESSION['csrf_token'] ?? ''),
    'locationEndpoint' => 'track-location.php',
    'pageCommerceEvent' => $pageCommerceEvent ?? null,
    'checkoutCommerceEvent' => $checkoutCommerceEvent ?? null,
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#6c2857">
    <meta name="description" content="<?= e($pageDescription) ?>">
    <meta name="robots" content="<?= e($robotsContent) ?>">
    <link rel="canonical" href="<?= e($canonicalUrl) ?>">
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($pageDescription) ?>">
    <meta property="og:type" content="website">
    <meta property="og:image" content="<?= e($socialImage) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($pageTitle) ?>">
    <meta name="twitter:description" content="<?= e($pageDescription) ?>">
    <meta name="twitter:image" content="<?= e($socialImage) ?>">
    <title><?= e($pageTitle) ?></title>
    <link rel="preconnect" href="https://cdn.shopify.com" crossorigin>
    <link rel="preconnect" href="https://img-va.myshopline.com" crossorigin>
    <link rel="stylesheet" href="assets/css/style.css?v=20261024-simple-payment-1">
    <script>window.ComfyNestTracking = <?= json_encode($trackingClientConfig, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
    <?php if (!empty($structuredData) && is_array($structuredData)): ?><script type="application/ld+json"><?= json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script><?php endif; ?>
</head>
<body class="<?= e($pageClass) ?>">
<div class="site-stage">
    <div class="phone-shell">
        <?php if ($layout === 'home'): ?>
            <header class="home-header">
                <div class="brand-row">
                    <a class="brand" href="index.php" aria-label="ComfyNest home">
                        <span class="brand-mark">C</span>
                        <span>Comfy<span>Nest</span></span>
                    </a>
                    <div class="header-actions">
                        <button class="icon-button" type="button" aria-label="Notifications">♧<span class="dot"></span></button>
                        <a class="icon-button bag-button" href="cart.php" aria-label="Cart"><span class="ui-icon icon-shopping-cart" aria-hidden="true"></span><?php if (cartCount()): ?><span class="cart-badge"><?= cartCount() ?></span><?php endif; ?></a>
                    </div>
                </div>
                <label class="search-box">
                    <span aria-hidden="true">⌕</span>
                    <input type="search" placeholder="Search cushions, chairs, blankets…" aria-label="Search products" data-product-search>
                </label>
            </header>
        <?php else: ?>
            <header class="topbar">
                <a class="back-button" href="<?= e($backHref) ?>" aria-label="Go back">‹</a>
                <?php if ($layout === 'product'): ?>
                    <a class="brand compact" href="index.php"><span class="brand-mark">C</span><span>Comfy<span>Nest</span></span></a>
                    <div class="topbar-spacer"></div>
                    <button class="icon-button favourite-toggle" type="button" aria-label="Add to favourites">♡</button>
                    <a class="icon-button bag-button" href="cart.php" aria-label="Cart"><span class="ui-icon icon-shopping-cart" aria-hidden="true"></span><?php if (cartCount()): ?><span class="cart-badge"><?= cartCount() ?></span><?php endif; ?></a>
                <?php else: ?>
                    <strong class="topbar-title"><?= e($headerTitle) ?></strong>
                    <div class="topbar-spacer"></div>
                <?php endif; ?>
            </header>
        <?php endif; ?>
        <main>
