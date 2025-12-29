<?php
session_start();
include "db.php";

if (empty($_SESSION['logged_in'])) {
  header("Location: login.php");
  exit;
}

$userId = (int)($_SESSION['user_id'] ?? 0);

function safe($v){ return htmlspecialchars($v ?? "", ENT_QUOTES, "UTF-8"); }

/* Decide image path safely (uploads first; fallback to default) */
function roomImg($img){
  $img = trim($img ?? '');
  if ($img !== '' && file_exists(__DIR__ . "/uploads/" . $img)) return "uploads/" . $img;
  return "images/default.jpg"; // make sure this exists
}

$sql = "
SELECT 
  b.id AS booking_id,
  b.phone, b.check_in, b.check_out, b.guests, b.message, b.status, b.created_at,
  r.id AS room_id, r.room_name, r.location, r.price, r.image
FROM bookings b
JOIN rooms r ON r.id = b.room_id
WHERE b.user_id = ?
  AND LOWER(b.status) <> 'cancelled'
ORDER BY b.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();

$fullName = $_SESSION['fullname'] ?? 'User';
$initial  = strtoupper(substr($fullName ?: 'U', 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width,initial-scale=1.0" />
<title>My Bookings || PahunaStay </title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

<style>
:root{
  --brand:#e53900;
  --brand2:#ff5a1f;
  --ink:#0b1220;
  --muted:#6b7280;
  --card:rgba(255,255,255,.86);
  --border:rgba(17,24,39,.10);
  --shadow:0 24px 70px rgba(0,0,0,.16);
  --radius:18px;
}
*{box-sizing:border-box}
body{
  margin:0;
  font-family:"Inter",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
  color:var(--ink);
  min-height:100vh;
  background:
    radial-gradient(1200px 600px at 10% 10%, rgba(229,57,0,.18), transparent 55%),
    radial-gradient(900px 500px at 95% 25%, rgba(255,90,31,.14), transparent 55%),
    linear-gradient(180deg, #f7f7fb, #eef1f8);
}

/* Top bar */
.nav{
  position:sticky; top:0; z-index:40;
  display:flex; align-items:center; justify-content:space-between;
  padding:14px 22px;
  background:rgba(255,255,255,.65);
  backdrop-filter: blur(14px);
  border-bottom:1px solid rgba(17,24,39,.08);
}
.brand{
  display:flex; align-items:center; gap:10px;
  font-weight:900; letter-spacing:.2px;
}
.brand-badge{

  width:40px;
  height:40px;
  border-radius:14px;
  background: url("images/lg.png") center / cover no-repeat;
  box-shadow: 0 12px 30px rgba(229,57,0,.22);
}
.brand a{color:var(--ink); text-decoration:none}
.userchip{
  display:flex; align-items:center; gap:10px;
  padding:8px 10px;
  border-radius:999px;
  border:1px solid rgba(17,24,39,.08);
  background:rgba(255,255,255,.75);
  box-shadow:0 10px 26px rgba(0,0,0,.06);
}
.avatar{
  width:34px;height:34px;border-radius:50%;
  display:grid; place-items:center;
  font-weight:900; color:#fff;
  background:linear-gradient(135deg,var(--brand),var(--brand2));
}
.userchip span{font-weight:800; font-size:13px; color:var(--muted)}
.userchip b{font-weight:900; font-size:13px}

/* Layout */
.wrap{width:min(1150px, 92%); margin:24px auto 60px}
.header{
  display:flex; justify-content:space-between; align-items:flex-end; gap:12px;
  margin-bottom:14px;
}
.header h1{margin:0; font-size:26px; letter-spacing:-.4px}
.sub{margin:6px 0 0; color:var(--muted); font-weight:600; font-size:13px}

.actionsTop{display:flex; gap:10px; flex-wrap:wrap}
.btn{
  display:inline-flex; align-items:center; justify-content:center;
  gap:10px;
  padding:10px 14px;
  border-radius:14px;
  font-weight:900;
  text-decoration:none;
  border:1px solid rgba(17,24,39,.08);
  transition: transform .15s ease, box-shadow .15s ease, background .15s ease;
}
.btn:active{transform:translateY(1px)}
.btn-ghost{background:rgba(17,24,39,.06); color:var(--ink)}
.btn-ghost:hover{background:rgba(17,24,39,.08); transform:translateY(-1px)}
.btn-primary{
  color:#fff; border:none;
  background:linear-gradient(135deg,var(--brand),var(--brand2));
  box-shadow:0 16px 34px rgba(229,57,0,.20);
}
.btn-primary:hover{transform:translateY(-1px); box-shadow:0 22px 50px rgba(229,57,0,.26)}
.btn-danger{
  color:#fff; border:none;
  background:linear-gradient(135deg,#d32f2f,#ff1744);
  box-shadow:0 16px 34px rgba(211,47,47,.18);
}
.btn-danger:hover{transform:translateY(-1px); box-shadow:0 22px 50px rgba(211,47,47,.22)}

/* Success/empty */
.toast{
  margin:14px 0 18px;
  border-radius:16px;
  padding:12px 14px;
  background:rgba(230,255,237,.78);
  border:1px solid rgba(16,185,129,.18);
  box-shadow:0 10px 26px rgba(0,0,0,.06);
  font-weight:800;
}
.empty{
  margin-top:20px;
  padding:18px;
  border-radius:18px;
  border:1px dashed rgba(17,24,39,.20);
  background:rgba(255,255,255,.70);
  color:var(--muted);
  font-weight:700;
}

/* Cards grid */
.grid{
  display:grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap:16px;
}

/* Booking card */
.card{
  display:flex;
  gap:14px;
  padding:14px;
  border-radius:22px;
  background:var(--card);
  border:1px solid var(--border);
  box-shadow:0 18px 50px rgba(0,0,0,.10);
  overflow:hidden;
}
.thumb{
  width:190px;
  min-width:190px;
  height:140px;
  border-radius:18px;
  overflow:hidden;
  background:#eee;
  position:relative;
}
.thumb img{
  width:100%; height:100%;
  object-fit:cover;
  display:block;
  transform:scale(1.02);
}
.thumb::after{
  content:"";
  position:absolute; inset:0;
  background:linear-gradient(180deg, transparent, rgba(0,0,0,.22));
}

.cardBody{flex:1; min-width:0}
.rowTop{
  display:flex; justify-content:space-between; gap:12px; align-items:flex-start;
}
.title{
  margin:0;
  font-size:16px;
  letter-spacing:-.2px;
  font-weight:900;
}
.meta{
  margin-top:6px;
  color:var(--muted);
  font-weight:700;
  font-size:13px;
  line-height:1.45;
}
.meta b{color:var(--ink)}
.kv{
  margin-top:10px;
  display:grid;
  grid-template-columns: 1fr 1fr;
  gap:8px 12px;
}
.kv .item{
  padding:10px 10px;
  border-radius:14px;
  background:rgba(17,24,39,.045);
  border:1px solid rgba(17,24,39,.06);
}
.kv .lbl{color:var(--muted); font-weight:900; font-size:12px}
.kv .val{margin-top:4px; font-weight:900; font-size:13px; word-break:break-word}

.msg{
  margin-top:10px;
  padding:10px 12px;
  border-radius:16px;
  background:rgba(255,255,255,.75);
  border:1px solid rgba(17,24,39,.08);
  color:var(--muted);
  font-weight:650;
  font-size:13px;
  line-height:1.55;
}
.msg b{color:var(--ink); font-weight:900}

/* Status badges */
.badge{
  display:inline-flex;
  align-items:center;
  gap:8px;
  padding:8px 12px;
  border-radius:999px;
  font-weight:900;
  font-size:12px;
  border:1px solid rgba(17,24,39,.08);
  background:rgba(255,255,255,.70);
}
.dot{width:9px;height:9px;border-radius:999px;background:#999}

.badge.pending .dot{background:#f59e0b}
.badge.pending{background:rgba(255,246,214,.78)}
.badge.confirmed .dot{background:#10b981}
.badge.confirmed{background:rgba(230,255,237,.78)}
.badge.cancelled .dot{background:#ef4444}
.badge.cancelled{background:rgba(255,230,230,.78)}
.badge.booked .dot{background:#3b82f6}
.badge.booked{background:rgba(219,234,254,.78)}

/* Footer actions */
.footerActions{
  margin-top:12px;
  display:flex;
  gap:10px;
  flex-wrap:wrap;
}

/* Responsive */
@media (max-width: 980px){
  .grid{grid-template-columns: 1fr}
}
@media (max-width: 560px){
  .card{flex-direction:column}
  .thumb{width:100%; min-width:0; height:190px}
  .kv{grid-template-columns: 1fr}
}
</style>
</head>
<body>

<div class="nav">
  <div class="brand">
    <div class="brand-badge"></div>
    <a href="index.php">PahunaStay</a>
  </div>

  <div class="userchip" title="Signed in">
    <div class="avatar"><?php echo safe($initial); ?></div>
    <span>Signed in as</span> <b><?php echo safe($fullName); ?></b>
  </div>
</div>

<div class="wrap">
<?php
$cancelled = isset($_GET['cancelled']) && $_GET['cancelled'] == '1';
$bid = (int)($_GET['bid'] ?? 0);
$rid = (int)($_GET['rid'] ?? 0);
$fee = (int)($_GET['fee'] ?? 0);
$method = safe($_GET['method'] ?? '');
?>

<?php if ($cancelled): ?>
  <div style="background:#e6ffed;border:1px solid #b7f5c6;padding:12px 14px;border-radius:12px;font-weight:800;margin:14px 0;">
    Cancellation successful. Paid Rs. <?php echo $fee; ?> via <?php echo strtoupper($method); ?>.
    <?php if ($rid > 0): ?>
      <a href="room_detail.php?id=<?php echo $rid; ?>" style="margin-left:10px;color:#0b1220;text-decoration:underline;">
        View room
      </a>
    <?php endif; ?>
  </div>
<?php endif; ?>

  <div class="header">
    <div>
      <h1>My Bookings</h1>
      <p class="sub">Track your reservations, view details, and cancel pending bookings.</p>
    </div>

    <div class="actionsTop">
      <a class="btn btn-ghost" href="dashboard.php">Back to Dashboard</a>
      <a class="btn btn-primary" href="room.php">Book a Room</a>
    </div>
  </div>

  <?php if (isset($_GET['success'])): ?>
    <div class="toast">Booking placed successfully.</div>
  <?php endif; ?>

  <?php if ($res->num_rows === 0): ?>
    <div class="empty">
      No bookings yet. Go to <a href="room.php" style="font-weight:900;color:var(--brand);text-decoration:none;">Available Rooms</a> and book your first room.
    </div>
  <?php else: ?>

    <div class="grid">
      <?php while($row = $res->fetch_assoc()):
        $status = strtolower($row['status'] ?? 'pending');
        $img = roomImg($row['image'] ?? '');
      ?>
       <div class="card" id="booking-<?php echo (int)$row['booking_id']; ?>"
     style="<?php echo ($bid === (int)$row['booking_id']) ? 'outline:3px solid rgba(229,57,0,.25); box-shadow:0 20px 55px rgba(229,57,0,.12);' : ''; ?>">


          <div class="thumb">
            <img src="<?php echo safe($img); ?>" alt="<?php echo safe($row['room_name']); ?>">
          </div>

          <div class="cardBody">
            <div class="rowTop">
              <div style="min-width:0;">
                <h3 class="title"><?php echo safe($row['room_name']); ?></h3>
                <div class="meta">
                  <?php echo safe($row['location']); ?> •
                  <b>Rs. <?php echo safe($row['price']); ?></b> / night
                </div>
              </div>

              <span class="badge <?php echo safe($status); ?>">
                <span class="dot"></span>
                <?php echo strtoupper(safe($row['status'] ?? 'PENDING')); ?>
              </span>
            </div>

            <div class="kv">
              <div class="item">
                <div class="lbl">Check-in</div>
                <div class="val"><?php echo safe($row['check_in']); ?></div>
              </div>
              <div class="item">
                <div class="lbl">Check-out</div>
                <div class="val"><?php echo safe($row['check_out']); ?></div>
              </div>
              <div class="item">
                <div class="lbl">Guests</div>
                <div class="val"><?php echo safe($row['guests']); ?></div>
              </div>
              <div class="item">
                <div class="lbl">Phone</div>
                <div class="val"><?php echo safe($row['phone']); ?></div>
              </div>
            </div>

            <?php if (!empty($row['message'])): ?>
              <div class="msg"><b>Message:</b> <?php echo safe($row['message']); ?></div>
            <?php endif; ?>

            <div class="meta" style="margin-top:10px;">
              <b>Booked at:</b> <?php echo safe($row['created_at']); ?>
            </div>

            <div class="footerActions">
              <a class="btn btn-ghost" href="room_detail.php?id=<?php echo (int)$row['room_id']; ?>">View Room</a>

              <?php if ($status === 'pending' || $status === 'booked'): ?>
               <a class="btn btn-cancel"
   href="cancel_booking_confirm.php?id=<?php echo (int)$row['booking_id']; ?>">
   Cancel
</a>

              <?php endif; ?>
            </div>

          </div>
        </div>
      <?php endwhile; ?>
    </div>

  <?php endif; ?>

</div>
<?php if ($bid > 0): ?>
<script>
  const el = document.getElementById("booking-<?php echo (int)$bid; ?>");
  if (el) el.scrollIntoView({ behavior: "smooth", block: "center" });
</script>
<?php endif; ?>

</body>
</html>
