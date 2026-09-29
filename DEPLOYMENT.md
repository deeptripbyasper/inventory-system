# 🚀 Live Hosting & Deployment Guide

This guide covers all options for deploying **OmniStock** to production on shared hosting (cPanel), Linux VPS (Ubuntu/Nginx/Apache), Cloud PaaS (Render, Railway, Fly.io), and Docker containers.

---

## 📋 Table of Contents
1. [Pre-Deployment Checklist](#-pre-deployment-checklist)
2. [Option A: cPanel / Shared Hosting (Hostinger, Namecheap, GoDaddy, Bluehost)](#-option-a-cpanel--shared-hosting)
3. [Option B: Cloud PaaS (Railway, Render, Fly.io)](#-option-b-cloud-paas-railway-render-flyio)
4. [Option C: Linux VPS (Ubuntu 22.04/24.04 + Nginx/Apache + Let's Encrypt SSL)](#-option-c-linux-vps-deployment)
5. [Option D: Docker / Docker Compose](#-option-d-docker--docker-compose)
6. [🔐 Post-Deployment Security Hardening](#-post-deployment-security-hardening)
7. [💾 Automated Database Backups](#-automated-database-backups)

---

## 🔍 Pre-Deployment Checklist

- **PHP Version:** PHP 8.0, 8.1, 8.2, or 8.3.
- **PHP Extensions:** `mysqli`, `pdo_mysql`, `pdo_sqlite` (optional fallback), `mbstring`, `openssl`, `json`, `bcmath`.
- **Web Server:** Apache (with `mod_rewrite` & `mod_headers`) or Nginx or LiteSpeed.
- **Database:** MySQL 5.7+ / 8.0+ or MariaDB 10.3+.

---

## 🌐 Option A: cPanel / Shared Hosting

### Step 1: Create Database & User in cPanel
1. Log into your **cPanel** dashboard.
2. Under **Databases**, open **MySQL Database Wizard**.
3. Create a new database (e.g., `u123456_inventory`).
4. Create a new user with a strong password and assign **ALL PRIVILEGES** to the database.
5. Note down:
   - **DB Name:** `u123456_inventory`
   - **DB User:** `u123456_admin`
   - **DB Password:** `YourStrongPassword`
   - **DB Host:** `localhost` (or the host provided by your provider)

### Step 2: Upload Files
1. In cPanel, open **File Manager**.
2. Navigate to `public_html/` (or your subdomain directory e.g., `public_html/inventory`).
3. Upload a `.zip` archive of this project and extract it.
4. Ensure `.htaccess` is present in the root folder (enable *Show Hidden Files* in File Manager Settings).

### Step 3: Run the Web Installer
1. Open your browser and navigate to:
   ```
   https://yourdomain.com/install.php
   ```
2. Enter your MySQL database name, user, password, and port (`3306`).
3. Check **Install Sample Catalog & Initial Data** if you want pre-populated categories, products, batches, and sales records.
4. Click **Run Installation & Initialize**.
5. Once complete, `config/install.lock` will be created automatically to lock the installer.

### Step 4: Login & Change Credentials
1. Go to `https://yourdomain.com/login.php`.
2. Login with default credentials:
   - **Username:** `admin`
   - **Password:** `admin123`
3. Go to **Settings** or **Profile** and immediately update the administrator password.

---

## ☁️ Option B: Cloud PaaS (Railway, Render, Fly.io)

### Deploy on Railway
1. Push your repository to **GitHub**.
2. Go to [Railway.app](https://railway.app) and click **New Project** -> **Provision MySQL**.
3. In the same project, click **New Service** -> **GitHub Repo** -> Select your repo.
4. Railway will automatically build using the included `Dockerfile`.
5. Under service **Variables**, add:
   - `APP_ENV`: `production`
   - `APP_DEBUG`: `false`
   - `APP_TIMEZONE`: `Asia/Kolkata`
   - `DATABASE_URL`: `${{MySQL.DATABASE_URL}}` (or set `DB_HOST`, `DB_USER`, `DB_PASSWORD`, `DB_DATABASE`)
6. Import `database/schema.sql` (and optionally `database/sample_data.sql`) using the Railway MySQL CLI or web query tool.

### Deploy on Render
1. Create a **Web Service** on [Render.com](https://render.com).
2. Connect your GitHub repository.
3. Choose **Docker** environment (or Native PHP environment).
4. Create a managed **MySQL** database or use Render's persistent disk for SQLite.
5. Set environment variables:
   - `APP_ENV`: `production`
   - `APP_DEBUG`: `false`
   - `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
6. Health check path: `/api/health.php`.

---

## 🖥️ Option C: Linux VPS Deployment (Ubuntu 22.04 / 24.04)

### Step 1: Install LEMP/LAMP Stack
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx php8.3-fpm php8.3-mysql php8.3-sqlite3 php8.3-mbstring php8.3-bcmath php8.3-xml php8.3-zip php8.3-curl mysql-server git certbot python3-certbot-nginx
```

### Step 2: Clone & Set Permissions
```bash
cd /var/www
sudo git clone https://github.com/your-username/inventory_system.git /var/www/inventory_system
sudo chown -R www-data:www-data /var/www/inventory_system
sudo chmod -R 755 /var/www/inventory_system
sudo chmod -R 775 /var/www/inventory_system/config /var/www/inventory_system/database
```

### Step 3: Configure Environment
```bash
cd /var/www/inventory_system
cp .env.example .env
nano .env
```
Fill in your database credentials and production settings.

### Step 4: Setup Database & Run Schema
```bash
sudo mysql -u root -p
```
```sql
CREATE DATABASE inventory_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'inventory_user'@'localhost' IDENTIFIED BY 'YourSuperSecretPassword123!';
GRANT ALL PRIVILEGES ON inventory_db.* TO 'inventory_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```
Import schema:
```bash
mysql -u inventory_user -p inventory_db < database/schema.sql
mysql -u inventory_user -p inventory_db < database/sample_data.sql
```
Create the installation lock:
```bash
touch config/install.lock
```

### Step 5: Configure Nginx
```bash
sudo cp nginx-site.conf /etc/nginx/sites-available/inventory
sudo nano /etc/nginx/sites-available/inventory # Update server_name to your domain
sudo ln -s /etc/nginx/sites-available/inventory /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### Step 6: Install Free Let's Encrypt SSL
```bash
sudo certbot --nginx -d inventory.yourdomain.com
```

---

## 🐳 Option D: Docker & Docker Compose

### 1. Configure Environment
```bash
cp .env.example .env
# Edit credentials if needed
```

### 2. Start Application Stack
```bash
docker-compose up -d
```

- Web App: `http://your-server-ip:8080`
- phpMyAdmin (DB manager): `http://your-server-ip:8081`

### 3. Check Health
```bash
curl http://localhost:8080/api/health.php
```

---

## 🔐 Post-Deployment Security Hardening

1. **Change Default Passwords:**
   - Log into the app immediately and change the password for `admin` and `cashier`.
2. **Verify Installation Lock:**
   - Ensure `config/install.lock` exists.
   - Navigate to `https://yourdomain.com/install.php` to confirm it displays the security locked notice.
3. **Check File Permissions:**
   - Files: `644`
   - Directories: `755`
   - `config/` & `database/`: `775` (writable only by web server user `www-data`).
4. **Ensure HTTPS is Enforced:**
   - Verify all traffic redirects to `https://`. Session cookies will automatically enable the `Secure` flag.
5. **Disable Debug Mode:**
   - Set `APP_DEBUG=false` and `APP_ENV=production` in `.env`.

---

## 💾 Automated Database Backups

### Setup Nightly Cron Backup (Linux VPS / cPanel)
Add this cron job (`crontab -e`) to take nightly backups at 2:00 AM:

```bash
0 2 * * * mysqldump -u inventory_user -p'YourPassword' inventory_db | gzip > /var/backups/inventory_db_$(date +\%F).sql.gz
```

To clean up backups older than 30 days:
```bash
0 3 * * * find /var/backups -type f -name "inventory_db_*.sql.gz" -mtime +30 -delete
```
