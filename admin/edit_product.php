<?php
// admin/edit_product.php
session_start();
require_once '../includes/db.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
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
} catch (PDOException $e) {
    die("Database access error.");
}

// Update Product
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_product'])) {
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $price = $_POST['price'];
    $cat = $_POST['category'];
    $cond = $_POST['condition'];
    $stock = $_POST['stock'];
    $img_url = $product['image_url']; // Default to current

    // Handle Image Replacement
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] == 0) {
        $file = $_FILES['image_file'];
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_name = "prod_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
        $target = "../uploads/products/" . $new_name;
        
        if (move_uploaded_file($file['tmp_name'], $target)) {
            $img_url = "uploads/products/" . $new_name;
        }
    }

    try {
        $update = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, image_url = ?, category = ?, `condition` = ?, stock = ? WHERE id = ?");
        $update->execute([$name, $desc, $price, $img_url, $cat, $cond, $stock, $id]);
        $success = "Merchant Update Successful: Listing #$id has been synchronized with the live marketplace.";
        // Refresh product data
        $stmt->execute([$id]);
        $product = $stmt->fetch();
    } catch (PDOException $e) {
        $error = "Synchronization Error: Could not update listing.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Node #<?php echo $id; ?> | Nexus Market</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-dark text-white">

<div class="container py-5 animate-fade-in shadow-none border-0">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="d-flex justify-content-between align-items-center mb-5">
                <div>
                    <h1 class="fw-bold mb-0">Modify Listing</h1>
                    <p class="text-muted small">Update live merchant properties for node #<?php echo $id; ?>.</p>
                </div>
                <a href="manage_products.php" class="btn btn-outline-light rounded-pill px-4">Back to Inventory</a>
            </div>

            <div class="glass-card p-5 border-0 shadow-lg">
                <?php if ($success): ?><div class="alert alert-success border-0 small rounded-4 mb-5 p-3 text-center"><?php echo $success; ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger border-0 small rounded-4 mb-5 p-3 text-center"><?php echo $error; ?></div><?php endif; ?>

                <form action="edit_product.php?id=<?php echo $id; ?>" method="POST" enctype="multipart/form-data">
                    <div class="row g-4 mb-5">
                        <div class="col-md-6 mb-3 text-start">
                            <label class="small fw-bold text-primary opacity-75">PRODUCT IDENTITY (NAME)</label>
                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3 text-start">
                            <label class="small fw-bold text-primary opacity-75">LIVE VALUATION (PRICE $)</label>
                            <input type="number" step="0.01" name="price" class="form-control" value="<?php echo $product['price']; ?>" required>
                        </div>
                        <div class="col-md-6 mb-3 text-start">
                            <label class="small fw-bold text-primary opacity-75">BATCH QUANTITY (STOCK)</label>
                            <input type="number" name="stock" class="form-control" value="<?php echo $product['stock']; ?>" required>
                        </div>
                        <div class="col-md-6 mb-3 text-start">
                            <label class="small fw-bold text-primary opacity-75">CATEGORY NODE</label>
                            <select name="category" class="form-control bg-dark text-white" required>
                                <option value="Electronics" <?php echo ($product['category'] == 'Electronics' ? 'selected' : ''); ?>>Electronics</option>
                                <option value="Clothing" <?php echo ($product['category'] == 'Clothing' ? 'selected' : ''); ?>>Clothing</option>
                                <option value="Books" <?php echo ($product['category'] == 'Books' ? 'selected' : ''); ?>>Books</option>
                                <option value="Collectibles" <?php echo ($product['category'] == 'Collectibles' ? 'selected' : ''); ?>>Collectibles</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3 text-start">
                            <label class="small fw-bold text-primary opacity-75">CONDITION RATING</label>
                            <select name="condition" class="form-control bg-dark text-white" required>
                                <option value="New" <?php echo ($product['condition'] == 'New' ? 'selected' : ''); ?>>New</option>
                                <option value="Like New" <?php echo ($product['condition'] == 'Like New' ? 'selected' : ''); ?>>Like New</option>
                                <option value="Used" <?php echo ($product['condition'] == 'Used' ? 'selected' : ''); ?>>Used</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3 text-start">
                            <label class="small fw-bold text-primary opacity-75">UPDATE BIOMETRIC VISUAL</label>
                            <input type="file" name="image_file" class="form-control">
                            <span class="fs-mini opacity-25 italic mt-2 d-block">Current: <?php echo basename($product['image_url']); ?></span>
                        </div>
                        <div class="col-12 mb-4 text-start">
                            <label class="small fw-bold text-primary opacity-75">PRODUCT SPECIFICATIONS (DETAILS)</label>
                            <textarea name="description" class="form-control" rows="5" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                        </div>
                    </div>

                    <button type="submit" name="update_product" class="btn btn-primary w-100 py-3 rounded-pill fw-bold text-uppercase tracking-wide shadow-lg">Commit Synchronized Updates</button>
                    <p class="text-center text-muted small mt-4 opacity-50">Authorized change will instantly reflect in the global user marketplace.</p>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>
