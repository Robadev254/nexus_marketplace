<?php
// reset_password.php
require_once 'includes/db.php';
require_once 'includes/header.php';

$token = isset($_GET['token']) ? $_GET['token'] : "";
$success = false;
$error = "";

// --- Verify Token Logic ---
$stmt = $pdo->prepare("SELECT id, name, password FROM users WHERE reset_token = ? AND token_expiry > NOW()");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user && $token) {
    $error = "Transmission Compromised: Reset link is either invalid or has expired.";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_password']) && $user) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $error = "Synchronization Error: Passwords do not match.";
    } elseif (password_verify($new_password, $user['password'])) {
        $error = "Security Policy: New password cannot be the same as your previous credentials.";
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, token_expiry = NULL WHERE id = ?");
        $update->execute([$hashed, $user['id']]);
        $success = true;
    }
}
?>

<div class="container py-5 animate-fade-in text-center">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="glass-card p-5 border-0 shadow-lg h-100">
                <div class="p-3 bg-secondary bg-opacity-10 rounded-circle d-inline-flex mb-4 text-secondary">
                    <i class="fas fa-key fs-1"></i>
                </div>
                <h2 class="fw-bold mb-3 text-white">Redefine Integrity</h2>
                <p class="text-muted mb-5">Establish a new set of secure credentials for <strong><?php echo htmlspecialchars($user['name'] ?? 'User'); ?></strong>.</p>

                <?php if ($success): ?>
                    <div class="alert bg-success bg-opacity-10 border-0 text-success rounded-4 p-4 text-start">
                        <h6 class="fw-bold mb-2">Synchronization Complete!</h6>
                        <p class="small mb-0">Your new identity credentials have been successfully updated. You can now login.</p>
                    </div>
                    <a href="login.php" class="btn btn-primary w-100 rounded-pill mt-4 fw-bold shadow-lg">Login to Nexus</a>
                <?php elseif ($token && $user): ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger border-0 rounded-4 mb-4 small"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form action="reset_password.php?token=<?php echo urlencode($token); ?>" method="POST">
                        <div class="mb-4 text-start">
                            <label class="form-label text-muted small fw-bold">NEW CREDENTIAL (PASSWORD)</label>
                            <input type="password" name="new_password" class="form-control" placeholder="••••••••" required>
                        </div>
                        <div class="mb-5 text-start">
                            <label class="form-label text-muted small fw-bold">CONFIRM IDENTITY</label>
                            <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" required>
                        </div>
                        <button type="submit" name="reset_password" class="btn btn-secondary w-100 py-3 rounded-pill shadow-lg fw-bold">Activate New Password</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-danger border-0 rounded-4 mt-4 py-4 text-center">
                        <p class="mb-0">Invalid Transmission Link.</p>
                        <a href="forgot_password.php" class="btn btn-outline-light btn-sm mt-3 px-4 rounded-pill">Request New Link</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
