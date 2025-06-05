<?php
session_start();

$conn = new mysqli("localhost", "root", "", "dermahealth_clinic");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_appointment_id'])) {
    $deleteId = $_POST['delete_appointment_id'];
    $stmt = $conn->prepare("DELETE FROM appointments WHERE AppointmentID = ?");
    $stmt->bind_param("i", $deleteId);
    $stmt->execute();
    $stmt->close();
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_id'], $_POST['new_status'])) {
    $appointmentId = $_POST['appointment_id'];
    $newStatus = $_POST['new_status'];

    // IMPORTANT: Add 'Approved' and 'Cancelled' to validStatuses
    $validStatuses = ['Pending', 'Approved', 'Completed', 'Cancelled'];
    if (in_array($newStatus, $validStatuses)) {
        $stmt = $conn->prepare("UPDATE appointments SET Status = ? WHERE AppointmentID = ?");
        $stmt->bind_param("si", $newStatus, $appointmentId);
        $stmt->execute();
        $stmt->close();
    }
}

// Filter & Search
$searchTerm = $_GET['search'] ?? '';
$filterStatus = $_GET['status'] ?? 'All';

$sql = "SELECT a.*, p.FullName AS PatientFullName, d.FullName AS DoctorFullName
        FROM appointments a
        JOIN patients p ON a.PatientID = p.PatientID
        JOIN doctors d ON a.DoctorID = d.DoctorID
        WHERE a.AppointmentDate >= NOW()";

$params = [];
$types = '';

if ($searchTerm) {
    $sql .= " AND (p.FullName LIKE ? OR d.FullName LIKE ?)";
    $params[] = "%$searchTerm%";
    $params[] = "%$searchTerm%";
    $types .= 'ss';
}

if ($filterStatus !== 'All') {
    $sql .= " AND a.Status = ?";
    $params[] = $filterStatus;
    $types .= 's';
}

$sql .= " ORDER BY a.AppointmentDate ASC";
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Appointments - DermaHealth</title>
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
          }
        }
      }
    };
  </script>
  <link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css"/>
  <style>
    html, body { height: 100%; overflow: hidden; }
    .modal { background-color: rgba(0, 0, 0, 0.5); }
  </style>
