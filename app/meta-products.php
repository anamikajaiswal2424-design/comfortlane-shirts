<?php
require __DIR__ . '/includes/catalog-bootstrap.php';

$rows = catalogFeedRows($products, $business);
sendCatalogFeedHeaders('text/csv; charset=utf-8', 'comfynest-meta-products.csv', catalogFeedEtag($rows, 'meta'));

$output = fopen('php://output', 'wb');
if ($output === false) exit;
fwrite($output, "\xEF\xBB\xBF");
$columns = ['id', 'title', 'description', 'availability', 'condition', 'price', 'link', 'image_link', 'brand', 'product_type', 'color', 'size'];
fputcsv($output, $columns, ',', '"', '');
foreach ($rows as $row) {
    fputcsv($output, array_map(static fn(string $column): string => (string) ($row[$column] ?? ''), $columns), ',', '"', '');
}
fclose($output);
