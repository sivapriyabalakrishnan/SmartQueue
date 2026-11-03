<?php
include 'db.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Get today's statistics
$today = date('Y-m-d');
$queue_today = $conn->query("SELECT COUNT(*) as count FROM queue WHERE DATE(checkin_time) = '$today'")->fetch_assoc()['count'];
$queue_completed = $conn->query("SELECT COUNT(*) as count FROM queue WHERE DATE(checkin_time) = '$today' AND status = 'completed'")->fetch_assoc()['count'];
$appointments_today = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE appointment_date = '$today'")->fetch_assoc()['count'];
$total_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'")->fetch_assoc()['count'];

// Get weekly statistics
$week_start = date('Y-m-d', strtotime('monday this week'));
$week_queue = $conn->query("SELECT COUNT(*) as count FROM queue WHERE DATE(checkin_time) >= '$week_start'")->fetch_assoc()['count'];
$week_appointments = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE appointment_date >= '$week_start'")->fetch_assoc()['count'];

// Get monthly statistics
$month_start = date('Y-m-01');
$month_queue = $conn->query("SELECT COUNT(*) as count FROM queue WHERE DATE(checkin_time) >= '$month_start'")->fetch_assoc()['count'];
$month_appointments = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE appointment_date >= '$month_start'")->fetch_assoc()['count'];

// Get popular services
$popular_services = $conn->query("
    SELECT s.service_name, COUNT(q.id) as count 
    FROM services s 
    LEFT JOIN queue q ON s.id = q.service_id 
    GROUP BY s.id 
    ORDER BY count DESC 
    LIMIT 5
");

// Get recent activity
$recent_queue = $conn->query("
    SELECT q.*, u.name as user_name, s.service_name 
    FROM queue q 
    JOIN users u ON q.user_id = u.id 
    JOIN services s ON q.service_id = s.id 
    ORDER BY q.checkin_time DESC 
    LIMIT 10
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Admin Panel</title>
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .stat-title {
            font-size: 0.875rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-icon.primary {
            background: linear-gradient(135deg, #800020, #5c0016);
        }

        .stat-icon.success {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .stat-icon.info {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
        }

        .stat-icon.purple {
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

        .period-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .tab {
            padding: 0.75rem 1.5rem;
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 0.5rem;
            cursor: pointer;
            font-weight: 600;
            color: #64748b;
            transition: all 0.3s ease;
        }

        .tab.active {
            background: linear-gradient(135deg, #800020, #5c0016);
            color: white;
            border-color: #800020;
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

        .service-bar {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .service-name {
            width: 150px;
            font-weight: 600;
            color: #0f172a;
        }

        .bar-container {
            flex: 1;
            height: 30px;
            background: #e2e8f0;
            border-radius: 0.375rem;
            overflow: hidden;
        }

        .bar-fill {
            height: 100%;
            background: linear-gradient(135deg, #800020, #7c3aed);
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-right: 0.75rem;
            color: white;
            font-weight: 600;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <nav class="admin-nav">
        <div class="admin-brand">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            <h1>Reports & Analytics</h1>
        </div>
        <a href="admin.php" class="back-btn">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Admin Panel
        </a>
    </nav>

    <div class="container">
        <div class="period-tabs">
            <div class="tab active" onclick="showPeriod('today')">Today</div>
            <div class="tab" onclick="showPeriod('week')">This Week</div>
            <div class="tab" onclick="showPeriod('month')">This Month</div>
        </div>

        <div id="today-stats">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Queue Today</span>
                        <div class="stat-icon primary">
                            <svg width="24" height="24" fill="none" stroke="white" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $queue_today; ?></div>
                    <div class="stat-label">Total queue entries</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Completed</span>
                        <div class="stat-icon success">
                            <svg width="24" height="24" fill="none" stroke="white" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $queue_completed; ?></div>
                    <div class="stat-label">Served customers</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Appointments</span>
                        <div class="stat-icon info">
                            <svg width="24" height="24" fill="none" stroke="white" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $appointments_today; ?></div>
                    <div class="stat-label">Today's bookings</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Total Users</span>
                        <div class="stat-icon purple">
                            <svg width="24" height="24" fill="none" stroke="white" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $total_users; ?></div>
                    <div class="stat-label">Registered users</div>
                </div>
            </div>
        </div>

        <div class="card">
            <h2 class="card-title">Popular Services</h2>
            <?php if ($popular_services->num_rows > 0): 
                $max_count = 0;
                $services_data = [];
                while ($service = $popular_services->fetch_assoc()) {
                    $services_data[] = $service;
                    if ($service['count'] > $max_count) $max_count = $service['count'];
                }
                ?>
                <?php foreach ($services_data as $service): ?>
                    <div class="service-bar">
                        <div class="service-name"><?php echo htmlspecialchars($service['service_name']); ?></div>
                        <div class="bar-container">
                            <div class="bar-fill" style="width: <?php echo $max_count > 0 ? ($service['count'] / $max_count * 100) : 0; ?>%">
                                <?php echo $service['count']; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; color: #64748b; padding: 2rem;">No service data available yet.</p>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2 class="card-title">Recent Activity</h2>
            <?php if ($recent_queue->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Queue #</th>
                            <th>Customer</th>
                            <th>Service</th>
                            <th>Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($item = $recent_queue->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($item['queue_number']); ?></strong></td>
                                <td><?php echo htmlspecialchars($item['user_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['service_name']); ?></td>
                                <td><?php echo date('g:i A', strtotime($item['checkin_time'])); ?></td>
                                <td><?php echo ucfirst($item['status']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: #64748b; padding: 2rem;">No recent activity.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function showPeriod(period) {
            // Update active tab
            document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
            event.target.classList.add('active');
            
            // You can add period-specific data loading here
        }
    </script>
</body>
</html>
