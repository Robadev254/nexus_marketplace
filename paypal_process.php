<?php
// paypal_process.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['paypal_login'])) {
    $amount = (float)$_POST['order_amount'];
    
    // Simulated "PayPal Digital Wallet" balance check
    $user_balance = 750.45; // Fixed mock balance for the demo
    
    if ($user_balance >= $amount) {
        // Wait 2 seconds for authorization simulation
        sleep(2);
        // Successful authorization
        header("Location: checkout.php?paypal_success=true&auth=pp_" . bin2hex(random_bytes(8)));
        exit;
    } else {
        header("Location: checkout.php?error=paypal_insufficient_funds&balance=" . $user_balance);
        exit;
    }
} else {
    header("Location: checkout.php");
    exit;
}
?>
