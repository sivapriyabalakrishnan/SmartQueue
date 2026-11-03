<?php
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get statistics
$total_queues = $conn->query("SELECT COUNT(*) as count FROM queue WHERE user_id = $user_id")->fetch_assoc()['count'] ?? 0;
$completed_queues = $conn->query("SELECT COUNT(*) as count FROM queue WHERE user_id = $user_id AND status = 'completed'")->fetch_assoc()['count'] ?? 0;
$total_appointments = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE user_id = $user_id")->fetch_assoc()['count'] ?? 0;
$completed_appointments = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE user_id = $user_id AND status = 'completed'")->fetch_assoc()['count'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Smart Queue Manager</title>
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

        .nav-user {
            display: flex;
            align-items: center;
            gap: 1rem;
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

        .btn-logout {
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border: none;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 600;
        }

        .profile-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .profile-header {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 1rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #800020, #7c3aed);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
            font-weight: 800;
        }

        .profile-info h1 {
            font-size: 2rem;
            color: #0f172a;
            margin-bottom: 0.5rem;
        }

        .profile-info p {
            color: #64748b;
            font-size: 1rem;
        }

        .profile-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .info-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .info-card h2 {
            font-size: 1.5rem;
            color: #0f172a;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .info-item {
            padding: 1rem 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            font-size: 0.875rem;
            color: #64748b;
            margin-bottom: 0.25rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-value {
            font-size: 1.125rem;
            color: #0f172a;
            font-weight: 600;
        }

        .stats-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 1.5rem;
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: #800020;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.875rem;
            color: #64748b;
            margin-bottom: 1.5rem;
        }

        .btn-edit {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #800020, #5c0016);
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s ease;
        }

        .btn-edit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(128, 0, 32, 0.3);
        }

        .quick-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .quick-link-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
        }

        .quick-link-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .quick-link-icon {
            width: 50px;
            height: 50px;
            margin: 0 auto 1rem;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .icon-blue { background: linear-gradient(135deg, #800020, #5c0016); }
        .icon-green { background: linear-gradient(135deg, #800020, #5c0016); }
        .icon-orange { background: linear-gradient(135deg, #800020, #5c0016); }

        .quick-link-card h3 {
            font-size: 1rem;
            color: #0f172a;
            margin: 0;
        }

        @media (max-width: 768px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }

            .profile-header {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <nav class="user-nav">
        <div class="nav-brand">
            <svg width="35" height="35" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            My Profile
        </div>
        <div class="nav-user">
            <a href="dashboard.php" class="btn-back">← Back to Dashboard</a>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </nav>

    <div class="profile-container">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
            </div>
            <div class="profile-info">
                <h1><?php echo htmlspecialchars($user['name']); ?></h1>
                <p><?php echo htmlspecialchars($user['email']); ?></p>
                <p style="font-size: 0.875rem; color: #94a3b8;">
                    Member since <?php echo date('F Y', strtotime($user['created_at'])); ?>
                </p>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="quick-links" style="margin-bottom: 2rem;">
            <a href="my_queue.php" class="quick-link-card">
                <div class="quick-link-icon icon-blue">
                    <svg width="24" height="24" fill="none" stroke="white" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <h3>My Queue</h3>
            </a>

            <a href="my_appointments.php" class="quick-link-card">
                <div class="quick-link-icon icon-green">
                    <svg width="24" height="24" fill="none" stroke="white" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3>My Appointments</h3>
            </a>

            <a href="edit_profile.php" class="quick-link-card">
                <div class="quick-link-icon icon-orange">
                    <svg width="24" height="24" fill="none" stroke="white" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </div>
                <h3>Edit Profile</h3>
            </a>
        </div>

        <!-- Profile Grid -->
        <div class="profile-grid">
            <!-- Personal Information -->
            <div class="info-card">
                <h2>
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Personal Information
                </h2>

                <div class="info-item">
                    <div class="info-label">Full Name</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['name']); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">Email Address</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">Phone Number</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['phone']); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">Account Type</div>
                    <div class="info-value"><?php echo ucfirst($user['role']); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">Member Since</div>
                    <div class="info-value"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></div>
                </div>

                <a href="edit_profile.php" class="btn-edit" style="margin-top: 1.5rem;">
                    Edit Profile Information
                </a>
            </div>

            <!-- Statistics -->
            <div>
                <div class="stats-card" style="margin-bottom: 1.5rem;">
                    <div class="stat-number"><?php echo $total_queues; ?></div>
                    <div class="stat-label">Total Queue Entries</div>
                    <small style="color: #800020; font-weight: 600;"><?php echo $completed_queues; ?> Completed</small>
                </div>

                <div class="stats-card">
                    <div class="stat-number"><?php echo $total_appointments; ?></div>
                    <div class="stat-label">Total Appointments</div>
                    <small style="color: #800020; font-weight: 600;"><?php echo $completed_appointments; ?> Completed</small>
                </div>
            </div>
        </div>
    </div>
</body>
</html>