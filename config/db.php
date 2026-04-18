<?php
$host = "localhost";
$port = "5432";
$dbname = "ecommerce_db";
$user = "postgres";
$pass = "Joshpogi123";

$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$pass");

if (!$conn) {
    die("Connection failed: " . pg_last_error());
}
?>