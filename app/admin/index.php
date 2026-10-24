<?php
require __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/admin-auth.php';
requireAdmin();

$activeCount = count(visibleProducts($products));
$flash = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);
$catalogUpdated = is_file(catalogFilePath()) ? date('d M Y, h:i A', filemtime(catalogFilePath())) : 'Seed catalogue';
$catalogStatus = catalogFeedStatus($products, $business);
$catalogFeedUrls = catalogFeedUrls($business);
$upiReady = upiPaymentsReady($paymentSettings);
$analytics = analyticsDashboardData(7);
$liveMap = analyticsLiveMapData(5);
$orders = loadOrders();
$maxTrendViews = max(1, ...array_column($analytics['trend'], 'views'));
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Product admin — ComfyNest</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <link rel="stylesheet" href="../assets/css/admin.css?v=20260828-5">
</head>
<body class="admin-dashboard-page">
<header class="admin-topbar">
    <a class="admin-brand" href="index.php"><span>C</span><b>Comfy<em>Nest</em></b><small>ADMIN</small></a>
    <nav><a href="#upi-settings">UPI</a><a href="#catalog-sync">Ad catalogues</a><a href="#live-map">Live map</a><a href="#analytics">Visitors</a><a href="#tracking">Tracking</a><a href="#orders">Orders</a><a href="#catalogue">Products</a><a href="../index.php" target="_blank" rel="noopener">View store ↗</a><form method="post" action="logout.php"><?= csrfField() ?><button type="submit">Log out</button></form></nav>
</header>

