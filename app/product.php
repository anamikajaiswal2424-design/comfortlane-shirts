<?php
require __DIR__ . '/includes/init.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'buy') {
    if (!validCsrf()) { http_response_code(403); exit('Session expired. Reload the product page and try again.'); }
    $buyId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT) ?: 0;
    if (!isset($products[$buyId]) || !($products[$buyId]['is_show'] ?? true)) redirect('index.php');
    $size = strtoupper(trim((string) ($_POST['size'] ?? 'M')));
    if (!in_array($size, productSizes(), true)) $size = 'M';
    $_SESSION['cart'] ??= [];
    $_SESSION['cart_sizes'] ??= [];
    $_SESSION['cart'][$buyId] = min(8, max(1, (int) ($_SESSION['cart'][$buyId] ?? 0) + 1));
    $_SESSION['cart_sizes'][$buyId] = $size;
    redirect('address.php');
}
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 1;
$product = productById($products, $id);
if (!$product || !($product['is_show'] ?? true)) {
    http_response_code(404);
    $visibleCatalog = visibleProducts($products);
    $product = reset($visibleCatalog) ?: $seedProducts[1];
}
$productImages = array_values(array_filter((array) ($product['images'] ?? [$product['image']])));
$reviewData = productReviewData($product);
$schemaBaseUrl = siteUrl();
$structuredData = [
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => $product['name'],
    'description' => $product['description'],
    'image' => $productImages,
    'sku' => (string) $product['id'],
    'brand' => ['@type' => 'Brand', 'name' => $business['brand_name']],
    'offers' => [
        '@type' => 'Offer',
        'url' => $schemaBaseUrl . '/product.php?id=' . $product['id'],
        'priceCurrency' => 'INR',
        'price' => (string) $product['price'],
        'availability' => 'https://schema.org/InStock',
        'itemCondition' => 'https://schema.org/NewCondition',
    ],
];
$pageTitle = $product['name'] . ' — ComfyNest';
$pageDescription = $product['description'];
$socialImagePath = $product['image'];
$pageCanonicalPath = 'product.php?id=' . $product['id'];
$pageCommerceEvent = catalogCommercePayload([catalogTrackingItem($product)], (float) $product['price']);
$layout = 'product';
$backHref = 'index.php';
$pageClass = 'product-page';
require __DIR__ . '/includes/header.php';
?>

<style>
.product-page .product-actions { display:grid; grid-template-columns:1fr 1.2fr; gap:10px; z-index:50; }
.product-page .product-actions form { min-width:0; width:100%; margin:0; }
.product-page .product-actions .button { width:100%; min-height:48px; white-space:nowrap; }
.product-page .consent-banner { bottom:calc(88px + env(safe-area-inset-bottom)); }
@media(min-width:700px) { .product-page .consent-banner { bottom:calc(116px + env(safe-area-inset-bottom)); } }
</style>
<section class="product-gallery" aria-label="Product gallery">
    <div class="gallery-frame">
        <span class="gallery-label"><?= e($product['badge']) ?></span>
        <img class="gallery-main" src="<?= e($productImages[0]) ?>" alt="<?= e($product['name']) ?>" data-gallery-main>
        <button class="gallery-share" type="button" data-share aria-label="Share this product">↗</button>
    </div>
    <div class="gallery-bottom">
        <div class="thumbnail-carousel" aria-label="Product image thumbnails">
            <button class="gallery-scroll-button" type="button" data-gallery-prev aria-label="Previous thumbnails" disabled>‹</button>
            <div class="thumbnail-strip" data-gallery-strip>
            <?php foreach ($productImages as $imageIndex => $image): ?>
                <button class="thumbnail <?= $imageIndex === 0 ? 'active' : '' ?>" type="button" data-gallery-thumb aria-label="View image <?= $imageIndex + 1 ?>" aria-pressed="<?= $imageIndex === 0 ? 'true' : 'false' ?>" data-image-index="<?= $imageIndex + 1 ?>" data-image-src="<?= e($image) ?>"><img src="<?= e($image) ?>" alt="<?= e($product['color']) ?> view <?= $imageIndex + 1 ?>" loading="lazy"></button>
            <?php endforeach; ?>
        </div>
            <button class="gallery-scroll-button" type="button" data-gallery-next aria-label="Next thumbnails">›</button>
        </div>
        <span class="image-count" data-image-count>1 / <?= count($productImages) ?></span>
    </div>
</section>

<section class="product-summary">
    <div class="rating-line"><span>★ <?= number_format($reviewData['overall'], 1) ?></span><b><?= number_format($reviewData['ratings']) ?> ratings</b><i>Verified comfort</i></div>
    <h1><?= e($product['name']) ?></h1>
    <p class="product-subline">Cloud-soft everyday seating · <?= e($product['color']) ?></p>
    <div class="detail-price"><strong><?= money($product['price']) ?></strong><del><?= money($product['mrp']) ?></del><span><?= discountPercent($product) ?>% off</span></div>
    <p class="tax-note">Inclusive of all taxes · Free delivery</p>
