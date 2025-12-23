<?php
session_start();
include "db.php";

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

function safe($v){ return htmlspecialchars($v ?? "", ENT_QUOTES, "UTF-8"); }

$bookingId = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
if ($bookingId <= 0) {
    die("Invalid booking.");
}

/* Fetch booking + room details */
$stmt = $conn->prepare("
    SELECT 
        b.id AS booking_id,
        b.check_in, b.check_out, b.guests, b.status, b.created_at,
        r.id AS room_id,
        r.room_name, r.location, r.phone, r.price
    FROM bookings b
    JOIN rooms r ON r.id = b.room_id
    WHERE b.id = ? AND b.user_id = ?
    LIMIT 1
");
$stmt->bind_param("ii", $bookingId, $_SESSION['user_id']);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$data) {
    die("Booking not found.");
}

/* QR data (can be scanned later) */
$qrData = "QuickBook Booking\n"
        . "Booking ID: {$data['booking_id']}\n"
        . "Room: {$data['room_name']}\n"
        . "Location: {$data['location']}\n"
        . "Check-in: {$data['check_in']}\n"
        . "Check-out: {$data['check_out']}";
        
$qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=" . urlencode($qrData);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>PahunaStay </title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
body{
  margin:0;
  font-family:Inter,system-ui;
  background: linear-gradient(180deg,#f7f7fb,#eef1f8);
  color:#0b1220;
}
.wrap{
  width:min(900px,92%);
  margin:40px auto;
}
.card{
  background:#fff;
  border-radius:22px;
  box-shadow:0 25px 60px rgba(0,0,0,.12);
  padding:26px;
  text-align:center;
}
.check{
  width:72px;height:72px;
  border-radius:50%;
  background:linear-gradient(135deg,#22c55e,#16a34a);
  display:grid;place-items:center;
  color:#fff;font-size:34px;
  margin:0 auto 16px;
}
h1{margin:10px 0;font-size:24px}
.sub{color:#6b7280;font-weight:600;margin-bottom:22px}

.grid{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:18px;
  margin-top:20px;
}
.box{
  border:1px solid rgba(17,24,39,.12);
  border-radius:18px;
  padding:16px;
}
.row{
  display:flex;justify-content:space-between;
  padding:8px 0;
  font-weight:600;
}
.row span{color:#6b7280}

.qr img{
  width:220px;height:220px;
}

.btns{
  display:flex;gap:12px;justify-content:center;
  margin-top:22px;flex-wrap:wrap;
}
.btn{
  padding:12px 16px;
  border-radius:14px;
  font-weight:800;
  text-decoration:none;
}
.primary{
  color:#fff;
  background:linear-gradient(135deg,#e53900,#ff5a1f);
}
.ghost{
  background:#f1f5f9;
  color:#0b1220;
}
@media(max-width:700px){
  .grid{grid-template-columns:1fr}
}
.badge{
  display:flex;
  gap:14px;
  align-items:center;
  justify-content:center;
  margin-bottom:16px;
}
.headText{ text-align:left; }
.headText h1{ margin:0; font-size:24px; }
.headText .sub{ margin:6px 0 0; }

.proof{
  margin: 16px auto 0;
  display:flex;
  gap:12px;
  align-items:flex-start;
  padding:14px 16px;
  border-radius:18px;
  background: linear-gradient(180deg, rgba(229,57,0,.08), rgba(255,90,31,.06));
  border: 1px solid rgba(229,57,0,.18);
  text-align:left;
}
.proof-icon{
  width:42px;height:42px;
  border-radius:14px;
  display:grid;place-items:center;
  background: rgba(229,57,0,.12);
  font-size:18px;
}
.proof-text h3{
  margin:0;
  font-size:14px;
  font-weight:900;
}
.proof-text p{
  margin:6px 0 0;
  color:#374151;
  font-weight:600;
  line-height:1.55;
  font-size:13px;
}

.miniActions{
  display:flex;
  gap:10px;
  justify-content:center;
  margin-top:14px;
  flex-wrap:wrap;
}
.miniBtn{
  padding:10px 12px;
  border-radius:14px;
  font-weight:800;
  border:1px solid rgba(17,24,39,.12);
  background:#fff;
  cursor:pointer;
  text-decoration:none;
  color:#0b1220;
}
.miniBtn:hover{
  background:#f8fafc;
}

/* Print: hide buttons */
@media print{
  .miniActions, .btns{ display:none !important; }
  body{ background:#fff !important; }
  .card{ box-shadow:none !important; }
}

</style>
</head>
<body>

<div class="wrap">
  <div class="card">

    <div class="badge">
  <div class="check">✓</div>
  <div class="headText">
    <h1>Booking Confirmed</h1>
    <p class="sub">
      Your booking request has been submitted successfully. Please keep the QR code ready for verification.
    </p>
  </div>
</div>

<div class="proof">
  <div class="proof-icon">📸</div>
  <div class="proof-text">
    <h3>Screenshot Proof (Required)</h3>
    <p>
      Please take a screenshot of this page (showing <b>Booking ID</b> and the <b>QR code</b>) and keep it as proof.
      You may be asked to show it during check-in or confirmation.
    </p>
  </div>
</div>

<div class="miniActions">
  <a class="miniBtn" href="<?php echo safe($qrUrl); ?>" download>Download QR</a>
  <button class="miniBtn" onclick="window.print()">Print</button>
</div>


    <div class="grid">

      <div class="box">
        <div class="row"><span>Booking ID</span><b>#<?php echo safe($data['booking_id']); ?></b></div>
        <div class="row"><span>Room</span><b><?php echo safe($data['room_name']); ?></b></div>
        <div class="row"><span>Location</span><b><?php echo safe($data['location']); ?></b></div>
        <div class="row"><span>Check-in</span><b><?php echo safe($data['check_in']); ?></b></div>
        <div class="row"><span>Check-out</span><b><?php echo safe($data['check_out']); ?></b></div>
        <div class="row"><span>Status</span><b><?php echo ucfirst($data['status']); ?></b></div>
      </div>

      <div class="box qr">
        <img src="<?php echo $qrUrl; ?>" alt="QR Code">
        <p class="sub" style="margin-top:10px;">
          Scan for booking details
        </p>
      </div>

    </div>

    <div class="btns">
      <a class="btn primary" href="dashboard.php">Go to Dashboard</a>
      <a class="btn ghost" href="room.php">Book Another Room</a>
    </div>

  </div>
</div>

</body>
</html>
