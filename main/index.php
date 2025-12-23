<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "db.php";

function safe($v) { return htmlspecialchars($v ?? "", ENT_QUOTES, "UTF-8"); }

$userId   = $_SESSION['user_id'] ?? '';
$fullName = $_SESSION['fullname'] ?? '';
$loggedIn = $_SESSION['logged_in'] ?? false;

$initial = strtoupper(substr($fullName ?: 'U', 0, 1));

// Fetch 8 rooms
$sql = "SELECT * FROM rooms ORDER BY id DESC LIMIT 8";
$rooms = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PahunaStay</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
:root{
  --brand:#e53900;
  --brand2:#ff5a1f;
  --ink:#0b1220;
  --muted:#6b7280;
  --card: rgba(255,255,255,.86);
  --border: rgba(17,24,39,.10);
  --shadow: 0 24px 70px rgba(0,0,0,.14);
  --radius: 18px;
}

*{box-sizing:border-box}
html{scroll-behavior:smooth}
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

/* NAVBAR */
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

.nav-links{display:flex; list-style:none; gap:14px; padding:0; margin:0;}
.nav-links a{
  color:rgba(255,255,255,.92);
  text-decoration:none;
  font-weight:600;
  font-size:14px;
  padding:10px 12px;
  border-radius:10px;
}
.nav-links a:hover{ background: rgba(255,255,255,.10); }

.nav-right{ display:flex; align-items:center; gap:12px; }

/* Upload button */
.upload-btn{
  display:inline-flex; align-items:center; gap:10px;
  padding:10px 14px;
  border-radius:12px;
  border:1px solid rgba(255,255,255,.14);
  background: rgba(255,255,255,.08);
  color:#fff;
  font-weight:800;
  cursor:pointer;
  transition: background .15s ease, transform .15s ease;
}
.upload-btn:hover{ background: rgba(255,255,255,.12); transform: translateY(-1px); }

/* Login button */
.login-btn{
  background: linear-gradient(135deg,var(--brand),var(--brand2));
  color:#fff;
  border:none;
  padding:10px 16px;
  border-radius:12px;
  font-weight:900;
  cursor:pointer;
  box-shadow:0 14px 28px rgba(229,57,0,.18);
}
.login-btn:hover{ filter:brightness(.98); }

/* User avatar */
.user-link{ text-decoration:none; display:flex; align-items:center; }
.avatar{
  width:42px;height:42px;border-radius:50%;
  background: linear-gradient(135deg,var(--brand),var(--brand2));
  color:#fff; display:grid; place-items:center;
  font-weight:900;
  box-shadow:0 10px 24px rgba(0,0,0,.25);
}

/* WRAPPER */
.wrap{
  width:min(1150px, 92%);
  margin: 22px auto 70px;
}

/* HERO */
.hero{
  border-radius: 26px;
  overflow:hidden;
  position:relative;
  box-shadow: var(--shadow);
  min-height: 320px;
}
.hero::before{
  content:"";
  position:absolute; inset:0;
  background:
    linear-gradient(135deg, rgba(229,57,0,.42), rgba(255,90,31,.18)),
    url('images/hover3.jpg') center/cover no-repeat;
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
  background: linear-gradient(180deg, rgba(0,0,0,.20), rgba(0,0,0,.55));
}
.hero-inner{
  position:relative;
  z-index:2;
  padding: 34px 26px;
  color:#fff;
  display:flex;
  flex-direction:column;
  gap: 14px;
}
.hero h1{
  margin:0;
  font-size: 34px;
  letter-spacing:-.3px;
}
.hero p{
  margin: 0;
  max-width: 720px;
  color: rgba(255,255,255,.88);
  font-weight:600;
  line-height:1.6;
}
.hero-actions{
  display:flex;
  gap: 12px;
  flex-wrap:wrap;
  margin-top: 6px;
}
.btn{
  display:inline-flex; align-items:center; justify-content:center; gap:10px;
  padding: 12px 16px;
  border-radius: 14px;
  font-weight:900;
  text-decoration:none;
  border: 1px solid rgba(255,255,255,.18);
  transition: transform .15s ease, box-shadow .15s ease, background .15s ease;
}
.btn:active{ transform: translateY(1px); }
.btn-primary{
  border:none;
  color:#fff;
  background: linear-gradient(135deg,var(--brand),var(--brand2));
  box-shadow: 0 16px 34px rgba(229,57,0,.20);
}
.btn-primary:hover{ transform: translateY(-1px); box-shadow: 0 22px 50px rgba(229,57,0,.26); }
.btn-ghost{
  color:#fff;
  background: rgba(255,255,255,.10);
}
.btn-ghost:hover{ background: rgba(255,255,255,.14); }

