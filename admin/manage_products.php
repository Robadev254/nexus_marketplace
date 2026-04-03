<?php
// admin/manage_products.php
session_start();
require_once '../includes/db.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

$error = '';
$success = '';

// Inventory Actions
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$del_id]);
        $success = "Merchant Listing #$del_id archived successfully.";
    } catch (PDOException $e) { $error = "Archiving error: " . $e->getMessage(); }
}

// Bulk Stock Update Action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_stock'])) {
    $p_id = (int)$_POST['product_id'];
    $new_stock = (int)$_POST['quantity'];
    try {
        $stmt = $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?");
        $stmt->execute([$new_stock, $p_id]);
        $success = "Stock levels for Node #$p_id have been updated.";
    } catch (PDOException $e) { $error = "Stock update failure."; }
}

// Add New Listing with File Upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $price = $_POST['price'];
    $cat = $_POST['category'];
    $cond = $_POST['condition'];
    $stock = $_POST['stock'];
    $seller = $_SESSION['user_id'];

    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] == 0) {
        $file = $_FILES['image_file'];
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_name = "prod_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
        $target = "../uploads/products/" . $new_name;
        $db_path = "uploads/products/" . $new_name;

        if (move_uploaded_file($file['tmp_name'], $target)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO products (name, description, price, image_url, category, `condition`, stock, seller_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $desc, $price, $db_path, $cat, $cond, $stock, $seller]);
                $last_id = $pdo->lastInsertId();
                $success = "Broadcast Complete: New listing synchronized on Nexus Market! <a href='../product_details.php?id=$last_id' class='fw-bold text-white text-decoration-underline ms-2'>View Active Node</a>";
            } catch (PDOException $e) { $error = "Listing node failure: " . $e->getMessage(); }
        } else { $error = "Media transmission failure."; }
    } else { $error = "Product image required."; }
}

// Delete Product Node
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
        $success = "Product node archived from global marketplace.";
    } catch (PDOException $e) { $error = "Archival failure: Product may be linked to active orders."; }
}

// Toggle Stock Synchronicity
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_stock'])) {
    $id = (int)$_POST['product_id'];
    $new_stock = (int)$_POST['new_stock'];
    try {
        $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?")->execute([$new_stock, $id]);
        $success = "Inventory batch synchronized.";
    } catch (PDOException $e) { $error = "Batch update failed."; }
}

