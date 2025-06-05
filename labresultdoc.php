<?php
session_start(); // Start session to use $_SESSION for messages

include 'connect.php'; // Include the database connection

// Function to safely close prepared statements and results
function closeResources($stmt, $result = null) {
    if ($result) {
        $result->free();
    }
    if ($stmt) {
        $stmt->close();
    }
}

// --- Fetch Patients and Doctors for Dropdowns ---
$patients = [];
$doctors = [];

if (!isset($conn) || !$conn->ping()) {
    require 'connect.php'; // Ensure connect.php is included
}

// Fetch Patients
$sql_fetch_patients = "SELECT PatientID, FullName FROM patients ORDER BY FullName ASC";
if ($stmt_patients = $conn->prepare($sql_fetch_patients)) {
    $stmt_patients->execute();
    $result_patients = $stmt_patients->get_result();
    while ($row = $result_patients->fetch_assoc()) {
        $patients[] = $row;
    }
    closeResources($stmt_patients, $result_patients);
} else {
    error_log("DB_ERROR: labresultdoc.php - Failed to prepare patient fetch statement: " . $conn->error);
    $_SESSION['error_message'] = $_SESSION['error_message'] ?? "Failed to load patient list.";
}

// Fetch Doctors
$sql_fetch_doctors = "SELECT DoctorID, FullName FROM doctors ORDER BY FullName ASC";
if ($stmt_doctors = $conn->prepare($sql_fetch_doctors)) {
    $stmt_doctors->execute();
    $result_doctors = $stmt_doctors->get_result();
    while ($row = $result_doctors->fetch_assoc()) {
        $doctors[] = $row;
    }
    closeResources($stmt_doctors, $result_doctors);
} else {
    error_log("DB_ERROR: labresultdoc.php - Failed to prepare doctor fetch statement: " . $conn->error);
    $_SESSION['error_message'] = $_SESSION['error_message'] ?? "Failed to load doctor list.";
}

// --- Handle Form Submission for New Lab Result ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit_lab'])) {
    // Sanitize and get user input
    $patientID = intval($_POST['patientID'] ?? 0); // Cast to int for security
    $doctorID = intval($_POST['doctorID'] ?? 0);   // Cast to int for security
    $date_taken = trim($_POST['date'] ?? '');
    $test_type = trim($_POST['testType'] ?? '');
    $result_text = trim($_POST['result'] ?? '');

    $form_error = false;

    // Basic validation
    if (empty($patientID) || empty($doctorID) || empty($date_taken) || empty($test_type) || empty($result_text)) {
        $_SESSION['error_message'] = "Please fill in all required fields (Patient, Doctor, Date Taken, Test Type, Result).";
        $form_error = true;
    }

    if (!$form_error) {
        $recordID = null;
        $stmt = null;
        $result = null;

        // Lookup RecordID from medicalrecords (assuming one exists for patient/doctor/date, or pick latest)
        $sql_record = "SELECT RecordID FROM medicalrecords WHERE PatientID = ? AND DoctorID = ? ORDER BY RecordDate DESC LIMIT 1";
        if ($stmt = $conn->prepare($sql_record)) {
            $stmt->bind_param("ii", $patientID, $doctorID);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows === 1) {
                $record_data = $result->fetch_assoc();
                $recordID = $record_data['RecordID'];
            } else {
                $_SESSION['error_message'] = "No medical record found for the selected patient and doctor. A medical record must exist to link a lab result. If you wish to create one first, please go to the Medical Records page.";
                $form_error = true;
            }
            closeResources($stmt, $result);
        } else {
            error_log("DB_ERROR: labresultdoc.php - Record lookup prepare failed: " . $conn->error);
            $_SESSION['error_message'] = "Server error during medical record lookup.";
            $form_error = true;
        }

        // Insert Data into labresults table
        if (!$form_error && $recordID !== null) {
            $sql_insert = "INSERT INTO labresults (RecordID, TestType, Result, DateTaken) VALUES (?, ?, ?, ?)";
            if ($stmt = $conn->prepare($sql_insert)) {
                $stmt->bind_param("isss", $recordID, $test_type, $result_text, $date_taken);

                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Lab result added successfully!";
                } else {
                    error_log("DB_ERROR: labresultdoc.php - Lab result insert failed: " . $stmt->error);
                    $_SESSION['error_message'] = "Failed to add lab result to the database: " . $stmt->error;
                }
                closeResources($stmt);
            } else {
                error_log("DB_ERROR: labresultdoc.php - Lab result insert prepare failed: " . $conn->error);
                $_SESSION['error_message'] = "Server error preparing to add lab result.";
            }
        }
    }

    // Post-Redirect-Get (PRG) Pattern
    // Close connection for POST request after all operations
    if (isset($conn) && $conn->ping()) {
        $conn->close();
    }
    header("Location: labresultdoc.php");
    exit();
}

