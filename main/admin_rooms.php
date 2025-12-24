<?php
session_start();
include "db.php"; // must provide $conn (mysqli)

// ---- Admin guard ----
if (empty($_SESSION['logged_in']) || ($_SESSION['role'] ?? '') !== 'admin') {
  header("Location: login.php");
  exit;
}

// CSRF
if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

function safe($v) { return htmlspecialchars($v ?? "", ENT_QUOTES, "UTF-8"); }

$flash = "";
$flashType = "info"; // info | success | danger

// ---- Delete handler ----
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_id'])) {
  if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
    $flash = "Security check failed. Please refresh and try again.";
    $flashType = "danger";
  } else {
    $id = (int)($_POST['delete_id'] ?? 0);

    // Fetch image to delete file too (optional)
    $imgStmt = $conn->prepare("SELECT image FROM rooms WHERE id=? LIMIT 1");
    $imgStmt->bind_param("i", $id);
    $imgStmt->execute();
    $imgRes = $imgStmt->get_result();
    $imgRow = $imgRes->fetch_assoc();
    $imageName = $imgRow['image'] ?? '';
    $imgStmt->close();

    $del = $conn->prepare("DELETE FROM rooms WHERE id=?");
    $del->bind_param("i", $id);

    if ($del->execute()) {
      // Optional: delete image file
      if (!empty($imageName)) {
        $path = __DIR__ . "/uploads/" . $imageName;
        if (is_file($path)) { @unlink($path); }
      }
      $flash = "Room deleted successfully.";
      $flashType = "success";
    } else {
      $flash = "Delete failed. Please try again.";
      $flashType = "danger";
    }
    $del->close();
  }
}

// ---- Fetch rooms + stats ----
$rooms = $conn->query("SELECT * FROM rooms ORDER BY id DESC");

$totalRooms = 0;
$totalValue = 0; // sum of price
if ($rooms) {
  $totalRooms = $rooms->num_rows;
  // We will compute totalValue in PHP while rendering OR do another query
  // For speed, do another query:
  $sumQ = $conn->query("SELECT COALESCE(SUM(price),0) AS s FROM rooms");
  $sumRow = $sumQ ? $sumQ->fetch_assoc() : ['s'=>0];
  $totalValue = (float)($sumRow['s'] ?? 0);
}

$adminEmail = $_SESSION['email'] ?? 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Panel | PahunaStay</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <style>
    body{ font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial; }
  </style>
</head>

