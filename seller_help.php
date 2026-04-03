<?php
// seller_help.php
require_once 'includes/header.php';
?>

<div class="container py-5 animate-fade-in text-start">
    <div class="row mb-5">
        <div class="col-lg-8">
            <h1 class="fw-bold mb-3 display-4 text-white">Selling on Nexus</h1>
            <p class="text-muted fs-5">Turn your extraordinary finds into someone else's treasure.</p>
        </div>
    </div>

    <div class="row g-5">
        <div class="col-lg-8">
            <div class="glass-card p-5 border-0 shadow-lg mb-5">
                <h4 class="fw-bold text-white mb-4"><i class="fas fa-list-ul me-2 text-primary"></i> 3 Steps to Your First Sale</h4>
                <div class="row g-4 mt-2">
                    <div class="col-md-4">
                        <div class="bg-white bg-opacity-5 p-4 rounded-4 border border-light border-opacity-10 h-100">
                            <h2 class="fw-bold text-primary mb-3">01</h2>
                            <h6 class="fw-bold text-white mb-2">Capture Quality</h6>
                            <p class="text-muted small mb-0">High-resolution images of your items sell 3x faster.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="bg-white bg-opacity-5 p-4 rounded-4 border border-light border-opacity-10 h-100">
                            <h2 class="fw-bold text-primary mb-3">02</h2>
                            <h6 class="fw-bold text-white mb-2">Detailed Specs</h6>
                            <p class="text-muted small mb-0">Be honest about the condition (New, Used, Like New).</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="bg-white bg-opacity-5 p-4 rounded-4 border border-light border-opacity-10 h-100">
                            <h2 class="fw-bold text-primary mb-3">03</h2>
                            <h6 class="fw-bold text-white mb-2">Set Your Price</h6>
                            <p class="text-muted small mb-0">Research similar items to set a competitive price.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="glass-card p-5 border-0 shadow-lg">
                <h4 class="fw-bold text-white mb-4"><i class="fas fa-chart-line me-2 text-primary"></i> Managing Inventory</h4>
                <p class="text-muted">As a registered seller, you have access to a personalized <strong>Merchant Command Center</strong> where you can track your live listings, adjust stock "Batch" counts, and monitor recent acquisitions.</p>
                
                <div class="alert bg-primary bg-opacity-10 border-0 rounded-4 p-4 mt-4">
                    <div class="d-flex align-items-center mb-3">
                        <i class="fas fa-info-circle text-primary fs-4 me-3"></i>
                        <h6 class="fw-bold text-white mb-0">Pro Tip for Sellers</h6>
                    </div>
                    <p class="text-muted small mb-0">Listings with free shipping integrations attract 40% more buyer interaction across our global network.</p>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="glass-card p-5 border-0 shadow-lg h-100">
                <h5 class="fw-bold text-white mb-4">Seller Support Node</h5>
                
                <div class="mb-5">
                    <h6 class="fw-bold text-muted small text-uppercase tracking-wider mb-3 px-1">QUICK ACTIONS</h6>
                    <a href="admin/manage_products.php" class="btn btn-primary w-100 rounded-pill py-2 mb-3 shadow-sm">My Storefront</a>
                    <a href="contact.php" class="btn btn-outline-light w-100 rounded-pill py-2 opacity-75">Connect with a Human</a>
                </div>

                <div>
                    <h6 class="fw-bold text-muted small text-uppercase tracking-wider mb-3 px-1">SELLER FAQs</h6>
                    <ul class="list-unstyled">
                        <li class="mb-3"><a href="faq.php" class="text-white text-decoration-none opacity-75 hover-opacity-100"><i class="fas fa-chevron-right me-2 small text-primary"></i> Listing Fees</a></li>
                        <li class="mb-3"><a href="faq.php" class="text-white text-decoration-none opacity-75 hover-opacity-100"><i class="fas fa-chevron-right me-2 small text-primary"></i> Payout Cycles</a></li>
                        <li class="mb-3"><a href="faq.php" class="text-white text-decoration-none opacity-75 hover-opacity-100"><i class="fas fa-chevron-right me-2 small text-primary"></i> Return Policy</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
