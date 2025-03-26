<?php

date_default_timezone_set('Asia/Manila');

session_start();

if (!isset($_SESSION['admin_logged_in'])) {  // Matches the session variable name
    header("Location: login.php");
    exit;
}

// Database connection
$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "employeedatabase";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch employee names
$employee_options = '';
$employee_sql = "SELECT CONCAT(firstName, ' ', lastName) AS full_name, employee_id FROM employee_db";
if ($employee_stmt = $conn->prepare($employee_sql)) {
    $employee_stmt->execute();
    $result = $employee_stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $employee_options .= "<option data-value-id='".htmlspecialchars($row['employee_id'])."' value='".htmlspecialchars($row['full_name'])."'>".htmlspecialchars($row['full_name'])."</option>";
    }
    $employee_stmt->close();
} else {
    die("<script>alert('Error fetching employee names: " . addslashes($conn->error) . "');</script>");
}


if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deleteEmployee'])) {

    $employeeId = $_POST['employeeId'];

    $delete_stmt = $conn->prepare("DELETE FROM employee_db WHERE employee_id = ?");
    $delete_stmt->bind_param("i", $employeeId);

    if($delete_stmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Employee deleted successfully!'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Error deleting employee: ' . $delete_stmt->error
        ]);
    }
    $delete_stmt->close();
    exit;

}

// Handle new staff registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    header('Content-Type: application/json'); // Add this line to specify JSON response

    $firstName = strtoupper(trim($_POST['firstName']));
    $lastName = strtoupper(trim($_POST['lastName']));

    // Validate input
    if (empty($firstName) || empty($lastName)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'First name and last name are required.'
        ]);
        exit;
    }

    // Check if the combination of first name and last name already exists
    $check_stmt = $conn->prepare("SELECT COUNT(*) FROM employee_db WHERE firstName = ? AND lastName = ?");
    $check_stmt->bind_param("ss", $firstName, $lastName);
    $check_stmt->execute();
    $check_stmt->bind_result($count);
    $check_stmt->fetch();
    $check_stmt->close();

    if ($count > 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'This staff member is already registered.'
        ]);
        exit;
    }

    // Insert into database
    $stmt = $conn->prepare("INSERT INTO employee_db (firstName, lastName) VALUES (?, ?)");
    $stmt->bind_param("ss", $firstName, $lastName);

    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'New staff registered successfully!'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Error registering staff: ' . $stmt->error
        ]);
    }
    $stmt->close();
    exit;
}

// Date filtering logic
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$valid_start = DateTime::createFromFormat('Y-m-d', $start_date) && DateTime::createFromFormat('Y-m-d', $start_date)->format('Y-m-d') === $start_date;
$valid_end = DateTime::createFromFormat('Y-m-d', $end_date) && DateTime::createFromFormat('Y-m-d', $end_date)->format('Y-m-d') === $end_date;
$where_clause = '';

if ($valid_start && $valid_end) {
    $where_clause = " WHERE submission_date BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'";
}

// Export to Excel functionality
if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=health_records.csv');
    
    $output = fopen("php://output", "w");
    fputcsv($output, array('ID', 'Employee Name', 'Unit', 'Wellness Status', 'Symptoms', 'Symptoms Management', 
        'Household Symptoms', 'Household Symptoms Details', 'Environmental Check', 'Environmental Issues',
        'Mental Health Check', 'Mental Health Support','Heat Index Status', 'Current Status', 'Submission Date'));
    
    $export_query = "SELECT * FROM health_records" . $where_clause . " ORDER BY submission_date DESC";
    $result = $conn->query($export_query);
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

// Pagination logic
$limit = 5;
$page = isset($_GET["page"]) ? (int)$_GET["page"] : 1;
$page = max($page, 1);
$start_from = ($page - 1) * $limit;

$total_records_query = "SELECT COUNT(*) FROM health_records" . $where_clause;
$total_records_result = $conn->query($total_records_query);
$total_records = $total_records_result->fetch_array()[0];
$total_pages = ceil($total_records / $limit);

$prev_page = max($page - 1, 1);
$next_page = min($page + 1, $total_pages);

$sql = "SELECT * FROM health_records" . $where_clause . " ORDER BY submission_date DESC LIMIT $start_from, $limit";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Monitoring Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"rel="stylesheet">
    <link href='https://fonts.googleapis.com/css?family=Inter' rel='stylesheet'>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css"rel="stylesheet"  >
    <link href='https://fonts.googleapis.com/css?family=Cardo' rel='stylesheet'>
    <link href='https://fonts.googleapis.com/css?family=Roboto' rel='stylesheet'>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="admin.css">
   