// --- Fetch Lab Results for Display (on GET requests or after POST redirect) ---
// Re-establish connection if it was closed by POST or if this is the initial GET.
if (!isset($conn) || !$conn->ping()) {
    require 'connect.php'; // Ensure connect.php is included
}

$labResults = []; // Initialize array to hold results
$stmt = null;
$result_fetch = null;

$sql_fetch = "
    SELECT
        lr.LabResultID,
        p.FullName AS PatientName,
        d.FullName AS DoctorName,
        lr.TestType,
        lr.Result,
        lr.DateTaken
    FROM
        labresults lr
    JOIN
        medicalrecords mr ON lr.RecordID = mr.RecordID
    JOIN
        patients p ON mr.PatientID = p.PatientID
    JOIN
        doctors d ON mr.DoctorID = d.DoctorID
    ORDER BY
        lr.DateTaken DESC
";

if ($stmt = $conn->prepare($sql_fetch)) {
    $stmt->execute();
    $result_fetch = $stmt->get_result();

    if ($result_fetch) {
        while ($row = $result_fetch->fetch_assoc()) {
            $labResults[] = $row;
        }
    } else {
        error_log("DB_ERROR: labresultdoc.php - Failed to get results for fetch: " . $stmt->error);
        $_SESSION['error_message'] = $_SESSION['error_message'] ?? "Failed to retrieve lab results data.";
    }
    closeResources($stmt, $result_fetch);
} else {
    error_log("DB_ERROR: labresultdoc.php - Failed to prepare fetch statement: " . $conn->error);
    $_SESSION['error_message'] = $_SESSION['error_message'] ?? "Failed to prepare query to load lab results from the database.";
}

$conn->close(); // Close connection after fetching for display
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Lab Results - DermaHealth</title>
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
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <style>
        /* Ensure html and body fill the viewport and hide main scrollbar */
        html, body {
            height: 100%;
            overflow: hidden; /* Hide the main browser scrollbar */
        }

        /* Custom styles to make Select2 look more like Tailwind forms */
        /* These styles target the elements generated by Select2 */
        .select2-container .select2-selection--single {
            height: 42px !important; /* Match your input height */
            border-color: #D1D5DB !important; /* border-gray-300 */
            border-radius: 0.375rem !important; /* rounded-md */
            outline: none !important; /* Remove default focus outline */
            box-shadow: none !important; /* Remove default box shadow */
            display: flex;
            align-items: center;
            padding-left: 1rem; /* px-4 */
            padding-right: 1rem; /* px-4 */
        }

        .select2-container .select2-selection--single .select2-selection__rendered {
            color: #374151 !important; /* Default text color */
            padding-left: 0 !important; /* Adjust default padding */
            padding-right: 0 !important;
            line-height: inherit !important; /* Inherit line-height for proper vertical alignment */
        }

        .select2-container .select2-selection--single .select2-selection__arrow {
            height: 40px !important; /* Match height */
            top: 0 !important; /* Align arrow to top */
            right: 10px !important; /* Adjust arrow position */
            display: flex;
            align-items: center;
        }

        .select2-container--open .select2-selection--single {
            border-color: #C2185B !important; /* focus:ring-primary */
            box-shadow: 0 0 0 2px rgba(194, 24, 91, 0.25) !important; /* focus:ring-2 and focus:ring-primary equivalent */
        }

        .select2-container .select2-dropdown {
            border-color: #D1D5DB !important;
            border-radius: 0.375rem !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); /* Optional: add subtle shadow */
            margin-top: 4px; /* Space between input and dropdown */
        }

        .select2-container .select2-search--dropdown .select2-search__field {
            border-color: #D1D5DB !important;
            border-radius: 0.375rem !important;
            padding: 0.5rem 1rem !important; /* px-4 py-2 */
            width: calc(100% - 2rem) !important; /* Adjust for padding */
        }
        
        .select2-container .select2-results__option {
            padding: 0.5rem 1rem !important; /* Match px-4 py-2 */
        }

        .select2-container .select2-results__option--highlighted.select2-results__option--selectable {
            background-color: #FBB5CD !important; /* soft color for highlight */
            color: #374151 !important; /* text color on highlight */
        }
        
        .select2-container .select2-results__option--selected {
            background-color: #F88FB4 !important; /* tertiary color for selected */
            color: #374151 !important;
        }
    </style>
