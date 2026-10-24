<?php
require __DIR__ . '/includes/catalog-bootstrap.php';

$rows = catalogFeedRows($products, $business);
sendCatalogFeedHeaders('application/xml; charset=utf-8', 'comfynest-google-products.xml', catalogFeedEtag($rows, 'google'));

$brand = catalogXmlEscape((string) ($business['brand_name'] ?? 'ComfyNest'));
$baseUrl = catalogXmlEscape(catalogConfiguredBaseUrl($business));
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<rss xmlns:g="http://base.google.com/ns/1.0" version="2.0">' . "\n";
echo "  <channel>\n";
echo '    <title>' . $brand . " product catalogue</title>\n";
echo '    <link>' . $baseUrl . "</link>\n";
echo '    <description>Live products available from ' . $brand . "</description>\n";
foreach ($rows as $row) {
    echo "    <item>\n";
    foreach (['id', 'title', 'description', 'link', 'image_link', 'availability', 'price', 'condition', 'brand', 'product_type', 'color', 'size', 'identifier_exists'] as $field) {
        if ((string) ($row[$field] ?? '') === '') continue;
        echo '      <g:' . $field . '>' . catalogXmlEscape((string) $row[$field]) . '</g:' . $field . ">\n";
    }
    foreach ((array) ($row['additional_image_links'] ?? []) as $image) {
        echo '      <g:additional_image_link>' . catalogXmlEscape((string) $image) . "</g:additional_image_link>\n";
    }
    echo "    </item>\n";
}
echo "  </channel>\n</rss>\n";
