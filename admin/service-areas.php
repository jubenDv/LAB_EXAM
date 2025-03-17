<?php
require_once '../config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$success = '';
$error = '';

// Check if service ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('delivery-services.php');
}

$service_id = $_GET['id'];

// Get service details
try {
    $stmt = $conn->prepare("SELECT * FROM delivery_services WHERE service_id = :service_id");
    $stmt->bindParam(':service_id', $service_id);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        redirect('delivery-services.php');
    }
    
    $service = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = 'Error fetching service: ' . $e->getMessage();
}

// Delete area if requested
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $area_id = $_GET['delete'];
    
    try {
        $stmt = $conn->prepare("DELETE FROM service_areas WHERE area_id = :area_id AND service_id = :service_id");
        $stmt->bindParam(':area_id', $area_id);
        $stmt->bindParam(':service_id', $service_id);
        $stmt->execute();
        
        $success = 'Service area deleted successfully.';
    } catch(PDOException $e) {
        $error = 'Error deleting service area: ' . $e->getMessage();
    }
}

// Add new area
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_area'])) {
    $area_name = sanitize($_POST['area_name']);
    $delivery_fee = floatval($_POST['delivery_fee']);
    $estimated_time = sanitize($_POST['estimated_time']);
    
    // Validate input
    if (empty($area_name) || $delivery_fee < 0) {
        $error = 'Please fill in all required fields with valid values.';
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO service_areas (service_id, area_name, delivery_fee, estimated_time) 
                                   VALUES (:service_id, :area_name, :delivery_fee, :estimated_time)");
            $stmt->bindParam(':service_id', $service_id);
            $stmt->bindParam(':area_name', $area_name);
            $stmt->bindParam(':delivery_fee', $delivery_fee);
            $stmt->bindParam(':estimated_time', $estimated_time);
            $stmt->execute();
            
            $success = 'Service area added successfully.';
        } catch(PDOException $e) {
            $error = 'Error adding service area: ' . $e->getMessage();
        }
    }
}

// Get all areas for this service
try {
    $stmt = $conn->prepare("SELECT * FROM service_areas WHERE service_id = :service_id ORDER BY area_name");
    $stmt->bindParam(':service_id', $service_id);
    $stmt->execute();
    $areas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = 'Error fetching service areas: ' . $e->getMessage();
}

include '../header.php';
?>

<h2>Manage Service Areas for <?php echo $service['service_name']; ?></h2>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="service-info">
    <p><strong>Service:</strong> <?php echo $service['service_name']; ?></p>
    <p><strong>Base Price:</strong> $<?php echo number_format($service['base_price'], 2); ?></p>
    <p><strong>Status:</strong> <?php echo $service['is_available'] ? 'Available' : 'Unavailable'; ?></p>
</div>

<div class="form-container">
    <h3>Add New Service Area</h3>
    <form method="post" action="">
        <div class="form-group">
            <label for="area_name">Area Name</label>
            <input type="text" id="area_name" name="area_name" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label for="delivery_fee">Delivery Fee ($)</label>
            <input type="number" id="delivery_fee" name="delivery_fee" class="form-control" step="0.01" min="0" required>
        </div>
        
        <div class="form-group">
            <label for="estimated_time">Estimated Delivery Time</label>
            <input type="text" id="estimated_time" name="estimated_time" class="form-control" placeholder="e.g. 30-45 minutes" required>
        </div>
        
        <div class="form-group">
            <button type="submit" name="add_area" class="btn">Add Area</button>
        </div>
    </form>
</div>

<h3>Service Areas</h3>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Area Name</th>
                <th>Delivery Fee</th>
                <th>Estimated Time</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($areas)): ?>
                <tr>
                    <td colspan="5">No service areas found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($areas as $area): ?>
                    <tr>
                        <td><?php echo $area['area_id']; ?></td>
                        <td><?php echo $area['area_name']; ?></td>
                        <td>$<?php echo number_format($area['delivery_fee'], 2); ?></td>
                        <td><?php echo $area['estimated_time']; ?></td>
                        <td>
                            <a href="edit-service-area.php?id=<?php echo $area['area_id']; ?>&service_id=<?php echo $service_id; ?>" class="btn btn-sm">Edit</a>
                            <a href="service-areas.php?id=<?php echo $service_id; ?>&delete=<?php echo $area['area_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this service area?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="back-link">
    <a href="delivery-services.php" class="btn">Back to Delivery Services</a>
</div>

<?php include '../footer.php'; ?>