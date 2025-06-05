<?php
include 'db.php';

if (isset($_GET['id'])) {
    $patientID = $_GET['id'];

    $sql = "DELETE FROM patients WHERE PatientID='$patientID'";
    
    if ($conn->query($sql) === TRUE) {
        echo "Patient deleted successfully!";
    } else {
        echo "Error: " . $conn->error;
    }
}
?>
