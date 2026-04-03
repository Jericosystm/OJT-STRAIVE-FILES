<?php
// Use PHP tags, NOT script tags
function recordActivity($conn, $userId, $action, $details) {
    $ip = $_SERVER['REMOTE_ADDR'];
    
    // Prepare the statement
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    
    // Bind parameters: i = integer, s = string
    $stmt->bind_param("isss", $userId, $action, $details, $ip);
    
    // Execute and return result
    return $stmt->execute();
}
?>