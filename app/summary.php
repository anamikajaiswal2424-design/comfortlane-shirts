<?php
require __DIR__ . '/includes/init.php';
if (!cartCount()) redirect('cart.php');
if (empty($_SESSION['address'])) redirect('address.php');
$address = $_SESSION['address'];
$lines = cartLines($products);
$baseSubtotal = cartBaseSubtotal($products);
$offerPercent = bundleDiscountPercent(cartCount());
$offerDiscount = cartOfferDiscount($products);
$subtotal = cartSubtotal($products);
$mrpTotal = cartMrpTotal($products);
$pageTitle = 'Order summary — ComfyNest';
$layout = 'back';
$headerTitle = 'ORDER SUMMARY';
$backHref = 'address.php';
$pageClass = 'checkout-page summary-page';
$checkoutStep = 3;
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/checkout-progress.php';
?>

<section class="summary-section address-summary">
    <div class="summary-title"><span>⌖</span><h1>Delivering to</h1><a href="address.php">Change</a></div>
    <div class="saved-address"><span><?= e($address['type']) ?></span><strong><?= e($address['name']) ?></strong><p><?= e($address['house']) ?>, <?= e($address['area']) ?><?php if ($address['landmark']): ?>, <?= e($address['landmark']) ?><?php endif; ?><br><?= e($address['city']) ?>, <?= e($address['state']) ?> — <?= e($address['pincode']) ?><br>+91 <?= e($address['phone']) ?></p></div>
</section>

<section class="summary-section">
    <div class="summary-title"><span>□</span><h1><?= cartCount() ?> item<?= cartCount() === 1 ? '' : 's' ?> in this order</h1></div>
    <?php foreach ($lines as $line): ?>
        <article class="summary-product"><img src="<?= e($line['image']) ?>" alt="<?= e($line['name']) ?>"><div><strong><?= e($line['short_name']) ?></strong><p>Size <?= e($line['size']) ?> · Qty <?= $line['quantity'] ?></p><span>Delivery in 3–4 days</span></div><b><?= money($line['line_total']) ?></b></article>
    <?php endforeach; ?>
</section>

<section class="price-card summary-price">
    <div class="price-card-title"><h2>Price details</h2></div>
    <div><span>Total MRP</span><b><?= money($mrpTotal) ?></b></div>
    <div><span>Product discount</span><b class="saving">− <?= money($mrpTotal - $baseSubtotal) ?></b></div>
    <?php if ($offerDiscount > 0): ?><div class="bundle-saving"><span>Buy <?= cartCount() >= 5 ? '5+' : cartCount() ?> offer (<?= $offerPercent ?>% off)</span><b class="saving">− <?= money($offerDiscount) ?></b></div><?php endif; ?>
    <div><span>Delivery fee</span><b class="saving">FREE</b></div>
    <div class="total-line"><span>Total payable</span><b><?= money($subtotal) ?></b></div>
    <p>You save <?= money($mrpTotal - $subtotal) ?> on this order</p>
</section>

<div class="legal-copy">Review your address, products and payable amount. You can select the payment method in the next step.</div>
<div class="sticky-action checkout-action">
    <div><strong><?= money($subtotal) ?></strong><small>Free delivery</small></div>
    <a class="button primary" href="payment.php">Continue to payment <span>→</span></a>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
