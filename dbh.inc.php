<?php
// Database connection
$dsn = "mysql:host=localhost;dbname=dermahealth_clinic";
$dbusername = "root";
$dbpassword = "";

try {
    $pdo = new PDO($dsn, $dbusername, $dbpassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $patient_id = $_POST['patient_id'];
    $doctor_id = $_POST['doctor_id'];
    $datetime = $_POST['datetime'];
    $status = $_POST['status'];

    $sql = "INSERT INTO appointments (PatientID, DoctorID, AppointmentDateTime, Status)
            VALUES (:patient_id, :doctor_id, :datetime, :status)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':patient_id' => $patient_id,
        ':doctor_id' => $doctor_id,
        ':datetime' => $datetime,
        ':status' => $status
    ]);

    echo "<p>Appointment booked successfully!</p>";
}
?>

<!-- HTML Form to Book an Appointment -->
<form method="POST">
    Patient ID: <input type="number" name="patient_id" required><br>
    Doctor ID: <input type="number" name="doctor_id" required><br>
    Date and Time: <input type="datetime-local" name="datetime" required><br>
    Status: <input type="text" name="status" value="Scheduled"><br>
    <input type="submit" value="Book Appointment">
</form>
