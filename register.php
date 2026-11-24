<?php
require_once 'db.php';

if (is_logged_in()) {
    redirect($_SESSION['user_role'] === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php');
}

$errors = [];
$name   = '';
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Your session expired. Please refresh the page and try again.';
    } else {
        $name             = trim($_POST['name'] ?? '');
        $email            = trim(strtolower($_POST['email'] ?? ''));
        $password         = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if ($name === '' || mb_strlen($name) < 3) {
            $errors[] = 'Full name must be at least 3 characters.';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        if (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters long.';
        }
        if ($password !== $confirm_password) {
            $errors[] = 'Passwords do not match.';
        }

        if (empty($errors)) {
            $check = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
            $check->execute([':email' => $email]);
            if ($check->fetch()) {
                $errors[] = 'An account with this email address already exists.';
            }
        }

        if (empty($errors)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare(
                "INSERT INTO users (name, email, password, role, account_status)
                 VALUES (:name, :email, :password, 'student', 'Pending')"
            );
            $stmt->execute([
                ':name'     => $name,
                ':email'    => $email,
                ':password' => $hashed,
            ]);

            set_flash(
                'success',
                'Registration submitted! Your student account is pending administrator approval. Once approved, you can sign in and access the student portal.'
            );
            redirect('login.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration — Campus Clubs</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="login-body">
<main class="login-split">
    <section class="login-editorial">
        <div class="login-editorial-top">
            <span class="edition-tag">New Student Registration</span>
            <div class="brand-lockup">
                <span class="brand-monogram brand-monogram-ochre">CC</span>
                <span class="brand-lockup-name">Campus Clubs</span>
            </div>
        </div>

        <div class="login-editorial-center">
            <h1 class="editorial-headline">Join the campus<br>community.</h1>
            <div class="ochre-rule" aria-hidden="true"></div>
            <p class="editorial-lead">
                Create your student account to explore clubs, apply for membership, and follow campus announcements. New accounts are reviewed and approved by the administrator before portal access is enabled.
            </p>
        </div>

        <div class="login-editorial-foot">
            <span>Student Portal Access &middot; Administrator Verified Accounts</span>
        </div>
    </section>

    <section class="login-panel">
        <div class="login-box">
            <span class="kicker">Create Student Account</span>
            <h2 class="login-heading">Register as a Student</h2>
            <p class="login-sub">Fill in your details below. Your account will be activated after administrator approval.</p>

            <?php if (!empty($errors)): ?>
                <div class="notice notice-error" role="alert">
                    <div><?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?></div>
                </div>
            <?php endif; ?>

            <form method="post" action="register.php" class="form-stack" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                <div class="field">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" value="<?= e($name) ?>" placeholder="Enter your full name" required autofocus>
                </div>

                <div class="field">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" value="<?= e($email) ?>" placeholder="yourname@campus.edu" required>
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <div class="password-wrap">
                        <input type="password" id="password" name="password" placeholder="At least 6 characters" required>
                        <button type="button" class="password-btn" data-toggle-password="password">Show</button>
                    </div>
                </div>

                <div class="field">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="password-wrap">
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter your password" required>
                        <button type="button" class="password-btn" data-toggle-password="confirm_password">Show</button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Create Student Account</button>
            </form>

            <div class="auth-switch">
                Already have an account? <a href="login.php" class="text-link">Sign in here &rarr;</a>
            </div>
        </div>
    </section>
</main>
<script src="js/script.js" defer></script>
</body>
</html>
