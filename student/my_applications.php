<?php
require_once '../db.php';
require_role('student');

$student_id = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare(
    'SELECT ma.*, c.name AS club_name, c.category
     FROM membership_applications ma
     JOIN clubs c ON c.id = ma.club_id
     WHERE ma.student_id = :sid
     ORDER BY ma.application_date DESC'
);
$stmt->execute([':sid' => $student_id]);
$applications = $stmt->fetchAll();

$page_title = 'My Applications';
require '../header.php';
?>

<section class="editorial-masthead">
    <div>
        <span class="kicker">Membership Tracker</span>
        <h1 class="display-title">My Applications</h1>
        <p class="lead-copy">Follow the status of every club membership request you have submitted.</p>
    </div>
    <div class="masthead-actions">
        <a href="clubs.php" class="btn btn-primary">Browse Clubs</a>
    </div>
</section>

<section class="surface-block">
    <?php if (empty($applications)): ?>
        <p class="empty-copy">You have not applied to any clubs yet. <a href="clubs.php" class="text-link">Explore the clubs directory &rarr;</a></p>
    <?php else: ?>
        <div class="table-scroll">
            <table class="editorial-table">
                <thead>
                    <tr>
                        <th>Club</th>
                        <th>Category</th>
                        <th>Submitted</th>
                        <th>Status</th>
                        <th class="align-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $app): ?>
                        <tr>
                            <td><strong><?= e($app['club_name']) ?></strong></td>
                            <td><span class="cat-tag"><?= e($app['category']) ?></span></td>
                            <td class="muted"><?= e(format_date($app['application_date'])) ?></td>
                            <td><?= status_pill($app['status']) ?></td>
                            <td class="align-right">
                                <a href="club_details.php?id=<?= (int) $app['club_id'] ?>" class="btn btn-outline btn-xs">Open Club</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require '../footer.php'; ?>
