<?php
include("../connection.php");
include('../../../grossmodule/gross_common_function.php');
include('../../../transcription/common_function.php');
include('../../../transcription/FNA/function.php');
include('../../common_function.php');


$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--; $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

// Load translation files required by the page
$langs->loadLangs(array("cytology@cytology"));

$action = GETPOST('action', 'aZ09');


$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

$max = 5;
$now = dol_now();


$form = new Form($db);
$formfile = new FormFile($db);

llxHeader("", $langs->trans("CytologyArea"));



$loggedInUsername = $user->login;
$loggedInUserId = $user->id;

$isCytoAssistant = false;
$isDoctor = false;

$isAdmin = isUserAdmin($loggedInUserId);

$LabNumber = $_GET['labNumber'];


$assistants = get_cyto_tech_list();
foreach ($assistants as $assistant) {
    if ($assistant['username'] == $loggedInUsername) {
        $isCytoAssistant = true;
        break;
    }
}

$doctors = get_doctor_list();
foreach ($doctors as $doctor) {
    if ($doctor['doctor_username'] == $loggedInUsername) {
        $isDoctor = true;
        break;
    }
}

// Access control using switch statement
switch (true) {
    case $isCytoAssistant:
        // cyto Assistant has access, continue with the page content...
        break;
    case $isDoctor:
        // Doctor has access, continue with the page content...
        break;
    case $isAdmin:
        // Admin has access, continue with the page content...
        break;
        
    default:
        echo "<h1>Access Denied</h1>";
        echo "<p>You are not authorized to view this page.</p>";
        exit; // Terminate script execution
}

