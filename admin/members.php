<?php
require_once '../db.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_id'])) {
    if (!verify_csrf()) {
        set_flash('error', 'Security check failed.');
    } else {
        $remove_id = (int) $_POST['remove_id'];
        if ($remove_id > 0) {
            $del = $pdo->prepare('DELETE FROM club_members WHERE id = :id');
            $del->execute([':id' => $remove_id]);
            set_flash('success', 'Member removed from club roster.');
        }
    }
    redirect('admin/members.php');
}

$club_id = isset($_GET['club_id']) ? (int) $_GET['club_id'] : 0;
$clubs   = $pdo->query('SELECT id, name FROM clubs ORDER BY name ASC')->fetchAll();

$sql = 'SELECT cm.id, cm.joined_at, u.name AS student_name, u.email AS student_email, c.name AS club_name
        FROM club_members cm
        JOIN users u ON u.id = cm.student_id
        JOIN clubs c ON c.id = cm.club_id';
$params = [];
if ($club_id > 0) {
    $sql .= ' WHERE cm.club_id = :cid';
    $params[':cid'] = $club_id;
}
$sql .= ' ORDER BY c.name ASC, cm.joined_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$members = $stmt->fetchAll();

$page_title = 'Club Members';
require '../header.php';
?>

<section class="editorial-masthead">
    <div>
        <span class="kicker">Approved Roster</span>
        <h1 class="display-title">Club Members</h1>
        <p class="lead-copy">All students who have been approved and registered in university clubs.</p>
    </div>
</section>

<form method="get" action="members.php" class="filter-bar">
    <label for="club_id" class="filter-label">Filter by Club:</label>
    <select id="club_id" name="club_id" class="filter-select" onchange="this.form.submit()">
        <option value="0">All Clubs</option>
        <?php foreach ($clubs as $c): ?>
            <option value="<?= (int) $c['id'] ?>"<?= $club_id === (int) $c['id'] ? ' selected' : '' ?>><?= e($c['name']) ?></option>
        <?php endforeach; ?>
    </select>
</form>

<section class="surface-block">
    <?php if (empty($members)): ?>
        <p class="empty-copy">No approved members found for this selection.</p>
    <?php else: ?>
        <div class="table-scroll">
            <table class="editorial-table">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Email</th>
                        <th>Club</th>
                        <th>Joined Date</th>
                        <th class="align-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($members as $m): ?>
                        <tr>
                            <td><strong><?= e($m['student_name']) ?></strong></td>
                            <td class="muted"><?= e($m['student_email']) ?></td>
                            <td><span class="cat-tag"><?= e($m['club_name']) ?></span></td>
                            <td class="muted"><?= e(format_date($m['joined_at'])) ?></td>
                            <td class="align-right">
                                <form method="post" action="members.php" class="inline-form" data-confirm="Remove <?= e($m['student_name']) ?> from <?= e($m['club_name']) ?>?">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="remove_id" value="<?= (int) $m['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-xs">Remove</button>
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
