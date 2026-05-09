<?php
session_start();
// If already logged in, redirect to home/dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: /kkbank/user/home.php');
    exit;
}

$register_success = $_SESSION['register_success'] ?? null;
unset($_SESSION['register_success']);
$login_error = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>KKBank — User Login</title>
  <link rel="stylesheet" href="/kkbank/style.css" />
  <style>
    /* small login-specific styles */
    .login-wrap { max-width:420px; margin:3.5rem auto; background:#f0f2f5; padding:1.6rem; border-radius:12px; box-shadow:0 12px 30px rgba(12,34,78,0.06); }
    .login-wrap h2 { margin:0 0 0.6rem; }
    .form-row { margin-bottom:0.9rem; display:flex; flex-direction:column; gap:.35rem; }
    input[type="email"], input[type="password"] { padding:.7rem .9rem; border-radius:8px; border:1px solid #e6eefc; }
    .meta { font-size:.9rem; color:#556; margin-top:.6rem; }
    .btn { padding:.7rem 1rem; border-radius:10px; border:none; cursor:pointer; }
    .btn-primary { background: linear-gradient(90deg,#00b4ff,#0066ff); color:#fff; font-weight:700; }
    .helper-links { display:flex; justify-content:space-between; margin-top:.6rem; }
    .hidden { display:none; }
    #geo-status { font-size:.9rem; color:#334155; margin-top:.5rem; }
    .flash { background:#ecfeff;border:1px solid #bff0ff;padding:.6rem;border-radius:8px;margin-bottom:.8rem;color:#034; }
    .err { background:#fff4f4;border:1px solid #ffd2d2;padding:.6rem;border-radius:8px;color:#8a1f1f;margin-bottom:.8rem; }
  </style>
</head>
<body>
  <div class="container">
    <div class="login-wrap" role="main" aria-labelledby="login-title">
      <h2 id="login-title">KKBank — Account Login</h2>

      <?php if ($register_success): ?>
        <div class="flash"><?= htmlspecialchars($register_success) ?></div>
      <?php endif; ?>

      <?php if ($login_error): ?>
        <div class="err"><?= htmlspecialchars($login_error) ?></div>
      <?php endif; ?>

      <form id="loginForm" method="POST" action="/kkbank/user/process_login.php" autocomplete="off">
        <div class="form-row">
          <label for="email">Email</label>
          <input id="email" name="email" type="text" required placeholder="you@example.com" />
        </div>

        <div class="form-row">
          <label for="password">Password</label>
          <input id="password" name="password" type="password" required placeholder="Your password" />
        </div>

        <!-- Hidden fields to send geolocation -->
        <input type="hidden" name="lat" id="lat" />
        <input type="hidden" name="lng" id="lng" />
        <input type="hidden" name="accuracy" id="accuracy" />

        <button type="submit" class="btn btn-primary">Sign in</button>

        <div class="helper-links">
          <a href="/kkbank/user/register.php">Create account</a>
          <!-- Edit Geofence link removed as requested -->
        </div>

        <div id="geo-status" aria-live="polite">Allow location to enable GeoFence check (recommended).</div>
      </form>

      <p class="meta">If you deny location permission, we will challenge the login for your security.</p>
    </div>
  </div>

<script>
const form = document.getElementById('loginForm');
const latF = document.getElementById('lat');
const lngF = document.getElementById('lng');
const accF = document.getElementById('accuracy');
const geoStatus = document.getElementById('geo-status');

function tryGetLocation() {
  if (!navigator.geolocation) {
    geoStatus.textContent = 'Geolocation not supported by browser.';
    return;
  }
  geoStatus.textContent = 'Requesting location… (will not block sign-in)';
  navigator.geolocation.getCurrentPosition((pos) => {
    latF.value = pos.coords.latitude;
    lngF.value = pos.coords.longitude;
    accF.value = pos.coords.accuracy;
    geoStatus.textContent = 'Location captured for verification.';
  }, (err) => {
    console.warn('Geolocation error', err);
    geoStatus.textContent = 'Location not available — login will be challenged for safety.';
  }, { timeout: 8000, enableHighAccuracy: true });
}

tryGetLocation();
document.getElementById('password').addEventListener('focus', tryGetLocation);

// do not block submit; location is optional
form.addEventListener('submit', (e) => {});
</script>
</body>
</html>
