<?php
// user/register.php
session_start();
require_once __DIR__ . '/../db.php';

$errors = [];
$old = ['email' => '', 'phone' => '', 'security_question' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  $confirm = $_POST['confirm_password'] ?? '';
  $phone = trim($_POST['phone'] ?? '');
  $security_question = trim($_POST['security_question'] ?? '');
  $security_answer = trim($_POST['security_answer'] ?? '');

  // Keep old values
  $old['email'] = htmlspecialchars($email, ENT_QUOTES);
  $old['phone'] = htmlspecialchars($phone, ENT_QUOTES);
  $old['security_question'] = htmlspecialchars($security_question, ENT_QUOTES);

  // SIMPLE VALIDATION (No valid-email checking)
  if ($email === '') {
    $errors[] = 'Email field cannot be empty.';
  }

  if (strlen($password) < 6) {
    $errors[] = 'Password must be at least 6 characters.';
  }

  if ($password !== $confirm) {
    $errors[] = 'Password and confirmation do not match.';
  }

  // Check if email already exists
  if (empty($errors)) {
    $exists = db_fetch_one("SELECT id FROM users WHERE email = :email LIMIT 1", [
      ':email' => $email
    ]);

    if ($exists) {
      $errors[] = 'This email is already registered. Please login.';
    }
  }

  if (empty($errors)) {

    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Hash security answer
    $sec_answer_hash = null;
    if (!empty($security_question) && !empty($security_answer)) {
      $sec_answer_hash = password_hash($security_answer, PASSWORD_DEFAULT);
    }

    // Insert new user
    db_execute("
            INSERT INTO users (email, password_hash, phone, security_question, security_answer_hash, created_at)
            VALUES (:email, :pass, :phone, :sq, :sa, NOW())
        ", [
      ':email' => $email,
      ':pass' => $password_hash,
      ':phone' => $phone ?: null,
      ':sq' => $security_question ?: null,
      ':sa' => $sec_answer_hash ?: null,
    ]);

    $_SESSION['register_success'] = 'Account created successfully. Please login.';
    header("Location: /kkbank/user/login.php");
    exit;
  }
}
?>
<!doctype html>
<html>

<head>
  <meta charset="utf-8" />
  <title>Create KKBank Account</title>
  <link rel="stylesheet" href="/kkbank/style.css" />
  <style>
    .card {
      max-width: 540px;
      margin: 2.4rem auto;
      padding: 1.5rem;
      background: #f0f2f5;
      border-radius: 10px;
      box-shadow: 0 12px 28px rgba(12, 34, 78, 0.06);
    }

    label {
      font-weight: 600;
      margin-bottom: 4px;
      display: block
    }

    input {
      width: 100%;
      padding: .75rem;
      border-radius: 8px;
      border: 1px solid #ccd5e0;
      margin-bottom: 1rem
    }

    .btn-primary {
      background: #0066ff;
      color: #fff;
      border: none;
      padding: .7rem 1rem;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer
    }

    .errbox {
      background: #fee;
      border: 1px solid #f99;
      color: #900;
      padding: .7rem;
      border-radius: 8px;
      margin-bottom: 1rem
    }
  </style>
</head>

<body>

  <div class="container">
    <div class="card">
      <h2>Create your KKBank Account</h2>

      <?php if (!empty($errors)): ?>
        <div class="errbox">
          <ul>
            <?php foreach ($errors as $e): ?>
              <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="POST" action="">

        <label>Email</label>
        <input type="text" name="email" required value="<?= $old['email'] ?>" placeholder="Enter any email-like text" />

        <label>Password</label>
        <input type="password" name="password" required />

        <label>Confirm Password</label>
        <input type="password" name="confirm_password" required />

        <label>Phone (optional)</label>
        <input type="text" name="phone" value="<?= $old['phone'] ?>" />

        <label>Security Question (required)</label>
        <input type="text" name="security_question" value="<?= $old['security_question'] ?>" required
          placeholder="e.g. Your first pet's name?" />

        <label>Security Answer (required)</label>
        <input type="text" name="security_answer" required />

        <button type="submit" class="btn-primary">Create Account</button>

        <div style="margin-top:10px;">
          <a href="/kkbank/user/login.php">Already have an account?</a>
        </div>

      </form>
    </div>
  </div>

</body>

</html>