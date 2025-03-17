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
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
                <li><a href="./mfc.php">MFC</a></li>
                <li><a href="./unstain_slide.php">UnStain Slide</a></li>
                <li><a href="./special_instruction.php" class="tab">Special Instructions</a></li>
                <li><a href="./slide_prepared.php" class="tab">Slide Prepared</a></li>
                <li><a href="./new_slide_centrifuge.php" class="tab">New Slide (Centrifuge)</a></li>
                <li><a href="./sbo.php">SBO(Slide Block Order)</a></li>
                <li><a href="../recall.php">Re-Call</a></li>
                <li class="active"><a href="./doctor_instruction.php">Doctor's Instructions</a></li>
                <li><a href="./cancel_information.php">Cancel Information</a></li>
                <li><a href="./postpone_information.php">Postpone</a></li>
            </ul>
        <br>

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

                <!-- Tab Content -->
                <div id="List" class="tabcontent active">
                    <h3>List</h3>

                        
                        <?php
                            // Fetch the data
                            $doctor_lab_instruction = cyto_doctor_lab_instruction();

                            if (is_array($doctor_lab_instruction) && count($doctor_lab_instruction) > 0) {
                                echo("<h3 style='color:red;'>Lab Related Instruction</h3><br>");
                                echo '<table class="table table-bordered table-striped">';

                                // Fields to exclude
                                $exclude_fields = ['rowid', 'screening_stain_name', 'screening_doctor_name', 'finalization_stain_name', 'finalization_doctor_name', 'status'];

                                // Fields to rename
                                $rename_fields = [
                                    'screening_stain_again' => 'Screening Lab Instruction',
                                    'finalization_stain_again' => 'Finalization Lab Instruction'
                                ];

                                // Initialize headers flag
                                $headers = false;

                                foreach ($doctor_lab_instruction as $row) {
                                    if (is_array($row)) {
                                        // Generate table headers once
                                        if (!$headers) {
                                            echo '<thead><tr>';
                                            foreach ($row as $field => $value) {
                                                if (!in_array($field, $exclude_fields)) {
                                                    $column_name = $rename_fields[$field] ?? ucfirst(str_replace('_', ' ', $field));
                                                    echo '<th>' . htmlspecialchars($column_name) . '</th>';
                                                }
                                            }
                                            // Add "Status" column
                                            echo '<th>Status</th>';
                                            echo '</tr></thead>';
                                            $headers = true;
                                        }

                                        // Display row values
                                        echo '<tr>';
                                        foreach ($row as $field => $value) {
                                            if (in_array($field, $exclude_fields)) {
                                                continue;
                                            }

                                            if (in_array($field, ['screening_stain_again', 'finalization_stain_again'])) {
                                                // Decode JSON and format the data
                                                $formatted_data = '';
                                                $decoded_data = json_decode($value, true);

                                                if (is_array($decoded_data)) {
                                                    foreach ($decoded_data as $person => $entries) {
                                                        $formatted_data .= '<strong>' . ucfirst($person) . ':</strong><br>';
                                                        foreach ($entries as $entry) {
                                                            if (isset($entry['stains']) && isset($entry['timestamp'])) {
                                                                try {
                                                                    $utc_time = new DateTime($entry['timestamp'], new DateTimeZone('UTC'));
                                                                    $utc_time->setTimezone(new DateTimeZone('Asia/Dhaka'));
                                                                    $datetime = $utc_time->format('j F, Y g:i A');
                                                                    $formatted_data .= $datetime . '<br>';

                                                                    // Handle stains
                                                                    $formatted_data .= '';
                                                                    if (is_array($entry['stains'])) {
                                                                        foreach ($entry['stains'] as $stain) {
                                                                            if (is_array($stain) && isset($stain['other'])) {
                                                                                $formatted_data .= '' . htmlspecialchars($stain['other']) . ', ';
                                                                            } else {
                                                                                $formatted_data .= htmlspecialchars($stain) . ', ';
                                                                            }
                                                                        }
                                                                        // Remove trailing comma and space
                                                                        $formatted_data = rtrim($formatted_data, ', ');
                                                                    }
                                                                    $formatted_data .= '<br>';
                                                                } catch (Exception $e) {
                                                                    $formatted_data .= 'Invalid Date<br>';
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                                echo '<td>' . $formatted_data . '</td>';
                                            } else {
                                                echo '<td>' . htmlspecialchars($value) . '</td>';
                                            }
                                        }

                                        // Add the form for "Status" column
                                        echo '<td>';
                                        echo '<form method="POST" action="../insert/status_save.php">';
                                        echo '<input type="hidden" name="lab_number" value="' . htmlspecialchars($row['lab_number']) . '">';
                                        echo '<button type="submit" class="btn btn-primary btn-sm">Complete</button>';
                                        echo '</form>';
                                        echo '</td>';

                                        echo '</tr>';
                                    }
                                }
                                echo '</table>';
                            } else {
                                echo '<p>No lab instructions found.</p>';
                            }
                        ?>

                </div>


                <div id="Complete" class="tabcontent">
                        <h3>Completed Instructions</h3>

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
                                $special_instruction_complete = cyto_lab_instruction_status();

                                // Fields to exclude
                                $excluded_fields = ['rowid', 'screening_stain_name', 'screening_doctor_name', 'finalization_stain_name', 'finalization_doctor_name'];

                                // Check for errors or empty data
                                if (isset($special_instruction_complete['error'])) {
                                    echo "<p>Error: " . $special_instruction_complete['error'] . "</p>";
                                } elseif (empty($special_instruction_complete)) {
                                    echo "<p>No completed instructions available.</p>";
                                } else {
                                    echo '<table id="completeTable" class="table table-bordered table-striped">';
                                    echo '<thead>';
                                    echo '<tr>';

                                // Dynamically generate table headers based on field names
                                if (!empty($special_instruction_complete[0])) {
                                    foreach (array_keys($special_instruction_complete[0]) as $field_name) {
                                        if (!in_array($field_name, $excluded_fields)) {
                                            echo '<th>' . htmlspecialchars(ucwords(str_replace('_', ' ', $field_name))) . '</th>';
                                        }
                                    }
                                }

                                echo '</tr>';
                                echo '</thead>';
                                echo '<tbody>';

                                // Loop through data and create rows dynamically
                                foreach ($special_instruction_complete as $row) {
                                    echo '<tr>';

                                    foreach ($row as $field => $value) {
                                        if (in_array($field, $excluded_fields)) {
                                            continue;
                                        }

                                        // Handle 'screening_stain_again' and 'finalization_stain_again' fields
                                        if (in_array($field, ['screening_stain_again', 'finalization_stain_again'])) {
                                            // Decode JSON and format the data
                                            $formatted_data = '';
                                            $decoded_data = json_decode($value, true);

                                            if (is_array($decoded_data)) {
                                                foreach ($decoded_data as $person => $entries) {
                                                    $formatted_data .= '<strong>' . ucfirst($person) . ':</strong><br>';
                                                    foreach ($entries as $entry) {
                                                        if (isset($entry['stains']) && isset($entry['timestamp'])) {
                                                            try {
                                                                $formatted_data .= htmlspecialchars($entry['timestamp']) . '<br>';

                                                                // Handle stains
                                                                if (is_array($entry['stains'])) {
                                                                    foreach ($entry['stains'] as $stain) {
                                                                        if (is_array($stain) && isset($stain['other'])) {
                                                                            $formatted_data .= htmlspecialchars($stain['other']) . ', ';
                                                                        } else {
                                                                            $formatted_data .= htmlspecialchars($stain) . ', ';
                                                                        }
                                                                    }
                                                                    // Remove trailing comma and space
                                                                    $formatted_data = rtrim($formatted_data, ', ');
                                                                }
                                                                $formatted_data .= '<br>';
                                                            } catch (Exception $e) {
                                                                $formatted_data .= 'Invalid Date<br>';
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                            echo '<td>' . $formatted_data . '</td>';
                                        } 

                                        // Handle 'status_list' field
                                        elseif ($field === 'status_list') {
                                            // Check if the field contains valid JSON data
                                            $status_data = json_decode($value, true);

                                            // Check if json_decode was successful
                                            if (json_last_error() === JSON_ERROR_NONE) {
                                                $formatted_status_list = '';

                                                // Loop through each status entry and format it
                                                foreach ($status_data as $status_entry) {
                                                    // Directly display the status and timestamp
                                                    $formatted_status_list .= htmlspecialchars($status_entry['status']) . ' - ' . htmlspecialchars($status_entry['timestamp']) . '<br>';
                                                }
                                                echo '<td>' . $formatted_status_list . '</td>';
                                            } else {
                                                echo '<td></td>';
                                            }
                                        } 

                                        // Default field handling
                                        else {
                                            echo '<td>' . htmlspecialchars($value) . '</td>';
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