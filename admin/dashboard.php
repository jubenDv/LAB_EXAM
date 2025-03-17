<?php
require_once '../config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Get dashboard statistics
try {
    // Total products
    $stmt = $conn->query("SELECT COUNT(*) as total FROM products");
    $total_products = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total customers
    $stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE user_type = 'customer'");
    $total_customers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total orders
    $stmt = $conn->query("SELECT COUNT(*) as total FROM orders");
    $total_orders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total revenue
    $stmt = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE status != 'cancelled'");
    $total_revenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;
    
    // Recent orders
    $stmt = $conn->query("SELECT o.*, u.username FROM orders o 
                          JOIN users u ON o.user_id = u.user_id 
                          ORDER BY o.created_at DESC LIMIT 5");
    $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}

include '../header.php';
?>

<h2>Admin Dashboard</h2>

<div class="dashboard-stats">
    <div class="stat-card">
        <div class="stat-number"><?php echo $total_products; ?></div>
        <div class="stat-title">Total Products</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-number"><?php echo $total_customers; ?></div>
        <div class="stat-title">Total Customers</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-number"><?php echo $total_orders; ?></div>
        <div class="stat-title">Total Orders</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-number">$<?php echo number_format($total_revenue, 2); ?></div>
        <div class="stat-title">Total Revenue</div>
    </div>
</div>

<h3>Recent Orders</h3>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($recent_orders)): ?>
                <tr>
                    <td colspan="6">No orders found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($recent_orders as $order): ?>
                    <tr>
                        <td>#<?php echo $order['order_id']; ?></td>
                        <td><?php echo $order['username']; ?></td>
                        <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                        <td id="status-<?php echo $order['order_id']; ?>">
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
                        </td>
                        <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                        <td>
                            <a href="view-order.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="dashboard-actions">
    <a href="products.php" class="btn">Manage Products</a>
    <a href="delivery-services.php" class="btn">Manage Delivery Services</a>
    <a href="orders.php" class="btn">View All Orders</a>
</div>

<?php include '../footer.php'; ?>