<?php
// Load environment variables from a .env file or use getenv() for server environment variables
$servername = getenv('DB_SERVER') ?: 'localhost';
$username = getenv('DB_USERNAME') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
$dbname = getenv('DB_NAME') ?: 'aihwa';

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  error_log("Database connection failed: " . $conn->connect_error);
  header("HTTP/1.1 500 Internal Server Error");
  exit("An error occurred. Please try again later.");
}

// Connection successful

error_log("Database connected successfully");
?>