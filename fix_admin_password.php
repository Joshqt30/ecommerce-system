<?php
include 'config/db.php';

$new_hash = password_hash('admin123', PASSWORD_DEFAULT);

// Update admin user
$result = pg_query_params($conn, "UPDATE users SET password = $1 WHERE username = 'admin'", [$new_hash]);

if ($result && pg_affected_rows($result) > 0) {
    echo "✅ Admin password updated successfully!<br>";
    echo "New hash: " . $new_hash;
} else {
    // If admin doesn't exist, insert it
    $insert = pg_query_params($conn, 
        "INSERT INTO users (username, email, password, role) VALUES ($1, $2, $3, $4)",
        ['admin', 'admin@ecommerce.local', $new_hash, 'admin']
    );
    if ($insert) {
        echo "✅ Admin user created with password 'admin123'.<br>";
        echo "Hash: " . $new_hash;
    } else {
        echo "❌ Error: " . pg_last_error($conn);
    }
}
?>