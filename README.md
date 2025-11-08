# Uruhushya Software - Driving License Exam Preparation Platform

**Developer**: Benjamin NIYOMURINZI  
**Contact**: +250780239540  
**Client**: Uruhushya Software Ltd  
**Website**: www.uruhushya.rw  
**Email**: info@uruhushya.rw  

---

## 📋 About

Uruhushya Software is a comprehensive web application designed to help Rwandans prepare for their provisional driving license exam. The platform offers:

- Mock driving exams with real questions
- Multi-language support (Kinyarwanda & English)
- Driving school management system
- Agent referral program
- Subscription-based access
- Mobile Money & card payment integration
- Performance analytics & progress tracking

---

## 🛠️ Technology Stack

- **Backend**: PHP 7.4+ (Plain PHP, no frameworks)
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 4
- **Server**: Apache (XAMPP for Windows, LAMP for Linux)

---

## 📦 Installation Guide

### **For Windows (XAMPP)**

1. **Install XAMPP**
   - Download from: https://www.apachefriends.org/
   - Install to default location: `C:\xampp`

2. **Copy Project Files**
```
   Copy uruhushya_app folder to: C:\xampp\htdocs\
```

3. **Start Apache & MySQL**
   - Open XAMPP Control Panel
   - Click "Start" for Apache and MySQL

4. **Create Database**
   - Open browser: `http://localhost/phpmyadmin`
   - Click "Import" tab
   - Choose file: `C:\xampp\htdocs\uruhushya_app\sql\create_db.sql`
   - Click "Go"
   - Then import: `C:\xampp\htdocs\uruhushya_app\sql\seed.sql`

5. **Configure Database Connection**
   - Copy `config/config.example.php` to `config/config.php`
   - Edit `config/config.php` with your database details:
```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'uruhushya_db');
   define('DB_USER', 'root');
   define('DB_PASS', '');  // Empty for XAMPP default
```

6. **Access Application**
```
   Homepage: http://localhost/uruhushya_app/public/
   Admin Login: http://localhost/uruhushya_app/public/login.php
```

---

### **For Linux (LAMP Stack)**

1. **Install LAMP Stack**
```bash
   sudo apt update
   sudo apt install apache2 mysql-server php php-mysql libapache2-mod-php
```

2. **Copy Project Files**
```bash
   sudo cp -r uruhushya_app /var/www/html/
   sudo chown -R www-data:www-data /var/www/html/uruhushya_app
   sudo chmod -R 755 /var/www/html/uruhushya_app
```

3. **Create Database**
```bash
   sudo mysql -u root -p
```
```sql
   CREATE DATABASE uruhushya_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'uruhushya_user'@'localhost' IDENTIFIED BY 'YourSecurePassword123!';
   GRANT ALL PRIVILEGES ON uruhushya_db.* TO 'uruhushya_user'@'localhost';
   FLUSH PRIVILEGES;
   EXIT;
```

4. **Import SQL Files**
```bash
   mysql -u root -p uruhushya_db < /var/www/html/uruhushya_app/sql/create_db.sql
   mysql -u root -p uruhushya_db < /var/www/html/uruhushya_app/sql/seed.sql
```

5. **Configure Database Connection**
```bash
   cd /var/www/html/uruhushya_app/config
   sudo cp config.example.php config.php
   sudo nano config.php
```
   Update with your credentials:
```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'uruhushya_db');
   define('DB_USER', 'uruhushya_user');
   define('DB_PASS', 'YourSecurePassword123!');
```

6. **Set Permissions**
```bash
   sudo chown www-data:www-data /var/www/html/uruhushya_app/config/config.php
   sudo chmod 600 /var/www/html/uruhushya_app/config/config.php
```

7. **Enable Apache Rewrite Module** (for clean URLs later)
```bash
   sudo a2enmod rewrite
   sudo systemctl restart apache2
```

8. **Access Application**
```
   Homepage: http://localhost/uruhushya_app/public/
   OR: http://your-server-ip/uruhushya_app/public/
```

