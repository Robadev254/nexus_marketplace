<?php
// checkout.php - Finance Module with Delivery/Pickup Options
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

$success = false;
$error = '';

// Fetch Cart with Item Details + Seller fulfillment info
try {
    $cartStmt = $pdo->prepare("
        SELECT c.id as cart_id, c.quantity, 
               p.id as product_id, p.name, p.price, p.image_url, p.stock, p.seller_id,
               u.name as seller_name, u.store_name, u.store_address, u.store_city,
               u.delivery_fee as seller_delivery_fee, u.offers_delivery, u.offers_pickup
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        JOIN users u ON p.seller_id = u.id
        WHERE c.user_id = ?
    ");
    $cartStmt->execute([$user_id]);
    $cart_items = $cartStmt->fetchAll();
    
    if (count($cart_items) == 0 && !isset($_POST['place_order']) && !isset($_GET['paypal_success'])) {
        header("Location: cart.php");
        exit;
    }

    // Group items by seller for fulfillment options
    $sellers = [];
    $items_subtotal = 0;
    foreach ($cart_items as $item) {
        $sid = $item['seller_id'];
        if (!isset($sellers[$sid])) {
            $sellers[$sid] = [
                'seller_name' => $item['seller_name'],
                'store_name' => $item['store_name'],
                'store_address' => $item['store_address'],
                'store_city' => $item['store_city'],
                'delivery_fee' => (float)$item['seller_delivery_fee'],
                'offers_delivery' => $item['offers_delivery'],
                'offers_pickup' => $item['offers_pickup'],
                'items' => []
            ];
        }
        $sellers[$sid]['items'][] = $item;
        $items_subtotal += ($item['price'] * $item['quantity']);
    }
} catch (PDOException $e) {
    die("Error fetching cart: " . $e->getMessage());
}

// Handle quantity update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_qty'])) {
    $c_id = (int)$_POST['cart_id'];
    $new_qty = (int)$_POST['quantity'];
    if ($new_qty > 0) {
        $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?")->execute([$new_qty, $c_id, $user_id]);
    }
    header("Location: checkout.php");
    exit;
}

// PayPal Authorization Return Handler
if (isset($_GET['paypal_success'])) {
    // Re-calculate totals with delivery fees from session
    $delivery_choices = $_SESSION['delivery_choices'] ?? [];
    $total_delivery_fee = 0;
    $delivery_method_final = 'Delivery';
    $shipping_addr = $_SESSION['checkout_address'] ?? '';
    
    foreach ($sellers as $sid => $sdata) {
        $method = $delivery_choices[$sid] ?? 'delivery';
        if ($method === 'delivery') {
            $total_delivery_fee += $sdata['delivery_fee'];
        }
    }
    
    // If all sellers are pickup, set method to Pickup
    $all_pickup = true;
    foreach ($sellers as $sid => $sdata) {
        if (($delivery_choices[$sid] ?? 'delivery') !== 'pickup') $all_pickup = false;
    }
    if ($all_pickup) $delivery_method_final = 'Pickup';

    $grand_total = $items_subtotal + $total_delivery_fee;
    
    $success = true;
    try {
        $pdo->beginTransaction();
        $orderStmt = $pdo->prepare("INSERT INTO orders (user_id, total_price, delivery_fee, delivery_method, shipping_address, payment_method, status) VALUES (?, ?, ?, ?, ?, 'PayPal', 'Pending')");
        $orderStmt->execute([$user_id, $grand_total, $total_delivery_fee, $delivery_method_final, $shipping_addr]);
        $order_id = $pdo->lastInsertId();
        $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $stockStmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        foreach ($cart_items as $item) {
            $itemStmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
            $stockStmt->execute([$item['quantity'], $item['product_id']]);
        }
        $pdo->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$user_id]);
        $pdo->commit();
        unset($_SESSION['delivery_choices'], $_SESSION['checkout_address']);
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = "PayPal Synchronization Error: " . $e->getMessage();
        $success = false;
    }
}

