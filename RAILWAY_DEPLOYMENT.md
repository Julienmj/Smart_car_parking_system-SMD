# Railway Deployment Guide - Smart Parking System

## 🚀 Modern PHP Deployment with Railway

Railway is a modern, developer-friendly platform that makes deploying PHP applications incredibly simple.

---

## 📋 Prerequisites

1. **Free Railway Account** - https://railway.app/
2. **GitHub Account** - For repository integration
3. **Git Repository** - Your project on GitHub

---

## 🎯 Step 1: Sign Up for Railway

1. Go to https://railway.app/
2. Click "Start Building"
3. Sign up with GitHub (recommended)
4. Verify your email address

---

## 🎯 Step 2: Create New Project

### 2.1 Start New Project
1. Click "New Project" in Railway dashboard
2. Select "Deploy from GitHub repo"
3. Choose your repository: `Julienmj/Smart_car_parking_system-SMD`
4. Click "Deploy Now"

### 2.2 Configure Service
Railway will automatically detect your PHP application and create:
- **Web Service** - For your PHP application
- **Database Service** - PostgreSQL database

---

## 🎯 Step 3: Configure Environment

### 3.1 Add Database
1. Click "+ New" in your project
2. Select "Database"
3. Choose "PostgreSQL"
4. Click "Add PostgreSQL"

### 3.2 Connect Database to App
1. Click on your web service
2. Go to "Variables" tab
3. Add these environment variables:
   ```
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=${RAILWAY_PUBLIC_DOMAIN}
   DATABASE_URL=${{DATABASE_URL}}
   ```

---

## 🎯 Step 4: Update Database Configuration

### 4.1 Modify includes/db.php for Railway
```php
<?php
// includes/db.php

class Database {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        // Railway database URL
        $databaseUrl = getenv('DATABASE_URL');
        
        if ($databaseUrl) {
            // Parse Railway PostgreSQL URL
            $url = parse_url($databaseUrl);
            $dsn = sprintf(
                'pgsql:host=%s;port=%s;dbname=%s;user=%s;password=%s',
                $url['host'],
                $url['port'],
                ltrim($url['path'], '/'),
                $url['user'],
                $url['pass']
            );
        } else {
            // Fallback to local development
            $dsn = 'mysql:host=localhost;dbname=parking_system';
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
    
    // Add other database methods...
}
```

---

## 🎯 Step 5: Import Database Schema

### 5.1 Access Database
1. Click on your database service
2. Click "Connect" tab
3. Copy the connection string

### 5.2 Import Schema
```bash
# Using psql with Railway connection string
psql "postgresql://username:password@host:port/database" < sql/parking.sql

# Or use Railway's built-in query editor
# 1. Click on database service
# 2. Click "Query" tab
# 3. Copy-paste contents of sql/parking.sql
# 4. Click "Run"
```

---

## 🎯 Step 6: Deploy and Test

### 6.1 Automatic Deployment
Railway automatically deploys when you push to GitHub:
```bash
git add .
git commit -m "Deploy to Railway"
git push origin main
```

### 6.2 Access Your Application
1. Click on your web service
2. Click the URL under "Your service is available at"
3. Test all features:
   - ✅ Registration/Login
   - ✅ Admin Dashboard
   - ✅ Parking Slots
   - ✅ API Endpoints

---

## 🎯 Step 7: Configure Custom Domain (Optional)

### 7.1 Add Custom Domain
1. Click on your web service
2. Go to "Settings" tab
3. Click "Custom Domain"
4. Enter your domain name
5. Click "Add Domain"

### 7.2 Update DNS
Add these DNS records:
```
Type: CNAME
Name: @
Value: proxy.railway.app

Type: CNAME
Name: www
Value: proxy.railway.app
```

---

## 🔧 Railway Features

### Automatic Features:
✅ **SSL Certificates** - Auto-generated and renewed  
✅ **CI/CD Pipeline** - Automatic deployments from GitHub  
✅ **Health Checks** - Automatic service monitoring  
✅ **Logs** - Built-in log viewing  
✅ **Environment Variables** - Secure configuration management  
✅ **Scaling** - Easy horizontal scaling  

### Database Features:
✅ **Automatic Backups** - Daily backups included  
✅ **Connection Pooling** - Optimized database connections  
✅ **Migration Support** - Easy database schema updates  
✅ **Query Editor** - Built-in database management  

---

## 📊 Monitoring and Logs

### View Logs:
1. Click on your service
2. Click "Logs" tab
3. View real-time application logs

### Monitor Performance:
1. Click "Metrics" tab
2. View CPU, memory, and network usage
3. Monitor database performance

### Health Checks:
Railway automatically checks `/health-check.php` endpoint

---

## 💰 Pricing

### Free Tier:
- **$0/month** - 500 hours/month
- **Database**: 1GB storage
- **Bandwidth**: 100GB/month
- **Perfect for development and small projects**

### Paid Plans:
- **$5/month** - More hours and resources
- **$20/month** - Production-ready with scaling

---

## 🔄 Continuous Deployment

### Automatic Deployments:
1. Connect GitHub repository
2. Enable automatic deploys
3. Any push to main branch triggers deployment

### Manual Deployments:
1. Click "Deploy" button in Railway dashboard
2. Choose branch and commit
3. Click "Deploy"

---

## 🎯 Success Checklist

✅ **Railway account created**  
✅ **Project deployed from GitHub**  
✅ **PostgreSQL database added**  
✅ **Environment variables configured**  
✅ **Database schema imported**  
✅ **Application deployed successfully**  
✅ **All features tested**  
✅ **Custom domain configured (optional)**  

---

## 🌟 Your Live Application

Once deployed, your Smart Parking System will be available at:

**Railway URL**: `https://your-app-name.up.railway.app`  
**Custom Domain**: `https://yourdomain.com` (if configured)

---

## 📚 Additional Resources

- [Railway Documentation](https://docs.railway.app/)
- [Railway PHP Guide](https://docs.railway.app/deploy/php)
- [Railway Database Guide](https://docs.railway.app/deploy/database)
- [Environment Variables](https://docs.railway.app/environment-variables)

---

## 🆘 Troubleshooting

### Common Issues:

#### 1. Build Failed
**Solution**: Check logs and ensure composer.json is valid

#### 2. Database Connection Error
**Solution**: Verify DATABASE_URL environment variable

#### 3. 502 Bad Gateway
**Solution**: Check if application is running and healthy

#### 4. Permission Denied
**Solution**: Check file permissions and .htaccess configuration

---

## 🎉 Why Choose Railway?

✅ **Easiest Deployment** - One-click GitHub integration  
✅ **Modern Interface** - Clean, intuitive dashboard  
✅ **Excellent Support** - Responsive customer service  
✅ **Good Free Tier** - Generous free plan for development  
✅ **Auto SSL** - HTTPS automatically configured  
✅ **Built-in Database** - No separate database setup needed  

---

**🚀 Railway is perfect for developers who want the simplest deployment experience with modern features!**
