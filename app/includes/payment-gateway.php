<?php
declare(strict_types=1);

// PHP port of Kitchan's environment-based UPI intents and Paytm status flow.
require_once dirname(__DIR__, 2) . '/setting.php';

function gatewayConfig(): array
{
    $central = comfortlaneSettings();
    return [
        'PAYTM_UPI_ID' => trim((string) ($central['upi_id'] ?? '')),
        'PAYTM_MERCHANT_NAME' => trim((string) ($central['merchant_name'] ?? 'ComfyNest')) ?: 'ComfyNest',
        'PHONEPE_MERCHANT_NAME' => trim((string) ($central['merchant_name'] ?? 'ComfyNest')) ?: 'ComfyNest',
        'PAYTM_MID' => trim((string) ($central['paytm_mid'] ?? '')),
        'PAYTM_ENV' => ($central['paytm_env'] ?? '') === 'test' ? 'test' : 'production',
    ];
}

function gatewayPaymentSettings(): array
{
    $settings = loadPaymentSettings();
    $central = comfortlaneSettings();
    $config = gatewayConfig();
    $settings['upi_id'] = $config['PAYTM_UPI_ID'];
    $settings['payee_name'] = $config['PAYTM_MERCHANT_NAME'];
    $settings['upi_enabled'] = (bool) ($central['upi_enabled'] ?? true);
    $settings['methods'] = array_merge(array_fill_keys(['phonepe', 'gpay', 'paytm', 'upi', 'qr_scan'], true), $settings['methods'] ?? []);
    return $settings;
}

function gatewayVerificationReady(): bool
{
    return gatewayConfig()['PAYTM_MID'] !== '' && function_exists('curl_init');
}

function gatewayCartFingerprint(): string
{
    return hash('sha256', serialize([
        $_SESSION['cart'] ?? [], $_SESSION['cart_sizes'] ?? [],
        cartSubtotal($GLOBALS['products']), $_SESSION['address'] ?? [],
    ]));
}

function gatewayAttemptMatches(array $attempt): bool
{
    return !empty($attempt['reference'])
        && preg_match('/^TXN[0-9a-f]{20,50}$/', (string) $attempt['reference']) === 1
        && hash_equals((string) ($attempt['fingerprint'] ?? ''), gatewayCartFingerprint())
        && (int) ($attempt['created_at'] ?? 0) > time() - 1800;
}

function gatewayBegin(string $method): array
{
    $settings = gatewayPaymentSettings();
    $methods = ['phonepe' => 'PhonePe', 'gpay' => 'Google Pay', 'paytm' => 'Paytm', 'upi' => 'Other UPI app', 'qr_scan' => 'UPI QR code'];
    if (!isset($methods[$method]) || empty($settings['methods'][$method]) || !upiPaymentsReady($settings)) {
        throw new RuntimeException('This payment method is unavailable.');
    }
    $attempt = (array) ($_SESSION['payment_verification'] ?? []);
    if (!gatewayAttemptMatches($attempt) || ($attempt['status'] ?? '') === 'failure') {
        $config = gatewayConfig();
        $attempt = [
            'reference' => 'TXN' . (string) ((int) floor(microtime(true) * 1000)) . bin2hex(random_bytes(8)),
            'amount' => (float) cartSubtotal($GLOBALS['products']),
            'fingerprint' => gatewayCartFingerprint(), 'created_at' => time(),
            'upi_id' => $settings['upi_id'], 'payee_name' => $settings['payee_name'],
            'phonepe_name' => $config['PHONEPE_MERCHANT_NAME'] ?: $settings['payee_name'],
            'mid' => $config['PAYTM_MID'], 'environment' => $config['PAYTM_ENV'],
            'verified' => false, 'transaction_id' => '', 'status' => 'pending',
        ];
    }
    $attempt['method'] = $method;
    $attempt['tracked'] = $method !== 'phonepe';
    $_SESSION['payment_verification'] = $attempt;
    $_SESSION['payment'] = ['method' => $method, 'label' => $methods[$method]];
    $_SESSION['upi_payment_reference'] = $attempt['reference'];
    $_SESSION['order_processing_started_at'] = $attempt['created_at'];
    return $attempt;
}

function gatewayIntents(array $attempt): array
{
    $params = http_build_query([
        'pa' => $attempt['upi_id'], 'pn' => $attempt['payee_name'],
        'am' => number_format((float) $attempt['amount'], 2, '.', ''), 'cu' => 'INR',
        'tn' => $attempt['reference'], 'tr' => $attempt['reference'],
    ], '', '&', PHP_QUERY_RFC3986);
    $payload = [
        'p2pPaymentCheckoutParams' => [
            'checkoutType' => 'COLLECT', 'initialAmount' => (int) round($attempt['amount'] * 100),
            'note' => ['type' => 'text', 'message' => $attempt['reference']], 'supportedInstruments' => -1,
        ],
        'contact' => ['type' => 'EXTERNAL_MERCHANT', 'name' => $attempt['phonepe_name'], 'vpa' => $attempt['upi_id']],
    ];
    $upi = 'upi://pay?' . $params;
    return [
        'upi' => $upi, 'gpay' => 'tez://upi/pay?' . $params,
        'paytm' => 'paytmmp://cash_wallet?' . $params . '&featuretype=money_transfer',
        'phonepe' => 'phonepe://native?data=' . rawurlencode(base64_encode((string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))) . '&id=p2ppayment',
        'qr_scan' => $upi,
    ];
}