$host = $_SERVER['HTTP_HOST'];
$homeUrl = "http://" . $host . "/custom/cytology/cytologyindex.php";
$reportUrl = "http://" . $host . "/custom/transcription/FNA/fna_report.php?LabNumber=" . urlencode($LabNumber) . "&username=" . urlencode($loggedInUsername);


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../bootstrap-3.4.1-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../bootstrap-3.4.1-dist/js/bootstrap.min.js">
    <style>
        .tab-container {
            text-align: center;
            margin-top: 20px;
        }
        .tabs {
            display: flex;
            justify-content: center;
            gap: 20px;
        }
        .tablink {
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 100px;
            font-size: 16px;
        }
        
        .tabcontent {
            display: none;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .tabcontent.active {
            display: block;
        }
    </style>
</head>
<body>
<div class="container">
        <h3>Cyto Lab WorkFlow</h3>
            <ul class="nav nav-tabs">
                <li><a href="../index.php">Home</a></li>
                <li><a href="./special_instruction.php" class="tab">Special Instructions</a></li>
                <li class="active"><a href="./slide_prepared.php" class="tab">Slide Prepared</a></li>
                <li><a href="./new_slide_centrifuge.php" class="tab">New Slide (Centrifuge)</a></li>
                <li><a href="./sbo.php">SBO(Slide Block Order)</a></li>
                <li><a href="../recall.php">Re-Call</a></li>
            </ul>
        <br>
        <h1>Slide Prepare</h1>
        <div class="tab-container">
            <!-- Tab Links -->
            <div class="tabs">
                <button class="tablink active" onclick="openTab(event, 'List')">
                    <i class="fas fa-list-alt" style="font-size: 25px;"></i>
                    List
                </button>
                <button class="tablink" onclick="openTab(event, 'Complete')">
                    <i class="fas fa-check-circle" style="font-size: 25px;"></i>
                    Complete
                </button>
            </div>

            <div id="List" class="tabcontent active">
               
                <!-- Form to Input Lab Number -->
                <form id="labForm" action="../insert/slide_prepare.php" method="POST">
                    <label for="lab_number" style="font-size: 35px;">Lab Number:</label>
                    <input type="text" id="lab_number" name="lab_number" required placeholder="Enter Lab Number" style="padding: 10px; font-size: 16px; width: 100%; margin-bottom: 20px;">

                    <!-- Hidden Field for Created User -->
                    <input type="hidden" name="created_user" value="<?php echo $loggedInUsername; ?>">

                    <!-- Submit Button (Hidden) -->
                    <button type="submit" style="display: none;">Save Lab Number</button>
                </form>
            </div>

            <div id="Complete" class="tabcontent">
                <h3>Completed Slide Prepare </h3>

                <!-- Search Input -->
                <div class="search-container">
                    <input 
                        type="text" 
                        id="searchInput" 
                        onkeyup="searchTable()" 
                        placeholder="Search for data..." 
                        class="form-control"
                        style="margin-bottom: 10px; width: 50%;"
                    >
                </div>

                <?php
                    // Fetch data
                    $slide_prepare = cyto_slide_prepared_list();
                    // Fields to exclude
                    $excluded_fields = [''];
                    // Check for errors or empty data
                    if (isset($slide_prepare['error'])) {
                        echo "<p>Error: " . $slide_prepare['error'] . "</p>";
                    } elseif (empty($slide_prepare)) {
                        echo "<p>No completed instructions available.</p>";
                    } else {
                            echo '<table id="completeTable" class="table table-bordered table-striped">';
                            echo '<thead>';
                            echo '<tr>';

                            // Dynamically generate table headers based on field names
                            if (!empty($slide_prepare[0])) {
                                foreach (array_keys($slide_prepare[0]) as $field_name) {
                                    if (!in_array($field_name, $excluded_fields)) {
                                        echo '<th>' . htmlspecialchars(ucwords(str_replace('_', ' ', $field_name))) . '</th>';
                                    }
                                }
                            }

                            echo '</tr>';
                            echo '</thead>';
                            echo '<tbody>';

                            // Loop through data and create rows dynamically
                            foreach ($slide_prepare as $row) {
                                echo '<tr>';

                                // Generate cells dynamically for each field
                                foreach ($row as $field_name => $field_value) {
                                    if (!in_array($field_name, $excluded_fields)) {
                                        // Format the created_date field
                                        if ($field_name === 'created_date') {
                                            // Assuming the database stores time in UTC
                                            $utc_time = new DateTime($field_value, new DateTimeZone('UTC')); // Specify UTC timezone
                                            $utc_time->setTimezone(new DateTimeZone('Asia/Dhaka')); // Convert to Asia/Dhaka timezone
                                            $field_value = $utc_time->format('j F, Y g:i A'); // Format in 12-hour format with AM/PM
                                        }
                                        echo '<td>' . htmlspecialchars($field_value) . '</td>';
                                    }
                                }

                                echo '</tr>';
                            }

                            echo '</tbody>';
                            echo '</table>';
                        }
                        ?>
            </div>

        </div>
</div>

<script>
        function openTab(evt, tabName) {
            // Get all tab contents and hide them
            const tabContents = document.getElementsByClassName("tabcontent");
            for (let i = 0; i < tabContents.length; i++) {
                tabContents[i].classList.remove("active");
            }

            // Remove active class from all tab links
            const tabLinks = document.getElementsByClassName("tablink");
            for (let i = 0; i < tabLinks.length; i++) {
                tabLinks[i].classList.remove("active");
            }

            // Show the selected tab and mark it as active
            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.classList.add("active");

            // Save the active tab in sessionStorage
            sessionStorage.setItem("activeTab", tabName);
        }

        // On page load, restore the active tab from sessionStorage
        window.onload = function() {
            const activeTab = sessionStorage.getItem("activeTab") || "List"; // Default to "List"
            const tabToClick = document.querySelector(`.tablink[onclick*="${activeTab}"]`);
            if (tabToClick) {
                tabToClick.click();
            }
        };
</script>


<script>
    // Get the input field and the form
    const labNumberInput = document.getElementById('lab_number');
    const labForm = document.getElementById('labForm');

    // Flag to prevent premature form submission
    let isTyping = false;

    // Automatically submit the form when Enter is pressed
    labNumberInput.addEventListener('keydown', function(event) {
        if (event.key === 'Enter') {
            // Only submit if the input value is not empty and the user has finished typing
            if (labNumberInput.value.trim() !== "" && !isTyping) {
                event.preventDefault(); // Prevent default form submission
                labForm.submit(); // Submit the form programmatically
            }
        }
    });

    // Prevent premature submission while typing (Optional delay or condition)
    labNumberInput.addEventListener('input', function() {
        if (labNumberInput.value.trim() !== "") {
            // Set the flag to indicate the user is typing
            isTyping = true;
        } else {
            isTyping = false;
        }
    });

    // Optional: You can add a small delay to prevent submission right after typing, like after 1 second.
    let timeout;
    labNumberInput.addEventListener('input', function() {
        clearTimeout(timeout);
        timeout = setTimeout(function() {
            if (labNumberInput.value.trim() !== "") {
                labForm.submit(); // Submit the form programmatically
            }
        }, 1000000); // Adjust the delay (1000ms = 1 second) as needed
    });
</script>

<script>
        // Search functionality for the table
        function searchTable() {
            const input = document.getElementById("searchInput");
            const filter = input.value.toUpperCase();
            const table = document.getElementById("completeTable");
            const tr = table.getElementsByTagName("tr");

            for (let i = 1; i < tr.length; i++) { // Start from 1 to skip the header row
                let td = tr[i].getElementsByTagName("td");
                let isMatch = false;

                for (let j = 0; j < td.length; j++) { // Check all columns
                    if (td[j]) {
                        const textValue = td[j].textContent || td[j].innerText;
                        if (textValue.toUpperCase().indexOf(filter) > -1) {
                            isMatch = true;
                            break;
                        }
                    }
                }

                tr[i].style.display = isMatch ? "" : "none"; // Show or hide rows based on match
            }
        }
</script>

</body>
</html>