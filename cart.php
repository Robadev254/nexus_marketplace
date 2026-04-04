<?php
// cart.php
require_once 'includes/header.php';

// Authentication Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Seller Guard: Sellers cannot buy
if (isset($_SESSION['role']) && $_SESSION['role'] === 'Seller') {
    $_SESSION['flash_error'] = "Seller accounts cannot make purchases. Switch to a Buyer account to shop.";
    header("Location: products.php");
    exit;
}

// Add to Cart Logic
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['product_id'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    try {
        // Check if item already exists in cart for this user
        $checkStmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
        $checkStmt->execute([$user_id, $product_id]);
        $existing = $checkStmt->fetch();

        if ($existing) {
            $updateStmt = $pdo->prepare("UPDATE cart SET quantity = quantity + ? WHERE id = ?");
            $updateStmt->execute([$quantity, $existing['id']]);
        } else {
            $insertStmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
            $insertStmt->execute([$user_id, $product_id, $quantity]);
        }
    } catch (PDOException $e) {
        die("Error updating cart: " . $e->getMessage());
    }
}

// Remove from Cart Logic
if (isset($_GET['remove'])) {
    $cart_id = (int)$_GET['remove'];
    try {
        $deleteStmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        $deleteStmt->execute([$cart_id, $user_id]);
    } catch (PDOException $e) {
        die("Error deleting item: " . $e->getMessage());
    }
}

// Fetch Cart Items
try {
    $productStmt = $pdo->prepare("
        SELECT c.id as cart_id, c.quantity, p.id as product_id, p.name, p.price, p.image_url, p.description 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ?
    ");
    $productStmt->execute([$user_id]);
    $cart_items = $productStmt->fetchAll();
} catch (PDOException $e) {
    die("Error fetching cart: " . $e->getMessage());
}

$total_cart = 0;
?>

<div class="container py-5 animate-fade-in">
    <div class="row mb-5">
        <div class="col-12">
            <h1 class="fw-bold mb-4 display-5">Your Nexus Cart</h1>
            <p class="text-muted fs-5">Secure checkout for your handpicked treasures.</p>
        </div>
    </div>

    <div class="row g-5">
        <div class="col-lg-8">
            <div class="glass-card p-4 h-100 border-0 shadow-lg">
                <?php if (count($cart_items) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover border-0 mb-0">
                            <thead class="border-light opacity-25">
                                <tr>
                                    <th class="py-4 px-3 border-0 bg-transparent">Product</th>
                                    <th class="py-4 border-0 bg-transparent text-center">Quantity</th>
                                    <th class="py-4 border-0 bg-transparent text-end">Subtotal</th>
                                    <th class="py-4 border-0 bg-transparent text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart_items as $item): 
                                    $subtotal = $item['price'] * $item['quantity'];
                                    $total_cart += $subtotal;
                                ?>
                                    <tr class="align-middle border-light opacity-50">
                                        <td class="py-4 px-3 border-0 bg-transparent">
                                            <div class="d-flex align-items-center">
                                                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" class="rounded-3 shadow-sm me-3" width="70" height="70" style="object-fit: cover;">
                                                <div>
                                                    <h6 class="fw-bold text-white mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                                    <p class="text-muted small mb-0">$<?php echo number_format($item['price'], 2); ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-4 border-0 bg-transparent text-center">
                                            <span class="fs-5 fw-bold text-white"><?php echo $item['quantity']; ?></span>
                                        </td>
                                        <td class="py-4 border-0 bg-transparent text-end">
                                            <span class="fs-5 fw-bold text-primary">$<?php echo number_format($subtotal, 2); ?></span>
                                        </td>
                                        <td class="py-4 border-0 bg-transparent text-center">
                                            <a href="cart.php?remove=<?php echo $item['cart_id']; ?>" class="btn btn-outline-danger btn-sm rounded-circle p-2 shadow-sm"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-shopping-basket fs-1 text-muted opacity-25 mb-4"></i>
                        <h3>Your cart is empty.</h3>
                        <p class="text-muted mb-5">Seems like you haven't discovered anything yet.</p>
                        <a href="products.php" class="btn btn-primary rounded-pill px-5 btn-lg shadow-lg">Start Exploring Marketplace</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (count($cart_items) > 0): ?>
            <div class="col-lg-4">
                <div class="glass-card p-4 border-0 shadow-lg position-sticky" style="top: 100px;">
                    <h4 class="fw-bold mb-4">Order Summary</h4>
                    <div class="d-flex justify-content-between mb-3 text-muted">
                        <span>Items Total</span>
                        <span>$<?php echo number_format($total_cart, 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3 text-muted">
                        <span>Shipping Est.</span>
                        <span class="text-success fw-bold">FREE</span>
                    </div>
                    <hr class="opacity-25 my-4">
                    <div class="d-flex justify-content-between mb-5 align-items-center">
                        <span class="fs-4 fw-bold">Grand Total</span>
                        <span class="fs-2 fw-bold text-secondary">$<?php echo number_format($total_cart, 2); ?></span>
                    </div>
                    <a href="checkout.php" class="btn btn-primary btn-lg w-100 rounded-pill py-3 fs-5 shadow-lg shadow-primary-50">Proceed to Checkout <i class="fas fa-arrow-right ms-2 fs-6 opacity-75"></i></a>
                    <p class="text-center mt-4 small text-muted"><i class="fas fa-lock text-primary me-1"></i> SSL Layer Encrypted Checkout</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
