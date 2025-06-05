<?php
session_start(); // Always start the session at the very top of your PHP file
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Doctor Login - DermaHealth</title>

  <script src="https://cdn.tailwindcss.com/3.4.16"></script>
  <script>
    // Tailwind CSS configuration for custom colors and fonts
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: '#A31641',   // Deep Berry Red
            accent: '#E03E8C',    // Vibrant Pink
            secondary: '#F36AA0', // Medium Pink
            tertiary: '#F88FB4',  // Light Pink
            soft: '#FBB5CD'       // Very Light Pink
          },
          fontFamily: {
            pacifico: ['Pacifico', 'cursive'], // For brand name
            inter: ['Inter', 'sans-serif']     // For general text
          }
        }
      }
    }
  </script>

  <link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css" rel="stylesheet" />

  <style>
    body {
      background: linear-gradient(to bottom, #A31641, #E03E8C, #F36AA0, #F88FB4, #FBB5CD);
    }
  </style>
</head>
<body class="min-h-screen flex items-center justify-center px-4 font-inter">

  <div class="bg-white p-10 rounded-3xl shadow-2xl w-full max-w-md space-y-8 text-center">
    
    <img src="pinklogo.png" alt="DermaHealth Logo" class="mx-auto w-24 h-24 mb-2" />

    <div class="text-4xl font-pacifico text-primary mb-4">DermaHealth</div>

    <?php
    // PHP block to display login error messages
    // Checks if 'login_error' is set in the session (set by doctorlogin_process.php)
    if (isset($_SESSION['login_error'])) {
        echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">';
        echo '<strong class="font-bold">Error!</strong>';
        echo '<span class="block sm:inline"> ' . htmlspecialchars($_SESSION['login_error']) . '</span>'; // Display the error message safely
        echo '</div>';
        unset($_SESSION['login_error']); // Clear the error message after displaying it
    }
    ?>

    <form action="doctorlogin_process.php" method="POST" class="space-y-4 text-left">
      
      <input type="text" placeholder="Doctor ID"
             class="w-full px-4 py-2 border rounded-xl focus:outline-none focus:ring-2 focus:ring-primary" /> 

      <input type="text" placeholder="Full Name"
             class="w-full px-4 py-2 border rounded-xl focus:outline-none focus:ring-2 focus:ring-primary" />

      <input type="text" placeholder="License Number"
             class="w-full px-4 py-2 border rounded-xl focus:outline-none focus:ring-2 focus:ring-primary" />

      <input type="email" name="email" placeholder="Email"
             class="w-full px-4 py-2 border rounded-xl focus:outline-none focus:ring-2 focus:ring-primary" required />

      <input type="password" name="password" placeholder="Password"
             class="w-full px-4 py-2 border rounded-xl focus:outline-none focus:ring-2 focus:ring-primary" required />

      <button type="submit"
              class="w-full bg-primary text-white py-2 rounded-full font-semibold hover:bg-accent transition shadow-md">
        <i class="ri-login-box-line mr-2"></i>Login
      </button>
    </form>

    <p class="text-sm text-gray-700">
      Are you a patient?
      <a href="patientsignup.php" class="text-primary font-semibold hover:underline">Sign up here</a>
    </p>
  </div>

</body>
</html>