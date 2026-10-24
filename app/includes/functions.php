<?php

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function money(int|float $amount): string
{
    return '₹' . number_format($amount, 0, '.', ',');
}

function discountPercent(array $product): int
{
    $mrp = (float) ($product['mrp'] ?? 0);
    $price = (float) ($product['price'] ?? 0);
    return $mrp > 0 ? max(0, (int) round((1 - ($price / $mrp)) * 100)) : 0;
}

function productById(array $products, int $id): ?array
{
    return $products[$id] ?? null;
}

function visibleProducts(array $products): array
{
    return array_filter($products, static fn(array $product): bool => (bool) ($product['is_show'] ?? true));
}

function productReviewData(array $product): array
{
    $seed = (int) sprintf('%u', crc32((string) ($product['id'] ?? 0) . '|' . (string) ($product['name'] ?? '')));
    $totalRatings = 6999 + ($seed % 3001);
    $targetRating = 4 + (($seed % 11) / 10);
    $ratingFactor = $targetRating - 4;
    $fivePercent = 48 + (int) round(44 * $ratingFactor);
    $fourPercent = 30 - (int) round(25 * $ratingFactor);
    $threePercent = 12 - (int) round(10 * $ratingFactor);
    $twoPercent = 6 - (int) round(5 * $ratingFactor);
    $onePercent = max(0, 100 - $fivePercent - $fourPercent - $threePercent - $twoPercent);
    $percentages = [$fivePercent, $fourPercent, $threePercent, $twoPercent, $onePercent];
    $counts = [];
    $used = 0;
    foreach ($percentages as $index => $percentage) {
        $count = $index === 4 ? $totalRatings - $used : (int) round($totalRatings * ($percentage / 100));
        $counts[] = max(0, $count);
        $used += $count;
    }
    $weighted = (($counts[0] * 5) + ($counts[1] * 4) + ($counts[2] * 3) + ($counts[3] * 2) + $counts[4]) / max(1, $totalRatings);

    $names = ['Vivek Patil', 'Gopi Shukla', 'Isha Jain', 'Aakash Verma', 'Neha Singh', 'Rohit Mehta', 'Pooja Nair', 'Arjun Khan', 'Meera Joshi', 'Karan Shah', 'Sana Ali', 'Riya Kapoor'];
    $reviewTexts = [
        'Highly recommend this to everyone! The quality feels better than expected.',
        'Looks just like the product photos and arrived in secure packaging.',
        'Amazing quality and fast shipping. It fits perfectly in our space.',
        'Comfortable, useful and a very good value for the price.',
        'The finish is neat and setup was simple. Happy with this purchase.',
        'Good everyday product. Delivery was quick and the colour looks lovely.',
        'A practical choice for the home and the build feels reassuring.',
        'The product is good overall and the images helped us choose confidently.',
    ];
    $ratingOptions = [4.8, 4.7, 4.6, 4.4, 4.2, 4.1];
    $productImages = array_values(array_filter((array) ($product['images'] ?? [$product['image'] ?? ''])));
    $reviews = [];
    for ($index = 0; $index < 4; $index++) {
        $name = $names[($seed + ($index * 5)) % count($names)];
        $text = $reviewTexts[(($seed >> ($index + 1)) + ($index * 3)) % count($reviewTexts)];
        $rating = $ratingOptions[(($seed >> ($index + 2)) + $index) % count($ratingOptions)];
        $daysAgo = 3 + (($seed >> ($index + 4)) % 19) + ($index * 8);
        $reviewImages = [];
        if ($productImages) {
            $imageLimit = min(3, count($productImages));
            for ($imageIndex = 0; $imageIndex < $imageLimit; $imageIndex++) {
                $reviewImages[] = $productImages[($index + $imageIndex) % count($productImages)];
            }
        }
        $reviews[] = [
            'name' => $name,
            'initial' => strtoupper(substr($name, 0, 1)),
            'rating' => $rating,
            'date' => date('F j, Y', strtotime('-' . $daysAgo . ' days')),
            'text' => $text,
            'helpful' => 140 + (($seed + ($index * 173)) % 690),
            'images' => $reviewImages,
        ];
    }

    return [
        'overall' => round($weighted, 1),
        'ratings' => $totalRatings,
        'reviews_count' => (int) round($totalRatings * (0.13 + (($seed % 5) / 100))),
        'distribution' => [
            ['label' => 'Excellent', 'count' => $counts[0], 'percent' => $percentages[0], 'class' => 'excellent'],
            ['label' => 'Very Good', 'count' => $counts[1], 'percent' => $percentages[1], 'class' => 'very-good'],
            ['label' => 'Good', 'count' => $counts[2], 'percent' => $percentages[2], 'class' => 'good'],
            ['label' => 'Average', 'count' => $counts[3], 'percent' => $percentages[3], 'class' => 'average'],
            ['label' => 'Poor', 'count' => $counts[4], 'percent' => $percentages[4], 'class' => 'poor'],
        ],
        'reviews' => $reviews,
    ];
}

function cart(): array
{
    return $_SESSION['cart'] ?? [];
}

function cartCount(): int
{
    return array_sum(array_map('intval', cart()));
}

function productSizes(): array
{
    return ['S', 'M', 'L', 'XL', 'XXL', '3XL', '4XL'];
}

function bundleDiscountPercent(int $quantity): int
{
    if ($quantity >= 5) return 50;
    if ($quantity === 4) return 40;
    if ($quantity === 3) return 30;
    if ($quantity === 2) return 20;
    return 0;
}

function cartLines(array $products): array
{
    $lines = [];
    foreach (cart() as $id => $quantity) {
        if (!isset($products[$id])) {
            continue;
        }
        $product = $products[$id];
        $product['quantity'] = max(1, (int) $quantity);
        $selectedSize = strtoupper((string) ($_SESSION['cart_sizes'][$id] ?? 'M'));
        $product['size'] = in_array($selectedSize, productSizes(), true) ? $selectedSize : 'M';
        $product['line_total'] = $product['price'] * $product['quantity'];
        $lines[] = $product;
    }
    return $lines;
}

function cartBaseSubtotal(array $products): int
{
    return (int) array_sum(array_column(cartLines($products), 'line_total'));
}

function cartOfferDiscount(array $products): int
{
    $baseSubtotal = cartBaseSubtotal($products);
    $percent = bundleDiscountPercent(cartCount());
    $payable = (int) round($baseSubtotal * (100 - $percent) / 100);
    return max(0, $baseSubtotal - $payable);
}

function cartSubtotal(array $products): int
{
    return max(0, cartBaseSubtotal($products) - cartOfferDiscount($products));
}

function cartMrpTotal(array $products): int
{
    $total = 0;
    foreach (cartLines($products) as $line) {
        $total += $line['mrp'] * $line['quantity'];
    }
    return $total;
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e($_SESSION['csrf_token']) . '">';
}

function validCsrf(): bool
{
    return isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string) $_POST['csrf_token']);
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}
