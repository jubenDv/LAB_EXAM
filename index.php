<?php
require_once 'config.php';

// Get featured products
try {
    $stmt = $conn->query("SELECT * FROM products WHERE is_available = 1 ORDER BY RAND() LIMIT 6");
    $featured_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = 'Error fetching products: ' . $e->getMessage();
}

include 'header.php';
?>

<div class="hero-section">
    <div class="hero-content">
        <h1>Welcome to TV Smartscreen</h1>
        <p>Your one-stop shop for local product delivery</p>
        <div class="hero-buttons">
            <a href="products.php" class="btn">Browse Products</a>
            <?php if (!isLoggedIn()): ?>
                <a href="register.php" class="btn">Sign Up Now</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="featured-section">
    <h2>Featured Products</h2>
    
    <div class="products-grid">
        <?php if (empty($featured_products)): ?>
            <p>No products available at the moment.</p>
        <?php else: ?>
            <?php foreach ($featured_products as $product): ?>
                <div class="product-card">
                    <div class="product-image">
                        <?php if (!empty($product['image_url'])): ?>
                            <img src="<?php echo $product['image_url']; ?>" alt="<?php echo $product['name']; ?>">
                        <?php else: ?>
                            <img src="images/no-image.jpg" alt="No Image">
                        <?php endif; ?>
                    </div>
                    <div class="product-details">
                        <h3 class="product-title"><?php echo $product['name']; ?></h3>
                        <p class="product-price">$<?php echo number_format($product['price'], 2); ?></p>
                        <p class="product-description"><?php echo substr($product['description'], 0, 100) . '...'; ?></p>
                        <div class="product-actions">
                            <a href="product-details.php?id=<?php echo $product['product_id']; ?>" class="btn">View Details</a>
                            <?php if (isLoggedIn() && !isAdmin()): ?>
                                <button onclick="addToCart(<?php echo $product['product_id']; ?>, 1)" class="btn">Add to Cart</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <div class="view-all">
        <a href="products.php" class="btn">View All Products</a>
    </div>
</div>

<div class="how-it-works">
    <h2>How It Works</h2>
    
    <div class="steps-container">
        <div class="step">
            <div class="step-icon">1</div>
            <h3>Browse Products</h3>
            <p>Explore our wide range of products available for delivery.</p>
        </div>
        
        <div class="step">
            <div class="step-icon">2</div>
            <h3>Add to Cart</h3>
            <p>Select the items you want and add them to your cart.</p>
        </div>
        
        <div class="step">
            <div class="step-icon">3</div>
            <h3>Choose Delivery</h3>
            <p>Select your preferred delivery service and provide your address.</p>
        </div>
        
        <div class="step">
            <div class="step-icon">4</div>
            <h3>Receive Products</h3>
            <p>Sit back and relax as your products are delivered to your doorstep.</p>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>