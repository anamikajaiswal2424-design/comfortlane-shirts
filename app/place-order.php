<?php
require __DIR__ . '/includes/init.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validCsrf() || !cartCount() || empty($_SESSION['address'])) {
    redirect('cart.php');
}

$isFinaliseRequest = (string) ($_POST['finalise'] ?? '') === '1';
if (!$isFinaliseRequest) {
    try {
        gatewayBegin((string) ($_POST['payment_method'] ?? ''));
    } catch (Throwable $error) {
        redirect('payment.php');
    }
    redirect('payment.php');
}
$paymentVerification = (array) ($_SESSION['payment_verification'] ?? []);
if (!gatewayAttemptMatches($paymentVerification) || empty($paymentVerification['verified'])) redirect('payment.php');
$paymentMethod = (string) ($_SESSION['payment']['method'] ?? '');
if (empty($paymentVerification['tracked']) || $paymentMethod === 'phonepe') redirect('payment.php');
$orderId = (string) $paymentVerification['reference'];
$upiReference = $orderId;
$transactionId = (string) $paymentVerification['transaction_id'];
$placedDate = new DateTimeImmutable('today');
$order = [
    'id' => $orderId,
    'transaction_id' => $transactionId,
    'utr' => (string) ($paymentVerification['utr'] ?? ''),
    'payment_gateway' => 'paytm',
    'lines' => cartLines($products),
    'total' => cartSubtotal($products),
    'bundle_discount_percent' => bundleDiscountPercent(cartCount()),
    'bundle_discount' => cartOfferDiscount($products),
    'address' => $_SESSION['address'],
    'payment' => $_SESSION['payment'],
    'payment_reference' => $upiReference,
    'payment_status' => 'Verified — SUCCESS',
    'payment_verified_at' => (string) ($paymentVerification['verified_at'] ?? date(DATE_ATOM)),
    'order_status' => 'Confirmed',
    'shipping_method' => 'Normal Delivery',
    'delivery_from' => $placedDate->modify('+3 days')->format('Y-m-d'),
    'delivery_by' => $placedDate->modify('+4 days')->format('Y-m-d'),
    'placed_at' => date('d M Y, h:i A'),
    'created_at' => date(DATE_ATOM),
];

if (!appendOrder($order)) {
    http_response_code(500);
    exit('The order could not be saved. Please go back and try again.');
}
recordPurchaseEvent($order);
$_SESSION['last_order'] = $order;
unset($_SESSION['order_processing_started_at'], $_SESSION['upi_payment_reference'], $_SESSION['payment_verification']);
$_SESSION['cart'] = [];
$_SESSION['cart_sizes'] = [];
redirect('thankyou.php');
