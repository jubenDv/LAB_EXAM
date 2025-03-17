<?php
require_once 'config.php';

// Check if location parameter is provided
if (!isset($_GET['location']) || empty($_GET['location'])) {
    echo json_encode(['success' => false, 'message' => 'Location is required']);
    exit;
}

$location = sanitize($_GET['location']);

try {
    // Get delivery services that serve the specified location
    $stmt = $conn->prepare("SELECT ds.service_id, ds.service_name, ds.description, sa.delivery_fee as price, sa.estimated_time 
                           FROM delivery_services ds 
                           JOIN service_areas sa ON ds.service_id = sa.service_id 
                           WHERE ds.is_available = 1 AND sa.area_name LIKE :location 
                           ORDER BY sa.delivery_fee");
    $stmt->bindValue(':location', "%$location%");
    $stmt->execute();
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no specific area matches, get all available services
    if (empty($services)) {
        $stmt = $conn->query("SELECT service_id, service_name, description, base_price as price, 'Varies based on location' as estimated_time 
                             FROM delivery_services 
                             WHERE is_available = 1 
                             ORDER BY base_price");
        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode(['success' => true, 'services' => $services]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>