# 🅿️ Smart Car Parking System

![Smart Parking System](https://img.shields.io/badge/Version-1.0.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4.svg)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1.svg)
![License](https://img.shields.io/badge/License-Educational-green.svg)

> **🎯 Purpose**: This project is designed for **educational and demonstration purposes** to showcase modern web development skills, UI/UX design, and system architecture concepts.

A modern, intelligent parking management system designed to streamline parking operations and enhance user experience through real-time slot tracking, automated fee calculation, and comprehensive analytics.

## 🎯 Live Demo

**🌐 Try the Live Interface Demo**: https://smartcarpark.netlify.app/

> **⚠️ Important**: This is a **static demo version** created for **interface demonstration purposes only**. It showcases the UI/UX design and user interactions but does not include backend functionality or real database operations.

### Demo Features:
✅ **Complete UI Showcase** - All interface elements and interactions  
✅ **Responsive Design** - Works on desktop, tablet, and mobile  
✅ **Interactive Elements** - Hover effects, animations, transitions  
✅ **Demo Navigation** - All pages linked and functional  
✅ **Mock Data** - Realistic parking slots, users, and sessions  

### Demo Limitations:
❌ **No Backend Processing** - All data is mock/demo  
❌ **No Real Database** - No data persistence  
❌ **No Real Authentication** - Login forms are UI only  
❌ **No Actual Booking** - Parking selection is simulated

## Features

### 🚗 Client Features
- **User Registration & Authentication** - Secure login/registration system with role-based access
- **Real-time Parking Map** - Interactive parking slot visualization with live status updates
- **Smart Check-in/Check-out** - Automated parking session management
- **Dynamic Fee Calculation** - Fair pricing based on duration and slot type (30 min free, then hourly rates)
- **Multiple Payment Methods** - Support for cash, card, and mobile money payments
- **Session History** - Complete parking history with receipts
- **Responsive Design** - Works seamlessly on desktop, tablet, and mobile devices

### 🛠️ Admin Features
- **Comprehensive Dashboard** - Real-time statistics and charts
- **Slot Management** - Add, edit, delete parking slots with maintenance mode
- **User Management** - View, activate/deactivate users, detailed user analytics
- **Session Monitoring** - Track all parking sessions with filtering and export options
- **Revenue Analytics** - Detailed revenue reports and charts
- **CSV Export** - Export users, sessions, and reports for analysis

### 🎨 Design & UX
- **Modern Dark Theme** - Professional dark navy and electric cyan color scheme
- **Smooth Animations** - Engaging micro-interactions and transitions
- **Toast Notifications** - Non-intrusive feedback system
- **Modal Dialogs** - Clean, accessible modal interfaces
- **Loading States** - Professional loading indicators
- **Print-friendly Receipts** - Optimized receipt printing

## Technology Stack

### Backend
- **PHP 8.0+** - Server-side logic and API endpoints
- **MySQL 8.0+** - Database management with PDO
- **Session Management** - Secure user authentication
- **Prepared Statements** - SQL injection protection

### Frontend
- **HTML5** - Semantic markup structure
- **CSS3** - Modern styling with CSS variables and animations
- **Vanilla JavaScript** - No framework dependencies, pure JS functionality
- **Chart.js** - Interactive data visualization (CDN)
- **Google Fonts** - Professional typography (Bebas Neue, DM Sans)

### Architecture
- **MVC Pattern** - Separation of concerns
- **RESTful APIs** - Clean API endpoints
- **Responsive Grid** - Mobile-first design approach
- **Component-based CSS** - Reusable styling patterns

## Installation Guide

### Prerequisites
- XAMPP/WAMP/MAMP or similar PHP development environment
- MySQL database server
- PHP 8.0 or higher
- Modern web browser

### Step 1: Database Setup

1. **Start MySQL Server**
   - Open XAMPP Control Panel
   - Start Apache and MySQL services

2. **Create Database**
   ```sql
   CREATE DATABASE parking_system;
   USE parking_system;
   ```

3. **Import Database Schema**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Select `parking_system` database
   - Click "Import" tab
   - Choose the `sql/parking.sql` file from the project
   - Click "Go" to import

### Step 2: Project Setup

1. **Clone/Extract Project**
   - Extract the project to `C:\xampp\htdocs\carparking\parking-system\`
   - Ensure the folder structure is maintained

2. **Configure Database Connection**
   - Open `includes/db.php`
   - Verify database credentials:
   ```php
   private static $host = 'localhost';
   private static $dbname = 'parking_system';
   private static $username = 'root';
   private static $password = ''; // Default XAMPP password
   ```

3. **Set File Permissions**
   - Ensure the web server has read permissions for all files
   - Write permissions may be needed for logs (if implemented)

### Step 3: Access the Application

1. **Open in Browser**
   - Navigate to: `http://localhost/carparking/parking-system/`
   - You should see the landing page

2. **Default Admin Account**
   - Email: `admin@parking.com`
   - Password: `admin123`
   - Role: Administrator

3. **Test Registration**
   - Click "Register" to create a new client account
   - Test the complete parking workflow

## Project Structure

```
parking-system/
├── admin/                          # Admin management pages
│   ├── manage-slots.php            # Slot management interface
│   ├── manage-users.php            # User management interface
│   └── view-sessions.php           # Session viewing interface
├── api/                            # API endpoints for AJAX
│   ├── check-email-availability.php # Email validation
│   ├── get-dashboard-stats.php     # Dashboard statistics
│   ├── get-slot-status.php         # Real-time slot status
│   └── get-user-history.php        # User parking history
├── assets/                         # Static assets
│   ├── css/
│   │   └── style.css              # Main stylesheet
│   └── js/
│       └── main.js                # Main JavaScript file
├── includes/                       # Shared components
│   ├── auth.php                   # Authentication helpers
│   ├── db.php                     # Database connection class
│   ├── footer.php                 # Footer component
│   └── header.php                 # Header component
├── sql/                           # Database files
│   └── parking.sql                # Database schema and seed data
├── checkin.php                    # Check-in processing
├── checkout.php                   # Checkout processing
├── dashboard-admin.php            # Admin dashboard
├── dashboard-client.php           # Client dashboard
├── index.html                     # Landing page
├── login.php                      # Login page
├── logout.php                     # Logout processing
├── payment.php                    # Payment processing
├── register.php                   # Registration page
└── README.md                      # This file
```

## Database Schema

### Tables Overview

1. **users** - User accounts and authentication
   - `id`, `full_name`, `email`, `password`, `role`, `is_active`, `created_at`

2. **parking_slots** - Physical parking spaces
   - `id`, `slot_code`, `slot_type`, `floor`, `status`

3. **parking_sessions** - Parking session records
   - `id`, `user_id`, `slot_id`, `checkin_time`, `checkout_time`, `fee_amount`, `status`

4. **payments** - Payment transaction records
   - `id`, `session_id`, `user_id`, `amount`, `payment_method`, `payment_status`, `paid_at`

### Fee Structure

- **First 30 minutes**: Free
- **Standard slots**: 200 RWF per hour (after free period)
- **VIP slots**: 350 RWF per hour (after free period)
- **Disabled slots**: 200 RWF per hour (after free period)
- **Billing**: Rounded up to the next hour

## API Endpoints

### Authentication Required
- `GET /api/get-dashboard-stats.php` - Dashboard statistics (Admin only)
- `GET /api/get-user-history.php?user_id=X` - User parking history (Admin only)
- `GET /api/get-slot-status.php` - Real-time slot status (All users)

### Public Endpoints
- `GET/POST /api/check-email-availability.php` - Email availability check

## Security Features

### Authentication & Authorization
- **Password Hashing** - Uses PHP's `password_hash()` with bcrypt
- **Session Management** - Secure session handling with timeout
- **Role-based Access** - Admin and client role separation
- **CSRF Protection** - Token-based CSRF prevention

### Input Validation & Sanitization
- **Server-side Validation** - All inputs validated server-side
- **SQL Injection Protection** - PDO prepared statements
- **XSS Prevention** - Output escaping with `htmlspecialchars()`
- **Email Validation** - Server-side email format validation

### Session Security
- **Secure Cookies** - HttpOnly and secure session cookies
- **Session Timeout** - Automatic logout after inactivity
- **Session Regeneration** - Session ID regeneration on login

## Performance Optimizations

### Database Optimization
- **Indexed Columns** - Proper indexing on foreign keys and search fields
- **Prepared Statements** - Query caching and optimization
- **Efficient Queries** - Optimized SQL with proper JOINs

### Frontend Optimization
- **Lazy Loading** - Images and content loaded as needed
- **CSS Variables** - Efficient styling management
- **Minified Assets** - Production-ready minified files
- **Caching Headers** - Proper browser caching

## Browser Compatibility

- **Chrome 90+** - Full support
- **Firefox 88+** - Full support
- **Safari 14+** - Full support (with -webkit prefixes)
- **Edge 90+** - Full support
- **Mobile Browsers** - Full support on iOS Safari and Android Chrome

## Customization Guide

### Styling Customization
1. **Color Scheme** - Modify CSS variables in `assets/css/style.css`
2. **Typography** - Update Google Fonts in `includes/header.php`
3. **Animations** - Adjust animation durations and easing functions

### Business Logic Customization
1. **Fee Structure** - Update fee calculation in `checkout.php`
2. **Slot Types** - Modify slot types in database and UI
3. **Payment Methods** - Add/remove payment options in `payment.php`

### Database Customization
1. **Additional Fields** - Add columns to relevant tables
2. **New Tables** - Create additional tables for extended functionality
3. **Relationships** - Modify foreign key relationships as needed

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Verify MySQL service is running
   - Check database credentials in `includes/db.php`
   - Ensure database exists and is imported

2. **Permission Denied**
   - Check file permissions on the project folder
   - Verify web server user has read access

3. **Session Not Working**
   - Check PHP session configuration
   - Verify browser cookies are enabled
   - Clear browser cache and cookies

4. **AJAX Not Working**
   - Check browser console for JavaScript errors
   - Verify API endpoints are accessible
   - Check network tab for failed requests

### Debug Mode
To enable debug mode, add this to `includes/db.php`:
```php
// In the Database class constructor
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
```

## Deployment Notes

### Production Setup
1. **Environment Variables** - Use environment variables for sensitive data
2. **HTTPS** - Enable SSL/TLS for secure communication
3. **Database Security** - Use strong database passwords
4. **File Permissions** - Restrict file access to web server user
5. **Error Logging** - Implement proper error logging system
6. **Backup Strategy** - Regular database backups

### Performance Monitoring
- Monitor database query performance
- Track page load times
- Monitor server resource usage
- Implement caching strategies

## Contributing

### Code Standards
- Follow PSR-12 coding standards for PHP
- Use semantic HTML5 markup
- Write clean, commented CSS
- Implement proper error handling

### Testing
- Test all user workflows
- Verify responsive design on multiple devices
- Test admin functionality thoroughly
- Validate form inputs and edge cases

## License

This project is for educational and demonstration purposes. Feel free to modify and use according to your needs.

## Support

For technical support or questions:
1. Check the troubleshooting section above
2. Review the code comments for detailed explanations
3. Test with the provided admin account
4. Verify all prerequisites are met

---

## 🚀 Quick Start

### Prerequisites
- XAMPP/WAMP/MAMP or similar PHP development environment
- MySQL database server
- PHP 8.0 or higher
- Modern web browser

### Installation Steps

1. **Clone the Repository**
   ```bash
   git clone https://github.com/Julienmj/Smart_car_parking_system-SMD.git
   cd Smart_car_parking_system-SMD
   ```

2. **Database Setup**
   ```sql
   CREATE DATABASE parking_system;
   -- Import sql/parking.sql file
   ```

3. **Configure & Run**
   - Update database credentials in `includes/db.php` if needed
   - Place the project in your web server's document root
   - Access via: `http://localhost/Smart_car_parking_system-SMD/`

### Default Credentials
- **Admin**: `admin@parking.com` / `admin123`
- **Client**: Register via the registration page

### 🎨 Demo Version
For a static demo showcase (no backend required):
- Navigate to the `demo/` folder
- Deploy to Netlify or any static hosting
- See `demo/README.md` for details

### Demo vs Full Version
- **Full Version**: Complete PHP/MySQL application (this folder)
- **Demo Version**: Static showcase only (`demo/` folder)

## 🎯 Project Highlights

### ✨ Key Features
- **Real-time Slot Tracking** - Live parking availability updates
- **Automated Fee Calculation** - Fair pricing with 30 min free period
- **Multi-floor Support** - Navigate multiple parking levels
- **Role-based Access** - Separate admin and client interfaces
- **Responsive Design** - Works on all devices
- **Modern UI/UX** - Dark theme with smooth animations
- **Data Analytics** - Comprehensive dashboard with charts
- **Export Functionality** - CSV export for reports

### 🛠️ Technology Stack
- **Backend**: PHP 8.0+, MySQL 8.0+, PDO
- **Frontend**: HTML5, CSS3, Vanilla JavaScript
- **Visualization**: Chart.js
- **Typography**: Google Fonts (Bebas Neue, DM Sans)
- **Architecture**: MVC pattern, RESTful APIs

### 📊 Database Schema
- **users** - User accounts and authentication
- **parking_slots** - Physical parking spaces
- **parking_sessions** - Parking session records  
- **payments** - Payment transaction records

### 🎨 Demo Technology Stack
- **Frontend**: HTML5, CSS3, Vanilla JavaScript
- **Deployment**: Netlify (static hosting)
- **Visualization**: Chart.js
- **Typography**: Google Fonts (Bebas Neue, DM Sans)
- **Architecture**: Static site with demo data

### 💰 Fee Structure
- **First 30 minutes**: Free
- **Standard slots**: 200 RWF/hour
- **VIP slots**: 350 RWF/hour
- **Disabled slots**: 200 RWF/hour

## 🎯 Demo Deployment (Static)

### For Static Demo Only:
Navigate to the `demo/` folder for a complete static demonstration.

### Demo Features:
✅ **Modern UI/UX** - Complete interface showcase  
✅ **Responsive Design** - Works on all devices  
✅ **Interactive Elements** - Animations and hover effects  
✅ **Demo Dashboard** - Layout and design demonstration  
✅ **Clear Limitations** - Users understand it's a demo  

### Quick Demo Deploy:
```bash
cd demo
# Deploy to Netlify, Vercel, or any static hosting
```

## 🔧 Configuration

### Database Connection
```php
// includes/db.php
private static $host = 'localhost';
private static $dbname = 'parking_system';
private static $username = 'root';
private static $password = ''; // Default XAMPP password
```

### Customization Options
- **Colors**: Modify CSS variables in `assets/css/style.css`
- **Fees**: Update calculation logic in `checkout.php`
- **Slot Types**: Modify database and UI components
- **Payment Methods**: Add/remove options in `payment.php`

## 🔒 Demo Mode Information

### What Works in Demo Mode:
✅ **Complete UI/UX** - All interface elements  
✅ **Responsive Design** - Mobile and desktop layouts  
✅ **Animations** - Smooth transitions and effects  
✅ **Demo Data** - Mock parking slots and sessions  
✅ **Navigation** - All pages and links work  

### Limitations:
❌ **No Backend Processing** - PHP files are for display only  
❌ **No Database** - All data is mock/demo data  
❌ **No Real Authentication** - Login forms are UI only  
❌ **No Actual Booking** - Parking selection is simulated  

## 📱 Browser Support

- Chrome 90+ ✅
- Firefox 88+ ✅
- Safari 14+ ✅
- Edge 90+ ✅
- Mobile browsers ✅

## 🐛 Troubleshooting

### Common Netlify Issues

1. **Build Failed**
   - Check build command: `echo 'No build needed for static deployment'`
   - Verify publish directory: `public`
   - Check netlify.toml configuration

2. **404 Errors**
   - Check redirects in netlify.toml
   - Ensure all files are in `public/` directory
   - Verify file paths are correct

3. **CSS/JS Not Loading**
   - Check file paths in HTML
   - Verify assets are in `public/assets/`
   - Check browser console for errors

4. **Images Not Showing**
   - Check image file names and paths
   - Verify images are committed to Git

### Debug Mode
Check Netlify build logs in your dashboard for detailed error information.

## 📈 Performance Features

- **Static Hosting** - Fast CDN delivery via Netlify
- **Optimized Assets** - CSS and JS minification
- **Image Optimization** - Automatic image compression
- **Caching Headers** - Proper browser caching
- **CDN Distribution** - Global content delivery

## 🌟 Demo Features

### UI/UX Showcase
- **Modern Dark Theme** - Professional navy and cyan design
- **Smooth Animations** - Engaging micro-interactions
- **Responsive Grid** - Mobile-first design approach
- **Interactive Elements** - Hover effects and transitions
- **Typography** - Professional Google Fonts

### Demo Data
- **Mock Parking Slots** - 10 demo slots with different types
- **Sample Sessions** - Example parking sessions
- **Demo Users** - Sample user profiles
- **Statistics** - Mock dashboard analytics

## 🔄 Demo API

### Mock Endpoints
- `GET /api/get-slot-status.php` - Demo slot data
- `GET /api/get-dashboard-stats.php` - Demo statistics
- `POST /api/check-email-availability.php` - Demo validation

## 📦 Project Structure

```
Smart_car_parking_system-SMD/
├── demo/                     # Static demo version
│   ├── index.html          # Demo landing page
│   ├── login.html           # Demo login
│   ├── register.html        # Demo registration
│   ├── dashboard-client.html # Demo client dashboard
│   ├── dashboard-admin.html  # Demo admin dashboard
│   ├── payment.html         # Demo payment interface
│   ├── assets/              # Demo CSS and JS
│   ├── demo-data.js         # Mock data
│   ├── netlify.toml         # Netlify configuration
│   └── README.md            # Demo documentation
├── admin/                   # Admin management pages
├── api/                     # API endpoints
├── assets/                  # Static assets
├── includes/                # Shared components
├── sql/                     # Database files
├── NETLIFY_DEPLOYMENT.md    # Deployment guide
└── README.md                # This file
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test the demo functionality
5. Submit a pull request

### Code Standards
- Use semantic HTML5
- Write clean, commented CSS
- Optimize for performance
- Test responsive design

## 📄 License

This project is for educational and demonstration purposes. Feel free to modify and use according to your needs.

## 🆘 Support

For technical support:
1. Check the troubleshooting section
2. Review NETLIFY_DEPLOYMENT.md
3. Check Netlify build logs
4. Test with different browsers

---

**Version**: 1.0.0  
**Last Updated**: March 2026  
**Developer**: Smart Parking System Team  
**Repository**: https://github.com/Julienmj/Smart_car_parking_system-SMD

## ⭐ Star this Project

If you find this Smart Car Parking System demo useful, give it a star on GitHub!

[![GitHub stars](https://img.shields.io/github/stars/Julienmj/Smart_car_parking_system-SMD.svg?style=social&label=Star)](https://github.com/Julienmj/Smart_car_parking_system-SMD)

## 🚀 Deploy Your Own Demo

Ready to deploy your own version? 

### For Static Demo:
1. Navigate to the `demo/` folder
2. Follow the demo README instructions
3. Deploy to Netlify, Vercel, or any static hosting

### For Full Application:
1. Set up PHP/MySQL environment
2. Import database schema
3. Configure database connection
4. Deploy to PHP hosting platform
