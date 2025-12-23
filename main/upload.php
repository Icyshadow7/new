<?php
session_start();
include "db.php"; // must create $conn

$feedback_message = '';
$feedback_class = 'hidden';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Input values
    $roomName     = trim($_POST['name'] ?? '');
    $roomLocation = trim($_POST['location'] ?? '');
    $roomPhone    = trim($_POST['phone'] ?? '');
    $roomPriceRaw = $_POST['price'] ?? '';

    $roomPrice = filter_var($roomPriceRaw, FILTER_VALIDATE_FLOAT);

    // File upload
    $uploadedFile = $_FILES['image'] ?? null;
    $uploadError = false;
    $newFileName = '';

    if ($uploadedFile && $uploadedFile['error'] === UPLOAD_ERR_OK) {

        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $fileExtension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
        $allowedExt = ["jpg", "jpeg", "png", "webp"];

        if (!in_array($fileExtension, $allowedExt)) {
            $uploadError = true;
            $feedback_message = "Error: Only JPG, JPEG, PNG, WEBP images are allowed.";
        } else {
            $newFileName = uniqid("room_", true) . "." . $fileExtension;
            $target_file = $target_dir . $newFileName;

            if (!move_uploaded_file($uploadedFile['tmp_name'], $target_file)) {
                $uploadError = true;
                $feedback_message = "Error: Could not save uploaded file. Check folder permissions.";
            }
        }

    } else {
        $uploadError = true;
        $feedback_message = "Error: Image file is required.";
    }

    // FINAL VALIDATION
    if ($roomName === '' || $roomLocation === '' || $roomPhone === '' || $roomPrice === false || $uploadError) {

        if (!$feedback_message) {
            $feedback_message = "Please fill all required fields correctly.";
        }
        $feedback_class = 'bg-red-50 text-red-700 border border-red-200';

    } else {

        // ✅ INSERT INTO DATABASE (rooms table)
        $stmt = $conn->prepare("INSERT INTO rooms (room_name, location, phone, price, image) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            $feedback_message = "Database prepare error: " . $conn->error;
            $feedback_class = 'bg-red-50 text-red-700 border border-red-200';
        } else {
            $stmt->bind_param("sssds", $roomName, $roomLocation, $roomPhone, $roomPrice, $newFileName);

            if ($stmt->execute()) {
                $newId = $stmt->insert_id;

                $feedback_message = "
                    <div class='space-y-2'>
                        <div class='flex items-center justify-between'>
                            <h2 class='font-extrabold text-emerald-700 text-lg'>Uploaded Successfully</h2>
                            <span class='text-xs font-bold bg-emerald-100 text-emerald-700 px-2 py-1 rounded-full'>ID: $newId</span>
                        </div>
                        <div class='grid grid-cols-2 gap-2 text-sm'>
                            <p><span class='font-bold text-slate-600'>Name:</span> " . htmlspecialchars($roomName) . "</p>
                            <p><span class='font-bold text-slate-600'>Price:</span> Rs. " . htmlspecialchars($roomPrice) . "</p>
                            <p><span class='font-bold text-slate-600'>Location:</span> " . htmlspecialchars($roomLocation) . "</p>
                            <p><span class='font-bold text-slate-600'>Phone:</span> " . htmlspecialchars($roomPhone) . "</p>
                        </div>
                        <div class='pt-2'>
                            <p class='text-xs font-semibold text-slate-500'>Saved Image: " . htmlspecialchars($newFileName) . "</p>
                            <img src='uploads/$newFileName' class='mt-2 w-full max-w-xs rounded-xl shadow-lg border border-slate-200'>
                        </div>
                    </div>
                ";
                $feedback_class = 'bg-emerald-50 text-emerald-800 border border-emerald-200';
            } else {
                @unlink("uploads/" . $newFileName);
                $feedback_message = "Database insert error: " . $stmt->error;
                $feedback_class = 'bg-red-50 text-red-700 border border-red-200';
            }

            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PahunaStay</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body { font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; }
        .glass {
            background: rgba(255,255,255,.75);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(15, 23, 42, .08);
        }
        .soft-shadow { box-shadow: 0 24px 70px rgba(0,0,0,.14); }
        .ringline { box-shadow: inset 0 0 0 1px rgba(15,23,42,.06); }
        .focusline:focus { outline: none; box-shadow: 0 0 0 4px rgba(229,57,0,.15); border-color: rgba(229,57,0,.35) !important; }
    </style>
</head>

<body class="min-h-screen bg-gradient-to-b from-slate-50 to-slate-100">

    <!-- Background glow -->
    <div class="pointer-events-none fixed inset-0">
        <div class="absolute -top-24 -left-24 h-80 w-80 rounded-full blur-3xl opacity-25"
             style="background: linear-gradient(135deg,#e53900,#ff5a1f);"></div>
        <div class="absolute top-20 right-10 h-72 w-72 rounded-full blur-3xl opacity-20"
             style="background: linear-gradient(135deg,#ff5a1f,#ffd1c2);"></div>
    </div>

    <div class="relative max-w-5xl mx-auto px-4 py-10">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-2xl"
                     style="background: linear-gradient(135deg,#e53900,#ff5a1f); box-shadow: 0 14px 30px rgba(229,57,0,.20);"></div>
                <div>
                    <h1 class="text-xl md:text-2xl font-extrabold tracking-tight text-slate-900">PahunaStay</h1>
                    <p class="text-sm font-semibold text-slate-500">Upload a new room listing</p>
                </div>
            </div>

            <a href="index.php"
               class="hidden sm:inline-flex items-center gap-2 px-4 py-2 rounded-xl font-bold text-slate-800 bg-white/70 border border-slate-200 shadow-sm hover:bg-white transition">
                ← Home
            </a>
        </div>

        <div class="grid lg:grid-cols-2 gap-6">
            <!-- Left: Form -->
            <div class="glass soft-shadow rounded-3xl p-6 md:p-8">
                <div class="flex items-start justify-between gap-4 mb-5">
                    <div>
                        <h2 class="text-2xl font-extrabold text-slate-900">Upload Room</h2>
                        <p class="text-sm font-semibold text-slate-500 mt-1">
                            Fill the details carefully. Image must be JPG/PNG/WEBP.
                        </p>
                    </div>

                    <span class="text-xs font-extrabold px-3 py-1 rounded-full"
                          style="background: rgba(229,57,0,.08); color:#e53900; border:1px solid rgba(229,57,0,.18);">
                        PahunaStay Admin
                    </span>
                </div>

                <!-- Feedback -->
                <?php if (!empty($feedback_message)): ?>
                    <div class="<?= $feedback_class ?> p-4 rounded-2xl mb-5 ringline">
                        <?= $feedback_message ?>
                    </div>
                <?php endif; ?>

                <form action="upload.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-extrabold text-slate-700"id="roomName" >Room Name</label>
                            <input type="text" name="name" required
                                   placeholder="e.g. Mountain View Suite"
                                   class="mt-2 w-full bg-white/80 border border-slate-200 rounded-2xl px-4 py-3 font-semibold text-slate-900 focusline" />
                        </div>

                        <div>
                            <label class="text-sm font-extrabold text-slate-700" id="roomPrice" >Price (Rs.)</label>
                            <input type="number" name="price" required
                                   placeholder="e.g. 4200"
                                   class="mt-2 w-full bg-white/80 border border-slate-200 rounded-2xl px-4 py-3 font-semibold text-slate-900 focusline" />
                        </div>

                        <div>
                            <label class="text-sm font-extrabold text-slate-700"id="roomLocation">Location</label>
                            <input type="text" name="location" required
                                   placeholder="e.g. Nagarkot"
                                   class="mt-2 w-full bg-white/80 border border-slate-200 rounded-2xl px-4 py-3 font-semibold text-slate-900 focusline" />
                        </div>

                        <div>
                            <label class="text-sm font-extrabold text-slate-700"  id="roomPhone">Phone</label>
                            <input type="text" name="phone" required
                                   placeholder="e.g. 9812345678"
                                   class="mt-2 w-full bg-white/80 border border-slate-200 rounded-2xl px-4 py-3 font-semibold text-slate-900 focusline" />
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-extrabold text-slate-700">Room Image</label>

                        <div class="mt-2 flex items-center justify-between gap-3 p-3 rounded-2xl bg-white/60 border border-slate-200">
                            <input id="imageInput" type="file" name="image" accept="image/*" required
                                   class="w-full text-sm font-semibold text-slate-600 file:mr-4 file:py-2 file:px-4
                                          file:rounded-xl file:border-0 file:font-extrabold
                                          file:bg-slate-900 file:text-white hover:file:bg-slate-800 transition" />
                        </div>

                        <p class="text-xs font-semibold text-slate-500 mt-2">
                            Recommended: 1200×800 or higher. Allowed: jpg, jpeg, png, webp.
                        </p>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <a href="index.php"
                           class="w-1/2 text-center bg-white/70 text-slate-900 py-3 rounded-2xl font-extrabold
                                  border border-slate-200 shadow-sm hover:bg-white transition">
                            Back to Home
                        </a>

                        <button type="submit"
                                class="w-1/2 py-3 rounded-2xl font-extrabold text-white shadow-md transition-all duration-200 hover:shadow-lg hover:scale-[1.01]"
                                style="background: linear-gradient(135deg,#e53900,#ff5a1f);">
                            Upload Listing
                        </button>
                    </div>
                </form>
            </div>

            <!-- Right: Preview card -->
            <div class="glass soft-shadow rounded-3xl p-6 md:p-8">
                <h3 class="text-lg font-extrabold text-slate-900">Live Preview</h3>
                <p class="text-sm font-semibold text-slate-500 mt-1 mb-4">
                    Your uploaded image will show like this on the rooms page.
                </p>

                <div class="rounded-3xl overflow-hidden border border-slate-200 bg-white/60">
                    <div class="h-56 bg-slate-200 relative">
                        <img id="previewImg" src="images/default.jpg" alt="Preview"
                             class="w-full h-full object-cover" />
                        <div class="absolute inset-0 bg-gradient-to-t from-black/45 to-transparent"></div>

                        <div class="absolute bottom-4 left-4">
                            <div class="text-white font-extrabold text-xl leading-tight">Room Preview</div>
                            <div class="text-white/90 text-sm font-semibold">PahunaStay</div>
                        </div>
                    </div>

                    <div class="p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                               <p id="previewName" class="text-slate-900 font-extrabold">Example Room Name</p>
<p class="text-slate-500 text-sm font-semibold">
  <span id="previewLocation">Location</span> • <span id="previewPhone">Phone</span>
</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs font-extrabold text-slate-500">Per Night</p>
                                <p class="font-extrabold text-slate-900">Rs. 0.00</p>
                            </div>
                        </div>

                        <div class="mt-4">
                            <a href="#" onclick="return false;"
                               class="inline-flex items-center justify-center px-4 py-2 rounded-xl font-extrabold text-white"
                               style="background: linear-gradient(135deg,#e53900,#ff5a1f);">
                                View Details
                            </a>
                        </div>
                    </div>
                </div>

                <div class="mt-5 p-4 rounded-2xl border border-slate-200 bg-white/60">
                    <p class="text-sm font-semibold text-slate-600 leading-relaxed">
                        Tip: If your room images are not showing in <b>room.php</b>,
                        ensure you display them using:
                        <span class="font-extrabold text-slate-900">uploads/<?php echo "filename"; ?></span>
                        and that the file really exists inside the <b>uploads</b> folder.
                    </p>
                </div>
            </div>
        </div>
    </div>

<script>
(function () {
  const el = (id) => document.getElementById(id);

  // inputs
  const nameEl  = el("roomName");
  const priceEl = el("roomPrice");
  const locEl   = el("roomLocation");
  const phoneEl = el("roomPhone");
  const imgEl   = el("imageInput");

  // preview targets
  const pName  = el("previewName");
  const pPrice = el("previewPrice");
  const pLoc   = el("previewLocation");
  const pPhone = el("previewPhone");
  const pImg   = el("previewImg");

  function updatePreview() {
    if (pName)  pName.textContent  = (nameEl?.value || "").trim() || "Example Room Name";
    if (pLoc)   pLoc.textContent   = (locEl?.value || "").trim()  || "Location";
    if (pPhone) pPhone.textContent = (phoneEl?.value || "").trim()|| "Phone";

    const raw = (priceEl?.value || "").trim();
    const num = Number(raw);
    if (pPrice) {
      pPrice.textContent = raw === "" || Number.isNaN(num) ? "0.00" : num.toLocaleString();
    }
  }

  // live update while typing
  [nameEl, priceEl, locEl, phoneEl].forEach(input => {
    if (!input) return;
    input.addEventListener("input", updatePreview);
    input.addEventListener("change", updatePreview);
  });

  // image preview
  if (imgEl && pImg) {
    imgEl.addEventListener("change", (e) => {
      const file = e.target.files && e.target.files[0];
      if (!file) return;
      pImg.src = URL.createObjectURL(file);
    });
  }

  // initialize
  updatePreview();
})();
</script>


</body>
</html>
