<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/../db.php';

// Fetch some quick stats
$userCount = db_fetch_one("SELECT count(*) as c FROM users")['c'] ?? 0;
$geoCount = db_fetch_one("SELECT count(*) as c FROM geofences")['c'] ?? 0;
$logs = db_query("SELECT * FROM auth_logs ORDER BY created_at DESC LIMIT 20");
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>KKBank — Admin Panel</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="/kkbank/style.css">
    <style>
        body {
            background: #f1f5f9;
        }

        .admin-header {
            background: #fff;
            padding: 1rem 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .brand {
            font-size: 1.2rem;
            font-weight: bold;
            color: #0b3a6b;
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: #fff;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .stat-val {
            font-size: 2rem;
            font-weight: 700;
            color: #0b3a6b;
        }

        .stat-label {
            color: #64748b;
            font-size: 0.9rem;
            margin-top: .2rem;
        }

        .table-card {
            background: #fff;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        th {
            text-align: left;
            background: #f8fafc;
            padding: .8rem;
            color: #475569;
            font-weight: 600;
            font-size: .85rem;
        }

        td {
            padding: .8rem;
            border-bottom: 1px solid #e2e8f0;
            font-size: .9rem;
            color: #334155;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .tag {
            display: inline-block;
            padding: .2rem .5rem;
            border-radius: 4px;
            font-size: .75rem;
            font-weight: 600;
        }

        .tag-allow {
            background: #dcfce7;
            color: #166534;
        }

        .tag-block {
            background: #fee2e2;
            color: #991b1b;
        }

        .tag-challenge {
            background: #fff7ed;
            color: #9a3412;
        }

        .logout {
            color: #ef4444;
            text-decoration: none;
            font-weight: 600;
            font-size: .9rem;
        }
    </style>
</head>

<body>
    <header class="admin-header">
        <div class="brand">KKBank Admin</div>
        <div>
            <a href="alerts.php" style="margin-right:1rem;color:#b91c1c;font-weight:700;text-decoration:none">🔔
                Validations</a>
            <span style="margin-right:1rem;color:#475569">Hello,
                <?= htmlspecialchars($_SESSION['admin_name']) ?>
            </span>
            <a href="logout.php" class="logout">Logout</a>
        </div>
    </header>

    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-val">
                    <?= $userCount ?>
                </div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-val">
                    <?= $geoCount ?>
                </div>
                <div class="stat-label">Active Geofences</div>
            </div>
            <div class="stat-card">
                <div class="stat-val">
                    <?= count($logs) ?>
                </div>
                <div class="stat-label">Recent Logs</div>
            </div>
        </div>

        <div class="table-card">
            <h3 style="margin:0 0 .5rem;">Recent Auth Activity</h3>
            <div style="overflow-x:auto">
                <table>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>User ID</th>
                            <th>Email</th>
                            <th>IP</th>
                            <th>Loc Status / Result</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td>
                                    <?= $log['created_at'] ?>
                                </td>
                                <td>
                                    <?= $log['user_id'] ? $log['user_id'] : '-' ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($log['email_attempted']) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($log['ip']) ?>
                                </td>
                                <td>
                                    <?php
                                    $cls = 'tag-challenge';
                                    if (strpos($log['action'], 'allow') !== false)
                                        $cls = 'tag-allow';
                                    if (strpos($log['action'], 'block') !== false)
                                        $cls = 'tag-block';

                                    $isIn = $log['inside_geofence'];
                                    $inTxt = is_null($isIn) ? '-' : ($isIn ? 'INSIDE' : 'OUTSIDE');
                                    $inCls = ($inTxt === 'INSIDE') ? 'tag-allow' : 'tag-block';
                                    ?>
                                    <span class="tag <?= $inCls ?>"><?= $inTxt ?></span>
                                    <span class="tag <?= $cls ?>">
                                        <?= htmlspecialchars($log['action']) ?>
                                    </span>
                                </td>
                                <td><small>
                                        <?= htmlspecialchars($log['details']) ?>
                                    </small></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="6" style="text-align:center;padding:2rem">No logs yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script>
        // Simple client-side table sort
        document.querySelectorAll('th').forEach(th => th.addEventListener('click', (() => {
            const table = th.closest('table');
            const tbody = table.querySelector('tbody');
            Array.from(tbody.querySelectorAll('tr'))
                .sort(comparer(Array.from(th.parentNode.children).indexOf(th), this.asc = !this.asc))
                .forEach(tr => tbody.appendChild(tr));
        })));
        const comparer = (idx, asc) => (a, b) => ((v1, v2) =>
            v1 !== '' && v2 !== '' && !isNaN(v1) && !isNaN(v2) ? v1 - v2 : v1.toString().localeCompare(v2)
        )(getCellValue(asc ? a : b, idx), getCellValue(asc ? b : a, idx));
        const getCellValue = (tr, idx) => tr.children[idx].innerText || tr.children[idx].textContent;
    </script>
</body>

</html>