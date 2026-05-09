<?php
// user/dashboard.php
// Simple, reliable dashboard that requires login and shows geofence map.
// Replace YOUR_GOOGLE_MAPS_API_KEY with your real key.

ini_set('display_errors',1);
error_reporting(E_ALL);
session_start();
require_once __DIR__ . '/../db.php';

// require login
if (!isset($_SESSION['user_id'])) {
    // not logged in — redirect to login
    header('Location: /kkbank/user/login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$userEmail = $_SESSION['user_email'] ?? '(unknown)';

// fetch geofence summary (for display)
$gf = db_fetch_one("SELECT * FROM geofences WHERE user_id = :uid AND active = 1 ORDER BY id DESC LIMIT 1", [':uid'=>$userId]);

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>KKBank — Dashboard</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="/kkbank/style.css">
  <style>
    body { background:#f4f7fb; font-family:Inter, system-ui, -apple-system,Segoe UI, Roboto, Arial; color:#0b2a4a; }
    .container { width:min(1100px,92%); margin:1.6rem auto; }
    .top { display:flex; justify-content:space-between; align-items:center; gap:1rem; margin-bottom:1rem; }
    .card { background:#fff; padding:1rem; border-radius:10px; box-shadow:0 12px 30px rgba(12,34,78,0.06); }
    #map { height:520px; border-radius:8px; overflow:hidden; }
    .controls { display:flex; gap:.6rem; margin-top:.8rem; }
    .btn { padding:.55rem .85rem; border-radius:8px; border:none; cursor:pointer; font-weight:600; }
    .btn-primary { background:linear-gradient(90deg,#00b4ff,#0066ff); color:#fff; }
    .btn-ghost { background:transparent; border:1px solid #e6eefc; color:#0b3a6b; }
    .small { font-size:.95rem; color:#475569; }
    @media(max-width:980px){ #map{height:360px} }
  </style>
</head>
<body>
  <div class="container">
    <div class="top">
      <div>
        <h2 style="margin:0">Welcome, <?= htmlspecialchars($userEmail) ?></h2>
        <div class="small">User ID: <?= $userId ?></div>
      </div>
      <div style="display:flex; gap:.6rem; align-items:center;">
        <a class="btn btn-ghost" href="/kkbank/user/geofence.php">Edit Geofence</a>
        <a class="btn btn-ghost" href="/kkbank/user/logout.php">Logout</a>
      </div>
    </div>

    <div class="card">
      <h3 style="margin-top:0">GeoFence Map</h3>
      <div id="map"></div>

      <div class="controls">
        <button id="btn-check" class="btn btn-primary">Check My Location</button>
        <button id="btn-reload" class="btn btn-ghost">Reload Geofence</button>
        <div id="result" class="small" style="align-self:center;"></div>
      </div>
    </div>

    <div class="card" style="margin-top:1rem;">
      <h4 style="margin:0 0 .6rem 0">Geofence summary</h4>
      <?php if ($gf): ?>
        <div><strong>Type:</strong> <?= htmlspecialchars($gf['type']) ?></div>
        <?php if ($gf['type']==='circle'): ?>
          <div><strong>Center:</strong> <?= htmlspecialchars($gf['center_lat']).', '.htmlspecialchars($gf['center_lng']) ?></div>
          <div><strong>Radius:</strong> <?= htmlspecialchars($gf['radius_m']) ?> m</div>
        <?php else: ?>
          <div><strong>Polygon points:</strong></div>
          <pre style="background:#fbfdff;padding:.6rem;border-radius:8px;"><?= htmlspecialchars($gf['polygon_json']) ?></pre>
        <?php endif; ?>
      <?php else: ?>
        <div class="small">No geofence set. <a href="/kkbank/user/geofence.php">Create one</a>.</div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Google Maps API: include geometry library -->
  <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCLmBE8UrshXZUzkLPpqHb5TQL4li_ve_0&libraries=geometry&callback=initMap" async defer></script>

  <script>
    let map, savedShape=null, savedType=null;

    function initMap(){
      const fallback = { lat: 12.9716, lng: 77.5946 };
      map = new google.maps.Map(document.getElementById('map'), { center: fallback, zoom: 13 });

      // load geofence
      loadGeofence();
      document.getElementById('btn-check').addEventListener('click', checkMyLocation);
      document.getElementById('btn-reload').addEventListener('click', loadGeofence);
    }

    async function loadGeofence(){
      document.getElementById('result').textContent = 'Loading…';
      try {
        const res = await fetch('/kkbank/user/get_geofence.php', { credentials: 'same-origin' });
        const data = await res.json();
        if (!data.success || !data.geofence) {
          document.getElementById('result').textContent = 'No saved geofence.';
          if (savedShape) { savedShape.setMap(null); savedShape=null; savedType=null; }
          return;
        }
        const g = data.geofence;
        if (savedShape) { savedShape.setMap(null); savedShape=null; savedType=null; }

        if (g.type === 'circle') {
          const center = { lat: parseFloat(g.center_lat), lng: parseFloat(g.center_lng) };
          savedShape = new google.maps.Circle({
            map, center, radius: parseFloat(g.radius_m), editable:false,
            fillColor:'#4fd1ff', fillOpacity:.18, strokeColor:'#4fd1ff', strokeWeight:2
          });
          savedType = 'circle';
          map.fitBounds(savedShape.getBounds());
          document.getElementById('result').textContent = `Circle radius ${g.radius_m} m`;
        } else {
          const pts = JSON.parse(g.polygon_json);
          const path = pts.map(p=>({lat:parseFloat(p.lat), lng:parseFloat(p.lng)}));
          savedShape = new google.maps.Polygon({ map, paths: path, editable:false, fillColor:'#8a5bff', fillOpacity:.18, strokeColor:'#8a5bff' });
          savedType = 'polygon';
          const bounds = new google.maps.LatLngBounds();
          path.forEach(pt=>bounds.extend(pt));
          map.fitBounds(bounds);
          document.getElementById('result').textContent = `Polygon ${path.length} points`;
        }
      } catch(err){
        console.error(err);
        document.getElementById('result').textContent = 'Failed to load geofence';
      }
    }

    function checkMyLocation(){
      document.getElementById('result').textContent = 'Getting location…';
      if (!navigator.geolocation) { document.getElementById('result').textContent='Geolocation not supported'; return; }
      navigator.geolocation.getCurrentPosition(pos=>{
        const lat=pos.coords.latitude, lng=pos.coords.longitude;
        const userLatLng = new google.maps.LatLng(lat,lng);
        new google.maps.Marker({position:userLatLng, map});
        if (!savedShape) { document.getElementById('result').textContent = `No geofence saved. Your: ${lat.toFixed(5)}, ${lng.toFixed(5)}`; return; }
        if (savedType === 'circle') {
          const center = savedShape.getCenter();
          const d = google.maps.geometry.spherical.computeDistanceBetween(userLatLng, center);
          const inside = d <= savedShape.getRadius();
          document.getElementById('result').innerHTML = inside ? `<span style="color:#065f46;font-weight:700">INSIDE</span> — ${Math.round(d)} m` : `<span style="color:#b42318;font-weight:700">OUTSIDE</span> — ${Math.round(d)} m`;
          const b = new google.maps.LatLngBounds(); b.extend(center); b.extend(userLatLng); map.fitBounds(b);
        } else {
          const inside = google.maps.geometry.poly.containsLocation(userLatLng, savedShape);
          document.getElementById('result').innerHTML = inside ? '<span style="color:#065f46;font-weight:700">INSIDE</span>' : '<span style="color:#b42318;font-weight:700">OUTSIDE</span>';
          const b = savedShape.getBounds ? savedShape.getBounds() : new google.maps.LatLngBounds(); b.extend(userLatLng); map.fitBounds(b);
        }
      }, err=>{
        console.warn(err);
        document.getElementById('result').textContent = 'Could not get location (permission or timeout).';
      }, { enableHighAccuracy:true, timeout:10000 });
    }
  </script>
</body>
</html>
