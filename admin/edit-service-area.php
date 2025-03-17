<?php
require_once '../config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$success = '';
$error = '';

// Check if area ID and service ID are provided
if (!isset($_GET['id']) || !is_numeric($_GET['id']) || !isset($_GET['service_id']) || !is_numeric($_GET['service_id'])) {
    redirect('delivery-services.php');
}

$area_id = $_GET['id'];
$service_id = $_GET['service_id'];

// Get area details
try {
    $stmt = $conn->prepare("SELECT * FROM service_areas WHERE area_id = :area_id AND service_id = :service_id");
    $stmt->bindParam(':area_id', $area_id);
    $stmt->bindParam(':service_id', $service_id);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        redirect('service-areas.php?id=' . $service_id);
    }
    
    $area = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get service name
    $stmt = $conn->prepare("SELECT service_name FROM delivery_services WHERE service_id = :service_id");
    $stmt->bindParam(':service_id', $service_id);
    $stmt->execute();
    $service_name = $stmt->fetch(PDO::FETCH_ASSOC)['service_name'];
    
} catch(PDOException $e) {
    $error = 'Error fetching area details: ' . $e->getMessage();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $area_name = sanitize($_POST['area_name']);
    $delivery_fee = floatval($_POST['delivery_fee']);
    $estimated_time = sanitize($_POST['estimated_time']);
    
    // Validate input
    if (empty($area_name) || $delivery_fee < 0) {
        $error = 'Please fill in all required fields with valid values.';
    } else {
        try {
            $stmt = $conn->prepare("UPDATE service_areas SET area_name = :area_name, delivery_fee = :delivery_fee, estimated_time = :estimated_time 
                                   WHERE area_id = :area_id");
            $stmt->bindParam(':area_name', $area_name);
            $stmt->bindParam(':delivery_fee', $delivery_fee);
            $stmt->bindParam(':estimated_time', $estimated_time);
            $stmt->bindParam(':area_id', $area_id);
            $stmt->execute();
            
            $success = 'Service area updated successfully.';
            
            // Refresh area data
            $stmt = $conn->prepare("SELECT * FROM service_areas WHERE area_id = :area_id");
            $stmt->bindParam(':area_id', $area_id);
            $stmt->execute();
            $area = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            $error = 'Error updating service area: ' . $e->getMessage();
        }
    }
}

include '../header.php';
?>

<h2>Edit Service Area</h2>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="service-info">
    <p><strong>Service:</strong> <?php echo $service_name; ?></p>
</div>

<div class="form-container">
    <form method="post" action="">
        <div class="form-group">
            <label for="area_name">Area Name</label>
            <input type="text" id="area_name" name="area_name" class="form-control" value="<?php echo $area['area_name']; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="delivery_fee">Delivery Fee ($)</label>
            <input type="number" id="delivery_fee" name="delivery_fee" class="form-control" step="0.01" min="0" value="<?php echo $area['delivery_fee']; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="estimated_time">Estimated Delivery Time</label>
            <input type="text" id="estimated_time" name="estimated_time" class="form-control" value="<?php echo $area['estimated_time']; ?>" required>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn">Update Area</button>
            <a href="service-areas.php?id=<?php echo $service_id; ?>" class="btn btn-danger">Cancel</a>
        </div>
    </form>
</div>

<?php include '../footer.php'; ?>