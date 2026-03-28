# Heroku Deployment Guide - Smart Parking System

## 🚀 Deploy Your Complete PHP/MySQL Application

This guide will help you deploy your Smart Car Parking System with full functionality on Heroku.

---

## 📋 Prerequisites

1. **Free Heroku Account** - https://signup.heroku.com/
2. **Heroku CLI** - https://devcenter.heroku.com/articles/heroku-cli
3. **Git** - For version control
4. **GitHub Account** - For repository management

---

## 🎯 Step 1: Install Heroku CLI

### Windows:
```bash
# Download and install from:
# https://devcenter.heroku.com/articles/heroku-cli#download-and-install

# Or use Chocolatey:
choco install heroku-cli

# Or use npm:
npm install -g heroku
```

### macOS/Linux:
```bash
# Use Homebrew (macOS):
brew tap heroku/brew && brew install heroku

# Or use npm:
npm install -g heroku
```

### Verify Installation:
```bash
heroku --version
```

---

## 🎯 Step 2: Login to Heroku

```bash
# Login to your Heroku account
heroku login

# This will open a browser window for authentication
```

---

## 🎯 Step 3: Prepare Your Project

### 3.1 Check Your Files
Ensure these files exist in your project:
```
Smart_car_parking_system-SMD/
├── composer.json           # PHP dependencies
├── Procfile               # Heroku process configuration
├── .htaccess             # Apache configuration
├── public/               # Web root directory
│   └── index.php        # Entry point
├── health-check.php      # Health check endpoint
├── includes/            # Application code
├── admin/               # Admin panels
├── api/                 # API endpoints
├── assets/              # CSS, JS, images
└── sql/                 # Database schema
```

### 3.2 Initialize Git (if not already done)
```bash
git init
git add .
git commit -m "Prepare for Heroku deployment"
```

---

## 🎯 Step 4: Create Heroku App

### 4.1 Create New App
```bash
# Create a new Heroku app
heroku create your-parking-app

# Or specify a custom name
heroku create smart-parking-system-demo

# This will create:
# - A new Git remote named 'heroku'
# - A random subdomain like https://your-parking-app.herokuapp.com
```

### 4.2 Add PostgreSQL Database
```bash
# Add Heroku PostgreSQL addon (free tier)
heroku addons:create heroku-postgresql:hobby-dev

# This will create a PostgreSQL database and set DATABASE_URL
```

---

## 🎯 Step 5: Configure Environment Variables

### 5.1 Get Database Credentials
```bash
# View database URL
heroku config:get DATABASE_URL

# This will show something like:
# postgres://username:password@hostname:port/database_name
```

### 5.2 Set Application Variables
```bash
# Set environment variables for your application
heroku config:set APP_ENV=production
heroku config:set APP_DEBUG=false
heroku config:set APP_URL=https://your-app-name.herokuapp.com

# Optional: Set custom variables
heroku config:set APP_NAME="Smart Parking System"
heroku config:set APP_VERSION="1.0.0"
```

---

## 🎯 Step 6: Update Database Configuration

### 6.1 Modify includes/db.php
Update your database connection to use Heroku environment:

```php
<?php
// includes/db.php

class Database {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        // Heroku database URL
        $url = parse_url(getenv('DATABASE_URL') ?: 'sqlite::memory:');
        
        if ($url['scheme'] === 'postgres') {
            $dsn = sprintf(
                'pgsql:host=%s;port=%s;dbname=%s;user=%s;password=%s',
                $url['host'],
                $url['port'] ?: 5432,
                substr($url['path'], 1),
                $url['user'],
                $url['pass']
            );
        } else {
            // Fallback to local development
            $dsn = 'mysql:host=localhost;dbname=parking_system';
            // Use your local credentials for development
        }
        
        try {
            $this->pdo = new PDO($dsn, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    }
    
    public static function getConnection() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance->pdo;
    }
}
```

---

## 🎯 Step 7: Deploy to Heroku

### 7.1 Push to Heroku
```bash
# Push your code to Heroku
git push heroku main

# Heroku will automatically:
# - Detect the PHP app
# - Install dependencies from composer.json
# - Build the application
# - Deploy to dynos
```

### 7.2 Open Your Application
```bash
# Open your app in the browser
heroku open

# Or visit the URL directly:
# https://your-app-name.herokuapp.com
```

---

## 🎯 Step 8: Set Up Database

### 8.1 Initialize Database Schema
```bash
# Connect to Heroku PostgreSQL
heroku pg:psql

# Or use the connection string from DATABASE_URL
```

