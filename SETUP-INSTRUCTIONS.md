# 🚀 Smart Queue Manager - Setup Instructions

## 📦 What's Included

A complete, professional queue and appointment management system with:

- **9 Pages**: Index, Login, Register, Dashboard, Queue, Appointments, Services, Admin Panel, Help
- **Full Authentication System**
- **Real-time Queue Management**
- **Appointment Booking**
- **Admin Dashboard**
- **Modern Responsive Design**

## 🛠️ Installation Steps

### 1. Extract Files
- Extract the entire folder to: `C:\xampp\htdocs\smartqueue`

### 2. Start XAMPP
- Open XAMPP Control Panel
- Start **Apache**
- Start **MySQL**

### 3. Create Database
1. Open browser: `http://localhost/phpmyadmin`
2. Click "Import" tab
3. Choose `database.sql` file
4. Click "Go"

### 4. Access the System
- **Homepage**: `http://localhost/smartqueue/`
- **Admin Panel**: `http://localhost/smartqueue/admin.php`

## 🔐 Login Credentials

### Admin Account
- Email: `admin@smartqueue.com`
- Password: `admin123`

### Test User Account  
- Email: `user@test.com`
- Password: `test123`

## 📂 File Structure

```
smartqueue/
├── index.html              # Homepage
├── login.php               # Login page
├── register.php            # Registration
├── dashboard.php           # User dashboard
├── my-queue.php            # Queue status
├── my-appointments.php     # Appointments list
├── services.php            # Services catalog
├── admin.php               # Admin panel
├── help.php                # Help & FAQ
├── db.php                  # Database connection
├── database.sql            # Database setup
├── css/
│   └── style.css          # Main stylesheet
├── js/
│   └── main.js            # JavaScript functions
└── php/
    ├── join-queue.php     # Join queue handler
    ├── book-appointment.php
    ├── update-status.php
    └── logout.php
```

## ✨ Features

### For Customers
- Join queue with instant queue number
- Book appointments online
- Real-time position tracking
- View appointment history
- SMS/Email notifications
- Mobile-responsive interface

### For Admins
- Dashboard with statistics
- Queue management (Start/Complete/Cancel)
- Appointment management
- Service catalog management
- User management
- Real-time updates

## 🎨 Pages Overview

1. **Homepage** - Landing page with features
2. **Login** - Split-screen professional login
3. **Register** - User registration form
4. **Dashboard** - User control panel
5. **My Queue** - Current queue status
6. **My Appointments** - Appointment management
7. **Services** - Available services catalog
8. **Admin Panel** - Complete admin dashboard
9. **Help** - FAQ and support

## 🔧 Customization

### Change Business Name
Edit `db.php` and update settings table

### Add Services
Login as admin → Services page → Add new service

### Modify Colors
Edit `css/style.css` → `:root` variables

## ⚠️ Troubleshooting

**Database Error?**
- Check MySQL is running
- Verify database imported correctly

**Page Not Found?**
- Check files are in `htdocs/smartqueue/`
- Ensure Apache is running

**Login Not Working?**
- Import database.sql again
- Clear browser cache

## 📧 Support

For issues, check:
1. XAMPP logs
2. Browser console (F12)
3. Database tables exist

## 🎉 You're Ready!

Access: `http://localhost/smartqueue/`

**Happy Queue Managing!**
