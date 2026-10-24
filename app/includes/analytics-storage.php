<?php

function analyticsFilePath(): string
{
    return __DIR__ . '/../data/analytics.json';
}

function analyticsEventLogPath(): string
{
    return __DIR__ . '/../data/analytics-events.ndjson';
}

function emptyAnalyticsStore(): array
{
    return ['version' => 1, 'pageviews' => [], 'purchases' => []];
}

function appendAnalyticsEvent(array $event): bool
{
    try { return cloudAppendEvent($event); }
    catch (Throwable $error) { error_log('Optional analytics persistence unavailable.'); return false; }
}

function readAnalyticsStore(): array
{
    $store = emptyAnalyticsStore();
    $events = cloudCommand(['LRANGE', cloudKey('analytics'), 0, -1]);
    $locations = [];
    $purchaseIds = [];
    foreach ((array) $events as $encoded) {
        $event = json_decode((string) $encoded, true);
        if (!is_array($event)) continue;
        $type = (string) ($event['type'] ?? '');
        unset($event['type']);
        if ($type === 'pageview') $store['pageviews'][] = $event;
        elseif ($type === 'location') $locations[(string) ($event['page_view_id'] ?? '')] = $event;
        elseif ($type === 'purchase' && !isset($purchaseIds[(string) ($event['id'] ?? '')])) {
            $store['purchases'][] = $event;
            $purchaseIds[(string) ($event['id'] ?? '')] = true;
        }
    }
    foreach ($store['pageviews'] as &$pageview) {
        $location = $locations[(string) ($pageview['id'] ?? '')] ?? null;
        if (!is_array($location) || ($location['visitor'] ?? '') !== ($pageview['visitor'] ?? '')) continue;
        $pageview['city'] = (string) ($location['city'] ?? '');
        $pageview['state'] = (string) ($location['state'] ?? '');
        $pageview['country'] = (string) ($location['country'] ?? '');
        $pageview['latitude'] = is_numeric($location['latitude'] ?? null) ? (float) $location['latitude'] : null;
        $pageview['longitude'] = is_numeric($location['longitude'] ?? null) ? (float) $location['longitude'] : null;
        $pageview['location_source'] = (string) ($location['source'] ?? '');
    }
    unset($pageview);
    $store['pageviews'] = array_slice(array_values((array) $store['pageviews']), -10000);
    $store['purchases'] = array_slice(array_values((array) $store['purchases']), -2500);
    return $store;
}

function analyticsVisitorKey(): string
{
    return substr(hash('sha256', session_id() . '|ComfyNest visitor'), 0, 24);
}

function analyticsDeviceType(): string
{
    $agent = strtolower((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
    if (preg_match('/bot|crawler|spider|slurp|preview|facebookexternalhit/', $agent)) return 'bot';
    if (preg_match('/ipad|tablet/', $agent)) return 'tablet';
    if (preg_match('/mobile|android|iphone|ipod/', $agent)) return 'mobile';
    return 'desktop';
}

function analyticsPath(): string
{
    $script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'index.php'));
    $allowedQuery = '';
    if ($script === 'product.php' && isset($_GET['id'])) {
        $allowedQuery = '?id=' . max(1, (int) $_GET['id']);
    }
    return $script . $allowedQuery;
}

function recordPageView(string $title, bool $enabled = true): ?string
{
    if (!$enabled || analyticsDeviceType() === 'bot') return null;
    $pageViewId = bin2hex(random_bytes(10));
    $referrerHost = '';
    $referrer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
    if ($referrer !== '') $referrerHost = (string) (parse_url($referrer, PHP_URL_HOST) ?? '');
    $record = [
        'id' => $pageViewId,
        'at' => date(DATE_ATOM),
        'date' => date('Y-m-d'),
        'visitor' => analyticsVisitorKey(),
        'path' => analyticsPath(),
        'title' => substr(strip_tags($title), 0, 150),
        'device' => analyticsDeviceType(),
        'referrer' => substr($referrerHost, 0, 120),
        'city' => '',
        'state' => '',
        'country' => '',
    ];
    return appendAnalyticsEvent(['type' => 'pageview'] + $record) ? $pageViewId : null;
}

