<?php
require_once 'db.php';

$raw = file_get_contents(__DIR__ . '/campus_clubs.sql');
// إزالة التعليقات وأمري CREATE DATABASE و USE
$lines = preg_split('/\R/', $raw);
$clean = [];
foreach ($lines as $line) {
    $trim = trim($line);
    if ($trim === '' || str_starts_with($trim, '--')) continue;
    $clean[] = $line;
}
$sql = implode("\n", $clean);
$sql = preg_replace('/CREATE\s+DATABASE\b.*?;/is', '', $sql);
$sql = preg_replace('/USE\s+`?[^;`]+`?\s*;/i', '', $sql);

// تقسيم الأوامر وتنفيذها واحداً تلو الآخر
$statements = array_filter(array_map('trim', explode(";\n", $sql)));

try {
    foreach ($statements as $stmt) {
        if ($stmt !== '') {
            $pdo->exec($stmt);
        }
    }
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    echo '<h2>Success! Tables created in database:</h2><ul>';
    foreach ($tables as $t) {
        echo '<li>' . htmlspecialchars($t) . '</li>';
    }
    echo '</ul><p><a href="login.php">Open Login Page &rarr;</a></p>';
} catch (PDOException $e) {
    echo '<h2>Import Error:</h2><pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
}