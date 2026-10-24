<?php
require __DIR__ . '/includes/init.php';
$pageTitle = 'Your cart — ComfyNest';
$layout = 'back';
$headerTitle = 'YOUR CART';
$backHref = 'index.php';
$pageClass = 'checkout-page';
$checkoutStep = 1;
$lines = cartLines($products);
$baseSubtotal = cartBaseSubtotal($products);
$offerPercent = bundleDiscountPercent(cartCount());
$offerDiscount = cartOfferDiscount($products);
$subtotal = cartSubtotal($products);
$mrpTotal = cartMrpTotal($products);
$checkoutItems = array_map(static fn(array $line): array => catalogTrackingItem($line, (int) $line['quantity']), $lines);
$checkoutCommerceEvent = catalogCommercePayload($checkoutItems, (float) $subtotal);
$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/checkout-progress.php';
?>

<?php if ($flash): ?><div class="inline-notice">✓ <?= e($flash) ?></div><?php endif; ?>

<?php if (!$lines): ?>
    <section class="empty-state">
        <div class="empty-illustration"><span class="ui-icon icon-shopping-cart" aria-hidden="true"></span><i>♥</i></div>
        <small>YOUR CART FEELS LIGHT</small>
        <h1>Let's add some comfort.</h1>
        <p>Cosy chairs, soft blankets and little luxuries are waiting for you.</p>
        <a class="button primary" href="index.php#products">Explore favourites</a>
    </section>
<?php else: ?>
    <section class="cart-list" aria-label="Cart items">
        <?php foreach ($lines as $line): ?>
            <article class="cart-item">
                <a class="cart-thumb" href="product.php?id=<?= $line['id'] ?>"><img src="<?= e($line['image']) ?>" alt="<?= e($line['name']) ?>"></a>
                <div class="cart-item-info">
                    <div class="cart-item-top"><a href="product.php?id=<?= $line['id'] ?>"><?= e($line['short_name']) ?></a>
                        <form method="post" action="cart-action.php"><?= csrfField() ?><input type="hidden" name="product_id" value="<?= $line['id'] ?>"><input type="hidden" name="action" value="remove"><button type="submit" aria-label="Remove <?= e($line['name']) ?>">×</button></form>
                    </div>
                    <p>Size: <?= e($line['size']) ?></p>
                    <div class="cart-price"><strong><?= money($line['line_total']) ?></strong><del><?= money($line['mrp'] * $line['quantity']) ?></del><span><?= discountPercent($line) ?>% off</span></div>
                    <div class="quantity-row">
                        <span>Qty</span>
                        <form class="quantity-control" method="post" action="cart-action.php">
                            <?= csrfField() ?><input type="hidden" name="product_id" value="<?= $line['id'] ?>"><input type="hidden" name="action" value="update">
                            <button name="quantity" value="<?= $line['quantity'] - 1 ?>" type="submit" aria-label="Decrease quantity">−</button>
                            <b><?= $line['quantity'] ?></b>
                            <button name="quantity" value="<?= $line['quantity'] + 1 ?>" type="submit" aria-label="Increase quantity" <?= $line['quantity'] >= 8 ? 'disabled' : '' ?>>+</button>
                        </form>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </section>

    <section class="price-card">
        <div class="price-card-title"><h2>Price details</h2><span><?= cartCount() ?> item<?= cartCount() === 1 ? '' : 's' ?></span></div>
        <div><span>Total MRP</span><b><?= money($mrpTotal) ?></b></div>
        <div><span>Product discount</span><b class="saving">− <?= money($mrpTotal - $baseSubtotal) ?></b></div>
        <?php if ($offerDiscount > 0): ?><div class="bundle-saving"><span>Buy <?= cartCount() >= 5 ? '5+' : cartCount() ?> offer (<?= $offerPercent ?>% off)</span><b class="saving">− <?= money($offerDiscount) ?></b></div><?php endif; ?>
        <div><span>Delivery</span><b class="saving">FREE</b></div>
        <div class="total-line"><span>Order total</span><b><?= money($subtotal) ?></b></div>
        <p>You save <?= money($mrpTotal - $subtotal) ?> on this order</p>
    </section>

    <section class="safety-card">
        <div class="shield">✓</div>
        <div><small>THE COMFYNEST CARE PROMISE</small><h2>Packed with care, delivered safely.</h2><p>Every order is quality-checked before it leaves our nest.</p></div>
        <div class="parcel" aria-hidden="true">📦</div>
    </section>

    <div class="sticky-action checkout-action">
        <div><strong><?= money($subtotal) ?></strong><button type="button" data-scroll-price>View price details</button></div>
        <a class="button primary" href="address.php" data-initiate-checkout>Continue <span>→</span></a>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
