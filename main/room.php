<?php
session_start();
include "db.php";

function safe($v) { return htmlspecialchars($v ?? "", ENT_QUOTES, "UTF-8"); }

$initial = strtoupper(substr($_SESSION['fullname'] ?? 'U', 0, 1));

// Fetch rooms (same logic, but use ORDER to look nicer)
$sql = "SELECT * FROM rooms ORDER BY id DESC";
$rooms = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Available Rooms |   PahunaStay </title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
:root{
  --brand:#e53900;
  --brand2:#ff5a1f;
  --ink:#0b1220;
  --muted:#6b7280;
  --card: rgba(255,255,255,.88);
  --border: rgba(17,24,39,.10);
  --shadow: 0 24px 70px rgba(0,0,0,.14);
  --radius: 18px;
}

*{box-sizing:border-box}
body{
  margin:0;
  font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
  color:var(--ink);
  min-height:100vh;
  background:
    radial-gradient(1200px 600px at 10% 10%, rgba(229,57,0,.18), transparent 55%),
    radial-gradient(900px 500px at 95% 25%, rgba(255,90,31,.16), transparent 55%),
    linear-gradient(180deg, #f7f7fb, #eef1f8);
}

/* Navbar */
.navbar{
  position:sticky; top:0; z-index:50;
  display:flex; justify-content:space-between; align-items:center;
  padding:14px 48px;
  background:#333;
  border-bottom: 1px solid rgba(255,255,255,.08);
}
.logo{
  display:flex; align-items:center; gap:10px;
  font-weight:800; letter-spacing:.3px; color:#fff;
}
.logo-badge{
   width:40px;
  height:40px;
  border-radius:14px;
  background: url("images/lg.png") center / cover no-repeat;
  box-shadow: 0 12px 30px rgba(229,57,0,.22);
}
.logo a{color:#fff;text-decoration:none;font-size:18px}

.nav-links{ display:flex; list-style:none; gap:14px; padding:0; margin:0; }
.nav-links a{
  color:rgba(255,255,255,.92);
  text-decoration:none;
  font-weight:600;
  font-size:14px;
  padding:10px 12px;
  border-radius:10px;
}
.nav-links a:hover{ background: rgba(255,255,255,.10); }

.nav-user{ display:flex; align-items:center; gap:12px; }
.user-link{ text-decoration:none; display:flex; align-items:center; }
.avatar{
  width:42px;height:42px;border-radius:50%;
  background: linear-gradient(135deg,var(--brand),var(--brand2));
  color:#fff; display:grid; place-items:center;
  font-weight:900; box-shadow:0 10px 24px rgba(0,0,0,.25);
}
.login-btn{
  background: linear-gradient(135deg,var(--brand),var(--brand2));
  color:#fff; border:none;
  padding:10px 16px;
  border-radius:12px;
  font-weight:800;
  cursor:pointer;
  box-shadow:0 14px 28px rgba(229,57,0,.18);
}
.login-btn a{
  text-decoration:none;
  color:white;
  font-weight:800;
}
.login-btn:hover{ filter:brightness(.98); }

/* Page wrapper */
.wrap{
  width:min(1150px, 92%);
  margin: 22px auto 70px;
}

/* Hero */
.hero{
  border-radius: 26px;
  overflow:hidden;
  background: #111;
  position:relative;
  box-shadow: var(--shadow);
  min-height: 240px;
}
.hero::before{
  content:"";
  position:absolute; inset:0;
  background:
    linear-gradient(135deg, rgba(229,57,0,.45), rgba(255,90,31,.20)),
    url('images/hover3.jpg') center/cover no-repeat;
  filter:saturate(1.08);
  transform: scale(1.04);
  animation: heroZoom 14s linear alternate infinite;
}
@keyframes heroZoom{
  0%{ transform: scale(1.04); }
  100%{ transform: scale(1.14); }
}
.hero::after{
  content:"";
  position:absolute; inset:0;
  background: linear-gradient(180deg, rgba(0,0,0,.25), rgba(0,0,0,.55));
}
.hero-inner{
  position:relative;
  padding: 26px 24px;
  z-index:2;
  color:#fff;
  display:flex;
  flex-direction:column;
  gap: 12px;
}
.hero-title{
  display:flex;
  align-items:flex-end;
  justify-content:space-between;
  gap: 14px;
  flex-wrap:wrap;
}
.hero-title h1{
  margin:0;
  font-size: 30px;
  letter-spacing:-.3px;
}
.hero-title p{
  margin:6px 0 0;
  color: rgba(255,255,255,.86);
  font-weight:600;
  max-width: 680px;
}
.hero-pill{
  display:inline-flex;
  align-items:center;
  gap:10px;
  padding:10px 14px;
  border-radius:999px;
  background: rgba(255,255,255,.14);
  border: 1px solid rgba(255,255,255,.22);
  backdrop-filter: blur(10px);
  font-weight:900;
}

/* Search / Filter bar */
.filterbar{
  margin-top: 14px;
  display:flex;
  gap: 10px;
  flex-wrap:wrap;
}
.search{
  flex: 1;
  min-width: 240px;
  display:flex;
  gap: 10px;
  background: rgba(255,255,255,.12);
  border: 1px solid rgba(255,255,255,.22);
  border-radius: 16px;
  padding: 10px;
  backdrop-filter: blur(10px);
}
.search input{
  flex:1;
  border:0;
  outline:none;
  background:transparent;
  color:#fff;
  font-weight:700;
}
.search input::placeholder{ color: rgba(255,255,255,.75); }
.search button{
  border:0;
  cursor:pointer;
  padding: 10px 14px;
  border-radius: 14px;
  font-weight:900;
  color:#0b1220;
  background: rgba(255,255,255,.92);
}
.small-info{
  font-weight:800;
  color: rgba(255,255,255,.88);
  display:flex;
  align-items:center;
  gap:10px;
}

/* Rooms grid */
.grid{
  margin-top: 18px;
  display:grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
}
@media (max-width: 980px){
  .navbar{ padding: 12px 16px; }
  .grid{ grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 640px){
  .nav-links{ display:none; }
  .grid{ grid-template-columns: 1fr; }
}

/* Room card */
.room-card{
  border-radius: 22px;
  overflow:hidden;
  background: var(--card);
  border: 1px solid var(--border);
  box-shadow: 0 18px 55px rgba(0,0,0,.10);
  transform: translateZ(0);
  transition: transform .18s ease, box-shadow .18s ease;
}
.room-card:hover{
  transform: translateY(-4px);
  box-shadow: 0 28px 70px rgba(0,0,0,.14);
}
.room-img{
  position:relative;
  height: 190px;
  background:#ddd;
  overflow:hidden;
}
.room-img img{
  width:100%;
  height:100%;
  object-fit:cover;
  transition: transform 1.1s ease;
}
.room-card:hover .room-img img{
  transform: scale(1.08);
}
.badge{
  position:absolute;
  left: 12px;
  top: 12px;
  padding: 8px 10px;
  border-radius: 999px;
  font-weight:900;
  font-size: 12px;
  color:#fff;
  background: rgba(229,57,0,.92);
  box-shadow: 0 14px 30px rgba(229,57,0,.22);
}
.priceTag{
  position:absolute;
  right: 12px;
  bottom: 12px;
  padding: 10px 12px;
  border-radius: 16px;
  background: rgba(0,0,0,.40);
  border: 1px solid rgba(255,255,255,.20);
  color:#fff;
  backdrop-filter: blur(10px);
  font-weight:900;
}
.priceTag small{
  display:block;
  font-weight:800;
  color: rgba(255,255,255,.85);
  font-size: 12px;
  margin-bottom: 2px;
}

.room-body{
  padding: 14px 14px 16px;
}
.room-title{
  font-weight:900;
  font-size: 16px;
  margin: 0 0 6px;
  letter-spacing:-.2px;
}
.meta{
  display:grid;
  gap: 6px;
  color: var(--muted);
  font-weight:700;
  font-size: 13px;
}
.meta b{ color: var(--ink); font-weight:900; }
.actions{
  display:flex;
  gap: 10px;
  margin-top: 12px;
}
.btn{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  gap: 10px;
  padding: 10px 12px;
  border-radius: 14px;
  text-decoration:none;
  font-weight:900;
  border: 1px solid rgba(17,24,39,.10);
  transition: transform .15s ease, background .15s ease;
}
.btn:active{ transform: translateY(1px); }
.btn-primary{
  color:#fff;
  border:none;
  background: linear-gradient(135deg,var(--brand),var(--brand2));
  box-shadow: 0 16px 34px rgba(229,57,0,.18);
}
.btn-primary:hover{ transform: translateY(-1px); }
.btn-ghost{
  color: var(--ink);
  background: rgba(17,24,39,.06);
}
.btn-ghost:hover{ background: rgba(17,24,39,.08); }

/* Footer */
footer{ margin-top: 28px; }
.footer-bottom{
  background:#333;
  color:#fff;
  text-align:center;
  padding: 14px 0;
  font-weight:700;
  opacity:.95;
}
/* ===== PahunaStay Footer ===== */
.sn-footer{
  margin-top: 40px;
  position: relative;
  color: rgba(255,255,255,.88);
  background:
    radial-gradient(900px 500px at 15% 10%, rgba(229,57,0,.22), transparent 55%),
    radial-gradient(700px 420px at 90% 30%, rgba(255,90,31,.18), transparent 55%),
    linear-gradient(180deg, #2b2b2b, #1f1f1f);
  border-top: 1px solid rgba(255,255,255,.08);
}

.sn-footer__wrap{
  width: min(1150px, 92%);
  margin: 0 auto;
  padding: 34px 0 18px;
}

.sn-footer__top{
  display: grid;
  grid-template-columns: 1.4fr 1fr 1fr 1.2fr;
  gap: 18px;
  padding-bottom: 18px;
  border-bottom: 1px solid rgba(255,255,255,.10);
}

@media (max-width: 980px){
  .sn-footer__top{
    grid-template-columns: 1fr 1fr;
  }
}
@media (max-width: 640px){
  .sn-footer__top{
    grid-template-columns: 1fr;
  }
}

.sn-footer__brand{
  display: flex;
  gap: 12px;
  align-items: center;
}

.sn-footer__logo{
   width:40px;
  height:40px;
  border-radius:14px;
  background: url("images/lg.png") center / cover no-repeat;
  box-shadow: 0 12px 30px rgba(229,57,0,.22);
}

.sn-footer__name{
  font-weight: 900;
  letter-spacing: .2px;
  font-size: 18px;
  color: #fff;
}
.sn-footer__tag{
  font-weight: 700;
  font-size: 12px;
  color: rgba(255,255,255,.72);
}

.sn-footer__text{
  margin: 12px 0 14px;
  font-weight: 650;
  line-height: 1.6;
  color: rgba(255,255,255,.78);
  max-width: 46ch;
}

.sn-footer__pill{
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  border-radius: 999px;
  background: rgba(255,255,255,.08);
  border: 1px solid rgba(255,255,255,.14);
  font-weight: 800;
  color: rgba(255,255,255,.84);
  width: fit-content;
}

.sn-footer__pill .dot{
  width: 10px;
  height: 10px;
  border-radius: 999px;
  background: linear-gradient(135deg, #e53900, #ff5a1f);
  box-shadow: 0 0 0 6px rgba(229,57,0,.14);
}

.sn-footer__title{
  margin: 6px 0 12px;
  font-size: 14px;
  font-weight: 900;
  color: #fff;
  letter-spacing: .2px;
}

.sn-footer__links{
  list-style: none;
  padding: 0;
  margin: 0;
  display: grid;
  gap: 10px;
}

.sn-footer__links a{
  text-decoration: none;
  color: rgba(255,255,255,.76);
  font-weight: 750;
  font-size: 13px;
  padding: 8px 10px;
  border-radius: 12px;
  display: inline-flex;
  align-items: center;
  gap: 10px;
  width: fit-content;
  transition: background .18s ease, transform .18s ease, color .18s ease;
}

.sn-footer__links a:hover{
  background: rgba(255,255,255,.08);
  color: #fff;
  transform: translateY(-1px);
}

.sn-footer__contact{
  display: grid;
  gap: 10px;
  font-weight: 750;
  color: rgba(255,255,255,.78);
  font-size: 13px;
}

.sn-footer__row{
  display: flex;
  gap: 10px;
  align-items: center;
}

.sn-ico{
  width: 28px;
  height: 28px;
  display: grid;
  place-items: center;
  border-radius: 10px;
  background: rgba(255,255,255,.08);
  border: 1px solid rgba(255,255,255,.12);
  color: rgba(255,255,255,.92);
  font-weight: 900;
}

.sn-footer__social{
  display: flex;
  gap: 10px;
  margin-top: 12px;
}

.sn-footer__social a{
  width: 40px;
  height: 40px;
  border-radius: 14px;
  display: grid;
  place-items: center;
  text-decoration: none;
  font-weight: 900;
  color: #fff;
  background: rgba(255,255,255,.10);
  border: 1px solid rgba(255,255,255,.14);
  transition: transform .18s ease, background .18s ease;
}

.sn-footer__social a:hover{
  transform: translateY(-2px);
  background: linear-gradient(135deg, rgba(229,57,0,.85), rgba(255,90,31,.75));
}

.sn-footer__bottom{
  padding-top: 14px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  font-weight: 750;
  font-size: 13px;
  color: rgba(255,255,255,.72);
}

@media (max-width: 640px){
  .sn-footer__bottom{
    flex-direction: column;
    align-items: flex-start;
  }
}

.sn-footer__mini{
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.sn-footer__mini .heart{
  width: 10px;
  height: 10px;
  border-radius: 999px;
  background: linear-gradient(135deg, #e53900, #ff5a1f);
  box-shadow: 0 0 0 6px rgba(229,57,0,.14);
}

</style>
</head>

<body>

<nav class="navbar">
  <div class="logo">
    <div class="logo-badge"></div>
    <a href="index.php">  PahunaStay </a>
  </div>

  <ul class="nav-links">
    <li><a href="index.php">Home</a></li>
    <li><a href="room.php">Available Rooms</a></li>
    <li><a href="#footer">Contact</a></li>
  </ul>

  <div class="nav-user">
    <?php if (!empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
      <a href="dashboard.php" class="user-link" title="Dashboard">
        <div class="avatar"><?php echo safe($initial); ?></div>
      </a>
    <?php else: ?>
      <button class="login-btn"><a href="login.php">Login</a></button>
    <?php endif; ?>
  </div>
</nav>

<div class="wrap">

  <section class="hero">
    <div class="hero-inner">
      <div class="hero-title">
        <div>
          <h1>Available Rooms</h1>
          <p>Browse verified rooms, view details, and book quickly with QR verification.</p>
        </div>
        <div class="hero-pill">
          <span>Rooms</span>
          <b><?php echo (int)($rooms ? $rooms->num_rows : 0); ?></b>
        </div>
      </div>

      <div class="filterbar">
        <div class="search">
          <input id="searchInput" type="text" placeholder="Search by room name or location...">
          <button type="button" onclick="filterRooms()">Search</button>
        </div>
        <div class="small-info">
          <span>Tip:</span>
          <span>Click “View Details” to see map, contact, and booking.</span>
        </div>
      </div>
    </div>
  </section>

  <div class="grid" id="roomsGrid">
    <?php
    if ($rooms && $rooms->num_rows > 0):
      while ($row = $rooms->fetch_assoc()):
        $rid = (int)$row['id'];
        $name = $row['room_name'] ?? '';
        $loc  = $row['location'] ?? '';
        $phone = $row['phone'] ?? '';
        $price = $row['price'] ?? '';
        $img  = $row['image'] ?? '';
        $imgSrc = "uploads/" . $img;
        if (!$img || !file_exists($imgSrc)) $imgSrc = "images/default.jpg";

        // IMPORTANT: Your details page name must match your real file.
        // If your file is room_details.php, keep it. If it is room_detail.php, change here.
       // auto-detect details page file
$detailsFile = file_exists("room_details.php") ? "room_details.php" : "room_detail.php";
$detailsPage = $detailsFile . "?id=" . $rid;

    ?>
      <div class="room-card" data-name="<?php echo safe(strtolower($name)); ?>" data-location="<?php echo safe(strtolower($loc)); ?>">
        <div class="room-img">
          <span class="badge">Verified</span>
          <img src="<?php echo safe($imgSrc); ?>" alt="<?php echo safe($name); ?>">
          <div class="priceTag">
            <small>Per Night</small>
            Rs. <?php echo safe($price); ?>
          </div>
        </div>

        <div class="room-body">
          <h3 class="room-title"><?php echo safe($name); ?></h3>
          <div class="meta">
            <div><span>Location:</span> <b><?php echo safe($loc); ?></b></div>
            <div><span>Phone:</span> <b><?php echo safe($phone); ?></b></div>
          </div>

          <div class="actions">
            <a class="btn btn-primary" href="<?php echo safe($detailsPage); ?>">View Details</a>
            <a class="btn btn-ghost" href="room.php">Refresh</a>
          </div>
        </div>
      </div>
    <?php
      endwhile;
    else:
    ?>
      <div style="padding:18px; font-weight:800; color:var(--muted);">
        No rooms found.
      </div>
    <?php endif; ?>
  </div>

</div>

<!-- LOGIN MODAL (kept from your version) -->
<div id="loginModal" class="login-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.6); align-items:center; justify-content:center; z-index:2000;">
  <div class="login-box" style="background:white; padding:22px; border-radius:14px; width:320px; position:relative; text-align:center;">
    <h2 style="margin:0 0 14px;">Login</h2>
    <form action="login.php" method="POST">
      <input style="width:100%; padding:10px; margin-bottom:10px; border-radius:10px; border:1px solid #d1d5db;" type="email" name="email" placeholder="Email" required>
      <input style="width:100%; padding:10px; margin-bottom:12px; border-radius:10px; border:1px solid #d1d5db;" type="password" name="password" placeholder="Password" required>
      <button class="login-btn" type="submit" style="width:100%;">Login</button>
    </form>
    <span onclick="closeLogin()" style="position:absolute; top:10px; right:14px; cursor:pointer; font-size:20px;">×</span>
  </div>
</div>

<footer id="footer" class="sn-footer">
  <div class="sn-footer__wrap">

    <div class="sn-footer__top">
      <!-- Brand -->
      <div class="sn-footer__col">
        <div class="sn-footer__brand">
          <div class="sn-footer__logo"></div>
          <div>
            <div class="sn-footer__name">PahunaStay</div>
            <div class="sn-footer__tag">Verified room booking • Faster check-in</div>
          </div>
        </div>

        <p class="sn-footer__text">
          PahunaStay helps you book trusted rooms with transparent pricing, direct contact, location access, and QR-based confirmation for smoother check-ins.
        </p>

        <div class="sn-footer__pill">
          <span class="dot"></span>
          <span>Secure • Simple • Reliable</span>
        </div>
      </div>

      <!-- Quick Links -->
      <div class="sn-footer__col">
        <h4 class="sn-footer__title">Quick Links</h4>
        <ul class="sn-footer__links">
          <li><a href="index.php">Home</a></li>
          <li><a href="room.php">Available Rooms</a></li>
          <li><a href="#contact">Contact</a></li>
          <li><a href="upload.php">Upload Listing</a></li>
        </ul>
      </div>

      <!-- Support -->
      <div class="sn-footer__col">
        <h4 class="sn-footer__title">Support</h4>
        <ul class="sn-footer__links">
          <li><a href="#">Help Center</a></li>
          <li><a href="#">Booking Guide</a></li>
          <li><a href="#">Terms & Policy</a></li>
          <li><a href="#">Report Listing</a></li>
        </ul>
      </div>

      <!-- Contact -->
      <div class="sn-footer__col">
        <h4 class="sn-footer__title" id="contact">Contact</h4>

        <div class="sn-footer__contact">
          <div class="sn-footer__row">
            <span class="sn-ico">📍</span>
            <span>Lainchour,Kathmandu</span>
          </div>
          <div class="sn-footer__row">
            <span class="sn-ico">☎</span>
            <span>+977 9841745236</span>
          </div>
          <div class="sn-footer__row">
            <span class="sn-ico">✉</span>
            <span>aashishmaharjan48@gmail.com</span>
          </div>
        </div>

        <div class="sn-footer__social">
          <a href="#" aria-label="Facebook" title="Facebook"><i class="fa-brands fa-facebook"></i></a>
          <a href="https://www.instagram.com/hamropaunaghar" aria-label="Instagram" title="Instagram" target="_blank"><i class="fa-brands fa-instagram"></i></a>
          <a href="#" aria-label="X" title="X">x</a>
        </div>
      </div>
    </div>

    <div class="sn-footer__bottom">
      <div>© <?php echo date("Y"); ?> Mrzn's. All rights reserved.</div>
      <div class="sn-footer__mini">
        Built with <span class="heart">●</span> for smooth stays
      </div>
    </div>

  </div>
</footer>

<script>
function openLogin(){ document.getElementById("loginModal").style.display = "flex"; }
function closeLogin(){ document.getElementById("loginModal").style.display = "none"; }

function filterRooms(){
  const q = (document.getElementById("searchInput").value || "").trim().toLowerCase();
  const cards = document.querySelectorAll(".room-card");
  cards.forEach(c => {
    const name = c.getAttribute("data-name") || "";
    const loc  = c.getAttribute("data-location") || "";
    const show = (q === "") || name.includes(q) || loc.includes(q);
    c.style.display = show ? "" : "none";
  });
}

// Enter key triggers search
document.getElementById("searchInput").addEventListener("keydown", function(e){
  if (e.key === "Enter") {
    e.preventDefault();
    filterRooms();
  }
});

</script>

</body>
</html>
<?php $conn->close(); ?>
