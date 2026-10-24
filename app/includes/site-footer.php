<footer class="site-footer">
    <div class="footer-brand-block">
        <a class="brand footer-brand" href="index.php"><span class="brand-mark">C</span><span>Comfy<span>Nest</span></span></a>
        <p>Comfort-forward home essentials with clear pricing, delivery and support policies.</p>
        <a class="footer-support" href="contact.php">Customer support →</a>
    </div>
    <nav class="footer-links" aria-label="Store information and policies">
        <div><strong>Company</strong><a href="about.php">About Us</a><a href="contact.php">Contact Us</a><a href="disclaimer.php">Disclaimer</a></div>
        <div><strong>Customer care</strong><a href="shipping.php">Shipping &amp; Delivery</a><a href="returns.php">Returns &amp; Refunds</a><a href="payments.php">Payment &amp; Pricing</a></div>
        <div><strong>Legal</strong><a href="privacy.php">Privacy Policy</a><a href="terms.php">Terms &amp; Conditions</a><a href="cookies.php">Cookie Policy</a></div>
    </nav>
    <div class="footer-disclosure"><span>Secure checkout</span><span><?= (int) $business['return_window_days'] ?>-day eligible returns</span><span>India-focused support</span></div>
    <div class="footer-bottom"><span>© <?= date('Y') ?> <?= e($business['legal_name']) ?>. All rights reserved.</span><a href="sitemap.php">Sitemap</a><?php if (trackingIsConfigured($tracking)): ?><button type="button" data-privacy-choices>Privacy choices</button><?php endif; ?></div>
</footer>
