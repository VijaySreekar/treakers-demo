<?php
session_start();
require_once __DIR__ . '/../Database/connectdb.php';
header('Content-Type: application/json; charset=utf-8');

// Check if search query is set
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['searchQuery'])) {
    $searchQuery = trim((string)$_POST['searchQuery']);
    if ($searchQuery === '') {
        echo json_encode([]);
        exit;
    }

    if (!($db instanceof PDO)) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection is not available.']);
        exit;
    }

    $stmt = $db->prepare('
        SELECT product_id, name, slug, image, discounted_price
        FROM product
        WHERE status = 1 AND name LIKE :q
        LIMIT 10
    ');
    $stmt->execute(['q' => '%' . $searchQuery . '%']);

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return results as JSON and exit script to prevent further HTML rendering
    echo json_encode($products);
    exit;
}
