<?php
require_once 'config.php';

// Get search and filter parameters
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Build query based on search
$query = "SELECT * FROM products WHERE is_available = 1";
$params = [];

if (!empty($search)) {
    $query .= " AND (name LIKE :search OR description LIKE :search)";
    $params[':search'] = "%$search%";
}

$query .= " ORDER BY name";

// Get products
try {
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = 'Error fetching products: ' . $e->getMessage();
}

include 'header.php';
?>

<h2>Products</h2>

<div class="search-container">
    <form method="get" action="" class="search-form">
        <div class="form-group">
            <input type="text" id="product-search" name="search" class="form-control" placeholder="Search products..." value="<?php echo $search; ?>">
            <button type="submit" class="btn">Search</button>
            <?php if (!empty($search)): ?>
                <a href="products.php" class="btn btn-danger">Clear</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="products-grid">
    <?php if (empty($products)): ?>
        <p>No products found.</p>
    <?php else: ?>
        <?php foreach ($products as $product): ?>
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

<?php include 'footer.php'; ?>