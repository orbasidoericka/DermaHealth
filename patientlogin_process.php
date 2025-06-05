<?php
// patientlogin_process.php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ─────────────────────────────────────────────────────────────────────────────
// 1) Database Connection
// ─────────────────────────────────────────────────────────────────────────────
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'dermahealth_clinic';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    $_SESSION['login_error'] = "A server error occurred. Please try again later.";
    header("Location: patientlogin.php"); // Redirect back to login page
    exit();
}

// ─────────────────────────────────────────────────────────────────────────────
// 2) Handle Login Form Submission
// ─────────────────────────────────────────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? ''; // Raw password from form

    // Basic validation
    if (empty($email) || empty($password)) {
        $_SESSION['login_error'] = "Please enter both email and password.";
        header("Location: patientlogin.php");
        exit();
    }

    // Prepare statement to prevent SQL injection
    // Fetch PatientID and PasswordHash directly from the patients table
    $stmt = $conn->prepare("SELECT PatientID, PasswordHash FROM patients WHERE Email = ?");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        $_SESSION['login_error'] = "An internal error occurred. Please try again.";
        header("Location: patientlogin.php");
        exit();
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $patient_data = $result->fetch_assoc();
        $patient_id = $patient_data['PatientID'];
        $hashed_password = $patient_data['PasswordHash']; // Get the hashed password directly from patients table

        // Verify the password
        if (password_verify($password, $hashed_password)) {
            // Login successful!
            $_SESSION['patient_id'] = $patient_id; // Store patient_id in session
            $_SESSION['logged_in'] = true; // Indicate user is logged in

            // Optionally, you might want to fetch and store the patient's full name
            // for display purposes on the dashboard, if it's not already in session.
            // Example:
            // $stmt_name = $conn->prepare("SELECT FullName FROM patients WHERE PatientID = ?");
            // $stmt_name->bind_param("i", $patient_id);
            // $stmt_name->execute();
            // $name_result = $stmt_name->get_result();
            // if ($name_row = $name_result->fetch_assoc()) {
            //     $_SESSION['full_name'] = $name_row['FullName'];
            // }

            header("Location: dashpatient.php"); // Redirect to patient dashboard
            exit();
        } else {
            // Password does not match
            $_SESSION['login_error'] = "Invalid email or password.";
            header("Location: patientlogin.php");
            exit();
        }
    } else {
        // Email not found in database
        $_SESSION['login_error'] = "Invalid email or password.";
        header("Location: patientlogin.php");
        exit();
    }
} else {
    // If someone tries to access this script directly via GET
    header("Location: patientlogin.php");
    exit();
}

$stmt->close();
$conn->close();
?>