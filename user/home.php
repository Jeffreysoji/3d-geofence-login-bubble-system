<?php
// user/home.php
session_start();
require_once __DIR__ . '/../db.php';

// require login (same as dashboard)
if (!isset($_SESSION['user_id'])) {
  header('Location: /kkbank/user/login.php');
  exit;
}

$userId = (int) $_SESSION['user_id'];
$userEmail = $_SESSION['user_email'] ?? '(you)';

?>
<!doctype html>
<html>

<head>
  <meta charset="utf-8">
  <title>KKBank — Home</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="/kkbank/style.css">
  <style>
    .container {
      width: min(1100px, 94%);
      margin: 1.6rem auto
    }

    .top {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1rem
    }

    .card {
      background: #fff;
      padding: 1rem;
      border-radius: 10px;
      box-shadow: 0 12px 30px rgba(12, 34, 78, 0.06)
    }

    .grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 1rem
    }

    .tile {
      padding: 1rem;
      border-radius: 8px;
      background: #f8fbff;
      text-align: center
    }

    .btn {
      display: inline-block;
      padding: .6rem .9rem;
      border-radius: 8px;
      background: #0066ff;
      color: #fff;
      text-decoration: none
    }

    @media(max-width:860px) {
      .grid {
        grid-template-columns: repeat(1, 1fr)
      }
    }
  </style>
</head>

<body>
  <div class="container">
    <div class="top">
      <div>
        <h2 style="margin:0">Welcome, <?= htmlspecialchars($userEmail) ?></h2>
        <div style="color:#546">User ID: <?= $userId ?></div>
      </div>
      <div style="display:flex;gap:.6rem">
        <a class="btn" href="/kkbank/user/logout.php">Logout</a>
      </div>
    </div>

    <div class="card">
      <h3 style="margin-top:0">Your Banking</h3>
      <div class="grid" style="margin-top:1rem">
        <div class="tile">
          <h4>Accounts</h4>
          <p>View balances, mini-statement, and transactions.</p>
          <div style="margin-top:.6rem"><a href="#" class="btn">Open Accounts</a></div>
        </div>
        <div class="tile">
          <h4>Dashboard</h4>
          <p>View your overall banking dashboard and stats.</p>
          <div style="margin-top:.6rem"><a href="/kkbank/user/dashboard.php" class="btn">Open Dashboard</a></div>
        </div>
        <div class="tile">
          <h4>Transfers</h4>
          <p>Send money, manage beneficiaries, schedule transfers.</p>
          <div style="margin-top:.6rem"><a href="#" class="btn">Make Transfer</a></div>
        </div>
        <div class="tile">
          <h4>Security</h4>
          <p>2FA, trusted devices, and Geofence settings.</p>
          <div style="margin-top:.6rem">
            <!-- Manage Geofence requires re-authentication -->
            <a class="btn" href="/kkbank/user/geofence_auth.php">Manage Geofence</a>
          </div>
        </div>
      </div>
    </div>

    <div style="height:16px"></div>

    <div class="card" style="margin-top:1rem">
      <h4 style="margin:0 0 .6rem 0">Quick actions</h4>
      <ul>
        <li><a href="/kkbank/user/dashboard.php">Open Geofence Dashboard (view only)</a></li>
        <li><a href="/kkbank/user/geofence.php"
            onclick="return confirm('Direct open will require re-auth. Use Manage Geofence to re-auth first.');">Open
            Geofence Editor (may require re-auth)</a></li>
      </ul>
    </div>
  </div>
</body>

</html>