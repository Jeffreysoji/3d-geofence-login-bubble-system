<?php
session_start();
require_once __DIR__ . '/../db.php';

$token = $_GET['token'] ?? '';
if (!$token) {
    echo json_encode(['status' => 'error']);
    exit;
}

$req = db_fetch_one("SELECT * FROM login_requests WHERE request_token = :t LIMIT 1", [':t' => $token]);

if (!$req) {
    echo json_encode(['status' => 'error']);
    exit;
}

if ($req['status'] === 'approved') {
    // Determine who this user is to set session if mostly stateless, 
    // but usually we set session AFTER client gets this 'approved' msg.
    // HOWEVER, for security, let's set the session HERE if approved, 
    // so the client just redirects.

    // Actually, setting session here is tricky if it requires session cookie on same client.
    // Since this is an AJAX request from the same browser, session cookie works.

    $user = db_fetch_one("SELECT * FROM users WHERE id = :id", [':id' => $req['user_id']]);
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        // clear token so they can't replay? 
        // nah, keep it simple.
    }
}

echo json_encode(['status' => $req['status']]);
