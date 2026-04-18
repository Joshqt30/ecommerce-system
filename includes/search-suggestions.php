<?php

include '../config/db.php';

// Get and sanitize input parameters
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$cat = isset($_GET['cat']) ? trim($_GET['cat']) : '';

// Need at least 2 characters for search
if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

// Build query — optionally scoped to category
$params = ['%' . $q . '%'];
$catWhere = '';
if (!empty($cat) && $cat !== 'All' && $cat !== 'Others') {
    $catWhere = 'AND category = $2';
    $params[] = $cat;
}

$sql = "
    SELECT id, name, category
    FROM products
    WHERE (name ILIKE $1 OR description ILIKE $1) {$catWhere}
    ORDER BY name ASC
    LIMIT 8
";

$result = pg_query_params($conn, $sql, $params);
$suggestions = [];

if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $suggestions[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'category' => $row['category'],
        ];
    }
}

echo json_encode($suggestions);

?>