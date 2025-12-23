<?php
// =====================================================================================
// WARNING: PHP execution and actual server-side database connections (like MySQL or 
// a server-side Firestore SDK) are NOT supported in this client-side environment.
// This script SIMULATES the process and displays the captured data.
// =====================================================================================

$feedback_message = '';
$feedback_class = 'hidden';

// 1. Check if the form was submitted using the POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 2. Collect and sanitize form data
    $roomName = htmlspecialchars($_POST['room-name'] ?? '');
    $roomType = htmlspecialchars($_POST['room-type'] ?? '');
    // Using FILTER_VALIDATE_FLOAT for price validation
    $roomPrice = filter_var($_POST['room-price'] ?? 0, FILTER_VALIDATE_FLOAT);
    $roomImageUrl = filter_var($_POST['room-image-url'] ?? '', FILTER_VALIDATE_URL);
    $roomDescription = htmlspecialchars($_POST['room-description'] ?? '');

    // 3. Simple validation
    if (empty($roomName) || empty($roomType) || $roomPrice === false || empty($roomDescription)) {
        $feedback_message = "Error: Please fill all required fields correctly.";
        $feedback_class = 'bg-red-100 text-red-700';
    } else {
        
        // 4. Database Connection (SIMULATED)
        // In a real PHP environment, you would use a library (like Google Cloud Client Library for Firestore, 
        // or PDO for MySQL) here to connect and save the data.
        
        // === SIMULATION OUTPUT ===
        $data_output = "
            Name: {$roomName}<br>
            Type: {$roomType}<br>
            Price: \${$roomPrice}<br>
            Image URL: {$roomImageUrl}<br>
            Description: {$roomDescription}
        ";

        $feedback_message = "
            <span class='font-bold'>SUCCESS! Data Captured (Simulated Save):</span>
            <div class='mt-2 p-3 bg-gray-50 border border-gray-200 rounded-md text-sm'>
                {$data_output}
            </div>
            <p class='mt-2 text-xs italic'>
                (In a real server, this data would now be saved to your database).
            </p>
        ";
        $feedback_class = 'bg-green-100 text-green-700';
        
        // Clear variables after 'successful' simulation
        $roomName = $roomType = $roomPrice = $roomImageUrl = $roomDescription = '';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Detail Uploader (PHP Simulation)</title>
    <!-- Load Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7f9;
        }
        /* Container and Form Reset/Base Styles */
.booking-form-container {
  display: flex;
  justify-content: center;
  padding: 20px;
  background-color: #f4f7f6; /* Light background for contrast */
}

.room-booking-form {
  background-color: #ffffff;
  padding: 30px;
  border-radius: 12px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
  max-width: 500px; /* Limit form width */
  width: 100%;
  font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
}

.form-title {
  text-align: center;
  color: #333;
  margin-bottom: 25px;
  font-size: 1.8em;
  font-weight: 300;
}

/* Grouping Inputs */
.form-group {
  margin-bottom: 20px;
}

/* Layout for side-by-side inputs (Dates and Counts) */
.date-group, .count-group {
  display: flex;
  gap: 20px;
}

.input-field {
  flex: 1; /* Makes both inputs take equal space */
  min-width: 0;
}

/* Labels */
label {
  display: block;
  font-weight: 600;
  color: #555;
  margin-bottom: 5px;
  font-size: 0.95em;
}

/* Input and Select Field Styling */
input[type="date"], 
input[type="number"], 
select {
  width: 100%;
  padding: 12px;
  border: 1px solid #ccc;
  border-radius: 6px;
  box-sizing: border-box; /* Include padding/border in the element's total width/height */
  font-size: 1em;
  color: #333;
  transition: border-color 0.3s, box-shadow 0.3s;
  -webkit-appearance: none; /* Reset default browser styles */
  -moz-appearance: none;
  appearance: none;
}

/* Focus State */
input:focus, select:focus {
  border-color: #007bff; /* Primary blue color on focus */
  box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
  outline: none; /* Remove default focus outline */
}

/* Submit Button Styling */
.submit-button {
  width: 100%;
  padding: 15px;
  background-color: #007bff; /* Primary action color */
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 1.1em;
  font-weight: 700;
  cursor: pointer;
  transition: background-color 0.3s ease, transform 0.1s ease;
  text-transform: uppercase;
}

.submit-button:hover {
  background-color: #0056b3;
}

.submit-button:active {
  transform: translateY(1px);
}

/* --- Responsive Adjustments for Small Screens --- */
@media (max-width: 600px) {
  .date-group, .count-group {
    /* Stack inputs vertically on very small screens */
    flex-direction: column; 
    gap: 15px;
  }
  
  .room-booking-form {
    padding: 20px;
  }
}
    </style>
</head>
<body>

<div class="container mx-auto p-4 md:p-8">
    <header class="text-center mb-6">
        <h1 class="text-4xl font-extrabold text-gray-900 mb-2">Room Detail Uploader (PHP)</h1>
        <p class="text-xl text-gray-500">PHP Form Handling Simulation</p>
    </header>

    <!-- Feedback Message (PHP Output) -->
    <div id="feedback-message" class="mb-6 p-4 rounded-xl text-sm font-medium <?= $feedback_class; ?>">
        <?php echo $feedback_message; ?>
    </div>
    
    <!-- Room Upload Form -->
   <div class="booking-form-container">
  <form class="room-booking-form" action="/submit-booking" method="POST">
    
    <h3 class="form-title">Find Your Perfect Stay</h3>
    
    <div class="form-group date-group">
      <div class="input-field">
        <label for="check-in">Check-in Date</label>
        <input type="date" id="check-in" name="check-in" required>
      </div>
      <div class="input-field">
        <label for="check-out">Check-out Date</label>
        <input type="date" id="check-out" name="check-out" required>
      </div>
    </div>
    
    <div class="form-group count-group">
      <div class="input-field">
        <label for="guests">Guests</label>
        <input type="number" id="guests" name="guests" min="1" max="10" value="2" required>
      </div>
      <div class="input-field">
        <label for="rooms">Rooms</label>
        <input type="number" id="rooms" name="rooms" min="1" max="5" value="1" required>
      </div>
    </div>

    <div class="form-group">
      <label for="room-type">Room Type Preference</label>
      <select id="room-type" name="room-type">
        <option value="any">Any Room Type</option>
        <option value="standard">Standard Double</option>
        <option value="deluxe">Deluxe Suite</option>
        <option value="family">Family Room</option>
        <option value="penthouse">Penthouse</option>
      </select>
    </div>

    <button type="submit" class="submit-button">
      Search Availability
    </button>
    
  </form>
</div>
</div>

</body>
</html>