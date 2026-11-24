<?php
require_once '../db.php';
require_role('admin');

$id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
if ($id <= 0) {
    set_flash('error', 'Invalid club ID.');
    redirect('admin/clubs.php');
}

$stmt = $pdo->prepare('SELECT * FROM clubs WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$club = $stmt->fetch();

if (!$club) {
    set_flash('error', 'Club not found.');
    redirect('admin/clubs.php');
}

$images      = club_image_options();
$errors      = [];
$name        = $club['name'];
$category    = $club['category'];
$description = $club['description'];
$activities  = $club['activities'];
$image       = $club['image'] ?: 'images/club-tech.jpg';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Session expired. Please refresh and try again.';
    } else {
        $name           = trim($_POST['name'] ?? '');
        $category       = trim($_POST['category'] ?? '');
        $description    = trim($_POST['description'] ?? '');
        $activities     = trim($_POST['activities'] ?? '');
        $selected_image = trim($_POST['image'] ?? '');

        if ($name === '' || mb_strlen($name) < 3) {
            $errors[] = 'Club name must be at least 3 characters.';
        }
        if ($category === '') {
            $errors[] = 'Category is required.';
        }
        if ($description === '' || mb_strlen($description) < 15) {
            $errors[] = 'Description must be at least 15 characters.';
        }
        if ($activities === '') {
            $errors[] = 'Activities field is required.';
        }

        if ($selected_image !== '' && array_key_exists($selected_image, $images)) {
            $image = $selected_image;
        }

        if (empty($errors)) {
            $dup = $pdo->prepare('SELECT id FROM clubs WHERE LOWER(name) = LOWER(:name) AND id != :id LIMIT 1');
            $dup->execute([':name' => $name, ':id' => $id]);
            if ($dup->fetch()) {
                $errors[] = 'Another club already uses that name.';
            }
        }

        if (empty($errors) && isset($_FILES['image_file'])) {
            $uploaded = handle_club_image_upload($_FILES['image_file'], $errors);
            if ($uploaded !== null) {
                $image = $uploaded;
            }
        }

        if (empty($errors)) {
            $upd = $pdo->prepare(
                'UPDATE clubs SET name = :name, description = :description, category = :category, activities = :activities, image = :image WHERE id = :id'
            );
            $upd->execute([
                ':name'        => $name,
                ':description' => $description,
                ':category'    => $category,
                ':activities'  => $activities,
                ':image'       => $image,
                ':id'          => $id,
            ]);
            set_flash('success', 'Club "' . $name . '" updated successfully.');
            redirect('admin/clubs.php');
        }
    }
}

$page_title = 'Edit Club';
require '../header.php';
?>

<a href="clubs.php" class="back-link">&larr; Back to Clubs</a>

<section class="editorial-masthead compact">
    <div>
        <span class="kicker">Update Club</span>
        <h1 class="display-title">Edit <?= e($club['name']) ?></h1>
    </div>
</section>

<section class="surface-block form-container">
    <?php if (!empty($errors)): ?>
        <div class="notice notice-error">
            <div><?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?></div>
        </div>
    <?php endif; ?>

    <form method="post" action="edit_club.php" enctype="multipart/form-data" class="form-stack">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="id" value="<?= $id ?>">

        <div class="form-row-2">
            <div class="field">
                <label for="name">Club Name *</label>
                <input type="text" id="name" name="name" value="<?= e($name) ?>" required>
            </div>
            <div class="field">
                <label for="category">Category *</label>
                <input type="text" id="category" name="category" value="<?= e($category) ?>" required>
            </div>
        </div>

        <div class="field">
            <label for="description">Description *</label>
            <textarea id="description" name="description" rows="4" required><?= e($description) ?></textarea>
        </div>

        <div class="field">
            <label for="activities">Main Activities *</label>
            <textarea id="activities" name="activities" rows="3" required><?= e($activities) ?></textarea>
        </div>

        <div class="field">
            <label>Current Club Photo</label>
            <img src="../<?= e($image) ?>" alt="<?= e($name) ?>" style="max-width: 240px; border-radius: 14px; border: 1px solid var(--border);">
        </div>

        <div class="form-row-2">
            <div class="field">
                <label for="image_file">Upload New Photo <span class="hint-inline">(JPG, PNG, WEBP · Max 3MB)</span></label>
                <input type="file" id="image_file" name="image_file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
            </div>

            <div class="field">
                <label for="image">Or Switch to Ready Photo</label>
                <select id="image" name="image">
                    <option value="">Keep current photo</option>
                    <?php foreach ($images as $path => $label): ?>
                        <option value="<?= e($path) ?>"<?= $image === $path ? ' selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-actions">
            <a href="clubs.php" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </form>
</section>

<?php require '../footer.php'; ?>
