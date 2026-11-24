<?php
$page_title = $page_title ?? 'Campus Clubs';
$prefix     = base_prefix();
$role       = $_SESSION['user_role'] ?? '';
$user_name  = $_SESSION['user_name'] ?? '';
$flash      = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> — Campus Clubs</title>
    <link rel="stylesheet" href="<?= e($prefix . 'css/style.css') ?>">
</head>
<body class="role-<?= e($role ?: 'guest') ?>">
<header class="site-header">
    <div class="container header-inner">
        <a href="<?= e($prefix . ($role === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php')) ?>" class="brand">
            <span class="brand-monogram" aria-hidden="true">CC</span>
            <span class="brand-copy">
                <span class="brand-title">Campus Clubs</span>
                <span class="brand-edition"><?= $role === 'admin' ? 'Editorial Desk · Admin' : 'University Community' ?></span>
            </span>
        </a>

        <button type="button" class="nav-toggle" id="navToggle" aria-label="Toggle menu" aria-expanded="false" aria-controls="mainNav">
            <span></span><span></span><span></span>
        </button>

        <nav class="site-nav" id="mainNav" aria-label="Primary Navigation">
            <?php if ($role === 'admin'): ?>
                <ul class="nav-links">
                    <li><a href="<?= e($prefix) ?>admin/dashboard.php" class="nav-link<?= active_nav('dashboard.php') ?>">Dashboard</a></li>
                    <li><a href="<?= e($prefix) ?>admin/clubs.php" class="nav-link<?= active_nav('clubs.php') . active_nav('add_club.php') . active_nav('edit_club.php') ?>">Clubs</a></li>
                    <li><a href="<?= e($prefix) ?>admin/applications.php" class="nav-link<?= active_nav('applications.php') ?>">Applications</a></li>
                    <li><a href="<?= e($prefix) ?>admin/students.php" class="nav-link<?= active_nav('students.php') ?>">Students</a></li>
                    <li><a href="<?= e($prefix) ?>admin/members.php" class="nav-link<?= active_nav('members.php') ?>">Members</a></li>
                    <li><a href="<?= e($prefix) ?>admin/announcements.php" class="nav-link<?= active_nav('announcements.php') ?>">Announcements</a></li>
                </ul>
            <?php elseif ($role === 'student'): ?>
                <ul class="nav-links">
                    <li><a href="<?= e($prefix) ?>student/dashboard.php" class="nav-link<?= active_nav('dashboard.php') ?>">Dashboard</a></li>
                    <li><a href="<?= e($prefix) ?>student/clubs.php" class="nav-link<?= active_nav('clubs.php') . active_nav('club_details.php') ?>">Clubs</a></li>
                    <li><a href="<?= e($prefix) ?>student/my_applications.php" class="nav-link<?= active_nav('my_applications.php') ?>">My Applications</a></li>
                    <li><a href="<?= e($prefix) ?>student/announcements.php" class="nav-link<?= active_nav('announcements.php') ?>">Announcements</a></li>
                </ul>
            <?php endif; ?>

            <div class="nav-account">
                <span class="account-name"><?= e($user_name) ?></span>
                <a href="<?= e($prefix) ?>logout.php" class="btn btn-outline btn-sm">Logout</a>
            </div>
        </nav>
    </div>
</header>

<main class="site-main">
    <div class="container">
        <?php if ($flash): ?>
            <div class="notice notice-<?= e($flash['type']) ?>" data-notice role="status">
                <span><?= e($flash['message']) ?></span>
                <button type="button" class="notice-close" data-notice-close aria-label="Dismiss">&times;</button>
            </div>
        <?php endif; ?>
