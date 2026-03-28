# Deployment Guide for Smart Car Parking System

## 🚀 Deployment Options

### Option 1: Full-Stack Hosting (Recommended)
Complete functionality with PHP backend and MySQL database.

### Option 2: Netlify Static (Demo Only)
Frontend only - no backend functionality.

---

## 🌟 Option 1: Heroku Deployment (Recommended)

### Prerequisites
- Free Heroku account
- Heroku CLI installed
- Git repository ready

### Step 1: Install Heroku CLI
```bash
# Download from: https://devcenter.heroku.com/articles/heroku-cli
# Or use npm:
npm install -g heroku
```

### Step 2: Prepare Project for Heroku
```bash
# Login to Heroku
heroku login

# Create Heroku app
heroku create your-parking-app

# Add Heroku PostgreSQL database
heroku addons:create heroku-postgresql:hobby-dev
```

### Step 3: Create Heroku Configuration Files

#### 1. composer.json
```json
{
    "require": {
        "php": ">=8.0"
    },
    "require-dev": {
        "ext-pdo": "*"
    }
}
```

#### 2. Procfile
```
web: vendor/bin/heroku-php-apache2 public/
```

#### 3. .htaccess (for Heroku)
```apache
DirectoryIndex index.html index.php

<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php [QSA,L]
</IfModule>
```

#### 4. index.php (entry point)
```php
<?php
// Redirect to main application
header('Location: index.html');
exit();
?>
```

### Step 4: Configure Database
```bash
# Get database URL
heroku config:get DATABASE_URL

# Set environment variables
heroku config:set DB_HOST=localhost
heroku config:set DB_NAME=parking_system
heroku config:set DB_USER=username
heroku config:set DB_PASSWORD=password
```

### Step 5: Deploy to Heroku
```bash
# Add files to Git
git add .
git commit -m "Prepare for Heroku deployment"

# Push to Heroku
git push heroku main

# Open your app
heroku open
```

---

## 🎨 Option 2: Netlify Static Deployment (Demo Only)

### Prerequisites
- Netlify account
- Git repository

### Step 1: Create netlify.toml
```toml
[build]
  publish = "public"
  command = "echo 'No build needed'"

[[redirects]]
  from = "/*"
  to = "/index.html"
  status = 404

[build.environment]
  PHP_VERSION = "8.0"
```

### Step 2: Create Public Directory
```bash
mkdir public
cp index.html public/
cp -r assets public/
cp -r screenshots public/
```

### Step 3: Create Static Demo Version
Replace PHP functionality with JavaScript demo data:

#### public/demo-data.js
```javascript
// Demo data for static deployment
const demoData = {
    slots: [
        {id: 1, code: 'A1', type: 'standard', floor: 1, status: 'available'},
        {id: 2, code: 'A2', type: 'standard', floor: 1, status: 'occupied'},
        // Add more demo slots...
    ],
    sessions: [
        {id: 1, user: 'Demo User', slot: 'A1', checkin: '2024-01-01 10:00', fee: 200}
    ]
};
```

### Step 4: Deploy to Netlify
1. Push changes to GitHub
2. Connect Netlify to your GitHub repository
3. Configure build settings:
   - Build command: `echo 'No build needed'`
   - Publish directory: `public`
4. Deploy site

---

## 🏆 Option 3: Railway Deployment (Modern Alternative)

### Step 1: Install Railway CLI
```bash
npm install -g @railway/cli
```

### Step 2: Deploy
```bash
# Login
railway login

# Initialize project
railway init

# Add PostgreSQL
railway add postgresql

# Deploy
railway up
```

---

## 📊 Option 4: Render Deployment

### Step 1: Create render.yaml
```yaml
services:
  - type: web
    name: smart-parking
    env: php
    buildCommand: "echo 'No build needed'"
    startCommand: "apache2-foreground"
    envVars:
      - key: DATABASE_URL
        fromDatabase:
          name: parking-db
          property: connectionString

databases:
  - name: parking-db
    databaseName: parking_system
```

### Step 2: Deploy
1. Push to GitHub
2. Connect Render to GitHub
3. Create new Web Service
4. Connect PostgreSQL database
5. Deploy

---

## 🔧 Option 5: DigitalOcean (Self-Hosted)

### Step 1: Create Droplet
- Ubuntu 22.04
- $5/month plan
- Enable SSH keys

### Step 2: Setup Server
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install LAMP stack
sudo apt install apache2 mysql-server php libapache2-mod-php php-mysql -y

# Install additional PHP extensions
sudo apt install php-curl php-json php-mbstring php-xml -y

# Enable Apache modules
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### Step 3: Deploy Application
```bash
# Clone repository
cd /var/www/html
sudo git clone https://github.com/Julienmj/Smart_car_parking_system-SMD.git

# Set permissions
sudo chown -R www-data:www-data Smart_car_parking_system-SMD/
sudo chmod -R 755 Smart_car_parking_system-SMD/
```

---

## 🎯 Recommendation

**For Production Use:**
1. **Heroku** - Easiest to setup, free tier available
2. **Railway** - Modern interface, good performance
3. **DigitalOcean** - Full control, best for scaling

**For Demo/Portfolio:**
1. **Netlify** - Fastest deployment, static only
2. **Vercel** - Similar to Netlify

**Next Steps:**
1. Choose your hosting platform
2. Follow the specific guide
3. Update database credentials
4. Test the deployment
5. Configure domain (optional)

---

## 🔗 Useful Links

- [Heroku PHP Guide](https://devcenter.heroku.com/articles/getting-started-with-php)
- [Railway PHP Guide](https://docs.railway.app/guides/deploy/php)
- [Render PHP Guide](https://render.com/docs/deploy-php-apache)
- [Netlify Static Sites](https://docs.netlify.com/visitor-access/git-auth/)
- [DigitalOcean LAMP Setup](https://www.digitalocean.com/community/tutorials/how-to-install-linux-apache-mysql-php-lamp-stack-on-ubuntu-22-04)
