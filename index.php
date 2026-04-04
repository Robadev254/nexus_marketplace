<?php
// index.php
require_once 'includes/header.php';

// Fetch featured products (Only In-Stock Nodes)
$limit = 6;
try {
    $stmt = $pdo->query("SELECT * FROM products WHERE stock > 0 ORDER BY created_at DESC LIMIT $limit OFFSET 0");
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
                <a href="login.php?redirect=products.php&auth_required=1" class="btn btn-primary btn-lg rounded-pill px-5">Explore Marketplace</a>
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
        <div id="featured-grid" class="row g-4" data-page="1" data-has-more="1">
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
        <!-- Loading Sentinel -->
        <div id="featured-sentinel" class="py-5 text-center d-none">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="py-5 my-5 glass-card text-center animate-fade-in" style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(236, 72, 153, 0.1));">
        <h2 class="fw-bold mb-4">Have something to sell?</h2>
        <p class="hero-subtitle mb-4">Turn your unused items into someone else's treasure. List your products and start earning today.</p>
        <?php if (!isset($_SESSION['user_id'])): ?>
            <a href="register.php?role=Seller" class="btn btn-primary btn-lg rounded-pill px-5">Start Selling Now</a>
        <?php else: ?>
            <a href="profile.php" class="btn btn-primary btn-lg rounded-pill px-5">Merchant Console</a>
        <?php endif; ?>
    </section>
</div>

<!-- Idle Alert Popup -->
<div id="idle-alert" class="idle-popup">
    <div class="idle-popup-close" onclick="document.getElementById('idle-alert').style.display='none'"><i class="fas fa-times"></i></div>
    <div class="d-flex align-items-center gap-3 mb-3">
        <div class="bg-primary bg-opacity-20 rounded-circle p-2">
            <i class="fas fa-magic text-primary"></i>
        </div>
        <h5 class="mb-0 fw-bold">Handpicked for you</h5>
    </div>
    <p class="text-muted small mb-3">Still browsing? Discover our latest treasures curated specifically for your nodes.</p>
    <a href="products.php" class="btn btn-primary btn-sm rounded-pill w-100">Explore New Nodes</a>
</div>

<?php require_once 'includes/footer.php'; ?>

<!-- Infinite Scroll & Idle Detection for Home -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Infinite Scroll Logic ---
    const grid = document.getElementById('featured-grid');
    const sentinel = document.getElementById('featured-sentinel');
    if (grid && sentinel) {
        const observer = new IntersectionObserver(entries => {
            if (entries[0].isIntersecting && grid.dataset.hasMore === '1') {
                loadFeatured();
            }
        }, { threshold: 0.1 });

        observer.observe(sentinel);

        async function loadFeatured() {
            const page = parseInt(grid.dataset.page) + 1;
            sentinel.classList.remove('d-none');
            try {
                const response = await fetch(`api/fetch_products.php?page=${page}`);
                const result = await response.json();
                if (result.status === 'success' && result.products.length > 0) {
                    result.products.forEach(p => {
                        const col = document.createElement('div');
                        col.className = 'col-md-4 animate-fade-in';
                        col.innerHTML = `
                            <div class="glass-card product-card">
                                <img src="${p.image_url}" alt="${p.name}" class="product-img">
                                <div class="p-2">
                                    <span class="badge-category">${p.category}</span>
                                    <h4 class="fw-bold text-white mb-2">${p.name}</h4>
                                    <p class="text-muted small mb-3">${p.short_description}</p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="price-tag">$${p.formatted_price}</span>
                                        <a href="product_details.php?id=${p.id}" class="btn btn-outline-light btn-sm rounded-pill">View Details</a>
                                    </div>
                                </div>
                            </div>
                        `;
                        grid.appendChild(col);
                    });
                    grid.dataset.page = page;
                    if (!result.has_more) {
                        grid.dataset.hasMore = '0';
                        sentinel.classList.add('d-none');
                    }
                } else {
                    grid.dataset.hasMore = '0';
                    sentinel.classList.add('d-none');
                }
            } catch (e) {
                console.error(e);
            } finally {
                sentinel.classList.add('d-none');
            }
        }
    }

    // --- Idle Detection Logic ---
    let idleTimer;
    const idleAlert = document.getElementById('idle-alert');
    if (idleAlert) {
        const resetTimer = () => {
            if (idleAlert.style.display !== 'block') {
                clearTimeout(idleTimer);
                idleTimer = setTimeout(() => {
                    idleAlert.style.display = 'block';
                }, 30000); // 30 seconds
            }
        };

        // Track user activity
        ['mousemove', 'mousedown', 'scroll', 'keypress', 'touchstart'].forEach(evt => {
            window.addEventListener(evt, resetTimer, { passive: true });
        });

        resetTimer(); // Start initial timer
    }
});
</script>