/* SECTION HEAD */
.section-head{
  margin-top: 18px;
  display:flex;
  align-items:flex-end;
  justify-content:space-between;
  gap: 14px;
  flex-wrap:wrap;
}
.section-head h2{
  margin:0;
  font-size: 18px;
  letter-spacing:-.2px;
}
.section-head p{
  margin: 6px 0 0;
  color: var(--muted);
  font-weight: 650;
}
.pill{
  display:inline-flex; align-items:center; gap:10px;
  padding:10px 14px;
  border-radius:999px;
  background: rgba(255,255,255,.70);
  border: 1px solid rgba(17,24,39,.06);
  box-shadow: 0 10px 30px rgba(0,0,0,.06);
  font-weight:900;
}
.pill span{ color: var(--muted); }

/* ROOMS GRID */
.grid{
  margin-top: 14px;
  display:grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}
@media (max-width: 1050px){ .grid{ grid-template-columns: repeat(3, 1fr);} }
@media (max-width: 820px){ .grid{ grid-template-columns: repeat(2, 1fr);} }
@media (max-width: 560px){ .grid{ grid-template-columns: 1fr;} .nav-links{display:none;} .navbar{padding:12px 16px;} }

.room-card{
  border-radius: 22px;
  overflow:hidden;
  background: var(--card);
  border: 1px solid var(--border);
  box-shadow: 0 18px 55px rgba(0,0,0,.10);
  transition: transform .18s ease, box-shadow .18s ease;
}
.room-card:hover{
  transform: translateY(-4px);
  box-shadow: 0 28px 70px rgba(0,0,0,.14);
}
.room-img{
  position:relative;
  height: 170px;
  background:#ddd;
  overflow:hidden;
}
.room-img img{
  width:100%;
  height:100%;
  object-fit:cover;
  transition: transform 1.1s ease;
}
.room-card:hover .room-img img{ transform: scale(1.08); }

.badge{
  position:absolute; left:12px; top:12px;
  padding: 8px 10px;
  border-radius: 999px;
  font-weight: 900;
  font-size: 12px;
  color:#fff;
  background: rgba(229,57,0,.92);
  box-shadow: 0 14px 30px rgba(229,57,0,.22);
}
.priceTag{
  position:absolute; right:12px; bottom:12px;
  padding: 10px 12px;
  border-radius: 16px;
  background: rgba(0,0,0,.40);
  border: 1px solid rgba(255,255,255,.20);
  color:#fff;
  backdrop-filter: blur(10px);
  font-weight: 900;
}
.priceTag small{
  display:block;
  font-weight:800;
  color: rgba(255,255,255,.85);
  font-size: 12px;
  margin-bottom: 2px;
}

.room-body{ padding: 14px 14px 16px; }
.room-title{ margin:0 0 6px; font-weight:900; font-size: 15px; letter-spacing:-.2px; }
.meta{ display:grid; gap:6px; color: var(--muted); font-weight:700; font-size: 13px; }
.meta b{ color: var(--ink); font-weight:900; }

.actions{ display:flex; gap:10px; margin-top:12px; }
.card-btn{
  flex:1;
  display:inline-flex; align-items:center; justify-content:center; gap:10px;
  padding: 10px 12px;
  border-radius: 14px;
  font-weight:900;
  text-decoration:none;
  border: 1px solid rgba(17,24,39,.10);
  background: rgba(17,24,39,.06);
  color: var(--ink);
}
.card-btn:hover{ background: rgba(17,24,39,.08); }
.card-btn.primary{
  border:none;
  color:#fff;
  background: linear-gradient(135deg,var(--brand),var(--brand2));
  box-shadow: 0 16px 34px rgba(229,57,0,.16);
}

/* TEAM */
.team{
  margin-top: 22px;
  border-radius: 26px;
  overflow:hidden;
  background: rgba(255,255,255,.72);
  border: 1px solid rgba(17,24,39,.08);
  box-shadow: 0 18px 55px rgba(0,0,0,.08);
}
.team-head{ padding: 18px 18px 12px; border-bottom: 1px solid rgba(17,24,39,.08); }
.team-head h3{ margin:0; font-size: 18px; letter-spacing:-.2px; }
.team-head p{ margin: 6px 0 0; color: var(--muted); font-weight:650; }
.team-grid{
  padding: 16px 18px 18px;
  display:grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 14px;
}
@media (max-width: 1000px){ .team-grid{ grid-template-columns: repeat(2, 1fr);} }
@media (max-width: 560px){ .team-grid{ grid-template-columns: 1fr;} }

