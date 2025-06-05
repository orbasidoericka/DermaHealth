<?php
// DB connection
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'dermahealth_clinic';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Get and sanitize form inputs
$fullname = trim($_POST['fullname']);
$email = trim($_POST['email']);
$password = password_hash($_POST['password'], PASSWORD_BCRYPT);
$dob = $_POST['dob'];
$gender = $_POST['gender'];
$address = trim($_POST['address']);
$username = explode('@', $email)[0]; // Optional: derive username from email
$createdAt = date('Y-m-d H:i:s');

// Prepare and insert
$stmt = $conn->prepare("INSERT INTO patients (Username, PasswordHash, FullName, Email, CreatedAt, Gender, DateOfBirth, Address) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssssss", $username, $password, $fullname, $email, $createdAt, $gender, $dob, $address);

if ($stmt->execute()) {
    header("Location: patientlogin.php?signup=success");
    exit;
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
