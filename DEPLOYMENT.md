# UgPro University Job Portal - Deployment Guide

This guide provides step-by-step instructions for deploying the **UgPro University Job & Career Portal** across local development environments, cPanel shared hosting, Linux Cloud VPS, and Docker environments.

---

## 1. System Requirements

- **PHP**: 7.4, 8.0, 8.1, 8.2+ (with `mysqli`, `pdo_mysql`, `mbstring`, `fileinfo`, `openssl` extensions enabled)
- **Database**: MySQL 5.7+ or MariaDB 10.3+
- **Web Server**: Apache 2.4+ (with `mod_rewrite`) or Nginx
- **HTTPS/SSL**: Recommended for production

---

## 2. Default Seed Credentials (For Testing)

After importing `database.sql`, the following demo accounts are available:

| Role | Username / Email | Password | Dashboard Link |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin` or `admin@ugpro.lk` | `admin123` | [`/admin/login.php`](file:///d:/Projects/UGPRO_JOB_PORTEL/admin/login.php) |
| **Undergraduate Student** | `illiyas@vau.ac.lk` | `student123` | [`/signin_undergraduate.php`](file:///d:/Projects/UGPRO_JOB_PORTEL/signin_undergraduate.php) |
| **Partner Employer (Virtusa)** | `careers@virtusa.com` | `employer123` | [`/signin_employer.php`](file:///d:/Projects/UGPRO_JOB_PORTEL/signin_employer.php) |
| **Partner Employer (WSO2)** | `recruitment@wso2.com` | `employer123` | [`/signin_employer.php`](file:///d:/Projects/UGPRO_JOB_PORTEL/signin_employer.php) |

---

## 3. Option A: Local Deployment (XAMPP / WAMP)

1. **Start Services**:
   - Open XAMPP Control Panel.
   - Start **Apache** and **MySQL** modules.

2. **Database Import**:
   - Open browser and navigate to `http://localhost/phpmyadmin/`.
   - Click **Import** in the top navigation bar.
   - Choose `database.sql` from this project folder and click **Import**.
   - *(Alternatively, create database `vavuniyauniversity` and import the SQL file)*.

3. **Place Files in Web Root**:
   - Copy or symlink this folder into `C:\xampp\htdocs\UGPRO_JOB_PORTEL`.

4. **Launch Application**:
   - Open your browser and navigate to:
     ```
     http://localhost/UGPRO_JOB_PORTEL/
     ```

---

## 4. Option B: Live cPanel Shared Hosting Deployment

1. **Create MySQL Database & User**:
   - Log into cPanel.
   - Go to **MySQL® Databases**.
   - Create a new database (e.g. `yourcpanel_ugpro`).
   - Create a new user with a strong password and assign all privileges to the database.

2. **Import Database Schema**:
   - In cPanel, open **phpMyAdmin**.
   - Select your new database and click **Import**.
   - Upload and execute `database.sql`.

3. **Upload Files**:
   - In cPanel, open **File Manager**.
   - Navigate to `public_html` (or subfolder/subdomain folder).
   - Upload all project files.

4. **Configure Database Credentials**:
   - Open `conf/dbconf.php` or `config.php` and set:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'yourcpanel_dbuser');
     define('DB_PASS', 'your_strong_password');
     define('DB_NAME', 'yourcpanel_ugpro');
     ```

5. **Set Upload Directory Permissions**:
   - Ensure the following directories have write permissions (`755` or `775`):
     - `uploads/`
     - `uploads/profiles/`
     - `uploads/logos/`
     - `uploads/resumes/`

---

## 5. Option C: Linux Cloud VPS (Ubuntu 22.04 / 24.04 LTS)

### Step 1: Install LAMP Stack
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y apache2 mariadb-server php php-mysql php-mbstring php-xml php-curl php-zip libapache2-mod-php
```

### Step 2: Configure MariaDB Database
```bash
sudo mysql -u root
```
```sql
CREATE DATABASE vavuniyauniversity CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ugpro_user'@'localhost' IDENTIFIED BY 'StrongSecurePassword123!';
GRANT ALL PRIVILEGES ON vavuniyauniversity.* TO 'ugpro_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Import schema:
```bash
sudo mysql -u ugpro_user -p vavuniyauniversity < /var/www/html/database.sql
```

### Step 3: Clone / Copy Codebase to `/var/www/html/ugpro`
```bash
sudo cp -r /path/to/UGPRO_JOB_PORTEL /var/www/html/ugpro
sudo chown -R www-data:www-data /var/www/html/ugpro/uploads
sudo chmod -R 775 /var/www/html/ugpro/uploads
```

### Step 4: Configure VirtualHost
Create `/etc/apache2/sites-available/ugpro.conf`:
```apache
<VirtualHost *:80>
    ServerName jobs.vau.ac.lk
    DocumentRoot /var/www/html/ugpro

    <Directory /var/www/html/ugpro>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/ugpro_error.log
    CustomLog ${APACHE_LOG_DIR}/ugpro_access.log combined
</VirtualHost>
```

Enable site and restart Apache:
```bash
sudo a2ensite ugpro.conf
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### Step 5: Enable Free SSL with Let's Encrypt
```bash
sudo apt install -y certbot python3-certbot-apache
sudo certbot --apache -d jobs.vau.ac.lk
```

---

## 6. Production Security Checklist

- [x] **Password Hashing**: Implemented with industry-standard bcrypt (`PASSWORD_BCRYPT`).
- [x] **SQL Injection Prevention**: All queries parameterized using prepared statements.
- [x] **File Upload Security**: Strict MIME-type and extension whitelisting (PDF for resumes, PNG/JPG/WebP for images) with unique random hashes to prevent script execution.
- [x] **XSS Mitigation**: Contextual sanitization (`htmlspecialchars()`) across all user outputs.
- [x] **Error Suppression**: `APP_ENV` set to `production` suppresses raw system exceptions from end-users.
