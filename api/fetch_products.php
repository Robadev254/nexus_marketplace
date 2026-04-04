<?php
// api/fetch_products.php
require_once '../includes/db.php';
header('Content-Type: application/json');

$page = (int)($_GET['page'] ?? 1);
$limit = 8;
$offset = ($page - 1) * $limit;

$category = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';
$seller_id = (int)($_GET['seller_id'] ?? 0);

$query = "SELECT p.*, u.name as seller_name FROM products p JOIN users u ON p.seller_id = u.id WHERE 1=1";
$params = [];

if ($seller_id > 0) {
    $query .= " AND p.seller_id = ?";
    $params[] = $seller_id;
}
if ($category) {
    $query .= " AND p.category = ?";
    $params[] = $category;
}
if ($search) {
    $query .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY (p.stock > 0) DESC, p.created_at DESC LIMIT :limit OFFSET :offset";

try {
    $stmt = $pdo->prepare($query);
    
    // Bind dynamic parameters
    for ($i = 0; $i < count($params); $i++) {
        $stmt->bindValue($i + 1, $params[$i]);
    }
    
    // Bind limit and offset by name (safer)
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format fields for frontend
    foreach ($products as &$product) {
        $product['formatted_price'] = number_format($product['price'], 2);
        $product['short_description'] = substr(htmlspecialchars($product['description']), 0, 70) . '...';
        $product['badge_class'] = ($product['condition'] === 'New' ? 'bg-primary' : 'bg-secondary');
    }

    echo json_encode([
        'status' => 'success',
        'products' => $products,
        'has_more' => count($products) === $limit
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
