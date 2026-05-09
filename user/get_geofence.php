<?php
// user/get_geofence.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'Unauthorized']);
    exit;
}
$user_id = (int)$_SESSION['user_id'];

require_once __DIR__ . '/../db.php';

$gf = db_fetch_one("SELECT * FROM geofences WHERE user_id = :uid AND active = 1 ORDER BY id DESC LIMIT 1", [':uid'=>$user_id]);
if (!$gf) {
    echo json_encode(['success'=>true,'geofence'=>null]);
    exit;
}

// Return geofence fields as-is (polygon_json is JSON string)
echo json_encode(['success'=>true,'geofence'=>$gf]);
