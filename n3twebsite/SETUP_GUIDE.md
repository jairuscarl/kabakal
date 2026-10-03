# NetSkills - Quick Setup Guide

## 🖥️ How to Open in Browser

This website is built with **PHP**, which means it needs a web server to run. You cannot just double-click the files to open them.

### Option 1: Using XAMPP (Recommended)

#### Step 1: Install XAMPP
1. Download XAMPP from: https://www.apachefriends.org/download.html
2. Install it to the default location (usually `C:\xampp`)

#### Step 2: Copy Project Files
1. Copy the entire `N3tSkills` folder
2. Paste it into: `C:\xampp\htdocs\`
3. Your path should be: `C:\xampp\htdocs\N3tSkills\index.php`

#### Step 3: Start XAMPP Services
1. Open **XAMPP Control Panel**
2. Click **Start** next to **Apache**
3. Click **Start** next to **MySQL**
4. Wait for both to turn green

#### Step 4: Setup Database
**Method A - Automatic (Easiest):**
1. Open your browser
2. Go to: `http://localhost/N3tSkills/setup.php`
3. The database will be created automatically

**Method B - Manual:**
1. Open browser and go to: `http://localhost/phpmyadmin`
2. Click **New** in the left sidebar
3. Database name: `netskills_db`
4. Click **Create**
5. Then go to: `http://localhost/N3tSkills/setup.php` to create tables

#### Step 5: Access the Website
Open your browser and go to:
```
http://localhost/N3tSkills/
```

**That's it!** The website should now be running.

---

### Option 2: Using PHP Built-in Server (Quick Test)

If you already have PHP installed and just want to test quickly:

1. Open Command Prompt in the N3tSkills folder:
   ```cmd
   cd C:\path\to\N3tSkills
   ```

2. Start PHP server:
   ```cmd
   php -S localhost:8000
   ```

3. Open browser and go to:
   ```
   http://localhost:8000
   ```

**Note:** You'll still need MySQL running for the database. This method is only for testing.

---

## 🎯 Default Login Credentials

After setup, you can login as admin:

- **Email:** `admin@netskills.com`
- **Password:** `admin123`
- **Admin Dashboard:** `http://localhost/N3tSkills/admin/dashboard.php`

⚠️ **Change the admin password immediately after first login!**

---

## 🔧 Troubleshooting

### Problem: "404 Not Found" or "Object not found!"
**Solution:**
- Make sure Apache is running in XAMPP
- Check that the folder is in `C:\xampp\htdocs\N3tSkills\`
- The URL should be: `http://localhost/N3tSkills/` (not `http://localhost/`)

### Problem: "Error establishing a database connection"
**Solution:**
- Make sure MySQL is running in XAMPP
- Run the setup script: `http://localhost/N3tSkills/setup.php`
- Check database credentials in `config/database.php`

### Problem: Blank white page
**Solution:**
- Check for PHP errors in `C:\xampp\apache\logs\error.log`
- Make sure PHP is enabled in XAMPP
- Check if all files were copied correctly

### Problem: "Access denied for user 'root'@'localhost'"
**Solution:**
- Open `config/database.php`
- Change `DB_PASSWORD` to your MySQL password (usually empty in XAMPP)
- Or set a password for MySQL root user in phpMyAdmin

---

## 📁 Project Structure

```
N3tSkills/
├── index.php              ← Homepage (start here!)
├── login.php              ← Login page
├── register.php           ← Registration page
├── tutorials.php          ← Browse courses
├── reviews.php            ← Reviews and feedback
├── about.php              ← About page
├── certificates.php       ← User certificates (after login)
├── setup.php              ← Database setup script
├── config/                ← Configuration files
├── models/                ← Database models
├── middleware/            ← Authentication middleware
├── includes/              ← Header and footer templates
└── public/                ← CSS, JS, images, uploads
```

---

## 🌐 What Files to Open?

**DO NOT open PHP files directly!** Instead:

✅ **Correct:** Open browser and go to `http://localhost/N3tSkills/`
❌ **Wrong:** Double-click `index.php` file

The web server (Apache) processes the PHP files and sends HTML to your browser.

---

## 📝 Important Notes

1. **PHP files need a server** - They generate HTML dynamically
2. **Database required** - MySQL stores all data
3. **XAMPP provides both** - Apache (web server) + MySQL (database)
4. **Keep XAMPP running** - Don't close XAMPP while using the website

---

## 🎨 What You'll See

Once running, you'll see:
- **Homepage** with hero section, featured courses, statistics
- **Tutorials page** with course cards and filters
- **Reviews page** with user feedback
- **Login/Register** pages for authentication
- **Admin dashboard** (after logging in as admin)

---

## 🆘 Need Help?

If you're still having trouble:

1. Check XAMPP error logs: `C:\xampp\apache\logs\error.log`
2. Check PHP errors: Enable `display_errors` in `config/config.php`
3. Make sure all files are in the correct location
4. Try restarting XAMPP services

---

**Ready to start? Follow Step 1-5 above and you'll be running in minutes!** 🚀