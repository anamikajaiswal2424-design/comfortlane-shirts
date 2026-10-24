<?php

declare(strict_types=1);

/*
 * Optional fixed canonical URL; Vercel deployment variables are the default.
 * Example: https://www.yourdomain.com
 * Do not add a trailing slash.
 */
const COMFYNEST_SITE_URL = ''; // Optional fixed canonical URL, without trailing slash.

function siteUrl(): string
{
    $configuredUrl = trim(COMFYNEST_SITE_URL);
    if ($configuredUrl === '') {
        $configuredUrl = trim((string) (getenv('COMFYNEST_BASE_URL') ?: ''));
    }

    if (filter_var($configuredUrl, FILTER_VALIDATE_URL)) {
        $scheme = strtolower((string) parse_url($configuredUrl, PHP_URL_SCHEME));
        if (in_array($scheme, ['http', 'https'], true)) {
            return rtrim($configuredUrl, '/');
        }
    }

    $deployment = (string) (getenv('VERCEL_PROJECT_PRODUCTION_URL') ?: getenv('VERCEL_URL') ?: '');
    if (preg_match('/^[a-zA-Z0-9.-]+$/', $deployment)) return 'https://' . $deployment;
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    if (in_array($host, ['comfortlane.it.com', 'www.comfortlane.it.com', 'cartiva.it.com', 'www.cartiva.it.com'], true)) return 'https://' . $host;
    return 'https://comfortlane.it.com';
}
