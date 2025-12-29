<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "db.php";

if (empty($_SESSION['logged_in'])) {
  header("Location: login.php");
  exit;
}

$userId    = (int)($_SESSION['user_id'] ?? 0);
$bookingId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

function safe($v){ return htmlspecialchars($v ?? "", ENT_QUOTES, "UTF-8"); }

if ($bookingId <= 0) {
  die("Invalid booking ID.");
}

/* Fetch booking + room */
$sql = "
SELECT 
  b.id, b.status,
  r.room_name, r.location, r.price
FROM bookings b
JOIN rooms r ON r.id = b.room_id
WHERE b.id = ? AND b.user_id = ?
LIMIT 1
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
  die("SQL Prepare failed: " . $conn->error);
}
$stmt->bind_param("ii", $bookingId, $userId);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking) {
  die("Booking not found (or not your booking).");
}

$status = strtolower($booking['status'] ?? 'pending');
if (!in_array($status, ['pending','booked','confirmed'])) {
  die("This booking cannot be cancelled.");
}

$price = (float)$booking['price'];
$fee   = (int) round($price * 0.10);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Cancel Booking | Quick Book</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">

<style>
  :root{
    --brand:#e53900;
    --brand2:#ff5a1f;
    --ink:#0b1220;
    --muted:#6b7280;
    --stroke:rgba(17,24,39,.12);
    --shadow: 0 28px 70px rgba(2,6,23,.16);
    --card: rgba(255,255,255,.78);
    --card2: rgba(255,255,255,.68);
  }

  *{box-sizing:border-box}
  html,body{height:100%}
  body{
    margin:0;
    font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
    color:var(--ink);
    display:grid;
    place-items:center;
    padding:28px 16px;

    background:
      radial-gradient(900px 520px at 12% 10%, rgba(229,57,0,.24), transparent 56%),
      radial-gradient(1000px 520px at 92% 22%, rgba(255,90,31,.18), transparent 58%),
      radial-gradient(900px 520px at 50% 112%, rgba(37,99,235,.10), transparent 56%),
      linear-gradient(180deg,#f8f8ff,#eef2ff);
  }

  /* floating orbs */
  .bg-orb{
    position:fixed;
    width:420px; height:420px;
    border-radius:999px;
    filter: blur(48px);
    opacity:.55;
    z-index:-1;
    pointer-events:none;
    animation: float 9s ease-in-out infinite;
  }
  .orb1{left:-160px; top:-160px; background:rgba(229,57,0,.30)}
  .orb2{right:-180px; top:-40px; background:rgba(255,90,31,.26); animation-delay:-2.5s}
  .orb3{left:18%; bottom:-220px; background:rgba(37,99,235,.22); animation-delay:-4.5s}

  @keyframes float{
    0%,100%{transform:translate(0,0) scale(1)}
    50%{transform:translate(16px,20px) scale(1.02)}
  }

  .wrap{
    width:min(980px, 96%);
  }

  .card{
    border:1px solid var(--stroke);
    border-radius:24px;
    overflow:hidden;
    background:var(--card);
    box-shadow:var(--shadow);
    backdrop-filter: blur(12px);
  }

  .head{
    padding:18px 20px;
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:16px;
    border-bottom:1px solid rgba(17,24,39,.08);
    background:
      linear-gradient(180deg, rgba(255,255,255,.86), rgba(255,255,255,.55));
  }

  .title{
    display:flex;
    gap:12px;
    align-items:flex-start;
  }

  .badge{
  width:40px;
  height:40px;
  border-radius:14px;
  background: url("images/lg.png") center / cover no-repeat;
  box-shadow: 0 12px 30px rgba(229,57,0,.22);
  }

  h1{
    margin:0;
    font-size:20px;
    letter-spacing:-.3px;
  }

  .sub{
    margin:6px 0 0;
    color:var(--muted);
    font-weight:650;
    font-size:13px;
    line-height:1.4;
    max-width:560px;
  }

  .fee-pill{
    align-self:flex-start;
    display:inline-flex;
    align-items:center;
    gap:8px;
    border-radius:999px;
    padding:10px 12px;
    font-weight:900;
    font-size:13px;
    color:#7c1400;
    background:rgba(229,57,0,.10);
    border:1px solid rgba(229,57,0,.20);
    white-space:nowrap;
  }
  .fee-pill .dot{
    width:8px; height:8px; border-radius:999px;
    background:linear-gradient(135deg,var(--brand),var(--brand2));
    box-shadow:0 10px 18px rgba(229,57,0,.25);
  }

  .body{
    padding:18px 20px 22px;
  }

  .grid{
    display:grid;
    grid-template-columns: 1.35fr .65fr;
    gap:16px;
  }

  .panel{
    background:var(--card2);
    border:1px solid rgba(17,24,39,.10);
    border-radius:18px;
    padding:16px;
  }

  .chip{
    display:flex;
    gap:10px;
    align-items:flex-start;
    padding:12px 12px;
    border-radius:16px;
    background:rgba(17,24,39,.04);
    border:1px solid rgba(17,24,39,.08);
    margin-bottom:12px;
  }

  .chip .ico{
    width:40px; height:40px;
    border-radius:14px;
    display:grid; place-items:center;
    background:rgba(229,57,0,.10);
    border:1px solid rgba(229,57,0,.20);
    font-size:18px;
    flex:0 0 auto;
  }
  .chip .name{
    font-weight:950;
    font-size:14px;
    line-height:1.2;
  }
  .chip small{
    display:block;
    margin-top:4px;
    color:var(--muted);
    font-weight:700;
    font-size:12px;
  }

  .list{
    display:grid;
    gap:10px;
  }

  .row{
    display:flex;
    justify-content:space-between;
    gap:14px;
    padding:11px 12px;
    border-radius:14px;
    background:rgba(255,255,255,.58);
    border:1px solid rgba(17,24,39,.08);
  }

  .label{
    color:var(--muted);
    font-weight:850;
    font-size:13px;
    display:flex;
    align-items:center;
    gap:8px;
  }

  .label .mini{
    width:26px; height:26px;
    border-radius:10px;
    display:grid; place-items:center;
    background:rgba(17,24,39,.04);
    border:1px solid rgba(17,24,39,.08);
    font-size:14px;
  }

  .value{
    font-weight:950;
    font-size:13.5px;
    text-align:right;
  }

  .warn{
    margin-top:14px;
    padding:12px 14px;
    border-radius:16px;
    background:rgba(229,57,0,.10);
    border:1px solid rgba(229,57,0,.22);
    color:#8c1600;
    font-weight:850;
    line-height:1.4;
  }
  .warn b{color:#5a0d00}

  .side{
    display:flex;
    flex-direction:column;
    gap:12px;
  }

  .divider{
    height:1px;
    background:rgba(17,24,39,.10);
    margin:10px 0;
  }

  .note{
    margin:12px 0 0;
    color:var(--muted);
    font-weight:650;
    font-size:13px;
    line-height:1.45;
  }

  .total-box{
    padding:12px 12px;
    border-radius:16px;
    background:rgba(229,57,0,.06);
    border:1px solid rgba(229,57,0,.18);
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
  }
  .total-box .tlabel{
    font-weight:900;
    color:var(--muted);
    font-size:13px;
    display:flex;
    align-items:center;
    gap:8px;
  }
  .total-box .tlabel .mini{
    width:26px; height:26px; border-radius:10px;
    display:grid; place-items:center;
    background:rgba(229,57,0,.10);
    border:1px solid rgba(229,57,0,.18);
  }
  .total-box .tval{
    font-weight:950;
    color:#8c1600;
    font-size:14px;
  }

  .actions{
    margin-top:14px;
    display:flex;
    justify-content:flex-end;
    gap:10px;
    flex-wrap:wrap;
  }

  .btn{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    padding:11px 14px;
    border-radius:14px;
    font-weight:950;
    font-size:14px;
    border:none;
    cursor:pointer;
    text-decoration:none;
    transition: transform .12s ease, box-shadow .12s ease, background .12s ease;
    user-select:none;
  }
  .btn:hover{transform:translateY(-1px)}
  .btn:active{transform:translateY(0)}

  .btn-ghost{
    background:rgba(17,24,39,.06);
    color:var(--ink);
    border:1px solid rgba(17,24,39,.10);
  }

  .btn-primary{
    color:#fff;
    background:linear-gradient(135deg,var(--brand),var(--brand2));
    box-shadow:0 18px 40px rgba(229,57,0,.18);
  }
  .btn-primary:hover{box-shadow:0 24px 60px rgba(229,57,0,.22)}

  /* small entrance animation */
  .card{animation: pop .22s ease-out}
  @keyframes pop{
    from{transform:translateY(10px); opacity:.0}
    to{transform:translateY(0); opacity:1}
  }

  @media (max-width: 860px){
    .grid{grid-template-columns:1fr}
    .fee-pill{white-space:normal}
  }
</style>
</head>
<body>

<div class="bg-orb orb1"></div>
<div class="bg-orb orb2"></div>
<div class="bg-orb orb3"></div>

<div class="wrap">
  <div class="card">
    <div class="head">
      <div class="title">
        <div class="badge">×</div>
        <div>
          <h1>Cancel Booking</h1>
          <p class="sub">
            Review the details below. Once you proceed, you’ll pay the cancellation fee and your booking will be cancelled.
          </p>
        </div>
      </div>

      <div class="fee-pill"><span class="dot"></span> Cancellation Fee (10%): Rs. <?php echo (int)$fee; ?></div>
    </div>

    <div class="body">
      <div class="grid">
        <!-- Left: Details -->
        <div class="panel">
          <div class="chip">
            <div class="ico">🏨</div>
            <div>
              <div class="name"><?php echo safe($booking['room_name']); ?></div>
              <small>Room details</small>
            </div>
          </div>

          <div class="list">
            <div class="row">
              <div class="label"><span class="mini">📍</span> Location</div>
              <div class="value"><?php echo safe($booking['location']); ?></div>
            </div>

            <div class="row">
              <div class="label"><span class="mini">💳</span> Room Price</div>
              <div class="value">
                Rs. <?php echo (int)$price; ?> <span style="font-weight:800;color:var(--muted)">/ night</span>
              </div>
            </div>

            <div class="row">
              <div class="label"><span class="mini">⚠️</span> Cancellation Fee</div>
              <div class="value">Rs. <?php echo (int)$fee; ?></div>
            </div>
          </div>

          <div class="warn">
            You must pay <b>Rs. <?php echo (int)$fee; ?></b> to cancel this booking.
            After payment, the booking will be marked as <b>cancelled</b> and removed from your active list.
          </div>
        </div>

        <!-- Right: Summary -->
        <div class="side">
          <div class="panel">
            <div style="font-weight:950; font-size:14px;">Summary</div>
            <div class="divider"></div>

            <div class="total-box">
              <div class="tlabel"><span class="mini">₹</span> Total to pay</div>
              <div class="tval">Rs. <?php echo (int)$fee; ?></div>
            </div>

            <p class="note">
              On the next page you will select a payment method (eSewa, Khalti, IME Pay, or Bank Transfer).
            </p>

            <div class="actions">
              <a class="btn btn-ghost" href="my_bookings.php">← Go Back</a>
              <a class="btn btn-primary" href="cancel_payment.php?id=<?php echo (int)$bookingId; ?>">
                Proceed to Payment →
              </a>
            </div>
          </div>

          <div class="panel" style="padding:14px 16px;">
            <div style="font-weight:950; font-size:14px;">Tip</div>
            <p class="note" style="margin:8px 0 0;">
              If you change your mind, go back and keep your booking active.
            </p>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

</body>
</html>
