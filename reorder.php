<?php
// reorder.php
session_start();
require_once 'includes/db.php';

// Authentication Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['order_id'])) {
    $order_id = (int)$_POST['order_id'];
    $user_id = $_SESSION['user_id'];

    try {
        // 1. Fetch original order items
        $stmt = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
        $stmt->execute([$order_id]);
        $items = $stmt->fetchAll();

        if (count($items) > 0) {
            $pdo->beginTransaction();

            // 2. Clear current cart to avoid confusing the user with past cart contents
            $pdo->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$user_id]);

            // 3. Load cancelled items into the cart
            foreach ($items as $item) {
                $p_id = $item['product_id'];
                $qty = $item['quantity'];

                $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)")
                    ->execute([$user_id, $p_id, $qty]);
            }

            $pdo->commit();
            
            // 4. Redirect directly to checkout where quantity controls (Batch) are active
            header("Location: checkout.php?reorder=active");
            exit;
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        header("Location: myorders.php?error=reorder_failed");
        exit;
    }
} else {
    header("Location: myorders.php");
    exit;
}
?>
