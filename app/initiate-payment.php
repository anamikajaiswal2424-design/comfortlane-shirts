<?php
require __DIR__ . '/includes/checkout-bootstrap.php';
try {
    $attempt = gatewayBegin((string) ($_POST['payment_method'] ?? 'paytm'));
    $intents = gatewayIntents($attempt);
    checkoutJson(['success' => true, 'txnid' => $attempt['reference'], 'amount' => $attempt['amount'],
        'tracked' => $attempt['tracked'], 'intents' => $intents,
        'upiUrl' => $intents['upi'], 'paytmIntent' => $intents['paytm'], 'phonepeIntent' => $intents['phonepe'],
        'qrCodeUrl' => 'https://api.qrserver.com/v1/create-qr-code/?data=' . rawurlencode($intents['upi']) . '&size=220x220',
        'merchantName' => $attempt['payee_name'], 'upiId' => $attempt['upi_id']]);
} catch (Throwable $error) {
    checkoutJson(['success' => false, 'error' => $error->getMessage()], 409);
}
