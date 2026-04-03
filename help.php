<?php
// help.php
require_once 'includes/header.php';
?>

<div class="container py-5 animate-fade-in">
    <div class="text-center mb-5">
        <h1 class="fw-bold mb-3 display-4 text-white">How can we assist you today?</h1>
        <p class="text-muted fs-5 mb-5">Explore our categories to find answers or connect with our support nodes.</p>
        
        <div class="input-group col-md-6 mx-auto shadow-lg rounded-pill overflow-hidden border border-light border-opacity-10" style="max-width: 600px;">
            <span class="input-group-text bg-white bg-opacity-5 border-0 px-4 text-primary"><i class="fas fa-search"></i></span>
            <input type="text" class="form-control bg-white bg-opacity-5 border-0 py-3 text-white" placeholder="Search for help topics (e.g. tracking, refunds, payment)...">
        </div>
    </div>

    <div class="row g-4 mt-5">
        <!-- Buying Section -->
        <div class="col-md-4">
            <a href="faq.php" class="text-decoration-none">
                <div class="glass-card p-5 border-0 text-center hover-up h-100 shadow-lg">
                    <div class="p-3 bg-primary bg-opacity-10 rounded-circle d-inline-flex mb-4 text-primary">
                        <i class="fas fa-shopping-cart fs-1"></i>
                    </div>
                    <h5 class="fw-bold text-white mb-3">Buying on Nexus</h5>
                    <p class="text-muted small opacity-75 mb-0">Learn about checkout, "Batch" quantities, and our global payment simulations.</p>
                </div>
            </a>
        </div>

        <!-- Selling Section -->
        <div class="col-md-4">
            <a href="seller_help.php" class="text-decoration-none">
                <div class="glass-card p-5 border-0 text-center hover-up h-100 shadow-lg">
                    <div class="p-3 bg-secondary bg-opacity-10 rounded-circle d-inline-flex mb-4 text-secondary">
                        <i class="fas fa-store fs-1"></i>
                    </div>
                    <h5 class="fw-bold text-white mb-3">Selling with Us</h5>
                    <p class="text-muted small opacity-75 mb-0">Learn about listing your finds, managing inventory in the Merchant Center, and increasing your sale potential.</p>
                </div>
            </a>
        </div>

        <!-- Account Section -->
        <div class="col-md-4">
            <a href="myorders.php" class="text-decoration-none">
                <div class="glass-card p-5 border-0 text-center hover-up h-100 shadow-lg">
                    <div class="p-3 bg-success bg-opacity-10 rounded-circle d-inline-flex mb-4 text-success">
                        <i class="fas fa-user-shield fs-1"></i>
                    </div>
                    <h5 class="fw-bold text-white mb-3">Account & Orders</h5>
                    <p class="text-muted small opacity-75 mb-0">Understand your Purchase History, Cancelled orders, and our Reorder feature.</p>
                </div>
            </a>
        </div>

        <!-- Security Section -->
        <div class="col-md-6">
            <a href="faq.php" class="text-decoration-none">
                <div class="glass-card p-5 border-0 d-flex align-items-center hover-up shadow-lg">
                    <div class="p-4 bg-info bg-opacity-10 rounded-4 me-4 text-info">
                        <i class="fas fa-lock fs-2"></i>
                    </div>
                    <div class="text-start">
                        <h5 class="fw-bold text-white mb-2">Safety & Policy</h5>
                        <p class="text-muted small mb-0 opacity-75">Documentation on our 256-bit encryption simulation and transaction security protocols.</p>
                    </div>
                </div>
            </a>
        </div>

        <!-- Contact Section -->
        <div class="col-md-6">
            <a href="contact.php" class="text-decoration-none">
                <div class="glass-card p-5 border-0 d-flex align-items-center hover-up shadow-lg">
                    <div class="p-4 bg-warning bg-opacity-10 rounded-4 me-4 text-warning">
                        <i class="fas fa-comments fs-2"></i>
                    </div>
                    <div class="text-start">
                        <h5 class="fw-bold text-white mb-2">Direct Channel</h5>
                        <p class="text-muted small mb-0 opacity-75">Connect with our support team if you require immediate manual assistance from a human node.</p>
                    </div>
                </div>
            </a>
        </div>
    </div>
    
    <div class="mt-5 py-5 text-center">
        <h4 class="fw-bold text-white mb-4 opacity-75">Popular Immediate Status Inquiries</h4>
        <div class="d-flex flex-wrap justify-content-center gap-3">
            <span class="badge rounded-pill bg-white bg-opacity-5 px-4 py-2 border border-light border-opacity-10 text-white">#TrackingOrders</span>
            <span class="badge rounded-pill bg-white bg-opacity-5 px-4 py-2 border border-light border-opacity-10 text-white">#BatchQuantity</span>
            <span class="badge rounded-pill bg-white bg-opacity-5 px-4 py-2 border border-light border-opacity-10 text-white">#ReorderProcess</span>
            <span class="badge rounded-pill bg-white bg-opacity-5 px-4 py-2 border border-light border-opacity-10 text-white">#CancelPolicy</span>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
