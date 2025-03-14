<?php
//set date time zone manila 
date_default_timezone_set('Asia/Manila');
// Start the session
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "employeedatabase";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("<script>alert('Database connection failed: " . addslashes($conn->connect_error) . "');</script>");
}
// Fetch units
$unit_options = '';
$unit_sql = "SELECT name FROM units ORDER BY name";
if ($unit_stmt = $conn->prepare($unit_sql)) {
    $unit_stmt->execute();
    $result = $unit_stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $unit_options .= "<option value='".htmlspecialchars($row['name'])."'>".htmlspecialchars($row['name'])."</option>";
    }
    $unit_stmt->close();
} else {
    die("<script>alert('Error fetching units: " . addslashes($conn->error) . "');</script>");
}

// Fetch employee symptoms
$employee_symptoms_html = '';
$symptom_sql = "SELECT name FROM symptoms WHERE category = 'employee' ORDER BY name";
if ($symptom_stmt = $conn->prepare($symptom_sql)) {
    $symptom_stmt->execute();
    $symptom_result = $symptom_stmt->get_result();
    while ($row = $symptom_result->fetch_assoc()) {
        $employee_symptoms_html .= "<div class='form-check form-check-inline'>
            <input class='form-check-input' type='checkbox' name='symptoms[]' value='".htmlspecialchars($row['name'])."'>
            <label class='form-check-label'>".htmlspecialchars($row['name'])."</label>
        </div><br>";
    }
    $symptom_stmt->close();
} else {
    die("<script>alert('Error fetching employee symptoms: " . addslashes($conn->error) . "');</script>");
}

