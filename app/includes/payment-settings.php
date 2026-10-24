<?php

declare(strict_types=1);

function paymentSettingsPath(): string
{
    return __DIR__ . '/../data/payment.json';
}

function defaultPaymentSettings(): array
{
    return [
        'upi_id' => '',
        'payee_name' => 'ComfyNest',
        'upi_enabled' => true,
        'paytm_mid' => '',
        'paytm_env' => 'production',
        'updated_at' => '',
    ];
}

function loadPaymentSettings(): array
{
    $defaults = defaultPaymentSettings();
    $path = paymentSettingsPath();
    $json = cloudReadText($path);
    $data = $json !== false ? json_decode($json, true) : null;
    return is_array($data) ? array_merge($defaults, $data) : $defaults;
}

function validUpiId(string $value): bool
{
    return preg_match('/^[A-Za-z0-9._-]{2,128}@[A-Za-z0-9._-]{2,64}$/', $value) === 1;
}

function validatePaymentSettings(array $input): array
{
    $upiId = strtolower(trim((string) ($input['upi_id'] ?? '')));
    $payeeName = trim((string) ($input['payee_name'] ?? 'ComfyNest'));
    $errors = [];
    if ($upiId !== '' && !validUpiId($upiId)) $errors[] = 'Enter a valid UPI ID such as business@bank.';
    if (strlen($payeeName) < 2 || strlen($payeeName) > 60) $errors[] = 'UPI payee name must contain 2–60 characters.';
    $mid = trim((string) ($input['paytm_mid'] ?? loadPaymentSettings()['paytm_mid']));
    $environment = (string) ($input['paytm_env'] ?? loadPaymentSettings()['paytm_env']);
    if ($mid !== '' && !preg_match('/^[A-Za-z0-9_-]{2,80}$/', $mid)) $errors[] = 'Enter a valid Paytm MID.';
    if (!in_array($environment, ['production', 'test'], true)) $errors[] = 'Invalid Paytm environment.';
    if ($errors) throw new InvalidArgumentException(implode(' ', $errors));
    return [
        'upi_id' => $upiId,
        'payee_name' => $payeeName,
        'upi_enabled' => isset($input['upi_enabled']),
        'paytm_mid' => $mid,
        'paytm_env' => $environment,
    ];
}

function savePaymentSettings(array $settings): bool
{
    $payload = array_merge(loadPaymentSettings(), $settings, ['updated_at' => date(DATE_ATOM)]);
    $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    return $json !== false && cloudWriteText(paymentSettingsPath(), $json) !== false;
}

function upiPaymentsReady(array $settings): bool
{
    return !empty($settings['upi_enabled']) && validUpiId((string) ($settings['upi_id'] ?? ''));
}

function upiPaymentReference(): string
{
    $existing = trim((string) ($_SESSION['upi_payment_reference'] ?? ''));
    $fingerprint = hash('sha256', serialize([$_SESSION['cart'] ?? [], $_SESSION['cart_sizes'] ?? [], cartSubtotal($GLOBALS['products'])]));
    if ($existing !== '' && ($_SESSION['upi_cart_fingerprint'] ?? '') === $fingerprint) return $existing;
    $_SESSION['upi_cart_fingerprint'] = $fingerprint;
    unset($_SESSION['payment_verification']);
    $reference = 'CN' . bin2hex(random_bytes(16));
    $_SESSION['upi_payment_reference'] = $reference;
    return $reference;
}