</section>

<section class="product-block">
    <div class="block-title"><h2>Select size</h2><span data-selected-size-label>M</span></div>
    <div class="size-options" role="group" aria-label="Select product size">
        <?php foreach (productSizes() as $size): ?>
            <button class="size-option <?= $size === 'M' ? 'selected' : '' ?>" type="button" data-size-option="<?= e($size) ?>" aria-pressed="<?= $size === 'M' ? 'true' : 'false' ?>"><?= e($size) ?></button>
        <?php endforeach; ?>
    </div>
    <div class="quantity-offer" aria-label="Buy more and save more">
        <div><span>％</span><strong>Buy more, save more</strong><small>Discount applies automatically in your cart</small></div>
        <ul>
            <li><b>Buy 2</b><span>20% off</span></li>
            <li><b>Buy 3</b><span>30% off</span></li>
            <li><b>Buy 4</b><span>40% off</span></li>
            <li><b>Buy 5+</b><span>50% off max</span></li>
        </ul>
    </div>
</section>

<section class="delivery-check">
    <span class="delivery-pin">⌖</span>
    <div><small>DELIVERING ACROSS INDIA</small><strong>Free delivery in 3–4 days</strong><p>Cash on delivery available</p></div>
    <button type="button" data-toast="Pincode availability checked">Check</button>
</section>

<section class="product-block about-product">
    <div class="block-title"><h2>Why you'll love it</h2></div>
    <p><?= e($product['description']) ?></p>
    <ul>
        <?php foreach ($product['features'] as $feature): ?>
            <li><span>✓</span><?= e($feature) ?></li>
        <?php endforeach; ?>
    </ul>
</section>

<section class="ratings-reviews" aria-labelledby="ratings-title">
    <div class="reviews-section-heading"><div><h2 id="ratings-title">Product Ratings &amp; Reviews</h2><p>Customer-style preview using this product's own images</p></div><span>DEMO FEEDBACK</span></div>
    <div class="ratings-overview">
        <div class="overall-score">
            <strong><?= number_format($reviewData['overall'], 1) ?><span>★</span></strong>
            <small><?= number_format($reviewData['ratings']) ?><br>Ratings</small>
            <small><?= number_format($reviewData['reviews_count']) ?><br>Reviews</small>
        </div>
        <div class="rating-bars">
            <?php foreach ($reviewData['distribution'] as $distribution): ?>
                <div class="rating-bar-row"><span><?= e($distribution['label']) ?></span><div><i class="<?= e($distribution['class']) ?>" style="width:<?= $distribution['percent'] ?>%"></i></div><b><?= number_format($distribution['count']) ?></b></div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="customer-media">
        <h3>Product images from customers</h3>
        <div class="customer-media-strip">
            <?php foreach (array_slice($productImages, 0, 5) as $image): ?><img src="<?= e($image) ?>" alt="Customer view of <?= e($product['short_name']) ?>" loading="lazy"><?php endforeach; ?>
        </div>
    </div>

    <div class="customer-review-list">
        <?php foreach ($reviewData['reviews'] as $review): ?>
            <article class="customer-review">
                <header><span class="review-avatar"><?= e($review['initial']) ?></span><strong><?= e($review['name']) ?></strong></header>
                <div class="review-meta"><span><?= number_format($review['rating'], 1) ?> ★</span><small>Posted on <?= e($review['date']) ?></small></div>
                <p><?= e($review['text']) ?></p>
                <?php if ($review['images']): ?><div class="review-image-row"><?php foreach ($review['images'] as $image): ?><img src="<?= e($image) ?>" alt="<?= e($product['short_name']) ?> customer photo" loading="lazy"><?php endforeach; ?></div><?php endif; ?>
                <button class="helpful-button" type="button" data-helpful data-count="<?= $review['helpful'] ?>"><span class="ui-icon icon-thumbs-up" aria-hidden="true"></span> Helpful (<b><?= $review['helpful'] ?></b>)</button>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<div class="sticky-action product-actions" role="region" aria-label="Product purchase actions">
    <form method="post" action="cart-action.php" data-add-to-cart data-product-id="<?= $product['id'] ?>" data-product-name="<?= e($product['short_name']) ?>" data-product-price="<?= (float) $product['price'] ?>">
        <?= csrfField() ?>
        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="size" value="M" data-selected-size>
        <button class="button secondary" type="submit"><span class="ui-icon icon-shopping-cart" aria-hidden="true"></span> Add to Cart</button>
    </form>
    <form method="post" action="product.php?id=<?= $product['id'] ?>" data-add-to-cart data-product-id="<?= $product['id'] ?>" data-product-name="<?= e($product['short_name']) ?>" data-product-price="<?= (float) $product['price'] ?>">
        <?= csrfField() ?>
        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
        <input type="hidden" name="action" value="buy">
        <input type="hidden" name="size" value="M" data-selected-size>
        <button class="button primary" type="submit">Buy Now <span>→</span></button>
    </form>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
