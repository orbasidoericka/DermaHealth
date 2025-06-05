<?php
include 'connect.php'; // Ensure this file establishes $conn correctly

// Fetch patient data
$sql = "SELECT fullname, gender, dateofbirth, address FROM patients";
$result = mysqli_query($conn, $sql);
if (!$result) {
    die("Query Failed: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Patients - DermaHealth</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: '#C2185B',
            accent: '#E03E8C',
            secondary: '#F36AA0',
            tertiary: '#F88FB4',
            soft: '#FBB5CD'
          },
          fontFamily: {
            pacifico: ['Pacifico', 'cursive'],
            inter: ['Inter', 'sans-serif']
          },
          borderRadius: {
            button: '8px'
          }
        }
      }
    };
  </script>

  <link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css"/>
  <style>
    /* Ensure html and body fill the viewport and hide main scrollbar */
    html, body {
      height: 100%;
      overflow: hidden; /* Hide the main browser scrollbar */
    }
  </style>
</head>

<body class="bg-pink-100 font-inter">
  <div class="flex h-screen">
    <aside class="w-64 bg-primary text-white p-6 hidden md:flex flex-col justify-between">
      <div>
        <div class="text-3xl font-pacifico mb-10">DermaHealth</div> <nav class="space-y-4">
          <a href="dashdoctor.php" class="flex items-center space-x-3 hover:bg-white/10 p-2 rounded text-base"> <i class="ri-dashboard-line"></i><span>Dashboard</span>
          </a>
          <a href="patientdoc.php" class="flex items-center space-x-3 bg-white/20 p-2 rounded font-semibold text-base"> <i class="ri-user-line"></i><span>Patients</span>
          </a>
          <a href="appointdoctor.php" class="flex items-center space-x-3 hover:bg-white/10 p-2 rounded text-base"> <i class="ri-calendar-line"></i><span>Appointments</span>
          </a>
          <a href="recordsdoc.php" class="flex items-center space-x-3 hover:bg-white/10 p-2 rounded text-base"> <i class="ri-file-list-3-line"></i><span>Medical Records</span>
          </a>
          <a href="labresultdoc.php" class="flex items-center space-x-3 hover:bg-white/10 p-2 rounded text-base"> <i class="ri-flask-line"></i><span>Lab Results</span>
          </a>
        </nav>
      </div>

      <div class="pt-6 border-t border-white/20">
        <a href="index.php" class="flex items-center space-x-3 hover:bg-white/10 p-2 rounded text-base"> <i class="ri-logout-box-r-line"></i><span>Logout</span>
        </a>
      </div>
    </aside>

    <div class="flex-1 flex flex-col h-full">
      <header class="bg-pink-50 p-6 flex-shrink-0"> <h1 class="text-4xl font-bold text-primary">Patients</h1> <p class="text-gray-600 mt-1">View and manage patient information.</p>
      </header>

      <main class="p-8 pb-24 flex-grow overflow-y-auto"> <div class="bg-white rounded-lg shadow p-6 overflow-x-auto">
          <table class="min-w-full table-auto text-left border-separate border-spacing-y-2">
            <thead class="text-sm text-gray-700 bg-pink-100">
              <tr>
                <th class="p-3">#</th>
                <th class="p-3">Full Name</th>
                <th class="p-3">Gender</th>
                <th class="p-3">Date of Birth</th>
                <th class="p-3">Address</th>
              </tr>
            </thead>
            <tbody class="text-sm text-gray-700">
              <?php
              if (mysqli_num_rows($result) > 0) {
                $i = 1;
                while ($row = mysqli_fetch_assoc($result)) {
                  echo "<tr class='bg-white shadow rounded hover:bg-pink-50 transition'>";
                  echo "<td class='p-3'>{$i}</td>";
                  echo "<td class='p-3'>" . htmlspecialchars($row['fullname']) . "</td>";
                  echo "<td class='p-3'>" . htmlspecialchars($row['gender']) . "</td>";
                  echo "<td class='p-3'>" . htmlspecialchars($row['dateofbirth']) . "</td>";
                  echo "<td class='p-3'>" . htmlspecialchars($row['address']) . "</td>";
                  echo "</tr>";
                  $i++;
                }
              } else {
                echo "<tr><td colspan='5' class='p-4 text-center text-gray-500'>No patients found.</td></tr>";
              }
              ?>
            </tbody>
          </table>
        </div>
      </main>
    </div>
  </div>
  <script>
    // Sidebar active link highlighting (uses 'patientdoc.php' as current page for example)
    const currentPage = window.location.pathname.split('/').pop();
    document.querySelectorAll("aside nav a").forEach(link => {
      // Remove any previously active classes
      link.classList.remove("bg-white/20", "font-semibold");
      // Add active classes if href matches current page
      if (link.getAttribute("href") === currentPage) {
        link.classList.add("bg-white/20", "font-semibold");
      }
    });
  </script>
</body>
</html>

<?php mysqli_close($conn); ?>