<?php
$checkoutStep = $checkoutStep ?? 1;
$steps = ['Cart', 'Address', 'Summary', 'Payment'];
?>
<nav class="checkout-progress" aria-label="Checkout progress">
    <?php foreach ($steps as $index => $label): $number = $index + 1; ?>
        <div class="checkout-step <?= $number < $checkoutStep ? 'complete' : '' ?> <?= $number === $checkoutStep ? 'active' : '' ?>">
            <span><?= $number < $checkoutStep ? '✓' : $number ?></span>
            <small><?= $label ?></small>
        </div>
    <?php endforeach; ?>
</nav>
