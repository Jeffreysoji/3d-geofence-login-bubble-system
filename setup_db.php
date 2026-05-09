<?php
// setup_db.php
// Visit http://localhost/kkbank/setup_db.php to initialize the database

$host = '127.0.0.1';
$user = 'root';
$pass = ''; // Default XAMPP password is empty
$dbname = 'kkbank_db';

$error = null;

try {
  // 1. Connect to MySQL Server (no DB selected yet)
  $pdo = new PDO("mysql:host=$host", $user, $pass);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  // 2. Create Database
  $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

  // 3. Select Database
  $pdo->exec("USE `$dbname`");

  // 4. Create Tables
  // Users
  $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
          id INT AUTO_INCREMENT PRIMARY KEY,
          email VARCHAR(255) UNIQUE NOT NULL,
          password_hash VARCHAR(255) NOT NULL,
          phone VARCHAR(30),
          security_question VARCHAR(255),
          security_answer_hash VARCHAR(255),
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB CHARSET=utf8mb4;
    ");

  // Geofences
  $pdo->exec("
        CREATE TABLE IF NOT EXISTS geofences (
          id INT AUTO_INCREMENT PRIMARY KEY,
          user_id INT NOT NULL,
          type ENUM('circle','polygon') NOT NULL,
          center_lat DOUBLE NULL,
          center_lng DOUBLE NULL,
          radius_m INT NULL,
          polygon_json TEXT NULL,
          active TINYINT(1) DEFAULT 1,
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB CHARSET=utf8mb4;
    ");

  // Auth Logs
  $pdo->exec("
        CREATE TABLE IF NOT EXISTS auth_logs (
          id INT AUTO_INCREMENT PRIMARY KEY,
          user_id INT NULL,
          email_attempted VARCHAR(255),
          ip VARCHAR(45),
          ua TEXT,
          lat DOUBLE NULL,
          lng DOUBLE NULL,
          inside_geofence TINYINT(1) NULL,
          action VARCHAR(64) NOT NULL,
          details TEXT NULL,
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB CHARSET=utf8mb4;
    ");

  // Admins
  $pdo->exec("
        CREATE TABLE IF NOT EXISTS admins (
          id INT AUTO_INCREMENT PRIMARY KEY,
          username VARCHAR(50) UNIQUE NOT NULL,
          password_hash VARCHAR(255) NOT NULL,
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB CHARSET=utf8mb4;
    ");

  // Seed default admin if not exists
  $stmt = $pdo->query("SELECT count(*) FROM admins WHERE username='admin'");
  if ($stmt->fetchColumn() == 0) {
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt->execute([$hash]);
  }

  // Login Requests
  $pdo->exec("
        CREATE TABLE IF NOT EXISTS login_requests (
          id INT AUTO_INCREMENT PRIMARY KEY,
          user_id INT NOT NULL,
          request_token VARCHAR(64) UNIQUE NOT NULL,
          ip VARCHAR(45),
          device VARCHAR(255) NULL,
          status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
          FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB CHARSET=utf8mb4;
    ");

} catch (PDOException $e) {
  $error = "DB Setup Failed: " . $e->getMessage();
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>KKBank — Database Setup</title>
  <link rel="stylesheet" href="/kkbank/style.css" />
  <style>
    /* Clean setup wrapper similar to login styles */
    .setup-wrap {
      max-width: 420px;
      margin: 4rem auto;
      background: #f0f2f5;
      padding: 2.5rem 1.6rem;
      border-radius: 12px;
      box-shadow: 0 12px 30px rgba(12,34,78,0.06);
      text-align: center;
    }
    .setup-wrap h2 { margin-top: 0; margin-bottom: 0.6rem; font-size: 1.8rem; }
    .status-icon {
      font-size: 3.5rem;
      margin-bottom: 1rem;
      display: inline-block;
    }
    .status-icon.success { color: #10b981; }
    .status-icon.error { color: #ef4444; }
    
    .status-logo {
      width: 80px;
      height: 80px;
      object-fit: contain;
      margin-bottom: 1rem;
    }

    .setup-wrap p { color: #4a5568; line-height: 1.5; margin-bottom: 2rem; }
    
    .btn {
      display: inline-block;
      padding: 0.7rem 1.2rem;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 600;
      cursor: pointer;
      font-size: 0.95rem;
    }
    .btn-primary {
      background: linear-gradient(90deg, #00b4ff, #0066ff);
      color: #fff;
      border: none;
    }
    .btn-outline {
      border: 1px solid #ccd5e0;
      color: #334155;
      background: transparent;
    }
    .action-links {
      display: flex;
      justify-content: center;
      gap: 1rem;
    }
    .errbox {
      background: #fff4f4;
      border: 1px solid #ffd2d2;
      padding: 1rem;
      border-radius: 8px;
      color: #8a1f1f;
      text-align: left;
      font-size: 0.9rem;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="setup-wrap">
      <?php if ($error): ?>
        <div class="status-icon error">✖</div>
        <h2>Setup Failed</h2>
        <div class="errbox"><?= htmlspecialchars($error) ?></div>
        <div class="action-links" style="margin-top: 1.5rem;">
          <a href="/kkbank/setup_db.php" class="btn btn-outline">Retry Setup</a>
        </div>
      <?php else: ?>
        <img src="/kkbank/kkbank_logo.png" alt="KK Bank Logo" class="status-logo" />
        <h2>Welcome to KK Bank</h2>
        <p>Where privacy matters</p>
        
        <div class="action-links">
          <a href="/kkbank/user/register.php" class="btn btn-primary">Register a User</a>
          <a href="/kkbank/user/login.php" class="btn btn-outline">Go to Login</a>
        </div>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
