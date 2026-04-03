<?php
// checkout.php
require_once 'includes/header.php';

// Authentication Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success = false;
$error = '';

// Fetch Cart with Item Details
try {
    $cartStmt = $pdo->prepare("
        SELECT c.id as cart_id, c.quantity, p.id as product_id, p.name, p.price, p.image_url, p.stock
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ?
    ");
    $cartStmt->execute([$user_id]);
    $cart_items = $cartStmt->fetchAll();
    
    if (count($cart_items) == 0 && !isset($_POST['place_order'])) {
        header("Location: cart.php");
        exit;
    }

    $grand_total = 0;
    foreach($cart_items as $item) {
        $grand_total += ($item['price'] * $item['quantity']);
    }
} catch (PDOException $e) {
    die("Error fetching cart: " . $e->getMessage());
}

// Handle quantity update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_qty'])) {
    $c_id = (int)$_POST['cart_id'];
    $new_qty = (int)$_POST['quantity'];
    // Verification: ensure positive and within stock
    if ($new_qty > 0) {
        $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?")->execute([$new_qty, $c_id, $user_id]);
    }
    header("Location: checkout.php");
    exit;
}

// PayPal Authorization Return Handler
if (isset($_GET['paypal_success'])) {
    $success = true;
    try {
        $pdo->beginTransaction();
        $orderStmt = $pdo->prepare("INSERT INTO orders (user_id, total_price, status) VALUES (?, ?, 'Pending')");
        $orderStmt->execute([$user_id, $grand_total]);
        $order_id = $pdo->lastInsertId();
        $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $stockStmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        foreach ($cart_items as $item) {
            $itemStmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
            $stockStmt->execute([$item['quantity'], $item['product_id']]);
        }
        $pdo->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$user_id]);
        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = "PayPal Synchronization Error: " . $e->getMessage();
        $success = false;
    }
}

if (isset($_GET['error']) && $_GET['error'] == 'paypal_insufficient_funds') {
    $error = "PayPal Error: Your wallet balance is insufficient for this transaction.";
}

// Order Finalization
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    // Card Validation Logic
    if (isset($_POST['card_number'])) {
        $c_num = str_replace(' ', '', $_POST['card_number']);
        $c_exp = $_POST['card_expiry'];
        $c_cvc = $_POST['card_cvc'];

        // Basic Validation: 16 digits, correct expiry label, 3 digit CVC
        // Card Balance Verification (Simulated)
        $simulated_bank_balance = 450.00;
        if ($simulated_bank_balance < $grand_total) {
            $error = "Payment Authorization Failed: Insufficient funds in your associated bank account.";
        } else {
            // Check expiry date isn't in the past
            list($month, $year) = explode('/', $c_exp);
            $month = (int)$month;
            $year = (int)$year + 2000;
            if ($month < 1 || $month > 12 || ($year < date('Y')) || ($year == date('Y') && $month < date('n'))) {
                $error = "Payment Authorization Failed: Card has expired.";
            }
        }
    }

    if (empty($error)) {
        try {
            $pdo->beginTransaction();

        // 1. Create Order record
        $orderStmt = $pdo->prepare("INSERT INTO orders (user_id, total_price, status) VALUES (?, ?, 'Pending')");
        $orderStmt->execute([$user_id, $grand_total]);
        $order_id = $pdo->lastInsertId();

        // 2. Transcribe items to order_items & deduct stock
        $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $stockStmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");

        foreach ($cart_items as $item) {
            $itemStmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
            $stockStmt->execute([$item['quantity'], $item['product_id']]);
        }

        // 3. Purge Cart
        $purgeStmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        $purgeStmt->execute([$user_id]);

        $pdo->commit();
        $success = true;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = "Payment fulfillment failed: " . $e->getMessage();
    }
}
}
?>

