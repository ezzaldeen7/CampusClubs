<?php
require_once 'db.php';

if (is_logged_in()) {
    if ($_SESSION['user_role'] === 'admin') {
        redirect('admin/dashboard.php');
    }
    redirect('student/dashboard.php');
}

redirect('login.php');
