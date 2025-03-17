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
        redirect('cart.php');
    }
    
    $cart_id = $stmt->fetch(PDO::FETCH_ASSOC)['cart_id'];
    
    // Get cart items
    $stmt = $conn->prepare("SELECT ci.*, p.name, p.price, p.image_url, p.stock_quantity 
                           FROM cart_items ci 
                           JOIN products p ON ci.product_id = p.product_id 
                           WHERE ci.cart_id = :cart_id");
    $stmt->bindParam(':cart_id', $cart_id);
    $stmt->execute();
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check if cart is empty
    if (empty($cart_items)) {
        redirect('cart.php');
    }
    
    // Calculate cart total
    $cart_total = 0;
    foreach ($cart_items as $item) {
        $cart_total += $item['price'] * $item['quantity'];
    }
    
    // Get delivery services
    $stmt = $conn->query("SELECT * FROM delivery_services WHERE is_available = 1 ORDER BY service_name");
    $delivery_services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error = 'Error fetching cart: ' . $e->getMessage();
}

// Process checkout form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $delivery_address = sanitize($_POST['delivery_address']);
    $contact_number = sanitize($_POST['contact_number']);
    $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
    
    // Validate input
    if (empty($delivery_address) || empty($contact_number)) {
        $error = 'Please fill in all required fields.';
    } else {
        try {
            // Start transaction
            $conn->beginTransaction();
            
            // Create order
            $stmt = $conn->prepare("INSERT INTO orders (user_id, service_id, total_amount, delivery_address, contact_number) 
                                   VALUES (:user_id, :service_id, :total_amount, :delivery_address, :contact_number)");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':service_id', $service_id);
            $stmt->bindParam(':total_amount', $cart_total);
            $stmt->bindParam(':delivery_address', $delivery_address);
            $stmt->bindParam(':contact_number', $contact_number);
            $stmt->execute();
            
            $order_id = $conn->lastInsertId();
            
            // Add order items
            foreach ($cart_items as $item) {
                $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) 
                                       VALUES (:order_id, :product_id, :quantity, :price)");
                $stmt->bindParam(':order_id', $order_id);
                $stmt->bindParam(':product_id', $item['product_id']);
                $stmt->bindParam(':quantity', $item['quantity']);
                $stmt->bindParam(':price', $item['price']);
                $stmt->execute();
                
                // Update product stock
                $new_stock = $item['stock_quantity'] - $item['quantity'];
                $stmt = $conn->prepare("UPDATE products SET stock_quantity = :stock_quantity WHERE product_id = :product_id");
                $stmt->bindParam(':stock_quantity', $new_stock);
                $stmt->bindParam(':product_id', $item['product_id']);
                $stmt->execute();
            }
            
            // Clear cart
            $stmt = $conn->prepare("DELETE FROM cart_items WHERE cart_id = :cart_id");
            $stmt->bindParam(':cart_id', $cart_id);
            $stmt->execute();
            
            // Commit transaction
            $conn->commit();
            
            // Redirect to order confirmation
            redirect('order-confirmation.php?id=' . $order_id);
            
        } catch(PDOException $e) {
            // Rollback transaction on error
            $conn->rollBack();
            $error = 'Error processing order: ' . $e->getMessage();
        }
    }
}

include 'header.php';
?>

<h2>Checkout</h2>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="checkout-container">
    <div class="checkout-summary">
        <h3>Order Summary</h3>
        
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
                        <h4><?php echo $item['name']; ?></h4>
                        <p>$<?php echo number_format($item['price'], 2); ?> x <?php echo $item['quantity']; ?></p>
                    </div>
                    
                    <div class="cart-item-total">
                        $<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="cart-total">
            <h4>Total: $<?php echo number_format($cart_total, 2); ?></h4>
        </div>
    </div>
    
    <div class="checkout-form">
        <h3>Delivery Information</h3>
        
        <form method="post" action="">
            <div class="form-group">
                <label for="delivery_address">Delivery Address</label>
                <textarea id="delivery_address" name="delivery_address" class="form-control" rows="3" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="contact_number">Contact Number</label>
                <input type="text" id="contact_number" name="contact_number" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="location">Location (for delivery service search)</label>
                <input type="text" id="location" class="form-control" onkeyup="loadDeliveryServices(this.value)">
                <small>Enter your area or neighborhood to find available delivery services</small>
            </div>
            
            <div class="form-group">
                <label>Select Delivery Service</label>
                <div id="delivery-services" class="delivery-services-container">
                    <?php if (empty($delivery_services)): ?>
                        <p>No delivery services available.</p>
                    <?php else: ?>
                        <?php foreach ($delivery_services as $service): ?>
                            <div class="delivery-service-option">
                                <input type="radio" name="service_id" id="service-<?php echo $service['service_id']; ?>" value="<?php echo $service['service_id']; ?>" required>
                                <label for="service-<?php echo $service['service_id']; ?>">
                                    <strong><?php echo $service['service_name']; ?></strong> - $<?php echo number_format($service['base_price'], 2); ?>
                                    <p><?php echo $service['description']; ?></p>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-success">Place Order</button>
                <a href="cart.php" class="btn">Back to Cart</a>
            </div>
        </form>
    </div>
</div>

<script>
// Function to load delivery services based on location
function loadDeliveryServices(location) {
    if (location.length < 2) return; // Only search if at least 2 characters
    
    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'get-delivery-services.php?location=' + encodeURIComponent(location), true);
    
    xhr.onload = function() {
        if (this.status === 200) {
            try {
                const response = JSON.parse(this.responseText);
                const servicesContainer = document.getElementById('delivery-services');
                
                servicesContainer.innerHTML = '';
                
                if (response.success && response.services && response.services.length > 0) {
                    response.services.forEach(service => {
                        const serviceDiv = document.createElement('div');
                        serviceDiv.className = 'delivery-service-option';
                        serviceDiv.innerHTML = `
                            <input type="radio" name="service_id" id="service-${service.service_id}" value="${service.service_id}" required>
                            <label for="service-${service.service_id}">
                                <strong>${service.service_name}</strong> - $${parseFloat(service.price).toFixed(2)}
                                <p>${service.description}</p>
                                <p>Estimated delivery time: ${service.estimated_time}</p>
                            </label>
                        `;
                        servicesContainer.appendChild(serviceDiv);
                    });
                } else {
                    servicesContainer.innerHTML = '<p>No delivery services available for this location.</p>';
                }
            } catch (e) {
                console.error('Error parsing JSON response:', e);
            }
        }
    };
    
    xhr.send();
}
</script>

<?php include 'footer.php'; ?>