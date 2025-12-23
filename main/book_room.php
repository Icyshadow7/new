<?php
session_start();
include "db.php";

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

function safe($v) { return htmlspecialchars($v ?? "", ENT_QUOTES, "UTF-8"); }

$userId   = (int)($_SESSION['user_id'] ?? 0);
$fullName = trim($_SESSION['fullname'] ?? '');
$email    = trim($_SESSION['email'] ?? '');

$roomId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$room = null;

if ($roomId > 0) {
    $stmt = $conn->prepare("SELECT id, room_name, location, phone, price, image FROM rooms WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $roomId);
    $stmt->execute();
    $room = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if (!$room) {
    die("Room not found. <a href='room.php'>Back to Rooms</a>");
}

/* Image path (uploads first; fallback to images; else default) */
$imgPath = "images/default.jpg";
$img = $room["image"] ?? "";
if ($img) {
    $uploads = "uploads/" . $img;
    $images  = "images/" . $img;
    if (file_exists($uploads)) $imgPath = $uploads;
    elseif (file_exists($images)) $imgPath = $images;
}

$detailsUrl = "room_details.php?id=" . (int)$room['id'];

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $phone    = trim($_POST["phone"] ?? "");
    $checkIn  = trim($_POST["check_in"] ?? "");
    $checkOut = trim($_POST["check_out"] ?? "");
    $guests   = (int)($_POST["guests"] ?? 1);
    $message  = trim($_POST["message"] ?? "");

    if ($phone === "" || $checkIn === "" || $checkOut === "") {
        $error = "Please fill all required fields.";
    } else {
        $in  = strtotime($checkIn);
        $out = strtotime($checkOut);

        if ($in === false || $out === false) {
            $error = "Invalid date.";
        } elseif ($out <= $in) {
            $error = "Check-out must be after check-in.";
        } elseif ($guests < 1 || $guests > 20) {
            $error = "Guests must be between 1 and 20.";
        } else {

            $stmt = $conn->prepare("
                INSERT INTO bookings (user_id, room_id, phone, check_in, check_out, guests, message, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            $stmt->bind_param("iisssis", $userId, $roomId, $phone, $checkIn, $checkOut, $guests, $message);

           if ($stmt->execute()) {
    $bookingId = $stmt->insert_id;
    header("Location: booking_success.php?booking_id=" . $bookingId);
    exit();
}
 else {
                $error = "Database error: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// Dates for min validation
$today = date("Y-m-d");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>PahunaStay </title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
:root{
  --brand:#e53900;
  --brand2:#ff5a1f;
  --ink:#0b1220;
  --muted:#6b7280;
  --bg1:#f7f7fb;
  --bg2:#eef1f8;
  --card: rgba(255,255,255,.92);
  --border: rgba(17,24,39,.10);
  --shadow: 0 22px 60px rgba(0,0,0,.12);
  --radius: 18px;
}

*{box-sizing:border-box}
body{
  margin:0;
  font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
  color:var(--ink);
  min-height:100vh;
  background:
    radial-gradient(1100px 550px at 8% 10%, rgba(229,57,0,.18), transparent 55%),
    radial-gradient(900px 500px at 95% 25%, rgba(255,90,31,.16), transparent 55%),
    linear-gradient(180deg, var(--bg1), var(--bg2));
}

/* Navbar */
.navbar{
  position:sticky; top:0; z-index:50;
  display:flex; justify-content:space-between; align-items:center;
  padding:14px 48px;
  background: #333;
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
.nav-links{display:flex; list-style:none; gap:14px; padding:0; margin:0;}
.nav-links a{
  color:rgba(255,255,255,.92);
  text-decoration:none; font-weight:600; font-size:14px;
  padding:10px 12px; border-radius:10px;
}
.nav-links a:hover{ background: rgba(255,255,255,.10); }

/* Page */
.wrap{ width:min(1100px, 92%); margin: 26px auto 60px; }
.top{
  display:flex; justify-content:space-between; align-items:flex-end;
  gap: 14px; margin-bottom: 14px;
}
.top h1{ margin:0; font-size:22px; letter-spacing:-.2px; }
.top p{ margin:6px 0 0; color:var(--muted); font-weight:600; }

.pill{
  display:inline-flex; align-items:center; gap:8px;
  padding:10px 14px; border-radius:999px;
  background: rgba(255,255,255,.70);
  border: 1px solid rgba(17,24,39,.06);
  box-shadow: 0 10px 30px rgba(0,0,0,.06);
  font-weight:800;
}
.pill span{ color: var(--muted); }
.pill b{ color: var(--ink); }

.grid{
  display:grid;
  grid-template-columns: 1.1fr .9fr;
  gap: 18px;
}

.card{
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: 22px;
  box-shadow: var(--shadow);
  overflow:hidden;
}

.card-head{
  padding:16px 16px 12px;
  border-bottom: 1px solid rgba(17,24,39,.07);
}
.card-head h2{ margin:0; font-size:16px; letter-spacing:-.2px; }
.card-body{ padding:16px; }

.hero{
  height: 220px;
  position:relative;
  background:#ddd;
}
.hero img{ width:100%; height:100%; object-fit:cover; display:block; }
.hero::after{
  content:""; position:absolute; inset:0;
  background: linear-gradient(180deg, rgba(0,0,0,.08), rgba(0,0,0,.42));
}
.hero-info{
  position:absolute; left:14px; right:14px; bottom:12px;
  z-index:2;
  display:flex; justify-content:space-between; align-items:flex-end; gap:12px;
  color:#fff;
}
.hero-info .title{
  font-weight:900;
  font-size:18px;
}
.hero-info .sub{
  font-weight:600;
  color: rgba(255,255,255,.86);
  font-size: 13px;
  margin-top:6px;
}
.priceTag{
  display:inline-flex; flex-direction:column; gap:2px;
  background: rgba(255,255,255,.16);
  border: 1px solid rgba(255,255,255,.22);
  padding:10px 12px;
  border-radius: 14px;
  backdrop-filter: blur(10px);
  font-weight:900;
}
.priceTag small{ font-weight:800; color: rgba(255,255,255,.85); }

.row{
  display:flex; justify-content:space-between; gap:10px;
  padding: 12px 10px;
}
.row + .row{ border-top: 1px dashed rgba(17,24,39,.12); }
.label{ color: var(--muted); font-weight:800; font-size:13px; }
.value{ font-weight:900; text-align:right; word-break:break-word; }

label{
  display:block;
  font-weight:800;
  font-size:13px;
  margin: 10px 0 6px;
}
input, textarea{
  width:100%;
  padding: 12px 12px;
  border-radius: 14px;
  border: 1px solid rgba(17,24,39,.12);
  outline:none;
  font-weight:600;
  background: rgba(255,255,255,.95);
}
textarea{ min-height:96px; resize:vertical; }

.two{
  display:grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.btnbar{ display:flex; gap:10px; flex-wrap:wrap; margin-top:12px; }
.btn{
  display:inline-flex; align-items:center; justify-content:center;
  padding: 12px 14px;
  border-radius: 14px;
  font-weight:900;
  text-decoration:none;
  border: 1px solid rgba(17,24,39,.10);
  transition: transform .15s ease, box-shadow .15s ease, background .15s ease;
}
.btn:active{ transform: translateY(1px); }
.btn-primary{
  color:#fff; border:none;
  background: linear-gradient(135deg,var(--brand),var(--brand2));
  box-shadow: 0 16px 34px rgba(229,57,0,.20);
}
.btn-primary:hover{ transform: translateY(-1px); box-shadow: 0 22px 50px rgba(229,57,0,.26); }
.btn-ghost{
  color: var(--ink);
  background: rgba(17,24,39,.06);
}
.btn-ghost:hover{ transform: translateY(-1px); background: rgba(17,24,39,.08); }

.msg{
  margin: 12px 0 16px;
  padding: 12px 14px;
  border-radius: 14px;
  font-weight:800;
}
.success{ background:rgba(16,185,129,.12); border:1px solid rgba(16,185,129,.22); color:#065f46; }
.error{ background:rgba(239,68,68,.12); border:1px solid rgba(239,68,68,.22); color:#7f1d1d; }

.note{
  margin-top: 10px;
  color: var(--muted);
  font-weight:600;
  line-height:1.6;
  font-size: 13px;
}

@media (max-width: 900px){
  .navbar{ padding:12px 16px; }
  .grid{ grid-template-columns: 1fr; }
  .two{ grid-template-columns: 1fr; }
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
    <a href="index.php">PahunaStay</a>
  </div>

  <ul class="nav-links">
    <li><a href="index.php">Home</a></li>
    <li><a href="room.php">Available Rooms</a></li>
    <li><a href="<?php echo safe($detailsUrl); ?>">Room Details</a></li>
  </ul>
</nav>

<div class="wrap">

  <div class="top">
    <div>
      <h1>Book Room</h1>
      <p>Fill the form to request booking for this room.</p>
    </div>
    <div class="pill"><span>Status</span> <b>Available</b></div>
  </div>

  <?php if ($success): ?><div class="msg success"><?php echo safe($success); ?></div><?php endif; ?>
  <?php if ($error): ?><div class="msg error"><?php echo safe($error); ?></div><?php endif; ?>

  <div class="grid">

    <!-- Left Card: Room Summary -->
    <div class="card">
      <div class="hero">
        <img src="<?php echo safe($imgPath); ?>" alt="<?php echo safe($room['room_name']); ?>">
        <div class="hero-info">
          <div>
            <div class="title"><?php echo safe($room['room_name']); ?></div>
            <div class="sub"><?php echo safe($room['location']); ?> • Owner: <?php echo safe($room['phone']); ?></div>
          </div>
          <div class="priceTag">
            <small>Per Night</small>
            <span>Rs. <?php echo safe($room['price']); ?></span>
          </div>
        </div>
      </div>

      <div class="card-body">
        <div class="row">
          <div class="label">Room</div>
          <div class="value"><?php echo safe($room['room_name']); ?></div>
        </div>
        <div class="row">
          <div class="label">Location</div>
          <div class="value"><?php echo safe($room['location']); ?></div>
        </div>
        <div class="row">
          <div class="label">Owner Phone</div>
          <div class="value"><?php echo safe($room['phone']); ?></div>
        </div>
        <div class="row">
          <div class="label">Price</div>
          <div class="value">Rs. <?php echo safe($room['price']); ?></div>
        </div>

        <div class="btnbar">
          <!-- IMPORTANT: This always goes back to SAME room details -->
        
          <a class="btn btn-ghost" href="room.php">Back to Rooms</a>
        </div>

        <div class="note">
          Tip: Submit correct phone and dates for fast confirmation.
        </div>
      </div>
    </div>

    <!-- Right Card: Booking Form -->
    <div class="card">
      <div class="card-head">
        <h2>Your Booking Details</h2>
      </div>
      <div class="card-body">

        <form method="POST" action="">
          <label>Full Name</label>
          <input type="text" value="<?php echo safe($fullName); ?>" readonly>

          <label>Email</label>
          <input type="email" value="<?php echo safe($email); ?>" readonly>

          <label>Your Phone (required)</label>
          <input type="text" name="phone" placeholder="98XXXXXXXX" required value="<?php echo safe($_POST['phone'] ?? ''); ?>">

          <div class="two">
            <div>
              <label>Check-in (required)</label>
              <input type="date" name="check_in" required min="<?php echo safe($today); ?>" value="<?php echo safe($_POST['check_in'] ?? ''); ?>">
            </div>
            <div>
              <label>Check-out (required)</label>
              <input type="date" name="check_out" required min="<?php echo safe($today); ?>" value="<?php echo safe($_POST['check_out'] ?? ''); ?>">
            </div>
          </div>

          <label>Guests</label>
          <input type="number" name="guests" min="1" max="20" value="<?php echo safe($_POST['guests'] ?? '1'); ?>">

          <label>Message (optional)</label>
          <textarea name="message" placeholder="Special request..."><?php echo safe($_POST['message'] ?? ''); ?></textarea>

          <div class="btnbar">
            <button class="btn btn-primary" type="submit">Confirm Booking</button>
            <a class="btn btn-ghost" href="<?php echo safe($detailsUrl); ?>">Cancel</a>
          </div>
        </form>

      </div>
    </div>

  </div>

</div>

</body>
</html>
