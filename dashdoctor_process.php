<?php
// dashdoctor_process.php
session_start(); // Start session to access doctor's name if logged in

// Include the database connection
require_once 'connect.php';

// Initialize variables with default values in case queries fail
$todaysAppointmentsCount = 0;
$activePatientsCount = 0;
$labResultsCount = 0;
$doctorsOnDutyCount = 0;
$recordsThisMonthCount = 0;
$doctorFullName = 'Doctor'; // Default name if not logged in

// Fetch doctor's full name from session
if (isset($_SESSION['fullName']) && $_SESSION['role'] === 'doctor') {
    $doctorFullName = htmlspecialchars($_SESSION['fullName']);
} else {
    // If not logged in or session invalid, redirect to login page
    header("Location: doclogin.php");
    exit();
}

// --- Fetch Statistics ---

// 1. Today's Appointments
$sql_appointments = "SELECT COUNT(*) AS count FROM appointments WHERE DATE(AppointmentDate) = CURDATE()";
$result_appointments = $conn->query($sql_appointments);
if ($result_appointments) {
    $row = $result_appointments->fetch_assoc();
    $todaysAppointmentsCount = $row['count'];
} else {
    error_log("DB_ERROR: dashdoctor_process.php - Failed to fetch today's appointments: " . $conn->error);
}

// 2. Active Patients (Total patients in the patients table)
$sql_patients = "SELECT COUNT(*) AS count FROM patients";
$result_patients = $conn->query($sql_patients);
if ($result_patients) {
    $row = $result_patients->fetch_assoc();
    $activePatientsCount = $row['count'];
} else {
    error_log("DB_ERROR: dashdoctor_process.php - Failed to fetch active patients: " . $conn->error);
}

// 3. Lab Results (Total records in labresults table)
$sql_labresults = "SELECT COUNT(*) AS count FROM labresults";
$result_labresults = $conn->query($sql_labresults);
if ($result_labresults) {
    $row = $result_labresults->fetch_assoc();
    $labResultsCount = $row['count'];
} else {
    error_log("DB_ERROR: dashdoctor_process.php - Failed to fetch lab results: " . $conn->error);
}

// 4. Doctors On Duty (Total doctors in the doctors table)
$sql_doctors = "SELECT COUNT(*) AS count FROM doctors";
$result_doctors = $conn->query($sql_doctors);
if ($result_doctors) {
    $row = $result_doctors->fetch_assoc();
    $doctorsOnDutyCount = $row['count'];
} else {
    error_log("DB_ERROR: dashdoctor_process.php - Failed to fetch doctors on duty: " . $conn->error);
}

// 5. Records This Month (Medical records within the current month)
$sql_medicalrecords = "SELECT COUNT(*) AS count FROM medicalrecords WHERE YEAR(RecordDate) = YEAR(CURDATE()) AND MONTH(RecordDate) = MONTH(CURDATE())";
$result_medicalrecords = $conn->query($sql_medicalrecords);
if ($result_medicalrecords) {
    $row = $result_medicalrecords->fetch_assoc();
    $recordsThisMonthCount = $row['count'];
} else {
    error_log("DB_ERROR: dashdoctor_process.php - Failed to fetch medical records this month: " . $conn->error);
}

// Close the database connection
$conn->close();
?>