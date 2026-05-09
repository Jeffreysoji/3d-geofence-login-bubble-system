<?php
session_start();
require_once __DIR__ . '/../db.php';

// Must have a token
if (!isset($_SESSION['pending_token'])) {
    header('Location: login.php');
    exit;
}

$token = $_SESSION['pending_token'];
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>KKBank — Security Check</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="/kkbank/style.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: #f0f2f5;
            text-align: center;
            padding: 1rem;
            box-sizing: border-box;
        }

        .card {
            background: #fff;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            max-width: 400px;
            width: 100%;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 1.5rem;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        h2 {
            margin-bottom: .5rem;
            color: #0b3a6b;
        }

        p {
            color: #64748b;
            margin-bottom: 1.5rem;
        }

        .btn-cancel {
            color: #ef4444;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 600;
            text-decoration: underline;
        }

        #status-msg {
            font-weight: bold;
            margin-bottom: 1rem;
            min-height: 1.5em;
        }

        .approved {
            color: #16a34a;
        }

        .rejected {
            color: #dc2626;
        }
    </style>
</head>

<body>
    <div class="card">
        <div id="loader" class="spinner"></div>
        <h2>Unusual Login Detected</h2>
        <p>You are signing in from outside your secure geofence bubble.</p>
        <p><strong>An admin alert has been sent.</strong><br>Please wait for an admin to approve this request.</p>

        <div id="status-msg">Waiting for approval...</div>

        <form action="login.php" method="GET">
            <button class="btn-cancel">Cancel Request</button>
        </form>
    </div>

    <script>
        const token = <?= json_encode($token) ?>;
        const statusEl = document.getElementById('status-msg');
        const loaderEl = document.getElementById('loader');

        // Poll every 3 seconds
        const interval = setInterval(checkStatus, 3000);

        async function checkStatus() {
            try {
                const res = await fetch('/kkbank/user/check_approval.php?token=' + token);
                const data = await res.json();

                if (data.status === 'approved') {
                    clearInterval(interval);
                    statusEl.textContent = 'Approved! Logging you in...';
                    statusEl.className = 'approved';
                    loaderEl.style.display = 'none';
                    setTimeout(() => window.location.href = '/kkbank/user/home.php', 1000);
                } else if (data.status === 'rejected') {
                    clearInterval(interval);
                    statusEl.textContent = 'Request Declined by Admin.';
                    statusEl.className = 'rejected';
                    loaderEl.style.display = 'none';
                }
            } catch (e) {
                console.error(e);
            }
        }
    </script>
</body>

</html>