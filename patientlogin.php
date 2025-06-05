<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Patient Login - DermaHealth</title>

  <script src="https://cdn.tailwindcss.com/3.4.16"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: '#A31641',
            accent: '#E03E8C',
            secondary: '#F36AA0',
            tertiary: '#F88FB4',
            soft: '#FBB5CD'
          },
          fontFamily: {
            pacifico: ['Pacifico', 'cursive'],
            inter: ['Inter', 'sans-serif']
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
    
    <img src="pinklogo.png" alt="DermaHealth Logo" class="mx-auto w-20 h-20">

    <div class="text-4xl font-pacifico text-primary">DermaHealth</div>

    <?php
    // Start session to access error messages from patientlogin_process.php
    session_start();

    // Display error message if set in session
    if (isset($_SESSION['login_error'])) {
        echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">';
        echo '<strong class="font-bold">Error!</strong>';
        echo '<span class="block sm:inline"> ' . htmlspecialchars($_SESSION['login_error']) . '</span>';
        echo '</div>';
        unset($_SESSION['login_error']); // Clear the error message after displaying
    }

    // Display signup success message
    if (isset($_GET['signup']) && $_GET['signup'] == 'success') {
        echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">';
        echo '<strong class="font-bold">Success!</strong>';
        echo '<span class="block sm:inline"> Your account has been created. Please log in.</span>';
        echo '</div>';
    }
    ?>

    <form class="space-y-4 text-left mt-4" action="patientlogin_process.php" method="POST">
      <input type="email" name="email" placeholder="Email" required
        class="w-full px-4 py-2 border rounded-xl focus:outline-none focus:ring-2 focus:ring-primary" />

      <input type="password" name="password" placeholder="Password" required
        class="w-full px-4 py-2 border rounded-xl focus:outline-none focus:ring-2 focus:ring-primary" />

      <button type="submit"
        class="w-full bg-primary text-white py-2 rounded-full font-semibold hover:bg-accent transition shadow-md">
        <i class="ri-login-box-line mr-2"></i>Login
      </button>
    </form>

    <p class="text-sm text-gray-700">
      Don’t have an account?
      <a href="patientsignup.php" class="text-primary font-semibold underline hover:text-accent">Sign up</a>
    </p>
  </div>

  </body>
</html>