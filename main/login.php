<?php
session_start();
// Start the session

// Database connection details (Same as signup.php)
$host = "localhost";
$user = "root";
$pass = "";
$db   = "aashish"; 

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// ---- PROCESS LOGIN ---- //
$loginMessage = ""; // ✅ IMPORTANT: prevents undefined variable error

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $loginMessage = "Email and password are required.";
    } else {
        $stmt = $conn->prepare("SELECT id, fullname, email, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {

                $_SESSION['user_id']   = $user['id'];
                $_SESSION['fullname']  = $user['fullname'];
                $_SESSION['email']     = $user['email'];
                $_SESSION['logged_in'] = true;

                header("Location: index.php");
                exit();

            } else {
                $loginMessage = "Invalid password.";
            }
        } else {
            $loginMessage = "No account found with that email address.";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Log In | Quick Book</title>

<script src="https://cdn.tailwindcss.com"></script>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap');

body{
  font-family:'Inter',sans-serif;
  background:
    radial-gradient(900px 500px at 10% 10%, rgba(229,57,0,.25), transparent 55%),
    radial-gradient(700px 400px at 90% 20%, rgba(255,90,31,.22), transparent 55%),
    linear-gradient(180deg, #f7f7fb, #eef1f8);
}
</style>
</head>

<body class="min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-md bg-white/85 backdrop-blur-xl
            shadow-2xl rounded-2xl p-8 md:p-10 border border-white/50">

    <!-- BRAND -->
    <div class="text-center mb-8">
        <div class="w-14 h-14 mx-auto rounded-2xl
                    bg-gradient-to-br from-red-600 to-orange-500
                    flex items-center justify-center
                    text-white font-extrabold text-xl shadow-lg">
            QB
        </div>

        <h1 class="text-3xl font-extrabold text-gray-900 mt-4">
            Welcome Back
        </h1>
        <p class="text-gray-500 mt-1">
            Log in to continue booking your stay
        </p>
    </div>

    <!-- LOGIN FORM -->
    <form action="login.php" method="POST" class="space-y-5">

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">
                Email Address
            </label>
            <input type="email" name="email" required
                   placeholder="you@example.com"
                   class="w-full px-4 py-3 rounded-xl
                          border border-gray-300
                          focus:ring-2 focus:ring-red-500
                          focus:border-red-500 outline-none
                          transition">
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">
                Password
            </label>
            <input type="password" name="password" required
                   placeholder="••••••••"
                   class="w-full px-4 py-3 rounded-xl
                          border border-gray-300
                          focus:ring-2 focus:ring-red-500
                          focus:border-red-500 outline-none
                          transition">
        </div>

        <!-- ERROR MESSAGE -->
        <?php if (!empty($loginMessage)): ?>
            <div class="p-3 rounded-xl bg-red-50 border border-red-200
                        text-sm text-red-700 font-semibold">
                <?= htmlspecialchars($loginMessage) ?>
            </div>
        <?php endif; ?>

        <button type="submit"
                class="w-full py-3 rounded-xl font-extrabold text-white
                       bg-gradient-to-r from-red-600 to-orange-500
                       hover:from-red-700 hover:to-orange-600
                       shadow-lg hover:shadow-xl
                       transition transform hover:-translate-y-[1px]">
            Log In
        </button>
    </form>

    <!-- FOOTER -->
    <div class="mt-6 text-center text-sm text-gray-600">
        Don’t have an account?
        <a href="signup.php"
           class="font-bold text-red-600 hover:underline">
            Sign Up
        </a>
    </div>

</div>

</body>
</html>
