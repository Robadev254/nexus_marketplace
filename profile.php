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

// --- Profile Photo Upload Handling ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_pic'])) {
    $file = $_FILES['profile_pic'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 20 * 1024 * 1024; // 20MB

    if ($file['error'] === 0) {
        if ($file['size'] > $max_size) {
            $error_msg = "Package too large. Maximal capacity is 20MB.";
        } elseif (!in_array($file['type'], $allowed_types)) {
            $error_msg = "Unsupported data format. Please use JPEG, PNG, or WebP.";
        } else {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_name = "profile_" . $user_id . "_" . time() . "." . $ext;
            $upload_path = "uploads/profiles/" . $new_name;

            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                $stmt = $pdo->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
                $stmt->execute([$upload_path, $user_id]);
                $success_msg = "Biometric visual updated successfully.";
            } else {
                $error_msg = "Transmission failed. Could not stabilize file on server.";
            }
        }
    }
}

// Fetch User & Role-specific data
try {
    $userStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $userStmt->execute([$user_id]);
    $user_data = $userStmt->fetch();
    $role = (isset($user_data['role'])) ? $user_data['role'] : 'Buyer';
    $profile_pic = (isset($user_data['profile_pic']) && $user_data['profile_pic']) ? $user_data['profile_pic'] : 'assets/img/default_user.png';

    if ($role === 'Seller') {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE seller_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user_id]);
        $seller_products = $stmt->fetchAll();
        $sales_stmt = $pdo->prepare("SELECT COUNT(oi.id) as total_sales, SUM(oi.price * oi.quantity) as total_revenue FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE p.seller_id = ?");
        $sales_stmt->execute([$user_id]);
        $stats = $sales_stmt->fetch();
    } else {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC LIMIT 3");
        $stmt->execute([$user_id]);
        $buyer_orders = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    die("Profile node error: " . $e->getMessage());
}
?>

