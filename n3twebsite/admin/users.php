<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../config/database.php';
$conn = getDBConnection();

// Get all users
$users = [];
$result = $conn->query("SELECT * FROM users WHERE role = 'user' ORDER BY created_at DESC");
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

$page_title = 'Manage Users';
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
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></noscript>
    <link rel="stylesheet" href="../public/css/style.css?v=<?php echo @filemtime(__DIR__ . '/../public/css/style.css') ?: time(); ?>">
    <link rel="stylesheet" href="../public/css/responsive.css?v=<?php echo @filemtime(__DIR__ . '/../public/css/responsive.css') ?: time(); ?>">
    <style>
        .admin-sidebar { position: fixed; left: 0; top: 70px; width: 260px; height: calc(100vh - 70px); background: var(--darker); border-right: 1px solid var(--glass-border); padding: var(--spacing-lg); overflow-y: auto; z-index: 100; transition: left var(--transition-normal); }
        .admin-content { margin-left: 260px; margin-top: 70px; padding: var(--spacing-2xl); min-height: calc(100vh - 70px); }
        .admin-nav-item { display: flex; align-items: center; gap: var(--spacing-md); padding: var(--spacing-md); color: var(--text-secondary); border-radius: var(--radius-md); margin-bottom: var(--spacing-sm); transition: all var(--transition-fast); text-decoration: none; }
        .admin-nav-item:hover, .admin-nav-item.active { background: var(--glass-bg); color: var(--text-primary); }
        .admin-nav-item i { width: 20px; text-align: center; }
        .admin-table { width: 100%; border-collapse: collapse; }
        .admin-table th, .admin-table td { padding: var(--spacing-md); text-align: left; border-bottom: 1px solid var(--glass-border); white-space: nowrap; }
        .admin-table th { color: var(--text-secondary); font-weight: 500; font-size: var(--text-sm); text-transform: uppercase; letter-spacing: 0.5px; }
        .admin-table td { color: var(--text-primary); }
        .admin-card { background: var(--dark-card); border: 1px solid var(--glass-border); border-radius: var(--radius-lg); overflow: hidden; }
        .admin-card-header { padding: var(--spacing-lg); border-bottom: 1px solid var(--glass-border); display: flex; justify-content: space-between; align-items: center; }
        .admin-card-header h3 { margin: 0; font-size: var(--text-lg); }
        .admin-card-body { padding: var(--spacing-lg); overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .admin-nav-toggle { display: none; background: none; border: none; cursor: pointer; padding: var(--spacing-sm); color: var(--text-primary); font-size: var(--text-xl); }
        @media (max-width: 991px) {
            .admin-nav-toggle { display: flex; align-items: center; justify-content: center; }
            .admin-sidebar { left: -100%; width: 100%; height: calc(100vh - 70px); top: 70px; }
            .admin-sidebar.active { left: 0; }
            .admin-sidebar nav { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: var(--spacing-sm); }
            .admin-nav-item { margin-bottom: 0; }
            .admin-content { margin-left: 0; margin-top: 0; padding: var(--spacing-md) !important; }
            .admin-table { min-width: 600px; }
        }
        @media (max-width: 575px) { .admin-sidebar nav { grid-template-columns: 1fr; } .admin-table { min-width: 500px; } }
        @media (max-width: 399px) { .admin-table { min-width: 400px; } }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="../index.php" class="nav-logo"><img src="../public/images/logo.png?v=<?php echo @filemtime('../public/images/logo.png') ?: time(); ?>" alt="NetSkill" height="36" style="object-fit: contain;"><span>NetSkill Admin</span></a>
            
            <div style="display: flex; align-items: center; gap: var(--spacing-md);">
                <span style="color: var(--text-secondary);"><i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <button class="admin-nav-toggle" id="adminNavToggle" aria-label="Toggle admin menu">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </nav>
    
    <aside class="admin-sidebar" id="adminSidebar">
        <nav>
            <a href="dashboard.php" class="admin-nav-item"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
            <a href="courses.php" class="admin-nav-item"><i class="fas fa-graduation-cap"></i><span>Courses</span></a>
            <a href="users.php" class="admin-nav-item active"><i class="fas fa-users"></i><span>Users</span></a>
            <a href="reviews.php" class="admin-nav-item"><i class="fas fa-star"></i><span>Reviews</span></a>
            <a href="certificates.php" class="admin-nav-item"><i class="fas fa-certificate"></i><span>Certificates</span></a>
            <a href="../index.php" class="admin-nav-item"><i class="fas fa-external-link-alt"></i><span>View Site</span></a>
            <hr style="border-color: var(--glass-border); margin: var(--spacing-md) 0;">
            <a href="../logout.php" class="admin-nav-item" style="color: var(--error);"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </nav>
    </aside>
    
    <main class="admin-content">
        <h1 style="margin-bottom: var(--spacing-2xl);"><i class="fas fa-users"></i> Manage Users</h1>
        
        <div class="admin-card">
            <div class="admin-card-header">
                <h3>All Users (<?php echo count($users); ?>)</h3>
            </div>
            <div class="admin-card-body">
                <?php if (empty($users)): ?>
                    <p style="color: var(--text-secondary);">No users found.</p>
                <?php else: ?>
                    <table class="admin-table">
                        <thead>
                            <tr><th>ID</th><th>Username</th><th>Full Name</th><th>Email</th><th>Joined</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
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
    </main>
    
    <script src="../public/js/main.js?v=<?php echo @filemtime(__DIR__ . '/../public/js/main.js') ?: time(); ?>"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var toggle = document.getElementById('adminNavToggle');
        var sidebar = document.getElementById('adminSidebar');
        if (toggle && sidebar) {
            toggle.addEventListener('click', function() { sidebar.classList.toggle('active'); });
            document.addEventListener('click', function(e) {
                if (!sidebar.contains(e.target) && !toggle.contains(e.target)) { sidebar.classList.remove('active'); }
            });
        }
    });
    </script>
</body>
</html>