<?php
require_once 'config.php';

// Check if user is logged in and is not admin
if (!isLoggedIn() || isAdmin()) {
    redirect('login.php');
}

// Check if order ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('index.php');
}

$order_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Get order details
try {
    // Check if order belongs to user
    $stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = :order_id AND user_id = :user_id");
    $stmt->bindParam(':order_id', $order_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        redirect('index.php');
    }
    
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get delivery service
    $service_name = 'N/A';
    if ($order['service_id']) {
        $stmt = $conn->prepare("SELECT service_name FROM delivery_services WHERE service_id = :service_id");
        $stmt->bindParam(':service_id', $order['service_id']);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $service_name = $stmt->fetch(PDO::FETCH_ASSOC)['service_name'];
        }
    }
    
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

<div class="order-confirmation">
    <div class="confirmation-header">
        <h2>Order Confirmation</h2>
        <div class="confirmation-message">
            <p>Thank you for your order! Your order has been placed successfully.</p>
            <p>Order #<?php echo $order_id; ?> was placed on <?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></p>
        </div>
    </div>
    
    <div class="order-details">
        <div class="order-info">
            <h3>Order Information</h3>
            <p><strong>Order Status:</strong> 
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
            </p>
            <p><strong>Delivery Address:</strong> <?php echo $order['delivery_address']; ?></p>
            <p><strong>Contact Number:</strong> <?php echo $order['contact_number']; ?></p>
            <p><strong>Delivery Service:</strong> <?php echo $service_name; ?></p>
        </div>
        
        <div class="order-items">
            <h3>Order Items</h3>
            
            <div class="order-items-list">
                <?php foreach ($order_items as $item): ?>
                    <div class="order-item">
                        <div class="order-item-image">
                            <?php if (!empty($item['image_url'])): ?>
                                <img src="<?php echo $item['image_url']; ?>" alt="<?php echo $item['name']; ?>">
                            <?php else: ?>
                                <img src="images/no-image.jpg" alt="No Image">
                            <?php endif; ?>
                        </div>
                        
                        <div class="order-item-details">
                            <h4><?php echo $item['name']; ?></h4>
                            <p>$<?php echo number_format($item['price'], 2); ?> x <?php echo $item['quantity']; ?></p>
                        </div>
                        
                        <div class="order-item-total">
                            $<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="order-total">
                <h4>Total: $<?php echo number_format($order['total_amount'], 2); ?></h4>
            </div>
        </div>
    </div>
    
    <div class="confirmation-actions">
        <a href="orders.php" class="btn">View My Orders</a>
        <a href="products.php" class="btn">Continue Shopping</a>
    </div>
</div>

<?php include 'footer.php'; ?>