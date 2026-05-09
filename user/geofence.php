<?php
// C:\xampp\htdocs\kkbank\user\geofence.php
// Single-file geofence editor with proper session handling and re-auth check.

// start session only if none active
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// include DB and helper functions
require_once __DIR__ . '/../db.php';

// require login
if (!isset($_SESSION['user_id'])) {
  header('Location: /kkbank/user/login.php');
  exit;
}

// ensure the redirect logic stays intact but doesn't trap the user if they want to leave? No, if they are on this page, they are authenticated. The issue is probably just navigation.
// require recent geofence re-auth (5 minutes)
$max_age = 60 * 5; // seconds
if (!isset($_SESSION['geofence_auth_ts']) || (time() - (int) $_SESSION['geofence_auth_ts'] > $max_age)) {
  // clear stale flag and redirect to re-auth page
  unset($_SESSION['geofence_auth_ts']);
  header('Location: /kkbank/user/geofence_auth.php');
  exit;
}

// get user info (optional, for display)
$userId = (int) $_SESSION['user_id'];
$userEmail = $_SESSION['user_email'] ?? '(you)';

?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title>KKBank — Geofence Editor</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="/kkbank/style.css">
  <style>
    body {
      background: #f4f7fb;
      color: #0b2a4a;
      font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, Arial;
    }

    .container {
      width: min(1100px, 94%);
      margin: 1.6rem auto;
    }

    h2 {
      margin: .4rem 0 0.2rem;
    }

    .lead {
      color: #556;
      margin-bottom: 1rem;
    }

    .map-wrap {
      width: 100%;
      height: 520px;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 12px 30px rgba(10, 20, 50, 0.06);
      background: #e6f7ff;
    }

    .controls {
      margin-top: 1rem;
      display: flex;
      gap: 0.6rem;
      align-items: center;
      flex-wrap: wrap;
    }

    .small {
      font-size: 0.95rem;
      color: #475569;
    }

    .btn {
      padding: .6rem 1rem;
      border-radius: 8px;
      cursor: pointer;
      border: none;
      font-weight: 600;
    }

    .btn-primary {
      background: linear-gradient(90deg, #00b4ff, #0066ff);
      color: #fff;
    }

    .btn-ghost {
      background: transparent;
      border: 1px solid #cbd5e1;
      color: #0b3a6b;
    }

    #status {
      margin-left: .6rem;
      color: #0b3a6b;
      font-weight: 700;
    }

    pre {
      background: #fbfdff;
      padding: .6rem;
      border-radius: 8px;
      overflow: auto;
    }
  </style>
</head>

