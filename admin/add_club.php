<?php
require_once '../db.php';
require_role('admin');

$images      = club_image_options();
$errors      = [];
$name        = '';
$category    = 'Technology';
$description = '';
$activities  = '';
$image       = 'images/club-tech.jpg';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Session expired. Please refresh and submit again.';
    } else {
        $name        = trim($_POST['name'] ?? '');
        $category    = trim($_POST['category'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $activities  = trim($_POST['activities'] ?? '');
        $image       = trim($_POST['image'] ?? 'images/club-tech.jpg');

        if ($name === '' || mb_strlen($name) < 3) {
            $errors[] = 'Club name must be at least 3 characters.';
        }
        if ($category === '') {
            $errors[] = 'Please provide a club category.';
        }
        if ($description === '' || mb_strlen($description) < 15) {
            $errors[] = 'Please write a short description (at least 15 characters).';
        }
        if ($activities === '') {
            $errors[] = 'Please list the club’s main activities.';
        }
        if (!array_key_exists($image, $images)) {
            $image = 'images/club-tech.jpg';
        }

        if (empty($errors)) {
            $chk = $pdo->prepare('SELECT id FROM clubs WHERE LOWER(name) = LOWER(:name) LIMIT 1');
            $chk->execute([':name' => $name]);
            if ($chk->fetch()) {
                $errors[] = 'A club with this name already exists.';
            }
        }

        if (empty($errors) && isset($_FILES['image_file'])) {
            $uploaded = handle_club_image_upload($_FILES['image_file'], $errors);
            if ($uploaded !== null) {
                $image = $uploaded;
            }
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare(
                'INSERT INTO clubs (name, description, category, activities, image)
                 VALUES (:name, :description, :category, :activities, :image)'
            );
            $stmt->execute([
                ':name'        => $name,
                ':description' => $description,
                ':category'    => $category,
                ':activities'  => $activities,
                ':image'       => $image,
            ]);
            set_flash('success', 'Club "' . $name . '" has been added.');
            redirect('admin/clubs.php');
        }
    }
}

$page_title = 'Add New Club';
require '../header.php';
?>

<a href="clubs.php" class="back-link">&larr; Back to Clubs</a>

<section class="editorial-masthead compact">
    <div>
        <span class="kicker">New Community</span>
        <h1 class="display-title">Add a Student Club</h1>
    </div>
</section>

<section class="surface-block form-container">
    <?php if (!empty($errors)): ?>
        <div class="notice notice-error">
            <div><?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?></div>
        </div>
    <?php endif; ?>

    <form method="post" action="add_club.php" enctype="multipart/form-data" class="form-stack">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <div class="form-row-2">
            <div class="field">
                <label for="name">Club Name *</label>
                <input type="text" id="name" name="name" value="<?= e($name) ?>" placeholder="e.g., Photography & Film Society" required>
            </div>
            <div class="field">
                <label for="category">Category *</label>
                <input type="text" id="category" name="category" value="<?= e($category) ?>" placeholder="e.g., Arts & Design, Technology, Culture" required>
            </div>
        </div>

        <div class="field">
            <label for="description">Description *</label>
            <textarea id="description" name="description" rows="4" placeholder="Introduce what this club stands for and who should join." required><?= e($description) ?></textarea>
        </div>

        <div class="field">
            <label for="activities">Main Activities * <span class="hint-inline">(separate with semicolons or new lines)</span></label>
            <textarea id="activities" name="activities" rows="3" placeholder="Weekly studio meetups; Campus exhibitions; Guest talks" required><?= e($activities) ?></textarea>
        </div>

        <div class="form-row-2">
            <div class="field">
                <label for="image_file">Upload Club Photo <span class="hint-inline">(JPG, PNG, WEBP · Max 3MB)</span></label>
                <input type="file" id="image_file" name="image_file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
            </div>

            <div class="field">
                <label for="image">Or Choose Ready Photo</label>
                <select id="image" name="image">
                    <?php foreach ($images as $path => $label): ?>
                        <option value="<?= e($path) ?>"<?= $image === $path ? ' selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-actions">
            <a href="clubs.php" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-primary">Create Club</button>
        </div>
    </form>
</section>

<?php require '../footer.php'; ?>
