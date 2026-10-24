<?php
require __DIR__ . '/includes/init.php';
if (!cartCount()) redirect('cart.php');
if (empty($_SESSION['address'])) redirect('address.php');

$methods = [
    'phonepe' => ['label' => 'PhonePe', 'mark' => 'पे', 'class' => 'phonepe'],
    'gpay' => ['label' => 'Google Pay', 'mark' => 'G', 'class' => 'gpay'],
    'paytm' => ['label' => 'Paytm', 'mark' => 'pay', 'class' => 'paytm'],
    'upi' => ['label' => 'Other UPI app', 'mark' => 'UPI', 'class' => 'upi'],
    'qr_scan' => ['label' => 'Scan QR code', 'mark' => '▦', 'class' => 'qr'],
];
foreach (($paymentSettings['methods'] ?? []) as $methodKey => $enabled) {
    if (!$enabled) unset($methods[$methodKey]);
}
$verificationReady = gatewayVerificationReady();
$upiReady = upiPaymentsReady($paymentSettings);
$selected = (string) ($_SESSION['payment']['method'] ?? array_key_first($methods));
if (!isset($methods[$selected])) $selected = (string) array_key_first($methods);
$total = cartSubtotal($products);
$paymentReference = ''; // Generated server-side when a payment is started.
$pageTitle = 'Payment — ComfyNest';
$layout = 'back';
$headerTitle = 'PAYMENT';
$backHref = 'summary.php';
$pageClass = 'checkout-page payment-page';
$checkoutStep = 4;
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/checkout-progress.php';
?>

<form class="payment-form" method="post" action="place-order.php" data-upi-payment-form>
    <?= csrfField() ?>
    <div class="payment-heading"><div><small>ONE LAST STEP</small><h1>Select payment method</h1></div><span>✓ 100%<br>SAFE PAYMENTS</span></div>

    <?php if ($upiReady): ?><div class="online-offer"><span class="offer-mark">पे</span><div><strong>Pay securely with your preferred UPI app</strong><small><?= $verificationReady ? 'Paytm-compatible payments are verified automatically' : 'Paytm / QR wait for merchant confirmation; PhonePe native is untracked' ?></small></div></div><?php else: ?><div class="online-offer upi-unavailable"><span class="offer-mark">!</span><div><strong>Online UPI verification is temporarily unavailable</strong><small>Payments are temporarily unavailable. Please try again later.</small></div></div><?php endif; ?>

    <h2 class="method-label">PAY ONLINE</h2>
    <section class="payment-methods">
        <div class="method-group-title"><span>UPI</span><strong>UPI apps</strong><small>⌄</small></div>
        <?php foreach ($methods as $key => $method): ?>
            <label class="payment-option">
                <input type="radio" name="payment_method" value="<?= e($key) ?>" <?= $selected === $key ? 'checked' : '' ?> <?= !$upiReady ? 'disabled' : '' ?>>
                <span class="radio-dot"></span>
                <b><?= e($method['label']) ?></b>
                <?php if ($key === 'paytm'): ?><em>₹50 cashback</em><?php endif; ?>
                <i class="pay-logo <?= e($method['class']) ?>"><?= e($method['mark']) ?></i>
            </label>
        <?php endforeach; ?>
    </section>

    <div class="payment-note"><span>♢</span><p>Your payment details are encrypted and never stored by ComfyNest.</p></div>
    <div class="legal-copy">By placing this order, you agree to ComfyNest's terms, returns and privacy policy.</div>
    <div class="form-bottom-space"></div>
    <div class="sticky-action checkout-action">
        <div><strong><?= money($total) ?></strong><small>Total payable</small></div>
        <button class="button primary" type="submit" <?= !$upiReady ? 'disabled' : '' ?>><span data-payment-submit-label><?= !$upiReady ? 'Payment unavailable' : ($selected === 'qr_scan' ? 'Show payment QR' : 'Open ' . e($methods[$selected]['label'])) ?></span> <span>→</span></button>
    </div>
</form>

<section class="upi-payment-modal" data-upi-payment-modal hidden aria-modal="true" role="dialog" aria-labelledby="upi-modal-title">
    <button class="upi-modal-backdrop" type="button" data-upi-close aria-label="Close payment window"></button>
    <article class="upi-modal-card">
        <button class="upi-modal-close" type="button" data-upi-close aria-label="Close">×</button>
        <div class="upi-modal-mark" data-upi-modal-mark>पे</div>
        <small>COMFYNEST UPI PAYMENT</small>
        <h2 id="upi-modal-title" data-upi-modal-title>Complete your payment</h2>
        <p data-upi-modal-copy>Finish the payment in your UPI app, then return here.</p>
        <div class="upi-qr-panel" data-upi-qr-panel hidden><img data-upi-qr-image alt="ComfyNest UPI payment QR code" referrerpolicy="no-referrer"><span>Scan from any UPI app</span></div>
        <div class="upi-payment-details"><div><span>Amount</span><strong><?= money($total) ?></strong></div><div><span>Reference</span><strong><span data-upi-reference><?= e($paymentReference ?: 'Generated when you pay') ?></span></strong></div></div>
        <p class="upi-settlement-note">Your order is confirmed only after a matching SUCCESS transaction is verified. Never enter your UPI PIN on this website.</p>
        <div class="upi-modal-actions"><button class="button secondary" type="button" data-upi-open-again>Open app again</button><button class="button primary" type="button" data-upi-complete>I've paid — continue</button></div>
        <button class="button secondary" type="button" data-upi-cancel hidden style="width:100%;margin-top:10px">I did not pay — cancel</button>
    </article>
</section>

<script>window.ComfyNestUPIPayment = <?= json_encode([
    'ready' => $upiReady,
    'upiId' => (string) ($paymentSettings['upi_id'] ?? ''),
    'payeeName' => (string) ($paymentSettings['payee_name'] ?? 'ComfyNest'),
    'amount' => (float) $total,
    'reference' => $paymentReference,
    'csrf' => (string) $_SESSION['csrf_token'],
    'initiateUrl' => 'initiate-payment.php',
    'verifyUrl' => 'verify-payment.php',
    'failureUrl' => 'payment-failed.php',
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
<script src="assets/js/upi-payment.js?v=20261024-reference-1" defer></script>

<?php require __DIR__ . '/includes/footer.php'; ?>
