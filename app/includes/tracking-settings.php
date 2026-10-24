<?php

function trackingSettingsPath(): string
{
    return __DIR__ . '/../data/tracking.json';
}

function defaultTrackingSettings(): array
{
    return [
        'google_tag_id' => '',
        'google_ads_purchase_send_to' => '',
        'meta_pixel_id' => '',
        'google_maps_api_key' => '',
        'currency' => 'INR',
        'analytics_enabled' => true,
        'location_analytics_enabled' => true,
        'updated_at' => '',
    ];
}

function loadTrackingSettings(): array
{
    $defaults = defaultTrackingSettings();
    $path = trackingSettingsPath();
    $json = cloudReadText($path);
    $data = $json !== false ? json_decode($json, true) : null;
    return is_array($data) ? array_merge($defaults, $data) : $defaults;
}

function saveTrackingSettings(array $settings): bool
{
    $payload = array_merge(loadTrackingSettings(), $settings, ['updated_at' => date(DATE_ATOM)]);
    $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    return $json !== false && cloudWriteText(trackingSettingsPath(), $json) !== false;
}

function validateTrackingSettings(array $input, array $current): array
{
    $googleTagId = strtoupper(trim((string) ($input['google_tag_id'] ?? '')));
    $googleAdsSendTo = trim((string) ($input['google_ads_purchase_send_to'] ?? ''));
    $metaPixelId = trim((string) ($input['meta_pixel_id'] ?? ''));
    $mapsApiKey = trim((string) ($input['google_maps_api_key'] ?? ''));
    $errors = [];

    if ($googleTagId !== '' && !preg_match('/^(G-[A-Z0-9]{6,20}|AW-[0-9]{6,20}|GTM-[A-Z0-9]{4,16})$/', $googleTagId)) {
        $errors[] = 'Google Tag ID must look like G-XXXX, AW-XXXX or GTM-XXXX.';
    }
    if ($googleAdsSendTo !== '' && !preg_match('/^AW-[0-9]{6,20}\/[A-Za-z0-9_-]{4,80}$/', $googleAdsSendTo)) {
        $errors[] = 'Google Ads purchase destination must look like AW-123456789/AbCdEf.';
    }
    if ($metaPixelId !== '' && !preg_match('/^[0-9]{5,25}(?:[\s,]+[0-9]{5,25})*$/', $metaPixelId)) {
        $errors[] = 'Meta Pixel IDs must be 5–25 digits each, separated by commas.';
    }
    if ($mapsApiKey !== '' && !preg_match('/^AIza[0-9A-Za-z_-]{20,80}$/', $mapsApiKey)) {
        $errors[] = 'Google Maps API key format is not valid.';
    }

    if ($errors) {
        throw new InvalidArgumentException(implode(' ', $errors));
    }

    return [
        'google_tag_id' => $googleTagId,
        'google_ads_purchase_send_to' => $googleAdsSendTo,
        'meta_pixel_id' => $metaPixelId,
        'google_maps_api_key' => isset($input['remove_google_maps_api_key']) ? '' : ($mapsApiKey !== '' ? $mapsApiKey : (string) ($current['google_maps_api_key'] ?? '')),
        'currency' => 'INR',
        'analytics_enabled' => isset($input['analytics_enabled']),
        'location_analytics_enabled' => isset($input['location_analytics_enabled']),
    ];
}

function trackingIsConfigured(array $tracking): bool
{
    return trim((string) ($tracking['google_tag_id'] ?? '')) !== ''
        || trim((string) ($tracking['google_ads_purchase_send_to'] ?? '')) !== ''
        || trim((string) ($tracking['meta_pixel_id'] ?? '')) !== ''
        || trim((string) ($tracking['google_maps_api_key'] ?? '')) !== ''
        || !empty($tracking['location_analytics_enabled']);
}

function maskedSecret(string $value): string
{
    if ($value === '') return 'Not configured';
    if (strlen($value) < 10) return str_repeat('•', strlen($value));
    return substr($value, 0, 5) . str_repeat('•', max(6, strlen($value) - 9)) . substr($value, -4);
}
