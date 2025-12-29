<?php
session_start();
include "db.php";

if (empty($_SESSION['logged_in'])) {
  header("Location: login.php");
  exit;
}

$userId    = (int)($_SESSION['user_id'] ?? 0);
$bookingId = (int)($_GET['id'] ?? 0);

function safe($v){ return htmlspecialchars($v ?? "", ENT_QUOTES, "UTF-8"); }

if ($bookingId <= 0 || $userId <= 0) {
  header("Location: my_booking.php");
  exit;
}

/* Get booking + room price to calculate 10% fee */
$sql = "
SELECT b.id, b.user_id, b.status, r.room_name, r.price
FROM bookings b
JOIN rooms r ON r.id = b.room_id
WHERE b.id = ? AND b.user_id = ?
LIMIT 1
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $bookingId, $userId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
  header("Location: my_booking.php");
  exit;
}

$status = strtolower(trim($row['status'] ?? 'pending'));
if (!in_array($status, ['pending','booked','confirmed'])) {
  header("Location: my_booking.php");
  exit;
}

$price    = (float)$row['price'];
$fee      = (int) round($price * 0.10); // 10%
$roomName = $row['room_name'];

$initial = strtoupper(substr($_SESSION['fullname'] ?? 'U', 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Select Payment | Cancel Booking</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">

<style>
  :root{
    --bg1:#f6f6fb;
    --bg2:#efeaf0;

    --nav:#3a3a3a;
    --nav2:#2f2f2f;

    --ink:#0b1220;
    --muted:#6b7280;

    --brand:#ff4c14;
    --brand2:#ff6a2a;

    --stroke:rgba(17,24,39,.10);
    --shadow: 0 28px 70px rgba(2,6,23,.14);

    --card: rgba(255,255,255,.78);
    --card2: rgba(255,255,255,.62);

    --hero1:#b48a80;
    --hero2:#6c564f;
  }

  *{box-sizing:border-box}
  html,body{height:100%}

  body{
    margin:0;
    font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
    color:var(--ink);
    background:
      radial-gradient(900px 520px at 12% 18%, rgba(255,76,20,.16), transparent 58%),
      radial-gradient(1000px 520px at 92% 18%, rgba(255,106,42,.12), transparent 60%),
      linear-gradient(180deg, var(--bg1), var(--bg2));
  }

  /* ======= NAV (match index vibe) ======= */
  .nav{
    height:64px;
    background:linear-gradient(180deg, var(--nav), var(--nav2));
    border-bottom:1px solid rgba(255,255,255,.08);
    display:flex;
    align-items:center;
  }
  .nav-inner{
    width:min(1200px, 94%);
    margin:0 auto;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:16px;
  }
  .brand{
    display:flex;
    align-items:center;
    gap:10px;
    text-decoration:none;
    color:#fff;
    font-weight:900;
    letter-spacing:.2px;
  }
  .mark{
    width:34px; height:34px;
    border-radius:12px;
    background: radial-gradient(18px 18px at 30% 30%, rgba(255,255,255,.55), transparent 55%),
                linear-gradient(135deg,var(--brand),var(--brand2));
    box-shadow:0 14px 26px rgba(0,0,0,.26);
    position:relative;
  }
  .mark:after{
    content:"";
    position:absolute;
    inset:9px 10px 10px 9px;
    border-radius:10px;
    background:rgba(255,255,255,.16);
  }

  .menu{
    display:flex;
    align-items:center;
    gap:26px;
  }
  .menu a{
    color:rgba(255,255,255,.84);
    text-decoration:none;
    font-weight:700;
    font-size:14px;
    padding:8px 10px;
    border-radius:12px;
    transition: background .12s ease, color .12s ease, transform .12s ease;
  }
  .menu a:hover{
    background:rgba(255,255,255,.10);
    color:#fff;
    transform:translateY(-1px);
  }
  .menu a.active{
    color:#b084ff; /* like your purple "My Bookings" */
  }

  .nav-actions{
    display:flex;
    align-items:center;
    gap:10px;
  }

  .pill{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:9px 12px;
    border-radius:14px;
    border:1px solid rgba(255,255,255,.14);
    background:rgba(255,255,255,.08);
    color:#fff;
    font-weight:800;
    font-size:13px;
    text-decoration:none;
    transition: transform .12s ease, background .12s ease;
  }
  .pill:hover{transform:translateY(-1px); background:rgba(255,255,255,.12)}
  .avatar{
    width:40px; height:40px;
    border-radius:999px;
    background:linear-gradient(135deg,var(--brand),var(--brand2));
    display:grid;
    place-items:center;
    color:#fff;
    font-weight:950;
    box-shadow:0 14px 28px rgba(0,0,0,.28);
    user-select:none;
  }

  /* ======= PAGE WRAP ======= */
  .page{
    width:min(1200px, 94%);
    margin:0 auto;
    padding:26px 0 40px;
  }

  /* HERO STRIP (like index big brown card) */
  .hero{
    border-radius:26px;
    background: linear-gradient(180deg, rgba(180,138,128,.95), rgba(108,86,79,.96));
    box-shadow: 0 26px 60px rgba(0,0,0,.18);
    padding:22px 22px;
    color:#fff;
    position:relative;
    overflow:hidden;
  }
  .hero:before{
    content:"";
    position:absolute;
    inset:-2px;
    background: radial-gradient(700px 260px at 12% 0%, rgba(255,255,255,.22), transparent 55%),
                radial-gradient(700px 260px at 88% 20%, rgba(255,255,255,.14), transparent 55%);
    pointer-events:none;
  }
  .hero-inner{
    position:relative;
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:16px;
  }
  .hero h1{
    margin:0;
    font-size:22px;
    letter-spacing:-.3px;
  }
  .hero p{
    margin:8px 0 0;
    color:rgba(255,255,255,.84);
    font-weight:650;
    font-size:13px;
    line-height:1.45;
    max-width:720px;
  }
  .fee-badge{
    align-self:flex-start;
    background:rgba(255,255,255,.12);
    border:1px solid rgba(255,255,255,.18);
    padding:10px 12px;
    border-radius:999px;
    font-weight:950;
    font-size:13px;
    white-space:nowrap;
    display:inline-flex;
    align-items:center;
    gap:8px;
  }
  .fee-badge .dot{
    width:8px; height:8px;
    border-radius:999px;
    background:linear-gradient(135deg,var(--brand),var(--brand2));
    box-shadow:0 10px 18px rgba(0,0,0,.25);
  }

  /* MAIN CARD */
  .card{
    margin-top:18px;
    background:var(--card);
    border:1px solid var(--stroke);
    border-radius:22px;
    box-shadow:var(--shadow);
    overflow:hidden;
    backdrop-filter: blur(10px);
  }

  .content{
    padding:18px 18px 18px;
  }

  .layout{
    display:grid;
    grid-template-columns: 1.1fr .9fr;
    gap:16px;
  }

  .panel{
    background:var(--card2);
    border:1px solid rgba(17,24,39,.10);
    border-radius:18px;
    padding:16px;
  }

  .panel h2{
    margin:0 0 10px;
    font-size:14px;
    font-weight:950;
    letter-spacing:-.2px;
  }

  .grid{
    display:grid;
    grid-template-columns: 1fr 1fr;
    gap:12px;
  }

  .method{
    border:1px solid rgba(17,24,39,.10);
    border-radius:18px;
    padding:14px;
    background:rgba(255,255,255,.72);
    display:flex;
    align-items:center;
    gap:12px;
    cursor:pointer;
    transition:transform .12s ease, box-shadow .12s ease, border-color .12s ease;
    user-select:none;
    position:relative;
    overflow:hidden;
  }
  .method:before{
    content:"";
    position:absolute;
    inset:-2px;
    opacity:0;
    transition:opacity .12s ease;
    background:linear-gradient(135deg, rgba(255,76,20,.16), rgba(255,106,42,.12));
  }
  .method > *{position:relative}
  .method:hover{
    transform:translateY(-1px);
    box-shadow:0 16px 30px rgba(0,0,0,.08);
  }
  .method:hover:before{opacity:1}

  .logo{
    width:46px; height:46px;
    border-radius:16px;
    display:grid; place-items:center;
    font-weight:950;
    color:#fff;
    background:linear-gradient(135deg,var(--brand),var(--brand2));
    box-shadow:0 14px 28px rgba(0,0,0,.18);
    flex:0 0 auto;
  }
  .m-title{font-weight:950;margin:0;font-size:14px}
  .m-sub{margin:4px 0 0;color:var(--muted);font-size:12.5px;font-weight:650}

  .selected{
    border-color: rgba(255,76,20,.35);
    outline:3px solid rgba(255,76,20,.18);
  }
  .selected:after{
    content:"✓";
    position:absolute;
    right:12px;
    top:12px;
    width:26px;
    height:26px;
    border-radius:10px;
    display:grid;
    place-items:center;
    background:rgba(255,76,20,.10);
    border:1px solid rgba(255,76,20,.22);
    color:#7c1400;
    font-weight:950;
    font-size:14px;
  }

  .divider{
    height:1px;
    background:rgba(17,24,39,.10);
    margin:12px 0;
  }

  .hint{
    margin:0;
    color:var(--muted);
    font-weight:650;
    font-size:13px;
    line-height:1.45;
  }

  /* SUMMARY SIDE */
  .room{
    margin:0;
    font-weight:950;
    font-size:15px;
    line-height:1.25;
  }
  .meta{
    margin:8px 0 0;
    color:var(--muted);
    font-size:13px;
    font-weight:650;
    line-height:1.45;
  }

  .total{
    margin-top:12px;
    padding:12px;
    border-radius:16px;
    background:rgba(255,76,20,.06);
    border:1px solid rgba(255,76,20,.18);
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
  }
  .total .tlabel{
    font-weight:900;
    color:var(--muted);
    font-size:13px;
    display:flex;
    align-items:center;
    gap:8px;
  }
  .total .mini{
    width:26px; height:26px;
    border-radius:10px;
    display:grid; place-items:center;
    background:rgba(255,76,20,.10);
    border:1px solid rgba(255,76,20,.18);
    color:#7c1400;
    font-weight:950;
  }
  .total .tval{
    font-weight:950;
    color:#8c1600;
    font-size:14px;
    white-space:nowrap;
  }

  .chosen{
    margin:12px 0 0;
    color:var(--muted);
    font-weight:650;
    font-size:13px;
    line-height:1.45;
  }

  .actions{
    margin-top:14px;
    display:flex;
    gap:10px;
    justify-content:flex-end;
    flex-wrap:wrap;
  }

  .btn{
    border:none;
    cursor:pointer;
    text-decoration:none;
    padding:11px 14px;
    border-radius:14px;
    font-weight:950;
    font-size:14px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
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
    background:linear-gradient(135deg,var(--brand),var(--brand2));
    color:#fff;
    box-shadow:0 18px 40px rgba(255,76,20,.18);
  }
  .btn-primary:hover{box-shadow:0 24px 60px rgba(255,76,20,.22)}

  .btn-primary:disabled{
    opacity:.6;
    cursor:not-allowed;
    transform:none;
    box-shadow:none;
  }

  @media (max-width: 900px){
    .menu{display:none;}
  }
  @media (max-width: 860px){
    .layout{grid-template-columns:1fr}
    .grid{grid-template-columns:1fr}
    .fee-badge{white-space:normal}
  }
</style>
</head>
<body>

<!-- NAVBAR -->
<header class="nav">
  <div class="nav-inner">
    <a class="brand" href="index.php">
      <span class="mark"></span>
      <span>PahunaStay</span>
    </a>

    <nav class="menu">
      <a href="index.php">Home</a>
      <a href="room.php">Available Rooms</a>
      <a href="#contact">Contact</a>
      <a class="active" href="my_bookings.php">My Bookings</a>
    </nav>

    <div class="nav-actions">
      <a class="pill" href="upload.php">Upload</a>
      <div class="avatar"><?php echo safe($initial); ?></div>
    </div>
  </div>
</header>

<main class="page">

  <!-- HERO STRIP -->
  <section class="hero">
    <div class="hero-inner">
      <div>
        <h1>Select a Payment Method</h1>
        <p>
          Cancel booking for <b><?php echo safe($roomName); ?></b>. Choose a payment option to pay the cancellation fee.
        </p>
      </div>
      <div class="fee-badge"><span class="dot"></span> Cancellation Fee (10%): Rs. <?php echo (int)$fee; ?></div>
    </div>
  </section>

  <!-- MAIN CARD -->
  <section class="card">
    <div class="content">
      <form method="POST" action="cancel_payment_process.php" id="payForm">
        <input type="hidden" name="id" value="<?php echo (int)$bookingId; ?>">
        <input type="hidden" name="method" id="methodInput" value="">

        <div class="layout">

          <!-- LEFT -->
          <div class="panel">
            <h2>Choose payment option</h2>

            <div class="grid" id="methods">
              <div class="method" data-method="esewa" role="button" tabindex="0">
                <div class="logo">eS</div>
                <div>
                  <p class="m-title">eSewa</p>
                  <p class="m-sub">Wallet payment (Nepal)</p>
                </div>
              </div>

              <div class="method" data-method="khalti" role="button" tabindex="0">
                <div class="logo">K</div>
                <div>
                  <p class="m-title">Khalti</p>
                  <p class="m-sub">Wallet payment (Nepal)</p>
                </div>
              </div>

              <div class="method" data-method="imepay" role="button" tabindex="0">
                <div class="logo">IM</div>
                <div>
                  <p class="m-title">IME Pay</p>
                  <p class="m-sub">Wallet payment (Nepal)</p>
                </div>
              </div>

              <div class="method" data-method="bank" role="button" tabindex="0">
                <div class="logo">Bk</div>
                <div>
                  <p class="m-title">Bank Transfer</p>
                  <p class="m-sub">Manual verification</p>
                </div>
              </div>
            </div>

            <div class="divider"></div>
            <p class="hint">
              Tip: Select a payment option. The “Pay & Cancel Booking” button will activate after selection.
            </p>
          </div>

          <!-- RIGHT -->
          <div class="panel">
            <p class="room"><?php echo safe($roomName); ?></p>
            <p class="meta">
              Cancellation charge is <b>10%</b> of room price. This action will mark your booking as <b>cancelled</b>.
            </p>

            <div class="total">
              <div class="tlabel"><span class="mini">₹</span> Total to pay</div>
              <div class="tval">Rs. <?php echo (int)$fee; ?></div>
            </div>

            <p class="chosen" id="chosenHint">No payment method selected yet.</p>

            <div class="actions">
              <a class="btn btn-ghost" href="cancel_booking_confirm.php?id=<?php echo (int)$bookingId; ?>">← Go Back</a>
              <button class="btn btn-primary" id="payBtn" type="submit" disabled>Pay & Cancel Booking →</button>
            </div>
          </div>

        </div>
      </form>
    </div>
  </section>

</main>

<script>
  const methods = document.querySelectorAll(".method");
  const input = document.getElementById("methodInput");
  const form = document.getElementById("payForm");
  const payBtn = document.getElementById("payBtn");
  const chosenHint = document.getElementById("chosenHint");

  function setSelected(el){
    methods.forEach(x => x.classList.remove("selected"));
    el.classList.add("selected");
    input.value = el.dataset.method;

    payBtn.disabled = false;

    const name = el.querySelector(".m-title")?.textContent?.trim() || input.value;
    chosenHint.innerHTML = "Selected: <b>" + name + "</b>. Click “Pay & Cancel Booking” to continue.";
  }

  methods.forEach(m => {
    m.addEventListener("click", () => setSelected(m));
    m.addEventListener("keydown", (e) => {
      if (e.key === "Enter" || e.key === " ") {
        e.preventDefault();
        setSelected(m);
      }
    });
  });

  form.addEventListener("submit", (e) => {
    if (!input.value) {
      e.preventDefault();
      alert("Please select a payment method.");
    }
  });
</script>

</body>
</html>
