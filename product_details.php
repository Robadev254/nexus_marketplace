<?php
// product_details.php
require_once 'includes/header.php';

// Authentication Check: Redirect guests to sign in/up
if (!isset($_SESSION['user_id'])) {
    $current_url = urlencode("product_details.php?" . $_SERVER['QUERY_STRING']);
    header("Location: login.php?redirect=$current_url&auth_required=1");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: products.php");
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT p.*, u.name as seller_name, u.store_name, u.store_terms FROM products p JOIN users u ON p.seller_id = u.id WHERE p.id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();

    if (!$product) {
        header("Location: products.php");
        exit;
    }
} catch (PDOException $e) {
    die("Error fetching product: " . $e->getMessage());
}
?>

<div class="container py-5 animate-fade-in">
    <div class="row g-5">
        <div class="col-md-6">
            <div class="glass-card p-2">
                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="img-fluid rounded-4 shadow-lg w-100">
            </div>
        </div>
        <div class="col-md-6 d-flex flex-column justify-content-center">
            <div class="p-4">
                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb bg-transparent p-0 mb-0">
                        <li class="breadcrumb-item"><a href="products.php" class="text-primary text-decoration-none small">Marketplace</a></li>
                        <li class="breadcrumb-item active text-white opacity-50 small"><?php echo htmlspecialchars($product['category']); ?></li>
                    </ol>
                </nav>
                <h1 class="fw-bold mb-3 display-4"><?php echo htmlspecialchars($product['name']); ?></h1>
                
                <div class="d-flex align-items-center mb-4">
                    <span class="price-tag fs-1 me-4">$<?php echo number_format($product['price'], 2); ?></span>
                    <span class="badge bg-primary rounded-pill px-3 py-2"><?php echo htmlspecialchars($product['condition']); ?></span>
                </div>

                <div class="glass-card mb-4 p-4 border-0" style="background: rgba(255, 255, 255, 0.02);">
                    <h5 class="fw-bold mb-3"><i class="fas fa-info-circle text-primary me-2"></i> Description</h5>
                    <p class="text-muted fs-5 lh-lg"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                </div>

                <div class="row g-3 mb-5">
                    <div class="col-6">
                        <p class="mb-1 text-muted small text-uppercase">Merchant Hub</p>
                        <p class="fw-bold fs-6 mb-0 text-white"><i class="fas fa-store me-1 text-primary"></i> <?php echo !empty($product['store_name']) ? htmlspecialchars($product['store_name']) : htmlspecialchars($product['seller_name']); ?></p>
                    </div>
                    <div class="col-6">
                        <p class="mb-1 text-muted small text-uppercase">Availability</p>
                        <p class="fw-bold fs-6 mb-0 <?php echo $product['stock'] > 0 ? 'text-success' : 'text-danger'; ?>">
                            <?php echo $product['stock'] > 0 ? htmlspecialchars($product['stock']) . " in stock" : "Out of stock"; ?>
                        </p>
                    </div>
                </div>

                <?php if (!empty($product['store_terms'])): ?>
                    <div class="glass-card mb-5 p-4 border-start border-primary border-4" style="background: rgba(99, 102, 241, 0.03);">
                        <h6 class="small fw-bold text-primary text-uppercase mb-2"><i class="fas fa-file-contract me-1"></i> Store Policies (T&Cs)</h6>
                        <p class="small text-muted mb-0 italic"><?php echo nl2br(htmlspecialchars($product['store_terms'])); ?></p>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Seller'): ?>
                    <div class="glass-card p-4 border-0 text-center" style="background: rgba(255, 255, 255, 0.03);">
                        <i class="fas fa-store text-primary fs-3 mb-3 opacity-50"></i>
                        <p class="text-muted mb-2">Seller accounts cannot make purchases.</p>
                        <a href="products.php" class="btn btn-outline-primary rounded-pill px-4 btn-sm">
                            <i class="fas fa-arrow-left me-1"></i> Back to My Storefront
                        </a>
                    </div>
                <?php else: ?>
                    <form action="cart.php" method="POST">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <div class="input-group mb-4" style="max-width: 150px;">
                            <input type="number" name="quantity" class="form-control rounded-start-pill py-3 px-3 text-center" value="1" min="1" max="<?php echo $product['stock']; ?>">
                            <span class="input-group-text bg-transparent border-primary-subtle text-muted fs-5 px-3">QTY</span>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill py-3 fs-5 shadow-lg animation-pulse <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?>">
                            <i class="fas fa-shopping-cart me-2"></i> Add to Nexus Cart
                        </button>
                        <p class="text-center mt-3 small text-muted"><i class="fas fa-shield-alt text-primary me-1"></i> Secure Transaction Guarantee</p>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
