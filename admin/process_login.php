<?php
session_start();
require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$username || !$password) {
        $_SESSION['login_error'] = 'Please fill in all fields.';
        header('Location: login.php');
        exit;
    }

    // Check admin credentials
    $stmt = db_connect()->prepare("SELECT * FROM admins WHERE username = :u LIMIT 1");
    $stmt->execute([':u' => $username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        // Success
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['username'];
        header('Location: index.php');
        exit;
    } else {
        // Fail
        $_SESSION['login_error'] = 'Invalid username or password.';
        header('Location: login.php');
        exit;
    }
}
header('Location: login.php');
exit;
