<?php
session_start();
if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}
$error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>KKBank — Admin Login</title>
    <link rel="stylesheet" href="/kkbank/style.css">
    <style>
        body {
            background-color: #f0f2f5;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }

        .login-box {
            background: #fff;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 400px;
        }

        .brand {
            font-size: 1.5rem;
            font-weight: 800;
            color: #0b3a6b;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: .4rem;
            color: #4a5568;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .form-control {
            width: 100%;
            padding: .75rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 1rem;
        }

        .btn-block {
            width: 100%;
            padding: .75rem;
            background: #0b3a6b;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 1rem;
        }

        .btn-block:hover {
            background: #092c52;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: .75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="login-box">
        <div class="brand">KKBank Admin</div>

        <?php if ($error): ?>
            <div class="error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="process_login.php">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" placeholder="admin" required
                    autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="••••••••"
                    required>
            </div>
            <button type="submit" class="btn-block">Login to Panel</button>
        </form>

        <div style="text-align:center; margin-top:1rem;">
            <a href="/kkbank/index.html" style="color:#64748b; font-size:0.9rem; text-decoration:none;">&larr; Back to
                Site</a>
        </div>
    </div>
</body>

</html>