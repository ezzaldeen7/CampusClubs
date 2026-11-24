<?php
require_once '../db.php';
require_role('student');

$student_id = (int) $_SESSION['user_id'];

$total_clubs = (int) $pdo->query('SELECT COUNT(*) FROM clubs')->fetchColumn();

$s_apps = $pdo->prepare('SELECT COUNT(*) FROM membership_applications WHERE student_id = :sid');
$s_apps->execute([':sid' => $student_id]);
$my_total_apps = (int) $s_apps->fetchColumn();

$s_pend = $pdo->prepare("SELECT COUNT(*) FROM membership_applications WHERE student_id = :sid AND status = 'Pending'");
$s_pend->execute([':sid' => $student_id]);
$my_pending = (int) $s_pend->fetchColumn();

$s_mem = $pdo->prepare('SELECT COUNT(*) FROM club_members WHERE student_id = :sid');
$s_mem->execute([':sid' => $student_id]);
$my_memberships = (int) $s_mem->fetchColumn();

$featured_clubs = $pdo->query(
    'SELECT c.*, (SELECT COUNT(*) FROM club_members cm WHERE cm.club_id = c.id) AS member_count
     FROM clubs c
     ORDER BY c.id ASC
     LIMIT 3'
)->fetchAll();

$recent_ann = $pdo->query(
    'SELECT a.*, c.name AS club_name
     FROM announcements a
     LEFT JOIN clubs c ON c.id = a.club_id
     ORDER BY a.created_at DESC
     LIMIT 3'
)->fetchAll();

$page_title = 'Student Dashboard';
require '../header.php';
?>

<section class="student-hero">
    <div class="campus-visual-banner" style="margin-bottom:0;">
        <span class="kicker">Student Life &middot; Campus Edition</span>
        <h1 class="display-title">Hello, <?= e($_SESSION['user_name']) ?>.</h1>
        <p class="lead-copy">University clubs are where ideas meet community. Browse active circles, follow your membership requests, and stay close to what is happening across campus.</p>
        <div class="hero-cta-row">
            <a href="clubs.php" class="btn btn-primary">Explore All Clubs</a>
            <a href="my_applications.php" class="btn btn-outline">Track My Applications</a>
        </div>
    </div>

    <aside class="sage-panel status-summary-box">
        <span class="kicker">Your Activity Summary</span>
        <div class="summary-grid">
            <div><strong class="summary-num"><?= $total_clubs ?></strong><span>Active Clubs</span></div>
            <div><strong class="summary-num"><?= $my_total_apps ?></strong><span>Submitted</span></div>
            <div><strong class="summary-num summary-accent"><?= $my_pending ?></strong><span>Pending</span></div>
            <div><strong class="summary-num"><?= $my_memberships ?></strong><span>Memberships</span></div>
        </div>
    </aside>
</section>

<section class="editorial-section">
    <div class="section-head">
        <h2>Featured Campus Communities</h2>
        <a href="clubs.php" class="text-link">Browse all clubs &rarr;</a>
    </div>

    <?php if (!empty($featured_clubs)): ?>
        <div class="featured-magazine-grid">
            <?php foreach ($featured_clubs as $i => $club): ?>
                <article class="mag-card<?= $i === 0 ? ' mag-card-lead' : '' ?>">
                    <a href="club_details.php?id=<?= (int) $club['id'] ?>" class="mag-img-wrap">
                        <img src="../<?= e($club['image'] ?: 'images/club-tech.jpg') ?>" alt="<?= e($club['name']) ?>">
                    </a>
                    <div class="mag-body">
                        <span class="cat-tag"><?= e($club['category']) ?></span>
                        <h3><a href="club_details.php?id=<?= (int) $club['id'] ?>"><?= e($club['name']) ?></a></h3>
                        <p><?= e(mb_strimwidth($club['description'], 0, $i === 0 ? 185 : 115, '…')) ?></p>
                        <div class="mag-foot">
                            <span class="muted small-text"><?= (int) $club['member_count'] ?> member<?= ((int) $club['member_count'] === 1 ? '' : 's') ?></span>
                            <a href="club_details.php?id=<?= (int) $club['id'] ?>" class="text-link">Read more &rarr;</a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="surface-block">
    <div class="section-head">
        <h2>Recent Campus Dispatches</h2>
        <a href="announcements.php" class="text-link">Open Gazette &rarr;</a>
    </div>

    <?php if (empty($recent_ann)): ?>
        <p class="empty-copy">No campus announcements have been published yet.</p>
    <?php else: ?>
        <div class="gazette-list">
            <?php foreach ($recent_ann as $idx => $ann): ?>
                <article class="gazette-entry">
                    <div class="gazette-meta">
                        <span class="gazette-num"><?= str_pad((string) ($idx + 1), 2, '0', STR_PAD_LEFT) ?></span>
                        <span class="cat-tag"><?= e($ann['club_name'] ?: 'Campus-Wide') ?></span>
                        <span class="muted small-text"><?= e(format_date($ann['created_at'])) ?></span>
                    </div>
                    <h3 class="gazette-title"><?= e($ann['title']) ?></h3>
                    <p class="gazette-body"><?= e(mb_strimwidth($ann['content'], 0, 175, '…')) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require '../footer.php'; ?>
