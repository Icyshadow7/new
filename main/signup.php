<?php
session_start(); // Start the session

// ---- DATABASE CONNECTION ---- //
$host = "localhost";
$user = "root";
$pass = "";
$db   = "aashish"; // Your database name

// Use the mysqli constructor for the connection object
$conn = new mysqli($host, $user, $pass, $db); 

// Check for connection error
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// ---- PROCESS REGISTRATION ---- //
$registrationMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullName = trim($_POST['fullName']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    // 1. Basic Validation
    if (empty($fullName) || empty($email) || empty($password)) {
        $registrationMessage = "<p class='text-red-700'>All fields are required.</p>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $registrationMessage = "<p class='text-red-700'>Invalid email format.</p>";
    } elseif (strlen($password) < 8) {
        $registrationMessage = "<p class='text-red-700'>Password must be at least 8 characters long.</p>";
    } else {
        // 2. Check if email already exists using Prepared Statements
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $registrationMessage = "<p class='text-red-700'>An account with this email already exists.</p>";
        } else {
            // 3. Hash the password securely (CRITICAL STEP)
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $createdAt = date('Y-m-d H:i:s'); 

            // 4. Insert new user into the database
            $stmt_insert = $conn->prepare("INSERT INTO users (fullname, email, password, created_at) VALUES (?, ?, ?, ?)");
            $stmt_insert->bind_param("ssss", $fullName, $email, $hashedPassword, $createdAt);

            if ($stmt_insert->execute()) {
                $registrationMessage = "<p class='text-green-700'>Registration successful! Redirecting to login...</p>";
                
                 // Redirect to login page after 2 seconds
                echo "<script>
                        setTimeout(() => {
                            window.location.href = 'login.php';
                        }, 2000);
                      </script>";

            } else {
                $registrationMessage = "<p class='text-red-700'>Error creating account: " . $conn->error . "</p>";
            }
            $stmt_insert->close();
        }
        $stmt_check->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - New Account</title>

    <!-- Load Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap');
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #ffe9e0 100%);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md bg-white shadow-2xl rounded-xl p-8 md:p-10 border border-gray-100/50">

        <div class="text-center mb-8">
            <h1 class="text-3xl font-extrabold text-gray-900 mb-2">Create Account</h1>
            <p class="text-gray-500">Join us and access your personal dashboard.</p>
        </div>

        <!-- SIGN UP FORM -->
        <form action="signup.php" method="POST" class="space-y-6">

            <div>
                <label for="fullName" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                <input type="text" id="fullName" name="fullName" required
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-red-500">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                <input type="email" id="email" name="email" required
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-red-500">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password (min 8 chars)</label>
                <input type="password" id="password" name="password" required minlength="8"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-red-500">
            </div>

            <!-- Message Box -->
            <?php if ($registrationMessage): ?>
                <div class="p-3 text-sm rounded-lg bg-gray-100">
                    <?= $registrationMessage ?>
                </div>
            <?php endif; ?>

            <button type="submit"
                    class="w-full py-3 px-4 rounded-lg shadow-md text-white bg-red-600 hover:bg-red-700">
                Sign Up
            </button>
        </form>

        <div class="mt-6 text-center text-sm">
            <p class="text-gray-500">
                Already have an account?
                <a href="login.php" class="font-medium text-red-600 hover:underline">Log in</a>
            </p>
        </div>

    </div>

</body>
</html>