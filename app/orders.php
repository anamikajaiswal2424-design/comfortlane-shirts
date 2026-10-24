<?php
require __DIR__ . '/includes/init.php';

$order = $_SESSION['last_order'] ?? null;
// Only the verified success route may expose the Purchase payload.
$successRoute = in_array(basename((string) ($_SERVER['SCRIPT_NAME'] ?? '')), ['thankyou.php', 'success.php'], true);
$verifiedSuccess = $successRoute && is_array($order)
    && ($order['payment_status'] ?? '') === 'Verified — SUCCESS'
    && !empty($order['payment_verified_at']) && !empty($order['transaction_id']);
$pageTitle = $order ? 'Thank you for your order — ComfyNest' : 'My orders — ComfyNest';
$layout = 'back';
$headerTitle = 'MY ORDERS';
$backHref = 'index.php';
$pageClass = 'success-page orders-page';
$showBottomNav = true;
$activeNav = 'orders';

$deliveryFromLabel = '';
$deliveryByLabel = '';
if ($order) {
    try {
        $placedDate = new DateTimeImmutable((string) ($order['created_at'] ?? 'now'));
    } catch (Throwable $error) {
        $placedDate = new DateTimeImmutable('now');
    }
    try {
        $deliveryFrom = new DateTimeImmutable((string) ($order['delivery_from'] ?? $placedDate->modify('+3 days')->format('Y-m-d')));
        $deliveryBy = new DateTimeImmutable((string) ($order['delivery_by'] ?? $placedDate->modify('+4 days')->format('Y-m-d')));
    } catch (Throwable $error) {
        $deliveryFrom = $placedDate->modify('+3 days');
        $deliveryBy = $placedDate->modify('+4 days');
    }
    $deliveryFromLabel = $deliveryFrom->format('d M');
    $deliveryByLabel = $deliveryBy->format('d M Y');
}

require __DIR__ . '/includes/header.php';
?>

<?php if ($order): ?>
    <section class="thankyou-shell">
        <article class="thankyou-card">
            <a class="brand thankyou-brand" href="index.php" aria-label="ComfyNest home"><span class="brand-mark">C</span><span>Comfy<span>Nest</span></span></a>
            <div class="thankyou-check" aria-hidden="true">✓</div>
            <h1><?= ($order['payment']['method'] ?? '') === 'cod' ? 'Order confirmed' : 'Payment Done' ?></h1>
            <p class="thankyou-intro">Your order has been successfully placed and is now being prepared.</p>

            <div class="order-reference">
                <div><span>Order ID</span><strong><?= e($order['id']) ?></strong></div>
                <div><span>Amount</span><strong><?= money($order['total']) ?></strong></div>
                <div><span>Payment status</span><strong><?= e($order['payment_status']) ?></strong></div>
                <div><span>Comment</span><strong><?= e($order['payment_reference']) ?></strong></div>
                <div><span>Transaction ID</span><strong><?= e($order['transaction_id'] ?? '') ?></strong></div>
            </div>
            <p class="shipping-method"><span aria-hidden="true">◆</span> Shipping method: <?= e($order['shipping_method'] ?? 'Normal Delivery') ?></p>

            <div class="order-progress" aria-label="Order progress">
                <div class="order-stage active"><span class="order-stage-icon">✓</span><b>Order<br>placed</b></div>
                <div class="order-stage active"><span class="order-stage-icon">↗</span><b>Shipment in<br>progress</b></div>
                <div class="order-stage"><span class="order-stage-icon">□</span><b>Order<br>shipped</b></div>
                <div class="order-stage"><span class="order-stage-icon">⌂</span><b>Delivered</b></div>
            </div>

            <div class="delivery-promise">
                <span>Estimated delivery</span>
                <strong><?= e($deliveryFromLabel) ?> – <?= e($deliveryByLabel) ?></strong>
                <small>Maximum delivery date is 4 days after order placement.</small>
            </div>

            <div class="order-compact-products">
                <div class="order-product-images">
                    <?php foreach (array_slice((array) $order['lines'], 0, 4) as $line): ?>
                        <img src="<?= e($line['image']) ?>" alt="<?= e($line['short_name'] ?? $line['name'] ?? 'Ordered product') ?>">
                    <?php endforeach; ?>
                </div>
                <div><span><?= count((array) $order['lines']) ?> item<?= count((array) $order['lines']) === 1 ? '' : 's' ?></span><strong><?= money($order['total']) ?></strong></div>
            </div>

            <div class="thankyou-actions">
                <a class="button primary" href="index.php#products">Continue shopping</a>
                <button class="button secondary" type="button" data-toast="Your order is confirmed and preparation has started">Track order</button>
            </div>
        </article>
    </section>
<?php else: ?>
    <section class="empty-state order-empty"><div class="empty-illustration"><span class="ui-icon icon-package" aria-hidden="true"></span><i>✦</i></div><small>NO ORDERS YET</small><h1>Your cosy history starts here.</h1><p>When you place an order, its confirmation, delivery date and tracking status will show up here.</p><a class="button primary" href="index.php#products">Start shopping</a></section>
<?php endif; ?>

<?php if ($verifiedSuccess): ?><script>window.ComfyNestPurchase = <?= json_encode(orderTrackingPayload($order), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script><?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
