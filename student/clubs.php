<?php
require_once '../db.php';
require_role('student');

$student_id = (int) $_SESSION['user_id'];
$search     = trim($_GET['q'] ?? '');
$cat        = trim($_GET['category'] ?? '');

$categories = $pdo->query('SELECT DISTINCT category FROM clubs ORDER BY category ASC')->fetchAll(PDO::FETCH_COLUMN);

$sql = 'SELECT c.*,
               (SELECT COUNT(*) FROM club_members cm WHERE cm.club_id = c.id) AS member_count,
               (SELECT ma.status FROM membership_applications ma
                WHERE ma.club_id = c.id AND ma.student_id = :sid
                ORDER BY ma.application_date DESC LIMIT 1) AS my_status
        FROM clubs c
        WHERE 1=1';
$params = [':sid' => $student_id];

if ($search !== '') {
    $sql .= ' AND (c.name LIKE :q1 OR c.description LIKE :q2 OR c.category LIKE :q3)';
    $like = '%' . $search . '%';
    $params[':q1'] = $like;
    $params[':q2'] = $like;
    $params[':q3'] = $like;
}
if ($cat !== '') {
    $sql .= ' AND c.category = :cat';
    $params[':cat'] = $cat;
}
$sql .= ' ORDER BY c.id ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$clubs = $stmt->fetchAll();

$page_title = 'Browse Clubs';
require '../header.php';
?>

<section class="editorial-masthead">
    <div>
        <span class="kicker">Community Directory</span>
        <h1 class="display-title">University Clubs</h1>
        <p class="lead-copy">Each club is a student-led space to learn, create, and belong. Explore the directory and open any club to apply.</p>
    </div>
</section>

<div class="directory-controls">
    <div class="filter-bar">
        <a href="clubs.php" class="filter-chip<?= $cat === '' ? ' is-active' : '' ?>">All Categories</a>
        <?php foreach ($categories as $c): ?>
            <a href="clubs.php?category=<?= urlencode($c) ?>" class="filter-chip<?= $cat === $c ? ' is-active' : '' ?>"><?= e($c) ?></a>
        <?php endforeach; ?>
    </div>

    <form method="get" action="clubs.php" class="search-inline">
        <?php if ($cat !== ''): ?>
            <input type="hidden" name="category" value="<?= e($cat) ?>">
        <?php endif; ?>
        <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search clubs..." aria-label="Search clubs" data-live-search=".club-story">
        <button type="submit" class="btn btn-forest btn-sm">Search</button>
    </form>
</div>

<?php if (empty($clubs)): ?>
    <section class="surface-block">
        <p class="empty-copy">No clubs matched your search. <a href="clubs.php" class="text-link">View all clubs &rarr;</a></p>
    </section>
<?php else: ?>
    <?php $lead = $clubs[0]; $rest = array_slice($clubs, 1); ?>

    <article class="club-feature club-story" data-search-text="<?= e(strtolower($lead['name'] . ' ' . $lead['category'] . ' ' . $lead['description'])) ?>">
        <a href="club_details.php?id=<?= (int) $lead['id'] ?>" class="club-feature-media">
            <img src="../<?= e($lead['image'] ?: 'images/club-tech.jpg') ?>" alt="<?= e($lead['name']) ?>">
        </a>
        <div class="club-feature-body">
            <div class="meta-line">
                <span class="cat-tag"><?= e($lead['category']) ?></span>
                <?php if (!empty($lead['my_status'])): ?>
                    <?= status_pill($lead['my_status']) ?>
                <?php endif; ?>
            </div>
            <h2 class="feature-heading"><a href="club_details.php?id=<?= (int) $lead['id'] ?>"><?= e($lead['name']) ?></a></h2>
            <p class="feature-desc"><?= e($lead['description']) ?></p>
            <div class="feature-footer">
                <span class="muted small-text"><?= (int) $lead['member_count'] ?> registered member<?= ((int) $lead['member_count'] === 1 ? '' : 's') ?></span>
                <a href="club_details.php?id=<?= (int) $lead['id'] ?>" class="btn btn-primary btn-sm">View Club Details</a>
            </div>
        </div>
    </article>

    <?php if (!empty($rest)): ?>
        <section class="editorial-club-grid">
            <?php foreach ($rest as $club): ?>
                <article class="mag-card club-story" data-search-text="<?= e(strtolower($club['name'] . ' ' . $club['category'] . ' ' . $club['description'])) ?>">
                    <a href="club_details.php?id=<?= (int) $club['id'] ?>" class="mag-img-wrap">
                        <img src="../<?= e($club['image'] ?: 'images/club-tech.jpg') ?>" alt="<?= e($club['name']) ?>">
                    </a>
                    <div class="mag-body">
                        <div class="meta-line">
                            <span class="cat-tag"><?= e($club['category']) ?></span>
                            <?php if (!empty($club['my_status'])): ?>
                                <?= status_pill($club['my_status']) ?>
                            <?php endif; ?>
                        </div>
                        <h3><a href="club_details.php?id=<?= (int) $club['id'] ?>"><?= e($club['name']) ?></a></h3>
                        <p><?= e(mb_strimwidth($club['description'], 0, 130, '…')) ?></p>
                        <div class="mag-foot">
                            <span class="muted small-text"><?= (int) $club['member_count'] ?> member<?= ((int) $club['member_count'] === 1 ? '' : 's') ?></span>
                            <a href="club_details.php?id=<?= (int) $club['id'] ?>" class="text-link">View details &rarr;</a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
<?php endif; ?>

<?php require '../footer.php'; ?>
