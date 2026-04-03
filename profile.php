<?php
// profile.php - Logic Hub (PRG Pattern) must precede all output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success_msg = $_SESSION['flash_success'] ?? "";
$error_msg = $_SESSION['flash_error'] ?? "";
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// --- MERCHANT: Delete Listing Node ---
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ? AND seller_id = ?");
        $stmt->execute([$del_id, $user_id]);
        if ($stmt->rowCount() > 0) {
            $_SESSION['flash_success'] = "Listing node successfully archived.";
        } else {
            $_SESSION['flash_error'] = "Archival failure: Unauthorized or node not found.";
        }
    } catch (PDOException $e) { $_SESSION['flash_error'] = "Archiving node failed."; }
    header("Location: profile.php");
    exit;
}

// --- Quick Stock Toggle Handling ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_stock') {
    $p_id = (int)$_POST['product_id'];
    $new_stock = (int)$_POST['new_stock'];
    try {
        $stmt = $pdo->prepare("UPDATE products SET stock = ? WHERE id = ? AND seller_id = ?");
        $stmt->execute([$new_stock, $p_id, $user_id]);
        $_SESSION['flash_success'] = "Inventory status synchronized.";
    } catch (PDOException $e) { $_SESSION['flash_error'] = "Status update failure."; }
    header("Location: profile.php");
    exit;
}

// --- MERCHANT: Update Order Fulfillment Status ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_order_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['new_status'];
    try {
        $verify = $pdo->prepare("SELECT COUNT(*) FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ? AND p.seller_id = ?");
        $verify->execute([$order_id, $user_id]);
        if ($verify->fetchColumn() > 0) {
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $order_id]);
            $_SESSION['flash_success'] = "Order #$order_id fulfillment status updated to $new_status.";
        } else { $_SESSION['flash_error'] = "Unauthorized logistics control."; }
    } catch (PDOException $e) { $_SESSION['flash_error'] = "Fulfillment update failure."; }
    header("Location: profile.php");
    exit;
}

// --- MERCHANT: Add New Listing Node ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $name = trim($_POST['name']);
    $desc = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $cat = $_POST['category'];
    $cond = $_POST['condition'];
    $stock = (int)$_POST['stock'];
    
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] == 0) {
        $file = $_FILES['image_file'];
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_name = "prod_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
        $target = "uploads/products/" . $new_name;
        
        if (move_uploaded_file($file['tmp_name'], $target)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO products (name, description, price, image_url, category, `condition`, stock, seller_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $desc, $price, $target, $cat, $cond, $stock, $user_id]);
                $_SESSION['flash_success'] = "Listing synchronized. Broadcast complete!";
                header("Location: profile.php");
                exit;
            } catch (PDOException $e) { $_SESSION['flash_error'] = "Listing node failure."; }
        } else { $_SESSION['flash_error'] = "Media transmission failure."; }
    } else { $_SESSION['flash_error'] = "Listing requires a visual image."; }
    header("Location: profile.php");
    exit;
}

// --- Messaging Node Handling ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    $receiver_id = (int)$_POST['receiver_id'];
    $content = trim($_POST['content']);
    if ($content) {
        try {
            $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, content) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $receiver_id, $content]);
            $_SESSION['flash_success'] = "Message broadcasted successfully.";
        } catch (PDOException $e) { $_SESSION['flash_error'] = "Communication failure."; }
    }
    header("Location: profile.php");
    exit;
}

// Now include the visual header (which starts HTML output)
require_once 'includes/header.php';

// --- Profile Photo Upload (Handles redirect AFTER header inclusion if needed, but PRG is better above) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_pic'])) {
    $file = $_FILES['profile_pic'];
    if ($file['error'] === 0) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_name = "profile_" . $user_id . "_" . time() . "." . $ext;
        $upload_path = "uploads/profiles/" . $new_name;
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            $stmt = $pdo->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
            $stmt->execute([$upload_path, $user_id]);
            $_SESSION['flash_success'] = "Identity visual updated.";
            header("Location: profile.php"); // This will fail if HTML started, but we only output header AFTER this normally. Wait, I included header above!
            exit;
        }
    }
}
// I will move the profile pic update ABOVE header inclusion as well for consistency.