// Kept separate from transport so mismatched provider responses can be tested.
function gatewayInterpretStatus(array $data, array $attempt): array
{
    $pending = ['status' => 'pending', 'message' => 'Payment has not been confirmed yet.'];
    if (!hash_equals((string) $attempt['mid'], (string) ($data['MID'] ?? ''))
        || !hash_equals((string) $attempt['reference'], (string) ($data['ORDERID'] ?? ''))) return $pending;
    if (($data['STATUS'] ?? '') === 'TXN_SUCCESS') {
        $amount = (string) ($data['TXNAMOUNT'] ?? '');
        if (!preg_match('/^[0-9]+(?:\.[0-9]{1,2})?$/', $amount)
            || (int) round((float) $amount * 100) !== (int) round((float) $attempt['amount'] * 100)
            || (isset($data['CURRENCY']) && $data['CURRENCY'] !== 'INR')
            ) return $pending;
        $transactionId = trim((string) ($data['TXNID'] ?? '')) ?: trim((string) ($data['BANKTXNID'] ?? '')) ?: (string) $attempt['reference'];
        return ['status' => 'success', 'transaction_id' => $transactionId,
            'utr' => (string) ($data['BANKTXNID'] ?? ''), 'message' => 'Payment verified successfully.'];
    }
    if (in_array(($data['STATUS'] ?? ''), ['TXN_FAILURE', 'FAILED'], true)) {
        $message = (string) ($data['RESPMSG'] ?? '');
        foreach (['Invalid Order Id', 'No record found', 'PENDING'] as $text) {
            if (stripos($message, $text) !== false) return $pending;
        }
        if (!empty($data['TXNID'])) return ['status' => 'failure', 'message' => 'Payment failed. Please check your app before retrying.'];
    }
    return $pending;
}

function gatewayCheckStatus(array $attempt): array
{
    if (empty($attempt['tracked'])) return ['status' => 'untracked', 'message' => 'PhonePe native payments cannot be automatically verified in this flow. Contact the store with your payment receipt.'];
    if (empty($attempt['mid']) || !function_exists('curl_init')) return ['status' => 'pending', 'message' => 'Payment verification is not configured. Contact the store; do not pay again.'];
    $endpoint = ($attempt['environment'] ?? '') === 'test'
        ? 'https://securegw-stage.paytm.in/order/status' : 'https://securegw.paytm.in/order/status';
    $query = http_build_query(['JsonData' => json_encode(['MID' => $attempt['mid'], 'ORDERID' => $attempt['reference']])], '', '&', PHP_QUERY_RFC3986);
    $handle = curl_init($endpoint . '?' . $query);
    curl_setopt_array($handle, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT => 8, CURLOPT_FOLLOWLOCATION => false, CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2, CURLOPT_HTTPHEADER => ['Accept: application/json']]);
    $body = curl_exec($handle);
    $code = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
    curl_close($handle);
    if (!is_string($body) || $code !== 200) return ['status' => 'pending', 'message' => 'Unable to check payment right now. Retrying automatically.'];
    $data = json_decode($body, true);
    return is_array($data) ? gatewayInterpretStatus($data, $attempt) : ['status' => 'pending', 'message' => 'Waiting for a valid payment response.'];
}

function gatewayVerifyCurrent(): array
{
    $attempt = (array) ($_SESSION['payment_verification'] ?? []);
    if (!gatewayAttemptMatches($attempt)) throw new RuntimeException('Checkout changed or expired. Return to payment.');
    if (!empty($attempt['verified'])) return ['status' => 'success', 'message' => 'Payment verified successfully.'];
    if (($attempt['status'] ?? '') === 'failure') return ['status' => 'failure', 'message' => 'Payment failed. Please check your app before retrying.'];
    if (microtime(true) - (float) ($attempt['last_checked'] ?? 0) < 2.5) return ['status' => 'pending', 'message' => 'Checking payment.'];
    $_SESSION['payment_verification']['last_checked'] = microtime(true);
    // Do not block the cart, page navigation or other tabs during the HTTPS call.
    session_write_close();
    $result = gatewayCheckStatus($attempt);
    session_start();
    $current = (array) ($_SESSION['payment_verification'] ?? []);
    if (($current['reference'] ?? '') !== $attempt['reference'] || !gatewayAttemptMatches($current)) {
        throw new RuntimeException('Checkout changed while checking payment.');
    }
    $_SESSION['payment_verification']['status'] = $result['status'];
    if ($result['status'] === 'success') {
        $_SESSION['payment_verification']['verified'] = true;
        $_SESSION['payment_verification']['verified_at'] = date(DATE_ATOM);
        $_SESSION['payment_verification']['transaction_id'] = $result['transaction_id'];
        $_SESSION['payment_verification']['utr'] = $result['utr'];
    }
    return $result;
}