<div class="container py-5 animate-fade-in text-start">
    <?php if ($success_msg): ?>
        <div class="alert alert-success border-0 rounded-4 mb-5 shadow-sm"><?php echo $success_msg; ?></div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="alert alert-danger border-0 rounded-4 mb-5 shadow-sm"><?php echo $error_msg; ?></div>
    <?php endif; ?>

    <div class="row mb-5 align-items-center">
        <div class="col-md-2">
            <div class="position-relative profile-upload-preview group">
                <form action="profile.php" method="POST" enctype="multipart/form-data" id="photo-form">
                    <label for="profile_pic_input" class="d-block cursor-pointer position-relative overflow-hidden rounded-circle border border-primary border-opacity-25 shadow-lg" style="width: 150px; height: 150px;">
                        <img src="<?php echo htmlspecialchars($profile_pic); ?>" 
                             class="profile-img-main w-100 h-100" 
                             style="object-fit: cover;">
                        <div class="profile-overlay position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-dark bg-opacity-50 opacity-0 transition-all">
                            <i class="fas fa-camera text-white fs-4"></i>
                        </div>
                    </label>
                    <input type="file" name="profile_pic" id="profile_pic_input" class="d-none" onchange="document.getElementById('photo-form').submit()">
                </form>
            </div>
        </div>
        <div class="col-md-10 py-3">
            <h1 class="fw-bold mb-1 text-white"><?php echo htmlspecialchars($user_data['name']); ?></h1>
            <p class="text-muted fs-5 mb-0"><span class="badge bg-primary rounded-pill px-3 py-2 fs-6 fw-normal"><?php echo $role; ?> Account</span> · Member since April 2026</p>
        </div>
    </div>

    <!-- Rest of the profile logic remains similar -->
    <?php if ($role === 'Seller'): ?>
        <div class="row g-4 mb-5">
            <div class="col-md-6">
                <div class="glass-card p-4 border-0 h-100 shadow-lg">
                    <h5 class="text-muted small fw-bold text-uppercase mb-4">Store Revenue (Simulated)</h5>
                    <h2 class="fw-bold text-secondary mb-0">$<?php echo number_format($stats['total_revenue'] ?? 0, 2); ?></h2>
                    <p class="text-muted small mt-2"><i class="fas fa-arrow-up text-success me-1"></i> 12% increase from last cycle</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="glass-card p-4 border-0 h-100 shadow-lg">
                    <h5 class="text-muted small fw-bold text-uppercase mb-4">Total Inventory Sales</h5>
                    <h2 class="fw-bold text-white mb-0"><?php echo $stats['total_sales'] ?? 0; ?> Units</h2>
                    <p class="text-muted small mt-2">Active buyers across your listings.</p>
                </div>
            </div>
        </div>
        <div class="glass-card p-5 border-0 shadow-lg">
            <div class="d-flex justify-content-between align-items-center mb-5">
                <h4 class="fw-bold text-white mb-0">Merchant Inventory</h4>
                <a href="admin/manage_products.php" class="btn btn-outline-primary rounded-pill px-4">Manage Listings</a>
            </div>
            <div class="table-responsive rounded-4 overflow-hidden">
                <table class="table table-dark table-hover border-light border-opacity-10">
                    <thead class="bg-white bg-opacity-5">
                        <tr>
                            <th class="py-4 px-4 border-0">Product Node</th>
                            <th class="py-4 border-0 text-center">Batch (Stock)</th>
                            <th class="py-4 border-0 text-center">Status</th>
                            <th class="py-4 border-0 text-end px-4">Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($seller_products as $p): ?>
                            <tr class="border-light border-opacity-10">
                                <td class="py-4 px-4 border-0">
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo htmlspecialchars($p['image_url']); ?>" class="rounded-3 me-3" width="50" height="50" style="object-fit: cover;">
                                        <span class="fw-bold text-white"><?php echo htmlspecialchars($p['name']); ?></span>
                                    </div>
                                </td>
                                <td class="py-4 border-0 text-center fw-bold text-white"><?php echo $p['stock']; ?></td>
                                <td class="py-4 border-0 text-center">
                                    <?php if ($p['stock'] > 0): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-2 border border-success border-opacity-25">IN STOCK</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3 py-2 border border-danger border-opacity-25">OUT OF STOCK</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 border-0 text-end px-4 fw-bold text-primary">$<?php echo number_format($p['price'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-5">
            <div class="col-lg-7">
                <div class="glass-card p-5 border-0 shadow-lg">
                    <h4 class="fw-bold text-white mb-5">Recent Acquisitions</h4>
                    <?php if (count($buyer_orders) > 0): ?>
                        <?php foreach ($buyer_orders as $order): ?>
                            <div class="d-flex align-items-center mb-4 p-4 rounded-4 bg-white bg-opacity-5 border border-light border-opacity-10">
                                <i class="fas fa-box bg-primary bg-opacity-25 p-3 rounded-4 me-4 text-primary"></i>
                                <div class="flex-grow-1">
                                    <h6 class="fw-bold text-white mb-1">Order #NX-ORD-<?php echo $order['id']; ?></h6>
                                    <p class="text-muted small mb-0"><?php echo date("M d, Y", strtotime($order['order_date'])); ?> · $<?php echo number_format($order['total_price'], 2); ?></p>
                                </div>
                                <span class="badge rounded-pill <?php echo ($order['status'] == 'Pending') ? 'bg-warning text-dark' : 'bg-success'; ?> px-3 py-2"><?php echo strtoupper($order['status']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-4 text-muted opacity-50">No purchases recorded.</div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="glass-card p-5 border-0 shadow-lg h-100">
                    <h4 class="fw-bold text-white mb-5">Messenger <span class="badge bg-danger ms-2 rounded-pill fs-mini">New</span></h4>
                    <div class="opacity-25 text-center mt-5">
                        <i class="fas fa-comment-slash fs-1 mb-3"></i>
                        <p class="small">No active transmissions.</p>
                    </div>
                    <a href="contact.php" class="btn btn-primary w-100 rounded-pill py-3 mt-5">Start New Conversation</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
    .profile-upload-preview label:hover .profile-overlay {
        opacity: 1 !important;
    }
    .transition-all {
        transition: all 0.3s ease;
    }
    .profile-img-main {
        transition: transform 0.5s ease;
    }
    .profile-upload-preview label:hover .profile-img-main {
        transform: scale(1.1);
    }
</style>

<?php require_once 'includes/footer.php'; ?>
