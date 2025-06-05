<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Patient Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            // Re-using doctor dashboard colors for consistency
            primary: '#C2185B', // This will replace 'rose' for main elements
            accent: '#E03E8C',
            secondary: '#F36AA0',
            tertiary: '#F88FB4',
            soft: '#FBB5CD',
            cardlight: '#FDDDEB', // This is the light pink background for the cards
            darkText: '#4A4A4A' // A general dark text color for better contrast
          },
          fontFamily: {
            pacifico: ['Pacifico', 'cursive'],
            inter: ['Inter', 'sans-serif']
          },
          borderRadius: {
            'xl': '1rem',
            '2xl': '1rem', // Added for consistency with doctor dashboard cards
            'button': '8px' // Consistent with doctor dashboard
          }
        }
      }
    };
  </script>
  <link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" rel="stylesheet">
  <style>
    /* Ensure html and body fill the viewport and hide main scrollbar */
    html, body {
      height: 100%;
      overflow: hidden; /* Hide the main browser scrollbar */
    }
  </style>
</head>
<body class="bg-pink-100 font-inter min-h-screen flex"> <aside class="w-64 bg-primary text-white flex flex-col justify-between"> <div>
      <div class="flex flex-col items-center py-8">
        <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center">
          <i class="ri-user-line text-3xl text-primary"></i> </div>
        <p id="user-name" class="mt-4 text-lg font-semibold">Loading...</p>
      </div>

      <nav class="flex flex-col gap-2 px-6">
        <a href="dashpatient.php" class="flex items-center gap-3 py-3 px-4 bg-white/20 font-semibold rounded-xl"> <i class="ri-dashboard-line text-lg"></i> Dashboard
        </a>
        <a href="appointpatient.php" class="flex items-center gap-3 py-3 px-4 hover:bg-white/10 rounded-xl"> <i class="ri-calendar-line text-lg"></i> Appointments
        </a>
        <a href="recordspatient.php" class="flex items-center gap-3 py-3 px-4 hover:bg-white/10 rounded-xl"> <i class="ri-file-list-3-line text-lg"></i> Medical Records
        </a>
      </nav>
    </div>

    <div class="p-4 border-t border-white/20"> <form action="patientlogin.php" method="POST">
        <button type="submit" class="flex items-center w-full p-3 rounded-button hover:bg-white/10 transition-colors"> <i class="ri-logout-box-line w-6 h-6 flex items-center justify-center mr-3"></i>
          <span>Logout</span>
        </button>
      </form>
    </div>
  </aside>

  <div class="flex-1 p-8">
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-primary">Dashboard</h1> </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8"> <div class="bg-cardlight text-gray-800 shadow-xl rounded-2xl p-8 flex items-center gap-6 hover:shadow-2xl transition"> <div class="bg-white p-5 rounded-full shadow">
          <i class="ri-calendar-check-line text-3xl text-primary"></i> </div>
        <div>
          <h3 class="text-lg">Date Scheduled</h3> <p class="text-3xl font-bold mt-2">June 8, 2025</p> </div>
      </div>

      <div class="bg-cardlight text-gray-800 shadow-xl rounded-2xl p-8 flex items-center gap-6 hover:shadow-2xl transition"> <div class="bg-white p-5 rounded-full shadow">
          <i class="ri-file-list-3-line text-3xl text-primary"></i> </div>
        <div>
          <h3 class="text-lg">Medical Records</h3> <p class="text-3xl font-bold mt-2">5</p> </div>
      </div>

      <div class="bg-cardlight text-gray-800 shadow-xl rounded-2xl p-8 flex items-center gap-6 hover:shadow-2xl transition"> <div class="bg-white p-5 rounded-full shadow">
          <i class="ri-user-heart-line text-3xl text-primary"></i> </div>
        <div>
          <h3 class="text-lg">Primary Doctor</h3> <p class="text-3xl font-bold mt-2">Dr. Smith</p> </div>
      </div>

      <div class="bg-cardlight text-gray-800 shadow-xl rounded-2xl p-8 flex items-center gap-6 hover:shadow-2xl transition"> <div class="bg-white p-5 rounded-full shadow">
          <i class="ri-heart-pulse-line text-3xl text-primary"></i> </div>
        <div>
          <h3 class="text-lg">Last Checkup</h3> <p class="text-3xl font-bold mt-2">May 27, 2025</p> </div>
      </div>
    </div>
  </div>

  <script>
    // Simulate user data loading
    document.addEventListener("DOMContentLoaded", function () {
      const accountName = "Stephanie Dulay"; // Replace with dynamic user data if needed
      document.getElementById("user-name").textContent = accountName;
    });

    // Sidebar active link highlighting (copied from doctor dashboard for consistency)
    const currentPage = window.location.pathname.split('/').pop();
    document.querySelectorAll("aside nav a").forEach(link => {
      // Remove previous active state if any
      link.classList.remove("bg-pinkAccent", "bg-coral/70"); // Remove old active/hover classes
      link.classList.remove("bg-white/20", "font-semibold"); // Ensure these are removed first

      // Add new active state if href matches current page
      if (link.getAttribute("href") === currentPage) {
        link.classList.add("bg-white/20", "font-semibold");
      } else {
        // Add new hover state for non-active links
        link.classList.add("hover:bg-white/10");
      }
    });

    // Adjust specific link for dashboard if needed, as it had a different initial class
    const dashboardLink = document.querySelector('aside nav a[href="dashpatient.php"]');
    if (dashboardLink) {
        dashboardLink.classList.remove("bg-pinkAccent"); // Remove old active class
        dashboardLink.classList.add("bg-white/20", "font-semibold"); // Apply new active class
    }
  </script>
</body>
</html>