<?php
require_once 'config.php';

// Check if product ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('products.php');
}

$product_id = $_GET['id'];

// Get product details
try {
    $stmt = $conn->prepare("SELECT * FROM products WHERE product_id = :product_id AND is_available = 1");
    $stmt->bindParam(':product_id', $product_id);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        redirect('products.php');
    }
    
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = 'Error fetching product: ' . $e->getMessage();
}

include 'header.php';
?>

<div class="product-details-container">
    <div class="product-details-image">
        <?php if (!empty($product['image_url'])): ?>
            <img src="<?php echo $product['image_url']; ?>" alt="<?php echo $product['name']; ?>">
        <?php else: ?>
            <img src="images/no-image.jpg" alt="No Image">
        <?php endif; ?>
    </div>
    
    <div class="product-details-info">
        <h2><?php echo $product['name']; ?></h2>
        <p class="product-price">$<?php echo number_format($product['price'], 2); ?></p>
        
        <div class="product-description">
            <h3>Description</h3>
            <p><?php echo $product['description']; ?></p>
        </div>
        
        <div class="product-stock">
            <p>
                <?php if ($product['stock_quantity'] > 0): ?>
                    <span class="in-stock">In Stock (<?php echo $product['stock_quantity']; ?> available)</span>
                <?php else: ?>
                    <span class="out-of-stock">Out of Stock</span>
                <?php endif; ?>
            </p>
        </div>
        
        <?php if (isLoggedIn() && !isAdmin() && $product['stock_quantity'] > 0): ?>
            <div class="add-to-cart-form">
                <div class="quantity-control">
                    <label for="quantity">Quantity:</label>
                    <div class="quantity-buttons">
                        <button type="button" class="quantity-btn decrement">-</button>
                        <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>">
                        <button type="button" class="quantity-btn increment">+</button>
                    </div>
                </div>
                
                <button onclick="addToCart(<?php echo $product['product_id']; ?>, document.getElementById('quantity').value)" class="btn btn-lg">Add to Cart</button>
            </div>
        <?php endif; ?>
        
        <div class="back-to-products">
            <a href="products.php" class="btn">Back to Products</a>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>