<?php
// forgot_password.php
require_once 'includes/db.php';
require_once 'includes/header.php';

$success = false;
$error = "";
$reset_link = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_reset'])) {
    $email = $_POST['email'];
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));
        
        $update = $pdo->prepare("UPDATE users SET reset_token = ?, token_expiry = ? WHERE id = ?");
        $update->execute([$token, $expiry, $user['id']]);
        
        $success = true;
        // In a real app, this would be an email. For this demo, we display the transmission link.
        $reset_link = "reset_password.php?token=" . $token;
    } else {
        $error = "Secure Node Error: No account discovered with this identifier.";
    }
}
?>

<div class="container py-5 animate-fade-in">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="glass-card p-5 border-0 shadow-lg text-center">
                <div class="p-3 bg-primary bg-opacity-10 rounded-circle d-inline-flex mb-4 text-primary">
                    <i class="fas fa-user-shield fs-1"></i>
                </div>
                <h2 class="fw-bold mb-3 text-white">Recover Identity</h2>
                <p class="text-muted mb-5">Enter your secure channel to receive a reset transmission.</p>

                <?php if ($success): ?>
                    <div class="alert bg-success bg-opacity-10 border-0 text-success rounded-4 p-4 text-start">
                        <h6 class="fw-bold mb-2">Transmission Successful!</h6>
                        <p class="small mb-3">A reset link has been generated. Since this is a demo environment, please follow the link below:</p>
                        <a href="<?php echo $reset_link; ?>" class="fw-bold text-decoration-underline"><?php echo $reset_link; ?></a>
                    </div>
                    <a href="login.php" class="btn btn-outline-light w-100 rounded-pill mt-4">Back to Login</a>
                <?php else: ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger border-0 rounded-4 mb-4 small"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form action="forgot_password.php" method="POST">
                        <div class="mb-5 text-start">
                            <label class="form-label text-muted small fw-bold">SECURE CHANNEL (EMAIL)</label>
                            <input type="email" name="email" class="form-control" placeholder="user@nexus.market" required>
                        </div>
                        <button type="submit" name="request_reset" class="btn btn-primary w-100 py-3 rounded-pill shadow-lg">Request Reset Transmission</button>
                    </form>
                    <p class="mt-4 text-muted small">Remembered your credentials? <a href="login.php" class="text-primary text-decoration-none fw-bold">Login Here</a></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
