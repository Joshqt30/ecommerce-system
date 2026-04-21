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
if (!$data || !isset($data['cart_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$cart_id = (int)$data['cart_id'];
$user_id = (int)$_SESSION['user_id'];

pg_query_params($conn, "DELETE FROM cart WHERE id = $1 AND user_id = $2", [$cart_id, $user_id]);

echo json_encode(['success' => true]);