</head>
<body>
    <!-- Date Picker Modal -->
    <div class="date-modal-backdrop"></div>
    <div class="date-modal">
        <div class="date-modal-header">
            <h4>Select Date Range</h4>
        </div>
        <input type="text" id="dateRange" style="width: 100%">
        <div class="date-modal-footer">
            <button type="button" class="btn btn-secondary" id="cancelDate">Cancel</button>
            <button type="button" class="btn btn-primary" id="applyDate">Apply</button>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="sidebar d-none d-md-block">
        <div class="sidebar-header mb-4">
            <h4 class="text-white">Health Monitoring</h4>
        </div>
        <nav class="nav flex-column">
            <a class="nav-link active" href="#" id="healthSubmissionsLink">
                <i class="bi bi-clipboard-pulse"></i>
                Health Submissions
            </a>
        
            <a class="nav-link" href="#" id="registerNewStaffLink">
                <i class="bi bi-people"></i>
                Register New Staff
            </a>

            <a class="nav-link" href="#" id="deleteStaffLink">
                <i class="bi bi-trash"></i>
                Delete Employee
            </a>

            <a class="nav-link" href="#" onclick="confirmLogout()">
                <i class="bi bi-box-arrow-right"></i>
                Log Out
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
       

        <!-- Health Submissions Table -->
        <div class="table-section" id="healthSubmissionsTable">
             <!-- Health Submissions Section -->
        <!-- In your health submissions header section -->
        <div class="header d-flex justify-content-between align-items-center rounded" id="healthSubmissionsHeader">
            <h3 class="mb-0">Health Check Submissions</h3>
            <div class="d-flex align-items-center gap-2">
                <?php if ($valid_start && $valid_end): ?>
                    <div class="bg-light px-3 py-1 rounded d-flex align-items-center gap-2">
                        <span><?= htmlspecialchars("$start_date to $end_date") ?></span>
                        <a href="admin.php" class="text-danger"><i class="bi bi-x-lg"></i></a>
                    </div>
                <?php endif; ?>
                <button class="btn btn-outline-secondary me-2" id="clearDateFilter" <?= (!$valid_start || !$valid_end) ? 'style="display:none"' : '' ?>>
                    <i class="bi bi-x-lg me-1"></i>Clear Dates
                </button>
                <button class="btn btn-primary me-2" id="dateFilterBtn">
                    <i class="bi bi-calendar-range me-2"></i>Filter by Date
                </button>
                <a href="?export=1<?= isset($_GET['start_date']) ? '&start_date=' . urlencode($_GET['start_date']) . '&end_date=' . urlencode($_GET['end_date']) : '' ?>" class="btn-export">
                    <i class="bi bi-file-earmark-excel"></i>
                    Export Excel
                </a>
            </div>
        </div>
                    <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Employee</th>
                            <th>Unit</th>
                            <th>Wellness</th>
                            <th>Symptoms</th>
                            <th>Management</th>
                            <th>Household</th>
                            <th>Environment</th>
                            <th>Mental Heat</th>
                            <th>Heat Index Check</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['id'] ?></td>
                                <td><?= $row['employee_name'] ?></td>
                                <td><?= $row['unit'] ?></td>
                                <td><?= $row['wellness_status'] == 'yes' ? '✅' : '❌' ?></td>
                                <td><?= $row['symptoms'] ?: 'N/A' ?></td>
                                <td><?= $row['symptoms_management'] ?: 'N/A' ?></td>
                                <td><?= $row['household_symptoms_details'] ?: 'N/A' ?></td>
                                <td><?= $row['environmental_issues'] ?: 'N/A' ?></td>
                                <td><?= $row['mental_health_support'] ?: 'N/A' ?></td>
                                <td><?= isset($row['heat_index_status']) ? ($row['heat_index_status'] == 'yes' ? '✅' : '❌') : 'N/A' ?></td>
                                <td><span class="badge bg-<?= 
                                    $row['current_status'] == 'Active' ? 'success' : 
                                    ($row['current_status'] == 'Pending' ? 'warning' : 'secondary') 
                                    ?>"><?= $row['current_status'] ?></span></td>
                                <td><?= date('M d, Y h:i A', strtotime($row['submission_date'])) ?></td>
                                <td>
                                    <form method="post" action="admin.php" onsubmit="return confirm('Are you sure you want to delete this record?');">
                                        <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
                                        <button type="submit" class="btn btn-link text-danger p-0">
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="12" class="text-center py-4">No records found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-end mt-4">
                    <li class="page-item <?= ($page == 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="admin.php?page=<?= $prev_page ?><?= $valid_start && $valid_end ? "&start_date=$start_date&end_date=$end_date" : '' ?>">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    if ($start_page > 1) {
                        echo '<li class="page-item"><a class="page-link" href="admin.php?page=1'.($valid_start && $valid_end ? "&start_date=$start_date&end_date=$end_date" : '').'">1</a></li>';
                        if ($start_page > 2) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                    }
                    
                    for ($i = $start_page; $i <= $end_page; $i++) {
                        $active = ($i == $page) ? 'active' : '';
                        echo "<li class='page-item $active'>
                                <a class='page-link' href='admin.php?page=$i".($valid_start && $valid_end ? "&start_date=$start_date&end_date=$end_date" : '')."'>$i</a>
                              </li>";
                    }
                    
                    if ($end_page < $total_pages) {
                        if ($end_page < $total_pages - 1) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                        echo '<li class="page-item"><a class="page-link" href="admin.php?page='.$total_pages.($valid_start && $valid_end ? "&start_date=$start_date&end_date=$end_date" : '').'">'.$total_pages.'</a></li>';
                    }
                    ?>
                    <li class="page-item <?= ($page == $total_pages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="admin.php?page=<?= $next_page ?><?= $valid_start && $valid_end ? "&start_date=$start_date&end_date=$end_date" : '' ?>">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>

        <!-- Registration Form Section -->
        <div class="registration-form" id="registrationForm">
            <h3>Register New Staff</h3>
            <form id="staffRegistrationForm" method="post">
            <div class="mb-3">
                <label for="firstName" class="form-label">First Name</label>
                <input type="text" class="form-control" id="firstName" name="firstName" required>
            </div>
            <div class="mb-3">
                <label for="lastName" class="form-label">Last Name</label>
                <input type="text" class="form-control" id="lastName" name="lastName" required>
            </div>
            <button type="submit" class="btn btn-primary" name="register">Register</button>
            
            </form>

        </div>
        <!-- Delete Staff Section -->
        <div class="form-group" id="deleteStaffSection" >
            <h3>Delete Staff</h3>
            <form id="deleteStaffForm" method="post">
                <label for="employeeName">Employee Name <span style="color: red;">*</span></label>
                <select class="form-control" id="employeeName" name="employeeName" required>
                    <option value="">Select your name</option>
                    <?php echo $employee_options; ?>
                </select>
                <button type="button" class="btn btn-danger mt-3" id="deleteEmployeeBtn">Delete Staff</button>
            </form>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script>
        $(document).ready(function() {
            // Apply Select2 to the dropdown
            $('#employeeName').select2({
                placeholder: "Select your name", 
                allowClear: true, 
                width: '100%' 
            });
        });
        </script>
        


    </div>
    <!-- Include jQuery and Select2 JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
    const deleteEmployeeBtn = document.getElementById('deleteEmployeeBtn');

    document.getElementById('deleteEmployeeBtn').addEventListener('click', function() {
    const selectedEmployee = document.getElementById('employeeName').value;
    let employeeId = $("#employeeName option:selected").data("value-id");
        

                    if (!selectedEmployee) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Validation Error',
                            text: 'Please select an employee to delete.',
                        });
                        return;
                    }

                    Swal.fire({
                        title: 'Are you sure you want to delete this employee?',
                        text: "This action cannot be undone!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, delete it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                method: "POST",
                                url: "admin.php",
                                data: { employeeName: selectedEmployee, deleteEmployee: true, employeeId : employeeId },
                                dataType: 'json',
                                success: function(response) {
                                    if (response.status === 'success') {
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Success',
                                            text: response.message,
                                        });
                                        document.getElementById('deleteStaffForm').reset(); // Reset the form
                                    } else {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Error',
                                            text: response.message,
                                        });
                                    }
                                },
                            })
                        }
                    });
                });
        



        document.addEventListener('DOMContentLoaded', function() {
            // Handle form submission
            document.getElementById('staffRegistrationForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const firstName = document.getElementById('firstName').value.trim();
            const lastName = document.getElementById('lastName').value.trim();

            // Validate input
            if (!firstName || !lastName) {
                Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: 'First name and last name are required.',
                });
                return;
            }

            // Confirm submission
            Swal.fire({
                title: 'Are you sure you want to submit?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, submit it!'
            }).then((result) => {
                if (result.isConfirmed) {
                // Submit form data via AJAX
                const formData = new FormData();
                formData.append('firstName', firstName);
                formData.append('lastName', lastName);
                formData.append('register', true);

                fetch('admin.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: data.message,
                    });
                    document.getElementById('staffRegistrationForm').reset(); // Reset the form
                    } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message,
                    });
                    }
                })
                .catch(error => {
                    Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while submitting the form.',
                    });
                });
                }
            });
            });
        });
        // Date Picker Functionality
