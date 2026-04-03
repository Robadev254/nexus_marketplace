<?php
// index.php
require_once 'includes/header.php';

// Fetch featured products
try {
    $stmt = $pdo->query("SELECT * FROM products ORDER BY created_at DESC LIMIT 6");
    $featured_products = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error fetching products: " . $e->getMessage());
}
?>

<div class="container overflow-hidden">
    <header class="hero-section animate-fade-in">
        <h1 class="hero-title">Nexus Market Platform</h1>
        <p class="hero-subtitle">Discover the extraordinary in the pre-loved. Join our community to buy and sell premium used items with a click.</p>
        <div class="d-flex justify-content-center gap-3">
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="products.php" class="btn btn-primary btn-lg rounded-pill px-5">Explore Marketplace</a>
                <a href="register.php?role=Seller" class="btn btn-outline-light btn-lg rounded-pill px-5">Start Selling Now</a>
            <?php elseif ($_SESSION['role'] === 'Buyer'): ?>
                <a href="products.php" class="btn btn-primary btn-lg rounded-pill px-5">Continue Shopping</a>
                <a href="register.php?role=Seller" class="btn btn-outline-light btn-lg rounded-pill px-5">Join as Seller</a>
            <?php else: ?>
                <a href="profile.php" class="btn btn-primary btn-lg rounded-pill px-5">Merchant Console</a>
                <a href="products.php" class="btn btn-outline-light btn-lg rounded-pill px-5">View Marketplace</a>
            <?php endif; ?>
        </div>
    </header>

    <!-- Categories Section -->
    <section class="py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold">Popular Categories</h2>
            <a href="products.php" class="text-primary text-decoration-none">View All <i class="fas fa-arrow-right ms-1"></i></a>
        </div>
        <div class="row g-4">
            <?php 
            $cats = $pdo->query("SELECT * FROM categories ORDER BY name ASC LIMIT 4")->fetchAll();
            if (count($cats) > 0):
                foreach($cats as $cat): ?>
                    <div class="col-6 col-md-3">
                        <a href="products.php?category=<?php echo urlencode($cat['name']); ?>" class="glass-card d-block text-center text-decoration-none py-4">
                            <i class="<?php echo htmlspecialchars($cat['icon']); ?> fs-2 mb-3" style="color: <?php echo $cat['color']; ?>;"></i>
                            <h5 class="mb-0 text-white"><?php echo htmlspecialchars($cat['name']); ?></h5>
                        </a>
                    </div>
                <?php endforeach; 
            else: ?>
                <div class="col-12"><p class="text-muted small italic">No classification nodes initialized.</p></div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Featured Products Section -->
    <section class="py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold">Featured Listings</h2>
            <span class="text-muted">Handpicked for you</span>
        </div>
        <div class="row g-4">
            <?php if(count($featured_products) > 0): ?>
                <?php foreach($featured_products as $product): ?>
                    <div class="col-md-4">
                        <div class="glass-card product-card">
                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-img">
                            <div class="p-2">
                                <span class="badge-category"><?php echo htmlspecialchars($product['category']); ?></span>
                                <h4 class="fw-bold text-white mb-2"><?php echo htmlspecialchars($product['name']); ?></h4>
                                <p class="text-muted small mb-3"><?php echo substr(htmlspecialchars($product['description']), 0, 80) . '...'; ?></p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="price-tag">$<?php echo number_format($product['price'], 2); ?></span>
                                    <a href="product_details.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-light btn-sm rounded-pill">View Details</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <p class="text-muted">No products found. Be the first to list one!</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="py-5 my-5 glass-card text-center animate-fade-in" style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(236, 72, 153, 0.1));">
        <h2 class="fw-bold mb-4">Have something to sell?</h2>
        <p class="hero-subtitle mb-4">Turn your unused items into someone else's treasure. List your products and start earning today.</p>
        <a href="login.php?redirect=add_product.php" class="btn btn-primary btn-lg rounded-pill px-5">Start Selling Now</a>
    </section>
</div>

<?php require_once 'includes/footer.php'; ?>
