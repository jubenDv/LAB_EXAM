<?php
require_once 'config.php';

// Check if user is logged in and is not admin
if (!isLoggedIn() || isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Please log in to add items to cart']);
    exit;
}

// Check if required parameters are provided
if (!isset($_POST['product_id']) || !isset($_POST['quantity'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$product_id = intval($_POST['product_id']);
$quantity = intval($_POST['quantity']);

// Validate quantity
if ($quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid quantity']);
    exit;
}

try {
    // Check if product exists and is available
    $stmt = $conn->prepare("SELECT product_id, stock_quantity FROM products WHERE product_id = :product_id AND is_available = 1");
    $stmt->bindParam(':product_id', $product_id);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        echo json_encode(['success' => false, 'message' => 'Product not available']);
        exit;
    }
    
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if quantity is available
    if ($quantity > $product['stock_quantity']) {
        echo json_encode(['success' => false, 'message' => 'Not enough stock available']);
        exit;
    }
    
    // Get user's cart
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
    
    // Check if product already exists in cart
    $stmt = $conn->prepare("SELECT item_id, quantity FROM cart_items WHERE cart_id = :cart_id AND product_id = :product_id");
    $stmt->bindParam(':cart_id', $cart_id);
    $stmt->bindParam(':product_id', $product_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        // Update quantity if product already in cart
        $cart_item = $stmt->fetch(PDO::FETCH_ASSOC);
        $new_quantity = $cart_item['quantity'] + $quantity;
        
        // Check if new quantity exceeds stock
        if ($new_quantity > $product['stock_quantity']) {
            $new_quantity = $product['stock_quantity'];
        }
        
        $stmt = $conn->prepare("UPDATE cart_items SET quantity = :quantity WHERE item_id = :item_id");
        $stmt->bindParam(':quantity', $new_quantity);
        $stmt->bindParam(':item_id', $cart_item['item_id']);
        $stmt->execute();
    } else {
        // Add new item to cart
        $stmt = $conn->prepare("INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (:cart_id, :product_id, :quantity)");
        $stmt->bindParam(':cart_id', $cart_id);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->execute();
    }
    
    // Get cart count for response
    $stmt = $conn->prepare("SELECT SUM(quantity) as count FROM cart_items WHERE cart_id = :cart_id");
    $stmt->bindParam(':cart_id', $cart_id);
    $stmt->execute();
    $cart_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo json_encode(['success' => true, 'cartCount' => $cart_count]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>