### 8.2 Import Database Schema
```bash
# Method 1: Use Heroku CLI
heroku pg:psql < sql/parking.sql

# Method 2: Use psql directly
psql $(heroku config:get DATABASE_URL) < sql/parking.sql

# Method 3: Run SQL commands manually
heroku pg:psql
# Then copy-paste the contents of sql/parking.sql
```

---

## 🎯 Step 9: Test Your Deployment

### 9.1 Check Application Status
```bash
# Check application logs
heroku logs --tail

# Test health check
curl https://your-app-name.herokuapp.com/health-check.php
```

### 9.2 Verify Functionality
✅ Application loads correctly  
✅ Database connection works  
✅ User registration/login functions  
✅ Admin dashboard accessible  
✅ Parking slots display  
✅ API endpoints respond  

---

## 🎯 Step 10: Configure Custom Domain (Optional)

### 10.1 Add Custom Domain
```bash
# Add custom domain
heroku domains:add yourdomain.com

# This will show DNS records you need to add
```

### 10.2 Update DNS
Add these DNS records to your domain provider:
```
Type: CNAME
Name: www
Value: your-app-name.herokuapp.com

Type: CNAME  
Name: @ (or yourdomain.com)
Value: your-app-name.herokuapp.com
```

---

## 🔧 Troubleshooting

### Common Issues:

#### 1. Application Error (H10)
**Problem**: Application crashes on startup
**Solution**:
```bash
# Check logs
heroku logs --tail

# Check dyno status
heroku ps

# Restart dynos
heroku restart
```

#### 2. Database Connection Error
**Problem**: Cannot connect to database
**Solution**:
```bash
# Check DATABASE_URL
heroku config:get DATABASE_URL

# Test database connection
heroku pg:psql -c "SELECT 1;"

# Recreate database if needed
heroku pg:reset DATABASE
```

#### 3. Build Failed
**Problem**: Application fails to build
**Solution**:
```bash
# Check build logs
heroku logs --tail --ps=build

# Verify composer.json is valid
composer validate

# Clear build cache
heroku builds:cache:purge
```

#### 4. 503 Service Unavailable
**Problem**: No web dynos running
**Solution**:
```bash
# Scale web dynos
heroku ps:scale web=1

# Check dyno status
heroku ps
```

---

## 📊 Monitoring Your App

### 1. Application Metrics
```bash
# View app metrics
heroku ps

# Check response time
curl -w "@curl-format.txt" https://your-app-name.herokuapp.com/
```

### 2. Database Metrics
```bash
# View database info
heroku pg:info

# Monitor database performance
heroku pg:diagnose
```

### 3. Error Tracking
```bash
# View recent errors
heroku logs --tail | grep "ERROR"

# Check application status
heroku releases
```

---

## 🔄 Continuous Deployment

### Automatic Deployment from GitHub:
1. Connect your GitHub repository to Heroku
2. Enable automatic deploys
3. Any push to main branch triggers deployment

```bash
# Link GitHub repository
heroku builds:connect github.com/Julienmj/Smart_car_parking_system-SMD

# Enable automatic deploys
heroku builds:auto -r heroku
```

---

## 💰 Cost Management

### Free Tier Limitations:
- **550 dyno hours/month** (free)
- **10,000 rows** in PostgreSQL (free)
- **100 MB** database storage (free)

### Monitoring Usage:
```bash
# Check dyno hours
heroku ps

# Check database usage
heroku pg:info

# View addon usage
heroku addons
```

---

## 🎯 Success Checklist

✅ **Heroku CLI installed and logged in**  
✅ **Application files prepared**  
✅ **Heroku app created**  
✅ **PostgreSQL database added**  
✅ **Environment variables configured**  
✅ **Database schema imported**  
✅ **Application deployed successfully**  
✅ **All features working**  
✅ **Custom domain configured (optional)**  

---

## 🌟 Your Live Application

Once deployed, your Smart Parking System will be available at:

**Primary URL**: `https://your-app-name.herokuapp.com`  
**Custom Domain**: `https://yourdomain.com` (if configured)

---

## 📚 Additional Resources

- [Heroku PHP Guide](https://devcenter.heroku.com/articles/getting-started-with-php)
- [Heroku PostgreSQL](https://devcenter.heroku.com/articles/heroku-postgresql)
- [Heroku CLI Commands](https://devcenter.heroku.com/articles/heroku-cli-commands)
- [Troubleshooting Guide](https://devcenter.heroku.com/articles/troubleshooting)

---

## 🆘 Need Help?

If you encounter issues:

1. **Check logs**: `heroku logs --tail`
2. **Visit docs**: https://devcenter.heroku.com/
3. **Ask community**: https://help.heroku.com/
4. **Contact support**: https://devcenter.heroku.com/articles/getting-support

---

**🎉 Congratulations! Your Smart Parking System is now live on Heroku with full PHP/MySQL functionality!**