// Fetch household symptoms
$household_symptoms_html = '';
$household_symptom_sql = "SELECT name FROM symptoms WHERE category = 'household' ORDER BY name";
if ($household_symptom_stmt = $conn->prepare($household_symptom_sql)) {
    $household_symptom_stmt->execute();
    $household_result = $household_symptom_stmt->get_result();
    while ($row = $household_result->fetch_assoc()) {
        $household_symptoms_html .= "<div class='form-check form-check-inline'>
            <input class='form-check-input' type='checkbox' name='householdSymptomsDetails[]' value='".htmlspecialchars($row['name'])."'>
            <label class='form-check-label'>".htmlspecialchars($row['name'])."</label>
        </div><br>";
    }
    $household_symptom_stmt->close();
} else {
    die("<script>alert('Error fetching household symptoms: " . addslashes($conn->error) . "');</script>");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Validate required fields
        $required = ['employeeName', 'unit', 'well', 'householdSymptoms', 'environmentalCheck', 'mentalHealthCheck', 'statusLocation','HeatIndexCheck'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("All required fields must be filled!");
            }
        }
        // Debugging: Log the symptoms array
        if (isset($_POST['symptoms'])) {
            error_log("Symptoms received: " . print_r($_POST['symptoms'], true));
        } else {
            error_log("No symptoms received.");
        }

        // Sanitize inputs
        $employee_name = $conn->real_escape_string($_POST['employeeName']);
        $unit = $conn->real_escape_string($_POST['unit']);
        $wellness_status = $conn->real_escape_string($_POST['well']);

        // Check duplicate submission
        $check_sql = "SELECT id FROM health_records 
                     WHERE employee_name = ? 
                     AND DATE(submission_date) = CURDATE()";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $employee_name);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) {
            throw new Exception("You've already submitted today's health check!");
        }

        // Process conditional fields
        
        $symptoms_array = isset($_POST['symptoms']) ? $_POST['symptoms'] : [];
        $other_symptom = isset($_POST['otherSymptom']) ? trim($_POST['otherSymptom']) : '';
        $environmental_issues = isset($_POST['environmentalIssues']) ? implode(", ", array_filter($_POST['environmentalIssues'])) : '';
        $mental_support = isset($_POST['mentalHealthsupport']) ? implode(", ", array_filter($_POST['mentalHealthsupport'])) : '';
        $symptoms_management = isset($_POST['symptomsManagement']) ? $conn->real_escape_string($_POST['symptomsManagement']) : '';

        // Initialize symptoms variable
        $symptoms = implode(", ", $symptoms_array);
            
        // Handle "Other" symptom fields
        if (isset($_POST['symptoms']) && in_array('Other', $_POST['symptoms'])) {
            $symptoms_array = array_diff($symptoms_array, ['Other']);
            $symptoms_array[] = $conn->real_escape_string($other_symptom);
            $symptoms = implode(", ", $symptoms_array);
        }
        if ($wellness_status === 'no' && empty($symptoms)) {
            throw new Exception("Please select symptoms if you're unwell!");
        }
        if ($wellness_status === 'no' && empty($symptoms_management)) {
            throw new Exception("Please select symptoms management option!");
        }

        $household_details = isset($_POST['householdSymptomsDetails']) ? implode(", ", array_filter($_POST['householdSymptomsDetails'])) : '';
        if (isset($_POST['householdSymptomsDetails']) && in_array('Other', $_POST['householdSymptomsDetails'])) {
            $household_details .= ', ' . $conn->real_escape_string($_POST['otherHouseholdSymptom'] ?? '');
        } 
        if ($_POST['householdSymptoms'] === 'yes' && count(array_filter($_POST['householdSymptomsDetails'])) == 0 && empty($_POST['otherHouseholdSymptom'])) {
            throw new Exception("Please specify household member symptoms!");
        } else if ($_POST['householdSymptoms'] === 'yes' && in_array('Other', $_POST['householdSymptomsDetails']) && empty($_POST['otherHouseholdSymptom'])) {
            throw new Exception("Please specify the 'Other' household symptom!");
        }
        // Validate conditional requirements 
        if ($wellness_status === 'no' && empty($symptoms_management)) {
            throw new Exception("Please select symptoms management option!");
        }

        if ($_POST['environmentalCheck'] === 'yes' && empty($environmental_issues)) {
            throw new Exception("Please specify environmental issues!");
        }

        if ($_POST['mentalHealthCheck'] === 'yes' && empty($mental_support)) {
            throw new Exception("Please select mental health support options!");
        }

       

        // Prepare SQL statement
        $stmt = $conn->prepare("INSERT INTO health_records (
            employee_name, unit, wellness_status, symptoms, symptoms_management,
            household_symptoms, household_symptoms_details, environmental_check,
            environmental_issues, mental_health_check, mental_health_support, current_status, heat_index_status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param("sssssssssssss",
        $employee_name, 
        $unit, 
        $wellness_status,
        $symptoms,
        $symptoms_management,
        $_POST['householdSymptoms'],
        $household_details,
        $_POST['environmentalCheck'],
        $environmental_issues,
        $_POST['mentalHealthCheck'],
        $mental_support,
        $_POST['statusLocation'],
        $_POST['HeatIndexCheck']
        );

        if ($stmt->execute()) {
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Submission successful! Date: ' . date('F j, Y, g:i a')];
        } else {
            throw new Exception("Database error: " . $stmt->error);
        }

        $stmt->close();
    } catch (Exception $e) {
        $_SESSION['message'] = ['type' => 'error', 'text' => $e->getMessage()];
    }
    
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

