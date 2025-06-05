<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include 'connect.php';

// Ensure $conn is a valid mysqli object
if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Database connection not found. Please check connect.php.");
}

// ─────────────────────────────────────────────────────────────
// USE FAKE SESSION PATIENT_ID FOR TESTING
if (!isset($_SESSION['patient_id'])) {
    $_SESSION['patient_id'] = 1; // FOR TESTING ONLY — replace with real login in production
}
$patient_id = $_SESSION['patient_id'];

// ─────────────────────────────────────────────────────────────
// FETCH PATIENT FULL NAME FROM `patients` TABLE
$fullName = "Unknown Patient";

$stmt = $conn->prepare("SELECT FullName FROM patients WHERE PatientID = ?");
if ($stmt) {
    $stmt->bind_param('i', $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $fullName = htmlspecialchars($row['FullName']);
    }
    $stmt->close();
} else {
    error_log("Statement error: " . $conn->error);
}

// ─────────────────────────────────────────────────────────────
// FETCH LIST OF DOCTORS FOR DROPDOWN (for new appointment form)
$doctors = [];
$result = $conn->query("SELECT DoctorID, FullName FROM doctors ORDER BY FullName ASC"); // Order doctors
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $doctors[] = $row;
    }
}

// ─────────────────────────────────────────────────────────────
// Initialize message variables for PRG pattern
$showSuccessMessage = false;
$showErrorMessage = false;
$messageText = "";