.team-card{
  background:#fff;
  border-radius: 20px;
  border: 1px solid rgba(17,24,39,.10);
  overflow:hidden;
  box-shadow: 0 14px 40px rgba(0,0,0,.08);
}
.team-card img{
  width:100%;
  height: 150px;
  object-fit:cover;
  display:block;
}
.team-info{ padding: 12px 12px 14px; }
.team-info h4{ margin:0; font-weight:900; }
.team-info span{ display:block; margin-top:4px; color: var(--muted); font-weight:800; font-size: 13px; }
.team-info p{ margin: 8px 0 0; color:#374151; font-weight:650; font-size: 13px; line-height:1.55; }

/* FOOTER */
.footer-bottom{
  background:#333;
  color:#fff;
  text-align:center;
  padding: 14px 0;
  font-weight:700;
  opacity:.95;
  margin-top: 22px;
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
    <a href="index.php">PahunaStay</a>
  </div>

  <ul class="nav-links">
    <li><a href="index.php">Home</a></li>
    <li><a href="room.php#rooms-container">Available Rooms</a></li>
    <li><a href="#footer">Contact</a></li>
  </ul>

  <div class="nav-right">
    <button class="upload-btn" onclick="checkUpload()" type="button">
      <i class="fa-solid fa-cloud-arrow-up"></i> Upload
    </button>

    <?php if ($loggedIn): ?>
      <a href="dashboard.php" class="user-link" title="Dashboard">
        <div class="avatar"><?php echo safe($initial); ?></div>
      </a>
    <?php else: ?>
      <button class="login-btn" onclick="window.location.href='login.php'">Log In</button>
    <?php endif; ?>
  </div>
</nav>

<div class="wrap">

  <header class="hero">
    <div class="hero-inner">
      <h1>PahunaStay</h1>
      <p>PahunaStay makes room booking simple and reliable with verified listings, transparent pricing, direct contact, and easy location access. Discover comfortable stays, book instantly, and enjoy a smooth, secure check-in experience designed for modern travelers.</p>
      <div class="hero-actions">
        <a href="room.php#rooms-container" class="btn btn-primary">
          <i class="fa-solid fa-bed"></i> Book Now
        </a>
        <a href="#rooms-container" class="btn btn-ghost">
          <i class="fa-solid fa-magnifying-glass"></i> Explore Rooms
        </a>
      </div>
    </div>
  </header>

  <div class="section-head" id="rooms-container">
    <div>
      <h2>Top Rooms</h2>
      <p>Popular rooms from the latest listings. View details and book securely.</p>
    </div>
    <div class="pill">
      <span>Showing</span>
      <b><?php echo (int)($rooms ? $rooms->num_rows : 0); ?></b>
    </div>
  </div>

  <div class="grid">
    <?php if ($rooms && $rooms->num_rows > 0): ?>
      <?php while ($row = $rooms->fetch_assoc()): 
        $rid   = (int)$row['id'];
        $name  = $row['room_name'] ?? '';
        $loc   = $row['location'] ?? '';
        $phone = $row['phone'] ?? '';
        $price = $row['price'] ?? '';
        $img   = $row['image'] ?? '';
        $imgSrc = "uploads/" . $img;
        if (!$img || !file_exists($imgSrc)) $imgSrc = "images/default.jpg";

        // IMPORTANT: use the filename you actually have
        $detailsPage = "room_detail.php?id=" . $rid; 
      ?>
        <div class="room-card">
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
              <a class="card-btn primary" href="<?php echo safe($detailsPage); ?>">
                View Details
              </a>
              <a class="card-btn" href="room.php">
                All Rooms
              </a>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <div style="padding:14px; font-weight:800; color:var(--muted);">
        No rooms found.
      </div>
    <?php endif; ?>
  </div>

  <section class="team">
    <div class="team-head">
      <h3>Meet Our Team</h3>
      <p>People behind PahunaStay — building a smooth booking experience.</p>
    </div>

    <div class="team-grid">
      <div class="team-card">
        <img src="uploads/team1.jpg" alt="Team Member">
        <div class="team-info">
          <h4>Member One</h4>
          <span>Frontend Developer</span>
          <p>Builds clean, responsive interfaces with a strong focus on usability.</p>
        </div>
      </div>

      <div class="team-card">
        <img src="uploads/team2.jpg" alt="Team Member">
        <div class="team-info">
          <h4>Member Two</h4>
          <span>Backend Developer</span>
          <p>Handles server-side logic, security, and reliable booking workflows.</p>
        </div>
      </div>

      <div class="team-card">
        <img src="uploads/team3.jpg" alt="Team Member">
        <div class="team-info">
          <h4>Member Three</h4>
          <span>UI / UX Designer</span>
          <p>Designs modern layouts and improves user experience across the platform.</p>
        </div>
      </div>

      <div class="team-card">
        <img src="uploads/team4.jpg" alt="Team Member">
        <div class="team-info">
          <h4>Member Four</h4>
          <span>Database Manager</span>
          <p>Maintains data integrity and optimizes system performance.</p>
        </div>
      </div>
    </div>
  </section>

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
            <span>Kathmandu, Nepal</span>
          </div>
          <div class="sn-footer__row">
            <span class="sn-ico">☎</span>
            <span>+977 98XXXXXXXX</span>
          </div>
          <div class="sn-footer__row">
            <span class="sn-ico">✉</span>
            <span>support@PahunaStay.com</span>
          </div>
        </div>

        <div class="sn-footer__social">
          <a href="#" aria-label="Facebook" title="Facebook" target="_blank"><i class="fa-brands fa-facebook"></i></a>
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
function checkUpload() {
  let isLoggedIn = <?php echo $loggedIn ? 'true' : 'false'; ?>;
  if (!isLoggedIn) {
    alert("Please log in before uploading.");
    window.location.href = "login.php";
  } else {
    window.location.href = "upload.php";
  }
}
</script>

</body>
</html>
<?php $conn->close(); ?>
