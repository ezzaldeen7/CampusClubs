<?php
require_once '../db.php';
require_role('admin');

// Handle Approve / Reject student account registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['decision'])) {
    if (!verify_csrf()) {
        set_flash('error', 'Security check failed. Please refresh and try again.');
    } else {
        $uid      = (int) $_POST['user_id'];
        $decision = in_array($_POST['decision'], ['Approved', 'Rejected'], true) ? $_POST['decision'] : '';

        if ($uid > 0 && $decision !== '') {
            $upd = $pdo->prepare("UPDATE users SET account_status = :status WHERE id = :id AND role = 'student'");
            $upd->execute([':status' => $decision, ':id' => $uid]);
            set_flash(
                'success',
                $decision === 'Approved'
                    ? 'Student account approved. The student can now sign in and view the student portal.'
                    : 'Student account rejected. Portal access has been denied.'
            );
        }
    }
    redirect('admin/students.php');
}

$status_filter = $_GET['status'] ?? '';
if (!in_array($status_filter, ['Pending', 'Approved', 'Rejected'], true)) {
    $status_filter = '';
}

$sql = "SELECT id, name, email, account_status, created_at FROM users WHERE role = 'student'";
$params = [];
if ($status_filter !== '') {
    $sql .= ' AND account_status = :status';
    $params[':status'] = $status_filter;
}
$sql .= " ORDER BY FIELD(account_status, 'Pending', 'Approved', 'Rejected'), created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

$page_title = 'Student Accounts';
require '../header.php';
?>

<section class="editorial-masthead">
    <div>
        <span class="kicker">Account Approvals</span>
        <h1 class="display-title">Student Accounts</h1>
        <p class="lead-copy">Review newly registered students. Only approved student accounts can sign in and view the student interface.</p>
    </div>
</section>

<div class="filter-bar">
    <a href="students.php" class="filter-chip<?= $status_filter === '' ? ' is-active' : '' ?>">All</a>
    <a href="students.php?status=Pending" class="filter-chip<?= $status_filter === 'Pending' ? ' is-active' : '' ?>">Pending</a>
    <a href="students.php?status=Approved" class="filter-chip<?= $status_filter === 'Approved' ? ' is-active' : '' ?>">Approved</a>
    <a href="students.php?status=Rejected" class="filter-chip<?= $status_filter === 'Rejected' ? ' is-active' : '' ?>">Rejected</a>
</div>

<section class="surface-block">
    <?php if (empty($students)): ?>
        <p class="empty-copy">No student accounts match this filter.</p>
    <?php else: ?>
        <div class="table-scroll">
            <table class="editorial-table">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Email</th>
                        <th>Registered</th>
                        <th>Account Status</th>
                        <th class="align-right">Decision</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $st): ?>
                        <tr>
                            <td><strong><?= e($st['name']) ?></strong></td>
                            <td class="muted"><?= e($st['email']) ?></td>
                            <td class="muted nowrap"><?= e(format_date($st['created_at'])) ?></td>
                            <td><?= status_pill($st['account_status']) ?></td>
                            <td class="align-right nowrap">
                                <form method="post" action="students.php" class="inline-form">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="user_id" value="<?= (int) $st['id'] ?>">
                                    <?php if ($st['account_status'] !== 'Approved'): ?>
                                        <button type="submit" name="decision" value="Approved" class="btn btn-forest btn-xs">Approve</button>
                                    <?php endif; ?>
                                    <?php if ($st['account_status'] !== 'Rejected'): ?>
                                        <button type="submit" name="decision" value="Rejected" class="btn btn-danger btn-xs">Reject</button>
                                    <?php endif; ?>
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
