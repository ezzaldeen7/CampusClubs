<?php
require_once 'db.php';

$sql = file_get_contents(__DIR__ . '/campus_clubs.sql');
$sql = preg_replace('/^\s*CREATE\s+DATABASE\b.*?;/ims', '', $sql);
$sql = preg_replace('/^\s*USE\s+`?[^;`]+`?\s*;/im', '', $sql);

try {
    $pdo->exec($sql);
    echo '<h2>Success! All 5 tables and sample data were imported into Railway MySQL.</h2>';
    echo '<p><a href="login.php">Open Login Page &rarr;</a></p>';
} catch (PDOException $e) {
    echo '<h2>Import Error:</h2><pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
}