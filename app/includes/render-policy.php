<?php
require __DIR__ . '/init.php';
require_once __DIR__ . '/policy-content.php';
$documents = policyDocuments($business);
if (!isset($documents[$policySlug])) {
    http_response_code(404);
    exit('Policy not found');
}
$document = $documents[$policySlug];
$pageTitle = $document['title'] . ' — ComfyNest';
$pageDescription = $document['description'];
$layout = 'back';
$headerTitle = strtoupper($document['title']);
$backHref = 'index.php';
$pageClass = 'policy-page';
$showSiteFooter = true;
$pageCanonicalPath = basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'index.php'));
require __DIR__ . '/header.php';
?>
<article class="policy-document">
    <nav class="policy-breadcrumb" aria-label="Breadcrumb"><a href="index.php">Home</a><span>›</span><strong><?= e($document['title']) ?></strong></nav>
    <header class="policy-hero"><small>COMFYNEST TRUST CENTRE</small><h1><?= e($document['title']) ?></h1><p><?= e($document['intro']) ?></p><div><span>Effective: <?= e($business['last_policy_update']) ?></span><span><?= businessReady($business) ? 'Business details verified' : 'Business details pending configuration' ?></span></div></header>
    <div class="policy-body">
        <?php foreach ($document['sections'] as $sectionIndex => $section): ?>
            <section id="section-<?= $sectionIndex + 1 ?>"><h2><?= e($section['title']) ?></h2>
                <?php foreach (($section['paragraphs'] ?? []) as $paragraph): ?><p><?= nl2br(e($paragraph)) ?></p><?php endforeach; ?>
                <?php if (!empty($section['bullets'])): ?><ul><?php foreach ($section['bullets'] as $bullet): ?><li><?= e($bullet) ?></li><?php endforeach; ?></ul><?php endif; ?>
            </section>
        <?php endforeach; ?>
    </div>
    <aside class="policy-contact"><strong>Need help with this policy?</strong><p>Use our public contact page for orders, returns, privacy requests or business information.</p><a href="contact.php">Contact ComfyNest →</a></aside>
</article>
<?php require __DIR__ . '/footer.php'; ?>
