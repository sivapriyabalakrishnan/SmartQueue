<?php
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Check if redirected after joining queue
if (isset($_GET['joined']) && $_GET['joined'] == 1) {
    $message = "Successfully joined the queue! Check your queue status below.";
}

// Handle cancel queue
if (isset($_POST['cancel_queue'])) {
    $queue_id = intval($_POST['queue_id']);
    $stmt = $conn->prepare("UPDATE queue SET status = 'cancelled', end_time = NOW() WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $queue_id, $user_id);
    
    if ($stmt->execute()) {
        $message = "Queue cancelled successfully!";
    } else {
        $error = "Failed to cancel queue.";
    }
}

// Handle exit queue (after service starts)
if (isset($_POST['exit_queue'])) {
    $queue_id = intval($_POST['queue_id']);
    $stmt = $conn->prepare("UPDATE queue SET status = 'cancelled', end_time = NOW(), notes = CONCAT(IFNULL(notes, ''), ' [User exited during service]') WHERE id = ? AND user_id = ? AND status = 'in-progress'");
    $stmt->bind_param("ii", $queue_id, $user_id);
    
    if ($stmt->execute()) {
        $message = "You have exited the queue.";
    } else {
        $error = "Failed to exit queue.";
    }
}

// Get active queues
$active_query = "SELECT q.*, s.service_name, s.estimated_time 
                 FROM queue q 
                 JOIN services s ON q.service_id = s.id 
                 WHERE q.user_id = ? AND q.status IN ('waiting', 'in-progress') 
                 ORDER BY q.checkin_time DESC";
$stmt = $conn->prepare($active_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$active_queues = $stmt->get_result();

// Get queue history
$history_query = "SELECT q.*, s.service_name 
                  FROM queue q 
                  JOIN services s ON q.service_id = s.id 
                  WHERE q.user_id = ? AND q.status IN ('completed', 'cancelled') 
                  ORDER BY q.checkin_time DESC LIMIT 10";
$stmt = $conn->prepare($history_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$history = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Queue - Smart Queue Manager</title>
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
            animation: slideDown 0.3s ease-out;
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
        }

        .section-header h2 {
            font-size: 1.5rem;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .queue-card {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 0.75rem;
            margin-bottom: 1rem;
            border-left: 4px solid #800020;
            transition: all 0.3s ease;
        }

        .queue-card:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .queue-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .queue-number {
            font-size: 2rem;
            font-weight: 800;
            color: #800020;
        }

        .status-badge {
            padding: 0.375rem 0.875rem;
            border-radius: 2rem;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-waiting {
            background: rgba(245, 158, 11, 0.2);
            color: #92400e;
        }

        .status-in-progress {
            background: rgba(128, 0, 32, 0.2);
            color: #5c0016;
        }

        .status-completed {
            background: rgba(128, 0, 32, 0.2);
            color: #065f46;
        }

        .status-cancelled {
            background: rgba(239, 68, 68, 0.2);
            color: #b91c1c;
        }

        .queue-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
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

        .queue-actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 0.625rem 1.25rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.875rem;
        }

        .btn-cancel {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }

        .btn-cancel:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(239, 68, 68, 0.3);
        }

        .btn-exit {
            background: linear-gradient(135deg, #800020, #5c0016);
            color: white;
        }

        .btn-exit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(245, 158, 11, 0.3);
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

        .btn-join {
            margin-top: 1rem;
            padding: 0.75rem 2rem;
            background: linear-gradient(135deg, #800020, #5c0016);
            color: white;
            text-decoration: none;
            border-radius: 0.5rem;
            font-weight: 600;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-join:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(128, 0, 32, 0.3);
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
            .queue-header {
                flex-direction: column;
                gap: 1rem;
            }

            .queue-actions {
                width: 100%;
            }

            .btn {
                flex: 1;
            }
        }
    </style>
</head>
<body>
    <nav class="user-nav">
        <div class="nav-brand">
            <svg width="35" height="35" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            My Queue
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

        <!-- Active Queues -->
        <div class="section-card">
            <div class="section-header">
                <h2>
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Active Queue
                </h2>
            </div>

            <?php if ($active_queues->num_rows > 0): ?>
                <?php while ($queue = $active_queues->fetch_assoc()): ?>
                <div class="queue-card">
                    <div class="queue-header">
                        <div class="queue-number"><?php echo htmlspecialchars($queue['queue_number']); ?></div>
                        <span class="status-badge status-<?php echo $queue['status']; ?>">
                            <?php echo ucfirst(str_replace('-', ' ', $queue['status'])); ?>
                        </span>
                    </div>

                    <div class="queue-details">
                        <div class="detail-item">
                            <div class="detail-label">Service</div>
                            <div class="detail-value"><?php echo htmlspecialchars($queue['service_name']); ?></div>
                        </div>

                        <div class="detail-item">
                            <div class="detail-label">Check-in Time</div>
                            <div class="detail-value"><?php echo date('g:i A', strtotime($queue['checkin_time'])); ?></div>
                        </div>

                        <div class="detail-item">
                            <div class="detail-label">Estimated Wait</div>
                            <div class="detail-value">~<?php echo $queue['estimated_wait_time'] ?? 'N/A'; ?> min</div>
                        </div>

                        <?php if ($queue['start_time']): ?>
                        <div class="detail-item">
                            <div class="detail-label">Service Started</div>
                            <div class="detail-value"><?php echo date('g:i A', strtotime($queue['start_time'])); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="queue-actions">
                        <?php if ($queue['status'] == 'waiting'): ?>
                        <!-- Cancel button for waiting queue -->
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to cancel this queue?');">
                            <input type="hidden" name="queue_id" value="<?php echo $queue['id']; ?>">
                            <button type="submit" name="cancel_queue" class="btn btn-cancel">
                                ✕ Cancel Queue
                            </button>
                        </form>
                        <?php endif; ?>

                        <?php if ($queue['status'] == 'in-progress'): ?>
                        <!-- Exit button for in-progress service -->
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to exit? Your service is currently in progress.');">
                            <input type="hidden" name="queue_id" value="<?php echo $queue['id']; ?>">
                            <button type="submit" name="exit_queue" class="btn btn-exit">
                                🚪 Exit Queue
                            </button>
                        </form>
                        <span style="color: #800020; font-size: 0.875rem; display: inline-flex; align-items: center; gap: 0.5rem;">
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            Service in progress
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
            <div class="empty-state">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <h3 style="font-size: 1.25rem; color: #0f172a; margin-bottom: 0.5rem;">No Active Queue</h3>
                <p>You don't have any active queue entries</p>
                <a href="join-queue.php" class="btn-join">Join Queue Now</a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Queue History -->
        <div class="section-card">
            <div class="section-header">
                <h2>
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Queue History
                </h2>
            </div>

            <?php if ($history->num_rows > 0): ?>
                <?php while ($item = $history->fetch_assoc()): ?>
                <div class="history-item">
                    <div>
                        <div style="font-weight: 600; color: #0f172a; margin-bottom: 0.25rem;">
                            <?php echo htmlspecialchars($item['queue_number']); ?> - <?php echo htmlspecialchars($item['service_name']); ?>
                        </div>
                        <div style="font-size: 0.875rem; color: #64748b;">
                            <?php echo date('M j, Y g:i A', strtotime($item['checkin_time'])); ?>
                        </div>
                    </div>
                    <span class="status-badge status-<?php echo $item['status']; ?>">
                        <?php echo ucfirst($item['status']); ?>
                    </span>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
            <div class="empty-state">
                <p>No queue history yet</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>