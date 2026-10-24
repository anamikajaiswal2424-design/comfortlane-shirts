<?php

function catalogFilePath(): string
{
    return __DIR__ . '/../data/products.json';
}

function loadProductCatalog(array $fallback = []): array
{
    $path = catalogFilePath();
    $json = cloudReadText($path);
    if ($json === false) {
        return $fallback;
    }

    $decoded = json_decode($json, true);
    $rows = is_array($decoded) ? ($decoded['products'] ?? $decoded) : [];
    if (!is_array($rows)) {
        return $fallback;
    }

    $products = [];
    $position = 0;
    foreach ($rows as $row) {
        if (!is_array($row) || empty($row['id']) || empty($row['name'])) {
            continue;
        }
        $position++;
        $id = (int) $row['id'];
        $row['id'] = $id;
        $row['sort_order'] = isset($row['sort_order']) ? (int) $row['sort_order'] : $position * 10;
        $row['is_show'] = (bool) ($row['is_show'] ?? true);
        $row['images'] = array_values(array_filter((array) ($row['images'] ?? [$row['image'] ?? ''])));
        $row['image'] = (string) ($row['image'] ?? $row['images'][0] ?? 'assets/images/cloud-beanbag.jpg');
        $products[$id] = $row;
    }

    return $products ? sortProductCatalog($products) : sortProductCatalog($fallback);
}

function saveProductCatalog(array $products, array $meta = []): bool
{
    $products = sortProductCatalog($products);
    $position = 0;
    foreach ($products as &$product) {
        $position++;
        $product['sort_order'] = $position * 10;
    }
    unset($product);
    $payload = [
        'version' => 1,
        'updated_at' => date(DATE_ATOM),
        'meta' => $meta,
        'products' => array_values($products),
    ];
    $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    return $json !== false && cloudWriteText(catalogFilePath(), $json) !== false;
}

function sortProductCatalog(array $products): array
{
    $rows = array_values($products);
    usort($rows, static function (array $first, array $second): int {
        $orderComparison = ((int) ($first['sort_order'] ?? PHP_INT_MAX)) <=> ((int) ($second['sort_order'] ?? PHP_INT_MAX));
        return $orderComparison !== 0 ? $orderComparison : ((int) ($first['id'] ?? 0) <=> (int) ($second['id'] ?? 0));
    });
    $sorted = [];
    foreach ($rows as $row) {
        if (!isset($row['id'])) continue;
        $sorted[(int) $row['id']] = $row;
    }
    return $sorted;
}

function reorderProductCatalog(array $products, int $productId, string $direction): array
{
    $ordered = array_values(sortProductCatalog($products));
    $currentIndex = null;
    foreach ($ordered as $index => $product) {
        if ((int) ($product['id'] ?? 0) === $productId) {
            $currentIndex = $index;
            break;
        }
    }
    if ($currentIndex === null) return $products;

    $targetIndex = match ($direction) {
        'up' => max(0, $currentIndex - 1),
        'down' => min(count($ordered) - 1, $currentIndex + 1),
        'top' => 0,
        'bottom' => count($ordered) - 1,
        default => $currentIndex,
    };
    if ($targetIndex === $currentIndex) return sortProductCatalog($products);

    $moving = $ordered[$currentIndex];
    array_splice($ordered, $currentIndex, 1);
    array_splice($ordered, $targetIndex, 0, [$moving]);
    $result = [];
    foreach ($ordered as $index => $product) {
        $product['sort_order'] = ($index + 1) * 10;
        $result[(int) $product['id']] = $product;
    }
    return $result;
}

function cleanCatalogText(?string $value): string
{
    $plain = html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $clean = preg_replace('/\s+/u', ' ', $plain);
    return trim($clean ?? $plain);
}

function shortCatalogText(string $value, int $maxLength): string
{
    if (function_exists('mb_strimwidth')) {
        return rtrim(mb_strimwidth($value, 0, $maxLength, '…', 'UTF-8'));
    }
    return strlen($value) <= $maxLength ? $value : rtrim(substr($value, 0, max(1, $maxLength - 3))) . '...';
}

function extractCatalogFeatures(string $html): array
{
    $features = [];
    if (preg_match_all('/<li\b[^>]*>(.*?)<\/li>/is', $html, $matches)) {
        foreach ($matches[1] as $match) {
            $feature = shortCatalogText(cleanCatalogText($match), 170);
            if ($feature !== '' && !in_array($feature, $features, true)) {
                $features[] = $feature;
            }
            if (count($features) >= 6) break;
        }
    }

    if (!$features) {
        $plain = cleanCatalogText($html);
        if ($plain !== '') {
            $features[] = shortCatalogText($plain, 170);
        }
    }

    return array_slice($features, 0, 6);
}

function validCatalogImage(string $value): ?string
{
    $value = trim($value);
    if ($value === '') return null;
    if (filter_var($value, FILTER_VALIDATE_URL)) {
        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
        return in_array($scheme, ['http', 'https'], true) ? $value : null;
    }
    return preg_match('#^assets/images/[a-zA-Z0-9._/-]+$#', $value) ? $value : null;
}

