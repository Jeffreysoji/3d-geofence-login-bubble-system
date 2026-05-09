<?php
session_start();
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time()-42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
unset($_SESSION['geofence_auth_ts']);
session_destroy();
header('Location: /kkbank/user/login.php');
exit;
