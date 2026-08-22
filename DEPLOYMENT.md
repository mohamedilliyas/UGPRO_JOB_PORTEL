# UgPro University Job Portal - Deployment & Cloud Database Guide

This guide provides step-by-step instructions for deploying the **UgPro University Job & Career Portal** across Vercel Serverless, Cloud MySQL providers, Keep-Alive Cron Jobs, local development environments, cPanel shared hosting, and Linux Cloud VPS.

---

## 1. System Requirements & Architecture

- **PHP**: 7.4, 8.0, 8.1, 8.2+ (with `mysqli`, `pdo_mysql`, `mbstring`, `fileinfo`, `openssl` extensions enabled)
- **Database**: MySQL 5.7+, MariaDB 10.3+, or TiDB Serverless Cloud MySQL
- **Web Server**: Vercel Serverless / Apache 2.4+ / Nginx
- **HTTPS/SSL**: Enabled by default on Vercel

---

## 2. Default Seed Demo Credentials

| Role | Username / Email | Password | Dashboard Link |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin` or `admin@ugpro.lk` | `admin123` | [`/admin/login.php`](file:///d:/Projects/UGPRO_JOB_PORTEL/admin/login.php) |
| **Undergraduate Student** | `illiyas@vau.ac.lk` | `student123` | [`/signin_undergraduate.php`](file:///d:/Projects/UGPRO_JOB_PORTEL/signin_undergraduate.php) |
| **Partner Employer (Virtusa)** | `careers@virtusa.com` | `employer123` | [`/signin_employer.php`](file:///d:/Projects/UGPRO_JOB_PORTEL/signin_employer.php) |
| **Partner Employer (WSO2)** | `recruitment@wso2.com` | `employer123` | [`/signin_employer.php`](file:///d:/Projects/UGPRO_JOB_PORTEL/signin_employer.php) |

---

## 3. Vercel Deployment & Free Cloud MySQL Setup

### Step 1: Create a Free Permanent Cloud MySQL Database

Choose any of these reliable, permanently free cloud MySQL services:

#### Option A: TiDB Serverless (Recommended — 100% Free Forever, 5GB storage, Instant SSL)
1. Sign up at [https://tidbcloud.com/](https://tidbcloud.com/).
2. Create a free **Serverless Cluster**.
3. Under **Overview**, click **Connect** -> Choose **General Connection String / URI**.
4. Copy the connection URI:
   ```
   mysql://<user>:<password>@gateway01.<region>.prod.aws.tidbcloud.com:4000/<dbname>?ssl-mode=VERIFY_IDENTITY
   ```
5. In TiDB Cloud SQL Editor, import or paste the schema from [`database.sql`](file:///d:/Projects/UGPRO_JOB_PORTEL/database.sql).

#### Option B: Clever Cloud MySQL (Free Add-on)
1. Sign up at [https://www.clever-cloud.com/](https://www.clever-cloud.com/).
2. Create a free MySQL database add-on.
3. Copy the `MYSQL_ADDON_URI` or Host, Port, Database, User, Password.

#### Option C: Aiven Cloud MySQL
1. Create a MySQL service in Aiven Console.
2. Copy the Service URI (`mysql://avnadmin:password@host:port/defaultdb?ssl-mode=REQUIRED`).

---

### Step 2: Configure Environment Variables in Vercel

1. Go to your **Vercel Project Dashboard** -> **Settings** -> **Environment Variables**.
2. Add the database connection variable (either full URI or individual keys):

| Variable Name | Value Example | Description |
| :--- | :--- | :--- |
| `DATABASE_URL` | `mysql://user:pass@host:3306/dbname` | Full MySQL connection URI |
| *or* `DB_HOST` | `gateway01.region.tidbcloud.com` | Database Hostname |
| *or* `DB_USER` | `xxxx.root` | Database Username |
| *or* `DB_PASS` | `YourStrongPassword` | Database Password |
| *or* `DB_NAME` | `vavuniyauniversity` | Database Name |
| *or* `DB_PORT` | `3306` or `4000` | Database Port |
| `APP_ENV` | `production` | Suppresses raw server errors |
| `CRON_SECRET` | `ugpro_cron_keepalive_secret_2026` | Keep-alive auth secret |

3. Click **Save** and trigger a **Redeploy** on Vercel.

---

## 4. Automated Keep-Alive Cron Job Setup

Free cloud database instances sleep after 5 to 15 minutes of inactivity. UgPro includes a dedicated keep-alive cron job to keep your database active and healthy 24/7.

### 1. Automated Vercel Cron Job
`vercel.json` is pre-configured with:
```json
{
  "crons": [
    {
      "path": "/api/cron.php",
      "schedule": "*/10 * * * *"
    }
  ]
}
```
Vercel will trigger `/api/cron.php` automatically every 10 minutes from its global edge network.

### 2. External Backup Cron Monitoring (cron-job.org or UptimeRobot)
To add a secondary free 24/7 keep-alive pinger:
1. Sign up at [https://cron-job.org/](https://cron-job.org/) (100% Free).
2. Click **Create Cronjob**.
3. **Title**: `UgPro DB Keep-Alive`
4. **URL**:
   ```
   https://ugpro-job-portel.vercel.app/api/cron.php?secret=ugpro_cron_keepalive_secret_2026
   ```
5. **Schedule**: Every `10 minutes` (or `5 minutes`).
6. Click **Save**.

### 3. Testing Cron Keep-Alive Output
Access `https://ugpro-job-portel.vercel.app/cron.php` in your browser. It returns:
```json
{
  "success": true,
  "status": "HEALTHY",
  "message": "Database keep-alive ping successful. Connection is active.",
  "timestamp": "2026-08-22T06:30:00+00:00",
  "metrics": {
    "total_execution_ms": 14.2,
    "db_latency_ms": 9.8,
    "cleaned_sessions": 0
  },
  "database": {
    "connected": true,
    "host": "...",
    "database": "...",
    "port": 3306
  }
}
```

---

## 5. High-Availability Graceful Fallback Mode

If your remote database is waking up, sleeping, or temporarily offline:
- **Zero Crashes**: All views (`index.php`, `jobs.php`, `browse_candidates.php`, `job_details.php`) automatically serve high-quality cached seed data.
- **Demo Logins**: Student, Employer, and Admin demo accounts continue to work seamlessly in testing mode.
- **Automatic Recovery**: As soon as the cloud database responds, the system resumes live queries without requiring any server restarts.

---

## 6. Option B: Local Deployment (XAMPP / WAMP)

1. Start Apache & MySQL in XAMPP Control Panel.
2. Open `http://localhost/phpmyadmin/` -> Import `database.sql`.
3. Place project files into `C:\xampp\htdocs\UGPRO_JOB_PORTEL`.
4. Open `http://localhost/UGPRO_JOB_PORTEL/`.

---

## 7. Option C: Linux Cloud VPS (Ubuntu 22.04 / 24.04 LTS)

```bash
sudo apt update && sudo apt install -y apache2 mariadb-server php php-mysql php-mbstring php-xml php-curl php-zip
sudo mysql -u root -e "CREATE DATABASE vavuniyauniversity CHARACTER SET utf8mb4; GRANT ALL ON vavuniyauniversity.* TO 'ugpro_user'@'localhost' IDENTIFIED BY 'StrongPass123!';"
sudo mysql -u ugpro_user -p vavuniyauniversity < /var/www/html/database.sql
sudo cp -r /path/to/UGPRO_JOB_PORTEL /var/www/html/ugpro
sudo chown -R www-data:www-data /var/www/html/ugpro/uploads
sudo a2enmod rewrite && sudo systemctl restart apache2
```
