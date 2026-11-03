<?php
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Redirect admins to admin panel
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: admin.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'User';

// Initialize variables
$current_queue = null;
$total_queues = 0;
$total_appointments = 0;
$appointments = null;

// Try to get statistics (with error handling)
try {
    // Get total queues
    $result = $conn->query("SELECT COUNT(*) as count FROM queue WHERE user_id = $user_id");
    if ($result) {
        $row = $result->fetch_assoc();
        $total_queues = $row['count'];
    }
} catch (Exception $e) {
    // Table doesn't exist yet
    $total_queues = 0;
}

try {
    // Get total appointments
    $result = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE user_id = $user_id");
    if ($result) {
        $row = $result->fetch_assoc();
        $total_appointments = $row['count'];
    }
} catch (Exception $e) {
    // Table doesn't exist yet
    $total_appointments = 0;
}

// Try to get current queue status
try {
    $queue_query = "SELECT q.*, s.service_name, s.estimated_time 
                    FROM queue q 
                    JOIN services s ON q.service_id = s.id 
                    WHERE q.user_id = ? AND q.status IN ('waiting', 'in-progress') 
                    ORDER BY q.checkin_time DESC LIMIT 1";
    
    if ($stmt = $conn->prepare($queue_query)) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $current_queue = $result->fetch_assoc();
        $stmt->close();
    }
} catch (Exception $e) {
    // Table doesn't exist yet
    $current_queue = null;
}

// Try to get appointments
try {
    $appointments_query = "SELECT a.*, s.service_name 
                           FROM appointments a 
                           JOIN services s ON a.service_id = s.id 
                           WHERE a.user_id = ? AND a.status IN ('scheduled', 'confirmed') 
                           AND a.appointment_date >= CURDATE()
                           ORDER BY a.appointment_date, a.appointment_time LIMIT 3";
    
    if ($stmt = $conn->prepare($appointments_query)) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $appointments = $stmt->get_result();
        $stmt->close();
    }
} catch (Exception $e) {
    // Table doesn't exist yet
    $appointments = null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Smart Queue Manager</title>
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

        .nav-links {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .nav-links a {
            color: #0f172a;
            text-decoration: none;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }

        .nav-links a:hover {
            background: #f1f5f9;
            color: #800020;
        }

        .nav-user {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: #f8fafc;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 600;
            color: #0f172a;
        }

        .btn-logout {
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border: none;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-logout:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(239, 68, 68, 0.3);
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .welcome-section {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 1rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .welcome-section h1 {
            font-size: 2rem;
            color: #0f172a;
            margin-bottom: 0.5rem;
        }

        .welcome-section p {
            color: #64748b;
            font-size: 1.125rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 1.5rem;
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        .stat-label {
            font-size: 0.875rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            color: #800020;
            margin-bottom: 0.5rem;
        }

        .stat-description {
            font-size: 0.875rem;
            color: #94a3b8;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .action-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: all 0.3s ease;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        .action-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 1rem;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .action-icon.primary {
            background: linear-gradient(135deg, #800020, #5c0016);
        }

        .action-icon.secondary {
            background: linear-gradient(135deg, #1a1a1a, #000000);
        }

        .action-card h3 {
            font-size: 1.25rem;
            color: #0f172a;
            margin-bottom: 0.5rem;
        }

        .action-card p {
            color: #64748b;
            margin-bottom: 1.5rem;
        }

        .btn-action {
            padding: 0.75rem 2rem;
            background: linear-gradient(135deg, #800020, #5c0016);
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(128, 0, 32, 0.3);
        }

        .status-section {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 1rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .status-section h2 {
            font-size: 1.5rem;
            color: #0f172a;
            margin-bottom: 1.5rem;
        }

        .queue-status {
            padding: 1.5rem;
            background: linear-gradient(135deg, #800020, #5c0016);
            color: white;
            border-radius: 0.75rem;
            text-align: center;
        }

        .queue-number {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        @media (max-width: 768px) {
            .stats-grid, .quick-actions {
                grid-template-columns: 1fr;
            }

            .welcome-section h1 {
                font-size: 1.5rem;
            }

            .user-nav {
                flex-direction: column;
                gap: 1rem;
            }

            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
                gap: 0.75rem;
            }

            .nav-links a {
                font-size: 0.875rem;
                padding: 0.4rem 0.8rem;
            }
        }
    </style>
</head>
<body>
    <nav class="user-nav">
        <div class="nav-brand">
            <svg width="35" height="35" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Smart Queue Manager
        </div>
        <div class="nav-links">
            <a href="dashboard.php">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: inline-block; vertical-align: middle; margin-right: 4px;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Dashboard
            </a>
            <a href="profile.php">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: inline-block; vertical-align: middle; margin-right: 4px;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                Profile
            </a>
            <a href="my_queue.php">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: inline-block; vertical-align: middle; margin-right: 4px;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                My Queue
            </a>
            <a href="my_appointments.php">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: inline-block; vertical-align: middle; margin-right: 4px;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                My Appointments
            </a>
        </div>
        <div class="nav-user">
            <div class="user-info">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <?php echo htmlspecialchars($user_name); ?>
            </div>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="welcome-section">
            <h1>Welcome back, <?php echo htmlspecialchars($user_name); ?>! 👋</h1>
            <p>Manage your queue and appointments from your dashboard</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Queues</div>
                <div class="stat-value"><?php echo $total_queues; ?></div>
                <div class="stat-description">All time queue entries</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Appointments</div>
                <div class="stat-value"><?php echo $total_appointments; ?></div>
                <div class="stat-description">All appointments booked</div>
            </div>
        </div>

        <?php if ($current_queue): ?>
        <div class="status-section">
            <h2>Your Current Queue Status</h2>
            <div class="queue-status">
                <div class="queue-number"><?php echo htmlspecialchars($current_queue['queue_number']); ?></div>
                <p style="font-size: 1.25rem; margin: 0;">
                    <?php echo htmlspecialchars($current_queue['service_name']); ?>
                </p>
                <p style="margin: 0.5rem 0 0 0;">
                    Status: <?php echo ucfirst($current_queue['status']); ?> | 
                    Estimated wait: ~<?php echo $current_queue['estimated_wait_time']; ?> min
                </p>
            </div>
        </div>
        <?php endif; ?>

        <div class="quick-actions">
            <div class="action-card">
                <div class="action-icon primary">
                    <svg width="40" height="40" fill="none" stroke="white" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <h3>Join Queue</h3>
                <p>Get your queue number and track your position in real-time</p>
                <a href="join-queue.php" class="btn-action">Join Now</a>
            </div>

            <div class="action-card">
                <div class="action-icon secondary">
                    <svg width="40" height="40" fill="none" stroke="white" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3>Book Appointment</h3>
                <p>Schedule your visit in advance and skip the wait</p>
                <a href="book-appointment.php" class="btn-action">Book Now</a>
            </div>
        </div>

        <?php if ($appointments && $appointments->num_rows > 0): ?>
        <div class="status-section">
            <h2>Upcoming Appointments</h2>
            <?php while ($apt = $appointments->fetch_assoc()): ?>
            <div style="padding: 1rem; background: #f8fafc; border-radius: 0.5rem; margin-bottom: 1rem;">
                <strong><?php echo htmlspecialchars($apt['service_name']); ?></strong><br>
                <small><?php echo date('F j, Y', strtotime($apt['appointment_date'])); ?> at <?php echo date('g:i A', strtotime($apt['appointment_time'])); ?></small>
            </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>