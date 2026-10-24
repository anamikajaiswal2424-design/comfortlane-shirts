<?php
require __DIR__ . '/includes/init.php';
$pageTitle = 'Payment failed — ComfyNest';
$layout = 'back'; $headerTitle = 'PAYMENT'; $backHref = 'payment.php';
$pageClass = 'checkout-page processing-page'; $showSiteFooter = false;
require __DIR__ . '/includes/header.php';
?>
<section class="payment-processing"><div class="processing-card">
<div class="processing-spinner" aria-hidden="true"><span>×</span></div>
<small>PAYMENT NOT COMPLETED</small><h1>Payment failed</h1>
<p>Please check your payment app before retrying. If money was debited, contact the store and do not pay again.</p>
<a class="button primary" href="payment.php">Try again</a>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
