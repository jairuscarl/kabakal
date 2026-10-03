<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../config/database.php';
$conn = getDBConnection();

$stats = [];

$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'");
$stats['total_users'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM courses WHERE is_published = 1");
$stats['total_courses'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM lessons");
$stats['total_lessons'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM completed_courses");
$stats['total_completions'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM certificates");
$stats['total_certificates'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM reviews");
$stats['total_reviews'] = $result->fetch_assoc()['count'];

$recentUsers = [];
$result = $conn->query("SELECT id, username, email, full_name, created_at FROM users WHERE role = 'user' ORDER BY created_at DESC LIMIT 5");
while ($row = $result->fetch_assoc()) {
    $recentUsers[] = $row;
}

$recentCompletions = [];
$result = $conn->query("SELECT cc.completed_at, u.username, u.full_name, c.title as course_title 
                        FROM completed_courses cc 
                        JOIN users u ON cc.user_id = u.id 
                        JOIN courses c ON cc.course_id = c.id 
                        ORDER BY cc.completed_at DESC LIMIT 5");
while ($row = $result->fetch_assoc()) {
    $recentCompletions[] = $row;
}

$pendingReviews = [];
$result = $conn->query("SELECT r.id, r.rating, r.comment, r.created_at, u.username, u.full_name, c.title as course_title 
                        FROM reviews r 
                        JOIN users u ON r.user_id = u.id 
                        LEFT JOIN courses c ON r.course_id = c.id 
                        WHERE r.is_approved = 0 
                        ORDER BY r.created_at DESC LIMIT 5");
while ($row = $result->fetch_assoc()) {
    $pendingReviews[] = $row;
}

$page_title = 'Admin Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - NetSkill</title>
    <link rel="icon" type="image/png" sizes="16x16" href="../public/images/favicon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../public/images/favicon.png">
    <link rel="icon" type="image/png" sizes="96x96" href="../public/images/favicon.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../public/images/favicon.png">
    <link rel="shortcut icon" href="../public/images/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    </noscript>
    <link rel="stylesheet" href="../public/css/style.css?v=<?php echo @filemtime(__DIR__ . '/../public/css/style.css') ?: time(); ?>">
    <link rel="stylesheet" href="../public/css/responsive.css?v=<?php echo @filemtime(__DIR__ . '/../public/css/responsive.css') ?: time(); ?>">
    <style>
        .admin-sidebar {
            position: fixed;
            left: 0;
            top: 70px;
            width: 260px;
            height: calc(100vh - 70px);
            background: var(--darker);
            border-right: 1px solid var(--glass-border);
            padding: var(--spacing-lg);
            overflow-y: auto;
            z-index: 100;
            transition: left var(--transition-normal);
        }
        
        .admin-content {
            margin-left: 260px;
            margin-top: 70px;
            padding: var(--spacing-2xl);
            min-height: calc(100vh - 70px);
        }
        
        .admin-nav-item {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            padding: var(--spacing-md);
            color: var(--text-secondary);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-sm);
            transition: all var(--transition-fast);
            text-decoration: none;
        }
        
        .admin-nav-item:hover,
        .admin-nav-item.active {
            background: var(--glass-bg);
            color: var(--text-primary);
        }
        
        .admin-nav-item i {
            width: 20px;
            text-align: center;
        }
        
        .stats-card {
            background: var(--dark-card);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            padding: var(--spacing-xl);
            display: flex;
            align-items: center;
            gap: var(--spacing-lg);
        }
        
        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: var(--text-2xl);
        }
        
        .stats-icon.primary {
            background: rgba(139, 0, 0, 0.2);
            color: var(--primary-light);
        }
        
        .stats-icon.success {
            background: rgba(40, 167, 69, 0.2);
            color: var(--success);
        }
        
        .stats-icon.warning {
            background: rgba(255, 193, 7, 0.2);
            color: var(--warning);
        }
        
        .stats-icon.info {
            background: rgba(23, 162, 184, 0.2);
            color: var(--info);
        }
        
        .stats-info h3 {
            font-size: var(--text-3xl);
            margin-bottom: 0;
            font-family: var(--font-mono);
        }
        
        .stats-info p {
            color: var(--text-secondary);
            margin: 0;
            font-size: var(--text-sm);
        }
        
        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .admin-table th,
        .admin-table td {
            padding: var(--spacing-md);
            text-align: left;
            border-bottom: 1px solid var(--glass-border);
            white-space: nowrap;
        }
        
        .admin-table th {
            color: var(--text-secondary);
            font-weight: 500;
            font-size: var(--text-sm);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .admin-table td {
            color: var(--text-primary);
        }
        
        .admin-card {
            background: var(--dark-card);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            overflow: hidden;
        }
        
        .admin-card-header {
            padding: var(--spacing-lg);
            border-bottom: 1px solid var(--glass-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-card-header h3 {
            margin: 0;
            font-size: var(--text-lg);
        }
        
        .admin-card-body {
            padding: var(--spacing-lg);
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .admin-nav-toggle {
            display: none;
            background: none;
            border: none;
            cursor: pointer;
            padding: var(--spacing-sm);
            color: var(--text-primary);
            font-size: var(--text-xl);
        }

        @media (max-width: 1199px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: var(--spacing-md) !important;
            }
            .stats-card {
                padding: var(--spacing-md);
            }
            .stats-icon {
                width: 50px;
                height: 50px;
                font-size: var(--text-xl);
            }
            .stats-info h3 {
                font-size: var(--text-2xl);
            }
            .grid {
                grid-template-columns: 1fr !important;
            }
        }

        @media (max-width: 991px) {
            .admin-nav-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .admin-sidebar {
                left: -100%;
                width: 100%;
                height: calc(100vh - 70px);
                top: 70px;
            }
            .admin-sidebar.active {
                left: 0;
            }
            .admin-sidebar nav {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: var(--spacing-sm);
            }
            .admin-nav-item {
                margin-bottom: 0;
            }
            .admin-content {
                margin-left: 0;
                margin-top: 0;
                padding: var(--spacing-md) !important;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: var(--spacing-sm) !important;
            }
            .stats-card {
                padding: var(--spacing-sm);
                gap: var(--spacing-sm);
            }
            .stats-icon {
                width: 40px;
                height: 40px;
                font-size: var(--text-lg);
            }
            .stats-info h3 {
                font-size: var(--text-xl);
            }
            .admin-table {
                min-width: 600px;
            }
        }

        @media (max-width: 575px) {
            .stats-grid {
                grid-template-columns: 1fr 1fr !important;
                gap: var(--spacing-xs) !important;
            }
            .stats-card {
                flex-direction: column;
                padding: var(--spacing-sm);
                text-align: center;
            }
            .admin-sidebar nav {
                grid-template-columns: 1fr;
            }
            .admin-table {
                min-width: 500px;
            }
        }

        @media (max-width: 399px) {
            .admin-table {
                min-width: 400px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="../index.php" class="nav-logo">
                <img src="../public/images/logo.png?v=<?php echo @filemtime('../public/images/logo.png') ?: time(); ?>" alt="NetSkill" height="36" style="object-fit: contain;">
                <span>NetSkill Admin</span>
            </a>
            
            <div style="display: flex; align-items: center; gap: var(--spacing-md);">
                <span style="color: var(--text-secondary);">
                    <i class="fas fa-user-shield"></i> 
                    <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                </span>
                <button class="admin-nav-toggle" id="adminNavToggle" aria-label="Toggle admin menu">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </nav>
    
    <aside class="admin-sidebar" id="adminSidebar">
        <nav>
            <a href="dashboard.php" class="admin-nav-item active">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            <a href="courses.php" class="admin-nav-item">
                <i class="fas fa-graduation-cap"></i>
                <span>Courses</span>
            </a>
            <a href="users.php" class="admin-nav-item">
                <i class="fas fa-users"></i>
                <span>Users</span>
            </a>
            <a href="reviews.php" class="admin-nav-item">
                <i class="fas fa-star"></i>
                <span>Reviews</span>
            </a>
            <a href="certificates.php" class="admin-nav-item">
                <i class="fas fa-certificate"></i>
                <span>Certificates</span>
            </a>
            <a href="../index.php" class="admin-nav-item">
                <i class="fas fa-external-link-alt"></i>
                <span>View Site</span>
            </a>
            <hr style="border-color: var(--glass-border); margin: var(--spacing-md) 0;">
            <a href="../logout.php" class="admin-nav-item" style="color: var(--error);">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </nav>
    </aside>
    
    <main class="admin-content">
        <h1 style="margin-bottom: var(--spacing-2xl);">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </h1>
        
        <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: var(--spacing-2xl);">
            <div class="stats-card">
                <div class="stats-icon primary">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stats-info">
                    <h3><?php echo number_format($stats['total_users']); ?></h3>
                    <p>Total Users</p>
                </div>
            </div>
            <div class="stats-card">
                <div class="stats-icon success">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="stats-info">
                    <h3><?php echo number_format($stats['total_courses']); ?></h3>
                    <p>Published Courses</p>
                </div>
            </div>
            <div class="stats-card">
                <div class="stats-icon warning">
                    <i class="fas fa-book"></i>
                </div>
                <div class="stats-info">
                    <h3><?php echo number_format($stats['total_lessons']); ?></h3>
                    <p>Total Lessons</p>
                </div>
            </div>
            <div class="stats-card">
                <div class="stats-icon info">
                    <i class="fas fa-certificate"></i>
                </div>
                <div class="stats-info">
                    <h3><?php echo number_format($stats['total_certificates']); ?></h3>
                    <p>Certificates Issued</p>
                </div>
            </div>
        </div>
        
        <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: var(--spacing-2xl);">
            <div class="stats-card">
                <div class="stats-icon success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stats-info">
                    <h3><?php echo number_format($stats['total_completions']); ?></h3>
                    <p>Course Completions</p>
                </div>
            </div>
            <div class="stats-card">
                <div class="stats-icon primary">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stats-info">
                    <h3><?php echo number_format($stats['total_reviews']); ?></h3>
                    <p>Total Reviews</p>
                </div>
            </div>
        </div>
        
        <div class="grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: var(--spacing-xl);">
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3><i class="fas fa-user-plus"></i> Recent Users</h3>
                    <a href="users.php" class="btn btn-sm btn-outline">View All</a>
                </div>
                <div class="admin-card-body">
                    <?php if (empty($recentUsers)): ?>
                        <p style="color: var(--text-secondary);">No users yet.</p>
                    <?php else: ?>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentUsers as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3><i class="fas fa-trophy"></i> Recent Completions</h3>
                    <a href="courses.php" class="btn btn-sm btn-outline">View All</a>
                </div>
                <div class="admin-card-body">
                    <?php if (empty($recentCompletions)): ?>
                        <p style="color: var(--text-secondary);">No completions yet.</p>
                    <?php else: ?>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Course</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentCompletions as $completion): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($completion['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($completion['course_title']); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($completion['completed_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php if (!empty($pendingReviews)): ?>
        <div class="admin-card" style="margin-top: var(--spacing-2xl);">
            <div class="admin-card-header">
                <h3><i class="fas fa-clock"></i> Pending Reviews (<?php echo count($pendingReviews); ?>)</h3>
                <a href="reviews.php" class="btn btn-sm btn-outline">Manage All</a>
            </div>
            <div class="admin-card-body">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Course</th>
                            <th>Rating</th>
                            <th>Comment</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingReviews as $review): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($review['full_name']); ?></td>
                                <td><?php echo $review['course_title'] ? htmlspecialchars($review['course_title']) : '<em>General</em>'; ?></td>
                                <td>
                                    <span style="color: var(--warning);">
                                        <?php for ($i = 0; $i < $review['rating']; $i++) echo '<i class="fas fa-star"></i>'; ?>
                                        <?php for ($i = $review['rating']; $i < 5; $i++) echo '<i class="far fa-star"></i>'; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars(substr($review['comment'], 0, 50)) . (strlen($review['comment']) > 50 ? '...' : ''); ?></td>
                                <td><?php echo date('M j, Y', strtotime($review['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <script src="../public/js/main.js?v=<?php echo @filemtime(__DIR__ . '/../public/js/main.js') ?: time(); ?>"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggle = document.getElementById('adminNavToggle');
        const sidebar = document.getElementById('adminSidebar');
        
        if (toggle && sidebar) {
            toggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
            });
            
            document.addEventListener('click', function(e) {
                if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
                    sidebar.classList.remove('active');
                }
            });
        }
    });
    </script>
</body>
</html>