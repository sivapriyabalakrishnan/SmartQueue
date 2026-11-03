-- ============================================
-- Smart Queue Manager - Complete Database Setup
-- Database: smartqueue
-- ============================================

CREATE DATABASE IF NOT EXISTS smartqueue;
USE smartqueue;

-- ============================================
-- Table: users (Customer and Admin accounts)
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(15) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Table: services (Available services)
-- ============================================
CREATE TABLE IF NOT EXISTS services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    service_name VARCHAR(100) NOT NULL,
    description TEXT,
    estimated_time INT NOT NULL COMMENT 'in minutes',
    price DECIMAL(10,2) DEFAULT 0.00,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Table: queue (Queue entries)
-- ============================================
CREATE TABLE IF NOT EXISTS queue (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    service_id INT NOT NULL,
    queue_number VARCHAR(20) NOT NULL UNIQUE,
    status ENUM('waiting', 'in-progress', 'completed', 'cancelled') DEFAULT 'waiting',
    priority INT DEFAULT 0,
    checkin_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    start_time TIMESTAMP NULL,
    end_time TIMESTAMP NULL,
    estimated_wait_time INT COMMENT 'in minutes',
    notes TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_queue_number (queue_number),
    INDEX idx_checkin_time (checkin_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Table: appointments (Scheduled appointments)
-- ============================================
CREATE TABLE IF NOT EXISTS appointments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    service_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('scheduled', 'confirmed', 'in-progress', 'completed', 'cancelled') DEFAULT 'scheduled',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    INDEX idx_date_time (appointment_date, appointment_time),
    INDEX idx_status (status),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Table: settings (System settings)
-- ============================================
CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value TEXT NOT NULL,
    description VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Insert Default Admin User
-- Email: admin@smartqueue.com
-- Password: admin123
-- ============================================
INSERT INTO users (name, email, password, phone, role) VALUES
(
    'System Administrator',
    'admin@smartqueue.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    '+1234567890',
    'admin'
) ON DUPLICATE KEY UPDATE email=email;

-- ============================================
-- Insert Test User
-- Email: user@test.com  
-- Password: test123
-- ============================================
INSERT INTO users (name, email, password, phone, role) VALUES
(
    'Test User',
    'user@test.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    '+0987654321',
    'user'
) ON DUPLICATE KEY UPDATE email=email;

-- ============================================
-- Insert Sample Services
-- ============================================
INSERT INTO services (service_name, description, estimated_time, price) VALUES
('General Consultation', 'General medical or service consultation', 15, 50.00),
('Document Processing', 'Document verification and processing', 20, 75.00),
('Customer Support', 'Customer support and inquiries', 10, 0.00),
('Technical Support', 'Technical assistance and troubleshooting', 25, 100.00),
('Account Services', 'Account opening and management', 30, 150.00),
('Health Checkup', 'Complete health screening and checkup', 45, 200.00),
('Legal Consultation', 'Legal advice and document review', 30, 250.00),
('Financial Planning', 'Financial advisory and planning session', 40, 300.00)
ON DUPLICATE KEY UPDATE service_name=service_name;

-- ============================================
-- Insert Default Settings
-- ============================================
INSERT INTO settings (setting_key, setting_value, description) VALUES
('business_name', 'Smart Queue Manager', 'Business/Organization name'),
('business_hours', '9:00 AM - 6:00 PM', 'Operating hours'),
('contact_email', 'info@smartqueue.com', 'Contact email'),
('contact_phone', '+1-555-QUEUE-00', 'Contact phone number'),
('max_queue_capacity', '50', 'Maximum queue capacity'),
('appointment_slots_per_hour', '4', 'Number of appointment slots per hour'),
('enable_sms_notifications', '0', 'Enable SMS notifications'),
('enable_email_notifications', '1', 'Enable email notifications'),
('queue_prefix', 'Q', 'Queue number prefix'),
('appointment_prefix', 'A', 'Appointment number prefix')
ON DUPLICATE KEY UPDATE setting_key=setting_key;

-- ============================================
-- Create some sample queue entries for testing
-- ============================================
-- (These will be added dynamically by users)

-- ============================================
-- Success Messages
-- ============================================
SELECT 'Database setup completed successfully!' AS Status;
SELECT 'Login Credentials:' AS Info;
SELECT 'Admin: admin@smartqueue.com / admin123' AS AdminLogin;
SELECT 'User: user@test.com / test123' AS UserLogin;