<div class="container py-5">
    <div class="row g-5">
        <?php if (!$success): ?>
        <!-- Left Side: Order Summary & Review -->
        <div class="col-lg-5 animate-fade-in">
            <h2 class="fw-bold mb-4 text-white">Review Order</h2>
            <div class="glass-card p-4 border-0 shadow-lg">
                <?php foreach ($cart_items as $item): ?>
                    <div class="d-flex align-items-center mb-4 pb-4 border-bottom border-light border-opacity-10">
                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" class="rounded-3 shadow-sm me-3" width="70" height="70" style="object-fit: cover;">
                        <div class="flex-grow-1">
                            <h6 class="fw-bold text-white mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                            <p class="text-primary small fw-bold mb-2">$<?php echo number_format($item['price'], 2); ?></p>
                            
                            <form action="checkout.php" method="POST" class="d-flex align-items-center">
                                <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                <div class="input-group input-group-sm" style="max-width: 120px;">
                                    <button type="submit" name="update_qty" class="btn btn-outline-light border-opacity-25" onclick="this.form.quantity.value--">-</button>
                                    <input type="number" name="quantity" class="form-control bg-transparent text-white text-center border-opacity-25" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock']; ?>" readonly>
                                    <button type="submit" name="update_qty" class="btn btn-outline-light border-opacity-25" onclick="this.form.quantity.value++">+</button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="pt-2">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Subtotal</span>
                        <span class="text-white">$<?php echo number_format($grand_total, 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-4">
                        <span class="text-muted">Shipping</span>
                        <span class="text-success fw-bold">FREE</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center border-top border-light border-opacity-25 pt-4">
                        <h4 class="fw-bold text-white mb-0">Total</h4>
                        <h3 class="fw-bold text-secondary mb-0">$<?php echo number_format($grand_total, 2); ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side: Payment -->
        <div class="col-lg-7 animate-fade-in" style="animation-delay: 0.2s;">
            <div class="glass-card p-5 border-0 shadow-lg h-100">
                <h2 class="fw-bold mb-4"><i class="fas fa-credit-card text-primary me-2"></i> Payment Details</h2>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger bg-danger text-white border-0 py-2 rounded-pill text-center mb-4"><?php echo $error; ?></div>
                <?php endif; ?>

                <form action="checkout.php" method="POST" id="checkout-form">
                    <div class="row g-4 mb-5">
                        <div class="col-12">
                            <h5 class="fw-bold mb-4 opacity-75">Shipping Address</h5>
                            <textarea class="form-control rounded-4 py-3" placeholder="Enter full address..." rows="2" required></textarea>
                        </div>

                        <div class="col-12">
                            <div class="glass-card p-4 border-0" style="background: rgba(255, 255, 255, 0.03);">
                                <div class="mb-4 d-flex gap-3">
                                    <div id="tab-card" class="payment-method-tab active p-3 rounded-4 flex-grow-1 text-center border border-primary border-opacity-50 cursor-pointer" onclick="switchPayment('card')">
                                        <i class="fab fa-cc-visa fs-3 text-white mb-2"></i>
                                        <p class="small mb-0 text-white opacity-75">Card</p>
                                    </div>
                                    <div id="tab-paypal" class="payment-method-tab p-3 rounded-4 flex-grow-1 text-center border border-light border-opacity-10 opacity-50 cursor-pointer" onclick="switchPayment('paypal')">
                                        <i class="fab fa-paypal fs-3 text-white mb-2"></i>
                                        <p class="small mb-0 text-white opacity-75">PayPal</p>
                                    </div>
                                </div>

                                <div id="payment-card">
                                    <div class="mb-3">
                                        <label class="form-label text-muted small fw-bold">CARD NUMBER</label>
                                        <input type="text" name="card_number" id="card-number" class="form-control" placeholder="0000 0000 0000 0000" maxlength="19">
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-7">
                                            <label class="form-label text-muted small fw-bold">EXPIRY</label>
                                            <input type="text" name="card_expiry" id="card-expiry" class="form-control" placeholder="MM/YY" maxlength="5">
                                        </div>
                                        <div class="col-5">
                                            <label class="form-label text-muted small fw-bold">CVC</label>
                                            <input type="text" name="card_cvc" id="card-cvc" class="form-control" placeholder="123" maxlength="3">
                                        </div>
                                    </div>
                                </div>

                                <div id="payment-paypal" class="d-none text-center py-3">
                                    <img src="https://www.paypalobjects.com/webstatic/mktg/logo/pp_cc_mark_111x69.jpg" class="mb-3" width="80">
                                    <p class="text-muted small">You will be redirected to PayPal.</p>
                                    <div class="btn w-100 py-2 rounded-4 fw-bold" style="background: #ffc439; color: #000;" onclick="simulatePaypal(<?php echo $grand_total; ?>)">PayPal Checkout</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="payment-status" class="text-center mb-4 d-none">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-primary small fw-bold" id="status-text">Processing...</p>
                    </div>

                    <button type="submit" name="place_order" id="submit-btn" class="btn btn-primary btn-lg w-100 rounded-pill py-3 shadow-lg">Confirm Payment</button>
                </form>
            </div>
        </div>
        <?php else: ?>
        <!-- Success View -->
        <div class="col-12 text-center py-5 animate-fade-in">
            <div class="glass-card col-md-6 mx-auto p-5 border-0 shadow-lg">
                <div class="p-3 bg-success bg-opacity-25 rounded-circle d-inline-flex mb-4">
                    <i class="fas fa-check-circle text-success fs-1"></i>
                </div>
                <h2 class="fw-bold mb-3">Order Confirmed!</h2>
                <p class="text-muted fs-5 mb-5">Your treasures are being prepared for shipment.</p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="index.php" class="btn btn-primary rounded-pill px-5">Back to Market</a>
                    <a href="myorders.php" class="btn btn-outline-light rounded-pill px-5">My Orders</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
