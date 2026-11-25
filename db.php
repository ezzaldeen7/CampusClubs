<?php
/**
 * Database Connection & Simple Shared Helpers
 * Campus Clubs Management System (Procedural PHP + PDO)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function env_val(string $key, ?string $default = null): ?string
{
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') return (string) $_ENV[$key];
    if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') return (string) $_SERVER[$key];
    $v = getenv($key);
    if ($v !== false && $v !== '') return (string) $v;
    return $default;
}

$db_host = env_val('DB_HOST') ?? env_val('MYSQLHOST') ?? '127.0.0.1';
$db_port = env_val('DB_PORT') ?? env_val('MYSQLPORT') ?? '3306';
$db_name = env_val('DB_NAME') ?? env_val('MYSQLDATABASE') ?? 'campus_clubs_db';
$db_user = env_val('DB_USER') ?? env_val('MYSQLUSER') ?? 'root';
$db_pass = env_val('DB_PASS') ?? env_val('MYSQLPASSWORD') ?? '';

try {
    $pdo = new PDO(
        "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    error_log('Database connection error: ' . $e->getMessage());
    http_response_code(500);
    die('DB Error (' . htmlspecialchars($db_host . ':' . $db_port . '/' . $db_name) . '): ' . htmlspecialchars($e->getMessage()));
}

// إنشاء الجداول تلقائياً مرة واحدة فقط إذا كانت قاعدة البيانات فارغة
if (!$pdo->query("SHOW TABLES LIKE 'users'")->fetch()) {
    $raw = file_get_contents(__DIR__ . '/campus_clubs.sql');
    $lines = array_filter(preg_split('/\R/', $raw), fn($l) => trim($l) !== '' && !str_starts_with(trim($l), '--'));
    $sql = implode("\n", $lines);
    $sql = preg_replace('/CREATE\s+DATABASE\b.*?;/is', '', $sql);
    $sql = preg_replace('/USE\s+`?[^;`]+`?\s*;/i', '', $sql);
    foreach (explode(";\n", $sql) as $stmt) {
        $stmt = trim($stmt);
        if ($stmt !== '') {
            $pdo->exec($stmt);
        }
    }
}

function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function base_prefix(): string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    if (preg_match('#/(admin|student)/[^/]+$#', $script)) {
        return '../';
    }
    return '';
}

function redirect(string $path): void
{
    header('Location: ' . base_prefix() . ltrim($path, '/'));
    exit;
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): bool
{
    $token = $_POST['csrf_token'] ?? '';
    return !empty($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function is_logged_in(): bool
{
    return isset($_SESSION['user_id'], $_SESSION['user_role']);
}

function require_login(): void
{
    if (!is_logged_in()) {
        set_flash('error', 'Please sign in to continue.');
        redirect('login.php');
    }
}

function require_role(string $role): void
{
    global $pdo;
    require_login();
    if ($_SESSION['user_role'] === 'student') {
        $st = $pdo->prepare('SELECT account_status FROM users WHERE id = :id LIMIT 1');
        $st->execute([':id' => (int) $_SESSION['user_id']]);
        $accStatus = $st->fetchColumn();
        if ($accStatus !== 'Approved') {
            $_SESSION = [];
            session_destroy();
            session_start();
            set_flash('error', 'Your student account is not approved to access the portal.');
            redirect('login.php');
        }
    }
    if ($_SESSION['user_role'] !== $role) {
        http_response_code(403);
        $prefix = base_prefix();
        $home = ($_SESSION['user_role'] === 'admin') ? 'admin/dashboard.php' : 'student/dashboard.php';
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
        echo '<title>Access Denied — Campus Clubs</title><link rel="stylesheet" href="' . e($prefix . 'css/style.css') . '"></head>';
        echo '<body class="page-center"><main class="editorial-notice"><span class="kicker">403 · Restricted</span>';
        echo '<h1>You do not have permission to view this page.</h1>';
        echo '<p>This section is reserved for ' . e($role) . ' accounts.</p>';
        echo '<a class="btn btn-primary" href="' . e($prefix . $home) . '">Return to My Dashboard</a></main></body></html>';
        exit;
    }
}

function format_date(?string $dateStr): string
{
    if (empty($dateStr)) {
        return '—';
    }
    $ts = strtotime($dateStr);
    return $ts ? date('M j, Y', $ts) : e($dateStr);
}

function status_pill(string $status): string
{
    $key = strtolower(trim($status));
    $cls = 'status-pending';
    if ($key === 'approved') {
        $cls = 'status-approved';
    } elseif ($key === 'rejected') {
        $cls = 'status-rejected';
    }
    return '<span class="status-pill ' . $cls . '">' . e(ucfirst($key)) . '</span>';
}

function active_nav(string $filename): string
{
    return basename($_SERVER['SCRIPT_NAME'] ?? '') === $filename ? ' is-active' : '';
}

function club_image_options(): array
{
    return [
        'images/club-tech.jpg'       => 'Technology & Coding Photo',
        'images/club-design.jpg'     => 'Arts & Editorial Design Photo',
        'images/club-culture.jpg'    => 'Literature & Culture Photo',
        'images/club-community.jpg'  => 'Community & Volunteering Photo',
        'images/club-leadership.jpg' => 'Entrepreneurship & Leadership Photo',
    ];
}

function handle_club_image_upload(array $file, array &$errors): ?string
{
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Failed to upload image. Please try again.';
        return null;
    }
    if (($file['size'] ?? 0) > 3 * 1024 * 1024) {
        $errors[] = 'Image size must not exceed 3 MB.';
        return null;
    }

    $allowed_mimes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = $finfo ? finfo_file($finfo, $file['tmp_name']) : '';
    if ($finfo) {
        finfo_close($finfo);
    }

    if (!isset($allowed_mimes[$mime])) {
        $errors[] = 'Only JPG, PNG, and WEBP image files are allowed.';
        return null;
    }

    $upload_dir = __DIR__ . '/images';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $filename = 'club_' . bin2hex(random_bytes(8)) . '.' . $allowed_mimes[$mime];
    $dest     = $upload_dir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        $errors[] = 'Could not save the uploaded image to the images/ folder.';
        return null;
    }

    return 'images/' . $filename;
}