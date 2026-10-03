# NetSkills - Modern Networking Tutorial Platform

A professional, database-driven networking tutorial website designed for students and beginners who want to learn computer networking through online tutorials and video lessons.

## Features

### User Features
- **User Authentication**: Secure registration and login system with password hashing
- **Course Browsing**: Filter and search courses by category, difficulty, and keywords
- **Progress Tracking**: Track learning progress across all courses
- **Certificate System**: Automatically generate downloadable certificates upon course completion
- **Reviews & Feedback**: Submit reviews with ratings and images
- **Responsive Design**: Works seamlessly on desktop, tablet, and mobile devices

### Admin Features
- **Dashboard Analytics**: View statistics with Chart.js visualizations
- **User Management**: View and manage registered users
- **Course Management**: Add, edit, and delete courses
- **Certificate Management**: View and verify certificates
- **Review Moderation**: Approve or reject user reviews
- **Activity Logs**: Monitor user activities and system events

### Security Features
- **Role-Based Access Control (RBAC)**: User and Admin roles
- **CSRF Protection**: Token-based CSRF protection on all forms
- **Rate Limiting**: Prevent brute force attacks
- **SQL Injection Prevention**: Prepared statements throughout
- **XSS Protection**: Input sanitization and output escaping
- **Secure Session Handling**: HTTP-only cookies and session validation

## Tech Stack

- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Charts**: Chart.js 3.9.1
- **Icons**: Font Awesome 6.4.0
- **Local Environment**: XAMPP

## Installation

### Prerequisites
- XAMPP (or similar LAMP/WAMP stack)
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Composer (optional, for dependencies)

### Step-by-Step Installation

1. **Clone/Copy Project**
   ```bash
   # Copy the project to your XAMPP htdocs directory
   cp -r N3tSkills /xampp/htdocs/
   ```

2. **Start XAMPP Services**
   - Open XAMPP Control Panel
   - Start Apache and MySQL services

3. **Database Setup**
   - Open PHPMyAdmin (http://localhost/phpmyadmin)
   - Create a new database named `netskills_db`
   - OR run the included setup script:
     ```php
     // Access this URL to initialize the database
     http://localhost/N3tSkills/setup.php
     ```

4. **Configure Database Connection**
   - Open `config/database.php`
   - Update database credentials if needed:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USERNAME', 'root');
     define('DB_PASSWORD', ''); // Your MySQL password
     define('DB_NAME', 'netskills_db');
     ```

5. **Create Required Directories**
   ```bash
   mkdir -p public/uploads/avatars
   mkdir -p public/uploads/reviews
   mkdir -p public/uploads/certificates
   mkdir -p logs
   chmod -R 755 public/uploads
   chmod 755 logs
   ```

6. **Access the Application**
   - Open your browser and navigate to: `http://localhost/N3tSkills/`

### Default Admin Account
- **Email**: admin@netskills.com
- **Password**: admin123
- **Dashboard**: http://localhost/N3tSkills/admin/dashboard.php

**⚠️ IMPORTANT**: Change the default admin password immediately after first login!

## Database Schema

### Tables

#### `users`
- Stores user account information
- Fields: id, username, email, password, full_name, role, profile_image, created_at, updated_at, last_login, is_active

#### `courses`
- Stores course information
- Fields: id, title, description, category, difficulty, thumbnail, duration_minutes, order_index, is_published, created_at, updated_at

#### `lessons`
- Stores individual lesson content
- Fields: id, course_id, title, description, video_url, duration_minutes, order_index, is_required, created_at

#### `user_progress`
- Tracks user progress through lessons
- Fields: id, user_id, course_id, lesson_id, is_completed, completed_at, created_at, updated_at

#### `completed_courses`
- Records completed courses
- Fields: id, user_id, course_id, completed_at, grade

#### `certificates`
- Stores generated certificates
- Fields: id, certificate_code, user_id, course_id, user_name, course_name, issued_date, pdf_path, is_valid, created_at

#### `reviews`
- Stores user reviews and feedback
- Fields: id, user_id, course_id, rating, comment, image_path, is_approved, created_at, updated_at

#### `activity_logs`
- Logs user and system activities
- Fields: id, user_id, activity_type, description, ip_address, user_agent, created_at

#### `sessions`
- Manages user sessions and remember-me tokens
- Fields: id, user_id, session_token, expires_at, created_at

## Project Structure

```
N3tSkills/
├── config/
│   ├── config.php          # Main configuration
│   └── database.php        # Database configuration and setup
├── middleware/
│   └── auth.php            # Authentication middleware
├── models/
│   ├── User.php            # User model
│   ├── Course.php          # Course model
│   ├── Certificate.php     # Certificate model
│   └── Review.php          # Review model
├── public/
│   ├── css/
│   │   ├── style.css       # Main stylesheet
│   │   └── responsive.css  # Responsive design
│   ├── js/
│   │   └── main.js         # Main JavaScript
│   ├── images/             # Static images
│   └── uploads/            # User uploads
│       ├── avatars/
│       ├── reviews/
│       └── certificates/
├── includes/
│   ├── header.php          # Header template
│   └── footer.php          # Footer template
├── admin/                  # Admin panel (protected)
├── index.php               # Homepage
├── login.php               # Login page
├── register.php            # Registration page
├── logout.php              # Logout handler
├── tutorials.php           # Courses listing
├── reviews.php             # Reviews page
├── about.php               # About page
├── course.php              # Single course view
├── certificates.php        # User certificates
├── setup.php               # Database setup script
└── README.md               # This file
```

## Color Palette

- **Primary**: #8B0000 (Dark Red/Maroon)
- **Primary Dark**: #6B0000
- **Primary Light**: #A52A2A
- **Dark Background**: #1a1a1a
- **Dark Surface**: #2d2d2d
- **Dark Card**: #333333
- **Text Primary**: #ffffff
- **Text Secondary**: #b0b0b0
- **Success**: #28a745
- **Warning**: #ffc107
- **Error**: #dc3545
- **Info**: #17a2b8

## API Endpoints

### Authentication
- `POST /api/login.php` - User login
- `POST /api/register.php` - User registration
- `POST /api/logout.php` - User logout

### Courses
- `GET /api/get-courses.php` - Get all courses
- `GET /api/get-course.php?id=1` - Get single course
- `POST /api/complete-lesson.php` - Mark lesson as complete
- `GET /api/get-progress.php?course_id=1` - Get user progress

### Certificates
- `GET /api/verify-certificate.php?code=NS-XXX` - Verify certificate
- `GET /api/download-certificate.php?id=1` - Download certificate PDF

## Course Categories

1. **Networking Basics** - Introduction to networking concepts
2. **IP Addressing** - IPv4 and IPv6 addressing
3. **Cisco Fundamentals** - Cisco certification preparation
4. **Routing & Switching** - Advanced routing protocols
5. **Network Security** - Security best practices
6. **Troubleshooting** - Network diagnostics and repair

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## Security Notes

- Always use HTTPS in production
- Enable PHP error logging but disable display_errors
- Use strong passwords for database accounts
- Regularly update PHP and MySQL
- Implement rate limiting on production
- Use a Web Application Firewall (WAF)
- Regularly backup your database

## License

This project is open-source and available for educational purposes.

## Support

For support and questions:
- Email: support@netskills.com
- Documentation: [Link to docs]

## Credits

- Font Awesome for icons
- Chart.js for analytics charts
- Inter font family
- JetBrains Mono font

---

**NetSkills** - Master Computer Networking with confidence!