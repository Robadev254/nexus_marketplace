<?php
// products.php
require_once 'includes/header.php';

$category = isset($_GET['category']) ? $_GET['category'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Base query shows all items to support the 'Out of Stock' display state
$query = "SELECT * FROM products WHERE 1=1";
$params = [];

if ($category) {
    $query .= " AND category = ?";
    $params[] = $category;
}

if ($search) {
    $query .= " AND (name LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY (stock > 0) DESC, created_at DESC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error fetching products: " . $e->getMessage());
}
?>

<div class="container py-5">
    <div class="row mb-5 animate-fade-in shadow-none border-0 text-start">
        <div class="col-md-8">
            <h1 class="fw-bold mb-0">
                <?php 
                if ($search) echo "Matches for '" . htmlspecialchars($search) . "'";
                elseif ($category) echo "$category Listings"; 
                else echo "Discover All Treasures"; 
                ?>
            </h1>
            <p class="text-muted fs-5">Browse our community's latest deals and unique finds.</p>
        </div>
        <div class="col-md-4 d-flex justify-content-end align-items-center">
            <form action="products.php" method="GET" class="d-flex w-100">
                <input type="text" name="search" class="form-control rounded-pill me-2 px-3 bg-dark border-0 text-white" placeholder="Search items..." value="<?php echo htmlspecialchars($search); ?>">
                <button class="btn btn-primary rounded-pill px-4"><i class="fas fa-search"></i></button>
            </form>
        </div>
    </div>

    <!-- Listings Grid -->
    <div class="row g-4 animate-fade-in" style="animation-delay: 0.2s;">
        <?php if(count($products) > 0): ?>
            <?php foreach($products as $product): ?>
                <div class="col-6 col-md-4 col-xl-3">
                    <a href="product_details.php?id=<?php echo $product['id']; ?>" class="text-decoration-none h-100">
                        <div class="glass-card product-card h-100 border-0 shadow-sm hover-scale d-flex flex-column p-3">
                            <div class="position-relative mb-3">
                                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="rounded-4 w-100" style="aspect-ratio: 4/5; object-fit: cover;">
                                <span class="badge position-absolute top-0 end-0 m-3 <?php echo ($product['condition'] == 'New' ? 'bg-primary' : 'bg-secondary'); ?> rounded-pill px-3 py-1 fs-mini shadow-sm"><?php echo $product['condition']; ?></span>
                            </div>
                            <div class="p-1 flex-grow-1 text-start">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="fs-mini fw-bold text-primary opacity-50 text-uppercase tracking-widest"><?php echo htmlspecialchars($product['category']); ?></span>
                                    <?php if ($product['stock'] >= 10): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2 py-1 fs-mini shadow-sm">In Stock</span>
                                    <?php elseif ($product['stock'] > 0): ?>
                                        <span class="badge bg-warning text-dark rounded-pill px-2 py-1 fs-mini animate-pulse shadow-sm">Limited Stock (<?php echo $product['stock']; ?>)</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-2 py-1 fs-mini shadow-sm">Out of Stock</span>
                                    <?php endif; ?>
                                </div>
                                <h5 class="fw-bold text-white mb-2 text-truncate"><?php echo htmlspecialchars($product['name']); ?></h5>
                                <p class="text-muted small mb-3 flex-grow-1"><?php echo substr(htmlspecialchars($product['description']), 0, 70) . '...'; ?></p>
                                <div class="d-flex justify-content-between align-items-center mt-auto">
                                    <span class="price-tag fs-5 fw-bold text-white">$<?php echo number_format($product['price'], 2); ?></span>
                                    <div class="btn btn-outline-light btn-sm rounded-circle p-2 shadow-none"><i class="fas fa-chevron-right fs-mini"></i></div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 py-5 text-center opacity-50">
                <i class="fas fa-search-minus fs-1 mb-3"></i>
                <h4 class="fw-bold">No items found</h4>
                <p>Try searching for something else or explore a different category.</p>
                <a href="products.php" class="btn btn-outline-primary rounded-pill mt-3 px-5">View All Node Listings</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
