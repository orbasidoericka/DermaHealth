<?php 

include 'connect.php';

//signup
if (isset($_POST['create']))
{
    $email=$_POST['email'];
    $fullname=$_POST['fullname'];
    $role=$_POST['role'];
    $password=$_POST['password'];
    $password=md5($password);

    $checkEmail="SELECT * From users where Email='$email'";
    $result=$conn->query($checkEmail);
    if($result->num_rows>0)
    {
        echo "Email  Already Exists!";
    }
    else
    {
        $insertQuery="INSERT INTO users (Email, FullName, Role, PasswordHash)
                        VALUES ('$email', '$fullname', '$role', '$password')";
            if($conn->query($insertQuery)==TRUE)
            {
                header("Location: index.php");
            }
            else 
            {
                echo "Error: ".$conn->error;
            }
    }

}

session_start();
include 'connect.php';

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = md5($_POST['password']);
    $role = $_POST['role'];

    $sql = "SELECT * FROM users WHERE Email='$email' AND PasswordHash='$password' AND Role='$role'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $_SESSION['email'] = $row['email'];
        $_SESSION['password'] = $row['password'];
        $_SESSION['role'] = $row['role'];

        if ($role === 'Admin') {
            header("Location: dashdoctor.php");
        } elseif ($role === 'staff') {
            header("Location: dashstaff.php");
        } elseif ($role === 'Patient') {
            header("Location: dashpatient.php");
        }
        exit();
    } else {
        echo "<script>alert('Invalid email, password, or role.'); window.location.href='index.php';</script>";
        exit();
    }
}

?>

