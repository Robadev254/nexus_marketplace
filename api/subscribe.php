<?php
// api/subscribe.php
require_once '../includes/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

// Get the input data
$data = json_decode(file_get_contents('php://input'), true);
$email = isset($data['email']) ? trim($data['email']) : '';

// Validate email
if (empty($email)) {
    echo json_encode(['status' => 'error', 'message' => 'Email is required.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email format.']);
    exit;
}

try {
    // Check if already subscribed
    $checkStmt = $pdo->prepare("SELECT id FROM subscribers WHERE email = ?");
    $checkStmt->execute([$email]);
    
    if ($checkStmt->fetch()) {
        echo json_encode(['status' => 'info', 'message' => 'You are already subscribed to the Nexus news stream!']);
        exit;
    }

    // Insert new subscriber
    $insertStmt = $pdo->prepare("INSERT INTO subscribers (email) VALUES (?)");
    $insertStmt->execute([$email]);

    echo json_encode(['status' => 'success', 'message' => 'Successfully joined the Nexus update stream!']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