<main class="admin-layout">
    <section class="admin-welcome"><div><small>COMFYNEST CONTROL CENTRE</small><h1>Store dashboard</h1><p>Manage products, visitor insights, tracking tags and confirmed orders in one place.</p></div><span class="status-pill">● Store online</span></section>

    <?php if (is_array($flash)): ?><div class="admin-alert <?= e($flash['type'] ?? 'success') ?>" role="status"><strong><?= e($flash['title'] ?? 'Done') ?></strong><span><?= e($flash['message'] ?? '') ?></span></div><?php endif; ?>

    <section class="stat-grid" aria-label="Store summary">
        <article><small>Total products</small><strong><?= count($products) ?></strong><span>In catalogue</span></article>
        <article><small>Live products</small><strong><?= $activeCount ?></strong><span>Visible in store</span></article>
        <article><small>Visitors today</small><strong><?= (int) $analytics['today_visitors'] ?></strong><span>Estimated sessions</span></article>
        <article><small>Page views today</small><strong><?= (int) $analytics['today_pageviews'] ?></strong><span>Public pages</span></article>
        <article><small>Total orders</small><strong><?= count($orders) ?></strong><span>Saved records</span></article>
        <article><small>Tracked revenue</small><strong><?= money($analytics['revenue']) ?></strong><span>Purchase events</span></article>
    </section>

    <section class="admin-panel import-panel">
        <div class="panel-heading"><div><small>CSV IMPORT</small><h2>Add products in one go</h2><p>Required columns: id, name, selling_price, mrp and img1. Product-detail HTML is converted to safe plain text.</p></div><span class="csv-chip">CSV</span></div>
        <form class="import-form" method="post" action="import.php" enctype="multipart/form-data">
            <?= csrfField() ?>
            <label class="file-drop"><input type="file" name="product_csv" accept=".csv,text/csv" required><span class="upload-icon">↑</span><b>Choose product CSV</b><small>Maximum file size: 5 MB</small></label>
            <fieldset><legend>Import behaviour</legend><label><input type="radio" name="mode" value="replace" checked><span><b>Replace catalogue</b><small>Use only products from this CSV</small></span></label><label><input type="radio" name="mode" value="merge"><span><b>Merge by product ID</b><small>Update matches and keep other products</small></span></label></fieldset>
            <button class="admin-button primary" type="submit">Import products</button>
        </form>
    </section>

    <section class="admin-panel catalog-sync-panel" id="catalog-sync">
        <div class="panel-heading"><div><small>SALES CHANNEL SYNC</small><h2>Google &amp; Meta product catalogues</h2><p>These live feeds use every visible product in your admin catalogue. Product IDs also match storefront advertising events.</p></div><span class="readiness <?= $catalogStatus['ready'] ? 'ready' : 'pending' ?>"><?= $catalogStatus['ready'] ? '✓ Feed ready' : '! Setup needed' ?></span></div>
        <div class="catalog-sync-kpis">
            <article><span>Products in feeds</span><strong><?= (int) $catalogStatus['product_count'] ?></strong><small>Only products marked Live</small></article>
            <article><span>Last catalogue update</span><strong class="catalog-date"><?= e($catalogUpdated) ?></strong><small>Feeds update automatically</small></article>
            <article><span>Matching product IDs</span><strong>100%</strong><small>ViewContent → Purchase</small></article>
        </div>
        <div class="catalog-channel-grid">
            <article class="catalog-channel google-channel"><header><span>G</span><div><small>GOOGLE MERCHANT CENTER</small><h3>XML product feed</h3></div><i>LIVE</i></header><p>Connect this URL as a product data source using “Add products from a file” or scheduled fetch.</p><div class="feed-url-row"><input type="text" readonly value="<?= e($catalogFeedUrls['google']) ?>" aria-label="Google catalogue feed URL"><button type="button" data-copy-feed="<?= e($catalogFeedUrls['google']) ?>">Copy</button></div><a href="../google-products.php" target="_blank" rel="noopener">Preview Google feed ↗</a></article>
            <article class="catalog-channel meta-channel"><header><span>f</span><div><small>META COMMERCE MANAGER</small><h3>CSV product feed</h3></div><i>LIVE</i></header><p>Use this URL as the scheduled data feed for Facebook and Instagram catalogue ads.</p><div class="feed-url-row"><input type="text" readonly value="<?= e($catalogFeedUrls['meta']) ?>" aria-label="Meta catalogue feed URL"><button type="button" data-copy-feed="<?= e($catalogFeedUrls['meta']) ?>">Copy</button></div><a href="../meta-products.php" target="_blank" rel="noopener">Preview Meta feed ↗</a></article>
        </div>
        <div class="catalog-event-strip" aria-label="Catalogue event coverage"><span>PageView</span><span>ViewContent</span><span>AddToCart</span><span>InitiateCheckout</span><span>Purchase</span></div>
        <?php if ($catalogStatus['warnings']): ?><div class="catalog-warnings"><strong>Before submitting for ad review</strong><ul><?php foreach ($catalogStatus['warnings'] as $warning): ?><li><?= e($warning) ?></li><?php endforeach; ?></ul></div><?php else: ?><div class="catalog-ready-note">✓ Catalogue URLs, public business details and live HTTPS store address are ready for connection.</div><?php endif; ?>
        <ol class="catalog-steps"><li><b>1</b><span>Publish the site on your HTTPS domain and save that domain under Public contact details.</span></li><li><b>2</b><span>Add the Google XML URL in Merchant Center and the Meta CSV URL in Commerce Manager.</span></li><li><b>3</b><span>Add your Google Tag and Meta Pixel IDs below, then review rejected products in each ad platform.</span></li></ol>
    </section>

    <section class="admin-panel analytics-panel" id="analytics">
        <div class="panel-heading"><div><small>FIRST-PARTY ANALYTICS</small><h2>Visitors &amp; page views</h2><p>Privacy-friendly session counts are collected without storing raw IP addresses. Bots and admin pages are excluded.</p></div><span class="readiness ready">Last 7 days</span></div>
        <div class="analytics-kpis">
            <article><span>All-time visitors</span><strong><?= (int) $analytics['total_visitors'] ?></strong><small>Estimated sessions</small></article>
            <article><span>All-time page views</span><strong><?= (int) $analytics['total_pageviews'] ?></strong><small>Store pages</small></article>
            <article><span>Purchase events</span><strong><?= (int) $analytics['purchases'] ?></strong><small><?= (int) $analytics['today_purchases'] ?> today</small></article>
        </div>
        <section class="live-map-card" id="live-map">
            <div class="live-map-heading"><div><small>LIVE VISITOR VIEW</small><h3>Visitors around the world</h3><p>Approximate city-level activity from visitors who allowed analytics. The map refreshes every 10 seconds.</p></div><span class="live-now"><i></i><b data-live-count><?= (int) $liveMap['active_visitors'] ?></b> live now</span></div>
            <div class="live-map-grid">
                <div id="visitor-world-map" data-live-endpoint="live-visitors.php" role="img" aria-label="World map showing approximate live visitor locations"></div>
                <aside class="live-map-sidebar">
                    <div class="live-map-summary"><span>Located visitors</span><strong data-mapped-count><?= (int) $liveMap['mapped_visitors'] ?></strong><small>Active during the last <?= (int) $liveMap['window_minutes'] ?> minutes</small></div>
                    <div class="live-visitor-list" data-live-list>
                        <?php if ($liveMap['visitors']): foreach ($liveMap['visitors'] as $visitor): ?>
                            <article><i></i><div><strong><?= e(trim(($visitor['city'] ?: 'Unknown city') . ', ' . ($visitor['state'] ?: $visitor['country']))) ?></strong><small><?= e($visitor['path']) ?> · <?= e($visitor['device']) ?></small></div><time><?= (int) $visitor['seconds_ago'] ?>s</time></article>
                        <?php endforeach; else: ?><p class="live-map-empty">Waiting for a consented live visitor location.</p><?php endif; ?>
                    </div>
                    <p class="live-map-updated">Updated <span data-live-updated>just now</span> · Approximate location by <a href="https://ipwhois.io/" target="_blank" rel="noopener">ipwhois.io</a> · Free tier: 1,000 new session lookups/day</p>
                </aside>
            </div>
            <script type="application/json" id="live-map-initial"><?= json_encode($liveMap, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
        </section>
        <div class="analytics-split">
            <div class="trend-card"><h3>7-day activity</h3><div class="trend-chart">
                <?php foreach ($analytics['trend'] as $day): $barHeight = max(5, (int) round(($day['views'] / $maxTrendViews) * 100)); ?>
                    <div class="trend-day"><div class="trend-value"><?= (int) $day['views'] ?></div><span style="--bar:<?= $barHeight ?>%"></span><small><?= e($day['label']) ?></small></div>
                <?php endforeach; ?>
            </div><p>Bars show page views. Unique visitors are estimated from anonymous browser sessions.</p></div>
            <div class="popular-card"><h3>Popular pages</h3><?php if ($analytics['popular_pages']): ?><ol><?php foreach ($analytics['popular_pages'] as $page): ?><li><span><?= e($page['path']) ?></span><b><?= (int) $page['views'] ?></b></li><?php endforeach; ?></ol><?php else: ?><p class="analytics-empty">Visitor activity will appear here.</p><?php endif; ?></div>
        </div>
        <div class="location-card"><div><h3>Recent visitor cities</h3><p>Seven-day aggregate from consented free IP lookups or the optional Google Maps fallback. Map coordinates are rounded before storage.</p></div>
            <?php if ($analytics['locations']): ?><div class="location-table-wrap"><table class="location-table"><thead><tr><th>City</th><th>State</th><th>Country</th><th>Visitors</th><th>Views</th></tr></thead><tbody><?php foreach ($analytics['locations'] as $location): ?><tr><td><?= e($location['city']) ?></td><td><?= e($location['state']) ?></td><td><?= e($location['country']) ?></td><td><?= (int) $location['visitors'] ?></td><td><?= (int) $location['views'] ?></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><div class="analytics-empty">No consented city/state data yet. New locations will appear after visitors choose “Allow analytics”.</div><?php endif; ?>
        </div>
    </section>

    <section class="admin-panel tracking-panel" id="tracking">
        <div class="panel-heading"><div><small>PIXELS &amp; TAGS</small><h2>Tracking integrations</h2><p>Enter validated IDs only. The storefront will not accept arbitrary script code, and third-party tags load only after visitor consent.</p></div><span class="readiness <?= trackingIsConfigured($tracking) ? 'ready' : 'pending' ?>"><?= trackingIsConfigured($tracking) ? '✓ Configured' : '! Add IDs' ?></span></div>
        <form class="tracking-form" method="post" action="tracking-settings.php">
            <?= csrfField() ?>
            <label><span>Google Tag ID</span><input name="google_tag_id" value="<?= e($tracking['google_tag_id']) ?>" placeholder="G-XXXXXXXXXX, AW-XXXXXXXXX or GTM-XXXXXXX"><small>Loads site-wide after consent.</small></label>
            <label><span>Google Ads purchase destination</span><input name="google_ads_purchase_send_to" value="<?= e($tracking['google_ads_purchase_send_to']) ?>" placeholder="AW-123456789/AbCdEfGh"><small>Used for the purchase conversion.</small></label>
            <label><span>Meta Pixel IDs (comma separated)</span><input name="meta_pixel_id" readonly value="<?= e($tracking['meta_pixel_id']) ?>" placeholder="123456789012345,987654321098765"><small>Meta Pixel IDs are managed in root setting.php. Event code is unchanged.</small></label>
            <label><span>Google Maps API key (optional fallback)</span><input type="password" name="google_maps_api_key" value="" placeholder="<?= e(maskedSecret((string) $tracking['google_maps_api_key'])) ?>" autocomplete="new-password"><small>The free IP lookup works without a key. Leave blank to keep an existing fallback key.</small></label>
            <label class="tracking-check"><input type="checkbox" name="analytics_enabled" value="1" <?= !empty($tracking['analytics_enabled']) ? 'checked' : '' ?>><span><b>Internal visitor analytics</b><small>Count anonymous sessions and public page views.</small></span></label>
            <label class="tracking-check"><input type="checkbox" name="location_analytics_enabled" value="1" <?= !empty($tracking['location_analytics_enabled']) ? 'checked' : '' ?>><span><b>Live city/state map</b><small>After consent, use one cached free IP lookup per visitor session. Raw IP is not stored by ComfyNest.</small></span></label>
            <label class="tracking-check danger-check"><input type="checkbox" name="remove_google_maps_api_key" value="1"><span><b>Remove saved Maps fallback key</b><small>The free live visitor map will continue working.</small></span></label>
            <div class="tracking-guidance"><strong>Catalogue event payloads</strong><code>product ID · value · INR · quantity · order_id · transaction_id</code><p>Product IDs remain identical in both catalogue feeds and all ecommerce events. Purchase fires once after an order is saved.</p></div>
            <button class="admin-button primary" type="submit">Save tracking settings</button>
            <small>Meta Pixel IDs are read from root setting.php.</small>
        </form>
    </section>

    <section class="admin-panel tracking-panel upi-settings-panel" id="upi-settings">
        <div class="panel-heading"><div><small>UPI APP PAYMENTS</small><h2>PhonePe, Paytm, GPay &amp; QR</h2><p>UPI, merchant name, Paytm MID and Meta Pixel IDs are managed centrally in the root setting.php file. Values saved in older admin settings do not override that file.</p></div><span class="readiness <?= $upiReady ? 'ready' : 'pending' ?>"><?= $upiReady ? '✓ UPI active' : '! Add UPI ID' ?></span></div>
        <form class="tracking-form" method="post" action="payment-settings.php">
            <?= csrfField() ?>
            <label><span>Business UPI ID</span><input name="upi_id" readonly value="<?= e((string) $paymentSettings['upi_id']) ?>" placeholder="comfynest@bank" autocomplete="off" spellcheck="false"><small>This exact address is used in PhonePe, Paytm, GPay, generic UPI and QR payments.</small></label>
            <label><span>Payee / merchant name</span><input name="payee_name" readonly value="<?= e((string) $paymentSettings['payee_name']) ?>" placeholder="ComfyNest" maxlength="60"><small>Customers see this name inside their UPI app.</small></label>
            <label><span>Paytm merchant MID</span><input name="paytm_mid" readonly value="<?= e(gatewayConfig()['PAYTM_MID']) ?>" autocomplete="off"><small>Required for Paytm status verification of merchant-linked orders.</small></label>
            <label><span>Paytm environment</span><select name="paytm_env" disabled><option value="production" <?= gatewayConfig()['PAYTM_ENV'] !== 'test' ? 'selected' : '' ?>>Production</option><option value="test" <?= gatewayConfig()['PAYTM_ENV'] === 'test' ? 'selected' : '' ?>>Test</option></select></label>
            <label class="tracking-check"><input type="checkbox" name="upi_enabled" disabled value="1" <?= !empty($paymentSettings['upi_enabled']) ? 'checked' : '' ?>><span><b>Enable UPI app payments</b><small>Disable this to stop accepting new UPI payments.</small></span></label>
            <div class="tracking-guidance"><strong>Payment confirmation</strong><code>UPI app launch · transaction reference · order amount</code><p>Direct UPI intents open the selected app but cannot securely prove settlement by themselves. Online orders remain “gateway confirmation required” until Paytm confirms the matching merchant order. Native PhonePe has no automatic confirmation in this flow.</p></div>
            <p>Edit root <code>setting.php</code> to update these values.</p>
        </form>
    </section>

    <section class="admin-panel business-panel" id="business-details">
        <div class="panel-heading"><div><small>BUSINESS TRANSPARENCY</small><h2>Public contact details</h2><p>These details appear on Contact, About and policy pages. Complete them before starting an advertising campaign.</p></div><span class="readiness <?= businessReady($business) ? 'ready' : 'pending' ?>"><?= businessReady($business) ? '✓ Ready' : '! Action needed' ?></span></div>
        <form class="business-form" method="post" action="business-settings.php">
            <?= csrfField() ?>
            <label><span>Brand name</span><input name="brand_name" value="<?= e($business['brand_name']) ?>" required></label>
            <label><span>Legal business name</span><input name="legal_name" value="<?= e($business['legal_name']) ?>" required></label>
            <label class="wide"><span>Public HTTPS store URL</span><input type="url" name="store_url" value="<?= e(siteUrl()) ?>" readonly><small>This URL is controlled centrally from the root <code>url.php</code> file and updates across the complete website.</small></label>
            <label><span>Support email</span><input type="email" name="support_email" value="<?= e($business['support_email']) ?>" placeholder="care@yourdomain.com"></label>
            <label><span>Support phone</span><input name="support_phone" value="<?= e($business['support_phone']) ?>" placeholder="+91 98765 43210"></label>
            <label class="wide"><span>Business / return address</span><textarea name="business_address" rows="3" placeholder="Full address with city, state and PIN code"><?= e($business['business_address']) ?></textarea></label>
            <label><span>Country</span><input name="country" value="<?= e($business['country']) ?>"></label>
            <label><span>GSTIN (optional)</span><input name="gstin" value="<?= e($business['gstin']) ?>"></label>
            <label class="wide"><span>Support hours</span><input name="support_hours" value="<?= e($business['support_hours']) ?>"></label>
            <label><span>Return window (days)</span><input type="number" min="1" max="30" name="return_window_days" value="<?= (int) $business['return_window_days'] ?>"></label>
            <label><span>Refund timeline</span><input name="refund_timeline" value="<?= e($business['refund_timeline']) ?>"></label>
            <label class="wide"><span>Delivery timeline</span><input name="delivery_timeline" value="<?= e($business['delivery_timeline']) ?>"></label>
            <button class="admin-button primary" type="submit">Save public details</button>
        </form>
    </section>

    <section class="admin-panel orders-panel" id="orders">
        <div class="panel-heading"><div><small>ORDER REGISTER</small><h2>Recent orders &amp; transactions</h2><p>Order IDs, transaction references, amounts and checkout details are saved when an order is confirmed.</p></div><span class="readiness <?= $orders ? 'ready' : 'pending' ?>"><?= count($orders) ?> orders</span></div>
        <?php if (!$orders): ?><div class="admin-empty">New orders will appear here after checkout.</div><?php else: ?><div class="orders-table-wrap"><table class="orders-table"><thead><tr><th>Date</th><th>Order / transaction</th><th>Customer</th><th>Payment</th><th>Amount</th><th>Status</th></tr></thead><tbody>
            <?php foreach (array_slice($orders, 0, 75) as $order): ?><tr>
                <td><b><?= e(date('d M Y', strtotime((string) $order['created_at']))) ?></b><small><?= e(date('h:i A', strtotime((string) $order['created_at']))) ?></small></td>
                <td><b><?= e((string) $order['id']) ?></b><code><?= e((string) ($order['transaction_id'] ?? '')) ?></code></td>
                <td><b><?= e((string) ($order['address']['name'] ?? 'Guest')) ?></b><small><?= e((string) ($order['address']['city'] ?? '')) ?>, <?= e((string) ($order['address']['state'] ?? '')) ?></small></td>
                <td><b><?= e((string) ($order['payment']['label'] ?? '')) ?></b><small><?= e((string) ($order['payment_status'] ?? '')) ?></small></td>
                <td><strong><?= money((float) ($order['total'] ?? 0)) ?></strong></td>
                <td><span class="visibility live">● <?= e((string) ($order['order_status'] ?? 'Confirmed')) ?></span></td>
            </tr><?php endforeach; ?>
        </tbody></table></div><?php endif; ?>
    </section>

    <section class="admin-panel catalogue-panel" id="catalogue">
        <div class="panel-heading"><div><small>CATALOGUE</small><h2>All products</h2><p><?= count($products) ?> products · <?= $activeCount ?> currently visible</p></div></div>
        <?php if (!$products): ?>
            <div class="admin-empty">No products yet. Import a CSV above to get started.</div>
        <?php else: ?>
            <div class="product-table-wrap">
                <table class="product-table">
                    <thead><tr><th>Position</th><th>Product</th><th>Price</th><th>MRP</th><th>Status</th><th>Order on home</th><th>Visibility</th></tr></thead>
                    <tbody>
                    <?php $productPosition = 0; foreach ($products as $product): $productPosition++; ?>
                        <tr>
                            <td><span class="position-badge"><?= $productPosition ?></span></td>
                            <td><div class="admin-product"><img src="<?= e($product['image']) ?>" alt=""><div><strong><?= e($product['short_name']) ?></strong><small>#<?= $product['id'] ?> · <?= e($product['color'] ?? 'Standard') ?></small></div></div></td>
                            <td><b><?= money($product['price']) ?></b></td>
                            <td><span class="muted-price"><?= money($product['mrp']) ?></span></td>
                            <td><span class="visibility <?= !empty($product['is_show']) ? 'live' : 'hidden' ?>"><?= !empty($product['is_show']) ? '● Live' : '○ Hidden' ?></span></td>
                            <td><form class="order-actions" method="post" action="reorder-product.php"><?= csrfField() ?><input type="hidden" name="product_id" value="<?= $product['id'] ?>"><button type="submit" name="direction" value="top" title="Move to top" <?= $productPosition === 1 ? 'disabled' : '' ?>>⇈</button><button type="submit" name="direction" value="up" title="Move up" <?= $productPosition === 1 ? 'disabled' : '' ?>>↑</button><button type="submit" name="direction" value="down" title="Move down" <?= $productPosition === count($products) ? 'disabled' : '' ?>>↓</button><button type="submit" name="direction" value="bottom" title="Move to bottom" <?= $productPosition === count($products) ? 'disabled' : '' ?>>⇊</button></form></td>
                            <td><form method="post" action="toggle-product.php"><?= csrfField() ?><input type="hidden" name="product_id" value="<?= $product['id'] ?>"><button class="table-action" type="submit"><?= !empty($product['is_show']) ? 'Hide' : 'Show' ?></button></form></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="" defer></script>
<script src="../assets/js/admin-live-map.js?v=20260828-1" defer></script>
<script src="../assets/js/admin-catalog.js?v=20260828-1" defer></script>
</body>
</html>
