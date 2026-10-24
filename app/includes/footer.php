        <?php
        $includeSiteFooter = $showSiteFooter ?? !str_contains((string) ($pageClass ?? ''), 'checkout-page');
        if ($includeSiteFooter) require __DIR__ . '/site-footer.php';
        ?>
        </main>
        <?php if (!empty($showBottomNav)) { require __DIR__ . '/bottom-nav.php'; } ?>
    </div>
</div>
<?php if (trackingIsConfigured($tracking)): ?>
<section class="consent-banner" data-consent-banner hidden aria-label="Privacy choices">
    <div><strong>Your privacy choices</strong><p>We use anonymous visit counts. With permission, configured Google/Meta analytics and a free IP service may estimate your city/state for the live visitor map. ComfyNest does not store your raw IP.</p><a href="cookies.php">Learn more</a></div>
    <div class="consent-actions"><button type="button" data-consent="necessary">Only necessary</button><button type="button" data-consent="accepted">Allow analytics</button></div>
</section>
<?php endif; ?>
<iframe name="cart-background-target" data-cart-background-target hidden title="Cart update"></iframe>
<div class="toast" role="status" aria-live="polite"></div>
<script src="assets/js/app.js?v=20261023-gallery-3" defer></script>
</body>
</html>
