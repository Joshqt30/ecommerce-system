<?php
include '../config/db.php';
header('Content-Type: application/json');
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($productId <= 0) { http_response_code(400); echo json_encode(['error' => 'Invalid ID']); exit; }
$res = pg_query_params($conn, "SELECT id, name, price, image FROM products WHERE id = $1", [$productId]);
if ($res && ($row = pg_fetch_assoc($res))) {
    echo json_encode($row);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
}