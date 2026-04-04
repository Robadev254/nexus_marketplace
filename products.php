<?php
// products.php
require_once 'includes/header.php';

// Detect user role
$is_seller = isset($_SESSION['role']) && $_SESSION['role'] === 'Seller';
$current_user_id = $_SESSION['user_id'] ?? null;

$category = isset($_GET['category']) ? $_GET['category'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// --- SELLER VIEW: Show only their own products + incoming orders ---
if ($is_seller && $current_user_id) {

    // Fetch seller's own products
    $query = "SELECT * FROM products WHERE seller_id = ?";
    $params = [$current_user_id];

    if ($category) {
        $query .= " AND category = ?";
        $params[] = $category;
    }
    if ($search) {
        $query .= " AND (name LIKE ? OR description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $limit = 8;
    $query .= " ORDER BY (stock > 0) DESC, created_at DESC LIMIT :limit OFFSET 0";

    try {
        $stmt = $pdo->prepare($query);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key + 1, $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $products = $stmt->fetchAll();
    } catch (PDOException $e) {
        die("Error fetching products: " . $e->getMessage());
    }

    // Fetch incoming orders (orders containing this seller's products)
    try {
        $salesStmt = $pdo->prepare("
            SELECT DISTINCT o.*, u.name as buyer_name, u.email as buyer_email
            FROM orders o 
            JOIN order_items oi ON o.id = oi.order_id 
            JOIN products p ON oi.product_id = p.id 
            JOIN users u ON o.user_id = u.id
            WHERE p.seller_id = ? 
            ORDER BY o.order_date DESC
        ");
        $salesStmt->execute([$current_user_id]);
        $incoming_orders = $salesStmt->fetchAll();
    } catch (PDOException $e) {
        $incoming_orders = [];
    }

    // Seller stats
    try {
        $statsStmt = $pdo->prepare("SELECT COUNT(*) as total_items, COALESCE(SUM(stock), 0) as stock_total FROM products WHERE seller_id = ?");
        $statsStmt->execute([$current_user_id]);
        $stats = $statsStmt->fetch();

        $revenueStmt = $pdo->prepare("
            SELECT COALESCE(SUM(oi.price * oi.quantity), 0) as total_revenue
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            JOIN orders o ON oi.order_id = o.id
            WHERE p.seller_id = ? AND o.status != 'Cancelled'
        ");
        $revenueStmt->execute([$current_user_id]);
        $revenue = $revenueStmt->fetch();

        $pendingStmt = $pdo->prepare("
            SELECT COUNT(DISTINCT o.id) as pending_count
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            JOIN products p ON oi.product_id = p.id
            WHERE p.seller_id = ? AND o.status = 'Pending'
        ");
        $pendingStmt->execute([$current_user_id]);
        $pending = $pendingStmt->fetch();
    } catch (PDOException $e) {
        $stats = ['total_items' => 0, 'stock_total' => 0];
        $revenue = ['total_revenue' => 0];
        $pending = ['pending_count' => 0];
    }

} else {

    // --- BUYER / GUEST VIEW: Show ALL products from ALL merchants ---
    $query = "SELECT p.*, u.name as seller_name FROM products p JOIN users u ON p.seller_id = u.id WHERE 1=1";
    $params = [];

    if ($category) {
        $query .= " AND p.category = ?";
        $params[] = $category;
    }

    if ($search) {
        $query .= " AND (p.name LIKE ? OR p.description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $limit = 8;
    $query .= " ORDER BY (p.stock > 0) DESC, p.created_at DESC LIMIT :limit OFFSET 0";

    try {
        $stmt = $pdo->prepare($query);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key + 1, $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $products = $stmt->fetchAll();
    } catch (PDOException $e) {
        die("Error fetching products: " . $e->getMessage());
    }
}
?>

<?php if ($is_seller && $current_user_id): ?>
<!-- ==================== SELLER MARKETPLACE VIEW ==================== -->
<div class="container py-5 animate-fade-in">

    <!-- Seller Header -->
    <div class="row mb-5 text-start">
        <div class="col-md-8">
            <div class="d-flex align-items-center mb-3">
                <div class="bg-primary bg-opacity-10 rounded-circle p-3 me-3">
                    <i class="fas fa-store text-primary fs-4"></i>
                </div>
                <div>
                    <h1 class="fw-bold mb-0 display-6">My Storefront</h1>
                    <p class="text-muted mb-0">Manage your inventory and track incoming orders</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 d-flex justify-content-end align-items-center gap-2">
            <form action="products.php" method="GET" class="d-flex w-100">
                <input type="text" name="search" class="form-control rounded-pill me-2 px-3 bg-dark border-0 text-white" placeholder="Search my items..." value="<?php echo htmlspecialchars($search); ?>">
                <button class="btn btn-primary rounded-pill px-4"><i class="fas fa-search"></i></button>
            </form>
        </div>
    </div>

    <!-- Seller Stats Dashboard -->
    <div class="row g-4 mb-5 animate-fade-in" style="animation-delay: 0.1s;">
        <div class="col-6 col-lg-3">
            <div class="glass-card p-4 border-0 shadow-sm text-center h-100" style="border-left: 3px solid #6366f1 !important;">
                <i class="fas fa-boxes text-primary fs-3 mb-2 opacity-75"></i>
                <h3 class="fw-bold mb-0 text-white"><?php echo (int)$stats['total_items']; ?></h3>
                <p class="text-muted small mb-0 text-uppercase fw-bold">Total Listings</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="glass-card p-4 border-0 shadow-sm text-center h-100" style="border-left: 3px solid #22c55e !important;">
                <i class="fas fa-cubes text-success fs-3 mb-2 opacity-75"></i>
                <h3 class="fw-bold mb-0 text-white"><?php echo (int)$stats['stock_total']; ?></h3>
                <p class="text-muted small mb-0 text-uppercase fw-bold">Stock Units</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="glass-card p-4 border-0 shadow-sm text-center h-100" style="border-left: 3px solid #f59e0b !important;">
                <i class="fas fa-clock text-warning fs-3 mb-2 opacity-75"></i>
                <h3 class="fw-bold mb-0 text-white"><?php echo (int)$pending['pending_count']; ?></h3>
                <p class="text-muted small mb-0 text-uppercase fw-bold">Pending Orders</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="glass-card p-4 border-0 shadow-sm text-center h-100" style="border-left: 3px solid #ec4899 !important;">
                <i class="fas fa-dollar-sign fs-3 mb-2 opacity-75" style="color: #ec4899;"></i>
                <h3 class="fw-bold mb-0 text-white">$<?php echo number_format($revenue['total_revenue'], 2); ?></h3>
                <p class="text-muted small mb-0 text-uppercase fw-bold">Total Revenue</p>
            </div>
        </div>
    </div>

    <!-- Tab Navigation -->
    <ul class="nav nav-pills mb-5 gap-2 animate-fade-in" style="animation-delay: 0.15s;" id="sellerTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active rounded-pill px-4 py-2 fw-bold" id="inventory-tab" data-bs-toggle="pill" data-bs-target="#inventory-panel" type="button" role="tab">
                <i class="fas fa-th-large me-2"></i> My Inventory <span class="badge bg-white bg-opacity-25 ms-1 rounded-pill"><?php echo count($products); ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill px-4 py-2 fw-bold" id="orders-tab" data-bs-toggle="pill" data-bs-target="#orders-panel" type="button" role="tab">
                <i class="fas fa-shopping-bag me-2"></i> Incoming Orders <span class="badge bg-white bg-opacity-25 ms-1 rounded-pill"><?php echo count($incoming_orders); ?></span>
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="sellerTabContent">

        <!-- INVENTORY TAB -->
        <div class="tab-pane fade show active" id="inventory-panel" role="tabpanel">
            <div id="product-grid" class="row g-4 animate-fade-in" style="animation-delay: 0.2s;" 
                 data-page="1" 
                 data-has-more="1" 
                 data-category="<?php echo htmlspecialchars($category); ?>" 
                 data-search="<?php echo htmlspecialchars($search); ?>" 
                 data-seller-id="<?php echo $current_user_id; ?>">
                <!-- Loading Sentinel Added via Script if needed -->
                <div id="scroll-sentinel-seller" class="py-5 text-center d-none" style="grid-column: 1 / -1;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
                <?php if(count($products) > 0): ?>
                    <?php foreach($products as $product): ?>
                        <div class="col-6 col-md-4 col-xl-3">
                            <div class="glass-card product-card h-100 border-0 shadow-sm d-flex flex-column p-3 position-relative">
                                <!-- Seller badge overlay -->
                                <div class="position-absolute top-0 start-0 m-3 z-1">
                                    <?php if ($product['stock'] >= 10): ?>
                                        <span class="badge bg-success bg-opacity-75 rounded-pill px-2 py-1 fs-mini shadow-sm"><i class="fas fa-check-circle me-1"></i>In Stock</span>
                                    <?php elseif ($product['stock'] > 0): ?>
                                        <span class="badge bg-warning text-dark rounded-pill px-2 py-1 fs-mini shadow-sm"><i class="fas fa-exclamation-circle me-1"></i>Limited (<?php echo $product['stock']; ?>)</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger bg-opacity-75 rounded-pill px-2 py-1 fs-mini shadow-sm"><i class="fas fa-times-circle me-1"></i>Out of Stock</span>
                                    <?php endif; ?>
                                </div>
                                <div class="position-relative mb-3">
                                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="rounded-4 w-100" style="aspect-ratio: 4/5; object-fit: cover; <?php echo $product['stock'] <= 0 ? 'filter: grayscale(60%); opacity: 0.6;' : ''; ?>">
                                    <span class="badge position-absolute top-0 end-0 m-3 <?php echo ($product['condition'] == 'New' ? 'bg-primary' : 'bg-secondary'); ?> rounded-pill px-3 py-1 fs-mini shadow-sm"><?php echo $product['condition']; ?></span>
                                </div>
                                <div class="p-1 flex-grow-1 text-start">
                                    <span class="fs-mini fw-bold text-primary opacity-50 text-uppercase tracking-widest"><?php echo htmlspecialchars($product['category']); ?></span>
                                    <h5 class="fw-bold text-white mb-2 text-truncate mt-1"><?php echo htmlspecialchars($product['name']); ?></h5>
                                    <p class="text-muted small mb-3"><?php echo substr(htmlspecialchars($product['description']), 0, 60) . '...'; ?></p>
                                    <div class="d-flex justify-content-between align-items-center mt-auto">
                                        <span class="price-tag fs-5 fw-bold text-white">$<?php echo number_format($product['price'], 2); ?></span>
                                    </div>
                                </div>
                                <!-- Seller action buttons -->
                                <div class="d-flex gap-2 mt-3 pt-3 border-top border-light border-opacity-10">
                                    <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-primary btn-sm rounded-pill flex-grow-1 py-2" title="Edit">
                                        <i class="fas fa-pencil-alt me-1"></i> Edit
                                    </a>
                                    <a href="product_details.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-light btn-sm rounded-pill flex-grow-1 py-2 border-opacity-25" title="View">
                                        <i class="fas fa-eye me-1"></i> View
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 py-5 text-center">
                        <div class="glass-card p-5 border-0 shadow-lg">
                            <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex p-4 mb-4">
                                <i class="fas fa-box-open fs-1 text-primary opacity-50"></i>
                            </div>
                            <h4 class="fw-bold text-white mb-3">Your storefront is empty</h4>
                            <p class="text-muted mb-4">Start listing your products to begin selling on Nexus Market.</p>
                            <a href="profile.php" class="btn btn-primary rounded-pill px-5 py-3 shadow-lg fw-bold">
                                <i class="fas fa-plus me-2"></i> Add Your First Product
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ORDERS TAB -->
        <div class="tab-pane fade" id="orders-panel" role="tabpanel">
            <div class="animate-fade-in" style="animation-delay: 0.2s;">
                <?php if (count($incoming_orders) > 0): ?>
                    <?php foreach ($incoming_orders as $order): ?>
                        <div class="glass-card p-4 border-0 shadow-lg mb-4">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="text-primary small fw-bold text-uppercase tracking-wider">Order</span>
                                        <h5 class="fw-bold mb-0 text-white">#NX-ORD-<?php echo $order['id']; ?></h5>
                                    </div>
                                    <p class="text-muted small mb-0">
                                        <i class="fas fa-user me-1"></i> Buyer: <span class="text-white fw-bold"><?php echo htmlspecialchars($order['buyer_name']); ?></span>
                                        <span class="mx-2 opacity-25">|</span>
                                        <i class="far fa-calendar-alt me-1"></i> <?php echo date("M d, Y | H:i", strtotime($order['order_date'])); ?>
                                        <?php if (isset($order['delivery_method'])): ?>
                                            <span class="ms-2 badge bg-<?php echo $order['delivery_method'] === 'Pickup' ? 'success' : 'primary'; ?> bg-opacity-10 text-<?php echo $order['delivery_method'] === 'Pickup' ? 'success' : 'primary'; ?> rounded-pill px-2 py-1 fs-mini">
                                                <i class="fas fa-<?php echo $order['delivery_method'] === 'Pickup' ? 'store' : 'truck'; ?> me-1"></i><?php echo $order['delivery_method']; ?>
                                            </span>
                                        <?php endif; ?>
                                    </p>
                                    <?php if (isset($order['delivery_method']) && $order['delivery_method'] === 'Delivery' && !empty($order['shipping_address'])): ?>
                                        <p class="text-muted fs-mini mt-1 mb-0"><i class="fas fa-map-marker-alt text-primary me-1"></i> Ship to: <?php echo htmlspecialchars($order['shipping_address']); ?></p>
                                    <?php elseif (isset($order['delivery_method']) && $order['delivery_method'] === 'Pickup'): ?>
                                        <p class="fs-mini mt-1 mb-0" style="color: #10b981;"><i class="fas fa-store me-1"></i> Customer will pick up from your store</p>
                                    <?php endif; ?>
                                </div>
                                <div class="mt-3 mt-md-0 d-flex align-items-center gap-3">
                                    <?php 
                                        $status_class = match($order['status']) {
                                            'Pending' => 'bg-warning text-dark',
                                            'Shipped' => 'bg-info text-white',
                                            'Delivered' => 'bg-success text-white',
                                            'Cancelled' => 'bg-danger text-white',
                                            default => 'bg-secondary text-white'
                                        };
                                    ?>
                                    <span class="badge rounded-pill px-4 py-2 fw-bold fs-6 shadow-sm <?php echo $status_class; ?>">
                                        <?php echo strtoupper($order['status']); ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Order Items -->
                            <div class="table-responsive rounded-4 overflow-hidden mb-4">
                                <table class="table table-dark table-hover border-0 align-middle mb-0">
                                    <thead class="bg-white bg-opacity-10">
                                        <tr>
                                            <th class="py-3 px-4 border-0">Item</th>
                                            <th class="py-3 border-0 text-center">Qty</th>
                                            <th class="py-3 border-0 text-end">Unit Price</th>
                                            <th class="py-3 border-0 text-end px-4">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Only show items from this seller's products
                                        $itemStmt = $pdo->prepare("
                                            SELECT oi.*, p.name, p.image_url 
                                            FROM order_items oi 
                                            JOIN products p ON oi.product_id = p.id 
                                            WHERE oi.order_id = ? AND p.seller_id = ?
                                        ");
                                        $itemStmt->execute([$order['id'], $current_user_id]);
                                        $items = $itemStmt->fetchAll();
                                        $seller_subtotal = 0;
                                        foreach ($items as $item):
                                            $seller_subtotal += $item['price'] * $item['quantity'];
                                        ?>
                                            <tr class="border-light border-opacity-10">
                                                <td class="py-4 px-4 border-0">
                                                    <div class="d-flex align-items-center">
                                                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" class="rounded-3 shadow-sm me-3" width="50" height="50" style="object-fit: cover; border: 1px solid rgba(255,255,255,0.1);">
                                                        <span class="fw-bold text-white"><?php echo htmlspecialchars($item['name']); ?></span>
                                                    </div>
                                                </td>
                                                <td class="py-4 border-0 text-center fw-bold text-white"><?php echo $item['quantity']; ?></td>
                                                <td class="py-4 border-0 text-end text-muted">$<?php echo number_format($item['price'], 2); ?></td>
                                                <td class="py-4 border-0 text-end px-4 fw-bold text-primary">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Order Footer -->
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center p-4 bg-white bg-opacity-5 rounded-4">
                                <div class="d-flex align-items-center gap-3">
                                    <?php if ($order['status'] !== 'Delivered' && $order['status'] !== 'Cancelled'): ?>
                                        <form action="profile.php" method="POST" class="d-flex gap-2">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <input type="hidden" name="update_order_status" value="1">
                                            <?php if ($order['status'] === 'Pending'): ?>
                                                <input type="hidden" name="new_status" value="Shipped">
                                                <button type="submit" class="btn btn-outline-primary btn-sm rounded-pill px-4 py-2 fw-bold shadow-sm">
                                                    <i class="fas fa-shipping-fast me-1"></i> Mark Shipped
                                                </button>
                                            <?php elseif ($order['status'] === 'Shipped'): ?>
                                                <input type="hidden" name="new_status" value="Delivered">
                                                <button type="submit" class="btn btn-success btn-sm rounded-pill px-4 py-2 fw-bold shadow-sm">
                                                    <i class="fas fa-check-double me-1"></i> Mark Delivered
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted small">
                                            <i class="fas fa-<?php echo $order['status'] === 'Delivered' ? 'check-circle text-success' : 'times-circle text-danger'; ?> me-1"></i>
                                            <?php echo $order['status'] === 'Delivered' ? 'Fulfilled' : 'Cancelled'; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-end mt-3 mt-md-0">
                                    <p class="mb-0 text-muted small text-uppercase fw-bold">Your Earnings</p>
                                    <h4 class="fw-bold mb-0 text-secondary">$<?php echo number_format($seller_subtotal, 2); ?></h4>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="glass-card p-5 border-0 shadow-lg text-center">
                        <div class="bg-warning bg-opacity-10 rounded-circle d-inline-flex p-4 mb-4">
                            <i class="fas fa-inbox fs-1 text-warning opacity-50"></i>
                        </div>
                        <h4 class="fw-bold text-white mb-3">No orders yet</h4>
                        <p class="text-muted mb-0">When buyers purchase your products, their orders will appear here.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<!-- ==================== BUYER / GUEST MARKETPLACE VIEW ==================== -->
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
    <div id="product-grid" class="row g-4 animate-fade-in" style="animation-delay: 0.2s;" 
         data-page="1" 
         data-has-more="1" 
         data-category="<?php echo htmlspecialchars($category); ?>" 
         data-search="<?php echo htmlspecialchars($search); ?>" 
         data-seller-id="0">
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
                                <!-- Show seller name for buyers -->
                                <div class="mt-2 pt-2 border-top border-light border-opacity-10">
                                    <span class="text-muted fs-mini"><i class="fas fa-store me-1 opacity-50"></i> <?php echo htmlspecialchars($product['seller_name']); ?></span>
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
    <!-- Loading Sentinel -->
    <div id="scroll-sentinel" class="py-5 text-center d-none">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>

<!-- Infinite Scroll Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const grid = document.getElementById('product-grid');
    const sentinel = document.getElementById('scroll-sentinel') || document.getElementById('scroll-sentinel-seller');
    const isSeller = <?php echo $is_seller ? 'true' : 'false'; ?>;
    
    if (!grid || !sentinel) return;

    const observer = new IntersectionObserver(entries => {
        if (entries[0].isIntersecting && grid.dataset.hasMore === '1') {
            loadMoreProducts();
        }
    }, { threshold: 0.1 });

    observer.observe(sentinel);

    async function loadMoreProducts() {
        const page = parseInt(grid.dataset.page) + 1;
        const category = grid.dataset.category;
        const search = grid.dataset.search;
        const sellerId = grid.dataset.sellerId;

        sentinel.classList.remove('d-none');

        try {
            const response = await fetch(`api/fetch_products.php?page=${page}&category=${encodeURIComponent(category)}&search=${encodeURIComponent(search)}&seller_id=${sellerId}`);
            const result = await response.json();

            if (result.status === 'success' && result.products.length > 0) {
                result.products.forEach(product => {
                    const col = document.createElement('div');
                    col.className = 'col-6 col-md-4 col-xl-3 animate-fade-in';
                    
                    if (isSeller) {
                        col.innerHTML = generateSellerCard(product);
                    } else {
                        col.innerHTML = generateBuyerCard(product);
                    }
                    
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
        } catch (error) {
            console.error('Error fetching Products:', error);
            sentinel.classList.add('d-none');
        } finally {
            sentinel.classList.add('d-none');
        }
    }

    function generateBuyerCard(p) {
        return `
            <a href="product_details.php?id=${p.id}" class="text-decoration-none h-100">
                <div class="glass-card product-card h-100 border-0 shadow-sm hover-scale d-flex flex-column p-3">
                    <div class="position-relative mb-3">
                        <img src="${p.image_url}" alt="${p.name}" class="rounded-4 w-100" style="aspect-ratio: 4/5; object-fit: cover;">
                        <span class="badge position-absolute top-0 end-0 m-3 ${p.badge_class} rounded-pill px-3 py-1 fs-mini shadow-sm">${p.condition}</span>
                    </div>
                    <div class="p-1 flex-grow-1 text-start">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="fs-mini fw-bold text-primary opacity-50 text-uppercase tracking-widest">${p.category}</span>
                            ${p.stock >= 10 
                                ? '<span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2 py-1 fs-mini shadow-sm">In Stock</span>'
                                : (p.stock > 0 
                                    ? `<span class="badge bg-warning text-dark rounded-pill px-2 py-1 fs-mini animate-pulse shadow-sm">Limited Stock (${p.stock})</span>`
                                    : '<span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-2 py-1 fs-mini shadow-sm">Out of Stock</span>')}
                        </div>
                        <h5 class="fw-bold text-white mb-2 text-truncate">${p.name}</h5>
                        <p class="text-muted small mb-3 flex-grow-1">${p.short_description}</p>
                        <div class="d-flex justify-content-between align-items-center mt-auto">
                            <span class="price-tag fs-5 fw-bold text-white">$${p.formatted_price}</span>
                            <div class="btn btn-outline-light btn-sm rounded-circle p-2 shadow-none"><i class="fas fa-chevron-right fs-mini"></i></div>
                        </div>
                        <div class="mt-2 pt-2 border-top border-light border-opacity-10">
                            <span class="text-muted fs-mini"><i class="fas fa-store me-1 opacity-50"></i> ${p.seller_name}</span>
                        </div>
                    </div>
                </div>
            </a>
        `;
    }

    function generateSellerCard(p) {
        return `
            <div class="glass-card product-card h-100 border-0 shadow-sm d-flex flex-column p-3 position-relative">
                <div class="position-absolute top-0 start-0 m-3 z-1">
                    ${p.stock >= 10 
                        ? '<span class="badge bg-success bg-opacity-75 rounded-pill px-2 py-1 fs-mini shadow-sm"><i class="fas fa-check-circle me-1"></i>In Stock</span>'
                        : (p.stock > 0 
                            ? `<span class="badge bg-warning text-dark rounded-pill px-2 py-1 fs-mini shadow-sm"><i class="fas fa-exclamation-circle me-1"></i>Limited (${p.stock})</span>`
                            : '<span class="badge bg-danger bg-opacity-75 rounded-pill px-2 py-1 fs-mini shadow-sm"><i class="fas fa-times-circle me-1"></i>Out of Stock</span>')}
                </div>
                <div class="position-relative mb-3">
                    <img src="${p.image_url}" alt="${p.name}" class="rounded-4 w-100" style="aspect-ratio: 4/5; object-fit: cover; ${p.stock <= 0 ? 'filter: grayscale(60%); opacity: 0.6;' : ''}">
                    <span class="badge position-absolute top-0 end-0 m-3 ${p.badge_class} rounded-pill px-3 py-1 fs-mini shadow-sm">${p.condition}</span>
                </div>
                <div class="p-1 flex-grow-1 text-start">
                    <span class="fs-mini fw-bold text-primary opacity-50 text-uppercase tracking-widest">${p.category}</span>
                    <h5 class="fw-bold text-white mb-2 text-truncate mt-1">${p.name}</h5>
                    <p class="text-muted small mb-3">${p.short_description}</p>
                    <div class="d-flex justify-content-between align-items-center mt-auto">
                        <span class="price-tag fs-5 fw-bold text-white">$${p.formatted_price}</span>
                    </div>
                </div>
                <div class="d-flex gap-2 mt-3 pt-3 border-top border-light border-opacity-10">
                    <a href="edit_product.php?id=${p.id}" class="btn btn-outline-primary btn-sm rounded-pill flex-grow-1 py-2" title="Edit">
                        <i class="fas fa-pencil-alt me-1"></i> Edit
                    </a>
                    <a href="product_details.php?id=${p.id}" class="btn btn-outline-light btn-sm rounded-pill flex-grow-1 py-2 border-opacity-25" title="View">
                        <i class="fas fa-eye me-1"></i> View
                    </a>
                </div>
            </div>
        `;
    }
});
</script>