if (isset($_GET['error']) && $_GET['error'] == 'paypal_insufficient_funds') {
    $error = "PayPal Error: Your wallet balance is insufficient for this transaction.";
}

// Order Finalization (Card Payment)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    // Collect delivery choices
    $delivery_choices = [];
    $total_delivery_fee = 0;
    $delivery_method_final = 'Delivery';
    $shipping_address = trim($_POST['shipping_address'] ?? '');
    
    foreach ($sellers as $sid => $sdata) {
        $method = $_POST['delivery_' . $sid] ?? 'delivery';
        $delivery_choices[$sid] = $method;
        if ($method === 'delivery') {
            $total_delivery_fee += $sdata['delivery_fee'];
        }
    }
    
    // If all sellers are pickup, set method to Pickup
    $all_pickup = true;
    $any_delivery = false;
    foreach ($delivery_choices as $sid => $method) {
        if ($method === 'delivery') { $all_pickup = false; $any_delivery = true; }
    }
    if ($all_pickup) $delivery_method_final = 'Pickup';
    
    // Require shipping address if any delivery is selected
    if ($any_delivery && empty($shipping_address)) {
        $error = "Shipping address is required when delivery is selected.";
    }
    
    // Card Validation Logic
    if (empty($error) && isset($_POST['card_number'])) {
        $c_num = str_replace(' ', '', $_POST['card_number']);
        $c_exp = $_POST['card_expiry'];
        $c_cvc = $_POST['card_cvc'];

        $grand_total = $items_subtotal + $total_delivery_fee;

        $simulated_bank_balance = 5000.00;
        if ($simulated_bank_balance < $grand_total) {
            $error = "Payment Authorization Failed: Insufficient funds in your associated bank account.";
        } else {
            list($month, $year) = explode('/', str_replace(' ', '', $c_exp));
            $month = (int)$month;
            $year = (int)$year + 2000;
            if ($month < 1 || $month > 12 || ($year < date('Y')) || ($year == date('Y') && $month < date('n'))) {
                $error = "Payment Authorization Failed: Card has expired.";
            }
        }
    }

    if (empty($error)) {
        // PRE-FLIGHT: Batch Availability Verification
        foreach ($cart_items as $checkItem) {
            if ($checkItem['stock'] < $checkItem['quantity']) {
                $error = "Stock Depletion: '" . htmlspecialchars($checkItem['name']) . "' has insufficient stock. Available: " . $checkItem['stock'];
                break;
            }
        }
    }

    if (empty($error)) {
        $grand_total = $items_subtotal + $total_delivery_fee;
        
        try {
            $pdo->beginTransaction();

            $orderStmt = $pdo->prepare("INSERT INTO orders (user_id, total_price, delivery_fee, delivery_method, shipping_address, payment_method, status) VALUES (?, ?, ?, ?, ?, 'Card', 'Pending')");
            $orderStmt->execute([$user_id, $grand_total, $total_delivery_fee, $delivery_method_final, $shipping_address]);
            $order_id = $pdo->lastInsertId();

            $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stockStmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");

            foreach ($cart_items as $item) {
                $itemStmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
                $stockStmt->execute([$item['quantity'], $item['product_id']]);
            }

            $purgeStmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $purgeStmt->execute([$user_id]);

            $pdo->commit();
            $success = true;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = "Payment fulfillment failed: " . $e->getMessage();
        }
    }
    
    // Save for PayPal flow
    $_SESSION['delivery_choices'] = $delivery_choices;
    $_SESSION['checkout_address'] = $shipping_address;
}

// Calculate default grand total for display
$default_delivery = 0;
foreach ($sellers as $sid => $sdata) {
    if ($sdata['offers_delivery']) {
        $default_delivery += $sdata['delivery_fee'];
    }
}
$grand_total = $items_subtotal + $default_delivery;
?>

