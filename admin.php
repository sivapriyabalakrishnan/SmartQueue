<?php
include 'db.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Get admin name
$admin_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Admin';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Smart Queue Manager</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Admin Panel Specific Styles */
        body {
            background: linear-gradient(135deg, #800020 0%, #1a1a1a 100%);
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }

        /* Top Navigation Bar */
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

        .admin-brand svg {
            stroke: #800020;
        }

        .admin-brand h1 {
            font-size: 1.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #800020, #7c3aed);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 0;
        }

        .admin-user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 1rem;
            background: #f8fafc;
            border-radius: 0.75rem;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #800020, #7c3aed);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
        }

        .user-details {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-weight: 600;
            color: #0f172a;
            font-size: 0.9375rem;
        }

        .user-role {
            font-size: 0.75rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-logout {
            padding: 0.625rem 1.25rem;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-logout:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.3);
        }

        /* Container */
        .admin-container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        /* Statistics Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1.75rem;
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .stat-title {
            font-size: 0.875rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-icon.waiting {
            background: linear-gradient(135deg, #800020, #5c0016);
        }

        .stat-icon.progress {
            background: linear-gradient(135deg, #800020, #5c0016);
        }

        .stat-icon.completed {
            background: linear-gradient(135deg, #800020, #5c0016);
        }

        .stat-icon.appointments {
            background: linear-gradient(135deg, #7c3aed, #6d28d9);
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-size: 0.875rem;
            color: #64748b;
        }

        /* Main Content Cards */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            animation: fadeInUp 0.6s ease-out 0.2s both;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f172a;
        }

        .btn-primary {
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #800020, #5c0016);
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(128, 0, 32, 0.3);
        }

        /* Queue Table */
        .queue-table {
            width: 100%;
            border-collapse: collapse;
        }

        .queue-table thead {
            background: #f8fafc;
        }

        .queue-table th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #475569;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e2e8f0;
        }

        .queue-table td {
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
            color: #64748b;
        }

        .queue-table tbody tr {
            transition: all 0.3s ease;
        }

        .queue-table tbody tr:hover {
            background: #f8fafc;
        }

        .queue-number {
            font-weight: 700;
            color: #800020;
            font-size: 1.125rem;
        }

        .customer-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .customer-name {
            font-weight: 600;
            color: #0f172a;
        }

        .customer-service {
            font-size: 0.875rem;
            color: #64748b;
        }

        .status-badge {
            display: inline-block;
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-waiting {
            background: rgba(245, 158, 11, 0.1);
            color: #5c0016;
        }

        .status-in-progress {
            background: rgba(128, 0, 32, 0.1);
            color: #800020;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-action {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-start {
            background: linear-gradient(135deg, #800020, #5c0016);
            color: white;
        }

        .btn-complete {
            background: linear-gradient(135deg, #800020, #5c0016);
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

        /* Quick Actions */
        .quick-actions {
            display: grid;
            gap: 1rem;
        }

        .action-card {
            padding: 1.5rem;
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            border-radius: 0.75rem;
            border-left: 4px solid #800020;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .action-card:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .action-title {
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .action-description {
            font-size: 0.875rem;
            color: #64748b;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #94a3b8;
        }

        .empty-state svg {
            width: 64px;
            height: 64px;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 640px) {
            .admin-nav {
                flex-direction: column;
                gap: 1rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .queue-table {
                font-size: 0.875rem;
            }

            .queue-table th,
            .queue-table td {
                padding: 0.75rem 0.5rem;
            }
        }

        /* Alert Messages */
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideDown 0.5s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="admin-nav">
        <div class="admin-brand">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h1>Admin Panel</h1>
        </div>
        <div class="admin-user-info">
            <div class="user-profile">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($admin_name, 0, 1)); ?>
                </div>
                <div class="user-details">
                    <span class="user-name"><?php echo htmlspecialchars($admin_name); ?></span>
                    <span class="user-role">Administrator</span>
                </div>
            </div>
            <a href="logout.php" class="btn-logout">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                Logout
            </a>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="admin-container">
        <!-- Alert Container -->
        <div id="alertContainer"></div>

        <!-- Statistics Grid -->
        <div class="stats-grid">
            <?php
            // Get statistics
            $waiting = $conn->query("SELECT COUNT(*) as count FROM queue WHERE status='waiting' AND DATE(checkin_time) = CURDATE()")->fetch_assoc()['count'];
            $in_progress = $conn->query("SELECT COUNT(*) as count FROM queue WHERE status='in-progress' AND DATE(checkin_time) = CURDATE()")->fetch_assoc()['count'];
            $completed = $conn->query("SELECT COUNT(*) as count FROM queue WHERE status='completed' AND DATE(checkin_time) = CURDATE()")->fetch_assoc()['count'];
            $appointments = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE appointment_date = CURDATE() AND status IN ('scheduled', 'confirmed')")->fetch_assoc()['count'];
            ?>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Waiting</span>
                    <div class="stat-icon waiting">
                        <svg width="24" height="24" fill="none" stroke="white" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="stat-value"><?php echo $waiting; ?></div>
                <div class="stat-label">Currently in queue</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">In Progress</span>
                    <div class="stat-icon progress">
                        <svg width="24" height="24" fill="none" stroke="white" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                </div>
                <div class="stat-value"><?php echo $in_progress; ?></div>
                <div class="stat-label">Being served</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Completed</span>
                    <div class="stat-icon completed">
                        <svg width="24" height="24" fill="none" stroke="white" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="stat-value"><?php echo $completed; ?></div>
                <div class="stat-label">Served today</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Appointments</span>
                    <div class="stat-icon appointments">
                        <svg width="24" height="24" fill="none" stroke="white" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </div>
                <div class="stat-value"><?php echo $appointments; ?></div>
                <div class="stat-label">Today's bookings</div>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Current Queue -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Current Queue</h2>
                    <button class="btn-primary" onclick="refreshQueue()">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Refresh
                    </button>
                </div>

                <div id="queueTableContainer">
                    <?php
                    $result = $conn->query("
                        SELECT q.*, u.name, u.phone, s.service_name 
                        FROM queue q
                        JOIN users u ON q.user_id = u.id
                        JOIN services s ON q.service_id = s.id
                        WHERE DATE(q.checkin_time) = CURDATE()
                        AND q.status IN ('waiting', 'in-progress')
                        ORDER BY q.priority DESC, q.checkin_time ASC
                    ");

                    if ($result->num_rows > 0):
                    ?>
                        <table class="queue-table">
                            <thead>
                                <tr>
                                    <th>Queue #</th>
                                    <th>Customer</th>
                                    <th>Check-in Time</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <span class="queue-number"><?php echo htmlspecialchars($row['queue_number']); ?></span>
                                    </td>
                                    <td>
                                        <div class="customer-info">
                                            <span class="customer-name"><?php echo htmlspecialchars($row['name']); ?></span>
                                            <span class="customer-service"><?php echo htmlspecialchars($row['service_name']); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo date('g:i A', strtotime($row['checkin_time'])); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $row['status']; ?>">
                                            <?php echo ucfirst(str_replace('-', ' ', $row['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if ($row['status'] == 'waiting'): ?>
                                                <button class="btn-action btn-start" onclick="updateStatus(<?php echo $row['id']; ?>, 'in-progress')">
                                                    Start
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if ($row['status'] == 'in-progress'): ?>
                                                <button class="btn-action btn-complete" onclick="updateStatus(<?php echo $row['id']; ?>, 'completed')">
                                                    Complete
                                                </button>
                                            <?php endif; ?>
                                            
                                            <button class="btn-action btn-cancel" onclick="updateStatus(<?php echo $row['id']; ?>, 'cancelled')">
                                                Cancel
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                            </svg>
                            <p>No active queue items</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Quick Actions</h2>
                </div>

                <div class="quick-actions">
                    <div class="action-card" onclick="callNext()">
                        <div class="action-title">
                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                            Call Next in Queue
                        </div>
                        <div class="action-description">Move the next waiting customer to in-progress</div>
                    </div>

                    <div class="action-card" onclick="location.href='appointments.php'">
                        <div class="action-title">
                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            View Appointments
                        </div>
                        <div class="action-description">Manage today's scheduled appointments</div>
                    </div>

                    <div class="action-card" onclick="location.href='services.php'">
                        <div class="action-title">
                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            Manage Services
                        </div>
                        <div class="action-description">Add, edit, or remove services</div>
                    </div>

                    <div class="action-card" onclick="location.href='reports.php'">
                        <div class="action-title">
                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            View Reports
                        </div>
                        <div class="action-description">Analytics and performance metrics</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Update queue status
        function updateStatus(queueId, status) {
            if (!confirm(`Are you sure you want to ${status === 'cancelled' ? 'cancel' : status === 'completed' ? 'complete' : 'start'} this queue item?`)) {
                return;
            }

            fetch('update_queue_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `queue_id=${queueId}&status=${status}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Queue status updated successfully', 'success');
                    refreshQueue();
                } else {
                    showAlert(data.error || 'Failed to update status', 'error');
                }
            })
            .catch(error => {
                showAlert('An error occurred', 'error');
                console.error('Error:', error);
            });
        }

        // Call next in queue
        function callNext() {
            fetch('call_next.php', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(`Called next: ${data.queue_number}`, 'success');
                    refreshQueue();
                } else {
                    showAlert(data.error || 'No one in queue', 'error');
                }
            })
            .catch(error => {
                showAlert('An error occurred', 'error');
                console.error('Error:', error);
            });
        }

        // Refresh queue display
        function refreshQueue() {
            location.reload();
        }

        // Show alert message
        function showAlert(message, type) {
            const alertContainer = document.getElementById('alertContainer');
            const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
            
            const alert = document.createElement('div');
            alert.className = `alert ${alertClass}`;
            alert.innerHTML = `
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                    ${type === 'success' ? 
                        '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>' :
                        '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>'
                    }
                </svg>
                <span>${message}</span>
            `;
            
            alertContainer.appendChild(alert);
            
            setTimeout(() => {
                alert.style.animation = 'fadeOut 0.5s ease-out';
                setTimeout(() => alert.remove(), 500);
            }, 5000);
        }

        // Auto-refresh every 30 seconds
        setInterval(refreshQueue, 30000);
    </script>
</body>
</html>