function mapCsvProduct(array $row, int $fallbackId): ?array
{
    $name = cleanCatalogText((string) ($row['name'] ?? $row['unique_name'] ?? ''));
    $price = (float) ($row['selling_price'] ?? 0);
    $mrp = (float) ($row['mrp'] ?? 0);
    if ($name === '' || $price <= 0) return null;

    $id = max(1, (int) ($row['id'] ?? $fallbackId));
    $images = [];
    for ($index = 1; $index <= 5; $index++) {
        $image = validCatalogImage((string) ($row['img' . $index] ?? ''));
        if ($image && !in_array($image, $images, true)) $images[] = $image;
    }
    if (!$images) $images[] = 'assets/images/cloud-beanbag.jpg';

    $features = extractCatalogFeatures((string) ($row['fetaures'] ?? $row['features'] ?? ''));
    if (!$features) $features = ['Comfort-forward design for everyday use'];
    $description = shortCatalogText(implode(' ', array_slice($features, 0, 2)), 310);
    $mrp = max($price, $mrp);

    return [
        'id' => $id,
        'source_index' => cleanCatalogText((string) ($row['index'] ?? '')),
        'name' => shortCatalogText($name, 420),
        'short_name' => shortCatalogText($name, 78),
        'price' => round($price, 2),
        'mrp' => round($mrp, 2),
        'rating' => 0,
        'reviews' => 0,
        'badge' => $id === 1 ? 'Bestseller' : 'New arrival',
        'image' => $images[0],
        'images' => $images,
        'color' => cleanCatalogText((string) ($row['color'] ?? 'Standard')) ?: 'Standard',
        'size' => cleanCatalogText((string) ($row['size'] ?? 'Standard')) ?: 'Standard',
        'storage' => cleanCatalogText((string) ($row['storage'] ?? 'Not applicable')) ?: 'Not applicable',
        'category' => cleanCatalogText((string) ($row['category'] ?? '')),
        'description' => $description,
        'features' => $features,
        'sort_order' => isset($row['sort_order']) && is_numeric($row['sort_order']) ? (int) $row['sort_order'] : $fallbackId * 10,
        'is_show' => !isset($row['is_show']) || (string) $row['is_show'] === '1',
        'imported_at' => date(DATE_ATOM),
    ];
}

function importProductCsv(string $csvPath, array $existingProducts, string $mode = 'replace'): array
{
    $handle = fopen($csvPath, 'rb');
    if (!$handle) throw new RuntimeException('The CSV file could not be opened.');

    $rawHeaders = fgetcsv($handle, 0, ',', '"', '');
    if (!$rawHeaders) {
        fclose($handle);
        throw new RuntimeException('The CSV file has no header row.');
    }

    $headers = array_map(static function ($header): string {
        return strtolower(trim((string) $header, " \t\n\r\0\x0B\xEF\xBB\xBF"));
    }, $rawHeaders);
    foreach (['id', 'name', 'selling_price', 'mrp', 'img1'] as $required) {
        if (!in_array($required, $headers, true)) {
            fclose($handle);
            throw new RuntimeException('Missing required CSV column: ' . $required);
        }
    }

    $imported = $mode === 'merge' ? $existingProducts : [];
    $count = 0;
    $skipped = 0;
    $rowNumber = 1;
    $nextSortOrder = $existingProducts ? max(array_map(static fn(array $product): int => (int) ($product['sort_order'] ?? 0), $existingProducts)) + 10 : 10;
    while (($values = fgetcsv($handle, 0, ',', '"', '')) !== false) {
        $rowNumber++;
        if (count($values) < count($headers)) {
            $values = array_pad($values, count($headers), '');
        } elseif (count($values) > count($headers)) {
            $values = array_slice($values, 0, count($headers));
        }
        $row = array_combine($headers, $values);
        if (!is_array($row)) {
            $skipped++;
            continue;
        }
        $product = mapCsvProduct($row, $rowNumber);
        if (!$product) {
            $skipped++;
            continue;
        }
        if ($mode === 'merge' && isset($existingProducts[$product['id']])) {
            $product['sort_order'] = (int) ($existingProducts[$product['id']]['sort_order'] ?? $product['sort_order']);
        } elseif ($mode === 'merge') {
            $product['sort_order'] = $nextSortOrder;
            $nextSortOrder += 10;
        }
        $imported[$product['id']] = $product;
        $count++;
    }
    fclose($handle);

    if ($count === 0) throw new RuntimeException('No valid products were found in the CSV file.');
    if (!saveProductCatalog($imported, ['last_import_file' => basename($csvPath), 'last_import_count' => $count, 'mode' => $mode])) {
        throw new RuntimeException('The product catalogue could not be saved.');
    }

    return ['imported' => $count, 'skipped' => $skipped, 'total' => count($imported)];
}
