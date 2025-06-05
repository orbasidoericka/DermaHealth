<?php
// ─────────────────────────────────────────────────────────────────────────────
// 1) Configuration and Error Reporting
// ─────────────────────────────────────────────────────────────────────────────
error_reporting(E_ALL);
ini_set('display_errors', 1); // Display errors for debugging (turn off in production)
session_start(); // Start session if you plan to use it later (e.g., for login after signup)

// ─────────────────────────────────────────────────────────────────────────────
// 2) Database Connection
// ─────────────────────────────────────────────────────────────────────────────
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'dermahealth_clinic'; // This is the variable holding the database name

// Establish connection using $dbname
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
  // Log the error securely and provide a generic message to the user
  error_log("Database connection failed: " . $conn->connect_error);
  die("Connection to the database failed. Please try again later.");
}

// ─────────────────────────────────────────────────────────────────────────────
// 3) Process Form Submission
// ─────────────────────────────────────────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get and sanitize/validate form data
    $fullname = isset($_POST['fullname']) ? trim($_POST['fullname']) : '';
    $email = isset($_POST['email']) ? filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL) : ''; // Validate email format
    $password = isset($_POST['password']) ? $_POST['password'] : ''; // Get raw password for hashing
    $dob = isset($_POST['dob']) ? $_POST['dob'] : '';
    $gender = isset($_POST['gender']) ? $_POST['gender'] : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';

    // Server-side validation for required fields
    if (empty($fullname) || $email === false || empty($password) || empty($dob) || empty($gender) || empty($address)) {
        // Provide a specific and user-friendly error message
        die("Missing or invalid required fields. Please ensure full name, a valid email, password, birthday, gender, and address are provided.");
    }

    // Hash the password securely
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

    // Assign username (as per your previous code, it's the full name)
    $username = $fullname;
    $createdAt = date('Y-m-d H:i:s');

    // Prepare SQL statement for inserting into the patients table
    // Ensure your 'patients' table columns exactly match this:
    // Username, PasswordHash, FullName, Email, CreatedAt, Gender, DateOfBirth, Address
    $stmt = $conn->prepare("INSERT INTO patients (Username, PasswordHash, FullName, Email, CreatedAt, Gender, DateOfBirth, Address) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    if ($stmt === false) {
        // Handle prepare error
        error_log("SQL prepare failed: (" . $conn->errno . ") " . $conn->error);
        die("An internal error occurred. Please try again.");
    }

    // Bind parameters to the prepared statement
    // 'ssssssss' indicates 8 string parameters
    $stmt->bind_param("ssssssss", $username, $passwordHash, $fullname, $email, $createdAt, $gender, $dob, $address);

    try {
        // Execute the statement
        if ($stmt->execute()) {
            // Redirect on successful registration
            header("Location: patientlogin.php?signup=success"); // Add a success parameter for feedback
            exit(); // Always exit after a header redirect
        } else {
            // Handle execution error
            error_log("SQL execute failed: (" . $stmt->errno . ") " . $stmt->error);
            echo "<script>alert('Error: Could not register. Please try again.')</script>";
        }
    } catch (mysqli_sql_exception $e) {
        // Catch specific database errors (e.g., duplicate email if email is unique)
        if ($e->getCode() == 1062) { // MySQL error code for duplicate entry
            echo "<script>alert('Error: This email address is already registered. Please use a different one or login.')</script>";
        } else {
            error_log("Database error during registration: " . $e->getMessage());
            echo "<script>alert('An unexpected database error occurred. Please try again later.')</script>";
        }
    } finally {
        // Ensure statement and connection are closed
        $stmt->close();
        $conn->close();
    }
} else {

    echo "";
    $conn->close(); 
}
?>

<!-- patientsignup.html -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Patient Sign Up - DermaHealth</title>

  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com/3.4.16"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: '#A31641',
            accent: '#E03E8C',
            secondary: '#F36AA0',
            tertiary: '#F88FB4',
            soft: '#FBB5CD'
          },
          fontFamily: {
            pacifico: ['Pacifico', 'cursive'],
            inter: ['Inter', 'sans-serif']
          }
        }
      }
    }
  </script>

  <link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css" rel="stylesheet" />

  <style>
    body {
      background: linear-gradient(to bottom, #A31641, #E03E8C, #F36AA0, #F88FB4, #FBB5CD);
    }
  </style>
</head>
<body class="min-h-screen flex items-center justify-center px-4 font-inter">

  <div class="bg-white p-10 rounded-3xl shadow-2xl w-full max-w-md space-y-6 text-center">
    <img src="pinklogo.png" alt="DermaHealth Logo" class="mx-auto w-20 h-20">
    <div class="text-4xl font-pacifico text-primary">DermaHealth</div>

    <!-- ✅ Form fixed to submit to PHP with correct fields -->
    <form class="space-y-4 text-left mt-4" method="POST" action="patientsignup.php">
      <input type="text" name="fullname" placeholder="Full Name" required
             class="w-full px-4 py-2 border rounded-xl focus:outline-none focus:ring-2 focus:ring-primary"/>

      <input id="birthday" name="dob" type="text" placeholder="Birthday" required
             class="w-full px-4 py-2 border rounded-xl focus:outline-none focus:ring-2 focus:ring-primary"
             onfocus="this.type='date'" onblur="if(!this.value)this.type='text'" />

      <select name="gender" required
              class="w-full px-4 py-2 border rounded-xl focus:outline-none focus:ring-2 focus:ring-primary text-gray-700">
        <option value="" disabled selected>Select Gender</option>
        <option value="female">Female</option>
        <option value="male">Male</option>
        <option value="other">Other</option>
      </select>

      <input type="text" name="address" placeholder="Address" required
             class="w-full px-4 py-2 border rounded-xl focus:outline-none focus:ring-2 focus:ring-primary"/>

      <input type="email" name="email" placeholder="Email" required
             class="w-full px-4 py-2 border rounded-xl focus:outline-none focus:ring-2 focus:ring-primary"/>

      <input type="password" name="password" placeholder="Password" required
             class="w-full px-4 py-2 border rounded-xl focus:outline-none focus:ring-2 focus:ring-primary"/>

      <button type="submit"
              class="w-full bg-primary text-white py-2 rounded-full font-semibold hover:bg-accent transition shadow-md">
        <i class="ri-user-add-line mr-2"></i>Sign Up
      </button>
    </form>

    <p class="text-sm text-gray-700">
      Already have an account?
      <a href="patientlogin.php" class="text-primary font-semibold underline hover:text-accent">Login here</a>
    </p>
  </div>
</body>
</html>
