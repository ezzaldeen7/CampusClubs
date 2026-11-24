<?php
require_once '../db.php';
require_role('admin');

// Handle club deletion via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (!verify_csrf()) {
        set_flash('error', 'Security check failed. Please try again.');
    } else {
        $del_id = (int) $_POST['delete_id'];
        if ($del_id > 0) {
            $stmt = $pdo->prepare('DELETE FROM clubs WHERE id = :id');
            $stmt->execute([':id' => $del_id]);
            set_flash('success', 'Club deleted successfully.');
        }
    }
    redirect('admin/clubs.php');
}

$clubs = $pdo->query(
    'SELECT c.*,
            (SELECT COUNT(*) FROM club_members cm WHERE cm.club_id = c.id) AS member_count,
            (SELECT COUNT(*) FROM membership_applications ma WHERE ma.club_id = c.id AND ma.status = "Pending") AS pending_count
     FROM clubs c
     ORDER BY c.created_at DESC'
)->fetchAll();

$page_title = 'Manage Clubs';
require '../header.php';
?>

<section class="editorial-masthead">
    <div>
        <span class="kicker">Directory Control</span>
        <h1 class="display-title">Student Clubs</h1>
        <p class="lead-copy">Create, refine, and organize campus clubs, categories, and weekly activities.</p>
    </div>
    <div class="masthead-actions">
        <a href="add_club.php" class="btn btn-primary">+ Add New Club</a>
    </div>
</section>

<section class="surface-block">
    <?php if (empty($clubs)): ?>
        <p class="empty-copy">No clubs have been created yet. <a href="add_club.php" class="text-link">Add your first club &rarr;</a></p>
    <?php else: ?>
        <div class="table-scroll">
            <table class="editorial-table">
                <thead>
                    <tr>
                        <th>Club</th>
                        <th>Category</th>
                        <th>Members</th>
                        <th>Pending</th>
                        <th>Created</th>
                        <th class="align-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clubs as $club): ?>
                        <tr>
                            <td>
                                <div class="club-cell">
                                    <img src="../<?= e($club['image'] ?: 'images/club-tech.jpg') ?>" alt="" class="club-thumb">
                                    <div>
                                        <strong><?= e($club['name']) ?></strong>
                                        <div class="muted small-text"><?= e(mb_strimwidth($club['description'], 0, 75, '…')) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="cat-tag"><?= e($club['category']) ?></span></td>
                            <td><?= (int) $club['member_count'] ?></td>
                            <td><?= (int) $club['pending_count'] ?></td>
                            <td class="muted"><?= e(format_date($club['created_at'])) ?></td>
                            <td class="align-right nowrap">
                                <a href="edit_club.php?id=<?= (int) $club['id'] ?>" class="btn btn-outline btn-xs">Edit</a>
                                <form method="post" action="clubs.php" class="inline-form" data-confirm="Delete '<?= e($club['name']) ?>'? This will also remove its applications and announcements.">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="delete_id" value="<?= (int) $club['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-xs">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require '../footer.php'; ?>
