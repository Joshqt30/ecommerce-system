<?php
session_start();
include '../config/db.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo json_encode([]);
    exit;
}

$query = "
SELECT 
    c.id,
    c.quantity,
    p.id AS product_id,
    p.name,
    p.price,
    p.image
FROM cart c
JOIN products p ON p.id = c.product_id
WHERE c.user_id = $1
";

$result = pg_query_params($conn, $query, [$user_id]);

$cart = [];

while ($row = pg_fetch_assoc($result)) {
    $cart[] = [
        'variantId' => (int)$row['id'],
        'productId' => (int)$row['product_id'],
        'name' => $row['name'],
        'price' => (float)$row['price'],
        'quantity' => (int)$row['quantity'],
        'image' => $row['image']
    ];
}

echo json_encode($cart);