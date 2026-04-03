<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    try {
        // Step 1: Pre-Registration Identity Verification
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $checkStmt->execute([$email]);
        if ($checkStmt->fetch()) {
            $error = "Secure Identity Conflict: This email channel is already synchronized with an existing account.";
        } else {
            // Step 2: Global Account Initialization
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $password, $role]);
            header("Location: login.php?registered=1");
            exit;
        }
    } catch (PDOException $e) {
        $error = "Error creating account: " . $e->getMessage();
    }
}

require_once 'includes/header.php';
?>

<?php 
    $pre_role = isset($_GET['role']) ? $_GET['role'] : 'Buyer';
    $is_onboarding_seller = ($pre_role === 'Seller');
?>
<div class="container py-5 d-flex justify-content-center align-items-center" style="min-height: 80vh;">
    <div class="col-md-5 glass-card p-5 animate-fade-in shadow-lg">
        <h2 class="fw-bold mb-4 text-center"><?php echo ($is_onboarding_seller ? 'Register as Merchant' : 'Start Your Journey'); ?></h2>
        <p class="text-muted text-center mb-5"><?php echo ($is_onboarding_seller ? 'Create your specialized seller account and start listing treasures.' : 'Create your account and become a part of Nexus community.'); ?></p>
        
        <?php if ($is_onboarding_seller && isset($_SESSION['user_id']) && $_SESSION['role'] === 'Buyer'): ?>
            <div class="alert alert-info bg-primary bg-opacity-10 text-primary border-primary border-opacity-25 py-3 rounded-4 text-center mb-5 small">
                <i class="fas fa-info-circle me-1"></i> Since roles are permanent, you are initializing a <strong>new specialized Seller identity</strong>.
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger bg-danger text-white border-0 py-2 rounded-pill text-center mb-4"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <div class="mb-4">
                <label class="form-label text-muted">Full Name</label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0 border-primary-subtle text-primary"><i class="fas fa-user-circle"></i></span>
                    <input type="text" name="name" class="form-control border-start-0" placeholder="e.g. John Doe" required>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label text-muted">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0 border-primary-subtle text-primary"><i class="fas fa-envelope"></i></span>
                    <input type="email" name="email" class="form-control border-start-0" placeholder="your@email.com" required>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label text-muted">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0 border-primary-subtle text-primary"><i class="fas fa-lock"></i></span>
                    <input type="password" name="password" class="form-control border-start-0" placeholder="••••••••" required>
                </div>
            </div>
            <div class="mb-4 text-start">
                <label class="form-label text-muted small text-uppercase fw-bold">Select Account Type</label>
                <div class="d-flex gap-3 mt-2">
                    <div class="flex-grow-1">
                        <input type="radio" class="btn-check" name="role" id="role_buyer" value="Buyer" <?php echo ($pre_role === 'Buyer' ? 'checked' : ''); ?>>
                        <label class="btn btn-outline-light w-100 py-3 rounded-4 border-opacity-25 bg-white bg-opacity-5" for="role_buyer">
                            <i class="fas fa-shopping-bag mb-2 d-block"></i> Buyer
                        </label>
                    </div>
                    <div class="flex-grow-1">
                        <input type="radio" class="btn-check" name="role" id="role_seller" value="Seller" <?php echo ($pre_role === 'Seller' ? 'checked' : ''); ?>>
                        <label class="btn btn-outline-light w-100 py-3 rounded-4 border-opacity-25 bg-white bg-opacity-5" for="role_seller">
                            <i class="fas fa-store-alt mb-2 d-block"></i> Seller
                        </label>
                    </div>
                </div>
            </div>

            <div class="mt-2 mb-4 text-center">
                <p class="fs-mini text-muted opacity-50 italic"><i class="fas fa-fingerprint me-1"></i> Selection is permanent. Roles cannot be modified after global initialization.</p>
            </div>

            <button type="submit" name="register" class="btn btn-primary w-100 py-3 mb-4 rounded-pill fs-5">Create Account</button>

            <div class="d-flex align-items-center mb-4">
                <hr class="flex-grow-1 opacity-25">
                <span class="mx-3 text-muted small text-uppercase fw-bold">Or sign up with</span>
                <hr class="flex-grow-1 opacity-25">
            </div>

            <div class="row g-3 mb-4">
                <div class="col-6">
                    <button type="button" class="btn btn-outline-light w-100 py-2 rounded-pill d-flex align-items-center justify-content-center border-opacity-25" style="background: rgba(255, 255, 255, 0.03);">
                        <img src="https://www.gstatic.com/images/branding/product/1x/gsa_512dp.png" width="18" class="me-2"> Google
                    </button>
                </div>
                <div class="col-6">
                    <button type="button" class="btn btn-outline-light w-100 py-2 rounded-pill d-flex align-items-center justify-content-center border-opacity-25" style="background: rgba(255, 255, 255, 0.03);">
                        <i class="fab fa-apple fs-5 me-2"></i> Apple
                    </button>
                </div>
            </div>
        </form>

        <p class="text-center text-muted mb-0">Already a member? <a href="login.php" class="text-primary fw-bold text-decoration-none">Log In</a></p>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