</head>
<body class="bg-pink-100 font-inter">
<div class="flex h-screen">
  <aside class="w-64 bg-primary text-white p-6 hidden md:flex flex-col justify-between">
    <div>
      <div class="text-3xl font-pacifico mb-10">DermaHealth</div>
      <nav class="space-y-4">
        <a href="dashdoctor.php" class="flex items-center space-x-3 hover:bg-white/10 p-2 rounded"><i class="ri-dashboard-line"></i><span>Dashboard</span></a>
        <a href="patientdoc.php" class="flex items-center space-x-3 hover:bg-white/10 p-2 rounded"><i class="ri-user-line"></i><span>Patients</span></a>
        <a href="appointdoctor.php" class="flex items-center space-x-3 bg-white/20 font-semibold p-2 rounded"><i class="ri-calendar-line"></i><span>Appointments</span></a>
        <a href="recordsdoc.php" class="flex items-center space-x-3 hover:bg-white/10 p-2 rounded"><i class="ri-file-list-3-line"></i><span>Medical Records</span></a>
        <a href="labresultdoc.php" class="flex items-center space-x-3 hover:bg-white/10 p-2 rounded"><i class="ri-flask-line"></i><span>Lab Results</span></a>
      </nav>
    </div>
    <div class="pt-6 border-t border-white/20">
      <a href="index.php" class="flex items-center space-x-3 hover:bg-white/10 p-2 rounded"><i class="ri-logout-box-r-line"></i><span>Logout</span></a>
    </div>
  </aside>

    <div class="flex-1 flex flex-col h-full">
    <header class="bg-pink-50 p-6 flex-shrink-0">
      <h1 class="text-4xl font-bold text-primary">Appointments</h1>
    </header>

    <main class="p-8 pb-24 flex-grow overflow-y-auto">
      <header class="mb-6 flex flex-col md:flex-row justify-between items-center gap-4">
        <form method="GET" class="flex flex-col md:flex-row items-center gap-3 w-full md:w-auto">
          <input type="text" name="search" placeholder="Search by patient or doctor..." value="<?= htmlspecialchars($searchTerm) ?>" class="px-4 py-2 rounded-md border border-gray-300 w-full md:w-64 focus:outline-none focus:ring-2 focus:ring-primary"/>
          <select name="status" class="px-4 py-2 rounded-md border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary" onchange="this.form.submit()">
            <option value="All" <?= ($filterStatus === 'All') ? 'selected' : '' ?>>All</option>
            <option value="Pending" <?= ($filterStatus === 'Pending') ? 'selected' : '' ?>>Pending</option>
            <option value="Approved" <?= ($filterStatus === 'Approved') ? 'selected' : '' ?>>Approved</option>
            <option value="Completed" <?= ($filterStatus === 'Completed') ? 'selected' : '' ?>>Completed</option>
            <option value="Cancelled" <?= ($filterStatus === 'Cancelled') ? 'selected' : '' ?>>Cancelled</option>
          </select>
          <button type="submit" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-accent transition w-full md:w-auto">Search</button>
        </form>
      </header>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if ($result->num_rows > 0): ?>
          <?php while($row = $result->fetch_assoc()): ?>
            <?php
              $date = date('F j, Y', strtotime($row['AppointmentDate']));
              $time = date('g:i A', strtotime($row['AppointmentDate']));
              $status = $row['Status'];
              $colorClass = match ($status) {
                'Pending' => 'accent',
                'Approved' => 'blue', // Assign a specific color for 'Approved'
                'Completed' => 'green',
                'Cancelled' => 'red', // Assign a specific color for 'Cancelled'
                default => 'gray'
              };
            ?>
            <div class="relative bg-white border-l-4 border-<?= $colorClass ?>-500 rounded-lg shadow p-6 hover:shadow-lg transition group">
              <h3 class="font-semibold text-lg text-gray-700 mb-2"><?= htmlspecialchars($row['DoctorFullName']) ?></h3>
              <p><strong>Patient:</strong> <?= htmlspecialchars($row['PatientFullName']) ?></p>
              <p><strong>Date:</strong> <?= $date ?></p>
              <p><strong>Time:</strong> <?= $time ?></p>
              <p class="text-<?= $colorClass ?>-600 mt-2 flex items-center gap-1"><i class="ri-time-line"></i> <?= htmlspecialchars($status) ?></p>
              <form method="POST" class="mt-3">
                <select name="new_status" class="text-sm border border-gray-300 rounded px-2 py-1 mr-2 focus:ring-1 focus:ring-primary">
                  <option value="Pending" <?= $status === 'Pending' ? 'selected' : '' ?>>Pending</option>
                  <option value="Approved" <?= $status === 'Approved' ? 'selected' : '' ?>>Approved</option>
                  <option value="Completed" <?= $status === 'Completed' ? 'selected' : '' ?>>Completed</option>
                  <option value="Cancelled" <?= $status === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
                <input type="hidden" name="appointment_id" value="<?= $row['AppointmentID'] ?>">
                <button type="submit" class="text-sm text-white bg-primary px-3 py-1 rounded hover:bg-accent transition">Update</button>
              </form>
              <button onclick="openModal(<?= $row['AppointmentID'] ?>)" class="absolute top-3 right-3 text-gray-500 hover:text-red-600 transition">
                <i class="ri-delete-bin-line text-xl"></i>
              </button>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <p class="text-gray-500 text-lg">No upcoming appointments found matching your criteria.</p>
        <?php endif; ?>
      </div>
    </main>
  </div>
</div>

<div id="deleteModal" class="fixed inset-0 hidden items-center justify-center modal z-50">
  <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-sm text-center relative">
    <h2 class="text-xl font-semibold mb-4 text-gray-800">Delete Appointment?</h2>
    <p class="text-gray-600 mb-6">Are you sure you want to delete this appointment? This action cannot be undone.</p>
    <form method="POST" id="deleteForm">
      <input type="hidden" name="delete_appointment_id" id="deleteAppointmentId">
      <div class="flex justify-center gap-4">
        <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300 transition">Cancel</button>
        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition">Delete</button>
      </div>
    </form>
  </div>
</div>

<script>
  function openModal(appointmentId) {
    document.getElementById('deleteAppointmentId').value = appointmentId;
    document.getElementById('deleteModal').classList.remove('hidden');
    document.getElementById('deleteModal').classList.add('flex');
  }

  function closeModal() {
    document.getElementById('deleteModal').classList.add('hidden');
    document.getElementById('deleteModal').classList.remove('flex');
  }

  document.addEventListener('keydown', function(e) {
    if (e.key === "Escape") closeModal();
  });

  // Highlight sidebar active link
  const currentPage = window.location.pathname.split('/').pop();
  document.querySelectorAll("aside nav a").forEach(link => {
    link.classList.remove("bg-white/20", "font-semibold");
    if (link.getAttribute("href") === currentPage) {
      link.classList.add("bg-white/20", "font-semibold");
    }
  });
</script>
</body>
</html>

<?php $conn->close(); ?>