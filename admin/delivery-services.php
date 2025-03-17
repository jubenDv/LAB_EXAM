<?php
require_once '../config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$success = '';
$error = '';

// Delete delivery service if requested
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $service_id = $_GET['delete'];
    
    try {
        $stmt = $conn->prepare("DELETE FROM delivery_services WHERE service_id = :service_id");
        $stmt->bindParam(':service_id', $service_id);
        $stmt->execute();
        
        $success = 'Delivery service deleted successfully.';
    } catch(PDOException $e) {
        $error = 'Error deleting delivery service: ' . $e->getMessage();
    }
}

// Get all delivery services
try {
    $stmt = $conn->query("SELECT * FROM delivery_services ORDER BY service_name");
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = 'Error fetching delivery services: ' . $e->getMessage();
}

include '../header.php';
?>

<h2>Manage Delivery Services</h2>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="admin-actions">
    <a href="add-delivery-service.php" class="btn">Add New Delivery Service</a>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Service Name</th>
                <th>Base Price</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($services)): ?>
                <tr>
                    <td colspan="5">No delivery services found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($services as $service): ?>
                    <tr>
                        <td><?php echo $service['service_id']; ?></td>
                        <td><?php echo $service['service_name']; ?></td>
                        <td>$<?php echo number_format($service['base_price'], 2); ?></td>
                        <td><?php echo $service['is_available'] ? 'Available' : 'Unavailable'; ?></td>
                        <td>
                            <a href="edit-delivery-service.php?id=<?php echo $service['service_id']; ?>" class="btn btn-sm">Edit</a>
                            <a href="service-areas.php?id=<?php echo $service['service_id']; ?>" class="btn btn-sm">Areas</a>
                            <a href="delivery-services.php?delete=<?php echo $service['service_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this delivery service?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include '../footer.php'; ?>