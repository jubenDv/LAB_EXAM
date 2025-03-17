<?php
require_once 'config.php';

// Check if user is logged in and is not admin
if (!isLoggedIn() || isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Please log in to update cart']);
    exit;
}

// Check if required parameters are provided
if (!isset($_POST['item_id']) || !isset($_POST['quantity'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$item_id = intval($_POST['item_id']);
$quantity = intval($_POST['quantity']);

// Validate quantity
if ($quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid quantity']);
    exit;
}

try {
    // Get user's cart
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT cart_id FROM carts WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        echo json_encode(['success' => false, 'message' => 'Cart not found']);
        exit;
    }
    
    $cart_id = $stmt->fetch(PDO::FETCH_ASSOC)['cart_id'];
    
    // Check if item exists in cart
    $stmt = $conn->prepare("SELECT ci.item_id, p.product_id, p.price, p.stock_quantity 
                           FROM cart_items ci 
                           JOIN products p ON ci.product_id = p.product_id 
                           WHERE ci.item_id = :item_id AND ci.cart_id = :cart_id");
    $stmt->bindParam(':item_id', $item_id);
    $stmt->bindParam(':cart_id', $cart_id);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        echo json_encode(['success' => false, 'message' => 'Item not found in cart']);
        exit;
    }
    
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if quantity is available
    if ($quantity > $item['stock_quantity']) {
        $quantity = $item['stock_quantity'];
    }
    
    // Update item quantity
    $stmt = $conn->prepare("UPDATE cart_items SET quantity = :quantity WHERE item_id = :item_id");
    $stmt->bindParam(':quantity', $quantity);
    $stmt->bindParam(':item_id', $item_id);
    $stmt->execute();
    
    // Calculate item subtotal
    $item_subtotal = $item['price'] * $quantity;
    
    // Calculate cart total
    $stmt = $conn->prepare("SELECT ci.quantity, p.price 
                           FROM cart_items ci 
                           JOIN products p ON ci.product_id = p.product_id 
                           WHERE ci.cart_id = :cart_id");
    $stmt->bindParam(':cart_id', $cart_id);
    $stmt->execute();
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $cart_total = 0;
    foreach ($cart_items as $cart_item) {
        $cart_total += $cart_item['price'] * $cart_item['quantity'];
    }
    
    echo json_encode([
        'success' => true, 
        'itemSubtotal' => '$' . number_format($item_subtotal, 2), 
        'cartTotal' => '$' . number_format($cart_total, 2)
    ]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>