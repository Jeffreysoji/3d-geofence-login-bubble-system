<?php
session_start();
require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['challenge_user_id']) || !isset($_SESSION['challenge_otp_hash'])) {
  header('Location: login.php');
  exit;
}

$flash = $_SESSION['challenge_message'] ?? '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $otp = trim($_POST['otp'] ?? '');
  $sec_answer = trim($_POST['security_answer'] ?? '');

  if (!isset($_SESSION['challenge_expires']) || time() > $_SESSION['challenge_expires']) {
    $err = 'OTP expired. Please login again.';
    unset($_SESSION['challenge_user_id'], $_SESSION['challenge_otp_hash'], $_SESSION['challenge_expires']);
  } else {
    $_SESSION['challenge_attempts'] = ($_SESSION['challenge_attempts'] ?? 0) + 1;
    if ($_SESSION['challenge_attempts'] > 5) {
      $err = 'Too many attempts. Contact support.';
    } else {
      if (password_verify($otp, $_SESSION['challenge_otp_hash'])) {
        $uid = (int) $_SESSION['challenge_user_id'];
        $user = db_fetch_one("SELECT id, email, security_question, security_answer_hash FROM users WHERE id = :id LIMIT 1", [':id' => $uid]);
        if (!$user)
          $err = 'User not found.';
        else {
          $need_answer = !empty($user['security_question']) && !empty($user['security_answer_hash']);
          $sec_ok = true;
          if ($need_answer) {
            if (empty($sec_answer)) {
              $err = 'Please answer security question.';
              $sec_ok = false;
            } else if (!password_verify($sec_answer, $user['security_answer_hash'])) {
              $err = 'Security answer incorrect.';
              $sec_ok = false;
            }
          }

          if ($sec_ok) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            unset($_SESSION['challenge_user_id'], $_SESSION['challenge_otp_hash'], $_SESSION['challenge_expires'], $_SESSION['challenge_attempts'], $_SESSION['challenge_message'], $_SESSION['challenge_debug_otp']);
            log_auth_attempt($user['id'], $user['email'], $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '', null, null, false, 'allow-after-challenge', 'OTP validated (dev)');
            header('Location: /kkbank/user/home.php');
            exit;
          }
        }
      } else {
        $err = 'OTP incorrect.';
      }
    }
  }
}

?>
<!doctype html>
<html>

<head>
  <meta charset="utf-8" />
  <title>KKBank — Verify</title>
  <link rel="stylesheet" href="/kkbank/style.css" />
  <style>
    .card {
      max-width: 560px;
      margin: 2.4rem auto;
      padding: 1rem;
      background: #fff;
      border-radius: 10px;
      box-shadow: 0 12px 28px rgba(12, 34, 78, 0.06);
    }

    .err {
      color: #b42318;
      margin-top: .6rem
    }

    .debug {
      background: #f0f9ff;
      border: 1px solid #bde9ff;
      padding: .8rem;
      border-radius: 8px;
      margin: 8px 0
    }
  </style>
</head>

<body>
  <div class="container">
    <div class="card">
      <h2>Verify login</h2>
      <?php if ($flash): ?>
        <div class="debug"><?= htmlspecialchars($flash) ?></div><?php endif; ?>
      <?php if ($err): ?>
        <div class="err"><?= htmlspecialchars($err) ?></div><?php endif; ?>



      <form method="POST" action="">
        <label for="otp">One-time code (OTP)</label><br />
        <input id="otp" name="otp" type="text" maxlength="6" required
          style="padding:.6rem .8rem;border-radius:8px;border:1px solid #e6eefc" />

        <?php
        $uid = (int) ($_SESSION['challenge_user_id'] ?? 0);
        $secQ = null;
        if ($uid) {
          $u = db_fetch_one("SELECT security_question FROM users WHERE id = :id LIMIT 1", [':id' => $uid]);
          if ($u && !empty($u['security_question']))
            $secQ = $u['security_question'];
        }
        ?>
        <?php if ($secQ): ?>
          <div style="margin-top:.6rem;">
            <label><?= htmlspecialchars($secQ) ?></label><br />
            <input name="security_answer" type="text"
              style="padding:.6rem .8rem;border-radius:8px;border:1px solid #e6eefc" />
          </div>
        <?php endif; ?>

        <div style="margin-top:.8rem;">
          <button class="btn-primary" type="submit">Verify & Continue</button>
          <a href="login.php" style="margin-left:1rem;">Cancel</a>
        </div>
      </form>
      <p style="margin-top:.8rem;color:#556">If you want real OTP delivery, integrate an SMS/email provider and remove
        the debug OTP.</p>
    </div>
  </div>
</body>

</html>