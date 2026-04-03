<?php
// profile.php
require_once 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success_msg = "";
$error_msg = "";

// --- Quick Stock Toggle Handling ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_stock') {
    $p_id = (int)$_POST['product_id'];
    $new_stock = (int)$_POST['new_stock'];
    try {
        $stmt = $pdo->prepare("UPDATE products SET stock = ? WHERE id = ? AND seller_id = ?");
        $stmt->execute([$new_stock, $p_id, $user_id]);
        $success_msg = "Inventory status synchronized.";
    } catch (PDOException $e) { $error_msg = "Status update failure."; }
}

// --- Messaging Node Handling ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    $receiver_id = (int)$_POST['receiver_id'];
    $content = trim($_POST['content']);
    if ($content) {
        try {
            $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, content) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $receiver_id, $content]);
            $success_msg = "Message broadcasted successfully.";
        } catch (PDOException $e) { $error_msg = "Communication failure."; }
    }
}

// --- Profile Photo Upload (Existing) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_pic'])) {
    $file = $_FILES['profile_pic'];
    if ($file['error'] === 0) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_name = "profile_" . $user_id . "_" . time() . "." . $ext;
        $upload_path = "uploads/profiles/" . $new_name;
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            $stmt = $pdo->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
            $stmt->execute([$upload_path, $user_id]);
            $success_msg = "Aesthetic visual updated.";
        }
    }
}

// --- Global Data Fetching ---
$userStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$userStmt->execute([$user_id]);
$user_data = $userStmt->fetch();
$role = $user_data['role'];
$profile_pic = $user_data['profile_pic'] ?: 'assets/img/default_user.png';

