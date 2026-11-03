<?php
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Check if redirected after booking appointment
if (isset($_GET['booked']) && $_GET['booked'] == 1) {
    $message = "Appointment booked successfully! Check your upcoming appointments below.";
}

// Handle cancel appointment
if (isset($_POST['cancel_appointment'])) {
    $appointment_id = intval($_POST['appointment_id']);
    $stmt = $conn->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ? AND user_id = ? AND status IN ('scheduled', 'confirmed')");
    $stmt->bind_param("ii", $appointment_id, $user_id);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $message = "Appointment cancelled successfully!";
    } else {
        $error = "Failed to cancel appointment or appointment already processed.";
    }
}

// Get upcoming appointments
$upcoming_query = "SELECT a.*, s.service_name, s.estimated_time 
                   FROM appointments a 
                   JOIN services s ON a.service_id = s.id 
                   WHERE a.user_id = ? AND a.status IN ('scheduled', 'confirmed') 
                   AND a.appointment_date >= CURDATE()
                   ORDER BY a.appointment_date, a.appointment_time";
$stmt = $conn->prepare($upcoming_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$upcoming = $stmt->get_result();

// Get past appointments
$past_query = "SELECT a.*, s.service_name 
               FROM appointments a 
               JOIN services s ON a.service_id = s.id 
               WHERE a.user_id = ? AND (a.status IN ('completed', 'cancelled') OR a.appointment_date < CURDATE())
               ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT 10";
$stmt = $conn->prepare($past_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$past = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments - Smart Queue Manager</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #800020 0%, #1a1a1a 100%);
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }

        .user-nav {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.25rem;
            font-weight: 800;
            color: #800020;
        }

        .btn-back {
            padding: 0.5rem 1rem;
            background: #f8fafc;
            color: #800020;
            border: 2px solid #800020;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-back:hover {
            background: #800020;
            color: white;
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 0.75rem;
            margin-bottom: 2rem;
        }

        .alert-success {
            background: rgba(128, 0, 32, 0.1);
            border-left: 4px solid #800020;
            color: #ffffff;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border-left: 4px solid #ef4444;
            color: #b91c1c;
        }

        .section-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            gap: 1rem;
        }

        .section-header h2 {
            font-size: 1.5rem;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .btn-book {
            padding: 0.625rem 1.5rem;
            background: linear-gradient(135deg, #800020, #5c0016);
            color: white;
            text-decoration: none;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-book:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(128, 0, 32, 0.3);
        }

        .appointment-card {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 0.75rem;
            margin-bottom: 1rem;
            border-left: 4px solid #800020;
            transition: all 0.3s ease;
        }

        .appointment-card:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .appointment-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .appointment-date {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .date-box {
            background: linear-gradient(135deg, #800020, #5c0016);
            color: white;
            padding: 0.75rem;
            border-radius: 0.5rem;
            text-align: center;
            min-width: 60px;
        }

        .date-day {
            font-size: 1.5rem;
            font-weight: 800;
            line-height: 1;
        }

        .date-month {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .appointment-info h3 {
            font-size: 1.25rem;
            color: #0f172a;
            margin-bottom: 0.25rem;
        }

        .appointment-time {
            color: #64748b;
            font-size: 0.875rem;
        }

        .status-badge {
            padding: 0.375rem 0.875rem;
            border-radius: 2rem;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-scheduled {
            background: rgba(245, 158, 11, 0.2);
            color: #92400e;
        }

        .status-confirmed {
            background: rgba(128, 0, 32, 0.2);
            color: #065f46;
        }

        .status-completed {
            background: rgba(128, 0, 32, 0.2);
            color: #5c0016;
        }

        .status-cancelled {
            background: rgba(239, 68, 68, 0.2);
            color: #b91c1c;
        }

        .appointment-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
            padding: 1rem;
            background: white;
            border-radius: 0.5rem;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            font-size: 0.75rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
        }

        .detail-value {
            font-size: 1rem;
            color: #0f172a;
            font-weight: 600;
        }

        .appointment-actions {
            display: flex;
            gap: 0.75rem;
        }

        .btn-cancel {
            padding: 0.625rem 1.25rem;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.875rem;
        }

        .btn-cancel:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(239, 68, 68, 0.3);
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #64748b;
        }

        .empty-state svg {
            width: 80px;
            height: 80px;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .history-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .history-item:last-child {
            border-bottom: none;
        }

        @media (max-width: 768px) {
            .appointment-header {
                flex-direction: column;
                gap: 1rem;
            }

            .section-header {
                flex-direction: column;
                gap: 1rem;
                align-items: start;
            }
        }
    </style>
</head>
<body>
    <nav class="user-nav">
        <div class="nav-brand">
            <svg width="35" height="35" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            My Appointments
        </div>
        <a href="dashboard.php" class="btn-back">← Back to Dashboard</a>
    </nav>

    <div class="container">
        <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Upcoming Appointments -->
        <div class="section-card">
            <div class="section-header">
                <h2>
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Upcoming Appointments
                </h2>
                <a href="book-appointment.php" class="btn-book">+ Book New</a>
            </div>

            <?php if ($upcoming->num_rows > 0): ?>
                <?php while ($apt = $upcoming->fetch_assoc()): 
                    $date = new DateTime($apt['appointment_date']);
                ?>
                <div class="appointment-card">
                    <div class="appointment-header">
                        <div class="appointment-date">
                            <div class="date-box">
                                <div class="date-day"><?php echo $date->format('d'); ?></div>
                                <div class="date-month"><?php echo $date->format('M'); ?></div>
                            </div>
                            <div class="appointment-info">
                                <h3><?php echo htmlspecialchars($apt['service_name']); ?></h3>
                                <div class="appointment-time">
                                    <?php echo $date->format('l, F j, Y'); ?> at 
                                    <?php echo date('g:i A', strtotime($apt['appointment_time'])); ?>
                                </div>
                            </div>
                        </div>
                        <span class="status-badge status-<?php echo $apt['status']; ?>">
                            <?php echo ucfirst($apt['status']); ?>
                        </span>
                    </div>

                    <?php if ($apt['notes']): ?>
                    <div class="appointment-details">
                        <div class="detail-item">
                            <div class="detail-label">Notes</div>
                            <div class="detail-value"><?php echo htmlspecialchars($apt['notes']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Duration</div>
                            <div class="detail-value">~<?php echo $apt['estimated_time']; ?> minutes</div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="appointment-actions">
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to cancel this appointment?');">
                            <input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>">
                            <button type="submit" name="cancel_appointment" class="btn-cancel">
                                ✕ Cancel Appointment
                            </button>
                        </form>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
            <div class="empty-state">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <h3 style="font-size: 1.25rem; color: #0f172a; margin-bottom: 0.5rem;">No Upcoming Appointments</h3>
                <p>You don't have any scheduled appointments</p>
                <a href="book-appointment.php" class="btn-book" style="margin-top: 1rem; display: inline-block;">Book Appointment Now</a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Appointment History -->
        <div class="section-card">
            <div class="section-header">
                <h2>
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Appointment History
                </h2>
            </div>

            <?php if ($past->num_rows > 0): ?>
                <?php while ($item = $past->fetch_assoc()): ?>
                <div class="history-item">
                    <div>
                        <div style="font-weight: 600; color: #0f172a; margin-bottom: 0.25rem;">
                            <?php echo htmlspecialchars($item['service_name']); ?>
                        </div>
                        <div style="font-size: 0.875rem; color: #64748b;">
                            <?php echo date('M j, Y g:i A', strtotime($item['appointment_date'] . ' ' . $item['appointment_time'])); ?>
                        </div>
                    </div>
                    <span class="status-badge status-<?php echo $item['status']; ?>">
                        <?php echo ucfirst($item['status']); ?>
                    </span>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
            <div class="empty-state">
                <p>No appointment history yet</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>