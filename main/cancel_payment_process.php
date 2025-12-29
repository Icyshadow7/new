<?php
session_start();
include "db.php";

if (empty($_SESSION['logged_in'])) {
  header("Location: login.php");
  exit;
}

$userId    = (int)($_SESSION['user_id'] ?? 0);
$bookingId = (int)($_POST['id'] ?? 0);
$method    = strtolower(trim($_POST['method'] ?? ''));

if ($userId <= 0 || $bookingId <= 0) {
  header("Location: my_bookings.php");
  exit;
}

$allowed = ['esewa', 'khalti', 'imepay', 'bank'];
if (!in_array($method, $allowed)) {
  header("Location: cancel_payment.php?id=".$bookingId."&err=method");
  exit;
}

/* Verify booking belongs to this user and is cancellable */
$sql = "
SELECT b.id, b.status
FROM bookings b
WHERE b.id = ? AND b.user_id = ?
LIMIT 1
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $bookingId, $userId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
  header("Location: my_bookings.php?err=notfound");
  exit;
}

$status = strtolower(trim($row['status'] ?? 'pending'));
if (!in_array($status, ['pending', 'booked', 'confirmed'])) {
  header("Location: my_bookings.php?err=notallowed");
  exit;
}

/* Cancel booking (recommended: don't delete, just mark cancelled) */
$upd = $conn->prepare("UPDATE bookings SET status='cancelled' WHERE id=? AND user_id=?");
$upd->bind_param("ii", $bookingId, $userId);
$upd->execute();
$upd->close();

/* Redirect back */
header("Location: my_bookings.php?cancel=success");
exit;