---

## 🔐 Default Login Credentials

After importing seed data, use these credentials:

**Admin Account:**
- Email: `admin@uruhushya.rw`
- Password: `Admin@2025`

**Driving School Account:**
- Email: `school@example.rw`
- Password: `School@2025`

**Agent Account:**
- Email: `agent@example.rw`
- Password: `Agent@2025`

**Student Account:**
- Email: `student@example.rw`
- Password: `Student@2025`

⚠️ **IMPORTANT**: Change these passwords immediately in production!

---

## 🗄️ Database Schema

The application uses 14 main tables:

1. **users** - All platform users (Admin, School, Agent, Student)
2. **schools** - Driving school information
3. **agents** - Agent details and referral tracking
4. **subscriptions** - User subscription plans
5. **payments** - Payment transaction records
6. **courses** - Course/lesson categories
7. **lessons** - Individual lesson content
8. **questions** - Exam question bank
9. **question_choices** - Multiple choice answers
10. **exams** - Exam configurations
11. **exam_attempts** - User exam history
12. **exam_responses** - Individual question responses
13. **session_devices** - Device/session tracking
14. **irembo_requests** - Registration requests from Irembo
15. **notifications** - Email/SMS notification log

---

## 🧪 Testing

1. **Verify Database Connection**
   - Open: `http://localhost/uruhushya_app/public/`
   - Should see homepage (no database errors)

2. **Test Admin Login**
   - Go to: `http://localhost/uruhushya_app/public/login.php`
   - Use admin credentials above
   - Should redirect to admin dashboard

3. **Check phpMyAdmin**
   - Open: `http://localhost/phpmyadmin`
   - Database `uruhushya_db` should have 15 tables
   - `users` table should have 4 sample users

---

## 🔒 Security Notes

1. **Never commit `config/config.php`** - Contains database credentials
2. **Use prepared statements** - All database queries use PDO prepared statements
3. **Password hashing** - Uses PHP `password_hash()` with bcrypt
4. **CSRF protection** - Implemented in all forms
5. **Session security** - HTTP-only cookies, session regeneration
6. **Input validation** - Server-side validation for all user input
7. **Output escaping** - Use `htmlspecialchars()` to prevent XSS

---

## 📝 .gitignore

The following files/folders are ignored in Git:
```
config/config.php
/vendor/
.env
*.log
.DS_Store
Thumbs.db
```

---

## 🚀 Deployment Checklist

Before deploying to production:

- [ ] Change all default passwords
- [ ] Update `config/config.php` with production database credentials
- [ ] Enable HTTPS (SSL certificate)
- [ ] Set up automated database backups
- [ ] Configure real payment gateway credentials
- [ ] Set up cron jobs for subscription expiry checks
- [ ] Test all user roles and permissions
- [ ] Implement rate limiting for API endpoints
- [ ] Enable error logging (disable display_errors in php.ini)
- [ ] Set up monitoring and alerting

---

## 📞 Support

For issues or questions:
- **Developer**: Benjamin NIYOMURINZI (+250780239540)
- **Email**: info@uruhushya.rw

---

## 📄 License

© 2025 Uruhushya Software Ltd. All rights reserved.
```

---

### **STEP 4: Create .gitignore**

**File**: `.gitignore` (in project root: `uruhushya_app/.gitignore`)
```
# Configuration files with sensitive data
config/config.php
.env

# Composer dependencies
/vendor/

# Logs
*.log
error_log
debug.log

# OS generated files
.DS_Store
.DS_Store?
._*
.Spotlight-V100
.Trashes
ehthumbs.db
Thumbs.db

# IDE files
.vscode/
.idea/
*.sublime-project
*.sublime-workspace

# Temporary files
*.tmp
*.temp
*.swp
*~

# User uploaded files (you may want to backup these separately)
public/uploads/
public/assets/images/users/

# Cache directories
cache/
tmp/