<body>
  <div class="container">
    <header>
      <div style="display:flex;justify-content:space-between;align-items:center;">
        <h2>Geofence Editor — KKBank</h2>
        <div style="display:flex;gap:1rem;align-items:center">
          <?php
          $status = $_SESSION['geofence_status'] ?? 'UNKNOWN';
          $color = ($status === 'INSIDE') ? '#dcfce7;color:#166534' : '#fee2e2;color:#991b1b';
          ?>
          <div style="padding:.4rem .8rem;background:<?= $color ?>;border-radius:6px;font-weight:700;font-size:0.85rem">
            Status: <?= htmlspecialchars($status) ?>
          </div>
          <a href="/kkbank/user/home.php"
            style="text-decoration:none;color:#0b3a6b;font-weight:600;padding:.5rem 1rem;background:#e6f7ff;border-radius:8px;">&larr;
            Back to Home</a>
        </div>
      </div>
      <p class="lead">Draw a circle or polygon to set your secure login bubble. Save when ready.</p>
    </header>

    <main>
      <div id="map" class="map-wrap" aria-label="Map for drawing geofence"></div>

      <div class="controls">
        <button id="btn-save" class="btn btn-primary">Save Geofence</button>
        <button id="btn-clear" class="btn btn-ghost">Clear Draft</button>
        <button id="btn-load" class="btn btn-ghost">Reload Saved</button>
        <div id="status" role="status" aria-live="polite">Loading…</div>
      </div>

      <p class="small" style="margin-top:.8rem">
        Important: Browser will ask for location permission to help center the map and for testing if you're inside the
        bubble.
      </p>

      <section style="margin-top:1.1rem" aria-hidden="false">
        <h4 style="margin:0 .0 .6rem 0">Saved geofence (debug)</h4>
        <div id="saved-info" class="small">Loading saved geofence details…</div>
      </section>
    </main>
  </div>

  <script>
    // expose user id for debugging if needed
    const userId = <?= json_encode($userId) ?>;
  </script>

  <!-- Replace YOUR_GOOGLE_MAPS_API_KEY below with your actual Google Maps API key -->
  <!-- Get one at: https://console.cloud.google.com/ -->
  <script
    src="https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_MAPS_API_KEY&libraries=drawing,geometry&callback=initMap"
    async defer></script>

  <script>
    let map, drawingManager, currentShape = null, currentType = null;

    function initMap() {
      const fallback = { lat: 12.9716, lng: 77.5946 }; // fallback center
      map = new google.maps.Map(document.getElementById('map'), {
        center: fallback,
        zoom: 13,
        mapTypeId: 'roadmap',
        gestureHandling: 'greedy'
      });

      // Attempt to center map on user's device location (does not block)
      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(pos => {
          map.setCenter({ lat: pos.coords.latitude, lng: pos.coords.longitude });
        }, () => { /* ignore errors */ }, { timeout: 6000 });
      }

      // Drawing manager (circle & polygon)
      drawingManager = new google.maps.drawing.DrawingManager({
        drawingMode: null,
        drawingControl: true,
        drawingControlOptions: {
          position: google.maps.ControlPosition.TOP_CENTER,
          drawingModes: ['circle', 'polygon']
        },
        circleOptions: {
          fillColor: '#4fd1ff',
          fillOpacity: 0.2,
          strokeColor: '#4fd1ff',
          strokeWeight: 2,
          clickable: true,
          editable: true,
          zIndex: 1
        },
        polygonOptions: {
          fillColor: '#8a5bff',
          fillOpacity: 0.18,
          strokeColor: '#8a5bff',
          strokeWeight: 2,
          clickable: true,
          editable: true,
          zIndex: 1
        }
      });

      drawingManager.setMap(map);

      // When user finishes a drawing overlay
      google.maps.event.addListener(drawingManager, 'overlaycomplete', (e) => {
        // Remove existing draft
        if (currentShape) currentShape.setMap(null);

        currentShape = e.overlay;
        currentType = e.type; // 'circle' or 'polygon'

        // Make editable and listen for edits to update status
        if (currentType === 'circle') {
          currentShape.setEditable(true);
          currentShape.addListener('radius_changed', () => updateStatus('Draft updated'));
          currentShape.addListener('center_changed', () => updateStatus('Draft updated'));
        } else if (currentType === 'polygon') {
          currentShape.setEditable(true);
          const path = currentShape.getPath();
          path.addListener('set_at', () => updateStatus('Draft updated'));
          path.addListener('insert_at', () => updateStatus('Draft updated'));
          path.addListener('remove_at', () => updateStatus('Draft updated'));
        }

        updateStatus('Shape drawn. Click Save to store.');
        drawingManager.setDrawingMode(null);
      });

      // Wire up controls
      document.getElementById('btn-save').addEventListener('click', saveGeofence);
      document.getElementById('btn-clear').addEventListener('click', clearDraft);
      document.getElementById('btn-load').addEventListener('click', loadSavedGeofence);

      // Load existing saved geofence on open
      loadSavedGeofence();
    }

    function updateStatus(msg) {
      document.getElementById('status').textContent = msg;
    }

    function clearDraft() {
      if (currentShape) {
        currentShape.setMap(null);
        currentShape = null;
        currentType = null;
        updateStatus('Draft cleared.');
      } else {
        updateStatus('No draft present.');
      }
    }

    function extractShapeData() {
      if (!currentShape || !currentType) return null;

      if (currentType === google.maps.drawing.OverlayType.CIRCLE || currentType === 'circle') {
        const center = currentShape.getCenter();
        return {
          type: 'circle',
          center_lat: center.lat(),
          center_lng: center.lng(),
          radius_m: Math.round(currentShape.getRadius())
        };
      } else { // polygon
        const path = currentShape.getPath();
        const pts = [];
        for (let i = 0; i < path.getLength(); i++) {
          const p = path.getAt(i);
          pts.push({ lat: p.lat(), lng: p.lng() });
        }
        return {
          type: 'polygon',
          polygon: pts
        };
      }
    }

    async function saveGeofence() {
      const data = extractShapeData();
      if (!data) {
        updateStatus('No shape to save. Draw a circle or polygon first.');
        return;
      }

      updateStatus('Saving…');

      try {
        const resp = await fetch('/kkbank/user/save_geofence.php', {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(data)
        });
        const json = await resp.json();
        if (json.success) {
          updateStatus('Saved successfully.');
          loadSavedGeofence(); // reload to show editable saved version
        } else {
          updateStatus('Save failed: ' + (json.message || 'unknown'));
        }
      } catch (err) {
        updateStatus('Error saving: ' + err.message);
      }
    }

    async function loadSavedGeofence() {
      updateStatus('Loading saved fence…');
      try {
        const resp = await fetch('/kkbank/user/get_geofence.php?ts=' + Date.now(), { credentials: 'same-origin' });
        if (!resp.ok) throw new Error('Server error');
        const json = await resp.json();
        const savedInfoEl = document.getElementById('saved-info');

        if (!json.success || !json.geofence) {
          savedInfoEl.textContent = 'No saved geofence.';
          updateStatus('No saved geofence.');
          // remove any draft present
          if (currentShape) { currentShape.setMap(null); currentShape = null; currentType = null; }
          return;
        }

        // remove existing shapes
        if (currentShape) {
          currentShape.setMap(null);
          currentShape = null;
          currentType = null;
        }

        const g = json.geofence;
        if (g.type === 'circle') {
          const circ = new google.maps.Circle({
            map: map,
            center: { lat: parseFloat(g.center_lat), lng: parseFloat(g.center_lng) },
            radius: parseFloat(g.radius_m),
            editable: true,
            fillColor: '#4fd1ff',
            fillOpacity: 0.2,
            strokeColor: '#4fd1ff',
            strokeWeight: 2
          });
          currentShape = circ;
          currentType = 'circle';

          // Re-attach listeners for editing
          currentShape.addListener('radius_changed', () => updateStatus('Draft updated (unsaved)'));
          currentShape.addListener('center_changed', () => updateStatus('Draft updated (unsaved)'));

          map.fitBounds(circ.getBounds());
          savedInfoEl.innerHTML = `Type: circle<br>Center: ${g.center_lat}, ${g.center_lng}<br>Radius: ${g.radius_m} m`;
          updateStatus('Loaded saved geofence. Drag handles to edit, then Save.');
        } else if (g.type === 'polygon') {
          const polygonPoints = JSON.parse(g.polygon_json);
          const path = polygonPoints.map(p => ({ lat: parseFloat(p.lat), lng: parseFloat(p.lng) }));
          const poly = new google.maps.Polygon({
            map: map,
            paths: path,
            editable: true,
            fillColor: '#8a5bff',
            fillOpacity: 0.18,
            strokeColor: '#8a5bff',
            strokeWeight: 2
          });
          currentShape = poly;
          currentType = 'polygon';

          // Re-attach listeners for editing
          const p = currentShape.getPath();
          p.addListener('set_at', () => updateStatus('Draft updated (unsaved)'));
          p.addListener('insert_at', () => updateStatus('Draft updated (unsaved)'));
          p.addListener('remove_at', () => updateStatus('Draft updated (unsaved)'));

          const bounds = new google.maps.LatLngBounds();
          path.forEach(pt => bounds.extend(pt));
          map.fitBounds(bounds);
          savedInfoEl.innerHTML = `Type: polygon<br>Points: ${path.length}`;
          updateStatus('Loaded saved geofence. Drag points to edit, then Save.');
        } else {
          savedInfoEl.textContent = 'Unknown geofence type saved.';
          updateStatus('Loaded saved geofence (unknown type).');
        }
      } catch (err) {
        document.getElementById('saved-info').textContent = 'Error loading: ' + err.message;
        updateStatus('Error loading geofence: ' + err.message);
      }
    }
  </script>
</body>

</html>