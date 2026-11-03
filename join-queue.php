<?php
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Get all active services
$services = $conn->query("SELECT * FROM services WHERE is_active = 1 ORDER BY service_name");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['join_queue'])) {
    $service_id = intval($_POST['service_id']);
    
    // Check if user already in queue
    $check = $conn->query("SELECT * FROM queue WHERE user_id = $user_id AND status IN ('waiting', 'in-progress') AND DATE(checkin_time) = CURDATE()");
    
    if ($check->num_rows > 0) {
        $error = "You are already in the queue!";
    } else {
        // Generate queue number
        $queue_number = generate_queue_number();
        
        // Calculate wait time
        $waiting = $conn->query("SELECT COUNT(*) as count FROM queue WHERE service_id = $service_id AND status = 'waiting' AND DATE(checkin_time) = CURDATE()")->fetch_assoc()['count'];
        $service_time = $conn->query("SELECT estimated_time FROM services WHERE id = $service_id")->fetch_assoc()['estimated_time'];
        $estimated_wait = $waiting * $service_time;
        
        // Insert into queue
        $sql = "INSERT INTO queue (user_id, service_id, queue_number, status, estimated_wait_time) VALUES (?, ?, ?, 'waiting', ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisi", $user_id, $service_id, $queue_number, $estimated_wait);
        
        if ($stmt->execute()) {
            // Redirect to my queue page after successful join
            header("Location: my_queue.php?joined=1");
            exit();
        } else {
            $error = "Failed to join queue. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Queue - Smart Queue Manager</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body style="background: linear-gradient(135deg, #800020 0%, #1a1a1a 100%); min-height: 100vh; padding: 2rem;">
    
    <div style="max-width: 600px; margin: 0 auto; background: white; padding: 2rem; border-radius: 1rem; box-shadow: 0 20px 60px rgba(0,0,0,0.2);">
        <h1 style="color: #0f172a; margin-bottom: 0.5rem;">Join Queue</h1>
        <p style="color: #64748b; margin-bottom: 2rem;"><a href="dashboard.php" style="color: #800020;">← Back to Dashboard</a></p>
        
        <?php if ($message): ?>
        <div style="padding: 1rem; background: rgba(128, 0, 32, 0.1); border-left: 4px solid #800020; color: #065f46; margin-bottom: 1.5rem; border-radius: 0.5rem;">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div style="padding: 1rem; background: rgba(239, 68, 68, 0.1); border-left: 4px solid #ef4444; color: #b91c1c; margin-bottom: 1.5rem; border-radius: 0.5rem;">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #0f172a;">Select Service:</label>
                <select name="service_id" required style="width: 100%; padding: 0.75rem; border: 2px solid #e2e8f0; border-radius: 0.5rem; font-size: 1rem;">
                    <option value="">Choose a service...</option>
                    <?php while ($service = $services->fetch_assoc()): ?>
                    <option value="<?php echo $service['id']; ?>">
                        <?php echo htmlspecialchars($service['service_name']); ?> 
                        (~<?php echo $service['estimated_time']; ?> min)
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <button type="submit" name="join_queue" style="width: 100%; padding: 1rem; background: linear-gradient(135deg, #800020, #5c0016); color: white; border: none; border-radius: 0.5rem; font-size: 1.125rem; font-weight: 700; cursor: pointer; transition: all 0.3s ease;">
                Join Queue Now
            </button>
        </form>
    </div>
    
</body>
</html>
