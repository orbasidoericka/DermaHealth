<?php
session_start(); // Start session to access $_SESSION variables for messages

// Include the database connection
require 'connect.php'; // Your database connection details are in connect.php

// ─────────────────────────────────────────────────────────────────────────────
// 1) Handle Form Submission for New Medical Record
// ─────────────────────────────────────────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Sanitize and get user input
    $patient_name = trim($_POST['patient'] ?? '');
    $record_date = trim($_POST['date'] ?? '');
    $diagnosis = trim($_POST['diagnosis'] ?? '');
    $doctor_name = trim($_POST['doctor'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $symptoms = trim($_POST['symptoms'] ?? '');
    $prescription = trim($_POST['prescription'] ?? '');

    $form_error = false; // Flag to track form input errors

    // Basic validation
    if (empty($patient_name) || empty($record_date) || empty($diagnosis) || empty($doctor_name) || empty($symptoms) || empty($prescription) || empty($notes)) {
        $_SESSION['error_message'] = "Please fill in all required fields.";
        $form_error = true;
    }

    if (!$form_error) { // Only proceed with DB operations if no basic form errors
        $patientID = null;
        $doctorID = null;

        // Lookup PatientID from Patient Name
        $stmt_patient = $conn->prepare("SELECT PatientID FROM patients WHERE FullName = ?");
        if ($stmt_patient) {
            $stmt_patient->bind_param("s", $patient_name);
            $stmt_patient->execute();
            $result_patient = $stmt_patient->get_result();
            if ($result_patient->num_rows === 1) {
                $patient_data = $result_patient->fetch_assoc();
                $patientID = $patient_data['PatientID'];
            } else {
                $_SESSION['error_message'] = "Patient '" . htmlspecialchars($patient_name) . "' not found. Please ensure the name is correct.";
                $form_error = true;
            }
            $stmt_patient->close();
        } else {
            error_log("DB_ERROR: recordsdoc.php - Patient lookup prepare failed: " . $conn->error);
            $_SESSION['error_message'] = "Server error during patient lookup.";
            $form_error = true;
        }

        // Lookup DoctorID from Doctor Name (only if previous steps were successful)
        if (!$form_error) {
            $stmt_doctor = $conn->prepare("SELECT DoctorID FROM doctors WHERE FullName = ?");
            if ($stmt_doctor) {
                $stmt_doctor->bind_param("s", $doctor_name);
                $stmt_doctor->execute();
                $result_doctor = $stmt_doctor->get_result();
                if ($result_doctor->num_rows === 1) {
                    $doctor_data = $result_doctor->fetch_assoc();
                    $doctorID = $doctor_data['DoctorID'];
                } else {
                    $_SESSION['error_message'] = "Doctor '" . htmlspecialchars($doctor_name) . "' not found. Please ensure the name is correct.";
                    $form_error = true;
                }
                $stmt_doctor->close();
            } else {
                error_log("DB_ERROR: recordsdoc.php - Doctor lookup prepare failed: " . $conn->error);
                $_SESSION['error_message'] = "Server error during doctor lookup.";
                $form_error = true;
            }
        }

        // Insert Data into medicalrecords table (only if all lookups were successful)
        if (!$form_error && $patientID !== null && $doctorID !== null) {
            $appointmentID = NULL; // Not collected by the form, assuming NULL is allowed in DB

            $stmt_insert = $conn->prepare("INSERT INTO medicalrecords (PatientID, DoctorID, AppointmentID, Symptoms, Diagnosis, Prescription, Notes, RecordDate) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

            if ($stmt_insert) {
                $stmt_insert->bind_param("iissssss", $patientID, $doctorID, $appointmentID, $symptoms, $diagnosis, $prescription, $notes, $record_date);

                if ($stmt_insert->execute()) {
                    $_SESSION['success_message'] = "Medical record added successfully!";
                } else {
                    error_log("DB_ERROR: recordsdoc.php - Medical record insert failed: " . $stmt_insert->error);
                    $_SESSION['error_message'] = "Failed to add medical record. " . $stmt_insert->error; // Keep for debugging, remove in production
                }
                $stmt_insert->close();
            } else {
                error_log("DB_ERROR: recordsdoc.php - Medical record insert prepare failed: " . $conn->error);
                $_SESSION['error_message'] = "Server error preparing to add record.";
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Post-Redirect-Get (PRG) Pattern: Redirect after POST
    // ─────────────────────────────────────────────────────────────────────────────
    $conn->close(); // Close connection before redirect
    header("Location: recordsdoc.php");
    exit(); // IMPORTANT: Always exit after a header redirect
}

// ─────────────────────────────────────────────────────────────────────────────
// 2) Fetch Records for Display (Executed on GET requests)
// ─────────────────────────────────────────────────────────────────────────────
// Re-establish connection for GET request if it was closed by POST, or if this is the initial GET.
// This is necessary because connect.php is included, but if the POST block runs and closes,
// the GET block needs a fresh connection.
// A more robust solution involves passing $conn by reference or restructuring to keep it open.
// For now, I'll add a check to ensure $conn is available.
if (!isset($conn) || !$conn) {
    require 'connect.php';
}

// To display PatientName and DoctorName, we need to JOIN with 'patients' and 'doctors' tables
$sql = "
    SELECT
        mr.RecordID,
        p.FullName AS PatientName,
        d.FullName AS DoctorName,
        mr.Symptoms,
        mr.Diagnosis,
        mr.Prescription,
        mr.Notes,
        mr.RecordDate
    FROM
        medicalrecords mr
    JOIN
        patients p ON mr.PatientID = p.PatientID
    JOIN
        doctors d ON mr.DoctorID = d.DoctorID
    ORDER BY
        mr.RecordDate DESC
";
$result = $conn->query($sql);
if (!$result) {
    error_log("DB_ERROR: recordsdoc.php - Failed to fetch medical records: " . $conn->error);
    // You might want to display an error message to the user here as well
    $_SESSION['error_message'] = "Failed to load medical records."; // Store error in session for display
}

// Close connection after fetching records (for GET request path)
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Medical Records - DermaHealth</title>
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
            softbg: '#FBB5CD', // This custom color is available if needed
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
<div class="flex h-screen"> <aside class="w-64 bg-primary text-white p-6 hidden md:flex flex-col justify-between"> <div>
      <div class="text-3xl font-pacifico mb-10">DermaHealth</div> <nav class="space-y-4">
        <a href="dashdoctor.php" class="flex items-center space-x-3 hover:bg-white/10 p-2 rounded text-base"><i class="ri-dashboard-line"></i><span>Dashboard</span></a> <a href="patientdoc.php" class="flex items-center space-x-3 hover:bg-white/10 p-2 rounded text-base"><i class="ri-user-line"></i><span>Patients</span></a> <a href="appointdoctor.php" class="flex items-center space-x-3 hover:bg-white/10 p-2 rounded text-base"><i class="ri-calendar-line"></i><span>Appointments</span></a> <a href="recordsdoc.php" class="flex items-center space-x-3 bg-white/20 font-semibold p-2 rounded text-base"><i class="ri-file-list-3-line"></i><span>Medical Records</span></a> <a href="labresultdoc.php" class="flex items-center space-x-3 hover:bg-white/10 p-2 rounded text-base"><i class="ri-flask-line"></i><span>Lab Results</span></a> </nav>
    </div>
    <div class="pt-6 border-t border-white/20">
      <a href="index.php" class="flex items-center space-x-3 hover:bg-white/10 p-2 rounded text-base"><i class="ri-logout-box-r-line"></i><span>Logout</span></a> </div>
  </aside>

  <div class="flex-1 flex flex-col h-full"> <header class="bg-pink-50 p-6 flex-shrink-0"> <h1 class="text-4xl font-bold text-primary mb-4">Medical Records</h1> <button id="toggleFormBtn" class="mb-4 bg-primary text-white px-4 py-2 rounded-md hover:bg-accent transition flex items-center space-x-2">
        <i class="ri-add-line"></i><span>Add Medical Record</span>
      </button>
    </header>

    <main class="p-8 pb-24 flex-grow overflow-y-auto"> <?php
      if (isset($_SESSION['success_message']) && !empty($_SESSION['success_message'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
          <strong class="font-bold">Success!</strong>
          <span class="block sm:inline"><?= htmlspecialchars($_SESSION['success_message']) ?></span>
        </div>
        <?php unset($_SESSION['success_message']); // Clear message after display ?>
      <?php endif; ?>
      <?php
      if (isset($_SESSION['error_message']) && !empty($_SESSION['error_message'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
          <strong class="font-bold">Error!</strong>
          <span class="block sm:inline"><?= htmlspecialchars($_SESSION['error_message']) ?></span>
        </div>
        <?php unset($_SESSION['error_message']); // Clear message after display ?>
      <?php endif; ?>

      <section id="addForm" class="hidden bg-white rounded-2xl shadow-md p-6 mb-6">
        <form method="POST">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label for="patient_name" class="block text-gray-700 mb-1">Patient Name</label>
              <input type="text" id="patient_name" name="patient" required class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-primary" />
            </div>
            <div>
              <label for="record_date" class="block text-gray-700 mb-1">Date</label>
              <input type="date" id="record_date" name="date" required class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-primary" />
            </div>
            <div>
              <label for="diagnosis" class="block text-gray-700 mb-1">Diagnosis</label>
              <input type="text" id="diagnosis" name="diagnosis" required class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-primary" />
            </div>
            <div>
              <label for="doctor_name" class="block text-gray-700 mb-1">Doctor</label>
              <input type="text" id="doctor_name" name="doctor" required class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-primary" />
            </div>
            <div class="md:col-span-2">
              <label for="symptoms" class="block text-gray-700 mb-1">Symptoms</label>
              <textarea id="symptoms" name="symptoms" rows="2" required class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
            </div>
            <div class="md:col-span-2">
              <label for="prescription" class="block text-gray-700 mb-1">Prescription</label>
              <textarea id="prescription" name="prescription" rows="2" required class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
            </div>
            <div class="md:col-span-2">
              <label for="notes" class="block text-gray-700 mb-1">Notes</label>
              <textarea id="notes" name="notes" rows="3" class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
            </div>
          </div>
          <div class="mt-4">
            <button type="submit" class="bg-primary text-white px-6 py-2 rounded-button font-semibold hover:bg-secondary transition">Submit Medical Record</button>
          </div>
        </form>
      </section>

      <div class="grid md:grid-cols-2 gap-6">
        <?php if ($result && $result->num_rows > 0): ?>
          <?php while($row = $result->fetch_assoc()): ?>
            <div class="bg-tertiary/40 rounded-2xl shadow-md p-4">
              <h2 class="text-xl font-semibold text-primary mb-2">Patient Name: <?= htmlspecialchars($row['PatientName']) ?></h2>
              <p><span class="font-semibold">Date:</span> <?= htmlspecialchars(date('Y-m-d', strtotime($row['RecordDate']))) ?></p>
              <p><span class="font-semibold">Diagnosis:</span> <?= htmlspecialchars($row['Diagnosis']) ?></p>
              <p><span class="font-semibold">Doctor:</span> <?= htmlspecialchars($row['DoctorName']) ?></p>
              <p><span class="font-semibold">Symptoms:</span> <?= htmlspecialchars($row['Symptoms']) ?></p>
              <p><span class="font-semibold">Prescription:</span> <?= htmlspecialchars($row['Prescription']) ?></p>
              <p><span class="font-semibold">Notes:</span> <?= htmlspecialchars($row['Notes']) ?></p>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <p class="md:col-span-2 text-gray-600">No medical records found.</p>
        <?php endif; ?>
      </div>
    </main>
  </div>
</div>

<script>
  document.getElementById("toggleFormBtn").addEventListener("click", () => {
    document.getElementById("addForm").classList.toggle("hidden");
  });

  // Sidebar active link highlighting
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