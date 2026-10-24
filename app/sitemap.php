<?php
require __DIR__ . '/includes/init.php';
header('Content-Type: application/xml; charset=UTF-8');
$baseUrl = siteUrl();
$lastModified = date('c', max(filemtime(__FILE__), is_file(catalogFilePath()) ? filemtime(catalogFilePath()) : 0));
$pages = [
    ['path' => '/index.php', 'priority' => '1.0', 'frequency' => 'daily'],
    ['path' => '/about.php', 'priority' => '0.6', 'frequency' => 'monthly'],
    ['path' => '/contact.php', 'priority' => '0.7', 'frequency' => 'monthly'],
    ['path' => '/privacy.php', 'priority' => '0.5', 'frequency' => 'monthly'],
    ['path' => '/terms.php', 'priority' => '0.5', 'frequency' => 'monthly'],
    ['path' => '/shipping.php', 'priority' => '0.7', 'frequency' => 'monthly'],
    ['path' => '/returns.php', 'priority' => '0.7', 'frequency' => 'monthly'],
    ['path' => '/payments.php', 'priority' => '0.6', 'frequency' => 'monthly'],
    ['path' => '/cookies.php', 'priority' => '0.4', 'frequency' => 'monthly'],
    ['path' => '/disclaimer.php', 'priority' => '0.4', 'frequency' => 'monthly'],
];
foreach (visibleProducts($products) as $product) {
    $pages[] = ['path' => '/product.php?id=' . (int) $product['id'], 'priority' => '0.8', 'frequency' => 'weekly'];
}
function sitemapXml(string $value): string { return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8'); }
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($pages as $page): ?>
  <url>
    <loc><?= sitemapXml($baseUrl . $page['path']) ?></loc>
    <lastmod><?= sitemapXml($lastModified) ?></lastmod>
    <changefreq><?= $page['frequency'] ?></changefreq>
    <priority><?= $page['priority'] ?></priority>
  </url>
<?php endforeach; ?>
</urlset>
