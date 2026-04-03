<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexus Market | Premium Second-Hand Platform</title>
    <meta name="description" content="Discover, buy, and sell second-hand treasures on Nexus Market. Electronics, fashion, collectibles, and more.">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/styles.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-transparent">
    <div class="container">
        <a class="navbar-brand fw-bold fs-3" href="index.php" style="background: linear-gradient(45deg, #6366f1, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">NEXUS</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <!-- Left Side: Core Navigation -->
            <ul class="navbar-nav me-auto gap-lg-3">
                <li class="nav-item"><a class="nav-link px-3" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link px-3" href="products.php">Marketplace</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="catDropdown" role="button" data-bs-toggle="dropdown">Categories</a>
                    <ul class="dropdown-menu glass-card border-0 shadow-lg mt-2" aria-labelledby="catDropdown">
                        <?php 
                        $navCats = $pdo->query("SELECT name FROM categories ORDER BY name ASC LIMIT 10")->fetchAll();
                        foreach($navCats as $nc): ?>
                            <li><a class="dropdown-item py-2 px-4 small" href="products.php?category=<?php echo urlencode($nc['name']); ?>"><?php echo htmlspecialchars($nc['name']); ?></a></li>
                        <?php endforeach; ?>
                        <li><hr class="dropdown-divider opacity-25 mx-3"></li>
                        <li><a class="dropdown-item py-2 px-4 small fw-bold" href="products.php">All Nodes</a></li>
                    </ul>
                </li>
            </ul>

            <!-- Right Side: Unified Hamburger Account Hub -->
            <ul class="navbar-nav ms-auto align-items-center">
                <?php if (isset($_SESSION['user_id'])): 
                    $navUser = $pdo->prepare("SELECT profile_pic, name FROM users WHERE id = ?");
                    $navUser->execute([$_SESSION['user_id']]);
                    $navUserData = $navUser->fetch();
                    $nav_pic = $navUserData['profile_pic'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($navUserData['name']) . '&background=6366f1&color=fff';
                ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link p-0 border-0 shadow-none dropdown-toggle hide-caret" href="#" id="hamburgerMenu" role="button" data-bs-toggle="dropdown">
                            <img src="<?php echo htmlspecialchars($nav_pic); ?>" width="35" height="35" class="rounded-circle shadow-sm border border-primary border-opacity-25" style="object-fit: cover;">
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end glass-card p-3 shadow-lg border-light border-opacity-10 mt-3" style="min-width: 250px;">
                            <li>
                                <div class="px-3 mb-2 d-flex justify-content-between align-items-center">
                                    <h6 class="dropdown-header text-primary small fw-bold text-uppercase mb-0 p-0">Hi, <?php echo htmlspecialchars($_SESSION['user_name']); ?></h6>
                                    <span class="badge <?php echo ($_SESSION['role'] == 'Seller' ? 'bg-primary' : 'bg-success'); ?> rounded-pill px-2 py-1 fs-mini shadow-sm"><?php echo $_SESSION['role']; ?></span>
                                </div>
                            </li>
                            <li><p class="px-3 mb-2 small text-muted opacity-75">Authenticated Account Node</p></li>
                            <li><hr class="dropdown-divider opacity-10 mb-3"></li>
                            
                            <li><h6 class="dropdown-header text-primary small fw-bold text-uppercase mb-2">MY ACCOUNT</h6></li>
                            <li><a class="dropdown-item py-2 rounded-3 text-white" href="profile.php"><i class="fas fa-user-circle me-2 opacity-50"></i> My Profile</a></li>
                            <li><a class="dropdown-item py-2 rounded-3 text-white" href="cart.php"><i class="fas fa-shopping-cart me-2 opacity-50"></i> My Cart</a></li>
                            <li><a class="dropdown-item py-2 rounded-3 text-white" href="myorders.php"><i class="fas fa-box-open me-2 opacity-50"></i> My Orders</a></li>
                            <li><a class="dropdown-item py-2 rounded-3 text-white" href="testimonials.php"><i class="fas fa-star me-2 opacity-50"></i> Platform Reviews</a></li>
                            
                            <?php if ($_SESSION['is_admin']): ?>
                                <li><a class="dropdown-item py-2 rounded-3 text-warning" href="admin/dashboard.php"><i class="fas fa-shield-alt me-2 opacity-50"></i> Admin Panel</a></li>
                            <?php endif; ?>

                            <li><hr class="dropdown-divider opacity-10 my-3"></li>
                            <li><a class="dropdown-item py-2 rounded-3 text-danger mt-2" href="logout.php"><i class="fas fa-sign-out-alt me-2 opacity-75"></i> Logout Session</a></li>
                        </ul>
                    </li>

                    <li class="nav-item ms-3">
                        <button id="theme-toggle" class="btn btn-link btn-sm p-0 border-0 shadow-none" title="Switch Theme">
                            <img id="theme-icon" src="assets/light_theme.png" width="22" height="22" class="rounded-circle theme-changer">
                        </button>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                    <li class="nav-item"><a class="btn btn-primary rounded-pill px-4" href="register.php">Join Now</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
