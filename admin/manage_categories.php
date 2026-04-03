<?php
// admin/manage_categories.php
session_start();
require_once '../includes/db.php';

// Auth: Only Admin node access
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

$error = '';
$success = '';

// Add Category
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['name']);
    $icon = $_POST['icon'] ?: 'fas fa-box';
    $color = $_POST['color'] ?: '#6366f1';

    try {
        $stmt = $pdo->prepare("INSERT INTO categories (name, icon, color) VALUES (?, ?, ?)");
        $stmt->execute([$name, $icon, $color]);
        $success = "Category Node '$name' initialized successfully.";
    } catch (PDOException $e) { $error = "Initialization conflict: ID node may already exist."; }
}

// Delete Category
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
        $success = "Category node archived.";
    } catch (PDOException $e) { $error = "Archival failure."; }
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Category Command | Nexus Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-dark text-white">

<div class="container py-5 animate-fade-in text-start">
    <div class="row align-items-center mb-5">
        <div class="col-8">
            <h1 class="fw-bold mb-0">Category Command</h1>
            <p class="text-muted small">Synchronize global marketplace classification nodes.</p>
        </div>
        <div class="col-4 text-end">
            <a href="dashboard.php" class="btn btn-outline-light rounded-pill px-4">Back to Panel</a>
        </div>
    </div>

    <div class="row g-5">
        <!-- Initialization Form -->
        <div class="col-lg-4">
            <div class="glass-card p-5 border-0 shadow-lg h-100">
                <h5 class="fw-bold mb-5"><i class="fas fa-plus-circle text-primary me-2"></i> INITIALIZE CATEGORY</h5>
                
                <?php if ($success): ?><div class="alert alert-success border-0 small rounded-4 p-3 mb-4"><?php echo $success; ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger border-0 small rounded-4 p-3 mb-4"><?php echo $error; ?></div><?php endif; ?>

                <form action="manage_categories.php" method="POST">
                    <div class="mb-4">
                        <label class="small fw-bold opacity-50">DISPLAY NAME</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Rare Artifacts" required>
                    </div>
                    <div class="mb-4">
                        <label class="small fw-bold opacity-50">FONT-AWESOME ICON CLASS</label>
                        <input type="text" name="icon" class="form-control" placeholder="fas fa-gem" value="fas fa-box">
                    </div>
                    <div class="mb-5">
                        <label class="small fw-bold opacity-50">NODE BRAND COLOR (HEX)</label>
                        <input type="color" name="color" class="form-control form-control-color w-100 bg-transparent border-0" value="#6366f1">
                    </div>
                    <button type="submit" name="add_category" class="btn btn-primary w-100 rounded-pill py-3 fw-bold">BROADCAST NODE</button>
                </form>
            </div>
        </div>

        <!-- Node List -->
        <div class="col-lg-8">
            <div class="glass-card p-5 border-0 shadow-lg h-100">
                <h5 class="fw-bold mb-5">LIVE CATEGORY NODES (<?php echo count($categories); ?>)</h5>
                
                <div class="row g-4">
                    <?php foreach($categories as $c): ?>
                        <div class="col-md-6 col-xl-4">
                            <div class="p-4 bg-white bg-opacity-5 rounded-4 border border-light border-opacity-10 text-center position-relative">
                                <a href="manage_categories.php?delete=<?php echo $c['id']; ?>" class="position-absolute top-0 end-0 p-3 text-danger opacity-25 hover-opacity-100" onclick="return confirm('Archive global node?')"><i class="fas fa-trash-alt"></i></a>
                                <i class="<?php echo htmlspecialchars($c['icon']); ?> fs-2 mb-3" style="color: <?php echo $c['color']; ?>;"></i>
                                <h6 class="fw-bold mb-0 text-white"><?php echo htmlspecialchars($c['name']); ?></h6>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
