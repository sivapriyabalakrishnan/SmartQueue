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
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_appointment'])) {
    $service_id = intval($_POST['service_id']);
    $appointment_date = sanitize_input($_POST['appointment_date']);
    $appointment_time = sanitize_input($_POST['appointment_time']);
    $notes = sanitize_input($_POST['notes']);
    
    // Insert appointment
    $sql = "INSERT INTO appointments (user_id, service_id, appointment_date, appointment_time, status, notes) 
            VALUES (?, ?, ?, ?, 'scheduled', ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisss", $user_id, $service_id, $appointment_date, $appointment_time, $notes);
    
    if ($stmt->execute()) {
        // Redirect to my appointments page after successful booking
        header("Location: my_appointments.php?booked=1");
        exit();
    } else {
        $error = "Failed to book appointment. Please try again.";
    }
}

// Get minimum date (tomorrow)
$min_date = date('Y-m-d', strtotime('+1 day'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - Smart Queue Manager</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body style="background: linear-gradient(135deg, #800020 0%, #1a1a1a 100%); min-height: 100vh; padding: 2rem;">
    
    <div style="max-width: 600px; margin: 0 auto; background: white; padding: 2rem; border-radius: 1rem; box-shadow: 0 20px 60px rgba(0,0,0,0.2);">
        <h1 style="color: #0f172a; margin-bottom: 0.5rem;">Book Appointment</h1>
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
            
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #0f172a;">Appointment Date:</label>
                <input type="date" name="appointment_date" min="<?php echo $min_date; ?>" required style="width: 100%; padding: 0.75rem; border: 2px solid #e2e8f0; border-radius: 0.5rem; font-size: 1rem;">
            </div>
            
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #0f172a;">Appointment Time:</label>
                <input type="time" name="appointment_time" min="09:00" max="18:00" required style="width: 100%; padding: 0.75rem; border: 2px solid #e2e8f0; border-radius: 0.5rem; font-size: 1rem;">
                <small style="color: #64748b;">Business hours: 9:00 AM - 6:00 PM</small>
            </div>
            
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #0f172a;">Notes (Optional):</label>
                <textarea name="notes" rows="3" style="width: 100%; padding: 0.75rem; border: 2px solid #e2e8f0; border-radius: 0.5rem; font-size: 1rem;" placeholder="Any special requirements..."></textarea>
            </div>
            
            <button type="submit" name="book_appointment" style="width: 100%; padding: 1rem; background: linear-gradient(135deg, #800020, #5c0016); color: white; border: none; border-radius: 0.5rem; font-size: 1.125rem; font-weight: 700; cursor: pointer; transition: all 0.3s ease;">
                Book Appointment
            </button>
        </form>
    </div>
    
</body>
</html>
