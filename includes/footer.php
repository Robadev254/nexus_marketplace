<footer class="footer">
    <div class="container">
        <div class="row gx-5">
            <!-- Brand & Description -->
            <div class="col-lg-4 col-md-6 mb-5">
                <h4 class="footer-title" style="background: linear-gradient(45deg, #6366f1, #ec4899); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;">NEXUS MARKET</h4>
                <p class="footer-description mb-4">Connecting seekers and sellers in a modern, secure, and vibrant marketplace. Discover the extraordinary, reuse with style, and join our growing community of collectors and creators.</p>
                <div class="d-flex gap-3">
                    <a href="#" class="footer-social-link" title="Facebook"><img src="assets/facebook.png" alt="Facebook" style="width: 18px; height: 18px; object-fit: contain;"></a>
                    <a href="#" class="footer-social-link" title="Instagram"><img src="assets/instagram.png" alt="Instagram" style="width: 18px; height: 18px; object-fit: contain;"></a>
                    <a href="#" class="footer-social-link" title="Twitter"><img src="assets/twitter.png" alt="Twitter" style="width: 18px; height: 18px; object-fit: contain;"></a>
                    <a href="#" class="footer-social-link" title="LinkedIn"><img src="assets/linkedin.png" alt="LinkedIn" style="width: 18px; height: 18px; object-fit: contain;"></a>
                    <a href="mailto:support@nexusmarket.com" class="footer-social-link" title="Email Us">
                        <img src="assets/gmail.png" alt="Email" style="width: 18px; height: 18px; object-fit: contain;">
                    </a>
                </div>
            </div>

            <!-- Navigation Links -->
            <div class="col-lg-2 col-md-6 mb-5">
                <h5 class="footer-title fs-6 text-uppercase">Marketplace</h5>
                <ul class="list-unstyled d-flex flex-column gap-2 mb-0">
                    <li><a href="products.php" class="footer-link">All Categories</a></li>
                    <li><a href="products.php?category=Electronics" class="footer-link">Electronics</a></li>
                    <li><a href="products.php?category=Clothing" class="footer-link">Clothing Nodes</a></li>
                    <li><a href="products.php?category=Books" class="footer-link">Books & Media</a></li>
                </ul>
            </div>

            <!-- Support Links -->
            <div class="col-lg-2 col-md-6 mb-5">
                <h5 class="footer-title fs-6 text-uppercase">Support</h5>
                <ul class="list-unstyled d-flex flex-column gap-2 mb-0">
                    <li><a href="help.php" class="footer-link">Help Center</a></li>
                    <li><a href="contact.php" class="footer-link">Contact Us</a></li>
                    <li><a href="seller_help.php" class="footer-link">Selling Guide</a></li>
                    <li><a href="faq.php" class="footer-link">System FAQ</a></li>
                </ul>
            </div>

            <!-- Newsletter Signup -->
            <div class="col-lg-4 col-md-6 mb-5">
                <h5 class="footer-title fs-6 text-uppercase">News Channel</h5>
                <p class="footer-description mb-4">Subscribe to our decentralized update stream for the latest treasures and platform news.</p>
                <form id="newsletter-form">
                    <div class="input-group">
                        <input type="email" class="form-control newsletter-input" placeholder="Join the stream..." required>
                        <button type="submit" class="btn btn-primary newsletter-btn px-4">Subscribe</button>
                    </div>
                    <div id="newsletter-status" class="mt-2 small d-none animate-fade-in"></div>
                </form>
            </div>
        </div>

        <div class="pt-5 mt-5 border-top border-white border-opacity-10 text-center">
            <p class="mb-0 text-muted small opacity-75 tracking-wide">&copy; <?php echo date("Y"); ?> Nexus Market Platform. All synchronization rights reserved.</p>
        </div>
    </div>
</footer>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="assets/js/main.js"></script>
</body>
</html>
