<?php
// admin/dashboard.php
session_start();
require_once '../includes/db.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

try {
    $user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $product_count = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $order_count = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $total_revenue = $pdo->query("SELECT SUM(total_price) FROM orders WHERE status != 'Cancelled'")->fetchColumn() ?: 0;
    
    // Process Order Status Update
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_delivered'])) {
        $order_id = (int)$_POST['order_id'];
        $pdo->prepare("UPDATE orders SET status = 'Delivered' WHERE id = ?")->execute([$order_id]);
        header("Location: dashboard.php?msg=Order Updated Successfully");
        exit;
    }

    $recent_orders = $pdo->query("SELECT o.*, u.name as user_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.order_date DESC LIMIT 5")->fetchAll();
} catch (PDOException $e) {
    die("Error fetching dashboard data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel | Nexus Market</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="d-flex flex-column h-100">

<nav class="navbar navbar-expand-lg navbar-dark bg-transparent">
    <div class="container">
        <a class="navbar-brand fw-bold fs-3" href="../index.php" style="background: linear-gradient(45deg, #6366f1, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">NEXUS ADMIN</a>
        <div class="ms-auto d-flex align-items-center">
            <span class="text-white opacity-100 me-3">Welcome, <span class="fw-bold text-primary"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span></span>
            <a href="../logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3">Logout</a>
        </div>
    </div>
</nav>

<div class="container py-5 flex-shrink-0 animate-fade-in">
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="glass-card text-center py-5 border-start border-primary border-5 rounded-4 shadow-sm h-100">
                <i class="fas fa-users mb-4 fs-1 opacity-75 text-primary"></i>
                <h2 class="fw-bold mb-1"><?php echo $user_count; ?></h2>
                <p class="text-white-50 text-uppercase small mb-0 fw-bold">Total Community</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="glass-card text-center py-5 border-start border-secondary border-5 rounded-4 shadow-sm h-100">
                <i class="fas fa-box-open mb-4 fs-1 opacity-75 text-secondary"></i>
                <h2 class="fw-bold mb-1"><?php echo $product_count; ?></h2>
                <p class="text-white-50 text-uppercase small mb-0 fw-bold">Live Marketplace</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="glass-card text-center py-5 border-start border-accent border-5 rounded-4 shadow-sm h-100" style="border-color: #10b981 !important;">
                <i class="fas fa-shopping-cart mb-4 fs-1 opacity-75 text-accent"></i>
                <h2 class="fw-bold mb-1"><?php echo $order_count; ?></h2>
                <p class="text-white-50 text-uppercase small mb-0 fw-bold">Volume Orders</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="glass-card text-center py-5 border-start border-warning border-5 rounded-4 shadow-sm h-100" style="border-color: #f59e0b !important;">
                <i class="fas fa-wallet mb-4 fs-1 opacity-75 text-warning"></i>
                <h2 class="fw-bold mb-1">$<?php echo number_format((float)($total_revenue ?? 0), 2); ?></h2>
                <p class="text-white-50 text-uppercase small mb-0 fw-bold">Total Revenue</p>
            </div>
        </div>
    </div>

    <div class="row g-5">
        <div class="col-lg-8">
            <div class="glass-card p-4 border-0 shadow-lg">
                <div class="d-flex justify-content-between align-items-center mb-5">
                    <h4 class="fw-bold mb-0">Market Activity</h4>
                    <a href="#" class="btn btn-outline-light btn-sm rounded-pill px-3">See All Transitions</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-dark table-hover border-0 mb-0">
                        <thead class="border-light border-opacity-25">
                            <tr class="text-white-50">
                                <th class="py-4 border-0 bg-transparent">ID</th>
                                <th class="py-4 border-0 bg-transparent">User</th>
                                <th class="py-4 border-0 bg-transparent">Price</th>
                                <th class="py-4 border-0 bg-transparent">Status</th>
                                <th class="py-4 border-0 bg-transparent">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recent_orders as $order): ?>
                                <tr class="align-middle border-light border-opacity-10">
                                    <td class="py-4 border-0 bg-transparent fw-bold text-primary">#<?php echo $order['id']; ?></td>
                                    <td class="py-4 border-0 bg-transparent text-white"><?php echo htmlspecialchars($order['user_name']); ?></td>
                                    <td class="py-4 border-0 bg-transparent text-white fw-bold">$<?php echo number_format($order['total_price'], 2); ?></td>
                                    <td class="py-4 border-0 bg-transparent">
                                        <?php if ($order['status'] == 'Pending'): ?>
                                            <form action="dashboard.php" method="POST" class="d-inline">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <button type="submit" name="mark_delivered" class="badge bg-primary bg-opacity-25 border-0 rounded-pill px-3 py-2 text-white shadow-sm pointer">
                                                    Mark Delivered
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="badge bg-opacity-25 rounded-pill px-3 py-2 text-white bg-secondary opacity-75"><?php echo $order['status']; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 border-0 bg-transparent text-white-50 small"><?php echo date("M d, Y", strtotime($order['order_date'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="glass-card p-4 h-100 border-0 shadow-lg">
                <h4 class="fw-bold mb-5">Admin Portal</h4>
                <div class="d-grid gap-3">
                    <a href="manage_products.php" class="btn btn-primary py-4 rounded-4 fs-5 text-start shadow-sm position-relative overflow-hidden mb-3">
                        <i class="fas fa-boxes me-3 fs-3 opacity-25 position-absolute top-50 end-0 translate-middle"></i>
                        <h6 class="mb-1 text-white-50 small text-uppercase fw-bold">Live Inventory</h6>
                        <span class="fw-bold">Market Management</span>
                    </a>
                    <a href="manage_categories.php" class="btn btn-outline-light py-4 rounded-4 fs-5 text-start shadow-sm mb-3">
                        <h6 class="mb-1 text-white-50 small text-uppercase fw-bold">Node Hierarchy</h6>
                        <span class="fw-bold">Category Classification</span>
                    </a>
                    <a href="#" class="btn btn-outline-light py-4 rounded-4 fs-5 text-start shadow-sm opacity-50 mb-3">
                        <h6 class="mb-1 text-white-50 small text-uppercase fw-bold">User Records</h6>
                        <span class="fw-bold">Community Governance</span>
                    </a>
                </div>

                <hr class="my-5 opacity-10">
                
                <h5 class="fw-bold mb-4 small text-white-50 text-uppercase tracking-widest">Global Communication Frequency</h5>
                <div class="list-group list-group-flush bg-transparent overflow-auto" style="max-height: 400px;">
                    <?php 
                    $msgStmt = $pdo->query("SELECT m.*, u1.name as sender, u2.name as receiver FROM messages m JOIN users u1 ON m.sender_id = u1.id JOIN users u2 ON m.receiver_id = u2.id ORDER BY m.created_at DESC LIMIT 20");
                    foreach($msgStmt->fetchAll() as $m): ?>
                        <div class="list-group-item bg-transparent border-light border-opacity-10 py-3 px-0">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="small fw-bold text-primary"><?php echo htmlspecialchars($m['sender']); ?> <i class="fas fa-arrow-right mx-1 opacity-25"></i> <?php echo htmlspecialchars($m['receiver']); ?></span>
                                <span class="fs-mini opacity-25"><?php echo date("H:i", strtotime($m['created_at'])); ?></span>
                            </div>
                            <p class="small text-white-50 mb-0 italic">"<?php echo htmlspecialchars($m['content']); ?>"</p>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($msgStmt->rowCount() == 0): ?>
                        <p class="small text-muted italic text-center py-3">No active transmissions detected.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="footer mt-auto bg-transparent py-4 text-center border-top border-light border-opacity-10 text-muted small">
    &copy; <?php echo date("Y"); ?> Nexus Admin Terminal. Optimized for scale and performance.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
