<?php
require_once '../config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$error = '';

// Check if order ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('orders.php');
}

$order_id = $_GET['id'];

// Get order details
try {
    // Get order information
    $stmt = $conn->prepare("SELECT o.*, u.username, u.email, u.full_name, s.service_name 
                           FROM orders o 
                           JOIN users u ON o.user_id = u.user_id 
                           LEFT JOIN delivery_services s ON o.service_id = s.service_id 
                           WHERE o.order_id = :order_id");
    $stmt->bindParam(':order_id', $order_id);
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

include '../header.php';
?>

<h2>Order Details</h2>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php else: ?>

<div class="order-details">
    <div class="order-header">
        <h3>Order #<?php echo $order['order_id']; ?></h3>
        <p>Date: <?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></p>
        <p>Status: 
            <span id="status-<?php echo $order['order_id']; ?>">
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
            <div class="dropdown">
                <button class="btn btn-sm dropdown-toggle">Update Status</button>
                <div class="dropdown-content">
                    <a href="#" onclick="updateOrderStatus(<?php echo $order['order_id']; ?>, 'pending'); return false;">Pending</a>
                    <a href="#" onclick="updateOrderStatus(<?php echo $order['order_id']; ?>, 'preparing'); return false;">Preparing</a>
                    <a href="#" onclick="updateOrderStatus(<?php echo $order['order_id']; ?>, 'out_for_delivery'); return false;">Out for Delivery</a>
                    <a href="#" onclick="updateOrderStatus(<?php echo $order['order_id']; ?>, 'delivered'); return false;">Delivered</a>
                    <a href="#" onclick="updateOrderStatus(<?php echo $order['order_id']; ?>, 'cancelled'); return false;">Cancelled</a>
                </div>
            </div>
        </p>
    </div>
    
    <div class="order-sections">
        <div class="order-section">
            <h4>Customer Information</h4>
            <p><strong>Name:</strong> <?php echo $order['full_name']; ?></p>
            <p><strong>Email:</strong> <?php echo $order['email']; ?></p>
            <p><strong>Username:</strong> <?php echo $order['username']; ?></p>
        </div>
        
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
                                        <img src="../<?php echo $item['image_url']; ?>" alt="<?php echo $item['name']; ?>" width="50">
                                    <?php else: ?>
                                        <img src="../images/no-image.jpg" alt="No Image" width="50">
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
</div>

<div class="order-actions">
    <a href="orders.php" class="btn">Back to Orders</a>
    <button onclick="printOrder()" class="btn">Print Order</button>
</div>

<script>
    function updateOrderStatus(orderId, status) {
        if (confirm('Are you sure you want to update the status to ' + status + '?')) {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'update-order-status.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onload = function() {
                if (this.status === 200) {
                    try {
                        const response = JSON.parse(this.responseText);
                        if (response.success) {
                            document.getElementById('status-' + orderId).textContent = response.statusText;
                            alert('Order status updated successfully!');
                        } else {
                            alert('Error: ' + response.message);
                        }
                    } catch (e) {
                        console.error('Error parsing JSON response:', e);
                    }
                }
            };
            
            xhr.send('order_id=' + encodeURIComponent(orderId) + '&status=' + encodeURIComponent(status));
        }
    }
    
    function printOrder() {
        window.print();
    }
</script>

<?php endif; ?>

<?php include '../footer.php'; ?>