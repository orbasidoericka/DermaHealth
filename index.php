<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Welcome - DermaHealth</title>

  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com/3.4.16"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: '#A31641',   // Deep Magenta
            soft: '#FBB5CD',      // Pale Pink
            backdrop: '#E03E8C',  // Vivid Pink
            secondary: '#F36AA0', // Soft Pink
            accent: '#F88FB4'     // Light Rose
          },
          fontFamily: {
            pacifico: ['Pacifico', 'cursive'],
            inter: ['Inter', 'sans-serif']
          }
        }
      }
    }
  </script>

  <!-- Fonts and Icons -->
  <link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css" rel="stylesheet" />

  <style>
    body {
      background: radial-gradient(circle at center, #F36AA0, #E03E8C, #A31641);
    }

    .fade-out { opacity: 0; transition: opacity 0.8s ease; }
    .fade-in { opacity: 1; transition: opacity 0.8s ease; }
  </style>
</head>
<body class="min-h-screen flex items-center justify-center font-inter">

  <!-- Splash Logo -->
  <div id="logo-screen" class="flex flex-col items-center justify-center h-screen text-center transition-opacity duration-700">
    <div class="w-56 h-56 rounded-full bg-white p-4 shadow-2xl flex items-center justify-center">
      <img src="pinklogo.png" alt="DermaHealth Logo" class="w-full h-full object-contain rounded-full" />
    </div>
  </div>

  <!-- Role Selection -->
  <div id="role-selection" class="hidden fade-in flex items-center justify-center h-screen px-4">
    <div class="bg-white rounded-3xl shadow-2xl p-12 max-w-lg w-full space-y-10 text-center">
      <div>
        <h1 class="text-3xl font-bold text-gray-800">Welcome to</h1>
        <h2 class="text-4xl font-pacifico text-primary mt-2">DermaHealth</h2>
      </div>
      <div class="space-y-6">
        <p class="text-2xl sm:text-3xl font-semibold text-gray-900">Are you a doctor or patient?</p>
        <div class="flex justify-center gap-6">
          <button onclick="location.href='doclogin.php'"  class="w-36 py-3 flex items-center justify-center gap-2 bg-white text-primary border border-primary rounded-full text-lg font-bold hover:bg-primary hover:text-white transition shadow-lg">
            <i class="ri-stethoscope-line text-xl"></i> Doctor
          </button>
          <button onclick="location.href='patientsignup.php'" class="w-36 py-3 flex items-center justify-center gap-2 bg-white text-primary border border-primary rounded-full text-lg font-bold hover:bg-primary hover:text-white transition shadow-lg">
            <i class="ri-user-add-line text-xl"></i> Patient
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Script -->
  <script>
    window.addEventListener('DOMContentLoaded', () => {
      setTimeout(() => {
        document.getElementById('logo-screen').classList.add('fade-out');
        setTimeout(() => {
          document.getElementById('logo-screen').classList.add('hidden');
          document.getElementById('role-selection').classList.remove('hidden');
          document.getElementById('role-selection').classList.add('fade-in');
        }, 600);
      }, 1000);
    });
  </script>
</body>
</html>