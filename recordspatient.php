<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Medical Records</title>
  <script src="https://cdn.tailwindcss.com/3.4.16"></script>
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
        <a href="dashpatient.php" class="flex items-center gap-3 py-3 px-4 hover:bg-white/10 rounded-xl"> <i class="ri-dashboard-line text-lg"></i> Dashboard
        </a>
        <a href="appointpatient.php" class="flex items-center gap-3 py-3 px-4 hover:bg-white/10 rounded-xl"> <i class="ri-calendar-line text-lg"></i> Appointments
        </a>
        <a href="recordspatient.php" class="flex items-center gap-3 py-3 px-4 bg-white/20 font-semibold rounded-xl"> <i class="ri-file-list-3-line text-lg"></i> Medical Records
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
    <!-- Main Content -->
    <div class="flex-1 p-8">
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-primary">Your Medical Records</h1>
        <p class="text-gray-600 mt-2">View details of your past checkups and consultations</p>
      </div>

      <!-- Medical Records Table -->
      <div class="bg-white rounded-lg shadow-sm overflow-x-auto">
        <table class="w-full">
          <thead class="bg-pale/60">
            <tr>
              <th class="px-6 py-3 text-left text-sm font-semibold text-rose">Date</th>
              <th class="px-6 py-3 text-left text-sm font-semibold text-rose">Doctor</th>
              <th class="px-6 py-3 text-left text-sm font-semibold text-rose">Symptoms</th>
              <th class="px-6 py-3 text-left text-sm font-semibold text-rose">Diagnosis</th>
              <th class="px-6 py-3 text-left text-sm font-semibold text-rose">Prescription</th>
              <th class="px-6 py-3 text-left text-sm font-semibold text-rose">Notes</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 text-sm text-gray-700">
            <!-- Example record -->
            <tr class="hover:bg-gray-50">
              <td class="px-6 py-4">2025-06-01</td>
              <td class="px-6 py-4">Dr. Sarah Wilson</td>
              <td class="px-6 py-4">Headache, dizziness</td>
              <td class="px-6 py-4">Migraine</td>
              <td class="px-6 py-4">Ibuprofen, rest</td>
              <td class="px-6 py-4">Follow-up if symptoms persist</td>
            </tr>
            <!-- More rows can be generated dynamically -->
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <script>
  // Example: Replace with actual session/user data logic
  document.addEventListener("DOMContentLoaded", function () {
    const accountName = "Stephanie Dulay"; // Replace with dynamic data if needed
    document.getElementById("user-name").textContent = accountName;
  });
</script>

</body>
</html>


