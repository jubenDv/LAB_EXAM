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

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $service_name = sanitize($_POST['service_name']);
    $description = sanitize($_POST['description']);
    $base_price = floatval($_POST['base_price']);
    $is_available = isset($_POST['is_available']) ? 1 : 0;
    
    // Validate input
    if (empty($service_name) || empty($description) || $base_price < 0) {
        $error = 'Please fill in all required fields with valid values.';
    } else {
        try {
            // Update delivery service in database
            $stmt = $conn->prepare("UPDATE delivery_services SET service_name = :service_name, description = :description, 
                                   base_price = :base_price, is_available = :is_available 
                                   WHERE service_id = :service_id");
            $stmt->bindParam(':service_name', $service_name);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':base_price', $base_price);
            $stmt->bindParam(':is_available', $is_available);
            $stmt->bindParam(':service_id', $service_id);
            $stmt->execute();
            
            $success = 'Delivery service updated successfully.';
            
            // Refresh service data
            $stmt = $conn->prepare("SELECT * FROM delivery_services WHERE service_id = :service_id");
            $stmt->bindParam(':service_id', $service_id);
            $stmt->execute();
            $service = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

include '../header.php';
?>

<h2>Edit Delivery Service</h2>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="form-container">
    <form method="post" action="">
        <div class="form-group">
            <label for="service_name">Service Name</label>
            <input type="text" id="service_name" name="service_name" class="form-control" value="<?php echo $service['service_name']; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" class="form-control" rows="4" required><?php echo $service['description']; ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="base_price">Base Price ($)</label>
            <input type="number" id="base_price" name="base_price" class="form-control" step="0.01" min="0" value="<?php echo $service['base_price']; ?>" required>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="is_available" <?php echo $service['is_available'] ? 'checked' : ''; ?>>
                Available for selection
            </label>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn">Update Delivery Service</button>
            <a href="delivery-services.php" class="btn btn-danger">Cancel</a>
        </div>
    </form>
</div>

<div class="service-actions">
    <a href="service-areas.php?id=<?php echo $service_id; ?>" class="btn">Manage Service Areas</a>
</div>

<?php include '../footer.php'; ?>