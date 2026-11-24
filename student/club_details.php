<?php
require_once '../db.php';
require_role('student');

$student_id = (int) $_SESSION['user_id'];
$club_id    = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;

if ($club_id <= 0) {
    set_flash('error', 'Club not found.');
    redirect('student/clubs.php');
}

$stmt = $pdo->prepare(
    'SELECT c.*, (SELECT COUNT(*) FROM club_members cm WHERE cm.club_id = c.id) AS member_count
     FROM clubs c WHERE c.id = :id LIMIT 1'
);
$stmt->execute([':id' => $club_id]);
$club = $stmt->fetch();

if (!$club) {
    set_flash('error', 'Club not found.');
    redirect('student/clubs.php');
}

// Check existing application or membership
$chk = $pdo->prepare(
    'SELECT id, status, application_date FROM membership_applications
     WHERE student_id = :sid AND club_id = :cid
     ORDER BY application_date DESC LIMIT 1'
);
$chk->execute([':sid' => $student_id, ':cid' => $club_id]);
$existing_app = $chk->fetch();

// Handle POST membership application submission
$form_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $form_error = 'Session expired. Please refresh and try again.';
    } elseif ($existing_app && in_array($existing_app['status'], ['Pending', 'Approved'], true)) {
        set_flash('error', 'You already have an active (' . $existing_app['status'] . ') application for this club.');
        redirect('student/my_applications.php');
    } else {
        $motivation = trim($_POST['motivation'] ?? '');
        if ($motivation === '' || mb_strlen($motivation) < 10) {
            $form_error = 'Please write a brief note (at least 10 characters) on why you would like to join.';
        } else {
            $ins = $pdo->prepare(
                "INSERT INTO membership_applications (student_id, club_id, motivation, status)
                 VALUES (:sid, :cid, :motivation, 'Pending')"
            );
            $ins->execute([
                ':sid'        => $student_id,
                ':cid'        => $club_id,
                ':motivation' => $motivation,
            ]);
            set_flash('success', 'Your application to ' . $club['name'] . ' has been submitted (Status: Pending).');
            redirect('student/my_applications.php');
        }
    }
}

$ann_stmt = $pdo->prepare(
    'SELECT * FROM announcements WHERE club_id = :cid ORDER BY created_at DESC LIMIT 4'
);
$ann_stmt->execute([':cid' => $club_id]);
$club_announcements = $ann_stmt->fetchAll();

$activities = array_filter(array_map('trim', preg_split('/[;\r\n]+/', (string) $club['activities'])));
$can_apply  = (!$existing_app || $existing_app['status'] === 'Rejected');

$page_title = $club['name'];
require '../header.php';
?>

<a href="clubs.php" class="back-link">&larr; Back to Clubs Directory</a>

<section class="club-detail-header">
    <span class="kicker"><?= e($club['category']) ?> &middot; Est. <?= e(format_date($club['created_at'])) ?></span>
    <h1 class="display-title-xl"><?= e($club['name']) ?></h1>
    <p class="muted"><?= (int) $club['member_count'] ?> registered member<?= ((int) $club['member_count'] === 1 ? '' : 's') ?></p>
</section>

<div class="club-detail-banner">
    <img src="../<?= e($club['image'] ?: 'images/club-tech.jpg') ?>" alt="<?= e($club['name']) ?>">
</div>

<div class="editorial-spread">
    <div class="detail-story-col">
        <section class="surface-block">
            <span class="kicker">About This Club</span>
            <p class="story-prose"><?= nl2br(e($club['description'])) ?></p>

            <?php if (!empty($activities)): ?>
                <div class="ochre-rule"></div>
                <h2 class="sub-heading">Club Activities</h2>
                <ul class="editorial-bullets">
                    <?php foreach ($activities as $act): ?>
                        <li><?= e($act) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <section class="surface-block">
            <div class="section-head">
                <h2>Related Announcements</h2>
            </div>
            <?php if (empty($club_announcements)): ?>
                <p class="empty-copy">No announcements have been posted for this club yet.</p>
            <?php else: ?>
                <div class="gazette-list">
                    <?php foreach ($club_announcements as $ann): ?>
                        <article class="gazette-entry">
                            <span class="muted small-text"><?= e(format_date($ann['created_at'])) ?></span>
                            <h3 class="gazette-title"><?= e($ann['title']) ?></h3>
                            <p class="gazette-body"><?= nl2br(e($ann['content'])) ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <aside>
        <section class="sage-panel">
            <span class="kicker">Membership</span>
            <?php if (!$can_apply): ?>
                <h2 class="panel-heading">Application on File</h2>
                <p class="small-text">You applied to this club on <?= e(format_date($existing_app['application_date'])) ?>.</p>
                <div class="status-row"><?= status_pill($existing_app['status']) ?></div>
                <a href="my_applications.php" class="btn btn-forest btn-block">View My Applications</a>
            <?php else: ?>
                <h2 class="panel-heading">Apply to Join</h2>
                <p class="small-text">Share a short note about your interest. Your request will be reviewed by the club administrator.</p>

                <?php if ($form_error !== ''): ?>
                    <div class="notice notice-error"><span><?= e($form_error) ?></span></div>
                <?php endif; ?>

                <form method="post" action="club_details.php?id=<?= $club_id ?>" class="form-stack">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <div class="field">
                        <label for="motivation">Why do you want to join? *</label>
                        <textarea id="motivation" name="motivation" rows="4" placeholder="Tell us what excites you about this club..." required><?= e($_POST['motivation'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Submit Application</button>
                </form>
            <?php endif; ?>
        </section>
    </aside>
</div>

<?php require '../footer.php'; ?>
