<?php
// dashdoctor.php
// Include the process file to get all dashboard data
require_once 'dashdoctor_process.php'; 
// Note: session_start() is already called in dashdoctor_process.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard - DermaHealth</title>

  <script src="https://cdn.tailwindcss.com/3.4.16"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: '#C2185B',  // Updated sidebar color
            accent: '#E03E8C',
            secondary: '#F36AA0',
            tertiary: '#F88FB4',
            soft: '#FBB5CD',
            cardlight: '#FDDDEB'
          },
          fontFamily: {
            pacifico: ['Pacifico', 'cursive'],
            inter: ['Inter', 'sans-serif']
          }
        }
      }
    };
  </script>

  <link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css" />

  <style>
    /* Ensure html and body fill the viewport and hide main scrollbar */
    html, body {
      height: 100%; /* Use 100% for consistency with vh on the container */
      overflow: hidden; /* Hide the main browser scrollbar */
    }
  </style>
</head>

<body class="bg-pink-100 font-inter">
  <div class="flex h-screen"> 
    <aside class="w-64 bg-primary text-white p-6 hidden md:flex flex-col justify-between">
      <div>
        <div class="text-3xl font-pacifico mb-10">DermaHealth</div>
        <nav class="space-y-4">
          <a href="dashdoctor.php" class="flex items-center space-x-3 bg-white/20 font-semibold p-2 rounded text-base">
            <i class="ri-dashboard-line"></i><span>Dashboard</span>
          </a>
          <a href="patientdoc.php" class="flex items-center space-x-3 hover:bg-white/10 p-2 rounded text-base">
            <i class="ri-user-line"></i><span>Patients</span>
          </a>
          <a href="appointdoctor.php" class="flex items-center space-x-3 hover:bg-white/10 p-2 rounded text-base">
            <i class="ri-calendar-line"></i><span>Appointments</span>
          </a>
          <a href="recordsdoc.php" class="flex items-center space-x-3 hover:bg-white/10 p-2 rounded text-base">
            <i class="ri-file-list-3-line"></i><span>Medical Records</span>
          </a>
          <a href="labresultdoc.php" class="flex items-center space-x-3 hover:bg-white/10 p-2 rounded text-base">
            <i class="ri-flask-line"></i><span>Lab Results</span>
          </a>
        </nav>
      </div>
      <div class="pt-6 border-t border-white/20">
        <a href="index.php" class="flex items-center space-x-3 hover:bg-white/10 p-2 rounded text-base">
          <i class="ri-logout-box-r-line"></i><span>Logout</span>
        </a>
      </div>
    </aside>

    <div class="flex-1 flex flex-col h-full">
      <header class="bg-pink-50 p-6 flex-shrink-0">
        <div class="text-4xl font-semibold text-primary">Dashboard</div>
      </header>

      <main class="p-8 pb-24 flex-grow overflow-y-auto"> 
        <h2 class="text-3xl font-bold mb-8 text-gray-800">Welcome back, <?= $doctorFullName ?>!</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-8">
          <div class="bg-cardlight text-gray-800 shadow-xl rounded-2xl p-8 flex items-center gap-6 hover:shadow-2xl transition">
            <div class="bg-white p-5 rounded-full shadow">
              <i class="ri-calendar-line text-3xl text-primary"></i>
            </div>
            <div>
              <h3 class="text-lg">Today's Appointments</h3>
              <p class="text-5xl font-bold mt-2"><?= $todaysAppointmentsCount ?></p>
            </div>
          </div>

          <div class="bg-cardlight text-gray-800 shadow-xl rounded-2xl p-8 flex items-center gap-6 hover:shadow-2xl transition">
            <div class="bg-white p-5 rounded-full shadow">
              <i class="ri-user-heart-line text-3xl text-primary"></i>
            </div>
            <div>
              <h3 class="text-lg">Active Patients</h3>
              <p class="text-5xl font-bold mt-2"><?= $activePatientsCount ?></p>
            </div>
          </div>

          <div class="bg-cardlight text-gray-800 shadow-xl rounded-2xl p-8 flex items-center gap-6 hover:shadow-2xl transition">
            <div class="bg-white p-5 rounded-full shadow">
              <i class="ri-flask-line text-3xl text-primary"></i>
            </div>
            <div>
              <h3 class="text-lg">Lab Results</h3>
              <p class="text-5xl font-bold mt-2"><?= $labResultsCount ?></p>
            </div>
          </div>

          <div class="bg-cardlight text-gray-800 shadow-xl rounded-2xl p-8 flex items-center gap-6 hover:shadow-2xl transition">
            <div class="bg-white p-5 rounded-full shadow">
              <i class="ri-stethoscope-line text-3xl text-primary"></i>
            </div>
            <div>
              <h3 class="text-lg">Doctors On Duty</h3>
              <p class="text-5xl font-bold mt-2"><?= $doctorsOnDutyCount ?></p>
            </div>
          </div>

          <div class="bg-cardlight text-gray-800 shadow-xl rounded-2xl p-8 flex items-center gap-6 hover:shadow-2xl transition">
            <div class="bg-white p-5 rounded-full shadow">
              <i class="ri-file-list-line text-3xl text-primary"></i>
            </div>
            <div>
              <h3 class="text-lg">Records This Month</h3>
              <p class="text-5xl font-bold mt-2"><?= $recordsThisMonthCount ?></p>
            </div>
          </div>
          
        </div>
      </main>
    </div>
  </div>

  <script>
    const currentPage = window.location.pathname.split('/').pop();
    document.querySelectorAll("aside nav a").forEach(link => {
      // Remove previous active state if any (important for dynamic highlighting)
      link.classList.remove("bg-white/20", "font-semibold"); 
      // Add active state if href matches current page
      if (link.getAttribute("href") === currentPage) {
        link.classList.add("bg-white/20", "font-semibold");
      }
    });
  </script>
</body>
</html>