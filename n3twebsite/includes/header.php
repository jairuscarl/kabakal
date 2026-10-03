<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>NetSkill</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="16x16" href="public/images/favicon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="public/images/favicon.png">
    <link rel="icon" type="image/png" sizes="96x96" href="public/images/favicon.png">
    <link rel="apple-touch-icon" sizes="180x180" href="public/images/favicon.png">
    <link rel="shortcut icon" href="public/images/favicon.png">
    
    <!-- Fonts - Load asynchronously to prevent blocking -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    </noscript>
    
    <!-- Font Awesome - Load asynchronously -->
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    </noscript>
    
    <!-- Main CSS -->
    <link rel="stylesheet" href="<?php echo asset_url('public/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('public/css/responsive.css'); ?>">
    
    <!-- Additional page-specific CSS -->
    <?php if (isset($extra_css)): ?>
        <?php foreach ($extra_css as $css): ?>
            <link rel="stylesheet" href="<?php echo asset_url($css); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="nav-logo">
                <img src="public/images/logo.png?v=<?php echo @filemtime('public/images/logo.png') ?: time(); ?>" alt="NetSkill" height="45" style="object-fit: contain;" onerror="this.onerror=null;this.parentElement.innerHTML='<i class=\'fas fa-network-wired\'></i><span>NetSkill</span>'">
                <span>N3TSKILL</span>
            </a>
            
            <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
                <span class="hamburger"></span>
            </button>
            
            <ul class="nav-menu" id="navMenu">
                <li class="nav-item">
                    <a href="index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                        <i class="fas fa-home"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a href="tutorials.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'tutorials.php' ? 'active' : ''; ?>">
                        <i class="fas fa-graduation-cap"></i> Tutorials
                    </a>
                </li>
                <li class="nav-item">
                    <a href="reviews.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reviews.php' ? 'active' : ''; ?>">
                        <i class="fas fa-star"></i> Reviews
                    </a>
                </li>
                <li class="nav-item">
                    <a href="about.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'about.php' ? 'active' : ''; ?>">
                        <i class="fas fa-info-circle"></i> About
                    </a>
                </li>
                
                <?php if (isLoggedIn()): ?>
                    <?php $user = getCurrentUser(); ?>
                    <?php $avatarSrc = (isset($user['profile_image']) && preg_match('#^https?://#', $user['profile_image']))
                        ? $user['profile_image']
                        : 'public/uploads/avatars/' . htmlspecialchars($user['profile_image'] ?? 'default-avatar.png'); ?>
                    <li class="nav-item dropdown">
                        <a href="#" class="nav-link dropdown-toggle">
                            <img src="<?php echo $avatarSrc; ?>" 
                                 alt="<?php echo htmlspecialchars($user['full_name']); ?>" 
                                 class="nav-avatar"
                                 onerror="this.onerror=null;this.src='public/images/default-avatar.png'">
                            <span><?php echo htmlspecialchars($user['full_name']); ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a href="profile.php"><i class="fas fa-user"></i> My Profile</a></li>
                            <li><a href="certificates.php"><i class="fas fa-certificate"></i> My Certificates</a></li>
                            <li><a href="progress.php"><i class="fas fa-chart-line"></i> My Progress</a></li>
                            <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                            <?php if (isAdmin()): ?>
                                <li class="dropdown-divider"></li>
                                <li><a href="admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Admin Dashboard</a></li>
                            <?php endif; ?>
                            <li class="dropdown-divider"></li>
                            <li><a href="logout.php" class="logout-link" data-confirm-logout="true"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a href="login.php" class="nav-link nav-link-btn">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="register.php" class="nav-link nav-link-btn primary">
                            <i class="fas fa-user-plus"></i> Sign Up
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
    
    <!-- Flash Messages -->
    <?php
    $flash = getFlashMessage();
    if ($flash):
    ?>
        <div class="flash-message flash-<?php echo $flash['type']; ?>" id="flashMessage">
            <div class="flash-container">
                <i class="fas <?php echo $flash['type'] == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <span><?php echo htmlspecialchars($flash['message']); ?></span>
                <button class="flash-close" onclick="this.parentElement.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Main Content -->
    <main class="main-content">