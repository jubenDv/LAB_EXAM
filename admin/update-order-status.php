<?php
require_once '../config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Check if required parameters are provided
if (!isset($_POST['order_id']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$order_id = intval($_POST['order_id']);
$status = sanitize($_POST['status']);

// Validate status
$valid_statuses = ['pending', 'preparing', 'out_for_delivery', 'delivered', 'cancelled'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

try {
    // Update order status
    $stmt = $conn->prepare("UPDATE orders SET status = :status WHERE order_id = :order_id");
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':order_id', $order_id);
    $stmt->execute();
    
    // Get status text for display
    $status_text = '';
    switch($status) {
        case 'pending': $status_text = 'Pending'; break;
        case 'preparing': $status_text = 'Preparing'; break;
        case 'out_for_delivery': $status_text = 'Out for Delivery'; break;
        case 'delivered': $status_text = 'Delivered'; break;
        case 'cancelled': $status_text = 'Cancelled'; break;
        default: $status_text = $status; break;
    }
    
    echo json_encode(['success' => true, 'statusText' => $status_text]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>