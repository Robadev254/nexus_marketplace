<?php
// products.php
require_once 'includes/header.php';

$category = isset($_GET['category']) ? $_GET['category'] : '';
$query = "SELECT * FROM products";
$params = [];

if ($category) {
    $query .= " WHERE category = ?";
    $params[] = $category;
}

$query .= " ORDER BY created_at DESC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error fetching products: " . $e->getMessage());
}
?>

<div class="container py-5">
    <div class="row mb-5 animate-fade-in">
        <div class="col-md-8">
            <h1 class="fw-bold mb-0"><?php echo $category ? "$category Listings" : "Discover All Treasures"; ?></h1>
            <p class="text-muted fs-5">Browse our community's latest deals and unique finds.</p>
        </div>
        <div class="col-md-4 d-flex justify-content-end align-items-center">
            <form action="products.php" method="GET" class="d-flex w-100">
                <input type="text" name="search" class="form-control rounded-pill me-2 px-3" placeholder="Search items...">
                <button class="btn btn-primary rounded-pill px-4"><i class="fas fa-search"></i></button>
            </form>
        </div>
    </div>

    <!-- Listings Grid -->
    <div class="row g-4 animate-fade-in" style="animation-delay: 0.2s;">
        <?php if(count($products) > 0): ?>
            <?php foreach($products as $product): ?>
                <div class="col-6 col-md-4 col-xl-3">
                    <div class="glass-card product-card h-100">
                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-img w-100 mb-3" style="aspect-ratio: 4/5;">
                        <div class="p-2 d-flex flex-column flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge-category m-0"><?php echo htmlspecialchars($product['category']); ?></span>
                                <?php if ($product['stock'] == 0): ?>
                                    <span class="badge bg-danger rounded-pill px-2 py-1 shadow-sm fs-mini">Out of Stock</span>
                                <?php elseif ($product['stock'] < 10): ?>
                                    <span class="badge bg-warning text-dark rounded-pill px-2 py-1 shadow-sm fs-mini animate-pulse">Almost Out of Stock</span>
                                <?php else: ?>
                                    <span class="badge bg-success rounded-pill px-2 py-1 shadow-sm fs-mini">In Stock (<?php echo $product['stock']; ?>)</span>
                                <?php endif; ?>
                            </div>
                            <h5 class="fw-bold text-white mb-2"><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p class="text-muted small mb-3 flex-grow-1"><?php echo substr(htmlspecialchars($product['description']), 0, 80) . '...'; ?></p>
                            <div class="d-flex justify-content-between align-items-center mt-auto">
                                <span class="price-tag fs-5">$<?php echo number_format($product['price'], 2); ?></span>
                                <a href="product_details.php?id=<?php echo $product['id']; ?>" class="btn btn-primary btn-sm rounded-pill px-3 <?php echo ($product['stock'] == 0) ? 'disabled' : ''; ?>">View</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 py-5 text-center glass-card">
                <h3 class="mb-4">No results found in this category.</h3>
                <p class="text-muted">Try a different category or search for something else.</p>
                <a href="products.php" class="btn btn-outline-light rounded-pill px-4">Browse All Listings</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
