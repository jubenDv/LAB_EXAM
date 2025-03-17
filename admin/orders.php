<?php
require_once '../config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$success = '';
$error = '';

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query based on filters
$query = "SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id = u.user_id WHERE 1=1";
$params = [];

if (!empty($status_filter)) {
    $query .= " AND o.status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($date_from)) {
    $query .= " AND DATE(o.created_at) >= :date_from";
    $params[':date_from'] = $date_from;
}

if (!empty($date_to)) {
    $query .= " AND DATE(o.created_at) <= :date_to";
    $params[':date_to'] = $date_to;
}

$query .= " ORDER BY o.created_at DESC";

// Get all orders with filters
try {
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = 'Error fetching orders: ' . $e->getMessage();
}

include '../header.php';
?>

<h2>Manage Orders</h2>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="filter-container">
    <form method="get" action="" class="filter-form">
        <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status" class="form-control">
                <option value="">All Statuses</option>
                <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="preparing" <?php echo $status_filter == 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                <option value="out_for_delivery" <?php echo $status_filter == 'out_for_delivery' ? 'selected' : ''; ?>>Out for Delivery</option>
                <option value="delivered" <?php echo $status_filter == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="date_from">Date From</label>
            <input type="date" id="date_from" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
        </div>
        
        <div class="form-group">
            <label for="date_to">Date To</label>
            <input type="date" id="date_to" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn">Filter</button>
            <a href="orders.php" class="btn btn-danger">Reset</a>
        </div>
    </form>
</div>

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
            <?php if (empty($orders)): ?>
                <tr>
                    <td colspan="6">No orders found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
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
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
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
</script>

<?php include '../footer.php'; ?>