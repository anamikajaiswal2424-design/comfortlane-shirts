<?php
// Reference flow: Paytm status long-poll. No MongoDB, webhook or scraper.
require __DIR__ . '/includes/checkout-bootstrap.php';
try {
    $attempt = (array) ($_SESSION['payment_verification'] ?? []);
    if (!hash_equals((string) ($attempt['reference'] ?? ''), (string) ($_POST['txnid'] ?? '')) || !gatewayAttemptMatches($attempt)) {
        checkoutJson(['success' => false, 'error' => 'Checkout changed or expired. Return to payment.'], 409);
    }
    $started = microtime(true);
    do {
        $result = gatewayVerifyCurrent();
        if (in_array($result['status'], ['success', 'failure', 'untracked'], true)) break;
        if (microtime(true) - $started + 2.5 >= 9) break;
        session_write_close();
        usleep(2500000);
        session_start();
    } while (microtime(true) - $started < 9);
    checkoutJson(array_merge(['success' => true, 'amount' => $attempt['amount'],
        'utr' => (string) ($_SESSION['payment_verification']['utr'] ?? '')], $result));
} catch (Throwable $error) {
    checkoutJson(['success' => false, 'error' => $error->getMessage(), 'status' => 'pending'], 409);
}
