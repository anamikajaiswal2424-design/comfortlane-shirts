<?php
require __DIR__ . '/includes/init.php';
$catalogProducts = visibleProducts($products);
$pageTitle = 'ComfyNest — Comfort lives here';
$pageDescription = 'Shop cosy seating, blankets and home comfort essentials from ComfyNest.';
$schemaBaseUrl = siteUrl();
$structuredData = [
    '@context' => 'https://schema.org',
    '@type' => 'OnlineStore',
    'name' => $business['brand_name'],
    'legalName' => $business['legal_name'],
    'url' => $schemaBaseUrl . '/index.php',
    'image' => $schemaBaseUrl . '/assets/images/og.png',
    'areaServed' => $business['country'],
];
if (businessReady($business)) {
    $structuredData['email'] = $business['support_email'];
    $structuredData['telephone'] = $business['support_phone'];
    $structuredData['address'] = ['@type' => 'PostalAddress', 'streetAddress' => $business['business_address'], 'addressCountry' => 'IN'];
}
$layout = 'home';
$pageClass = 'home-page';
$showBottomNav = true;
$activeNav = 'home';
require __DIR__ . '/includes/header.php';
?>

<section class="home-hero" aria-label="ComfortLane printed and embroidered shirts starting at ₹299">
    <a class="home-banner" href="#products" aria-label="Shop printed and embroidered shirts starting at ₹299">
        <img src="assets/images/comfortlane-shirts-hero.webp" width="960" height="640" alt="ComfortLane Wear Art, Feel Comfort — printed and embroidered shirts starting at ₹299" fetchpriority="high" decoding="async">
    </a>
</section>

<section class="marquee" aria-label="Current offer">
    <div><span>BUY 2 — GET 20% OFF</span><i>•</i><span>BUY 3 — GET 30% OFF</span><i>•</i><span>BUY 4 — GET 40% OFF</span><i>•</i><span>BUY 5+ — GET 50% OFF MAX</span><i>•</i><span>BUY 2 — GET 20% OFF</span><i>•</i><span>BUY 3 — GET 30% OFF</span><i>•</i><span>BUY 4 — GET 40% OFF</span><i>•</i><span>BUY 5+ — GET 50% OFF MAX</span></div>
</section>

<section class="trust-row" aria-label="Shopping benefits">
    <div><span>↩</span><small><b>Easy returns</b>7-day window</small></div>
    <div><span>₹</span><small><b>Pay your way</b>UPI or COD</small></div>
    <div><span>♢</span><small><b>Made for India</b>Pan-India delivery</small></div>
</section>

<section class="deal-strip" aria-label="Daily deal timer">
    <div class="deal-icon">ϟ</div>
    <div><small>Ends tonight</small><strong>Cosy hour deals</strong></div>
    <div class="countdown" data-countdown="7617"><span>02</span>:<span>06</span>:<span>57</span></div>
</section>

<section class="section products-section" id="products">
    <div class="section-heading"><div><span>Picked for you</span><h2>Comfy favourites</h2></div><button class="filter-button" type="button" data-toast="All cosy picks are already showing">☷ Filter</button></div>
    <div class="product-grid">
        <?php $cardIndex = 0; foreach ($catalogProducts as $product): ?>
            <?php $cardReview = productReviewData($product); ?>
            <article class="product-card" data-product-name="<?= e(strtolower($product['name'] . ' ' . $product['color'])) ?>">
                <a class="product-image" href="product.php?id=<?= $product['id'] ?>" aria-label="View <?= e($product['name']) ?>">
                    <span class="product-badge"><?= e($product['badge']) ?></span>
                    <img src="<?= e($product['image']) ?>" alt="<?= e($product['name']) ?>" loading="<?= $cardIndex++ < 2 ? 'eager' : 'lazy' ?>" decoding="async">
                </a>
                <button class="card-heart favourite-toggle" type="button" aria-label="Save <?= e($product['name']) ?>">♡</button>
                <div class="product-info">
                    <div class="rating"><span><?= number_format($cardReview['overall'], 1) ?> ★</span> (<?= $cardReview['ratings'] ?>)</div>
                    <a class="product-name" href="product.php?id=<?= $product['id'] ?>"><?= e($product['name']) ?></a>
                    <div class="price-row"><strong><?= money($product['price']) ?></strong><del><?= money($product['mrp']) ?></del><span><?= discountPercent($product) ?>% off</span></div>
                    <div class="delivery-note">Free delivery</div>
                    <form class="card-add-form" method="post" action="cart-action.php" target="cart-background-target" data-add-to-cart data-product-id="<?= $product['id'] ?>" data-product-name="<?= e($product['short_name']) ?>" data-product-price="<?= (float) $product['price'] ?>">
                        <?= csrfField() ?>
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                        <input type="hidden" name="action" value="add">
                        <button class="card-add-button" type="submit"><span class="ui-icon icon-shopping-cart" aria-hidden="true"></span> Add to cart</button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <?php if (!$catalogProducts): ?><div class="no-products"><span>⌕</span><strong>Catalogue is being refreshed</strong><p>Please check back in a little while.</p></div><?php endif; ?>
    <div class="no-products" hidden><span>⌕</span><strong>No cosy picks found</strong><p>Try searching for chair, blanket or pouf.</p></div>
</section>

<section class="care-card">
    <div class="care-icon">♥</div>
    <div><small>THE COMFYNEST PROMISE</small><h2>Comfort without the worry.</h2><p>Easy returns, friendly support and carefully packed orders—always.</p></div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
