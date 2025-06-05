<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $patientID = $_POST['PatientID'];
    $gender = $_POST['Gender'];
    $dob = $_POST['DateOfBirth'];
    $address = $_POST['Address'];

    $sql = "UPDATE patients SET Gender='$gender', DateOfBirth='$dob', Address='$address' WHERE PatientID='$patientID'";
    
    if ($conn->query($sql) === TRUE) {
        echo "Patient updated successfully!";
    } else {
        echo "Error: " . $conn->error;
    }
}
?>
