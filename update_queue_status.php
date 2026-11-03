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

$queue_id = isset($_POST['queue_id']) ? intval($_POST['queue_id']) : 0;
$status = isset($_POST['status']) ? sanitize_input($_POST['status']) : '';

if ($queue_id <= 0) {
    echo json_encode(['error' => 'Invalid queue ID']);
    exit();
}

$valid_statuses = ['waiting', 'in-progress', 'completed', 'cancelled'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['error' => 'Invalid status']);
    exit();
}

// Build update query based on status
$update_fields = "status = ?";
$params = [$status];
$types = "s";

if ($status === 'in-progress') {
    $update_fields .= ", start_time = NOW()";
} elseif ($status === 'completed') {
    $update_fields .= ", end_time = NOW()";
}

$sql = "UPDATE queue SET $update_fields WHERE id = ?";
$types .= "i";
$params[] = $queue_id;

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Queue status updated successfully'
    ]);
} else {
    echo json_encode(['error' => 'Failed to update queue status']);
}

$stmt->close();
$conn->close();
?>