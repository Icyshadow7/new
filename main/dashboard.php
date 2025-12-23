<?php
session_start();
include "db.php";

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== TRUE) {
    header("Location: login.php");
    exit();
}

$userId   = (int)($_SESSION['user_id'] ?? 0);
$fullName = $_SESSION['fullname'] ?? 'User';
$email    = $_SESSION['email'] ?? '';

if ($userId <= 0) {
    header("Location: login.php");
    exit();
}

/* Fetch latest profile info from DB (so dashboard shows updated phone/address/image) */
$stmt = $conn->prepare("SELECT fullname, email, phone, address, profile_image FROM users WHERE id=? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

/* Fallbacks */
$dbFullName = $user['fullname'] ?? $fullName;
$dbEmail    = $user['email'] ?? $email;
$phone      = $user['phone'] ?? '';
$address    = $user['address'] ?? '';
$profileImg = $user['profile_image'] ?? '';

/* Keep session name/email synced */
$_SESSION['fullname'] = $dbFullName;
$_SESSION['email']    = $dbEmail;

/* Build image path */
$imgPath = (!empty($profileImg)) ? "uploads/profile/" . $profileImg : "";
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
      --card:rgba(255,255,255,.92);
      --muted:#6b7280;
      --text:#0b1220;
      --brand:#e53900;
      --brand2:#ff5a1f;
      --shadow: 0 18px 55px rgba(0,0,0,.18);
      --radius: 18px;
    }
    *{box-sizing:border-box}
    body{
      margin:0;
      font-family:"Inter",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
      color:var(--text);
      background:
        radial-gradient(1200px 600px at 10% 10%, rgba(229,57,0,.18), transparent 55%),
        radial-gradient(900px 500px at 95% 25%, rgba(255,90,31,.16), transparent 55%),
        linear-gradient(180deg, #f6f7fb, #eef1f8);
      min-height:100vh;
      display:flex;
      justify-content:center;
      padding:40px 16px;
    }
    .wrap{width:min(980px,100%)}

    .topbar{
      display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:18px;
    }
    .brand{
      display:flex;align-items:center;gap:10px;font-weight:800;letter-spacing:.4px;color:#111827;
    }
    .brand-badge{
    
  width:40px;
  height:40px;
  border-radius:14px;
  background: url("images/lg.png") center / cover no-repeat;
  box-shadow: 0 12px 30px rgba(229,57,0,.22);
}
    
    .pill{
      display:flex;align-items:center;gap:10px;
      background:rgba(255,255,255,.7);
      border:1px solid rgba(17,24,39,.06);
      padding:10px 14px;border-radius:999px;
      box-shadow:0 10px 30px rgba(0,0,0,.06);
    }
    .pill small{color:var(--muted);font-weight:600}

    .card{
      background:var(--card);
      border:1px solid rgba(17,24,39,.08);
      border-radius:var(--radius);
      box-shadow:var(--shadow);
      overflow:hidden;
    }

    .hero{
      padding:26px 28px;
      background:
        radial-gradient(900px 240px at 30% 0%, rgba(229,57,0,.15), transparent 60%),
        linear-gradient(180deg, rgba(255,255,255,.9), rgba(255,255,255,.78));
      border-bottom:1px solid rgba(17,24,39,.08);
      display:flex;align-items:center;justify-content:space-between;gap:18px;
    }
    .hero-left{display:flex;align-items:center;gap:14px}

    /* Avatar supports image */
    .avatar{
      width:56px;height:56px;border-radius:16px;
      background:linear-gradient(135deg,var(--brand),var(--brand2));
      display:grid;place-items:center;color:#fff;font-weight:900;
      box-shadow:0 14px 26px rgba(229,57,0,.22);
      user-select:none;overflow:hidden;
    }
    .avatar img{width:100%;height:100%;object-fit:cover;display:block}

    .hero h1{margin:0;font-size:22px;line-height:1.2;letter-spacing:-.3px}
    .hero p{margin:6px 0 0;color:var(--muted);font-weight:500;font-size:14px}

    .hero-actions{display:flex;gap:10px;flex-wrap:wrap;justify-content:flex-end}

    .btn{
      border:0;cursor:pointer;padding:11px 14px;border-radius:12px;
      font-weight:700;font-size:14px;transition:transform .15s ease, box-shadow .15s ease, background .15s ease;
      text-decoration:none;display:inline-flex;align-items:center;gap:10px;justify-content:center;white-space:nowrap;
    }
    .btn:active{transform:translateY(1px)}
    .btn-primary{
      background:linear-gradient(135deg,var(--brand),var(--brand2));
      color:#fff;box-shadow:0 14px 28px rgba(229,57,0,.22);
    }
    .btn-primary:hover{box-shadow:0 18px 40px rgba(229,57,0,.28);transform:translateY(-1px)}
    .btn-ghost{
      background:rgba(17,24,39,.06);
      color:#111827;border:1px solid rgba(17,24,39,.08);
    }
    .btn-ghost:hover{background:rgba(17,24,39,.08);transform:translateY(-1px)}
    .danger{
      background:linear-gradient(135deg,#b91c1c,#ef4444);
      box-shadow:0 14px 28px rgba(185,28,28,.18);
      color:#fff;
    }
    .danger:hover{box-shadow:0 18px 40px rgba(185,28,28,.26);transform:translateY(-1px)}

    .content{padding:22px 28px 28px}
    .grid{display:grid;grid-template-columns:1.2fr .8fr;gap:18px;margin-top:6px}

    .section-title{display:flex;align-items:center;justify-content:space-between;margin:6px 0 14px}
    .section-title h2{margin:0;font-size:18px;letter-spacing:-.2px}
    .hint{
      font-size:12px;color:var(--muted);font-weight:600;
      background:rgba(229,57,0,.08);
      border:1px solid rgba(229,57,0,.16);
      padding:6px 10px;border-radius:999px;
    }

    .info-card{
      background:rgba(255,255,255,.65);
      border:1px solid rgba(17,24,39,.08);
      border-radius:16px;
      padding:16px;
      box-shadow:0 10px 26px rgba(0,0,0,.06);
    }

    .row{
      display:flex;align-items:flex-start;justify-content:space-between;gap:12px;
      padding:12px 10px;border-radius:12px;
    }
    .row + .row{
      border-top:1px dashed rgba(17,24,39,.12);
      border-radius:0;
    }
    .label{color:var(--muted);font-weight:700;font-size:13px}
    .value{font-weight:800;color:#0b1220;text-align:right;font-size:14px;word-break:break-word}
    .value.email{color:var(--brand)}

    .mini{display:grid;gap:12px}
    .stat{
      display:flex;align-items:center;justify-content:space-between;
      padding:14px 14px;border-radius:16px;
      background:linear-gradient(180deg, rgba(255,255,255,.75), rgba(255,255,255,.55));
      border:1px solid rgba(17,24,39,.08);
      box-shadow:0 10px 26px rgba(0,0,0,.05);
    }
    .stat strong{font-size:13px;color:var(--muted);font-weight:800}
    .stat span{font-weight:900;font-size:14px}

    .footer-actions{display:flex;gap:12px;margin-top:16px;flex-wrap:wrap}

    @media (max-width:820px){
      .grid{grid-template-columns:1fr}
      .hero{flex-direction:column;align-items:flex-start}
      .hero-actions{width:100%;justify-content:flex-start}
    }
  </style>
</head>

<body>
  <div class="wrap">

    <div class="topbar">
      <div class="brand">
        <div class="brand-badge"></div>
       PahunaStay
      </div>

      <div class="pill">
        <small>Signed in as</small>
        <strong><?php echo htmlspecialchars($dbFullName); ?></strong>
      </div>
    </div>

    <div class="card">
      <div class="hero">
        <div class="hero-left">

          <div class="avatar">
            <?php if ($imgPath && file_exists($imgPath)): ?>
              <img src="<?php echo htmlspecialchars($imgPath); ?>" alt="Profile">
            <?php else: ?>
              <?php echo strtoupper(substr($dbFullName, 0, 1)); ?>
            <?php endif; ?>
          </div>

          <div>
            <h1>Welcome back, <?php echo htmlspecialchars($dbFullName); ?></h1>
            <p>Manage your profile and bookings from your dashboard.</p>
          </div>
        </div>

        <div class="hero-actions">
          <a class="btn btn-ghost" href="index.php">Back to Home</a>
          <a class="btn btn-primary" href="room.php">Book a Room</a>
        </div>
      </div>

      <div class="content">
        <div class="grid">

          <!-- Account Details -->
          <div class="info-card">
            <div class="section-title">
              <h2>Account Details</h2>
              <span class="hint">PahunaStay Profile</span>
            </div>

            <div class="row">
              <div class="label">Full Name</div>
              <div class="value"><?php echo htmlspecialchars($dbFullName); ?></div>
            </div>

            <div class="row">
              <div class="label">User ID</div>
              <div class="value"><?php echo htmlspecialchars($userId); ?></div>
            </div>

            <div class="row">
              <div class="label">Email</div>
              <div class="value email"><?php echo htmlspecialchars($dbEmail); ?></div>
            </div>

            <div class="row">
              <div class="label">Phone</div>
              <div class="value"><?php echo htmlspecialchars($phone ?: "Not set"); ?></div>
            </div>

            <div class="row">
              <div class="label">Address</div>
              <div class="value"><?php echo htmlspecialchars($address ?: "Not set"); ?></div>
            </div>

            <div class="footer-actions">
              <a class="btn btn-primary" href="edit_profile.php">Edit Profile</a>
              <a class="btn danger" href="logout.php">Log Out</a>
            </div>
          </div>

          <!-- Side Cards -->
          <div class="mini">
            <div class="stat">
              <strong>Status</strong>
              <span>Active</span>
            </div>

            <div class="stat">
              <strong>Account Type</strong>
              <span>User</span>
            </div>

            <div class="stat">
              <strong>Security</strong>
              <span>Protected</span>
            </div>

            <div class="info-card">
              <div class="section-title" style="margin-top:0;">
                <h2>Tips</h2>
                <span class="hint">Useful</span>
              </div>
              <p style="margin:0;color:var(--muted);font-weight:600;font-size:13px;line-height:1.6;">
                Update your profile (phone/address/photo) to make booking faster.
                Browse available rooms anytime from the button above.
              </p>
            </div>
          </div>

        </div>
      </div>
    </div>

  </div>
</body>
</html>