$products = $pdo->query("SELECT * FROM products ORDER BY created_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Merchant Console | Nexus Market</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .low-stock-alert { color: #ffc107; font-weight: bold; font-size: 0.75rem; }
        .out-stock-alert { color: #ef4444; font-weight: bold; font-size: 0.75rem; }
    </style>
</head>
<body class="bg-dark text-white">

<div class="container py-5 animate-fade-in shadow-none border-0">
    <div class="row mb-5 align-items-center">
        <div class="col-6 text-start">
            <h1 class="fw-bold mb-0">Merchant Console</h1>
            <p class="text-muted small">Global inventory & logistics management.</p>
        </div>
        <div class="col-6 text-end">
            <a href="dashboard.php" class="btn btn-outline-light rounded-pill px-4 me-2">Back to Panel</a>
            <a href="../logout.php" class="text-danger"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </div>

    <div class="row g-5">
        <!-- Listing Form -->
        <div class="col-lg-4">
            <div class="glass-card p-5 border-0 shadow-lg">
                <h5 class="fw-bold text-white mb-4"><i class="fas fa-plus-circle text-primary me-2"></i> NEW LISTING</h5>
                
                <?php if ($success): ?><div class="alert alert-success border-0 small rounded-4 mb-4"><?php echo $success; ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger border-0 small rounded-4 mb-4"><?php echo $error; ?></div><?php endif; ?>

                <form action="manage_products.php" method="POST" enctype="multipart/form-data">
                    <div class="mb-3 text-start"><label class="small fw-bold opacity-50">PRODUCT TITLE</label><input type="text" name="name" class="form-control" required></div>
                    <div class="mb-3 text-start"><label class="small fw-bold opacity-50">UNIT PRICE ($)</label><input type="number" step="0.01" name="price" class="form-control" required></div>
                    <div class="mb-3 text-start"><label class="small fw-bold opacity-50">INITIAL BATCH (STOCK)</label><input type="number" name="stock" class="form-control" required value="20"></div>
                    <div class="mb-4">
                        <label class="small fw-bold opacity-50">NODE CLASSIFICATION (CATEGORY)</label>
                        <select name="category" class="form-select border-0 bg-white bg-opacity-10 text-white rounded-4 py-3 shadow-none p-3 small" style="background-image: none !important;">
                            <?php 
                            $formCats = $pdo->query("SELECT name FROM categories ORDER BY name ASC")->fetchAll();
                            foreach($formCats as $fc): ?>
                                <option value="<?php echo htmlspecialchars($fc['name']); ?>" class="bg-dark"><?php echo htmlspecialchars($fc['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3 text-start">
                        <label class="small fw-bold opacity-50">CONDITION</label>
                        <select name="condition" class="form-control bg-dark text-white" required>
                            <option value="New">New</option>
                            <option value="Like New">Like New</option>
                            <option value="Used">Used</option>
                        </select>
                    </div>
                    <div class="mb-3 text-start">
                        <label class="small fw-bold opacity-50">REPRESENTATIVE IMAGE</label>
                        <input type="file" name="image_file" class="form-control" required>
                    </div>
                    <div class="mb-4 text-start"><label class="small fw-bold opacity-50">PRODUCT SPECIFICATIONS</label><textarea name="description" class="form-control" rows="3" required></textarea></div>
                    <button type="submit" name="add_product" class="btn btn-primary w-100 rounded-pill py-3 fw-bold">BROADCAST LISTING</button>
                </form>
            </div>
        </div>

        <!-- Inventory Management -->
        <div class="col-lg-8">
            <div class="glass-card p-5 border-0 shadow-lg">
                <h5 class="fw-bold text-white mb-5">LIVE INVENTORY NODES (<?php echo count($products); ?>)</h5>
                
                <div class="table-responsive">
                    <table class="table table-dark table-hover border-all border-light border-opacity-10 align-middle">
                        <thead class="bg-white bg-opacity-5">
                            <tr>
                                <th class="py-4 border-0">Global Listing</th>
                                <th class="py-4 border-0 text-center">Batch Status</th>
                                <th class="py-4 border-0 text-center">In-Stock Sync</th>
                                <th class="py-4 border-0 text-end">Action Node</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($products as $p): ?>
                                <tr class="border-light border-opacity-10">
                                    <td class="py-4 border-0">
                                        <div class="d-flex align-items-center">
                                            <img src="../<?php echo htmlspecialchars($p['image_url']); ?>" class="rounded-3 me-3 shadow-md" width="45" height="45" style="object-fit: cover;">
                                            <div>
                                                <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($p['name']); ?></h6>
                                                <span class="small text-primary opacity-75">$<?php echo number_format($p['price'], 2); ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 border-0 text-center">
                                        <?php if ($p['stock'] > 0): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-1">SYNCHRONIZED (<?php echo $p['stock']; ?>)</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3 py-1">OUT OF STOCK</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 border-0 text-center">
                                        <a href="../edit_product.php?id=<?php echo $p['id']; ?>" class="btn btn-outline-light btn-sm rounded-pill px-4">Edit Details</a>
                                        <form action="manage_products.php" method="POST" class="d-inline">
                                            <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                                            <?php if ($p['stock'] > 0): ?>
                                                <input type="hidden" name="new_stock" value="0">
                                                <button type="submit" name="toggle_stock" class="btn btn-outline-danger btn-sm rounded-pill px-3 py-1 scale-hover">Mark Out-Stock</button>
                                            <?php else: ?>
                                                <input type="hidden" name="new_stock" value="20">
                                                <button type="submit" name="toggle_stock" class="btn btn-outline-success btn-sm rounded-pill px-3 py-1 scale-hover">Mark In-Stock</button>
                                            <?php endif; ?>
                                        </form>
                                    </td>
                                    <td class="py-4 border-0 text-end px-3">
                                        <div class="d-flex align-items-center justify-content-end gap-3">
                                            <a href="edit_product.php?id=<?php echo $p['id']; ?>" class="text-primary opacity-50 hover-opacity-100" title="Edit Listing Properties"><i class="fas fa-edit"></i></a>
                                            <a href="manage_products.php?delete=<?php echo $p['id']; ?>" class="text-danger opacity-50 hover-opacity-100" onclick="return confirm('Retract listing permanently?')" title="Delete Listing Permanently"><i class="fas fa-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