// Fetch employee names
$employee_options = '';
$employee_sql = "SELECT CONCAT(firstName, ' ', lastName) AS full_name FROM employee_db";
if ($employee_stmt = $conn->prepare($employee_sql)) {
    $employee_stmt->execute();
    $result = $employee_stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $employee_options .= "<option value='".htmlspecialchars($row['full_name'])."'>".htmlspecialchars($row['full_name'])."</option>";
    }
    $employee_stmt->close();
} else {
    die("<script>alert('Error fetching employee names: " . addslashes($conn->error) . "');</script>");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- links -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"rel="stylesheet">
    <link href='https://fonts.googleapis.com/css?family=Inter' rel='stylesheet'>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css"rel="stylesheet"  >
    <link href='https://fonts.googleapis.com/css?family=Cardo' rel='stylesheet'>
    <link href='https://fonts.googleapis.com/css?family=Roboto' rel='stylesheet'>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link rel="stylesheet" href="healthmonitoringstyle.css">

    <title>Health and Wellness Check</title>


</head>
<!-- header section -->
<!-- Modified Header Section -->
<header class="text-white py-3 shadow" style="background: transparent;">
    <div class="container d-flex align-items-center justify-content-between">
        <a class="text-white text-decoration-none">
            <img src="https://wellness.zuelligfoundation.ngo/wp-content/uploads/2024/02/logo-300x81.png" 
                 alt="Zuellig Logo" 
                 class="me-2" 
                 style="height: 40px;">
        </a>
        <div class="d-flex align-items-center">
            <!-- Helpdesk Link -->
            <a href="https://helpdesk.zffintranet.cloud/" 
               class="btn btn-light d-flex align-items-center me-2 helpdesk-btn"
               style="padding: 5px 10px;">
            <i class="bi bi-question-circle me-2"></i>Helpdesk
            </a>
            <!-- Login Button -->
            <button type="button" 
            class="btn btn-light d-flex align-items-center login-btn" 
                onclick="window.location.href='login.php'">
            <i class="bi bi-box-arrow-in-right me-2"></i> Log In
            </button>
        </div>
    </div>
</header>

<style>
    .helpdesk-btn:hover, .login-btn:hover {
        background-color: blue;
        color: white;
    }
</style>
<!--Body section -->
<body>
<!-- Introduction -->
<div class="container mt-5 form-container">
    <img src="https://wellness.zuelligfoundation.ngo/wp-content/uploads/2024/02/logo-300x81.png" alt="Zuellig Logo" class="mb-4">
    <h2>Health and Wellness Check</h2>
    <p>By participating in this daily health and wellness check-in, I consent to the collection of my personal and health information (e.g., health condition, symptoms) to support employee health and safety across all work settings, including office and remote work locations. I understand that my information will be kept confidential and used only by authorized personnel, including but not limited to the Occupational Health Physician, Data Privacy Officer, the Human Resources Unit, and the Admin Associate. Furthermore, I understand my rights to access, correct, and delete my data and can contact Dane Cada or Mel Reyes with questions.</p>
<!-- Basic Information -->
    <h3>Basic Information</h3>
    <form method="post" action="" novalidate>
    <div class="form-group">
        <label for="employeeName">Employee Name <span style="color: red;">*</span></label>
        <select class="form-control" id="employeeName" name="employeeName" required>
            <option value="">Select your name</option>
            <?php echo $employee_options; ?>
        </select>
    </div>

    <!-- Include jQuery and Select2 JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
  <!-- Unit -->      
        <div class="form-group">
            <label for="unit">Unit <span style="color: red;">*</span></label>
            <select class="form-control" id="unit" name="unit" required>
                <option value="">Select your Unit</option>
                <?php echo $unit_options; ?>
            </select>
        </div>
   <!-- Health Check Section -->        
   <br><h3>Health Check</h3>
        <div class="form-group">
            <label>Are you well? <span style="color: red;">*</span></label><br>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="well" id="wellYes" value="yes" required>
                <label class="form-check-label" for="wellYes">Yes</label>
            </div><br>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="well" id="wellNo" value="no" required>
                <label class="form-check-label" for="wellNo">No</label>
            </div><br>
        </div>
        <!--Symptoms Details -->        
        <div class="form-group" id="symptomsDetails" style="display:none;">
        <label>If no, are you experiencing any of these symptoms? <span style="color: red;">*</span></label><br>
                <?php echo $employee_symptoms_html; ?>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="symptoms[]" value="Other" id="otherSymptomCheckbox2">
                    <label class="form-check-label" for="otherSymptomCheckbox2">Other</label>
                </div><br>
                <div class="form-group" id="otherSymptomText2" style="display:none;">
                    <label for="otherSymptom2">Please specify:</label>
                    <input type="text" class="form-control" id="otherSymptom2" name="otherSymptom">
                </div>
            <script>
                document.getElementById('otherSymptomCheckbox2').addEventListener('change', function() {
                    document.getElementById('otherSymptomText2').style.display = this.checked ? 'block' : 'none';
                });
            </script>
        </div>  

<div class="form-group" id="symptomsManagement" style="display:none;">
    <label>Given my symptoms <span style="color: red;">*</span></label><br>
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="symptomsManagement" id="manageAtHome" value="manageAtHome" required>
        <label class="form-check-label" for="manageAtHome">I can manage my symptoms at home</label>
    </div><br>
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="symptomsManagement" id="consultProfessional" value="consultProfessional" required>
        <label class="form-check-label" for="consultProfessional">I need to consult a medical professional through Etiqa.</label>
    </div><br>
    <p>Follow these steps to avail:</p>
    <ul>
        <li><a href="https://tinyurl.com/NaviDocAny/" target="_blank">Doctor Anywhere</a></li><br>
        <li><a href="https://tinyurl.com/NaviKonMD/" target="_blank">Konsulta MD</a></li><br>
    </ul>
</div>

<script>
    document.getElementById('wellNo').addEventListener('change', function() {
        document.getElementById('symptomsDetails').style.display = 'block';
        document.getElementById('symptomsManagement').style.display = 'block';
        document.querySelectorAll('#symptomsDetails input[type="checkbox"]').forEach(cb => {
            cb.required = true;
        });
        document.querySelectorAll('#symptomsManagement input[type="radio"]').forEach(rb => {
            rb.required = true;
        });
    });

    document.getElementById('wellYes').addEventListener('change', function() {
        document.getElementById('symptomsDetails').style.display = 'none';
        document.getElementById('symptomsManagement').style.display = 'none';
        document.querySelectorAll('#symptomsDetails input[type="checkbox"]').forEach(cb => {
            cb.required = false;
        });
        document.querySelectorAll('#symptomsManagement input[type="radio"]').forEach(rb => {
            rb.required = false;
            rb.checked = false;
        });
    });

</script> 
       
 
<div class="form-group">
    <label>Do you have household members with COVID-19 symptoms or other health condition/s? <span style="color: red;">*</span></label><br>
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="householdSymptoms" id="householdSymptomsYes" value="yes" required>
        <label class="form-check-label" for="householdSymptomsYes">Yes</label>
    </div><br>
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="householdSymptoms" id="householdSymptomsNo" value="no" required>
        <label class="form-check-label" for="householdSymptomsNo">No</label>
    </div><br>
</div>


    <!-- Household Symptoms Section -->
    <div class="form-group" id="householdSymptomsDetails" style="display:none;">
        <label>What symptoms are they experiencing? <span style="color: red;">*</span></label><br>
        <?php echo $household_symptoms_html; ?>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="checkbox" name="householdSymptomsDetails[]" value="Other" id="otherSymptomCheckbox">
            <label class="form-check-label">Other</label>
        </div><br>
        <div class="form-group" id="otherSymptomText" style="display:none;">
            <label for="otherSymptom">Please specify:</label>
            <input type="text" class="form-control" id="otherSymptom" name="otherHouseholdSymptom">
        </div><br>
  
    <script>
        // Household symptoms Script
        document.getElementById('householdSymptomsYes').addEventListener('change', function() {
            document.getElementById('householdSymptomsDetails').style.display = 'block';
            document.querySelectorAll('#householdSymptomsDetails input[type="checkbox"]').forEach(cb => {
                if (cb.value === 'Other') return;
                cb.required = true;
            });
        });

        document.getElementById('householdSymptomsNo').addEventListener('change', function() {
            document.getElementById('householdSymptomsDetails').style.display = 'none';
            document.querySelectorAll('#householdSymptomsDetails input[type="checkbox"]').forEach(cb => {
                cb.required = false;
                if (!cb.checked) cb.value = '';
            });
        });

        document.getElementById('otherSymptomCheckbox').addEventListener('change', function() {
            const otherInput = document.getElementById('otherSymptom');
            document.getElementById('otherSymptomText').style.display = this.checked ? 'block' : 'none';
            otherInput.required = this.checked;
            otherInput.disabled = !this.checked;
            if (!this.checked) otherInput.value = '';
        });
    </script>
</div>
<br><h3>Environmental Check </h3>
<div class="form-group">
    <label>Report imminent weather disturbance, natural disasters, unsafe conditions or service interruptions in your area, if any. <span style="color: red;">*</span></label><br>
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="environmentalCheck" id="environmentalCheckNone" value="none" required>
        <label class="form-check-label" for="environmentalCheckNone">None</label>
    </div><br>
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="environmentalCheck" id="environmentalCheckYes" value="yes" required>
        <label class="form-check-label" for="environmentalCheckYes">Yes</label>
    </div><br>
</div>

<div class="form-group" id="environmentalDetails" style="display:none;">
    <label>If yes, what are those? <span style="color: red;">*</span></label><br>
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="checkbox" name="environmentalIssues[]" value="Natural disasters – severe weather disturbance">
        <label class="form-check-label">Natural disasters – severe weather disturbance</label>
    </div><br>
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="checkbox" name="environmentalIssues[]" value="Natural disasters – flooding">
        <label class="form-check-label">Natural disasters – flooding</label>
    </div><br>
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="checkbox" name="environmentalIssues[]" value="Natural disasters – earthquake">
        <label class="form-check-label">Natural disasters – earthquake</label>
    </div><br>
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="checkbox" name="environmentalIssues[]" value="Natural disasters – volcanic eruption">
        <label class="form-check-label">Natural disasters – volcanic eruption</label>
    </div><br>
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="checkbox" name="environmentalIssues[]" value="Societal events – civil unrest">
        <label class="form-check-label">Societal events – civil unrest</label>
    </div><br>
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="checkbox" name="environmentalIssues[]" value="Societal events – labor strikes">
        <label class="form-check-label">Societal events – labor strikes</label>
    </div><br>
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="checkbox" name="environmentalIssues[]" value="Utility service disruptions – internet service disruption">
        <label class="form-check-label">Utility service disruptions – internet service disruption</label>
    </div><br>
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="checkbox" name="environmentalIssues[]" value="Utility service disruptions – power outage">
        <label class="form-check-label">Utility service disruptions – power outage</label>
    </div><br>
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="checkbox" name="environmentalIssues[]" value="Utility service disruptions – water interruption">
        <label class="form-check-label">Utility service disruptions – water interruption</label>
    </div><br>
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="checkbox" name="environmentalIssues[]" value="ICT Resources technical malfunction">
        <label class="form-check-label">ICT Resources technical malfunction</label>
    </div><br>
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="checkbox" name="environmentalIssues[]" value="I can manage the situation">
        <label class="form-check-label">I can manage the situation</label>
    </div><br>
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="checkbox" name="environmentalIssues[]" value="I need ZFF support to manage the situation">
        <label class="form-check-label">I need ZFF support to manage the situation</label>
    </div><br>
</div>

<script>
    document.getElementById('environmentalCheckYes').addEventListener('change', function() {
        document.getElementById('environmentalDetails').style.display = 'block';
    });

    document.getElementById('environmentalCheckNone').addEventListener('change', function() {
        document.getElementById('environmentalDetails').style.display = 'none';
    });
</script>

<br><h3>Mental Check</h3>    
<div class="form-group">
    <label>Do you have work or personal concerns that affect your mental health.  <span style="color: red;">*</span> </label><br>
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="mentalHealthCheck" id="mentalHealthCheckNone" value="none" required>
        <label class="form-check-label" for="mentalHealthCheckNone">None</label>
    </div><br>
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="mentalHealthCheck" id="mentalHealthCheckYes" value="yes" required>
        <label class="form-check-label" for="mentalHealthCheckYes">Yes</label>
    </div><br>
</div>

<div class="form-group" id="mentalHealthDetails" style="display:none;">
    <textarea class="form-control" name="mentalHealthDetails" rows="3" placeholder="If you are experiencing Mental Health (MH) Challenges and would like to set up a consultation/session with our partnered MH professionals, please reach out to Ms. Barbara Jamili via MS Teams, email or mobile." readonly></textarea>
    <br><div class="form-check form-check-inline">
        <input class="form-check-input" type="checkbox" name="mentalHealthsupport[]" value="mentalhealthsupport - can manage the situation">
        <label class="form-check-label">I can manage my mental health concern.</label>
    </div><br>
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="checkbox" name="mentalHealthsupport[]" value="mentalhealthsupport - need ZFF support">
        <label class="form-check-label">I need support from ZFF HR to setup mental health consultation.</label>
    </div><br>
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="checkbox" name="mentalHealthsupport[]" value="mentalhealthsupport - get in touch with own preferred mental health professional">
        <label class="form-check-label">I will get in touch with my own preferred mental health professional.</label>
    </div><br>
</div>

<script>
    document.getElementById('mentalHealthCheckYes').addEventListener('change', function() {
        document.getElementById('mentalHealthDetails').style.display = 'block';
    });

    document.getElementById('mentalHealthCheckNone').addEventListener('change', function() {
        document.getElementById('mentalHealthDetails').style.display = 'none';
    });
</script>

<!-- Heat Index Check Section -->
<br><h3>Heat Index Check</h3>  
<div class="form-group">
    <label>In view of rising Heat Index in the country, are you experiencing discomfort that affects your health?</label>
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="HeatIndexCheck" id="HeatIndexYes" value="yes" required>
        <label class="form-check-label" for="HeatIndexYes">Yes</label>
    </div><br>
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="HeatIndexCheck" id="HeatIndexNo" value="no" required>
        <label class="form-check-label" for="HeatIndexNo">No</label>
    </div><br>
</div>

<!-- Guidance Message (Initially Hidden) -->
<div class="form-group" id="HeatIndexGuidance" style="display: none;">
        <p><strong>If Yes, then we encourage you to file a leave (YSIL or EL) and be guided by the following:</strong></p>
        <ul style="font-size: 14px;">
            <li>If you're outside, find shade.</li>
            <li>Drink plenty of fluids to stay hydrated.</li>
            <li>Avoid high-energy activities or work outdoors during midday heat.</li>
            <li>Electric fans can help cool the body when the indoor temperature is below 39-40˚C.</li>
            <li>Keep your skin wet using a spray bottle or damp sponge.</li>
            <li>Soak a towel in cool tap water and wrap it loosely around your head.</li>
            <li>Take cool showers or foot baths with cool tap water.</li>
        </ul>
</div>

<script>
    // Toggle Heat Index Guidance Message
    document.getElementById('HeatIndexYes').addEventListener('change', function() {
        document.getElementById('HeatIndexGuidance').style.display = 'block';
    });

    document.getElementById('HeatIndexNo').addEventListener('change', function() {
        document.getElementById('HeatIndexGuidance').style.display = 'none';
    });
</script>

<div class="form-group">
    <label>Please check applicable status/location at the moment. <span style="color: red;">*</span></label><br>
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="statusLocation" id="statusLocationRemote" value="remote" required>
        <label class="form-check-label" for="statusLocationRemote">Remote or Work From Home</label>
    </div><br>
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="statusLocation" id="statusLocationRTO" value="rto" required>
        <label class="form-check-label" for="statusLocationRTO">RTO – ZFF Office</label>
    </div><br>
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="statusLocation" id="statusLocationOfficial" value="official" required>
        <label class="form-check-label" for="statusLocationOfficial">Official Business Meeting/Fieldwork</label>
    </div><br>

    <div class="d-flex justify-content-center">
        <button type="submit" class="btn-custom">Submit</button>
    </div>
</form>
</div>  

<script>
    document.querySelector('form').addEventListener('submit', function(event) {
        if (document.getElementById('wellNo').checked) {
            const symptomsChecked = document.querySelectorAll('#symptomsDetails input[type="checkbox"]:checked').length > 0;
            if (!symptomsChecked) {
                alert("Please select at least one symptom if you're unwell.");
                event.preventDefault();
            }

            if (document.getElementById('otherSymptomCheckbox2').checked && document.getElementById('otherSymptom2').value.trim() === '') {
                alert("Please specify the 'Other' symptom.");
                event.preventDefault();
            }
        }
    });

    // Toggle display of symptom details if household members have symptoms
    document.getElementById('householdSymptomsYes').addEventListener('change', function() {
        document.getElementById('householdSymptomsDetails').style.display = 'block';
    });

    document.getElementById('householdSymptomsNo').addEventListener('change', function() {
        document.getElementById('householdSymptomsDetails').style.display = 'none';
    });
</script>

<?php if (isset($_SESSION['message'])): ?>
<script>
    alert("<?= $_SESSION['message']['text'] ?>");
    <?php unset($_SESSION['message']); ?>
</script>
<?php endif; ?>
</body>
</html>
<?php $conn->close(); ?>
    