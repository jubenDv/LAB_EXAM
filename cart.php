<?php
require_once 'config.php';

// Check if user is logged in and is not admin
if (!isLoggedIn() || isAdmin()) {
    redirect('login.php');
}

$success = '';
$error = '';

// Get user's cart
try {
    // Get cart ID
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT cart_id FROM carts WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        // Create cart if it doesn't exist
        $stmt = $conn->prepare("INSERT INTO carts (user_id) VALUES (:user_id)");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $cart_id = $conn->lastInsertId();
    } else {
        $cart_id = $stmt->fetch(PDO::FETCH_ASSOC)['cart_id'];
    }
    
    // Get cart items
    $stmt = $conn->prepare("SELECT ci.*, p.name, p.price, p.image_url, p.stock_quantity 
                           FROM cart_items ci 
                           JOIN products p ON ci.product_id = p.product_id 
                           WHERE ci.cart_id = :cart_id");
    $stmt->bindParam(':cart_id', $cart_id);
    $stmt->execute();
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate cart total
    $cart_total = 0;
    foreach ($cart_items as $item) {
        $cart_total += $item['price'] * $item['quantity'];
    }
    
} catch(PDOException $e) {
    $error = 'Error fetching cart: ' . $e->getMessage();
}

// Remove item from cart
if (isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    $item_id = $_GET['remove'];
    
    try {
        $stmt = $conn->prepare("DELETE FROM cart_items WHERE item_id = :item_id AND cart_id = :cart_id");
        $stmt->bindParam(':item_id', $item_id);
        $stmt->bindParam(':cart_id', $cart_id);
        $stmt->execute();
        
        $success = 'Item removed from cart.';
        
        // Redirect to refresh the page
        redirect('cart.php');
    } catch(PDOException $e) {
        $error = 'Error removing item: ' . $e->getMessage();
    }
}

include 'header.php';
?>

<h2>Your Shopping Cart</h2>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="cart-container">
    <?php if (empty($cart_items)): ?>
        <div class="empty-cart">
            <p>Your cart is empty.</p>
            <a href="products.php" class="btn">Browse Products</a>
        </div>
    <?php else: ?>
        <div class="cart-items">
            <?php foreach ($cart_items as $item): ?>
                <div class="cart-item">
                    <div class="cart-item-image">
                        <?php if (!empty($item['image_url'])): ?>
                            <img src="<?php echo $item['image_url']; ?>" alt="<?php echo $item['name']; ?>">
                        <?php else: ?>
                            <img src="images/no-image.jpg" alt="No Image">
                        <?php endif; ?>
                    </div>
                    
                    <div class="cart-item-details">
                        <h3><?php echo $item['name']; ?></h3>
                        <p class="item-price">$<?php echo number_format($item['price'], 2); ?></p>
                    </div>
                    
                    <div class="cart-item-actions">
                        <div class="quantity-control">
                            <button type="button" class="quantity-btn decrement">-</button>
                            <input type="number" class="cart-quantity" data-item-id="<?php echo $item['item_id']; ?>" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock_quantity']; ?>">
                            <button type="button" class="quantity-btn increment">+</button>
                        </div>
                        
                        <div class="item-subtotal" id="item-subtotal-<?php echo $item['item_id']; ?>">
                            $<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                        </div>
                        
                        <a href="cart.php?remove=<?php echo $item['item_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to remove this item?')">Remove</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="cart-summary">
            <div class="cart-total">
                <h3>Cart Total: <span id="cart-total">$<?php echo number_format($cart_total, 2); ?></span></h3>
            </div>
            
            <div class="cart-actions">
                <a href="products.php" class="btn">Continue Shopping</a>
                <a href="checkout.php" class="btn btn-success">Proceed to Checkout</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>