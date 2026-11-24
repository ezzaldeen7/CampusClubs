<?php
require_once '../db.php';
require_role('student');

$announcements = $pdo->query(
    'SELECT a.*, c.name AS club_name
     FROM announcements a
     LEFT JOIN clubs c ON c.id = a.club_id
     ORDER BY a.created_at DESC'
)->fetchAll();

$page_title = 'Campus Announcements';
require '../header.php';
?>

<section class="editorial-masthead">
    <div>
        <span class="kicker">Campus Gazette &middot; Student Dispatches</span>
        <h1 class="display-title">Club Announcements</h1>
        <p class="lead-copy">Workshops, open studios, exhibitions, and student community notices published across campus.</p>
    </div>
</section>

<?php if (empty($announcements)): ?>
    <section class="surface-block">
        <p class="empty-copy">No announcements have been published yet.</p>
    </section>
<?php else: ?>
    <?php $lead = $announcements[0]; $others = array_slice($announcements, 1); ?>

    <article class="gazette-lead">
        <div class="gazette-lead-meta">
            <span class="edition-tag"><?= e($lead['club_name'] ?: 'Campus-Wide Dispatch') ?></span>
            <span class="lead-date"><?= e(format_date($lead['created_at'])) ?></span>
        </div>
        <h2><?= e($lead['title']) ?></h2>
        <div class="ochre-rule"></div>
        <p><?= nl2br(e($lead['content'])) ?></p>
    </article>

    <?php if (!empty($others)): ?>
        <section class="surface-block">
            <div class="gazette-list">
                <?php foreach ($others as $idx => $item): ?>
                    <article class="gazette-entry">
                        <div class="gazette-meta">
                            <span class="gazette-num"><?= str_pad((string) ($idx + 2), 2, '0', STR_PAD_LEFT) ?></span>
                            <span class="cat-tag"><?= e($item['club_name'] ?: 'Campus-Wide') ?></span>
                            <span class="muted small-text"><?= e(format_date($item['created_at'])) ?></span>
                        </div>
                        <h3 class="gazette-title"><?= e($item['title']) ?></h3>
                        <p class="gazette-body"><?= nl2br(e($item['content'])) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
<?php endif; ?>

<?php require '../footer.php'; ?>
