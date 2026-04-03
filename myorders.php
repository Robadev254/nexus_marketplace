<?php
// myorders.php
require_once 'includes/header.php';

// Authentication Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch User Orders
try {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error fetching orders: " . $e->getMessage());
}
?>

<div class="container py-5 animate-fade-in">
    <div class="row mb-5">
        <div class="col-12">
            <h1 class="fw-bold mb-4 display-5 text-white">My Purchase History</h1>
            <p class="text-muted fs-5">Track the status of your marketplace acquisitions.</p>
        </div>
    </div>

    <?php if (count($orders) > 0): ?>
        <div class="row g-4">
            <?php foreach ($orders as $order): ?>
                <div class="col-12">
                    <div class="glass-card p-4 border-0 shadow-lg mb-4">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4">
                            <div>
                                <span class="text-primary small fw-bold text-uppercase tracking-wider">Transaction ID</span>
                                <h4 class="fw-bold mb-1 text-white">#NX-ORD-<?php echo $order['id']; ?></h4>
                                <p class="text-muted small mb-0"><i class="far fa-calendar-alt me-1"></i> Placed on <?php echo date("M d, Y | H:i", strtotime($order['order_date'])); ?></p>
                            </div>
                            <div class="mt-3 mt-md-0 d-flex align-items-center">
                                <?php 
                                    $status_label = $order['status'];
                                    $status_class = 'bg-secondary';
                                    
                                    if ($status_label == 'Pending') {
                                        $status_class = 'bg-warning text-dark';
                                    } elseif ($status_label == 'Shipped' || $status_label == 'Delivered') {
                                        $status_label = 'Completed';
                                        $status_class = 'bg-success text-white';
                                    } elseif ($status_label == 'Cancelled') {
                                        $status_class = 'bg-danger text-white';
                                    }
                                ?>
                                <span class="badge rounded-pill px-4 py-3 fw-bold fs-6 shadow-sm <?php echo $status_class; ?>">
                                    <?php echo strtoupper($status_label); ?>
                                </span>
                            </div>
                        </div>

                        <div class="table-responsive rounded-4 overflow-hidden mb-4">
                            <table class="table table-dark table-hover border-0 align-middle mb-0">
                                <thead class="bg-white bg-opacity-10">
                                    <tr>
                                        <th class="py-3 px-4 border-0">Item Description</th>
                                        <th class="py-3 border-0 text-center">Batch</th>
                                        <th class="py-3 border-0 text-end px-4">Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $itemStmt = $pdo->prepare("
                                        SELECT oi.*, p.name, p.image_url 
                                        FROM order_items oi 
                                        JOIN products p ON oi.product_id = p.id 
                                        WHERE oi.order_id = ?
                                    ");
                                    $itemStmt->execute([$order['id']]);
                                    $items = $itemStmt->fetchAll();
                                    foreach ($items as $item):
                                    ?>
                                        <tr class="border-light border-opacity-10">
                                            <td class="py-4 px-4 border-0">
                                                <div class="d-flex align-items-center">
                                                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" class="rounded-3 shadow-sm me-3" width="60" height="60" style="object-fit: cover; border: 1px solid rgba(255,255,255,0.1);">
                                                    <div>
                                                        <p class="mb-0 fw-bold text-white"><?php echo htmlspecialchars($item['name']); ?></p>
                                                        <span class="text-muted small">$<?php echo number_format($item['price'], 2); ?> per unit</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-4 border-0 text-center fw-bold text-white"><?php echo $item['quantity']; ?></td>
                                            <td class="py-4 border-0 text-end px-4 fw-bold text-primary">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between align-items-center p-4 bg-white bg-opacity-5 rounded-4">
                            <div class="d-flex align-items-center">
                                <div class="me-4">
                                    <p class="mb-0 text-muted small text-uppercase">Payment Method</p>
                                    <p class="mb-0 text-white fw-bold"><i class="fas fa-shield-check text-success me-1"></i> Secure Transaction</p>
                                </div>
                                <?php if ($order['status'] == 'Pending'): ?>
                                    <form action="cancel_order.php" method="POST" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-4 py-2 border-opacity-25 bg-danger bg-opacity-10">
                                            <i class="fas fa-times me-1"></i> Cancel Order
                                        </button>
                                    </form>
                                <?php elseif ($order['status'] == 'Cancelled'): ?>
                                    <form action="reorder.php" method="POST">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 py-2 shadow-sm">
                                            <i class="fas fa-redo me-1"></i> Reorder Now
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                            <div class="text-end">
                                <p class="mb-0 text-muted small text-uppercase">Total Settlement</p>
                                <h3 class="fw-bold mb-0 text-secondary">$<?php echo number_format($order['total_price'], 2); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
<?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-5 glass-card animate-fade-in shadow-lg">
            <div class="mb-5 bg-white bg-opacity-10 rounded-circle d-inline-flex p-4">
                <i class="fas fa-shopping-bag fs-1 text-muted opacity-50"></i>
            </div>
            <h2 class="fw-bold mb-3 text-white">No Marketplace Activity</h2>
            <p class="text-muted mb-5 fs-5">You haven't placed any orders yet. Ready to find something extraordinary?</p>
            <a href="products.php" class="btn btn-primary rounded-pill px-5 btn-lg shadow-lg">Browse Marketplace</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
