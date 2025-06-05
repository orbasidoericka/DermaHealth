<?php
// doctorlogin_process.php
session_start();
error_reporting(E_ALL); // Report all PHP errors
ini_set('display_errors', 1); // Display errors (for development, turn off in production)

// ─────────────────────────────────────────────────────────────────────────────
// 1) Database Connection
// ─────────────────────────────────────────────────────────────────────────────
// Include the database connection file. This file should contain:
// $host = 'localhost';
// $user = 'root';
// $pass = '';
// $dbname = 'dermahealth_clinic';
// $conn = new mysqli($host, $user, $pass, $dbname);
// if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
require 'connect.php';

// Check if the database connection failed after including connect.php
// (though ideally connect.php would handle fatal errors itself, this is a fallback)
if ($conn->connect_error) {
    error_log("Database connection failed in doctorlogin_process.php: " . $conn->connect_error);
    $_SESSION['login_error'] = "A server error occurred. Please try again later.";
    // IMPORTANT: Redirect to the doctor login page
    header("Location: doclogin.php");
    exit();
}

// ─────────────────────────────────────────────────────────────────────────────
// 2) Handle Login Form Submission
// ─────────────────────────────────────────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Sanitize and get user input
    $email = trim($_POST["email"] ?? '');
    $password = $_POST["password"] ?? '';

    // Basic validation for empty fields
    if (empty($email) || empty($password)) {
        $_SESSION['login_error'] = "Please enter both email and password.";
        header("Location: doclogin.php");
        exit();
    }

    // Prepare statement to fetch all necessary doctor details
    // We now select DoctorID, PasswordHash, FullName, and LicenseNumber
    $stmt = $conn->prepare("SELECT DoctorID, PasswordHash, FullName, LicenseNumber FROM doctors WHERE Email = ?");

    // Check if the prepare statement failed
    if (!$stmt) {
        error_log("SQL prepare failed for doctors login query: (" . $conn->errno . ") " . $conn->error);
        $_SESSION['login_error'] = "An internal server error occurred. Please try again.";
        header("Location: doclogin.php");
        exit();
    }

    // Bind the email parameter and execute the statement
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result(); // Store result to check num_rows and fetch data

    // Check if a doctor with the provided email was found
    if ($stmt->num_rows === 1) {
        // Bind the result variables
        // IMPORTANT: Ensure the order matches the SELECT query
        $stmt->bind_result($doctorID, $hashedPassword, $fullName, $licenseNumber);
        $stmt->fetch(); // Fetch the results into the bound variables

        // Verify the provided password against the stored hashed password
        if (password_verify($password, $hashedPassword)) {
            // Login successful! Set session variables for the doctor
            $_SESSION['doctorID'] = $doctorID;
            $_SESSION['role'] = 'doctor'; // Explicitly set role for clarity
            $_SESSION['fullName'] = $fullName; // Store full name
            $_SESSION['licenseNumber'] = $licenseNumber; // Store license number

            // Redirect to the doctor dashboard
            header("Location: dashdoctor.php");
            exit(); // Always exit after a header redirect
        } else {
            // Password does not match
            // Provide a generic message for security (don't reveal if email exists)
            $_SESSION['login_error'] = "Invalid email or password.2";
            header("Location: doclogin.php");
            exit();
        }
    } else {
        // Email not found in the doctors table
        // Provide a generic message for security
        $_SESSION['login_error'] = "Invalid email or password.1";
        header("Location: doclogin.php");
        exit();
    }

    // Close the statement and connection (these should be at the end of the POST block)
    $stmt->close();
    $conn->close();

} else {
    // If someone tries to access this script directly via GET request (not a form submission),
    // redirect them to the login page.
    header("Location: doclogin.php");
    exit();
}
?>