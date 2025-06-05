<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userID = $_POST['UserID'];
    $gender = $_POST['Gender'];
    $dob = $_POST['DateOfBirth'];
    $address = $_POST['Address'];

    $sql = "INSERT INTO patients (UserID, Gender, DateOfBirth, Address) VALUES ('$userID', '$gender', '$dob', '$address')";
    
    if ($conn->query($sql) === TRUE) {
        echo "New patient added successfully!";
    } else {
        echo "Error: " . $conn->error;
    }
}
?>
