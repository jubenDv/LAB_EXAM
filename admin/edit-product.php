<?php
require_once '../config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$success = '';
$error = '';

// Check if product ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('products.php');
}

$product_id = $_GET['id'];

// Get product details
try {
    $stmt = $conn->prepare("SELECT * FROM products WHERE product_id = :product_id");
    $stmt->bindParam(':product_id', $product_id);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        redirect('products.php');
    }
    
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = 'Error fetching product: ' . $e->getMessage();
}

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
            $image_url = $product['image_url'];
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
                        // Delete old image if exists
                        if (!empty($product['image_url']) && file_exists('../' . $product['image_url'])) {
                            unlink('../' . $product['image_url']);
                        }
                        
                        $image_url = 'uploads/' . $new_filename;
                    } else {
                        $error = 'Failed to upload image.';
                    }
                } else {
                    $error = 'Invalid file type. Allowed types: ' . implode(', ', $allowed);
                }
            }
            
            if (empty($error)) {
                // Update product in database
                $stmt = $conn->prepare("UPDATE products SET name = :name, description = :description, price = :price, 
                                        image_url = :image_url, stock_quantity = :stock_quantity, is_available = :is_available 
                                        WHERE product_id = :product_id");
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':price', $price);
                $stmt->bindParam(':image_url', $image_url);
                $stmt->bindParam(':stock_quantity', $stock_quantity);
                $stmt->bindParam(':is_available', $is_available);
                $stmt->bindParam(':product_id', $product_id);
                $stmt->execute();
                
                $success = 'Product updated successfully.';
                
                // Refresh product data
                $stmt = $conn->prepare("SELECT * FROM products WHERE product_id = :product_id");
                $stmt->bindParam(':product_id', $product_id);
                $stmt->execute();
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } catch(PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

include '../header.php';
?>

<h2>Edit Product</h2>

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
            <input type="text" id="name" name="name" class="form-control" value="<?php echo $product['name']; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" class="form-control" rows="4" required><?php echo $product['description']; ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="price">Price ($)</label>
            <input type="number" id="price" name="price" class="form-control" step="0.01" min="0.01" value="<?php echo $product['price']; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="stock_quantity">Stock Quantity</label>
            <input type="number" id="stock_quantity" name="stock_quantity" class="form-control" min="0" value="<?php echo $product['stock_quantity']; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="image">Product Image</label>
            <?php if (!empty($product['image_url'])): ?>
                <div class="current-image">
                    <img src="../<?php echo $product['image_url']; ?>" alt="<?php echo $product['name']; ?>" width="100">
                    <p>Current image</p>
                </div>
            <?php endif; ?>
            <input type="file" id="image" name="image" class="form-control">
            <small>Allowed file types: JPG, JPEG, PNG, GIF. Leave empty to keep current image.</small>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="is_available" <?php echo $product['is_available'] ? 'checked' : ''; ?>>
                Available for purchase
            </label>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn">Update Product</button>
            <a href="products.php" class="btn btn-danger">Cancel</a>
        </div>
    </form>
</div>

<?php include '../footer.php'; ?>