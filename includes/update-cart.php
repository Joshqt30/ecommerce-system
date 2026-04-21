<?php
session_start();
error_reporting(0);
include '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
if (!$data || !isset($data['cart_id']) || !isset($data['delta'])) {
    echo json_encode(['success' => false]);
    exit;
}

$cart_id = (int)$data['cart_id'];
$delta = (int)$data['delta'];
$user_id = (int)$_SESSION['user_id'];

// Verify cart belongs to user
$check = pg_query_params($conn, "SELECT quantity FROM cart WHERE id = $1 AND user_id = $2", [$cart_id, $user_id]);
if (!$check || pg_num_rows($check) == 0) {
    echo json_encode(['success' => false]);
    exit;
}

$row = pg_fetch_assoc($check);
$new_qty = $row['quantity'] + $delta;

if ($new_qty <= 0) {
    // Remove item
    pg_query_params($conn, "DELETE FROM cart WHERE id = $1", [$cart_id]);
} else {
    pg_query_params($conn, "UPDATE cart SET quantity = $1 WHERE id = $2", [$new_qty, $cart_id]);
}

echo json_encode(['success' => true]);