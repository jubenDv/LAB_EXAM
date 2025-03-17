<?php
require_once 'config.php';

// Check if user is logged in and is not admin
if (!isLoggedIn() || isAdmin()) {
    redirect('login.php');
}

// Check if order ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('orders.php');
}

$order_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Get order details
try {
    // Check if order belongs to user
    $stmt = $conn->prepare("SELECT o.*, s.service_name 
                           FROM orders o 
                           LEFT JOIN delivery_services s ON o.service_id = s.service_id 
                           WHERE o.order_id = :order_id AND o.user_id = :user_id");
    $stmt->bindParam(':order_id', $order_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        redirect('orders.php');
    }
    
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get order items
    $stmt = $conn->prepare("SELECT oi.*, p.name, p.image_url 
                           FROM order_items oi 
                           JOIN products p ON oi.product_id = p.product_id 
                           WHERE oi.order_id = :order_id");
    $stmt->bindParam(':order_id', $order_id);
    $stmt->execute();
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error = 'Error fetching order details: ' . $e->getMessage();
}

include 'header.php';
?>

<h2>Order Details</h2>

<div class="order-details-container">
    <div class="order-header">
        <h3>Order #<?php echo $order['order_id']; ?></h3>
        <p>Placed on <?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></p>
        <p>Status: 
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
        </p>
    </div>
    
    <div class="order-sections">
        <div class="order-section">
            <h4>Delivery Information</h4>
            <p><strong>Address:</strong> <?php echo $order['delivery_address']; ?></p>
            <p><strong>Contact Number:</strong> <?php echo $order['contact_number']; ?></p>
            <p><strong>Delivery Service:</strong> <?php echo $order['service_name'] ?: 'N/A'; ?></p>
        </div>
    </div>
    
    <div class="order-items">
        <h4>Order Items</h4>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order_items as $item): ?>
                        <tr>
                            <td>
                                <div class="product-info">
                                    <?php if (!empty($item['image_url'])): ?>
                                        <img src="<?php echo $item['image_url']; ?>" alt="<?php echo $item['name']; ?>" width="50">
                                    <?php else: ?>
                                        <img src="images/no-image.jpg" alt="No Image" width="50">
                                    <?php endif; ?>
                                    <span><?php echo $item['name']; ?></span>
                                </div>
                            </td>
                            <td>$<?php echo number_format($item['price'], 2); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-right"><strong>Total:</strong></td>
                        <td><strong>$<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    
    <div class="order-actions">
        <a href="orders.php" class="btn">Back to Orders</a>
    </div>
</div>

<?php include 'footer.php'; ?>