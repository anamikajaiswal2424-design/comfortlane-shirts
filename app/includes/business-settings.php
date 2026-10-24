<?php

function businessSettingsPath(): string
{
    return __DIR__ . '/../data/business.json';
}

function defaultBusinessSettings(): array
{
    return [
        'brand_name' => 'ComfyNest',
        'legal_name' => 'ComfyNest',
        'store_url' => siteUrl(),
        'support_email' => '',
        'support_phone' => '',
        'business_address' => '',
        'country' => 'India',
        'support_hours' => 'Monday–Saturday, 10:00 AM–6:00 PM IST',
        'gstin' => '',
        'return_window_days' => 7,
        'refund_timeline' => '5–7 business days after approval',
        'delivery_timeline' => '3–4 days after order placement',
        'last_policy_update' => '28 August 2026',
    ];
}

function loadBusinessSettings(): array
{
    $defaults = defaultBusinessSettings();
    $path = businessSettingsPath();
    if (!is_file($path)) return $defaults;
    $json = file_get_contents($path);
    $data = $json !== false ? json_decode($json, true) : null;
    return is_array($data) ? array_merge($defaults, $data) : $defaults;
}

function saveBusinessSettings(array $settings): bool
{
    $json = json_encode(array_merge(defaultBusinessSettings(), $settings), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    return $json !== false && file_put_contents(businessSettingsPath(), $json, LOCK_EX) !== false;
}

function businessReady(array $business): bool
{
    return filter_var($business['support_email'] ?? '', FILTER_VALIDATE_EMAIL)
        && trim((string) ($business['support_phone'] ?? '')) !== ''
        && trim((string) ($business['business_address'] ?? '')) !== '';
}

function businessPublicValue(array $business, string $key): string
{
    $value = trim((string) ($business[$key] ?? ''));
    return $value !== '' ? $value : 'Not configured — update in Admin before advertising';
}
