<?php
// cancel_order.php
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
        // Verify order exists, belongs to user, and is Pending
        $checkStmt = $pdo->prepare("SELECT status FROM orders WHERE id = ? AND user_id = ?");
        $checkStmt->execute([$order_id, $user_id]);
        $order = $checkStmt->fetch();

        if ($order && $order['status'] == 'Pending') {
            // Initiate cancellation
            $pdo->beginTransaction();

            // 1. Update order status
            $updateStmt = $pdo->prepare("UPDATE orders SET status = 'Cancelled' WHERE id = ?");
            $updateStmt->execute([$order_id]);

            // 2. Return items to stock
            $itemStmt = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
            $itemStmt->execute([$order_id]);
            $items = $itemStmt->fetchAll();

            $restoreStockStmt = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
            foreach ($items as $item) {
                $restoreStockStmt->execute([$item['quantity'], $item['product_id']]);
            }

            $pdo->commit();
            header("Location: myorders.php?cancelled=success");
            exit;
        } else {
            header("Location: myorders.php?cancelled=error&reason=not_pending");
            exit;
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        header("Location: myorders.php?cancelled=error&reason=sys_error");
        exit;
    }
} else {
    header("Location: myorders.php");
    exit;
}
?>
