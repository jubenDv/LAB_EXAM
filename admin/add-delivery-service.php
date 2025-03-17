<?php
require_once '../config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$success = '';
$error = '';

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
            // Insert delivery service into database
            $stmt = $conn->prepare("INSERT INTO delivery_services (service_name, description, base_price, is_available) 
                                   VALUES (:service_name, :description, :base_price, :is_available)");
            $stmt->bindParam(':service_name', $service_name);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':base_price', $base_price);
            $stmt->bindParam(':is_available', $is_available);
            $stmt->execute();
            
            $service_id = $conn->lastInsertId();
            $success = 'Delivery service added successfully.';
            
            // Clear form data
            $service_name = $description = '';
            $base_price = 0;
            $is_available = 1;
        } catch(PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

include '../header.php';
?>

<h2>Add New Delivery Service</h2>

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
            <input type="text" id="service_name" name="service_name" class="form-control" value="<?php echo isset($service_name) ? $service_name : ''; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" class="form-control" rows="4" required><?php echo isset($description) ? $description : ''; ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="base_price">Base Price ($)</label>
            <input type="number" id="base_price" name="base_price" class="form-control" step="0.01" min="0" value="<?php echo isset($base_price) ? $base_price : ''; ?>" required>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="is_available" <?php echo (!isset($is_available) || $is_available) ? 'checked' : ''; ?>>
                Available for selection
            </label>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn">Add Delivery Service</button>
            <a href="delivery-services.php" class="btn btn-danger">Cancel</a>
        </div>
    </form>
</div>

<?php include '../footer.php'; ?>