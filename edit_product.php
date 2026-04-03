<?php
// edit_product.php
require_once 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];
$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
$error = '';
$success = '';

// Fetch existing product data
try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        die("Nexus Node Error: Listing #$id does not exist in our global index.");
    }
    
    // PERMISSION CHECK: Admin or the Owner!
    if (!$is_admin && $product['seller_id'] != $user_id) {
        die("Security Breach: Unauthorized access to listing node #$id. Credentials non-conforming.");
    }
} catch (PDOException $e) {
    die("Database access error.");
}

// Update Product Node
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_product'])) {
    $name = trim($_POST['name']);
    $desc = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $cat = $_POST['category'];
    $cond = $_POST['condition'];
    $stock = (int)$_POST['stock'];
    $img_url = $product['image_url'];

    // Handle Image Replacement
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] == 0) {
        $file = $_FILES['image_file'];
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_name = "prod_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
        $target = "uploads/products/" . $new_name;
        
        if (move_uploaded_file($file['tmp_name'], $target)) {
            $img_url = "uploads/products/" . $new_name;
        }
    }

    try {
        $update = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, image_url = ?, category = ?, `condition` = ?, stock = ? WHERE id = ?");
        $update->execute([$name, $desc, $price, $img_url, $cat, $cond, $stock, $id]);
        $success = "Synchronization Complete: Node properties for listing #$id updated successfully.";
        // Refresh product data
        $stmt->execute([$id]);
        $product = $stmt->fetch();
    } catch (PDOException $e) {
        $error = "Synchronization Error: Could not broadcast node changes.";
    }
}
?>

<div class="container py-5 animate-fade-in shadow-none border-0 text-start">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="d-flex justify-content-between align-items-center mb-5">
                <div>
                    <h1 class="fw-bold mb-0">Modify Listing</h1>
                    <p class="text-muted small">Update live merchant properties for node #<?php echo $id; ?>.</p>
                </div>
                <a href="<?php echo ($is_admin ? 'admin/manage_products.php' : 'profile.php'); ?>" class="btn btn-outline-light rounded-pill px-4">Back to Dashboard</a>
            </div>

            <div class="glass-card p-5 border-0 shadow-lg">
                <?php if ($success): ?><div class="alert alert-success border-0 small rounded-4 mb-5 p-3 text-center shadow-sm"><?php echo $success; ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger border-0 small rounded-4 mb-5 p-3 text-center shadow-sm"><?php echo $error; ?></div><?php endif; ?>

                <form action="edit_product.php?id=<?php echo $id; ?>" method="POST" enctype="multipart/form-data">
                    <div class="row g-4 mb-5">
                        <div class="col-md-6 mb-3">
                            <label class="small fw-bold text-primary opacity-50 mb-2">PRODUCT IDENTITY (NAME)</label>
                            <input type="text" name="name" class="form-control bg-dark border-0 rounded-4 p-3" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small fw-bold text-primary opacity-50 mb-2">LIVE VALUATION (PRICE $)</label>
                            <input type="number" step="0.01" name="price" class="form-control bg-dark border-0 rounded-4 p-3" value="<?php echo $product['price']; ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small fw-bold text-primary opacity-50 mb-2">BATCH QUANTITY (STOCK)</label>
                            <input type="number" name="stock" class="form-control bg-dark border-0 rounded-4 p-3" value="<?php echo $product['stock']; ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small fw-bold text-primary opacity-50 mb-2">CATEGORY NODE</label>
                            <select name="category" class="form-select bg-dark border-0 rounded-4 p-3 shadow-none">
                                <?php 
                                $editCats = $pdo->query("SELECT name FROM categories ORDER BY name ASC")->fetchAll();
                                foreach($editCats as $ec): ?>
                                    <option value="<?php echo htmlspecialchars($ec['name']); ?>" <?php echo ($product['category'] == $ec['name'] ? 'selected' : ''); ?>><?php echo htmlspecialchars($ec['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small fw-bold text-primary opacity-50 mb-2">CONDITION CLASSIFICATION</label>
                            <select name="condition" class="form-select bg-dark border-0 rounded-4 p-3">
                                <option value="New" <?php echo ($product['condition'] == 'New' ? 'selected' : ''); ?>>New</option>
                                <option value="Like New" <?php echo ($product['condition'] == 'Like New' ? 'selected' : ''); ?>>Like New</option>
                                <option value="Used" <?php echo ($product['condition'] == 'Used' ? 'selected' : ''); ?>>Used</option>
                                <option value="Fair" <?php echo ($product['condition'] == 'Fair' ? 'selected' : ''); ?>>Fair</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small fw-bold text-primary opacity-50 mb-2">UPDATE BIOMETRIC VISUAL</label>
                            <input type="file" name="image_file" class="form-control bg-dark border-0 rounded-4 p-3">
                            <span class="fs-mini opacity-25 italic mt-2 d-block">Current: <?php echo basename($product['image_url']); ?></span>
                        </div>
                        <div class="col-12 mb-4">
                            <label class="small fw-bold text-primary opacity-50 mb-2">PRODUCT SPECIFICATIONS (DETAILS)</label>
                            <textarea name="description" class="form-control bg-dark border-0 rounded-4 p-3" rows="5" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                        </div>
                    </div>

                    <button type="submit" name="update_product" class="btn btn-primary w-100 py-3 rounded-pill fw-bold text-uppercase tracking-wide shadow-lg">Commit Synchronized Updates</button>
                    <p class="text-center text-muted small mt-4 opacity-50">Authorized changes will instantly reflect in the Nexus Discovery hub.</p>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
