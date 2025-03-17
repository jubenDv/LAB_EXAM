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
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $price = floatval($_POST['price']);
    $stock_quantity = intval($_POST['stock_quantity']);
    $is_available = isset($_POST['is_available']) ? 1 : 0;
    
    // Validate input
    if (empty($name) || empty($description) || $price <= 0) {
        $error = 'Please fill in all required fields with valid values.';
    } else {
        try {
            // Handle image upload
            $image_url = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $filename = $_FILES['image']['name'];
                $ext = pathinfo($filename, PATHINFO_EXTENSION);
                
                if (in_array(strtolower($ext), $allowed)) {
                    $new_filename = uniqid() . '.' . $ext;
                    $upload_dir = '../uploads/';
                    
                    // Create directory if it doesn't exist
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $new_filename)) {
                        $image_url = 'uploads/' . $new_filename;
                    } else {
                        $error = 'Failed to upload image.';
                    }
                } else {
                    $error = 'Invalid file type. Allowed types: ' . implode(', ', $allowed);
                }
            }
            
            if (empty($error)) {
                // Insert product into database
                $stmt = $conn->prepare("INSERT INTO products (name, description, price, image_url, stock_quantity, is_available) 
                                        VALUES (:name, :description, :price, :image_url, :stock_quantity, :is_available)");
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':price', $price);
                $stmt->bindParam(':image_url', $image_url);
                $stmt->bindParam(':stock_quantity', $stock_quantity);
                $stmt->bindParam(':is_available', $is_available);
                $stmt->execute();
                
                $success = 'Product added successfully.';
                
                // Clear form data
                $name = $description = '';
                $price = $stock_quantity = 0;
                $is_available = 1;
            }
        } catch(PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

include '../header.php';
?>

<h2>Add New Product</h2>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="form-container">
    <form method="post" action="" enctype="multipart/form-data">
        <div class="form-group">
            <label for="name">Product Name</label>
            <input type="text" id="name" name="name" class="form-control" value="<?php echo isset($name) ? $name : ''; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" class="form-control" rows="4" required><?php echo isset($description) ? $description : ''; ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="price">Price ($)</label>
            <input type="number" id="price" name="price" class="form-control" step="0.01" min="0.01" value="<?php echo isset($price) ? $price : ''; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="stock_quantity">Stock Quantity</label>
            <input type="number" id="stock_quantity" name="stock_quantity" class="form-control" min="0" value="<?php echo isset($stock_quantity) ? $stock_quantity : ''; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="image">Product Image</label>
            <input type="file" id="image" name="image" class="form-control">
            <small>Allowed file types: JPG, JPEG, PNG, GIF</small>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="is_available" <?php echo (!isset($is_available) || $is_available) ? 'checked' : ''; ?>>
                Available for purchase
            </label>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn">Add Product</button>
            <a href="products.php" class="btn btn-danger">Cancel</a>
        </div>
    </form>
</div>

<?php include '../footer.php'; ?>