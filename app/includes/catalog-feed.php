<?php

declare(strict_types=1);

function catalogConfiguredBaseUrl(array $business): string
{
    $candidates = [
        siteUrl(),
        trim((string) ($business['store_url'] ?? '')),
        trim((string) (getenv('COMFYNEST_BASE_URL') ?: '')),
    ];
    foreach ($candidates as $candidate) {
        if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_URL)) {
            $scheme = strtolower((string) parse_url($candidate, PHP_URL_SCHEME));
            if (in_array($scheme, ['http', 'https'], true)) return rtrim($candidate, '/');
        }
    }

    return siteUrl();
}

function catalogIsPublicHttpsUrl(string $url): bool
{
    if (!filter_var($url, FILTER_VALIDATE_URL) || strtolower((string) parse_url($url, PHP_URL_SCHEME)) !== 'https') return false;
    $host = strtolower((string) parse_url($url, PHP_URL_HOST));
    if ($host === '' || $host === 'localhost' || str_ends_with($host, '.local')) return false;
    if (filter_var($host, FILTER_VALIDATE_IP)) {
        return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }
    return true;
}

function catalogAbsoluteUrl(string $value, string $baseUrl): string
{
    $value = trim($value);
    if (filter_var($value, FILTER_VALIDATE_URL)) return $value;
    return rtrim($baseUrl, '/') . '/' . ltrim($value, '/');
}

function catalogProductId(array $product): string
{
    return (string) max(1, (int) ($product['id'] ?? 0));
}

function catalogTrackingItem(array $product, int $quantity = 1): array
{
    return [
        'item_id' => catalogProductId($product),
        'item_name' => (string) ($product['short_name'] ?? $product['name'] ?? 'Product'),
        'price' => (float) ($product['price'] ?? 0),
        'quantity' => max(1, $quantity),
    ];
}

function catalogCommercePayload(array $items, float $value): array
{
    return [
        'currency' => 'INR',
        'value' => $value,
        'items' => array_values($items),
    ];
}

function catalogFeedRows(array $products, array $business): array
{
    $baseUrl = catalogConfiguredBaseUrl($business);
    $brand = cleanCatalogText((string) ($business['brand_name'] ?? 'ComfyNest')) ?: 'ComfyNest';
    $rows = [];
    foreach (visibleProducts($products) as $product) {
        $id = catalogProductId($product);
        $images = array_values(array_unique(array_filter(array_map(
            static fn($image): string => catalogAbsoluteUrl((string) $image, $baseUrl),
            (array) ($product['images'] ?? [$product['image'] ?? ''])
        ))));
        $primaryImage = $images[0] ?? catalogAbsoluteUrl('assets/images/cloud-beanbag.jpg', $baseUrl);
        $category = cleanCatalogText((string) ($product['category'] ?? ''));
        $productType = $category !== '' && $category !== '0' ? $category : 'Home & Garden > Furniture';
        $rows[] = [
            'id' => $id,
            'title' => shortCatalogText(cleanCatalogText((string) ($product['name'] ?? 'Product')), 150),
            'description' => shortCatalogText(cleanCatalogText((string) ($product['description'] ?? 'Comfort-forward home product from ' . $brand)), 4900),
            'availability' => 'in_stock',
            'condition' => 'new',
            'price' => number_format((float) ($product['price'] ?? 0), 2, '.', '') . ' INR',
            'link' => $baseUrl . '/product.php?id=' . rawurlencode($id),
            'image_link' => $primaryImage,
            'additional_image_links' => array_slice($images, 1, 9),
            'brand' => $brand,
            'product_type' => $productType,
            'color' => cleanCatalogText((string) ($product['color'] ?? '')),
            'size' => cleanCatalogText((string) ($product['size'] ?? '')),
            'identifier_exists' => 'no',
        ];
    }
    return $rows;
}

function catalogFeedUrls(array $business): array
{
    $baseUrl = catalogConfiguredBaseUrl($business);
    return [
        'google' => $baseUrl . '/google-products.php',
        'meta' => $baseUrl . '/meta-products.php',
    ];
}

function catalogFeedStatus(array $products, array $business): array
{
    $rows = catalogFeedRows($products, $business);
    $baseUrl = catalogConfiguredBaseUrl($business);
    $externalImages = 0;
    $missingDescriptions = 0;
    foreach ($rows as $row) {
        $imageHost = strtolower((string) parse_url((string) $row['image_link'], PHP_URL_HOST));
        $storeHost = strtolower((string) parse_url($baseUrl, PHP_URL_HOST));
        if ($imageHost !== '' && $imageHost !== $storeHost) $externalImages++;
        if (strlen((string) $row['description']) < 30) $missingDescriptions++;
    }
    $warnings = [];
    if (!catalogIsPublicHttpsUrl($baseUrl)) $warnings[] = 'Set your public HTTPS store URL before connecting an ad account.';
    if (!businessReady($business)) $warnings[] = 'Complete the public business, support and return details before catalogue review.';
    if ($externalImages > 0) $warnings[] = $externalImages . ' product image' . ($externalImages === 1 ? ' is' : 's are') . ' hosted on another domain; owned image hosting is safer for approval.';
    if ($missingDescriptions > 0) $warnings[] = $missingDescriptions . ' product description' . ($missingDescriptions === 1 ? ' is' : 's are') . ' too short.';
    return [
        'base_url' => $baseUrl,
        'product_count' => count($rows),
        'public_https' => catalogIsPublicHttpsUrl($baseUrl),
        'external_images' => $externalImages,
        'warnings' => $warnings,
        'ready' => count($rows) > 0 && catalogIsPublicHttpsUrl($baseUrl) && businessReady($business),
    ];
}

function catalogXmlEscape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

function catalogFeedEtag(array $rows, string $channel): string
{
    return '"' . hash('sha256', $channel . '|' . json_encode($rows, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) . '"';
}

function sendCatalogFeedHeaders(string $contentType, string $filename, string $etag): void
{
    header('Content-Type: ' . $contentType);
    header('Content-Disposition: inline; filename="' . $filename . '"');
    header('Cache-Control: public, max-age=300, must-revalidate');
    header('ETag: ' . $etag);
    header('X-Content-Type-Options: nosniff');
    if (trim((string) ($_SERVER['HTTP_IF_NONE_MATCH'] ?? '')) === $etag) {
        http_response_code(304);
        exit;
    }
}
