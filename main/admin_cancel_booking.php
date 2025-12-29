<?php
session_start();
include "db.php";

$isAdmin = ($_SESSION['is_admin'] ?? 0) == 1;

if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !$isAdmin) {
  header("Location: login.php");
  exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  header("Location: admin_orders.php");
  exit;
}

$bookingId = (int)($_POST['booking_id'] ?? 0);
if ($bookingId <= 0) {
  header("Location: admin_orders.php");
  exit;
}

// Admin can cancel any booking if it's BOOKED
$stmt = $conn->prepare("UPDATE bookings SET status='CANCELLED', cancelled_at=NOW()
                        WHERE id=? AND status='BOOKED'");
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$stmt->close();

header("Location: admin_orders.php");
exit;