function updatePageViewLocation(string $pageViewId, string $city, string $state, string $country, mixed $latitude = null, mixed $longitude = null, string $source = ''): bool
{
    $city = substr(trim(strip_tags($city)), 0, 80);
    $state = substr(trim(strip_tags($state)), 0, 80);
    $country = substr(trim(strip_tags($country)), 0, 80);
    $latitude = is_numeric($latitude) ? round((float) $latitude, 2) : null;
    $longitude = is_numeric($longitude) ? round((float) $longitude, 2) : null;
    if ($latitude !== null && ($latitude < -90 || $latitude > 90)) $latitude = null;
    if ($longitude !== null && ($longitude < -180 || $longitude > 180)) $longitude = null;
    $source = in_array($source, ['ipwhois', 'google', 'browser'], true) ? $source : '';
    if ($pageViewId === '' || ($city === '' && $state === '' && ($latitude === null || $longitude === null))) return false;
    return appendAnalyticsEvent([
        'type' => 'location',
        'page_view_id' => $pageViewId,
        'visitor' => analyticsVisitorKey(),
        'city' => $city,
        'state' => $state,
        'country' => $country,
        'latitude' => $latitude,
        'longitude' => $longitude,
        'source' => $source,
        'at' => date(DATE_ATOM),
    ]);
}

function recordPurchaseEvent(array $order): void
{
    $record = [
        'id' => (string) ($order['id'] ?? ''),
        'transaction_id' => (string) ($order['transaction_id'] ?? ''),
        'at' => date(DATE_ATOM),
        'date' => date('Y-m-d'),
        'value' => (float) ($order['total'] ?? 0),
        'currency' => 'INR',
        'payment_method' => (string) ($order['payment']['method'] ?? ''),
    ];
    appendAnalyticsEvent(['type' => 'purchase'] + $record);
}

function analyticsDashboardData(int $days = 7): array
{
    $store = readAnalyticsStore();
    $pageviews = array_values((array) ($store['pageviews'] ?? []));
    $purchases = array_values((array) ($store['purchases'] ?? []));
    $today = date('Y-m-d');
    $cutoff = date('Y-m-d', strtotime('-' . max(1, $days - 1) . ' days'));
    $todayViews = array_values(array_filter($pageviews, static fn(array $row): bool => ($row['date'] ?? '') === $today));
    $recentViews = array_values(array_filter($pageviews, static fn(array $row): bool => ($row['date'] ?? '') >= $cutoff));
    $todayPurchases = array_values(array_filter($purchases, static fn(array $row): bool => ($row['date'] ?? '') === $today));
    $visitorKeys = array_values(array_unique(array_filter(array_column($pageviews, 'visitor'))));
    $todayVisitorKeys = array_values(array_unique(array_filter(array_column($todayViews, 'visitor'))));

    $trend = [];
    for ($offset = $days - 1; $offset >= 0; $offset--) {
        $date = date('Y-m-d', strtotime('-' . $offset . ' days'));
        $dayViews = array_values(array_filter($pageviews, static fn(array $row): bool => ($row['date'] ?? '') === $date));
        $trend[] = [
            'date' => $date,
            'label' => date('D', strtotime($date)),
            'views' => count($dayViews),
            'visitors' => count(array_unique(array_filter(array_column($dayViews, 'visitor')))),
        ];
    }

    $locations = [];
    foreach ($recentViews as $view) {
        $city = trim((string) ($view['city'] ?? ''));
        $state = trim((string) ($view['state'] ?? ''));
        if ($city === '' && $state === '') continue;
        $country = trim((string) ($view['country'] ?? ''));
        $key = strtolower($city . '|' . $state . '|' . $country);
        if (!isset($locations[$key])) $locations[$key] = [
            'city' => $city ?: 'Unknown city',
            'state' => $state ?: 'Unknown state',
            'country' => $country ?: 'Unknown country',
            'latitude' => is_numeric($view['latitude'] ?? null) ? (float) $view['latitude'] : null,
            'longitude' => is_numeric($view['longitude'] ?? null) ? (float) $view['longitude'] : null,
            'views' => 0,
            'visitors' => [],
        ];
        $locations[$key]['views']++;
        $locations[$key]['visitors'][] = (string) ($view['visitor'] ?? '');
    }
    foreach ($locations as &$location) {
        $location['visitors'] = count(array_unique(array_filter($location['visitors'])));
    }
    unset($location);
    usort($locations, static fn(array $first, array $second): int => $second['views'] <=> $first['views']);

    $popularPages = [];
    foreach ($recentViews as $view) {
        $path = (string) ($view['path'] ?? 'Unknown');
        if (!isset($popularPages[$path])) $popularPages[$path] = ['path' => $path, 'views' => 0];
        $popularPages[$path]['views']++;
    }
    usort($popularPages, static fn(array $first, array $second): int => $second['views'] <=> $first['views']);

    return [
        'total_pageviews' => count($pageviews),
        'total_visitors' => count($visitorKeys),
        'today_pageviews' => count($todayViews),
        'today_visitors' => count($todayVisitorKeys),
        'purchases' => count($purchases),
        'today_purchases' => count($todayPurchases),
        'revenue' => array_sum(array_column($purchases, 'value')),
        'trend' => $trend,
        'locations' => array_slice($locations, 0, 25),
        'popular_pages' => array_slice($popularPages, 0, 10),
    ];
}

