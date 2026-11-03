<?php
include 'db.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';

// Handle appointment status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $appointment_id = intval($_POST['appointment_id']);
    $status = sanitize_input($_POST['status']);
    
    $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $appointment_id);
    
    if ($stmt->execute()) {
        $message = 'Appointment status updated successfully!';
    } else {
        $error = 'Failed to update appointment status.';
    }
}

// Get all appointments for today
$today = date('Y-m-d');
$appointments_query = "SELECT a.*, u.name as user_name, u.email, u.phone, s.service_name 
                       FROM appointments a 
                       JOIN users u ON a.user_id = u.id 
                       JOIN services s ON a.service_id = s.id 
                       WHERE appointment_date = '$today'
                       ORDER BY a.appointment_time ASC";
$appointments = $conn->query($appointments_query);

// Get upcoming appointments
$upcoming_query = "SELECT a.*, u.name as user_name, s.service_name 
                   FROM appointments a 
                   JOIN users u ON a.user_id = u.id 
                   JOIN services s ON a.service_id = s.id 
                   WHERE appointment_date > '$today'
                   ORDER BY a.appointment_date ASC, a.appointment_time ASC
                   LIMIT 10";
$upcoming = $conn->query($upcoming_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Appointments - Admin Panel</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #800020 0%, #1a1a1a 100%);
            min-height: 100vh;
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }

        .admin-nav {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .admin-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .admin-brand h1 {
            font-size: 1.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #800020, #7c3aed);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 0;
        }

        .back-btn {
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #800020, #5c0016);
            color: white;
            text-decoration: none;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(128, 0, 32, 0.3);
        }

        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 1.5rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f8fafc;
        }

        th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #475569;
            font-size: 0.875rem;
            text-transform: uppercase;
            border-bottom: 2px solid #e2e8f0;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
            color: #64748b;
        }

        tbody tr:hover {
            background: #f8fafc;
        }

        .status-badge {
            display: inline-block;
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-scheduled {
            background: rgba(59, 130, 246, 0.1);
            color: #1e40af;
        }

        .status-confirmed {
            background: rgba(128, 0, 32, 0.1);
            color: #800020;
        }

        .status-completed {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
        }

        .status-cancelled {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
        }

        .btn-action {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 0.25rem;
        }

        .btn-confirm {
            background: linear-gradient(135deg, #800020, #5c0016);
            color: white;
        }

        .btn-complete {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .btn-cancel {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .alert {
            padding: 1rem 1.25rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background: rgba(128, 0, 32, 0.1);
            border-left: 4px solid #800020;
            color: #065f46;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border-left: 4px solid #ef4444;
            color: #b91c1c;
        }

        .customer-info {
            font-weight: 600;
            color: #0f172a;
        }

        .customer-details {
            font-size: 0.875rem;
            color: #64748b;
            margin-top: 0.25rem;
        }
    </style>
</head>
<body>
    <nav class="admin-nav">
        <div class="admin-brand">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <h1>Manage Appointments</h1>
        </div>
        <a href="admin.php" class="back-btn">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Admin Panel
        </a>
    </nav>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Today's Appointments -->
        <div class="card">
            <h2 class="card-title">Today's Appointments (<?php echo date('F j, Y'); ?>)</h2>
            <?php if ($appointments->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Customer</th>
                            <th>Service</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($apt = $appointments->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('g:i A', strtotime($apt['appointment_time'])); ?></td>
                                <td>
                                    <div class="customer-info"><?php echo htmlspecialchars($apt['user_name']); ?></div>
                                    <div class="customer-details">
                                        <?php echo htmlspecialchars($apt['email']); ?><br>
                                        <?php echo htmlspecialchars($apt['phone']); ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($apt['service_name']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $apt['status']; ?>">
                                        <?php echo ucfirst($apt['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>">
                                        <?php if ($apt['status'] == 'scheduled'): ?>
                                            <input type="hidden" name="status" value="confirmed">
                                            <button type="submit" name="update_status" class="btn-action btn-confirm">Confirm</button>
                                        <?php endif; ?>
                                        <?php if ($apt['status'] == 'confirmed'): ?>
                                            <input type="hidden" name="status" value="completed">
                                            <button type="submit" name="update_status" class="btn-action btn-complete">Complete</button>
                                        <?php endif; ?>
                                        <?php if ($apt['status'] != 'cancelled' && $apt['status'] != 'completed'): ?>
                                            <input type="hidden" name="status" value="cancelled">
                                            <button type="submit" name="update_status" class="btn-action btn-cancel" onclick="return confirm('Cancel this appointment?')">Cancel</button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: #64748b; padding: 2rem;">No appointments for today.</p>
            <?php endif; ?>
        </div>

        <!-- Upcoming Appointments -->
        <div class="card">
            <h2 class="card-title">Upcoming Appointments</h2>
            <?php if ($upcoming->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Customer</th>
                            <th>Service</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($apt = $upcoming->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?php echo date('M j, Y', strtotime($apt['appointment_date'])); ?><br>
                                    <small><?php echo date('g:i A', strtotime($apt['appointment_time'])); ?></small>
                                </td>
                                <td class="customer-info"><?php echo htmlspecialchars($apt['user_name']); ?></td>
                                <td><?php echo htmlspecialchars($apt['service_name']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $apt['status']; ?>">
                                        <?php echo ucfirst($apt['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: #64748b; padding: 2rem;">No upcoming appointments.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
