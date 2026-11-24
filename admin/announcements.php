<?php
require_once '../db.php';
require_role('admin');

$clubs  = $pdo->query('SELECT id, name FROM clubs ORDER BY name ASC')->fetchAll();
$errors = [];

// Handle Create / Update / Delete POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('error', 'Security check failed.');
        redirect('admin/announcements.php');
    }

    if (isset($_POST['delete_id'])) {
        $del_id = (int) $_POST['delete_id'];
        if ($del_id > 0) {
            $del = $pdo->prepare('DELETE FROM announcements WHERE id = :id');
            $del->execute([':id' => $del_id]);
            set_flash('success', 'Announcement deleted.');
        }
        redirect('admin/announcements.php');
    }

    $edit_id = (int) ($_POST['edit_id'] ?? 0);
    $title   = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $club_id = ($_POST['club_id'] ?? '') !== '' ? (int) $_POST['club_id'] : null;

    if ($title === '' || mb_strlen($title) < 4) {
        $errors[] = 'Announcement title must be at least 4 characters.';
    }
    if ($content === '' || mb_strlen($content) < 10) {
        $errors[] = 'Announcement content must be at least 10 characters.';
    }

    if (empty($errors)) {
        if ($edit_id > 0) {
            $upd = $pdo->prepare('UPDATE announcements SET club_id = :cid, title = :title, content = :content WHERE id = :id');
            $upd->execute([':cid' => $club_id, ':title' => $title, ':content' => $content, ':id' => $edit_id]);
            set_flash('success', 'Announcement updated.');
        } else {
            $ins = $pdo->prepare('INSERT INTO announcements (club_id, title, content) VALUES (:cid, :title, :content)');
            $ins->execute([':cid' => $club_id, ':title' => $title, ':content' => $content]);
            set_flash('success', 'Announcement published.');
        }
        redirect('admin/announcements.php');
    }
}

// Check if editing an existing announcement
$editing = null;
if (isset($_GET['edit'])) {
    $eid = (int) $_GET['edit'];
    if ($eid > 0) {
        $s = $pdo->prepare('SELECT * FROM announcements WHERE id = :id LIMIT 1');
        $s->execute([':id' => $eid]);
        $editing = $s->fetch() ?: null;
    }
}

$announcements = $pdo->query(
    'SELECT a.*, c.name AS club_name
     FROM announcements a
     LEFT JOIN clubs c ON c.id = a.club_id
     ORDER BY a.created_at DESC'
)->fetchAll();

$page_title = 'Announcements';
require '../header.php';
?>

<section class="editorial-masthead">
    <div>
        <span class="kicker">Campus Gazette</span>
        <h1 class="display-title">Club Announcements</h1>
        <p class="lead-copy">Compose, edit, and publish dispatches for individual clubs or the entire campus.</p>
    </div>
</section>

<div class="editorial-spread">
    <section class="surface-block">
        <div class="section-head">
            <h2>Published Dispatches (<?= count($announcements) ?>)</h2>
        </div>

        <?php if (empty($announcements)): ?>
            <p class="empty-copy">No announcements have been published yet.</p>
        <?php else: ?>
            <div class="gazette-list">
                <?php foreach ($announcements as $idx => $item): ?>
                    <article class="gazette-entry">
                        <div class="gazette-meta">
                            <span class="gazette-num"><?= str_pad((string) ($idx + 1), 2, '0', STR_PAD_LEFT) ?></span>
                            <span class="cat-tag"><?= e($item['club_name'] ?: 'Campus-Wide') ?></span>
                            <span class="muted small-text"><?= e(format_date($item['created_at'])) ?></span>
                        </div>
                        <h3 class="gazette-title"><?= e($item['title']) ?></h3>
                        <p class="gazette-body"><?= nl2br(e($item['content'])) ?></p>
                        <div class="gazette-actions">
                            <a href="announcements.php?edit=<?= (int) $item['id'] ?>" class="btn btn-outline btn-xs">Edit</a>
                            <form method="post" action="announcements.php" class="inline-form" data-confirm="Delete announcement '<?= e($item['title']) ?>'?">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="delete_id" value="<?= (int) $item['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-xs">Delete</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <aside>
        <section class="sage-panel">
            <span class="kicker"><?= $editing ? 'Edit Dispatch' : 'New Dispatch' ?></span>
            <h2 class="panel-heading"><?= $editing ? 'Update Announcement' : 'Publish Announcement' ?></h2>

            <?php if (!empty($errors)): ?>
                <div class="notice notice-error">
                    <div><?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?></div>
                </div>
            <?php endif; ?>

            <form method="post" action="announcements.php" class="form-stack">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="edit_id" value="<?= $editing ? (int) $editing['id'] : 0 ?>">

                <div class="field">
                    <label for="title">Title *</label>
                    <input type="text" id="title" name="title" value="<?= e($editing['title'] ?? ($_POST['title'] ?? '')) ?>" placeholder="Announcement headline" required>
                </div>

                <div class="field">
                    <label for="club_id">Related Club</label>
                    <select id="club_id" name="club_id">
                        <option value="">Campus-Wide (All Clubs)</option>
                        <?php $selClub = $editing ? (string) ($editing['club_id'] ?? '') : (string) ($_POST['club_id'] ?? ''); ?>
                        <?php foreach ($clubs as $c): ?>
                            <option value="<?= (int) $c['id'] ?>"<?= $selClub === (string) $c['id'] ? ' selected' : '' ?>><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="content">Content *</label>
                    <textarea id="content" name="content" rows="5" placeholder="Write the full announcement details..." required><?= e($editing['content'] ?? ($_POST['content'] ?? '')) ?></textarea>
                </div>

                <div class="form-actions">
                    <?php if ($editing): ?>
                        <a href="announcements.php" class="btn btn-outline">Cancel</a>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary"><?= $editing ? 'Save Update' : 'Publish' ?></button>
                </div>
            </form>
        </section>
    </aside>
</div>

<?php require '../footer.php'; ?>