document.addEventListener('DOMContentLoaded', function() {
    const backdrop = document.querySelector('.date-modal-backdrop');
    const modal = document.querySelector('.date-modal');
    const clearDateFilterBtn = document.getElementById('clearDateFilter');
    
    // Initialize Flatpickr with range mode
    const dateRange = flatpickr("#dateRange", {
        mode: "range",
        dateFormat: "Y-m-d",
        inline: true,
        allowInput: true,
        defaultDate: [
            <?= $valid_start ? "'$start_date'" : 'null' ?>,
            <?= $valid_end ? "'$end_date'" : 'null' ?>
        ]
    });

    // Show modal
                document.getElementById('dateFilterBtn').addEventListener('click', function(e) {
                    e.preventDefault();
                    backdrop.style.display = 'block';
                    modal.style.display = 'block';
                });

                // Cancel button
                document.getElementById('cancelDate').addEventListener('click', function() {
                    backdrop.style.display = 'none';
                    modal.style.display = 'none';
                });

                // Apply button
                document.getElementById('applyDate').addEventListener('click', function() {
                    const selectedDates = dateRange.selectedDates;
                    if (selectedDates.length === 2) {
                        const startDate = selectedDates[0].toISOString().split('T')[0];
                        const endDate = selectedDates[1].toISOString().split('T')[0];
                        window.location.href = `admin.php?start_date=${startDate}&end_date=${endDate}`;
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Invalid Selection',
                            text: 'Please select a start and end date for the range',
                        });
                    }
                });

                // Clear dates button
                clearDateFilterBtn.addEventListener('click', function() {
                    window.location.href = 'admin.php';
                });

                // Close modal when clicking backdrop
                backdrop.addEventListener('click', function(e) {
                    if (e.target === backdrop) {
                        backdrop.style.display = 'none';
                        modal.style.display = 'none';
                    }
                });

                // Toggle clear button visibility based on URL params
                function updateClearButton() {
                    const urlParams = new URLSearchParams(window.location.search);
                    const hasDates = urlParams.has('start_date') && urlParams.has('end_date');
                    clearDateFilterBtn.style.display = hasDates ? 'block' : 'none';
                }
                
                // Initial update
                updateClearButton();
            });

            // Toggle between Health Submissions and Registration Form and delete form
            const healthSubmissionsLink = document.getElementById('healthSubmissionsLink');
            const deleteStaffLink = document.getElementById('deleteStaffLink');
            const registerNewStaffLink = document.getElementById('registerNewStaffLink');
            const healthSubmissionsHeader = document.getElementById('healthSubmissionsHeader');
            const healthSubmissionsTable = document.querySelector('.table-section'); // Corrected this line
            const registrationForm = document.getElementById('registrationForm');

    

            deleteStaffLink.addEventListener('click', function(e) {
                e.preventDefault();
                healthSubmissionsHeader.style.display = 'none'; 
                healthSubmissionsTable.style.display = 'none';
                registrationForm.style.display = 'none';
                deleteStaffSection.style.display = 'block';
                deleteStaffLink.classList.add('active');
                registerNewStaffLink.classList.remove('active');
                healthSubmissionsLink.classList.remove('active');
            });

            healthSubmissionsLink.addEventListener('click', function(e) {
                e.preventDefault();
                healthSubmissionsHeader.style.display = 'flex';
                healthSubmissionsTable.style.display = 'block';
                registrationForm.style.display = 'none';
                healthSubmissionsLink.classList.add('active');
                registerNewStaffLink.classList.remove('active');
                deleteStaffLink.classList.remove('active');
                deleteStaffSection.style.display = 'none';
            });

            registerNewStaffLink.addEventListener('click', function(e) {
                e.preventDefault();
                healthSubmissionsHeader.style.display = 'none';
                healthSubmissionsTable.style.display = 'none';
                registrationForm.style.display = 'block';
                registerNewStaffLink.classList.add('active');
                healthSubmissionsLink.classList.remove('active');
                deleteStaffLink.classList.remove('active');
                deleteStaffSection.style.display = 'none';
            });

        function confirmLogout() {
            Swal.fire({
            title: 'Are you sure you want to logout?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, log out!'
            }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'healthmonitoring.php';
            }
            });
        }
        
    </script>
</body>
</html>
<?php $conn->close(); ?>