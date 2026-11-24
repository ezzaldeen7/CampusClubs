<?php
require_once '../db.php';
require_role('admin');

$total_clubs    = (int) $pdo->query('SELECT COUNT(*) FROM clubs')->fetchColumn();
$total_students = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$pending_apps   = (int) $pdo->query("SELECT COUNT(*) FROM membership_applications WHERE status = 'Pending'")->fetchColumn();
$approved_apps  = (int) $pdo->query("SELECT COUNT(*) FROM membership_applications WHERE status = 'Approved'")->fetchColumn();
$total_ann      = (int) $pdo->query('SELECT COUNT(*) FROM announcements')->fetchColumn();

$recent_apps = $pdo->query(
    'SELECT ma.id, ma.status, ma.application_date, u.name AS student_name, c.name AS club_name
     FROM membership_applications ma
     JOIN users u ON u.id = ma.student_id
     JOIN clubs c ON c.id = ma.club_id
     ORDER BY ma.application_date DESC
     LIMIT 6'
)->fetchAll();

$recent_ann = $pdo->query(
    'SELECT a.id, a.title, a.created_at, c.name AS club_name
     FROM announcements a
     LEFT JOIN clubs c ON c.id = a.club_id
     ORDER BY a.created_at DESC
     LIMIT 4'
)->fetchAll();

$page_title = 'Admin Dashboard';
require '../header.php';
?>

<section class="editorial-masthead campus-visual-banner" style="flex-direction:row;align-items:flex-end;">
    <div>
        <span class="kicker">Administration Desk</span>
        <h1 class="display-title">Welcome, <?= e($_SESSION['user_name']) ?></h1>
        <p class="lead-copy">Oversee active student clubs, review membership requests, and publish campus dispatches from one calm desk.</p>
    </div>
    <div class="masthead-actions">
        <a href="add_club.php" class="btn btn-primary">+ New Club</a>
        <a href="announcements.php" class="btn btn-outline">Post Announcement</a>
    </div>
</section>

<section class="metrics-strip" aria-label="Overview Metrics">
    <div class="metric-cell">
        <span class="metric-label">Total Clubs</span>
        <strong class="metric-num"><?= $total_clubs ?></strong>
    </div>
    <div class="metric-cell">
        <span class="metric-label">Students</span>
        <strong class="metric-num"><?= $total_students ?></strong>
    </div>
    <div class="metric-cell">
        <span class="metric-label">Pending Requests</span>
        <strong class="metric-num metric-accent"><?= $pending_apps ?></strong>
    </div>
    <div class="metric-cell">
        <span class="metric-label">Approved Apps</span>
        <strong class="metric-num"><?= $approved_apps ?></strong>
    </div>
    <div class="metric-cell">
        <span class="metric-label">Announcements</span>
        <strong class="metric-num"><?= $total_ann ?></strong>
    </div>
</section>

<div class="editorial-spread">
    <section class="surface-block">
        <div class="section-head">
            <h2>Recent Membership Applications</h2>
            <a href="applications.php" class="text-link">Review all &rarr;</a>
        </div>

        <?php if (empty($recent_apps)): ?>
            <p class="empty-copy">No membership applications have been submitted yet.</p>
        <?php else: ?>
            <div class="table-scroll">
                <table class="editorial-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Club</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_apps as $app): ?>
                            <tr>
                                <td><strong><?= e($app['student_name']) ?></strong></td>
                                <td><?= e($app['club_name']) ?></td>
                                <td class="muted"><?= e(format_date($app['application_date'])) ?></td>
                                <td><?= status_pill($app['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <aside class="aside-stack">
        <section class="sage-panel">
            <span class="kicker">Quick Management</span>
            <ul class="index-links">
                <li><a href="clubs.php"><span>Manage Student Clubs</span><span>&rarr;</span></a></li>
                <li><a href="students.php?status=Pending"><span>Approve New Student Accounts</span><span>&rarr;</span></a></li>
                <li><a href="applications.php?status=Pending"><span>Review Pending Applications</span><span>&rarr;</span></a></li>
                <li><a href="members.php"><span>Approved Members Roster</span><span>&rarr;</span></a></li>
                <li><a href="announcements.php"><span>Publish Campus Announcements</span><span>&rarr;</span></a></li>
            </ul>
        </section>

        <section class="surface-block">
            <div class="section-head">
                <h2>Latest Dispatches</h2>
                <a href="announcements.php" class="text-link">Manage &rarr;</a>
            </div>
            <?php if (empty($recent_ann)): ?>
                <p class="empty-copy">No announcements published yet.</p>
            <?php else: ?>
                <ul class="digest-list">
                    <?php foreach ($recent_ann as $item): ?>
                        <li>
                            <span class="digest-meta"><?= e($item['club_name'] ?: 'Campus-Wide') ?> &middot; <?= e(format_date($item['created_at'])) ?></span>
                            <strong><?= e($item['title']) ?></strong>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </aside>
</div>

<?php require '../footer.php'; ?>
