<?php
require_once 'db.php';

if (is_logged_in()) {
    redirect($_SESSION['user_role'] === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php');
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Your session expired. Please refresh the page and try again.';
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            $error = 'Please enter both your email address and password.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $stmt = $pdo->prepare(
                'SELECT id, name, email, password, role, account_status FROM users WHERE email = :email LIMIT 1'
            );
            $stmt->execute([':email' => strtolower($email)]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if ($user['role'] === 'student' && $user['account_status'] === 'Pending') {
                    $error = 'Your account is pending administrator approval. You will be able to access the student portal once approved.';
                } elseif ($user['role'] === 'student' && $user['account_status'] === 'Rejected') {
                    $error = 'Your registration request was rejected by the administrator. Portal access is not available.';
                } else {
                    session_regenerate_id(true);
                    $_SESSION['user_id']    = (int) $user['id'];
                    $_SESSION['user_name']  = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role']  = $user['role'];

                    set_flash('success', 'Welcome back, ' . $user['name'] . '.');
                    redirect($user['role'] === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php');
                }
            } else {
                $error = 'Invalid email or password. Please try again.';
            }
        }
    }
}

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — Campus Clubs</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="login-body">
<main class="login-split">
    <section class="login-editorial">
        <div class="login-editorial-top">
            <span class="edition-tag">Campus Life &middot; Student Communities</span>
            <div class="brand-lockup">
                <span class="brand-monogram brand-monogram-ochre">CC</span>
                <span class="brand-lockup-name">Campus Clubs</span>
            </div>
        </div>

        <div class="login-editorial-center">
            <h1 class="editorial-headline">Find your circle.<br>Shape campus culture.</h1>
            <div class="ochre-rule" aria-hidden="true"></div>
            <p class="editorial-lead">
                Explore creative, technical, and cultural student clubs, apply for membership in a few clicks, and follow every campus announcement in one calm place.
            </p>
        </div>

        <div class="login-editorial-foot">
            <span>Creative Coding &middot; Visual Arts &middot; Literature &middot; Volunteering &middot; Ideas Lab</span>
        </div>
    </section>

    <section class="login-panel">
        <div class="login-box">
            <span class="kicker">Member &amp; Admin Portal</span>
            <h2 class="login-heading">Sign in to your account</h2>
            <p class="login-sub">Use your university course email and password to continue.</p>

            <?php if ($flash): ?>
                <div class="notice notice-<?= e($flash['type']) ?>" data-notice>
                    <span><?= e($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <div class="notice notice-error" role="alert">
                    <span><?= e($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="post" action="login.php" class="form-stack" autocomplete="off" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                <div class="field">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" value="<?= e($email) ?>" placeholder="student1@campus.edu" required autofocus>
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <div class="password-wrap">
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        <button type="button" class="password-btn" data-toggle-password="password">Show</button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Sign In</button>
            </form>

            <div class="auth-switch">
                New student? <a href="register.php" class="text-link">Create a student account &rarr;</a>
            </div>
</div>
    </section>
</main>
<script src="js/script.js" defer></script>
</body>
</html>
