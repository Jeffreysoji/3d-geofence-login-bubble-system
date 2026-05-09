<?php
// process_login.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../db.php';

// Haversine & point-in-polygon helpers (used to check geofence)
function haversine_meters($lat1, $lon1, $lat2, $lon2)
{
    $R = 6371000.0;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) +
        cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
        sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $R * $c;
}
function point_in_polygon($point, $polygon)
{
    $x = $point[0];
    $y = $point[1];
    $inside = false;
    $j = count($polygon) - 1;
    for ($i = 0; $i < count($polygon); $i++) {
        $xi = $polygon[$i]['lat'];
        $yi = $polygon[$i]['lng'];
        $xj = $polygon[$j]['lat'];
        $yj = $polygon[$j]['lng'];
        $intersect = (($yi > $y) != ($yj > $y)) && ($x < ($xj - $xi) * ($y - $yi) / (($yj - $yi) + 0.0) + $xi);
        if ($intersect)
            $inside = !$inside;
        $j = $i;
    }
    return $inside;
}

// Read incoming POST (login form should use name="email" and name="password")
$email = isset($_POST['email']) ? trim($_POST['email']) : null;
$password = $_POST['password'] ?? '';
$lat = isset($_POST['lat']) && $_POST['lat'] !== '' ? floatval($_POST['lat']) : null;
$lng = isset($_POST['lng']) && $_POST['lng'] !== '' ? floatval($_POST['lng']) : null;

if (!$email || !$password) {
    $_SESSION['login_error'] = 'Please provide email and password.';
    header('Location: /kkbank/user/login.php');
    exit;
}

// lookup user
$user = db_fetch_one("SELECT * FROM users WHERE email = :email LIMIT 1", [':email' => $email]);
if (!$user) {
    $_SESSION['login_error'] = 'Invalid credentials.';
    log_auth_attempt(null, $email, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '', $lat, $lng, null, 'block', 'no_user');
    header('Location: /kkbank/user/login.php');
    exit;
}

// verify password
if (!password_verify($password, $user['password_hash'])) {
    $_SESSION['login_error'] = 'Invalid credentials.';
    log_auth_attempt((int) $user['id'], $email, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '', $lat, $lng, null, 'block', 'wrong_password');
    header('Location: /kkbank/user/login.php');
    exit;
}

// fetch user's active geofence (if any)
$gf = db_fetch_one("SELECT * FROM geofences WHERE user_id = :uid AND active = 1 ORDER BY id DESC LIMIT 1", [':uid' => $user['id']]);

$inside = true;
if ($gf) {
    if ($lat === null || $lng === null) {
        // no geolocation provided -> treat as outside (policy choice)
        $inside = false;
    } else {
        if ($gf['type'] === 'circle') {
            $distance = haversine_meters($lat, $lng, floatval($gf['center_lat']), floatval($gf['center_lng']));
            $inside = ($distance <= intval($gf['radius_m']));
        } else {
            $poly = json_decode($gf['polygon_json'], true);
            $inside = (is_array($poly) && count($poly) >= 3) ? point_in_polygon([$lat, $lng], $poly) : false;
        }
    }
}

// Log attempt
log_auth_attempt((int) $user['id'], $email, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '', $lat, $lng, $inside, $inside ? 'allow' : 'challenge', 'login_attempt');

require_once __DIR__ . '/send_email.php';

// If inside geofence -> OTP Challenge (Enhanced Security)
if ($inside) {
    // 1. Generate OTP
    $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $otpHash = password_hash($otp, PASSWORD_DEFAULT);

    // 2. Send OTP via Email
    $subject = "KKBank Login OTP";
    $body = "Hello " . htmlspecialchars($user['email']) . ",\n\n";
    $body .= "You are logging in from a TRUSTED location (Inside Geofence).\n";
    $body .= "Your One-Time Password (OTP) is: $otp\n\n";
    $body .= "If this was not you, please contact support immediately.";
    send_email_alert($user['email'], $subject, $body);

    // 3. Store in session for verification
    session_regenerate_id(true);
    $_SESSION['challenge_user_id'] = (int) $user['id'];
    $_SESSION['challenge_otp_hash'] = $otpHash;
    $_SESSION['challenge_expires'] = time() + 300; // 5 mins
    $_SESSION['challenge_debug_otp'] = $otp; // Remove this line in production!
    $_SESSION['geofence_status'] = 'INSIDE'; // Will be preserved after verification

    // 4. Redirect to Verify Page
    header('Location: /kkbank/user/verify_challenge.php');
    exit;
}

// OUTSIDE geofence -> Alert Admin & Wait
// 1. Send Security Alert to User
$alertSubject = "SECURITY ALERT: Unusual Login Detected";
$alertBody = "Hello " . htmlspecialchars($user['email']) . ",\n\n";
$alertBody .= "We detected a login attempt from OUTSIDE your secure geofence via " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown IP') . ".\n";
$alertBody .= "This request has been blocked and flagged for Admin Approval.\n\n";
$alertBody .= "If this was you, please wait for approval. If not, change your password immediately.";
send_email_alert($user['email'], $alertSubject, $alertBody);

// 2. Generate request token (existing logic)
$token = bin2hex(random_bytes(32));

// 3. Insert Request
db_execute("INSERT INTO login_requests (user_id, request_token, ip, device, status) VALUES (:uid, :token, :ip, :ua, 'pending')", [
    ':uid' => $user['id'],
    ':token' => $token,
    ':ip' => $_SERVER['REMOTE_ADDR'] ?? '',
    ':ua' => $_SERVER['HTTP_USER_AGENT'] ?? ''
]);

// 4. Set session token for pooling
$_SESSION['pending_token'] = $token;
$_SESSION['geofence_status'] = 'OUTSIDE';

// 5. Log the block
log_auth_attempt((int) $user['id'], $email, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '', $lat, $lng, $inside, 'block-pending-approval', 'Outside Geofence - Waiting Admin');

// 6. Redirect to wait page
header('Location: /kkbank/user/waiting_approval.php');
exit;