// ─────────────────────────────────────────────────────────────
// HANDLE APPOINTMENT FORM SUBMISSION (NEW APPOINTMENT)
if (isset($_POST['book_appointment'])) {
    $date        = trim($_POST['appointment_date'] ?? '');
    $time        = trim($_POST['appointment_time'] ?? '');
    $reason      = trim($_POST['reason'] ?? '');
    $doctor_id   = intval($_POST['doctor_id'] ?? 0);

    if (empty($date) || empty($time) || empty($reason) || $doctor_id <= 0) {
        $showErrorMessage = true;
        $messageText = 'All fields including doctor selection are required.';
    } else {
        $appointmentDateTime = $date . ' ' . $time . ':00';
        $status      = 'Pending'; // Default status for new bookings

        $stmt2 = $conn->prepare("
            INSERT INTO appointments
                (PatientID, DoctorID, AppointmentDate, Status, Notes, CreatedAt)
            VALUES
                (?, ?, ?, ?, ?, NOW())
        ");

        if ($stmt2) {
            $stmt2->bind_param('iisss', $patient_id, $doctor_id, $appointmentDateTime, $status, $reason);
            if ($stmt2->execute()) {
                $showSuccessMessage = true;
                $messageText = "Appointment added successfully!";
            } else {
                $showErrorMessage = true;
                $messageText = "Booking error: " . $stmt2->error;
                error_log("Booking error: " . $stmt2->error);
            }
            $stmt2->close();
        } else {
            $showErrorMessage = true;
            $messageText = "Prepare failed: " . $conn->error;
            error_log("Prepare failed: " . $conn->error);
        }
    }
    // Redirect to self to prevent form resubmission on refresh (PRG pattern)
    header("Location: appointpatient.php?success=" . ($showSuccessMessage ? '1' : '0') . "&msg=" . urlencode($messageText));
    exit();
}

// ─────────────────────────────────────────────────────────────
// HANDLE APPOINTMENT STATUS UPDATE (CONFIRM/CANCEL/COMPLETE)
if (isset($_POST['update_appointment_status'])) {
    $appointment_id = intval($_POST['appointment_id'] ?? 0);
    $new_status     = trim($_POST['new_status'] ?? '');

    // Define valid statuses that a patient can set
    $valid_patient_statuses = ['Confirmed', 'Cancelled'];

    // Validate input
    if ($appointment_id <= 0 || !in_array($new_status, $valid_patient_statuses)) {
        $showErrorMessage = true;
        $messageText = "Invalid appointment ID or status provided for patient action.";
    } else {
        // Prepare update statement
        // Check if the appointment belongs to the current patient AND its current status allows for update
        $stmt_update = $conn->prepare("
            UPDATE appointments
            SET Status = ?
            WHERE AppointmentID = ? 
              AND PatientID = ? 
              AND Status = 'Pending' -- Only allow update if current status is Pending
        ");

        if ($stmt_update) {
            $stmt_update->bind_param('sii', $new_status, $appointment_id, $patient_id);
            if ($stmt_update->execute()) {
                if ($stmt_update->affected_rows > 0) {
                    $showSuccessMessage = true;
                    $messageText = "Appointment status updated to " . htmlspecialchars($new_status) . "!";
                } else {
                    // This could mean the appointment was not found for this patient,
                    // or the status was already changed by someone else/already confirmed/cancelled.
                    $showErrorMessage = true;
                    $messageText = "Failed to update status. Appointment not found, or its status has already changed from Pending.";
                }
            } else {
                $showErrorMessage = true;
                $messageText = "Error updating status: " . $stmt_update->error;
                error_log("Error updating appointment status: " . $stmt_update->error);
            }
            $stmt_update->close();
        } else {
            $showErrorMessage = true;
            $messageText = "Prepare statement failed for status update: " . $conn->error;
            error_log("Prepare statement failed for status update: " . $conn->error);
        }
    }
    // Redirect to self to prevent form resubmission on refresh (PRG pattern)
    header("Location: appointpatient.php?success=" . ($showSuccessMessage ? '1' : '0') . "&msg=" . urlencode($messageText));
    exit();
}

// Check for messages from PRG redirect (after page reload)
if (isset($_GET['success'])) {
    if ($_GET['success'] === '1') {
        $showSuccessMessage = true;
        $messageText = urldecode($_GET['msg'] ?? "Operation successful!");
    } else {
        $showErrorMessage = true;
        $messageText = urldecode($_GET['msg'] ?? "Operation failed!");
    }
}


// ─────────────────────────────────────────────────────────────
// FETCH PATIENT'S APPOINTMENTS (FOR DISPLAY)
$patientAppointments = [];
$stmt_appointments = $conn->prepare("
    SELECT
        a.AppointmentID,
        a.AppointmentDate,
        a.Status,
        a.Notes,
        d.FullName AS DoctorName
    FROM
        appointments a
    JOIN
        doctors d ON a.DoctorID = d.DoctorID
    WHERE
        a.PatientID = ?
    ORDER BY
        a.AppointmentDate DESC
");

if ($stmt_appointments) {
    $stmt_appointments->bind_param('i', $patient_id);
    $stmt_appointments->execute();
    $result_appointments = $stmt_appointments->get_result();

    if ($result_appointments) {
        while ($row = $result_appointments->fetch_assoc()) {
            $patientAppointments[] = $row;
        }
    } else {
        error_log("Error fetching appointments: " . $stmt_appointments->error);
    }
    $stmt_appointments->close();
} else {
    error_log("Prepare statement failed for fetching appointments: " . $conn->error);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
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
                        soft: '#FBB5CD',
                        cardlight: '#FDDDEB',
                        darkText: '#4A4A4A'
                    },
                    fontFamily: {
                        pacifico: ['Pacifico', 'cursive'],
                        inter: ['Inter', 'sans-serif']
                    },
                    borderRadius: {
                        'xl': '1rem',
                        '2xl': '1rem',
                        'button': '8px'
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
        }
        main {
            overflow-y: auto; /* Allow main content to scroll */
            flex-grow: 1; /* Ensures main content takes available space */
        }
        /* Custom styles for status badges */
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 9999px; /* Tailwind's full rounded */
            font-size: 0.75rem; /* text-xs */
            line-height: 1rem; /* leading-5 */
            font-weight: 600; /* font-semibold */
            display: inline-flex;
            align-items: center;
        }
        /* Specific colors for statuses */
        .status-pending { @apply bg-yellow-100 text-yellow-800; }
        .status-confirmed { @apply bg-green-100 text-green-800; }
        .status-cancelled { @apply bg-red-100 text-red-800; }
        .status-completed { @apply bg-blue-100 text-blue-800; } /* Added blue for completed */
        .status-default { @apply bg-gray-100 text-gray-800; } /* Fallback */
    </style>
</head>
<body class="bg-pink-100 font-inter min-h-screen flex">
    <aside class="w-64 bg-primary text-white flex flex-col justify-between">
        <div>
            <div class="flex flex-col items-center py-8">
                <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center">
                    <i class="ri-user-line text-3xl text-primary"></i>
                </div>
                <p id="user-name" class="mt-4 text-lg font-semibold"><?= $fullName ?></p>
            </div>

            <nav class="flex flex-col gap-2 px-6">
                <a href="dashpatient.php" class="flex items-center gap-3 py-3 px-4 hover:bg-white/10 rounded-xl">
                    <i class="ri-dashboard-line text-lg"></i> Dashboard
                </a>
                <a href="appointpatient.php" class="flex items-center gap-3 py-3 px-4 bg-white/20 font-semibold rounded-xl">
                    <i class="ri-calendar-line text-lg"></i> Appointments
                </a>
                <a href="recordspatient.php" class="flex items-center gap-3 py-3 px-4 hover:bg-white/10 rounded-xl">
                    <i class="ri-file-list-3-line text-lg"></i> Medical Records
                </a>
            </nav>
        </div>

        <div class="p-4 border-t border-white/20">
            <form action="patientlogin.php" method="POST">
                <button type="submit" class="flex items-center w-full p-3 rounded-button hover:bg-white/10 transition-colors">
                    <i class="ri-logout-box-line w-6 h-6 flex items-center justify-center mr-3"></i>
                    <span>Logout</span>
                </button>
            </form>
        </div>
    </aside>

    <main class="flex-1 p-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-primary">My Appointments</h1>
        </div>

        <?php if ($showSuccessMessage): ?>
            <div id="successMessage" class="mb-6 px-4 py-3 bg-green-100 text-green-800 rounded font-semibold shadow">
                <?= htmlspecialchars($messageText) ?>
            </div>
        <?php endif; ?>

        <?php if ($showErrorMessage): ?>
            <div id="errorMessage" class="mb-6 px-4 py-3 bg-red-100 text-red-800 rounded font-semibold shadow">
                <?= htmlspecialchars($messageText) ?>
            </div>
        <?php endif; ?>

        <section class="mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Schedule Your Visit</h2>
            <form action="" method="POST" class="bg-white shadow rounded-xl p-6 space-y-4">
                <div>
                    <label for="appointment_date" class="block text-gray-700 font-medium mb-1">Select Date:</label>
                    <input type="date" id="appointment_date" name="appointment_date" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label for="appointment_time" class="block text-gray-700 font-medium mb-1">Preferred Time:</label>
                    <input type="time" id="appointment_time" name="appointment_time" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label for="doctor_id" class="block text-gray-700 font-medium mb-1">Select Doctor:</label>
                    <select id="doctor_id" name="doctor_id" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="">-- Choose a Doctor --</option>
                        <?php foreach ($doctors as $doctor): ?>
                            <option value="<?= $doctor['DoctorID'] ?>">
                                <?= htmlspecialchars($doctor['FullName']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="reason" class="block text-gray-700 font-medium mb-1">Reason for Visit:</label>
                    <textarea id="reason" name="reason" rows="4" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
                        placeholder="e.g., General check-up, specific symptoms..."></textarea>
                </div>
                <button type="submit" name="book_appointment"
                        class="bg-primary text-white px-6 py-2 rounded-xl hover:bg-accent transition-colors duration-200">
                    Book Appointment
                </button>
            </form>
        </section>

        <section>
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Your Scheduled Appointments</h2>
            <?php if (empty($patientAppointments)): ?>
                <div class="bg-white shadow rounded-xl p-6 text-center text-gray-600">
                    You have no scheduled appointments.
                </div>
            <?php else: ?>
                <div class="overflow-x-auto bg-white shadow rounded-xl">
                    <table class="min-w-full table-auto">
                        <thead class="bg-soft">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Date & Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Doctor</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Reason</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($patientAppointments as $appointment): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= htmlspecialchars(date('M d, Y h:i A', strtotime($appointment['AppointmentDate']))) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= htmlspecialchars($appointment['DoctorName']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= htmlspecialchars($appointment['Notes']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold">
                                        <span class="status-badge
                                            <?php
                                            // Apply specific color classes based on status
                                            if ($appointment['Status'] == 'Pending') echo 'status-pending';
                                            elseif ($appointment['Status'] == 'Confirmed') echo 'status-confirmed';
                                            elseif ($appointment['Status'] == 'Cancelled') echo 'status-cancelled';
                                            elseif ($appointment['Status'] == 'Completed') echo 'status-completed'; // Added Completed status styling
                                            else echo 'status-default';
                                            ?>">
                                            <?= htmlspecialchars($appointment['Status']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-right">
                                        <?php
                                        $currentDateTime = new DateTime();
                                        $appointmentDateTime = new DateTime($appointment['AppointmentDate']);
                                        $isPastAppointment = ($appointmentDateTime < $currentDateTime);

                                        // Only allow action if status is 'Pending' AND appointment date is in the future
                                        if ($appointment['Status'] == 'Pending' && !$isPastAppointment):
                                        ?>
                                            <form action="" method="POST" class="flex items-center justify-end space-x-2">
                                                <input type="hidden" name="appointment_id" value="<?= $appointment['AppointmentID'] ?>">
                                                <select name="new_status" class="block px-3 py-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary text-gray-700 text-sm">
                                                    <option value="">Choose Action</option>
                                                    <option value="Confirmed">Confirm</option>
                                                    <option value="Cancelled">Cancel</option>
                                                    </select>
                                                <button type="submit" name="update_appointment_status"
                                                            class="bg-primary text-white px-3 py-1 rounded-md text-sm hover:bg-accent transition-colors duration-200">
                                                    Update
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-gray-500 text-xs">
                                                <?php
                                                if ($appointment['Status'] == 'Completed') {
                                                    echo "Already completed"; // Specific message for completed
                                                } elseif ($isPastAppointment && $appointment['Status'] != 'Completed') {
                                                    echo "Past appointment";
                                                } else {
                                                    echo "Action not available";
                                                }
                                                ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Set min date for appointment_date input to today
            const today = new Date();
            const yyyy = today.getFullYear();
            const mm = String(today.getMonth() + 1).padStart(2, '0'); // Months start at 0!
            const dd = String(today.getDate()).padStart(2, '0');
            const minDate = `<span class="math-inline">\{yyyy\}\-</span>{mm}-${dd}`;
            document.getElementById('appointment_date').setAttribute('min', minDate);

            // Hide messages after a few seconds
            setTimeout(() => {
                const successMsg = document.getElementById('successMessage');
                const errorMsg = document.getElementById('errorMessage');
                if (successMsg) successMsg.classList.add('hidden');
                if (errorMsg) errorMsg.classList.add('hidden');
            }, 5000); // Hide after 5 seconds
        });

        // Sidebar active link highlighting
        const currentPage = window.location.  pathname.split('/').pop();
        document.querySelectorAll("aside nav a").forEach(link => {
            link.classList.remove("bg-white/20", "font-semibold"); // Remove active classes from all links first
            if (link.getAttribute("href") === currentPage) {
                link.classList.add("bg-white/20", "font-semibold"); // Add active classes to the current page's link
            }
        });
    </script>
</body>
</html>

<?php
if (isset($conn)) {
    $conn->close();
}
?>