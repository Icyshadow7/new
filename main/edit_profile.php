<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include "db.php";

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
    die("Session user_id not found. Please login again.");
}

$success = "";
$error = "";

/* Check if columns exist (prevents SQL crash) */
$cols = [];
$res = $conn->query("SHOW COLUMNS FROM users");
while ($r = $res->fetch_assoc()) $cols[] = $r['Field'];

$hasPhone = in_array("phone", $cols);
$hasAddress = in_array("address", $cols);
$hasImg = in_array("profile_image", $cols);

if (!$hasPhone || !$hasAddress || !$hasImg) {
    $missing = [];
    if (!$hasPhone) $missing[] = "phone";
    if (!$hasAddress) $missing[] = "address";
    if (!$hasImg) $missing[] = "profile_image";
    $error = "Missing columns in users table: " . implode(", ", $missing) .
             ". Please run ALTER TABLE query to add them.";
}

/* Fetch user */
if ($error === "") {
    $stmt = $conn->prepare("SELECT id, fullname, email, phone, address, profile_image FROM users WHERE id=?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        die("User not found in database.");
    }
} else {
    // minimal fallback to avoid undefined variable
    $user = [
        "fullname" => $_SESSION["fullname"] ?? "User",
        "email" => $_SESSION["email"] ?? "",
        "phone" => "",
        "address" => "",
        "profile_image" => ""
    ];
}

function safe($v){ return htmlspecialchars($v ?? "", ENT_QUOTES, 'UTF-8'); }

if ($_SERVER["REQUEST_METHOD"] === "POST" && $error === "") {
    $fullname = trim($_POST["fullname"] ?? "");
    $phone    = trim($_POST["phone"] ?? "");
    $address  = trim($_POST["address"] ?? "");

    if ($fullname === "") {
        $error = "Full name is required.";
    }

    $newImageName = $user["profile_image"];

    if ($error === "" && !empty($_FILES["profile_image"]["name"])) {
        $targetDir = "uploads/profile/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $tmp  = $_FILES["profile_image"]["tmp_name"];
        $size = (int)$_FILES["profile_image"]["size"];
        $ext  = strtolower(pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION));
        $allowed = ["jpg","jpeg","png","webp"];

        if (!in_array($ext, $allowed)) {
            $error = "Only JPG, JPEG, PNG, WEBP allowed.";
        } elseif ($size > 2 * 1024 * 1024) {
            $error = "Image must be less than 2MB.";
        } else {
            $newImageName = "user_" . $userId . "_" . time() . "." . $ext;
            $path = $targetDir . $newImageName;

            if (!move_uploaded_file($tmp, $path)) {
                $error = "Upload failed.";
            } else {
                // delete old
                if (!empty($user["profile_image"])) {
                    $old = $targetDir . $user["profile_image"];
                    if (file_exists($old)) @unlink($old);
                }
            }
        }
    }

    if ($error === "") {
        $stmt = $conn->prepare("UPDATE users SET fullname=?, phone=?, address=?, profile_image=? WHERE id=?");
        $stmt->bind_param("ssssi", $fullname, $phone, $address, $newImageName, $userId);

        if ($stmt->execute()) {
            $success = "Profile updated successfully.";
            $_SESSION["fullname"] = $fullname;

            // refresh display
            $user["fullname"] = $fullname;
            $user["phone"] = $phone;
            $user["address"] = $address;
            $user["profile_image"] = $newImageName;
        } else {
            $error = "Update failed: " . $stmt->error;
        }
        $stmt->close();
    }
}

$imgPath = (!empty($user["profile_image"])) ? "uploads/profile/" . $user["profile_image"] : "";
$letter = strtoupper(substr($user["fullname"], 0, 1));
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Edit Profile</title>
<style>
  body{margin:0;font-family:Arial;background:#f4f6fb;display:flex;justify-content:center;padding:40px 16px}
  .card{width:min(780px,100%);background:#fff;border-radius:16px;box-shadow:0 12px 40px rgba(0,0,0,.12);padding:22px}
  .msg{padding:10px 12px;border-radius:10px;margin:10px 0;font-weight:700}
  .ok{background:#e8fff1;color:#0b7a3b;border:1px solid #b6f0cd}
  .bad{background:#ffecec;color:#b91c1c;border:1px solid #ffc4c4}
  .row{display:flex;gap:14px;align-items:center;margin-bottom:14px}
  .avatar{width:80px;height:80px;border-radius:16px;background:#e53900;color:#fff;display:grid;place-items:center;font-weight:900;font-size:24px;overflow:hidden}
  .avatar img{width:100%;height:100%;object-fit:cover}
  label{display:block;font-size:13px;font-weight:700;color:#555;margin-top:10px}
  input{width:100%;padding:12px;border-radius:10px;border:1px solid #ddd;margin-top:6px}
  .btns{display:flex;gap:10px;flex-wrap:wrap;margin-top:16px}
  .btn{padding:12px 16px;border:none;border-radius:10px;cursor:pointer;font-weight:800;text-decoration:none;display:inline-block}
  .save{background:#e53900;color:#fff}
  .back{background:#e9edf5;color:#111}
</style>
</head>
<body>
<div class="card">
  <h2>Edit Profile</h2>

  <?php if ($success): ?><div class="msg ok"><?php echo safe($success); ?></div><?php endif; ?>
  <?php if ($error): ?><div class="msg bad"><?php echo safe($error); ?></div><?php endif; ?>

  <div class="row">
    <div class="avatar">
      <?php if ($imgPath && file_exists($imgPath)): ?>
        <img src="<?php echo safe($imgPath); ?>" alt="Profile">
      <?php else: ?>
        <?php echo $letter; ?>
      <?php endif; ?>
    </div>
    <div>
      <div style="font-weight:900;font-size:18px;"><?php echo safe($user["fullname"]); ?></div>
      <div style="color:#666;margin-top:4px;"><?php echo safe($user["email"]); ?></div>
    </div>
  </div>

  <form method="POST" enctype="multipart/form-data">
    <label>Full Name</label>
    <input type="text" name="fullname" value="<?php echo safe($user["fullname"]); ?>" required>

    <label>Phone</label>
    <input type="text" name="phone" value="<?php echo safe($user["phone"]); ?>">

    <label>Address</label>
    <input type="text" name="address" value="<?php echo safe($user["address"]); ?>">

    <label>Profile Image</label>
    <input type="file" name="profile_image" accept=".jpg,.jpeg,.png,.webp">

    <div class="btns">
      <button class="btn save" type="submit">Save Changes</button>
      <a class="btn back" href="dashboard.php">Back</a>
    </div>
  </form>
</div>
</body>
</html>
