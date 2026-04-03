<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $intended_role = $_POST['role'];

    try {
        // Step 1: Role-Specified Authentication
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = ?");
        $stmt->execute([$email, $intended_role]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['is_admin'] = $user['is_admin'];
            $_SESSION['role'] = $user['role'];
            header("Location: index.php");
            exit;
        } else {
            // Check if user exists but has a different role for better feedback
            $roleCheck = $pdo->prepare("SELECT role FROM users WHERE email = ?");
            $roleCheck->execute([$email]);
            $existingUser = $roleCheck->fetch();
            
            if ($existingUser) {
                $error = "Access Restricted: This identity is synchronized as a <strong>" . $existingUser['role'] . "</strong> account. Please switch your login mode.";
            } else {
                $error = "Invalid credential node or account type.";
            }
        }
    } catch (PDOException $e) {
        $error = "An error occurred: " . $e->getMessage();
    }
}

require_once 'includes/header.php';
?>

<div class="container py-5 d-flex justify-content-center align-items-center" style="min-height: 80vh;">
    <div class="col-md-5 glass-card p-5 animate-fade-in">
        <h2 class="fw-bold mb-4 text-center">Welcome Back</h2>
        <p class="text-muted text-center mb-5">Login to your account and explore the marketplace.</p>

        <?php if ($error): ?>
            <div class="alert alert-danger bg-danger text-white border-0 p-3 rounded-4 text-center mb-5 small shadow-lg animate-pulse"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <!-- Role Selection Node -->
            <div class="mb-5">
                <label class="form-label text-muted small text-uppercase fw-bold text-center w-100 mb-3">Login Identity Mode</label>
                <div class="d-flex gap-3">
                    <div class="flex-grow-1">
                        <input type="radio" class="btn-check" name="role" id="login_buyer" value="Buyer" checked>
                        <label class="btn btn-outline-light w-100 py-3 rounded-4 border-opacity-25" for="login_buyer"><i class="fas fa-shopping-bag mb-1 d-block"></i> Buyer</label>
                    </div>
                    <div class="flex-grow-1">
                        <input type="radio" class="btn-check" name="role" id="login_seller" value="Seller">
                        <label class="btn btn-outline-light w-100 py-3 rounded-4 border-opacity-25" for="login_seller"><i class="fas fa-store-alt mb-1 d-block"></i> Seller</label>
                    </div>
                </div>
            </div>

            <div class="mb-4 text-start">
                <label class="form-label text-muted">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0 border-primary-subtle text-primary"><i class="fas fa-envelope"></i></span>
                    <input type="email" name="email" class="form-control border-start-0" placeholder="your@email.com" required>
                </div>
            </div>
            <div class="mb-5 text-start">
                <label class="form-label text-muted small fw-bold">SECURE CREDENTIAL (PASSWORD)</label>
                <div class="input-group">
                    <span class="input-group-text bg-white bg-opacity-5 border-end-0 text-primary"><i class="fas fa-lock"></i></span>
                    <input type="password" name="password" class="form-control border-start-0" placeholder="••••••••" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-3 mb-4 rounded-pill fs-5" style="margin-top: 10px;">Login to Market</button>

            <div class="d-flex align-items-center mb-4">
                <hr class="flex-grow-1 opacity-25">
                <span class="mx-3 text-muted small text-lowercase fw-bold">continue with</span>
                <hr class="flex-grow-1 opacity-25">
            </div>

            <div class="row g-3 mb-5">
                <div class="col-6 text-center">
                    <button type="button" class="btn btn-outline-light w-100 py-3 rounded-pill d-flex align-items-center justify-content-center border-opacity-25" style="background: rgba(255, 255, 255, 0.03);">
                        <img src="https://www.gstatic.com/images/branding/product/1x/gsa_512dp.png" width="18" class="me-2"> Google
                    </button>
                </div>
                <div class="col-6 text-center">
                    <button type="button" class="btn btn-outline-light w-100 py-3 rounded-pill d-flex align-items-center justify-content-center border-opacity-25" style="background: rgba(255, 255, 255, 0.03);">
                        <i class="fab fa-apple fs-5 me-2"></i> Apple
                    </button>
                </div>
            </div>

            <!-- Global Centered Acquisition Hub -->
            <div class="w-100 text-center mt-4">
                <p class="text-muted small mb-0">No synchronized node? <a href="register.php" class="text-primary fw-bold text-decoration-none">Join Now</a></p>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