// Messaging: Fetch active conversations
$convStmt = $pdo->prepare("
    SELECT DISTINCT 
        CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END as contact_id,
        u.name as contact_name
    FROM messages m
    JOIN users u ON (u.id = CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END)
    WHERE sender_id = ? OR receiver_id = ?
");
$convStmt->execute([$user_id, $user_id, $user_id, $user_id]);
$conversations = $convStmt->fetchAll();

// Merchant Data
if ($role === 'Seller') {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE seller_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $seller_products = $stmt->fetchAll();
    
    $statsStmt = $pdo->prepare("SELECT COUNT(*) as total_items, SUM(stock) as stock_total FROM products WHERE seller_id = ?");
    $statsStmt->execute([$user_id]);
    $stats = $statsStmt->fetch();
} else {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC LIMIT 5");
    $stmt->execute([$user_id]);
    $buyer_orders = $stmt->fetchAll();
}
?>

<div class="container py-5 animate-fade-in shadow-none border-0 text-start">
    <div class="row g-5">
        <!-- Identity Sidebar -->
        <div class="col-lg-4">
            <div class="glass-card p-5 border-0 shadow-lg text-center h-100">
                <div class="position-relative d-inline-block mb-5">
                    <img src="<?php echo htmlspecialchars($profile_pic); ?>" id="profile-preview" class="rounded-circle shadow-lg border border-3 border-primary" width="160" height="160" style="object-fit: cover;">
                    <form action="profile.php" method="POST" enctype="multipart/form-data" id="photo-form">
                        <label for="pic-upload" class="position-absolute bottom-0 end-0 bg-primary p-3 rounded-circle shadow-lg cursor-pointer hover-scale" title="Update Identity Visual">
                            <i class="fas fa-camera text-white"></i>
                            <input type="file" name="profile_pic" id="pic-upload" hidden onchange="document.getElementById('photo-form').submit()">
                        </label>
                    </form>
                </div>
                <h3 class="fw-bold mb-1"><?php echo htmlspecialchars($user_data['name']); ?></h3>
                <p class="text-muted small mb-4"><?php echo htmlspecialchars($user_data['email']); ?></p>
                <span class="badge bg-primary bg-opacity-10 text-primary px-4 py-2 rounded-pill fw-bold text-uppercase"><?php echo $role; ?> NODE</span>
                
                <hr class="my-5 opacity-10">
                
                <!-- Communication Hub -->
                <div class="text-start">
                    <h6 class="small fw-bold opacity-50 mb-4 text-uppercase tracking-widest">Global Messages</h6>
                    <?php if (count($conversations) > 0): ?>
                        <div class="list-group list-group-flush bg-transparent">
                            <?php foreach ($conversations as $c): ?>
                                <button type="button" class="list-group-item list-group-item-action bg-transparent border-0 text-white py-3 px-0 d-flex justify-content-between align-items-center" data-bs-toggle="modal" data-bs-target="#msgModal<?php echo $c['contact_id']; ?>">
                                    <span><i class="fas fa-user-circle me-2 opacity-50"></i> <?php echo htmlspecialchars($c['contact_name']); ?></span>
                                    <i class="fas fa-chevron-right fs-mini opacity-25"></i>
                                </button>
                                
                                <!-- Messaging Modal -->
                                <div class="modal fade" id="msgModal<?php echo $c['contact_id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content glass-card p-4 border-0 shadow-lg">
                                            <div class="modal-header border-0 pb-0"><h5 class="fw-bold">Channel: <?php echo htmlspecialchars($c['contact_name']); ?></h5><button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal"></button></div>
                                            <div class="modal-body">
                                                <div class="chat-log mb-4 overflow-auto p-3 bg-dark bg-opacity-50 rounded-4" style="height: 300px;">
                                                    <?php 
                                                    $chatStmt = $pdo->prepare("SELECT * FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY created_at ASC");
                                                    $chatStmt->execute([$user_id, $c['contact_id'], $c['contact_id'], $user_id]);
                                                    foreach($chatStmt->fetchAll() as $msg): ?>
                                                        <div class="mb-3 <?php echo ($msg['sender_id'] == $user_id ? 'text-end' : 'text-start'); ?>">
                                                            <div class="d-inline-block p-3 rounded-4 <?php echo ($msg['sender_id'] == $user_id ? 'bg-primary text-white' : 'bg-white bg-opacity-10 text-white'); ?> small">
                                                                <?php echo htmlspecialchars($msg['content']); ?>
                                                            </div>
                                                            <div class="fs-mini opacity-25 mt-1"><?php echo date("H:i", strtotime($msg['created_at'])); ?></div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                                <form action="profile.php" method="POST" class="d-flex gap-2">
                                                    <input type="hidden" name="receiver_id" value="<?php echo $c['contact_id']; ?>">
                                                    <input type="text" name="content" class="form-control rounded-pill px-3" placeholder="Broadcast message..." required>
                                                    <button type="submit" name="send_message" class="btn btn-primary rounded-circle p-2 shadow-none"><i class="fas fa-paper-plane"></i></button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="small text-muted italic">No active frequency found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Role Analytics & Control -->
        <div class="col-lg-8">
            <div class="glass-card p-5 border-0 shadow-lg h-100">
                <?php if ($success_msg): ?><div class="alert alert-success border-0 small rounded-4 mb-5 p-3 shadow-sm"><?php echo $success_msg; ?></div><?php endif; ?>
                
                <?php if ($role === 'Seller'): ?>
                    <h4 class="fw-bold mb-5">Merchant Hub: Resource Management</h4>
                    
                    <div class="row g-4 mb-5">
                        <div class="col-md-6"><div class="p-4 bg-white bg-opacity-5 rounded-4 border border-light border-opacity-10"><h3 class="fw-bold mb-0 text-primary"><?php echo count($seller_products); ?></h3><p class="small text-muted mb-0">Total Listing Nodes</p></div></div>
                        <div class="col-md-6"><div class="p-4 bg-white bg-opacity-5 rounded-4 border border-light border-opacity-10"><h3 class="fw-bold mb-0 text-success"><?php echo (int)$stats['stock_total']; ?></h3><p class="small text-muted mb-0">Aggregate Batch Units</p></div></div>
                    </div>

                    <h5 class="fw-bold mb-4 small opacity-50 text-uppercase tracking-widest">Inventory Management</h5>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover align-middle border-light border-opacity-10">
                            <thead class="bg-white bg-opacity-5">
                                <tr>
                                    <th class="py-4 border-0">Product Node</th>
                                    <th class="py-4 border-0 text-center">Batch Level</th>
                                    <th class="py-4 border-0 text-center">Control</th>
                                    <th class="py-4 border-0 text-end px-3">Valuation</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($seller_products as $p): ?>
                                    <tr class="border-light border-opacity-10">
                                        <td class="py-4 border-0">
                                            <div class="d-flex align-items-center">
                                                <img src="<?php echo htmlspecialchars($p['image_url']); ?>" class="rounded-3 me-3" width="40" height="40" style="object-fit: cover;">
                                                <span class="fw-bold"><?php echo htmlspecialchars($p['name']); ?></span>
                                            </div>
                                        </td>
                                        <td class="py-4 border-0 text-center">
                                            <?php if ($p['stock'] > 0): ?>
                                                <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-1">IN STOCK (<?php echo $p['stock']; ?>)</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3 py-1">OUT OF STOCK</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-4 border-0 text-center">
                                            <form action="profile.php" method="POST">
                                                <input type="hidden" name="action" value="toggle_stock">
                                                <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                                                <?php if ($p['stock'] > 0): ?>
                                                    <input type="hidden" name="new_stock" value="0">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3">Deactivate</button>
                                                <?php else: ?>
                                                    <input type="hidden" name="new_stock" value="20">
                                                    <button type="submit" class="btn btn-outline-success btn-sm rounded-pill px-3">Re-stock</button>
                                                <?php endif; ?>
                                            </form>
                                        </td>
                                        <td class="py-4 border-0 text-end px-3 fw-bold text-primary">$<?php echo number_format($p['price'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <h4 class="fw-bold mb-5">Acquisition History</h4>
                    <div class="list-group list-group-flush">
                        <?php foreach($buyer_orders as $order): ?>
                            <div class="list-group-item bg-transparent border-light border-opacity-10 py-4 px-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div><h6 class="fw-bold mb-1">Nexus Order #<?php echo $order['id']; ?></h6><p class="small text-muted mb-0"><?php echo date("M d, Y", strtotime($order['order_date'])); ?></p></div>
                                    <div class="text-end"><h5 class="fw-bold text-primary mb-1">$<?php echo number_format($order['total_price'], 2); ?></h5><span class="badge bg-<?php echo ($order['status'] === 'Delivered' ? 'success' : 'warning'); ?> rounded-pill small"><?php echo $order['status']; ?></span></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (count($buyer_orders) == 0): ?>
                            <div class="py-5 text-center opacity-25">No acquisition records found.</div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
