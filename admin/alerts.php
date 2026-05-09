<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/../db.php';

// Handle Actions (Approve/Reject)
if (isset($_POST['action']) && isset($_POST['id'])) {
    $reqId = (int) $_POST['id'];
    $act = $_POST['action']; // 'approve' or 'reject'

    if ($act === 'approve') {
        db_execute("UPDATE login_requests SET status='approved' WHERE id=:id", [':id' => $reqId]);
    } elseif ($act === 'reject') {
        db_execute("UPDATE login_requests SET status='rejected' WHERE id=:id", [':id' => $reqId]);
    }
    header("Location: alerts.php");
    exit;
}

// Fetch Pending Requests
$requests = db_query("
    SELECT r.*, u.email 
    FROM login_requests r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.status = 'pending' 
    ORDER BY r.created_at DESC
");
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Admin — Security Alerts</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="/kkbank/style.css">
    <style>
        body {
            background: #f1f5f9;
            padding: 2rem;
        }

        .card {
            background: #fff;
            padding: 1.5rem;
            border-radius: 10px;
            max-width: 800px;
            margin: 0 auto;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        h2 {
            margin-top: 0;
            color: #0b3a6b;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .req-item {
            background: #fff7ed;
            border: 1px solid #ffedd5;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .req-info {
            color: #334155;
        }

        .req-meta {
            font-size: 0.85rem;
            color: #64748b;
            margin-top: 0.3rem;
        }

        .actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-approve {
            background: #16a34a;
            color: #fff;
            padding: .5rem .8rem;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-reject {
            background: #dc2626;
            color: #fff;
            padding: .5rem .8rem;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 600;
        }

        .empty {
            text-align: center;
            padding: 2rem;
            color: #64748b;
            font-style: italic;
        }

        .back {
            font-size: 0.9rem;
            text-decoration: none;
            color: #0b3a6b;
        }
    </style>
</head>

<body>
    <div class="card">
        <h2>
            Pending Login Alerts
            <a href="index.php" class="back">&larr; Dashboard</a>
        </h2>

        <?php if (empty($requests)): ?>
            <div class="empty">No pending alerts. All clear!</div>
        <?php else: ?>
            <?php foreach ($requests as $r): ?>
                <div class="req-item">
                    <div>
                        <div class="req-info"><strong>
                                <?= htmlspecialchars($r['email']) ?>
                            </strong> needs approval</div>
                        <div class="req-meta">
                            IP:
                            <?= htmlspecialchars($r['ip']) ?> • Time:
                            <?= $r['created_at'] ?><br>
                            Device:
                            <?= htmlspecialchars($r['device'] ?? 'Unknown') ?>
                        </div>
                    </div>
                    <div class="actions">
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                            <input type="hidden" name="action" value="approve">
                            <button class="btn-approve">Approve</button>
                        </form>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                            <input type="hidden" name="action" value="reject">
                            <button class="btn-reject">Reject</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>


        <!-- History Section -->
        <?php
        $history = db_query("
            SELECT r.*, u.email 
            FROM login_requests r 
            JOIN users u ON r.user_id = u.id 
            WHERE r.status != 'pending' 
            ORDER BY r.updated_at DESC LIMIT 50
        ");
        ?>
        <h3 style="margin-top:3rem;color:#475569;border-top:1px solid #e2e8f0;padding-top:1.5rem">Recent Validations History</h3>
        <?php if (empty($history)): ?>
            <div class="empty">No history available.</div>
        <?php else: ?>
            <table style="width:100%;border-collapse:collapse;margin-top:1rem;">
                <thead>
                    <tr style="background:#f8fafc;color:#475569;text-align:left;">
                        <th style="padding:.8rem;">Time</th>
                        <th style="padding:.8rem;">User</th>
                        <th style="padding:.8rem;">IP</th>
                        <th style="padding:.8rem;">Result</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $h): ?>
                        <tr style="border-bottom:1px solid #e2e8f0;">
                            <td style="padding:.8rem;color:#64748b;font-size:0.9rem"><?= $h['updated_at'] ?></td>
                            <td style="padding:.8rem;font-weight:600;color:#334155"><?= htmlspecialchars($h['email']) ?></td>
                            <td style="padding:.8rem;color:#64748b;font-size:0.9rem"><?= htmlspecialchars($h['ip']) ?></td>
                            <td style="padding:.8rem;">
                                <?php
                                $st = $h['status'];
                                $badgeColor = ($st === 'approved') ? '#dcfce7;color:#166534' : '#fee2e2;color:#991b1b';
                                ?>
                                <span style="background:<?= $badgeColor ?>;padding:.2rem .6rem;border-radius:4px;font-size:0.8rem;font-weight:700">
                                    <?= strtoupper($st) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

    </div>
    <!-- refresh the page every 5 seconds to see new items automatically -->
    <script>
        setTimeout(() => window.location.reload(), 5000);
    </script>
</body>

</html>