<body class="min-h-screen bg-gradient-to-b from-zinc-950 via-zinc-950 to-zinc-900 text-zinc-100">

  <!-- Topbar (matches vibe) -->
  <header class="sticky top-0 z-50 border-b border-white/10 bg-zinc-950/70 backdrop-blur-xl">
    <div class="mx-auto max-w-7xl px-4 py-3 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-orange-500 to-red-600 grid place-items-center shadow-lg shadow-orange-500/20">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
            <path d="M3 10.5L12 4l9 6.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1v-9.5Z" stroke="white" stroke-width="2" stroke-linejoin="round"/>
          </svg>
        </div>
        <div class="leading-tight">
          <div class="font-extrabold text-lg">PahunaStay</div>
          <div class="text-xs text-zinc-400 -mt-0.5">Admin Dashboard</div>
        </div>
      </div>

      <nav class="hidden md:flex items-center gap-2 text-sm text-zinc-300">
        <a class="px-3 py-2 rounded-xl hover:bg-white/5" href="index.php">Home</a>
      <a class="px-3 py-2 rounded-xl hover:bg-white/5" href="room.php">Available Rooms</a>

        <span class="px-3 py-2 rounded-xl bg-white/5 border border-white/10 text-zinc-200">Admin</span>
      </nav>

      <div class="flex items-center gap-2">
        <div class="hidden sm:flex items-center gap-2 px-3 py-2 rounded-2xl border border-white/10 bg-white/5">
          <div class="w-8 h-8 rounded-xl bg-white/10 grid place-items-center font-bold text-orange-200">
            <?php echo strtoupper(substr($adminEmail,0,1)); ?>
          </div>
          <div class="leading-tight">
            <div class="text-sm font-semibold"><?php echo safe($adminEmail); ?></div>
            <div class="text-xs text-zinc-400">role: admin</div>
          </div>
        </div>

        <a href="logout.php"
           class="px-4 py-2 rounded-2xl font-semibold bg-white/5 border border-white/10 hover:bg-white/10">
          Logout
        </a>
      </div>
    </div>
  </header>

  <main class="mx-auto max-w-7xl px-4 py-8">
    <!-- Hero / Summary -->
    <section class="rounded-[28px] border border-white/10 bg-gradient-to-br from-white/10 to-white/5 overflow-hidden shadow-2xl shadow-black/30">
      <div class="p-6 md:p-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
          <div>
            <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight">
              Rooms Management
            </h1>
            <p class="text-zinc-300 mt-2 max-w-2xl">
              Manage listings, verify details, and remove rooms that are outdated or incorrect—keeping PahunaStay clean and reliable.
            </p>

            <div class="mt-5 flex flex-wrap gap-2">
              <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-orange-500/15 border border-orange-500/25 text-orange-200 text-sm font-semibold">
                <span class="w-2 h-2 rounded-full bg-orange-400"></span>
                Verified Listings UI
              </span>
              <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/5 border border-white/10 text-zinc-200 text-sm font-semibold">
                Secure Delete (CSRF)
              </span>
              <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/5 border border-white/10 text-zinc-200 text-sm font-semibold">
                Image Cleanup
              </span>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3 min-w-[260px]">
            <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
              <div class="text-xs text-zinc-400">Total Rooms</div>
              <div class="text-2xl font-extrabold mt-1"><?php echo (int)$totalRooms; ?></div>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
              <div class="text-xs text-zinc-400">Total Price Sum</div>
              <div class="text-2xl font-extrabold mt-1">Rs. <?php echo number_format($totalValue, 2); ?></div>
            </div>
          </div>
        </div>

        <?php if (!empty($flash)): ?>
          <?php
            $bg = $flashType === 'success' ? 'bg-emerald-500/15 border-emerald-500/25 text-emerald-100'
                 : ($flashType === 'danger' ? 'bg-red-500/15 border-red-500/25 text-red-100'
                 : 'bg-white/5 border-white/10 text-zinc-200');
          ?>
          <div class="mt-6 rounded-2xl border <?php echo $bg; ?> px-4 py-3 font-semibold">
            <?php echo safe($flash); ?>
          </div>
        <?php endif; ?>
      </div>
    </section>

    <!-- Controls -->
    <section class="mt-6 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
      <div class="flex-1">
        <div class="relative">
          <span class="absolute left-4 top-1/2 -translate-y-1/2 text-zinc-400">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
              <path d="M21 21l-4.3-4.3m1.8-5.2a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </span>
          <input id="search" oninput="filterRooms()"
                 class="w-full rounded-2xl border border-white/10 bg-white/5 px-11 py-3 outline-none focus:border-orange-500/50 focus:ring-2 focus:ring-orange-500/20"
                 placeholder="Search by room name, location, phone, price..." />
        </div>
      </div>

      <div class="flex items-center gap-2">
        <span class="px-4 py-3 rounded-2xl border border-white/10 bg-white/5 text-sm text-zinc-200">
          Showing: <b id="count"><?php echo (int)$totalRooms; ?></b>
        </span>
    <a href="upload.php"

           class="px-5 py-3 rounded-2xl font-extrabold bg-gradient-to-r from-orange-500 to-red-600 hover:from-orange-600 hover:to-red-700 shadow-lg shadow-orange-500/20">
          + Upload Room
        </a>
      </div>
    </section>

    <!-- Rooms List (modern card-table hybrid) -->
    <section class="mt-5">
      <div class="rounded-[28px] border border-white/10 bg-white/5 overflow-hidden">
        <div class="hidden md:block">
          <table class="w-full">
            <thead class="bg-zinc-950/40 border-b border-white/10">
              <tr class="text-left text-xs uppercase tracking-wider text-zinc-400">
                <th class="p-4">Room</th>
                <th class="p-4">Location</th>
                <th class="p-4">Phone</th>
                <th class="p-4">Price</th>
                <th class="p-4 text-right">Action</th>
              </tr>
            </thead>
            <tbody id="roomRows">
              <?php if ($rooms && $rooms->num_rows > 0): ?>
                <?php while($r = $rooms->fetch_assoc()): ?>
                  <?php
                    $id = (int)($r['id'] ?? 0);
                    $roomName = $r['room_name'] ?? '';
                    $location = $r['location'] ?? '';
                    $phone = $r['phone'] ?? '';
                    $price = $r['price'] ?? '';
                    $image = $r['image'] ?? '';
                  ?>
                  <tr class="border-b border-white/10 hover:bg-white/5 transition" data-text="<?php echo safe(strtolower($roomName.' '.$location.' '.$phone.' '.$price)); ?>">
                    <td class="p-4">
                      <div class="flex items-center gap-4">
                        <div class="relative">
                          <?php if (!empty($image)): ?>
                            <img src="uploads/<?php echo safe($image); ?>" class="w-20 h-14 rounded-2xl object-cover border border-white/10" alt="room">
                          <?php else: ?>
                            <div class="w-20 h-14 rounded-2xl bg-white/10 border border-white/10"></div>
                          <?php endif; ?>
                          <span class="absolute left-2 top-2 text-[11px] font-extrabold px-2 py-1 rounded-full bg-orange-500 text-white shadow">
                            Verified
                          </span>
                        </div>
                        <div>
                          <div class="font-extrabold text-zinc-100"><?php echo safe($roomName); ?></div>
                          <div class="text-xs text-zinc-400">ID: <?php echo $id; ?></div>
                        </div>
                      </div>
                    </td>
                    <td class="p-4 text-zinc-200"><?php echo safe($location); ?></td>
                    <td class="p-4 text-zinc-200"><?php echo safe($phone); ?></td>
                    <td class="p-4">
                      <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/5 border border-white/10 text-zinc-100 font-bold">
                        Rs. <?php echo safe($price); ?>
                        <span class="text-xs font-semibold text-zinc-400">/night</span>
                      </div>
                    </td>
                    <td class="p-4 text-right">
                      <form method="POST" onsubmit="return confirmDelete('<?php echo $id; ?>','<?php echo safe($roomName); ?>')">
                        <input type="hidden" name="csrf" value="<?php echo safe($_SESSION['csrf']); ?>">
                        <input type="hidden" name="delete_id" value="<?php echo $id; ?>">
                        <button type="submit"
                                class="px-4 py-2 rounded-2xl font-extrabold
                                       bg-red-500/15 border border-red-500/25 text-red-100
                                       hover:bg-red-500/25 transition">
                          Delete
                        </button>
                      </form>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="5" class="p-8 text-center text-zinc-400">No rooms found.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Mobile cards -->
        <div class="md:hidden p-4 space-y-4" id="roomCards">
          <?php
            // Re-run query for mobile section because $rooms is consumed above in table.
            $rooms2 = $conn->query("SELECT * FROM rooms ORDER BY id DESC");
            if ($rooms2 && $rooms2->num_rows > 0):
              while($r = $rooms2->fetch_assoc()):
                $id = (int)($r['id'] ?? 0);
                $roomName = $r['room_name'] ?? '';
                $location = $r['location'] ?? '';
                $phone = $r['phone'] ?? '';
                $price = $r['price'] ?? '';
                $image = $r['image'] ?? '';
                $text = strtolower($roomName.' '.$location.' '.$phone.' '.$price);
          ?>
          <div class="rounded-[22px] border border-white/10 bg-white/5 overflow-hidden" data-text="<?php echo safe($text); ?>">
            <div class="relative">
              <?php if (!empty($image)): ?>
                <img src="uploads/<?php echo safe($image); ?>" class="w-full h-44 object-cover" alt="room">
              <?php else: ?>
                <div class="w-full h-44 bg-white/10"></div>
              <?php endif; ?>
              <span class="absolute left-3 top-3 text-xs font-extrabold px-3 py-1.5 rounded-full bg-orange-500 text-white shadow">
                Verified
              </span>
              <div class="absolute right-3 bottom-3 px-3 py-2 rounded-2xl bg-zinc-950/60 border border-white/10 backdrop-blur font-extrabold">
                Rs. <?php echo safe($price); ?> <span class="text-xs font-semibold text-zinc-300">/night</span>
              </div>
            </div>

            <div class="p-4">
              <div class="font-extrabold text-lg"><?php echo safe($roomName); ?></div>
              <div class="text-sm text-zinc-300 mt-1">Location: <?php echo safe($location); ?></div>
              <div class="text-sm text-zinc-300">Phone: <?php echo safe($phone); ?></div>

              <form class="mt-4" method="POST" onsubmit="return confirmDelete('<?php echo $id; ?>','<?php echo safe($roomName); ?>')">
                <input type="hidden" name="csrf" value="<?php echo safe($_SESSION['csrf']); ?>">
                <input type="hidden" name="delete_id" value="<?php echo $id; ?>">
                <button type="submit"
                        class="w-full px-4 py-3 rounded-2xl font-extrabold
                               bg-red-500/15 border border-red-500/25 text-red-100
                               hover:bg-red-500/25 transition">
                  Delete Room
                </button>
              </form>
            </div>
          </div>
          <?php endwhile; else: ?>
            <div class="p-8 text-center text-zinc-400">No rooms found.</div>
          <?php endif; ?>
        </div>
      </div>

      <p class="text-xs text-zinc-500 mt-3">
        Tip: Use search to quickly locate rooms. Delete removes the database row and also deletes the uploaded image file (if exists).
      </p>
    </section>
  </main>

<script>
function confirmDelete(id, name){
  return confirm(`Delete Room #${id} (${name})? This cannot be undone.`);
}

function filterRooms(){
  const q = (document.getElementById('search').value || '').toLowerCase().trim();

  // Desktop table rows
  const rows = document.querySelectorAll('#roomRows tr');
  let shown = 0;
  rows.forEach(r => {
    const text = (r.getAttribute('data-text') || '');
    const ok = text.includes(q);
    r.style.display = ok ? '' : 'none';
    if (ok) shown++;
  });

  // Mobile cards
  const cards = document.querySelectorAll('#roomCards > div[data-text]');
  cards.forEach(c => {
    const text = (c.getAttribute('data-text') || '');
    c.style.display = text.includes(q) ? '' : 'none';
  });

  document.getElementById('count').innerText = shown || (q==='' ? rows.length : shown);
}
</script>
</body>
</html>
