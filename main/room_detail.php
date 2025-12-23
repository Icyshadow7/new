<?php
session_start();
include "db.php";

$roomId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$room = null;

if ($roomId > 0) {
    $stmt = $conn->prepare("SELECT id, room_name, location, phone, price, image FROM rooms WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $roomId);
    $stmt->execute();
    $room = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

function safe($v) {
    return htmlspecialchars($v ?? "", ENT_QUOTES, "UTF-8");
}
function mapEmbedUrl($location) {
    $q = trim($location ?? '');
    if ($q === '') return '';
    return "https://www.google.com/maps?q=" . rawurlencode($q) . "&output=embed";
}

$mapUrl = $room ? mapEmbedUrl($room['location'] ?? '') : '';

/* Decide image path (uploads first; fallback to images; else default) */
$imgPath = "";
if ($room) {
    $img = $room["image"] ?? "";
    $uploads = "uploads/" . $img;
    $images  = "images/" . $img;

    if ($img && file_exists($uploads)) $imgPath = $uploads;
    elseif ($img && file_exists($images)) $imgPath = $images;
    else $imgPath = "images/default.jpg"; // create this file
}

$initial = strtoupper(substr($_SESSION['fullname'] ?? 'U', 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>StayNexa</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
:root{
  --brand:#e53900;
  --brand2:#ff5a1f;
  --ink:#0b1220;
  --muted:#6b7280;
  --card: rgba(255,255,255,.86);
  --border: rgba(17,24,39,.10);
  --shadow: 0 24px 70px rgba(0,0,0,.16);
  --radius: 18px;
}

*{box-sizing:border-box}
body{
  margin:0;
  font-family:"Inter",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
  color:var(--ink);
  min-height:100vh;
  background:
    radial-gradient(1200px 600px at 10% 10%, rgba(229,57,0,.20), transparent 55%),
    radial-gradient(900px 500px at 95% 25%, rgba(255,90,31,.18), transparent 55%),
    linear-gradient(180deg, #f7f7fb, #eef1f8);
}

/* Navbar */
.navbar{
  position:sticky; top:0; z-index:50;
  display:flex; justify-content:space-between; align-items:center;
  padding:14px 48px;
  background:  #333;
  backdrop-filter: blur(14px);
  border-bottom: 1px solid rgba(255,255,255,.08);
}

.logo{
  display:flex; align-items:center; gap:10px;
  font-weight:800; letter-spacing:.3px; color:#fff;
}
.logo-badge{
  width:40px;height:40px;border-radius:14px;
  background: linear-gradient(135deg, var(--brand), var(--brand2));
  box-shadow: 0 12px 30px rgba(229,57,0,.22);
}
.logo a{color:#fff;text-decoration:none;font-size:18px}

.nav-links{
  display:flex; list-style:none; gap:14px; padding:0; margin:0;
}
.nav-links a{
  color:rgba(255,255,255,.92);
  text-decoration:none;
  font-weight:600;
  font-size:14px;
  padding:10px 12px;
  border-radius:10px;
  transition: background .15s ease;
}
.nav-links a:hover{ background: rgba(255,255,255,.10); }

.nav-user{display:flex; align-items:center; gap:12px;}
.avatar{
  width:42px;height:42px;border-radius:50%;
  background: linear-gradient(135deg,var(--brand),var(--brand2));
  color:#fff; display:grid; place-items:center;
  font-weight:900; box-shadow:0 10px 24px rgba(0,0,0,.25);
}
.user-link{ text-decoration:none; display:inline-flex; }
.login-btn{
  background: linear-gradient(135deg,var(--brand),var(--brand2));
  color:#fff;
  text-decoration:none;
  font-weight:800;
  padding:10px 16px;
  border-radius:12px;
  box-shadow:0 14px 28px rgba(229,57,0,.18);
}

/* Page layout */
.wrap{
  width:min(1100px, 92%);
  margin: 28px auto 60px;
}

.breadcrumb{
  display:flex; justify-content:space-between; align-items:center;
  gap:12px;
  margin-bottom: 16px;
}
.breadcrumb .left{
  display:flex; align-items:center; gap:10px;
  color: var(--muted);
  font-weight:700;
  font-size:13px;
}
.pill{
  display:inline-flex; align-items:center; gap:8px;
  padding:10px 14px;
  border-radius:999px;
  background: rgba(255,255,255,.70);
  border: 1px solid rgba(17,24,39,.06);
  box-shadow: 0 10px 30px rgba(0,0,0,.06);
}
.pill b{ color: var(--ink); }

.card{
  overflow:hidden;
  border-radius: 22px;
  background: var(--card);
  border: 1px solid var(--border);
  box-shadow: var(--shadow);
}

/* Top image header */
.hero{
  position:relative;
  height: 360px;
  background: #ddd;
}
.hero img{
  width:100%;
  height:100%;
  object-fit:cover;
  display:block;
}
.hero::after{
  content:"";
  position:absolute; inset:0;
  background:
    linear-gradient(180deg, rgba(0,0,0,.10), rgba(0,0,0,.42));
}
.hero-content{
  position:absolute;
  left:22px; right:22px; bottom:18px;
  display:flex; align-items:flex-end; justify-content:space-between;
  gap:16px;
  z-index:2;
}
.titlebox{
  color:#fff;
  max-width: 720px;
}
.titlebox h1{
  margin:0;
  font-size: 28px;
  letter-spacing:-.2px;
}
.titlebox p{
  margin:8px 0 0;
  color: rgba(255,255,255,.88);
  font-weight:600;
  font-size: 14px;
}

.priceTag{
  display:inline-flex; align-items:center; gap:10px;
  background: rgba(255,255,255,.16);
  border: 1px solid rgba(255,255,255,.25);
  backdrop-filter: blur(10px);
  color:#fff;
  padding: 10px 14px;
  border-radius: 14px;
  font-weight:900;
  box-shadow: 0 18px 42px rgba(0,0,0,.18);
}
.priceTag small{
  font-weight:800;
  color: rgba(255,255,255,.88);
}

/* Content grid */
.content{
  padding: 18px 20px 22px;
}
.grid{
  display:grid;
  grid-template-columns: 1.2fr .8fr;
  gap: 18px;
  margin-top: 4px;
}

/* Info block */
.section{
  background: rgba(255,255,255,.75);
  border: 1px solid rgba(17,24,39,.08);
  border-radius: 18px;
  padding: 16px;
  box-shadow: 0 10px 26px rgba(0,0,0,.06);
}
.section h2{
  margin:0 0 12px;
  font-size: 16px;
  letter-spacing:-.2px;
}
.row{
  display:flex; justify-content:space-between; align-items:flex-start;
  padding: 12px 10px;
  gap: 12px;
}
.row + .row{ border-top: 1px dashed rgba(17,24,39,.12); }
.label{
  color: var(--muted);
  font-weight:800;
  font-size: 13px;
}
.value{
  text-align:right;
  font-weight:900;
  color: var(--ink);
  word-break: break-word;
}
.value.mono{
  font-feature-settings: "tnum" 1, "lnum" 1;
}

/* Action panel */
.actions{
  display:grid;
  gap: 12px;
}
.btn{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  gap:10px;
  padding: 12px 14px;
  border-radius: 14px;
  font-weight:900;
  text-decoration:none;
  border: 1px solid rgba(17,24,39,.08);
  transition: transform .15s ease, box-shadow .15s ease, background .15s ease;
}
.btn:active{ transform: translateY(1px); }

.btn-primary{
  color:#fff;
  border: none;
  background: linear-gradient(135deg,var(--brand),var(--brand2));
  box-shadow: 0 16px 34px rgba(229,57,0,.20);
}
.btn-primary:hover{ transform: translateY(-1px); box-shadow: 0 22px 50px rgba(229,57,0,.26); }

.btn-ghost{
  color: var(--ink);
  background: rgba(17,24,39,.06);
}
.btn-ghost:hover{ transform: translateY(-1px); background: rgba(17,24,39,.08); }

.note{
  font-size: 13px;
  color: var(--muted);
  font-weight: 600;
  line-height: 1.6;
}

/* MAP */
.map-wrap{
  border-radius: 18px;
  overflow: hidden;
  border: 1px solid rgba(17,24,39,.10);
  box-shadow: 0 10px 26px rgba(0,0,0,.06);
  background: rgba(255,255,255,.70);
  margin-top: 16px;
}
.map-head{
  padding: 12px 14px;
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap: 10px;
  border-bottom: 1px solid rgba(17,24,39,.08);
}
.map-head h3{
  margin:0;
  font-size: 14px;
  font-weight: 900;
  letter-spacing: -.2px;
}
.map-head a{
  font-size: 13px;
  font-weight: 800;
  color: var(--brand);
  text-decoration:none;
}
.map-head a:hover{ text-decoration: underline; }
.map-iframe{
  width: 100%;
  height: 260px;
  border:0;
  display:block;
}

/* Responsive */
@media (max-width: 900px){
  .navbar{ padding: 12px 16px; }
  .wrap{ width: 94%; }
  .grid{ grid-template-columns: 1fr; }
  .hero{ height: 300px; }
  .hero-content{ flex-direction:column; align-items:flex-start; }
}
@media (max-width: 520px){
  .nav-links{ display:none; }
}
</style>
</head>
<body>

<nav class="navbar">
  <div class="logo">
    <div class="logo-badge"></div>
    <a href="index.php">StayNexa</a>
  </div>

  <ul class="nav-links">
    <li><a href="index.php">Home</a></li>
    <li><a href="room.php">Available Rooms</a></li>
    <li><a href="#foooter">Contact</a></li>
  </ul>

  <div class="nav-user">
    <?php if (!empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
      <a href="dashboard.php" class="user-link" title="Dashboard">
        <div class="avatar"><?php echo safe($initial); ?></div>
      </a>
    <?php else: ?>
      <a class="login-btn" href="login.php">Login</a>
    <?php endif; ?>
  </div>
</nav>

<div class="wrap">

  <div class="breadcrumb">
    <div class="left">
      <span>StayNexa</span>
      <span style="opacity:.6;">/</span>
      <span>Room Details</span>
    </div>

    <div class="pill">
      <span style="color:var(--muted);font-weight:800;">Status</span>
      <b>Available</b>
    </div>
  </div>

  <div class="card">
    <?php if ($room): ?>

      <div class="hero">
        <img src="<?php echo safe($imgPath); ?>" alt="<?php echo safe($room['room_name']); ?>">
        <div class="hero-content">
          <div class="titlebox">
            <h1><?php echo safe($room['room_name']); ?></h1>
            <p><?php echo safe($room['location']); ?> • Call <?php echo safe($room['phone']); ?></p>
          </div>

          <div class="priceTag">
            <small>Per Night</small>
            <span>Rs. <?php echo safe($room['price']); ?></span>
          </div>
        </div>
      </div>

      <div class="content">
        <div class="grid">

          <!-- Left: details -->
          <div>
            <div class="section">
              <h2>Room Information</h2>

              <div class="row">
                <div class="label">Room Name</div>
                <div class="value"><?php echo safe($room['room_name']); ?></div>
              </div>

              <div class="row">
                <div class="label">Location</div>
                <div class="value"><?php echo safe($room['location']); ?></div>
              </div>

              <div class="row">
                <div class="label">Phone</div>
                <div class="value mono"><?php echo safe($room['phone']); ?></div>
              </div>

              <div class="row">
                <div class="label">Price</div>
                <div class="value">Rs. <?php echo safe($room['price']); ?></div>
              </div>

              <div class="row">
                <div class="label">Note</div>
                <div class="value" style="font-weight:700;color:var(--muted);max-width:360px;">
                  Clean rooms, fast confirmation, and quick contact with the owner.
                </div>
              </div>
            </div>

            <!-- MAP (SHOW FOR EVERYONE) -->
            <?php if (!empty($mapUrl)): ?>
              <div class="map-wrap">
                <div class="map-head">
                  <h3>Location Map</h3>
                  <a href="https://www.google.com/maps/search/?api=1&query=<?php echo rawurlencode($room['location']); ?>" target="_blank">
                    Open in Google Maps
                  </a>
                </div>

                <iframe
                  class="map-iframe"
                  loading="lazy"
                  referrerpolicy="no-referrer-when-downgrade"
                  src="<?php echo safe($mapUrl); ?>">
                </iframe>
              </div>
            <?php endif; ?>
          </div>

          <!-- Right: actions -->
          <div class="actions">
            <a href="room.php" class="btn btn-ghost">Back to Rooms</a>

            <?php if (!empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
              <a href="book_room.php?id=<?php echo (int)$room['id']; ?>" class="btn btn-primary">Book Now</a>
              <div class="section">
                <h2>Tips</h2>
                <p class="note" style="margin:0;">
                  Keep your profile updated (phone/address/photo) for faster booking and better communication.
                </p>
              </div>
            <?php else: ?>
              <a href="login.php" class="btn btn-primary">Login to Book</a>
              <div class="section">
                <h2>Note</h2>
                <p class="note" style="margin:0;">
                  You need to log in before booking a room.
                </p>
              </div>
            <?php endif; ?>

          </div>

        </div>
      </div>

    <?php else: ?>
      <div style="padding:22px;">
        <p style="margin:0;font-weight:800;">Room not found.</p>
        <p style="margin:8px 0 0;color:var(--muted);font-weight:600;">
          The room may have been deleted or the ID is invalid.
        </p>
        <div style="margin-top:14px;">
          <a href="room.php" class="btn btn-ghost">Back to Rooms</a>
        </div>
      </div>
    <?php endif; ?>
  </div>

</div>

</body>
</html>