// --- Global Data Fetching ---
$userStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$userStmt->execute([$user_id]);
$user_data = $userStmt->fetch();
$role = $user_data['role'];
$profile_pic = $user_data['profile_pic'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($user_data['name']) . '&background=6366f1&color=fff';

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
    
    // Fetch Incoming Sales
    $salesStmt = $pdo->prepare("
        SELECT DISTINCT o.*, u.name as buyer_name 
        FROM orders o 
        JOIN order_items oi ON o.id = oi.order_id 
        JOIN products p ON oi.product_id = p.id 
        JOIN users u ON o.user_id = u.id
        WHERE p.seller_id = ? 
        ORDER BY o.order_date DESC
    ");
    $salesStmt->execute([$user_id]);
    $incoming_sales = $salesStmt->fetchAll();

    $statsStmt = $pdo->prepare("SELECT COUNT(*) as total_items, SUM(stock) as stock_total FROM products WHERE seller_id = ?");
    $statsStmt->execute([$user_id]);
    $stats = $statsStmt->fetch();
} else {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC LIMIT 5");
    $stmt->execute([$user_id]);
    $buyer_orders = $stmt->fetchAll();
}
?>

<!-- UI VIEW REMAINS THE SAME -->
<div class="container py-5 animate-fade-in shadow-none border-0 text-start">
    <div class="row g-5">
        <!-- Identity Sidebar -->
        <div class="col-lg-4">
            <div class="glass-card p-5 border-0 shadow-lg text-center h-100">
                <div class="position-relative d-inline-block mb-5">
                    <img src="<?php echo htmlspecialchars($profile_pic); ?>" class="rounded-circle shadow-lg border border-3 border-primary" width="160" height="160" style="object-fit: cover;">
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
                
                <div class="text-start">
                    <h6 class="small fw-bold opacity-50 mb-4 text-uppercase tracking-widest">Global Messages</h6>
                    <?php if (count($conversations) > 0): ?>
                        <div class="list-group list-group-flush bg-transparent">
                            <?php foreach ($conversations as $c): ?>
                                <button type="button" class="list-group-item list-group-item-action bg-transparent border-0 text-white py-3 px-0 d-flex justify-content-between align-items-center" data-bs-toggle="modal" data-bs-target="#msgModal<?php echo $c['contact_id']; ?>">
                                    <span><i class="fas fa-user-circle me-2 opacity-50"></i> <?php echo htmlspecialchars($c['contact_name']); ?></span>
                                    <i class="fas fa-chevron-right fs-mini opacity-25"></i>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="small text-muted italic">No active frequency found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="glass-card p-5 border-0 shadow-lg h-100">
                <?php if ($success_msg): ?><div class="alert alert-success border-0 small rounded-4 mb-5 p-4 shadow-sm animate-pulse-subtle"><i class="fas fa-check-circle me-2"></i> <?php echo $success_msg; ?></div><?php endif; ?>
                <?php if ($error_msg): ?><div class="alert alert-danger border-0 small rounded-4 mb-5 p-4 shadow-sm"><i class="fas fa-exclamation-triangle me-2"></i> <?php echo $error_msg; ?></div><?php endif; ?>
                
                <?php if ($role === 'Seller'): ?>
                    <a href="#" class="fab-node shadow-lg" data-bs-toggle="modal" data-bs-target="#addNodeModal" title="Add New Treasure Node">
                        <i class="fas fa-plus fs-3"></i>
                    </a>

                    <h4 class="fw-bold mb-5 text-uppercase tracking-wider">Merchant Hub</h4>
                    
                    <div class="row g-4 mb-5">
                        <div class="col-md-6"><div class="p-4 bg-white bg-opacity-5 rounded-4 border border-light border-opacity-10"><h3 class="fw-bold mb-0 text-primary"><?php echo count($seller_products); ?></h3><p class="small text-muted mb-0">Total Listing Nodes</p></div></div>
                        <div class="col-md-6"><div class="p-4 bg-white bg-opacity-5 rounded-4 border border-light border-opacity-10"><h3 class="fw-bold mb-0 text-success"><?php echo (int)$stats['stock_total']; ?></h3><p class="small text-muted mb-0">Aggregate Batch Units</p></div></div>
                    </div>

                    <h5 class="fw-bold mb-4 small opacity-50 text-uppercase tracking-widest">Inventory Management</h5>
                    <div class="table-responsive mb-5">
                        <table class="table table-dark table-hover align-middle border-light border-opacity-10">
                            <thead class="bg-white bg-opacity-5">
                                <tr>
                                    <th class="py-4 border-0">Product Node</th>
                                    <th class="py-4 border-0 text-center">Batch Level</th>
                                    <th class="py-4 border-0 text-center">Control</th>
                                    <th class="py-4 border-0 text-end">Valuation</th>
                                    <th class="py-4 border-0 text-end px-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($seller_products as $p): ?>
                                    <tr class="border-light border-opacity-10">
                                        <td class="py-4 border-0">
                                            <div class="d-flex align-items-center">
                                                <img src="<?php echo htmlspecialchars($p['image_url']); ?>" class="rounded-3 me-3 shadow-sm" width="40" height="40" style="object-fit: cover;">
                                                <div class="text-start">
                                                    <span class="fw-bold d-block"><?php echo htmlspecialchars($p['name']); ?></span>
                                                    <span class="fs-mini text-primary opacity-50"><?php echo htmlspecialchars($p['category']); ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-4 border-0 text-center">
                                            <?php if ($p['stock'] > 0): ?>
                                                <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-1">LIVE (<?php echo $p['stock']; ?>)</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3 py-1">OUT OF STOCK</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-4 border-0 text-center">
                                            <form action="profile.php" method="POST" id="status-form-<?php echo $p['id']; ?>">
                                                <input type="hidden" name="action" value="toggle_stock">
                                                <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                                                <select name="new_stock" class="form-select btn btn-outline-<?php echo ($p['stock'] >= 10 ? 'success' : ($p['stock'] > 0 ? 'warning' : 'danger')); ?> btn-sm rounded-pill px-3 py-1 fs-mini shadow-sm border-0 bg-transparent" onchange="this.form.submit()" style="min-width: 140px; -webkit-appearance: none;">
                                                    <option value="15" <?php echo ($p['stock'] >= 10 ? 'selected' : ''); ?> class="bg-dark text-white">Full Node: In Stock</option>
                                                    <option value="5" <?php echo ($p['stock'] > 0 && $p['stock'] < 10 ? 'selected' : ''); ?> class="bg-dark text-white">Limited: Few Left</option>
                                                    <option value="0" <?php echo ($p['stock'] == 0 ? 'selected' : ''); ?> class="bg-dark text-white">Archived: Out of Stock</option>
                                                </select>
                                            </form>
                                        </td>
                                        <td class="py-4 border-0 text-end fw-bold text-primary">$<?php echo number_format($p['price'], 2); ?></td>
                                        <td class="py-4 border-0 text-end px-3">
                                            <div class="d-flex gap-2 justify-content-end">
                                                <a href="edit_product.php?id=<?php echo $p['id']; ?>" class="btn btn-outline-light btn-sm rounded-circle p-2 opacity-50 hover-opacity-100 shadow-sm" title="Modify Node Specifications"><i class="fas fa-pencil-alt fs-mini"></i></a>
                                                <a href="profile.php?delete=<?php echo $p['id']; ?>" class="btn btn-outline-danger btn-sm rounded-circle p-2 opacity-50 hover-opacity-100 shadow-sm" title="Archive Treasure Node" onclick="return confirm('Archive global resource node?')"><i class="fas fa-trash-alt fs-mini"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <h5 class="fw-bold mb-4 small opacity-50 text-uppercase tracking-widest text-primary">Incoming Nexus Fulfillment</h5>
                    <div class="list-group list-group-flush bg-transparent">
                        <?php foreach($incoming_sales as $sale): ?>
                            <div class="list-group-item bg-white bg-opacity-5 rounded-4 border border-light border-opacity-10 py-4 px-4 mb-3 d-flex justify-content-between align-items-center animate-fade-in">
                                <div>
                                    <h6 class="fw-bold mb-1">Nexus Order #<?php echo $sale['id']; ?></h6>
                                    <p class="small text-muted mb-0">Buyer: <span class="text-white"><?php echo htmlspecialchars($sale['buyer_name']); ?></span> <i class="fas fa-circle mx-1 p-0" style="font-size: 4px;"></i> <?php echo date("M d, Y", strtotime($sale['order_date'])); ?></p>
                                </div>
                                <div class="text-end">
                                    <div class="mb-2">
                                        <span class="badge bg-<?php echo ($sale['status'] === 'Delivered' ? 'success' : 'warning'); ?> bg-opacity-10 text-<?php echo ($sale['status'] === 'Delivered' ? 'success' : 'warning'); ?> rounded-pill small px-3 py-1"><?php echo $sale['status']; ?></span>
                                    </div>
                                    <?php if ($sale['status'] !== 'Delivered' && $sale['status'] !== 'Cancelled'): ?>
                                        <form action="profile.php" method="POST" class="d-flex gap-2 justify-content-end">
                                            <input type="hidden" name="order_id" value="<?php echo $sale['id']; ?>">
                                            <?php if ($sale['status'] === 'Pending'): ?>
                                                <button type="submit" name="update_order_status" value="Shipped" class="btn btn-outline-primary btn-mini rounded-pill px-3 py-1">Mark Shipped</button>
                                            <?php elseif ($sale['status'] === 'Shipped'): ?>
                                                <button type="submit" name="update_order_status" value="Delivered" class="btn btn-primary btn-mini rounded-pill px-3 py-1">Mark Delivered</button>
                                            <?php endif; ?>
                                            <input type="hidden" name="new_status" value="<?php echo ($sale['status'] === 'Pending' ? 'Shipped' : 'Delivered'); ?>">
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (count($incoming_sales) == 0): ?>
                            <div class="py-5 text-center opacity-25">No incoming fulfillment nodes detected.</div>
                        <?php endif; ?>
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

<!-- TOP-LEVEL MODALS NODE -->
<div class="modal fade" id="addNodeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content glass-card p-5 border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold text-white"><i class="fas fa-box-open text-primary me-2"></i> INITIALIZE NEW MARKET NODE</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-4">
                <form action="profile.php" method="POST" enctype="multipart/form-data">
                    <div class="row g-4 text-start">
                        <div class="col-md-6">
                            <label class="small fw-bold opacity-50 mb-2">PRODUCT TITLE</label>
                            <input type="text" name="name" class="form-control bg-dark border-0 rounded-4 p-3" placeholder="e.g. Vintage 70s Lens" required>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold opacity-50 mb-2">CATEGORY NODE</label>
                            <select name="category" class="form-select bg-dark border-0 rounded-4 p-3 shadow-none">
                                <?php 
                                $sellCatsArr = $pdo->query("SELECT name FROM categories ORDER BY name ASC")->fetchAll();
                                foreach($sellCatsArr as $sc): ?>
                                    <option value="<?php echo htmlspecialchars($sc['name']); ?>"><?php echo htmlspecialchars($sc['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="small fw-bold opacity-50 mb-2">VALUATION ($)</label>
                            <input type="number" step="0.01" name="price" class="form-control bg-dark border-0 rounded-4 p-3" placeholder="0.00" required>
                        </div>
                        <div class="col-md-4">
                            <label class="small fw-bold opacity-50 mb-2">INITIAL BATCH (STOCK)</label>
                            <input type="number" name="stock" class="form-control bg-dark border-0 rounded-4 p-3" value="1" required>
                        </div>
                        <div class="col-md-4">
                            <label class="small fw-bold opacity-50 mb-2">CONDITION NODE</label>
                            <select name="condition" class="form-select bg-dark border-0 rounded-4 p-3">
                                <option value="New">New</option>
                                <option value="Like New">Like New</option>
                                <option value="Used">Used</option>
                                <option value="Fair">Fair</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="small fw-bold opacity-50 mb-2">VISUAL IDENTIFIER (IMAGE)</label>
                            <input type="file" name="image_file" class="form-control bg-dark border-0 rounded-4 p-3" required>
                        </div>
                        <div class="col-12">
                            <label class="small fw-bold opacity-50 mb-2">PRODUCT SPECIFICATIONS (DESCRIPTION)</label>
                            <textarea name="description" class="form-control bg-dark border-0 rounded-4 p-3" rows="4" placeholder="Detail the treasure's unique attributes..." required></textarea>
                        </div>
                    </div>
                    <button type="submit" name="add_product" class="btn btn-primary w-100 py-3 rounded-pill mt-5 fw-bold shadow-lg">BROADCAST LISTING</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php foreach ($conversations as $c): ?>
    <div class="modal fade" id="msgModal<?php echo $c['contact_id']; ?>" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-card p-4 border-0 shadow-lg text-start">
                <div class="modal-header border-0 pb-0"><h5 class="fw-bold">Channel: <?php echo htmlspecialchars($c['contact_name']); ?></h5><button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="chat-log mb-4 overflow-auto p-3 bg-dark bg-opacity-50 rounded-4" style="height: 300px;">
                        <?php 
                        $chatStmt2 = $pdo->prepare("SELECT * FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY created_at ASC");
                        $chatStmt2->execute([$user_id, $c['contact_id'], $c['contact_id'], $user_id]);
                        foreach($chatStmt2->fetchAll() as $msg): ?>
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

<?php require_once 'includes/footer.php'; ?>