<div class="container py-5">
    <div class="row g-5">
        <?php if (!$success): ?>
        <!-- Left Side: Order Review & Delivery Options -->
        <div class="col-lg-6 animate-fade-in">
            <h2 class="fw-bold mb-2 text-white"><i class="fas fa-receipt text-primary me-2"></i> Checkout</h2>
            <p class="text-muted mb-4">Review your items and choose fulfillment options.</p>

            <?php if ($error): ?>
                <div class="alert alert-danger bg-danger bg-opacity-10 text-danger border-0 py-3 rounded-4 text-center mb-4 small fw-bold shadow-sm"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Items Grouped by Seller -->
            <?php foreach ($sellers as $sid => $sdata): ?>
                <div class="glass-card p-4 border-0 shadow-lg mb-4">
                    <!-- Seller Header -->
                    <div class="d-flex align-items-center mb-3 pb-3 border-bottom border-light border-opacity-10">
                        <div class="bg-primary bg-opacity-10 rounded-circle p-2 me-3">
                            <i class="fas fa-store text-primary"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold text-white mb-0"><?php echo htmlspecialchars($sdata['store_name'] ?: $sdata['seller_name']); ?></h6>
                            <?php if ($sdata['store_city']): ?>
                                <span class="text-muted fs-mini"><i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($sdata['store_city']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Seller Items -->
                    <?php foreach ($sdata['items'] as $item): ?>
                        <div class="d-flex align-items-center mb-3 pb-3 border-bottom border-light border-opacity-5">
                            <img src="<?php echo htmlspecialchars($item['image_url']); ?>" class="rounded-3 shadow-sm me-3" width="55" height="55" style="object-fit: cover;">
                            <div class="flex-grow-1">
                                <h6 class="fw-bold text-white mb-0 small"><?php echo htmlspecialchars($item['name']); ?></h6>
                                <span class="text-muted fs-mini">Qty: <?php echo $item['quantity']; ?> × $<?php echo number_format($item['price'], 2); ?></span>
                            </div>
                            <span class="fw-bold text-primary">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                        </div>
                    <?php endforeach; ?>

                    <!-- Fulfillment Options for this Seller -->
                    <div class="mt-3 pt-2">
                        <label class="small fw-bold text-uppercase opacity-50 mb-2 d-block"><i class="fas fa-shipping-fast me-1"></i> Fulfillment Method</label>
                        <div class="d-flex gap-2 flex-wrap">
                            <?php if ($sdata['offers_delivery']): ?>
                                <label class="fulfillment-option flex-grow-1">
                                    <input type="radio" name="delivery_<?php echo $sid; ?>" value="delivery" class="d-none delivery-radio" data-fee="<?php echo $sdata['delivery_fee']; ?>" data-seller="<?php echo $sid; ?>" checked form="checkout-form">
                                    <div class="p-3 rounded-4 border border-primary border-opacity-50 text-center cursor-pointer fulfillment-card active-fulfillment">
                                        <i class="fas fa-truck text-primary fs-5 mb-1 d-block"></i>
                                        <span class="small fw-bold text-white d-block">Delivery</span>
                                        <span class="fs-mini <?php echo $sdata['delivery_fee'] > 0 ? 'text-warning' : 'text-success'; ?> fw-bold">
                                            <?php echo $sdata['delivery_fee'] > 0 ? '$' . number_format($sdata['delivery_fee'], 2) . ' fee' : 'FREE'; ?>
                                        </span>
                                    </div>
                                </label>
                            <?php endif; ?>
                            
                            <?php if ($sdata['offers_pickup'] && $sdata['store_address']): ?>
                                <label class="fulfillment-option flex-grow-1">
                                    <input type="radio" name="delivery_<?php echo $sid; ?>" value="pickup" class="d-none delivery-radio" data-fee="0" data-seller="<?php echo $sid; ?>" <?php echo !$sdata['offers_delivery'] ? 'checked' : ''; ?> form="checkout-form">
                                    <div class="p-3 rounded-4 border border-light border-opacity-10 text-center cursor-pointer fulfillment-card <?php echo !$sdata['offers_delivery'] ? 'active-fulfillment' : ''; ?>">
                                        <i class="fas fa-store fs-5 mb-1 d-block" style="color: #10b981;"></i>
                                        <span class="small fw-bold text-white d-block">Store Pickup</span>
                                        <span class="fs-mini text-success fw-bold">FREE</span>
                                    </div>
                                </label>
                            <?php endif; ?>
                            
                            <?php if (!$sdata['offers_delivery'] && !($sdata['offers_pickup'] && $sdata['store_address'])): ?>
                                <input type="hidden" name="delivery_<?php echo $sid; ?>" value="delivery" form="checkout-form">
                                <div class="p-3 rounded-4 bg-white bg-opacity-5 text-center flex-grow-1">
                                    <i class="fas fa-truck text-primary fs-5 mb-1 d-block"></i>
                                    <span class="small fw-bold text-white d-block">Standard Delivery</span>
                                    <span class="fs-mini text-success fw-bold">FREE</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Store Pickup Info (shown when pickup available) -->
                        <?php if ($sdata['offers_pickup'] && $sdata['store_address']): ?>
                            <div class="pickup-info mt-3 p-3 rounded-3 d-none" id="pickup-info-<?php echo $sid; ?>" style="background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.2);">
                                <div class="d-flex align-items-start">
                                    <i class="fas fa-map-pin me-2 mt-1" style="color: #10b981;"></i>
                                    <div>
                                        <span class="small fw-bold text-white d-block"><?php echo htmlspecialchars($sdata['store_name'] ?: $sdata['seller_name']); ?></span>
                                        <span class="fs-mini text-muted"><?php echo htmlspecialchars($sdata['store_address']); ?></span>
                                        <?php if ($sdata['store_city']): ?>
                                            <span class="fs-mini text-muted d-block"><?php echo htmlspecialchars($sdata['store_city']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Order Total Summary -->
            <div class="glass-card p-4 border-0 shadow-lg">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Items Subtotal</span>
                    <span class="text-white fw-bold">$<?php echo number_format($items_subtotal, 2); ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Delivery Fees</span>
                    <span class="fw-bold" id="delivery-fee-display">
                        <?php if ($default_delivery > 0): ?>
                            <span class="text-warning">$<?php echo number_format($default_delivery, 2); ?></span>
                        <?php else: ?>
                            <span class="text-success">FREE</span>
                        <?php endif; ?>
                    </span>
                </div>
                <hr class="border-light border-opacity-10 my-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold text-white mb-0">Grand Total</h5>
                    <h4 class="fw-bold text-secondary mb-0" id="grand-total-display">$<?php echo number_format($grand_total, 2); ?></h4>
                </div>
            </div>
        </div>

        <!-- Right Side: Shipping & Payment -->
        <div class="col-lg-6 animate-fade-in" style="animation-delay: 0.2s;">
            <div class="glass-card p-5 border-0 shadow-lg h-100">
                <form action="checkout.php" method="POST" id="checkout-form">
                    <!-- Shipping Address -->
                    <div class="mb-5" id="shipping-section">
                        <h5 class="fw-bold mb-3"><i class="fas fa-map-marker-alt text-primary me-2"></i> Shipping Address</h5>
                        <textarea name="shipping_address" class="form-control rounded-4 py-3" placeholder="Enter your full delivery address..." rows="2" id="shipping-address-input"></textarea>
                        <p class="text-muted fs-mini mt-2 mb-0"><i class="fas fa-info-circle me-1"></i> Required for items being delivered to you.</p>
                    </div>

                    <!-- Payment Method Section -->
                    <div class="mb-5">
                        <h5 class="fw-bold mb-3"><i class="fas fa-credit-card text-primary me-2"></i> Payment Method</h5>
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
                                <img src="https://www.paypalobjects.com/webstatic/mktg/logo/pp_cc_mark_111x69.jpg" class="mb-3 rounded-3" width="80">
                                <p class="text-muted small">You will be redirected to PayPal.</p>
                                <div class="btn w-100 py-2 rounded-4 fw-bold" style="background: #ffc439; color: #000;" id="paypal-btn">PayPal Checkout</div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Breakdown -->
                    <div class="p-4 bg-white bg-opacity-5 rounded-4 mb-4">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small text-muted">Items</span>
                            <span class="small text-white">$<?php echo number_format($items_subtotal, 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small text-muted">Delivery</span>
                            <span class="small" id="payment-delivery-fee"><?php echo $default_delivery > 0 ? '$' . number_format($default_delivery, 2) : 'FREE'; ?></span>
                        </div>
                        <hr class="border-light border-opacity-10 my-2">
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold text-white">Total Charge</span>
                            <span class="fw-bold text-secondary fs-5" id="payment-total">$<?php echo number_format($grand_total, 2); ?></span>
                        </div>
                    </div>

                    <div id="payment-status" class="text-center mb-4 d-none">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-primary small fw-bold" id="status-text">Processing...</p>
                    </div>

                    <button type="submit" name="place_order" id="submit-btn" class="btn btn-primary btn-lg w-100 rounded-pill py-3 shadow-lg fw-bold">
                        <i class="fas fa-lock me-2"></i> Confirm & Pay $<span id="btn-total"><?php echo number_format($grand_total, 2); ?></span>
                    </button>
                    <p class="text-center mt-3 small text-muted"><i class="fas fa-shield-alt text-primary me-1"></i> 256-bit SSL Encrypted Secure Checkout</p>
                </form>
            </div>
        </div>
        <?php else: ?>
        <!-- Success View -->
        <div class="col-12 text-center py-5 animate-fade-in">
            <div class="glass-card col-md-6 mx-auto p-5 border-0 shadow-lg">
                <div class="p-4 bg-success bg-opacity-10 rounded-circle d-inline-flex mb-4">
                    <i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>
                </div>
                <h2 class="fw-bold mb-3 text-white">Order Confirmed!</h2>
                <p class="text-muted fs-5 mb-2">Your order has been placed successfully.</p>
                
                <?php
                // Show delivery info summary
                $has_delivery = false;
                $has_pickup = false;
                if (isset($delivery_choices)) {
                    foreach ($delivery_choices as $sid => $method) {
                        if ($method === 'delivery') $has_delivery = true;
                        if ($method === 'pickup') $has_pickup = true;
                    }
                }
                ?>
                
                <div class="row g-3 justify-content-center my-4">
                    <?php if ($has_delivery): ?>
                        <div class="col-auto">
                            <div class="d-flex align-items-center p-3 rounded-4 bg-primary bg-opacity-10">
                                <i class="fas fa-truck text-primary me-2"></i>
                                <span class="small text-white fw-bold">Delivery items on the way</span>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if ($has_pickup): ?>
                        <div class="col-auto">
                            <div class="d-flex align-items-center p-3 rounded-4" style="background: rgba(16, 185, 129, 0.1);">
                                <i class="fas fa-store me-2" style="color: #10b981;"></i>
                                <span class="small text-white fw-bold">Some items ready for pickup</span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="d-flex justify-content-center gap-3 mt-4">
                    <a href="index.php" class="btn btn-primary rounded-pill px-5 py-3 fw-bold shadow-lg">Back to Market</a>
                    <a href="myorders.php" class="btn btn-outline-light rounded-pill px-5 py-3 fw-bold">My Orders</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Delivery Option Toggle Styles & Script -->
<style>
    .fulfillment-option { cursor: pointer; flex: 1; min-width: 120px; }
    .fulfillment-card { transition: all 0.25s ease; }
    .fulfillment-card:hover { border-color: var(--primary) !important; transform: translateY(-2px); }
    .active-fulfillment { border-color: var(--primary) !important; background: rgba(99, 102, 241, 0.08); box-shadow: 0 4px 15px rgba(99, 102, 241, 0.15); }
</style>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const itemsSubtotal = <?php echo $items_subtotal; ?>;
    
    // Delivery option toggle
    document.querySelectorAll('.delivery-radio').forEach(radio => {
        radio.addEventListener('change', () => {
            // Update UI for this seller group
            const parent = radio.closest('.fulfillment-option').parentElement;
            parent.querySelectorAll('.fulfillment-card').forEach(c => c.classList.remove('active-fulfillment'));
            radio.closest('.fulfillment-option').querySelector('.fulfillment-card').classList.add('active-fulfillment');
            
            // Show/hide pickup info
            const sid = radio.dataset.seller;
            const pickupInfo = document.getElementById('pickup-info-' + sid);
            if (pickupInfo) {
                pickupInfo.classList.toggle('d-none', radio.value !== 'pickup');
            }
            
            // Recalculate totals
            recalcTotals();
        });
    });
    
    // Show pickup info for initially selected pickups
    document.querySelectorAll('.delivery-radio:checked').forEach(radio => {
        if (radio.value === 'pickup') {
            const pickupInfo = document.getElementById('pickup-info-' + radio.dataset.seller);
            if (pickupInfo) pickupInfo.classList.remove('d-none');
        }
    });
    
    function recalcTotals() {
        let deliveryFee = 0;
        let anyDelivery = false;
        
        document.querySelectorAll('.delivery-radio:checked').forEach(radio => {
            deliveryFee += parseFloat(radio.dataset.fee || 0);
            if (radio.value === 'delivery') anyDelivery = true;
        });
        
        const grandTotal = itemsSubtotal + deliveryFee;
        const formatted = grandTotal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        const feeFormatted = deliveryFee > 0 ? '$' + deliveryFee.toFixed(2) : 'FREE';
        const feeClass = deliveryFee > 0 ? 'text-warning' : 'text-success';
        
        // Update all displays
        document.getElementById('delivery-fee-display').innerHTML = `<span class="${feeClass}">${feeFormatted}</span>`;
        document.getElementById('grand-total-display').textContent = '$' + formatted;
        document.getElementById('payment-delivery-fee').textContent = feeFormatted;
        document.getElementById('payment-total').textContent = '$' + formatted;
        document.getElementById('btn-total').textContent = formatted;
        
        // Show/hide shipping address
        const shippingSection = document.getElementById('shipping-section');
        const shippingInput = document.getElementById('shipping-address-input');
        if (!anyDelivery) {
            shippingSection.style.opacity = '0.4';
            shippingInput.removeAttribute('required');
            shippingInput.placeholder = 'Not needed for store pickup orders';
        } else {
            shippingSection.style.opacity = '1';
            shippingInput.placeholder = 'Enter your full delivery address...';
        }
    }
    
    // PayPal button
    const paypalBtn = document.getElementById('paypal-btn');
    if (paypalBtn) {
        paypalBtn.addEventListener('click', () => {
            let deliveryFee = 0;
            document.querySelectorAll('.delivery-radio:checked').forEach(r => deliveryFee += parseFloat(r.dataset.fee || 0));
            const total = itemsSubtotal + deliveryFee;
            
            const statusDiv = document.getElementById('payment-status');
            const statusText = document.getElementById('status-text');
            statusDiv.classList.remove('d-none');
            statusText.textContent = "Negotiating Secure SSL with PayPal...";
            setTimeout(() => { window.location.href = `paypal_login.php?amount=${total}`; }, 1200);
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
