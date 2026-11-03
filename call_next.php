<?php
include 'db.php';
session_start();

header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit();
}

// Get the next person in queue (waiting status, ordered by priority and time)
$sql = "SELECT id, queue_number FROM queue 
        WHERE status = 'waiting' 
        AND DATE(checkin_time) = CURDATE()
        ORDER BY priority DESC, checkin_time ASC 
        LIMIT 1";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $queue_item = $result->fetch_assoc();
    $queue_id = $queue_item['id'];
    $queue_number = $queue_item['queue_number'];
    
    // Update status to in-progress
    $update_sql = "UPDATE queue SET status = 'in-progress', start_time = NOW() WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("i", $queue_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'queue_number' => $queue_number,
            'message' => "Called next: $queue_number"
        ]);
    } else {
        echo json_encode(['error' => 'Failed to update queue status']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['error' => 'No one in queue']);
}

$conn->close();
?>
