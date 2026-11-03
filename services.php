<?php
include 'db.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';

// Handle service actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_service'])) {
        $name = sanitize_input($_POST['service_name']);
        $description = sanitize_input($_POST['description']);
        $estimated_time = intval($_POST['estimated_time']);
        
        $stmt = $conn->prepare("INSERT INTO services (service_name, description, estimated_time) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $name, $description, $estimated_time);
        
        if ($stmt->execute()) {
            $message = 'Service added successfully!';
        } else {
            $error = 'Failed to add service.';
        }
    }
    
    if (isset($_POST['delete_service'])) {
        $service_id = intval($_POST['service_id']);
        $stmt = $conn->prepare("DELETE FROM services WHERE id = ?");
        $stmt->bind_param("i", $service_id);
        
        if ($stmt->execute()) {
            $message = 'Service deleted successfully!';
        } else {
            $error = 'Failed to delete service.';
        }
    }
}

// Get all services
$services = $conn->query("SELECT * FROM services ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Services - Admin Panel</title>
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
            max-width: 1200px;
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

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 0.5rem;
        }

        .form-input, .form-textarea {
            width: 100%;
            padding: 0.875rem;
            border: 2px solid #e2e8f0;
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-input:focus, .form-textarea:focus {
            outline: none;
            border-color: #800020;
            box-shadow: 0 0 0 4px rgba(128, 0, 32, 0.1);
        }

        .form-textarea {
            min-height: 100px;
            resize: vertical;
        }

        .btn-primary {
            padding: 0.875rem 2rem;
            background: linear-gradient(135deg, #800020, #5c0016);
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(128, 0, 32, 0.3);
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .service-card {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 1.5rem;
            transition: all 0.3s ease;
        }

        .service-card:hover {
            border-color: #800020;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .service-name {
            font-size: 1.25rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.5rem;
        }

        .service-description {
            color: #64748b;
            margin-bottom: 1rem;
            font-size: 0.9375rem;
        }

        .service-time {
            display: inline-block;
            padding: 0.375rem 0.75rem;
            background: rgba(128, 0, 32, 0.1);
            color: #800020;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .btn-delete {
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border: none;
            border-radius: 0.375rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s ease;
        }

        .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(239, 68, 68, 0.3);
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
    </style>
</head>
<body>
    <nav class="admin-nav">
        <div class="admin-brand">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <h1>Manage Services</h1>
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

        <!-- Add Service Form -->
        <div class="card">
            <h2 class="card-title">Add New Service</h2>
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Service Name</label>
                    <input type="text" name="service_name" class="form-input" required placeholder="e.g., Hair Cut, Consultation">
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-textarea" required placeholder="Describe the service..."></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Estimated Time (minutes)</label>
                    <input type="number" name="estimated_time" class="form-input" required placeholder="30" min="5">
                </div>

                <button type="submit" name="add_service" class="btn-primary">Add Service</button>
            </form>
        </div>

        <!-- Existing Services -->
        <div class="card">
            <h2 class="card-title">Existing Services</h2>
            <?php if ($services->num_rows > 0): ?>
                <div class="services-grid">
                    <?php while ($service = $services->fetch_assoc()): ?>
                        <div class="service-card">
                            <div class="service-name"><?php echo htmlspecialchars($service['service_name']); ?></div>
                            <div class="service-description"><?php echo htmlspecialchars($service['description']); ?></div>
                            <div class="service-time">⏱️ <?php echo $service['estimated_time']; ?> minutes</div>
                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this service?');">
                                <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                                <button type="submit" name="delete_service" class="btn-delete">Delete Service</button>
                            </form>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: #64748b; padding: 2rem;">No services added yet.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
