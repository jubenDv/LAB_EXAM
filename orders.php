<?php
require_once 'config.php';

// Check if user is logged in and is not admin
if (!isLoggedIn() || isAdmin()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Get all orders for user
try {
    $stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = :user_id ORDER BY created_at DESC");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = 'Error fetching orders: ' . $e->getMessage();
}

include 'header.php';
?>

<h2>My Orders</h2>

<div class="orders-container">
    <?php if (empty($orders)): ?>
        <div class="empty-orders">
            <p>You haven't placed any orders yet.</p>
            <a href="products.php" class="btn">Browse Products</a>
        </div>
    <?php else: ?>
        <div class="orders-list">
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="order-id">
                            <h3>Order #<?php echo $order['order_id']; ?></h3>
                            <p>Placed on <?php echo date('M d, Y', strtotime($order['created_at'])); ?></p>
                        </div>
                        
                        <div class="order-status">
                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                <?php 
                                    switch($order['status']) {
                                        case 'pending': echo 'Pending'; break;
                                        case 'preparing': echo 'Preparing'; break;
                                        case 'out_for_delivery': echo 'Out for Delivery'; break;
                                        case 'delivered': echo 'Delivered'; break;
                                        case 'cancelled': echo 'Cancelled'; break;
                                        default: echo $order['status']; break;
                                    }
                                ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="order-details">
                        <p><strong>Total:</strong> $<?php echo number_format($order['total_amount'], 2); ?></p>
                        <p><strong>Delivery Address:</strong> <?php echo $order['delivery_address']; ?></p>
                    </div>
                    
                    <div class="order-actions">
                        <a href="view-order.php?id=<?php echo $order['order_id']; ?>" class="btn">View Details</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>