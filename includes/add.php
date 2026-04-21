<?php
session_start();
error_reporting(0); // Turn off warnings to avoid breaking JSON
include '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
if (!$data || !isset($data['product_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$product_id = (int)$data['product_id'];
$user_id = (int)$_SESSION['user_id'];

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
}

// Check product exists
$checkProd = pg_query_params($conn, "SELECT id FROM products WHERE id = $1", [$product_id]);
if (!$checkProd || pg_num_rows($checkProd) == 0) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit;
}

// Check existing cart item
$checkCart = pg_query_params($conn, "SELECT id FROM cart WHERE user_id = $1 AND product_id = $2", [$user_id, $product_id]);
if (pg_num_rows($checkCart) > 0) {
    $row = pg_fetch_assoc($checkCart);
    pg_query_params($conn, "UPDATE cart SET quantity = quantity + 1 WHERE id = $1", [$row['id']]);
} else {
    pg_query_params($conn, "INSERT INTO cart (user_id, product_id, quantity) VALUES ($1, $2, 1)", [$user_id, $product_id]);
}

echo json_encode(['success' => true, 'message' => 'Added to cart']);