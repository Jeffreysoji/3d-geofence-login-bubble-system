<?php
// user/geofence_auth.php
session_start();
require_once __DIR__ . '/../db.php';

// require login
if (!isset($_SESSION['user_id'])) {
    header('Location: /kkbank/user/login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$user = db_fetch_one("SELECT id, email, password_hash FROM users WHERE id = :id LIMIT 1", [':id'=>$userId]);
if (!$user) {
    session_destroy();
    header('Location: /kkbank/user/login.php');
    exit;
}

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pw = $_POST['password'] ?? '';
    if ($pw === '') {
        $err = 'Enter your password to continue.';
    } else {
        if (password_verify($pw, $user['password_hash'])) {
            // set short-lived geofence auth flag (valid for 5 minutes)
            $_SESSION['geofence_auth_ts'] = time();
            // redirect to geofence editor
            header('Location: /kkbank/user/geofence.php');
            exit;
        } else {
            $err = 'Password incorrect.';
            // log failed re-auth
            log_auth_attempt($userId, $user['email'], $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '', null, null, false, 'block', 'geofence_reauth_failed');
        }
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Re-authenticate — Geofence</title>
  <link rel="stylesheet" href="/kkbank/style.css">
  <style>
    .card{max-width:520px;margin:3rem auto;padding:1.2rem;background:#fff;border-radius:10px;box-shadow:0 12px 28px rgba(12,34,78,0.06)}
    input{width:100%;padding:.65rem;border-radius:8px;border:1px solid #e6eefc;margin-bottom:.75rem}
    .btn{padding:.6rem .9rem;border-radius:8px;background:#0066ff;color:#fff;border:none;cursor:pointer}
    .err{color:#b42318;margin-bottom:.6rem}
  </style>
</head>
<body>
  <div class="card">
    <h2>Confirm your password</h2>
    <p>For security, please re-enter your password to edit your geofence.</p>
    <?php if ($err): ?><div class="err"><?=htmlspecialchars($err)?></div><?php endif; ?>
    <form method="POST" action="">
      <label>Password</label>
      <input type="password" name="password" required />
      <div style="display:flex;gap:.6rem;margin-top:.6rem">
        <button class="btn" type="submit">Confirm & Edit</button>
        <a href="/kkbank/user/home.php" style="align-self:center">Cancel</a>
      </div>
    </form>
  </div>
</body>
</html>
