<?php
session_start();
include 'connect.php';

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = ($_POST['password']);
    $role = $_POST['role'];

    $sql = "SELECT * FROM users WHERE Email='$email' AND PasswordHash='$password' AND Role='$role'";
    $result = $conn->query($sql);

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $_SESSION['email'] = $user['Email'];
        $_SESSION['role'] = $user['Role'];

        switch ($role) {
            case 'doctor':
                header("Location: dashdoctor.php");
                break;
            case 'staff':
                header("Location: dashboard.php");
                break;
            case 'patient':
                header("Location: patient.php");
                break;
        }
        exit();
    } else {
        // Popup without redirect
        echo "<script>
            alert('Invalid email, password, or role.');
            window.history.back();
        </script>";
    }
}
?>
