<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Get cart and shipping data
$cartData = json_decode($_POST['cart_data'] ?? '[]', true);
$shippingData = json_decode($_POST['shipping_data'] ?? '{}', true);
$paymentMethod = $_POST['payment_method'] ?? 'cod';

if (empty($cartData)) {
    die("Cart is empty.");
}

// Calculate total
$total = 0;
foreach ($cartData as $item) {
    $total += $item['price'] * $item['quantity'];
}

// Begin transaction
pg_query($conn, "BEGIN");

// Insert order
$orderRes = pg_query_params($conn,
    "INSERT INTO orders (user_id, total, status, address, payment_method) 
     VALUES ($1, $2, 'pending', $3, $4) RETURNING id",
    [$userId, $total, json_encode($shippingData), $paymentMethod]
);
$orderRow = pg_fetch_assoc($orderRes);
$orderId = $orderRow['id'];

// Insert order items
foreach ($cartData as $item) {
    pg_query_params($conn,
        "INSERT INTO order_items (order_id, variant_id, product_name, price, quantity, image_url)
         VALUES ($1, $2, $3, $4, $5, $6)",
        [$orderId, $item['variantId'] ?? null, $item['name'], $item['price'], $item['quantity'], $item['image']]
    );
}

// Delete all cart items for this user from the database
pg_query_params($conn, "DELETE FROM cart WHERE user_id = $1", [$userId]);

// Commit transaction
pg_query($conn, "COMMIT");

// Redirect to success page
header("Location: order-success.php?id=" . $orderId);
exit;
?>