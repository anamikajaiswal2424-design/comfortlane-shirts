<?php

function ordersFilePath(): string
{
    return __DIR__ . '/../data/orders.json';
}

function loadOrders(): array
{
    $path = ordersFilePath();
    if (!is_file($path)) return [];
    $json = file_get_contents($path);
    $data = $json !== false ? json_decode($json, true) : null;
    $orders = is_array($data) ? (array) ($data['orders'] ?? $data) : [];
    usort($orders, static fn(array $first, array $second): int => strcmp((string) ($second['created_at'] ?? ''), (string) ($first['created_at'] ?? '')));
    return $orders;
}

function appendOrder(array $order): bool
{
    $handle = fopen(ordersFilePath(), 'c+');
    if ($handle === false) return false;
    try {
        if (!flock($handle, LOCK_EX)) return false;
        rewind($handle);
        $json = stream_get_contents($handle);
        $data = is_string($json) && $json !== '' ? json_decode($json, true) : null;
        $orders = is_array($data) ? array_values((array) ($data['orders'] ?? $data)) : [];
        foreach ($orders as $savedOrder) {
            if (($savedOrder['id'] ?? '') === ($order['id'] ?? '')) return true;
        }
        $orders[] = $order;
        $orders = array_slice($orders, -2500);
        $payload = ['version' => 1, 'updated_at' => date(DATE_ATOM), 'orders' => $orders];
        $encoded = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($encoded === false) return false;
        rewind($handle);
        ftruncate($handle, 0);
        $written = fwrite($handle, $encoded);
        fflush($handle);
        flock($handle, LOCK_UN);
        return $written !== false;
    } finally {
        fclose($handle);
    }
}

function orderTrackingPayload(array $order): array
{
    $items = [];
    foreach ((array) ($order['lines'] ?? []) as $line) {
        $items[] = catalogTrackingItem($line, (int) ($line['quantity'] ?? 1));
    }
    return [
        'transaction_id' => (string) ($order['transaction_id'] ?? $order['id'] ?? ''),
        'order_id' => (string) ($order['id'] ?? ''),
        'value' => (float) ($order['total'] ?? 0),
        'currency' => 'INR',
        'items' => $items,
    ];
}
