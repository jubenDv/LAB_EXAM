<?php
// Database configuration
$host = "localhost";
$dbname = "tv_smartscreen";
$username = "root";
$password = "";

// Create database connection
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Function to sanitize input data
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check if user is admin
function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'admin';
}

// Function to redirect to a specific page
function redirect($url) {
    header("Location: $url");
    exit();
}

// Function to display error message
function showError($message) {
    return "<div class='alert alert-danger'>$message</div>";
}

// Function to display success message
function showSuccess($message) {
    return "<div class='alert alert-success'>$message</div>";
}
?>