function analyticsLiveMapData(int $minutes = 5): array
{
    $minutes = max(1, min(60, $minutes));
    $store = readAnalyticsStore();
    $cutoff = time() - ($minutes * 60);
    $latestByVisitor = [];

    foreach ((array) ($store['pageviews'] ?? []) as $view) {
        $visitor = (string) ($view['visitor'] ?? '');
        $timestamp = strtotime((string) ($view['at'] ?? '')) ?: 0;
        if ($visitor === '' || $timestamp < $cutoff) continue;
        if (!isset($latestByVisitor[$visitor]) || $timestamp > ($latestByVisitor[$visitor]['timestamp'] ?? 0)) {
            $latestByVisitor[$visitor] = ['timestamp' => $timestamp, 'view' => $view];
        }
    }

    $mappedVisitors = [];
    foreach ($latestByVisitor as $visitor => $entry) {
        $view = (array) ($entry['view'] ?? []);
        $latitude = is_numeric($view['latitude'] ?? null) ? (float) $view['latitude'] : null;
        $longitude = is_numeric($view['longitude'] ?? null) ? (float) $view['longitude'] : null;
        if ($latitude === null || $longitude === null || $latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) continue;
        $timestamp = (int) ($entry['timestamp'] ?? 0);
        $mappedVisitors[] = [
            'id' => substr($visitor, 0, 8),
            'city' => (string) ($view['city'] ?? ''),
            'state' => (string) ($view['state'] ?? ''),
            'country' => (string) ($view['country'] ?? ''),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'path' => (string) ($view['path'] ?? ''),
            'device' => (string) ($view['device'] ?? ''),
            'at' => (string) ($view['at'] ?? ''),
            'seconds_ago' => max(0, time() - $timestamp),
        ];
    }
    usort($mappedVisitors, static fn(array $first, array $second): int => strcmp((string) $second['at'], (string) $first['at']));

    return [
        'generated_at' => date(DATE_ATOM),
        'window_minutes' => $minutes,
        'active_visitors' => count($latestByVisitor),
        'mapped_visitors' => count($mappedVisitors),
        'visitors' => array_slice($mappedVisitors, 0, 100),
    ];
}
