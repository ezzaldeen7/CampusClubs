<?php
require_once '../db.php';
require_role('admin');

// Handle Approve / Reject POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['app_id'], $_POST['decision'])) {
    if (!verify_csrf()) {
        set_flash('error', 'Security check failed. Please refresh and try again.');
    } else {
        $app_id   = (int) $_POST['app_id'];
        $decision = $_POST['decision'] === 'Approved' ? 'Approved' : ($_POST['decision'] === 'Rejected' ? 'Rejected' : '');

        if ($app_id > 0 && $decision !== '') {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare('SELECT student_id, club_id, status FROM membership_applications WHERE id = :id FOR UPDATE');
                $stmt->execute([':id' => $app_id]);
                $app = $stmt->fetch();

                if ($app && $app['status'] === 'Pending') {
                    $upd = $pdo->prepare('UPDATE membership_applications SET status = :status WHERE id = :id');
                    $upd->execute([':status' => $decision, ':id' => $app_id]);

                    if ($decision === 'Approved') {
                        $ins = $pdo->prepare(
                            'INSERT IGNORE INTO club_members (student_id, club_id) VALUES (:sid, :cid)'
                        );
                        $ins->execute([
                            ':sid' => (int) $app['student_id'],
                            ':cid' => (int) $app['club_id'],
                        ]);
                    }
                    $pdo->commit();
                    set_flash('success', $decision === 'Approved'
                        ? 'Application approved and student added to club members.'
                        : 'Application marked as Rejected.');
                } else {
                    $pdo->rollBack();
                    set_flash('error', 'This application has already been processed.');
                }
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                set_flash('error', 'Unable to update application status.');
            }
        }
    }
    redirect('admin/applications.php');
}

$status_filter = $_GET['status'] ?? '';
if (!in_array($status_filter, ['Pending', 'Approved', 'Rejected'], true)) {
    $status_filter = '';
}

$sql = 'SELECT ma.*, u.name AS student_name, u.email AS student_email, c.name AS club_name
        FROM membership_applications ma
        JOIN users u ON u.id = ma.student_id
        JOIN clubs c ON c.id = ma.club_id';
$params = [];
if ($status_filter !== '') {
    $sql .= ' WHERE ma.status = :status';
    $params[':status'] = $status_filter;
}
$sql .= " ORDER BY FIELD(ma.status, 'Pending', 'Approved', 'Rejected'), ma.application_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$applications = $stmt->fetchAll();

$page_title = 'Membership Applications';
require '../header.php';
?>

<section class="editorial-masthead">
    <div>
        <span class="kicker">Admissions Desk</span>
        <h1 class="display-title">Membership Applications</h1>
        <p class="lead-copy">Review incoming student requests. Approving an application automatically registers the student as a club member.</p>
    </div>
</section>

<div class="filter-bar">
    <a href="applications.php" class="filter-chip<?= $status_filter === '' ? ' is-active' : '' ?>">All</a>
    <a href="applications.php?status=Pending" class="filter-chip<?= $status_filter === 'Pending' ? ' is-active' : '' ?>">Pending</a>
    <a href="applications.php?status=Approved" class="filter-chip<?= $status_filter === 'Approved' ? ' is-active' : '' ?>">Approved</a>
    <a href="applications.php?status=Rejected" class="filter-chip<?= $status_filter === 'Rejected' ? ' is-active' : '' ?>">Rejected</a>
</div>

<section class="surface-block">
    <?php if (empty($applications)): ?>
        <p class="empty-copy">No membership applications match this filter.</p>
    <?php else: ?>
        <div class="table-scroll">
            <table class="editorial-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Club</th>
                        <th>Motivation</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th class="align-right">Decision</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $app): ?>
                        <tr>
                            <td>
                                <strong><?= e($app['student_name']) ?></strong>
                                <div class="muted small-text"><?= e($app['student_email']) ?></div>
                            </td>
                            <td><?= e($app['club_name']) ?></td>
                            <td class="small-text"><?= e($app['motivation'] ?: '—') ?></td>
                            <td class="muted nowrap"><?= e(format_date($app['application_date'])) ?></td>
                            <td><?= status_pill($app['status']) ?></td>
                            <td class="align-right nowrap">
                                <?php if ($app['status'] === 'Pending'): ?>
                                    <form method="post" action="applications.php" class="inline-form">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="app_id" value="<?= (int) $app['id'] ?>">
                                        <button type="submit" name="decision" value="Approved" class="btn btn-forest btn-xs">Approve</button>
                                        <button type="submit" name="decision" value="Rejected" class="btn btn-danger btn-xs">Reject</button>
                                    </form>
                                <?php else: ?>
                                    <span class="muted small-text">Processed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require '../footer.php'; ?>
