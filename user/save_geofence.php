<?php
// user/save_geofence.php
session_start();
header('Content-Type: application/json');

// require login
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
$user_id = (int) $_SESSION['user_id'];

require_once __DIR__ . '/../db.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data || empty($data['type'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid payload']);
    exit;
}

$type = $data['type'];

try {
    if ($type === 'circle') {
        if (!isset($data['center_lat'], $data['center_lng'], $data['radius_m'])) {
            throw new Exception('Missing circle parameters');
        }
        $center_lat = (float) $data['center_lat'];
        $center_lng = (float) $data['center_lng'];
        $radius_m = (int) $data['radius_m'];

        // upsert: if user already has a geofence, update it; otherwise insert
        $existing = db_fetch_one("SELECT id FROM geofences WHERE user_id = :uid LIMIT 1", [':uid' => $user_id]);
        if ($existing) {
            db_execute(
                "UPDATE geofences SET type='circle', center_lat=:cl, center_lng=:cg, radius_m=:r, polygon_json=NULL, active=1, updated_at=NOW() WHERE id=:id",
                [':cl' => $center_lat, ':cg' => $center_lng, ':r' => $radius_m, ':id' => $existing['id']]
            );
        } else {
            db_execute(
                "INSERT INTO geofences (user_id, type, center_lat, center_lng, radius_m, active) VALUES (:uid,'circle',:cl,:cg,:r,1)",
                [':uid' => $user_id, ':cl' => $center_lat, ':cg' => $center_lng, ':r' => $radius_m]
            );
        }

    } elseif ($type === 'polygon') {
        if (empty($data['polygon']) || !is_array($data['polygon'])) {
            throw new Exception('Invalid polygon');
        }
        // sanitize points to array of {lat,lng} floats
        $poly = array_map(function ($pt) {
            return ['lat' => (float) $pt['lat'], 'lng' => (float) $pt['lng']];
        }, $data['polygon']);

        $poly_json = json_encode($poly);

        $existing = db_fetch_one("SELECT id FROM geofences WHERE user_id = :uid LIMIT 1", [':uid' => $user_id]);
        if ($existing) {
            db_execute(
                "UPDATE geofences SET type='polygon', polygon_json=:pj, center_lat=NULL, center_lng=NULL, radius_m=NULL, active=1, updated_at=NOW() WHERE id=:id",
                [':pj' => $poly_json, ':id' => $existing['id']]
            );
        } else {
            db_execute(
                "INSERT INTO geofences (user_id, type, polygon_json, active) VALUES (:uid,'polygon',:pj,1)",
                [':uid' => $user_id, ':pj' => $poly_json]
            );
        }

    } else {
        throw new Exception('Unknown type');
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // in prod log error; here we return message for debugging
    error_log("save_geofence error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