</head>
<body class="bg-pink-100 font-inter">
    <div class="flex h-screen">
        <aside class="w-64 bg-primary text-white p-6 hidden md:flex flex-col justify-between h-screen">
            <div>
                <div class="text-4xl font-pacifico mb-10">DermaHealth</div>
                <nav class="space-y-4">
                    <a href="dashdoctor.php" class="flex items-center space-x-3 hover:bg-white/10 p-2 rounded">
                        <i class="ri-dashboard-line"></i><span>Dashboard</span>
                    </a>
                    <a href="patientdoc.php" class="flex items-center space-x-3 hover:bg-white/10 p-2 rounded">
                        <i class="ri-user-line"></i><span>Patients</span>
                    </a>
                    <a href="appointdoctor.php" class="flex items-center space-x-3 hover:bg-white/20 p-2 rounded">
                        <i class="ri-calendar-line"></i><span>Appointments</span>
                    </a>
                    <a href="recordsdoc.php" class="flex items-center space-x-3 hover:bg-white/10 p-2 rounded">
                        <i class="ri-file-list-3-line"></i><span>Medical Records</span>
                    </a>
                    <a href="labresultdoc.php" class="flex items-center space-x-3 bg-white/20 font-semibold p-2 rounded">
                        <i class="ri-flask-line"></i><span>Lab Results</span>
                    </a>
                </nav>
            </div>
            <div class="pt-6 border-t border-white/20">
                <a href="index.php" class="flex items-center space-x-3 hover:bg-white/10 p-2 rounded">
                    <i class="ri-logout-box-r-line"></i><span>Logout</span>
                </a>
            </div>
        </aside>

        <main class="flex-1 p-6 bg-pink-50 overflow-y-auto">
            <header class="mb-6">
                <h1 class="text-3xl font-bold text-primary mb-4">Lab Results</h1>
            </header>

            <button
                id="toggleAddFormBtn"
                class="mb-4 bg-primary text-white px-4 py-2 rounded-md hover:bg-accent transition flex items-center space-x-2"
            >
                <i class="ri-add-line"></i><span>Add Lab Result</span>
            </button>

            <?php if (isset($_SESSION['success_message']) && !empty($_SESSION['success_message'])): ?>
                <div id="successMessage" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">Success!</strong>
                    <span class="block sm:inline"><?= htmlspecialchars($_SESSION['success_message']) ?></span>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            <?php if (isset($_SESSION['error_message']) && !empty($_SESSION['error_message'])): ?>
                <div id="errorMessage" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">Error!</strong>
                    <span class="block sm:inline"><?= htmlspecialchars($_SESSION['error_message']) ?></span>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <section id="add-form" class="mb-10 hidden">
                <form id="labForm" method="POST" action="labresultdoc.php" class="bg-white rounded-lg shadow p-6 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="patientID" class="block text-sm font-medium text-gray-700 mb-1">Patient Name</label>
                            <select name="patientID" id="patientID" class="w-full" required>
                                <option value="">Select Patient</option>
                                <?php foreach ($patients as $patient): ?>
                                    <option value="<?= htmlspecialchars($patient['PatientID']) ?>"><?= htmlspecialchars($patient['FullName']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Date Taken</label>
                            <input name="date" id="date" type="date" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary" required>
                        </div>
                        <div>
                            <label for="testType" class="block text-sm font-medium text-gray-700 mb-1">Test Type</label>
                            <input name="testType" id="testType" type="text" placeholder="e.g. Skin Biopsy" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary" required>
                        </div>
                        <div>
                            <label for="result" class="block text-sm font-medium text-gray-700 mb-1">Result</label>
                            <input name="result" id="result" type="text" placeholder="e.g. Benign Mole" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary" required>
                        </div>
                        <div class="md:col-span-2">
                            <label for="doctorID" class="block text-sm font-medium text-gray-700 mb-1">Doctor</label>
                            <select name="doctorID" id="doctorID" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary" required>
                                <option value="">Select Doctor</option>
                                <?php foreach ($doctors as $doctor): ?>
                                    <option value="<?= htmlspecialchars($doctor['DoctorID']) ?>"><?= htmlspecialchars($doctor['FullName']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="submit_lab" class="bg-primary text-white px-6 py-2 rounded-md hover:bg-accent transition">Submit Lab Result</button>
                </form>
            </section>

            <div id="resultsTable" class="bg-white rounded-lg shadow overflow-x-auto">
                <table class="min-w-full table-auto text-left">
                    <thead class="bg-soft">
                        <tr>
                            <th class="p-3">Result ID</th>
                            <th class="p-3">Patient</th>
                            <th class="p-3">Date</th>
                            <th class="p-3">Test Type</th>
                            <th class="p-3">Result</th>
                            <th class="p-3">Doctor</th>
                        </tr>
                    </thead>
                    <tbody id="resultsTableBody">
                        <?php if (!empty($labResults)): ?>
                                <?php foreach ($labResults as $row): ?>
                                <tr class="border-t">
                                    <td class="px-6 py-4"><?= htmlspecialchars($row['LabResultID']) ?></td>
                                    <td class="px-6 py-4"><?= htmlspecialchars($row['PatientName']) ?></td>
                                    <td class="px-6 py-4"><?= htmlspecialchars(date('Y-m-d', strtotime($row['DateTaken']))) ?></td>
                                    <td class="px-6 py-4"><?= htmlspecialchars($row['TestType']) ?></td>
                                    <td class="px-6 py-4"><?= htmlspecialchars($row['Result']) ?></td>
                                    <td class="px-6 py-4"><?= htmlspecialchars($row['DoctorName']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                        <?php else: ?>
                                <tr class="border-t">
                                    <td class="px-6 py-4 text-center text-gray-500" colspan="6">No lab results found.</td>
                                </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        // Toggle Add Form
        document.getElementById("toggleAddFormBtn").addEventListener("click", function() {
            const addForm = document.getElementById("add-form");
            addForm.classList.toggle("hidden");
            // Optional: scroll to form if it becomes visible
            if (!addForm.classList.contains("hidden")) {
                addForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
                // Initialize Select2 on patientID dropdown when the form becomes visible
                $('#patientID').select2({
                    placeholder: "Select or search for a patient...", // Tweak placeholder text
                    allowClear: true // Allows clearing the selection
                });
            } else {
                // If the form is being hidden, destroy Select2 instance to clean up
                // This prevents issues if the form is repeatedly shown/hidden
                if ($('#patientID').data('select2')) {
                    $('#patientID').select2('destroy');
                }
            }
        });

        // Hide messages after a few seconds
        setTimeout(() => {
            const successMsg = document.getElementById('successMessage');
            const errorMsg = document.getElementById('errorMessage');
            if (successMsg) successMsg.classList.add('hidden');
            if (errorMsg) errorMsg.classList.add('hidden');
        }, 5000); // Hide after 5 seconds

        // Sidebar active link highlighting
        const currentPage = window.location.pathname.split('/').pop();
        document.querySelectorAll("aside nav a").forEach(link => {
            // Remove active classes from all links first
            link.classList.remove("bg-white/20", "font-semibold");
            // Add active classes to the current page's link
            if (link.getAttribute("href") === currentPage) {
                link.classList.add("bg-white/20", "font-semibold");
            }
        });

        // Initialize Select2 if the form is already visible on page load (e.g., after a POST redirect with errors)
        $(document).ready(function() {
            if (!document.getElementById("add-form").classList.contains("hidden")) {
                $('#patientID').select2({
                    placeholder: "Select or search for a patient...",
                    allowClear: true
                });
            }
        });
    </script>
</body>
</html>