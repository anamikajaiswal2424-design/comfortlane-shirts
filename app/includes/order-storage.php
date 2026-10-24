<?php

function ordersFilePath(): string
{
    return __DIR__ . '/../data/orders.json';
}

function loadOrders(): array
{
    $values = cloudCommand(['HVALS', cloudKey('orders')]);
    $orders = [];
    foreach ((array) $values as $value) {
        $order = json_decode((string) $value, true);
        if (is_array($order)) $orders[] = $order;
    }
    usort($orders, static fn(array $a, array $b): int => strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? '')));
    return $orders;
}

function appendOrder(array $order): bool
{
    return cloudAppendOrder($order);
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
