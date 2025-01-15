<?php
include('../connection.php');
include('../../grossmodule/gross_common_function.php');
include('../../transcription/common_function.php');
include('../../transcription/FNA/function.php');
include('../common_function.php');

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

$LabNumber = $_GET['LabNumber'];


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
  <title>Bootstrap Example</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="bootstrap-3.4.1-dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="bootstrap-3.4.1-dist/js/bootstrap.min.js">
 
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
            <li><a href="./index.php">Home</a></li>
            <li><a href="view/special_instruction.php" class="tab">Special Instructions</a></li>
            <li><a href="view/slide_prepared.php" class="tab">Slide Prepared</a></li>
            <li><a href="view/new_slide_centrifuge.php" class="tab">New Slide (Centrifuge)</a></li>
            <li><a href="view/sbo.php">SBO(Slide Block Order)</a></li>
            <li class="active"><a href="./recall.php">Re-Call</a></li>
            <li><a href="view/doctor_instruction.php">Doctor's Instructions</a></li>
            <li><a href="view/cancel_information.php">Cancel Information</a></li>
            <li><a href="view/postpone_information.php">Postpone</a></li>
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
                // Fetch data using the cyto_recall_status_not_done_list function
                $recall_data = cyto_recall_status_not_done_list();

                // Check for errors or empty data
                if (isset($recall_data['error'])) {
                    echo "<p>Error: " . $recall_data['error'] . "</p>";
                } elseif (isset($recall_data['message'])) {
                    echo "<p>" . $recall_data['message'] . "</p>";
                } else {
                    // Display data in a table if available
                    if (count($recall_data) > 0) {
                        echo '<table class="table table-bordered table-striped">';
                        echo '<thead>';
                        echo '<tr>';
                        echo '<th>Lab Number</th>';
                        echo '<th>Recall Reason</th>';
                        echo '<th>Date</th>';
                        echo '<th>Doctor</th>';
                        echo '<th>Notified Method</th>';
                        echo '<th>Follow-Up Date</th>';
                        echo '<th><i class="fas fa-pencil-alt"></i></th>';
                        echo '</tr>';
                        echo '</thead>';
                        echo '<tbody>';

                        // Loop through each record and create a table row
                        foreach ($recall_data as $row) {
                            // Decode the JSON data for recall reasons (dynamic keys)
                            $recall_data_json = json_decode($row['recall_reason'], true);

                            // Prepare the output for each person dynamically
                            $formatted_recall_reasons = '';
                            foreach ($recall_data_json as $person => $records) {
                                $formatted_recall_reasons .= '<strong style="font-size: 18px;">' . htmlspecialchars($person) . '</strong><br>';
                                foreach ($records as $record) {
                                    $reasons = isset($record['reason']) ? implode(", ", $record['reason']) : '';
                                    $timestamp = new DateTime($record['timestamp']);
                                    $timestamp->setTimezone(new DateTimeZone('Asia/Dhaka'));
                                    $formatted_recall_reasons .= 'Reasons: ' . htmlspecialchars($reasons) . '<br>';
                                    $formatted_recall_reasons .= 'Timestamp: ' . $timestamp->format('d F, Y h:i A') . '<br><br>';
                                }
                            }

                            // Format the created date
                            $created_date = new DateTime($row['created_date']);
                            $created_date->setTimezone(new DateTimeZone('Asia/Dhaka'));
                            $formatted_date = $created_date->format('d F, Y h:i A');

                            // Define notification methods
                            $notified_methods = ['Whatsapp', 'By Call', 'Message', 'Face-to-face conversations'];

                            // Form for updating data
                            echo '<form method="POST" action="edit/update_recall_status.php">';
                            echo '<input type="hidden" name="rowid" value="' . htmlspecialchars($row['rowid']) . '">';
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($row['lab_number']) . '</td>';
                            echo '<td>' . $formatted_recall_reasons . '</td>';
                            echo '<td>' . $formatted_date . '</td>';
                            echo '<td>' . htmlspecialchars($row['recalled_doctor']) . '</td>';

                            // Render notification methods with checkboxes
                            echo '<td>';
                            foreach ($notified_methods as $method) {
                                $checked = in_array($method, explode(', ', $row['notified_method'])) ? 'checked' : '';
                                echo '<label>';
                                echo '<input type="checkbox" name="notified_method[]" value="' . htmlspecialchars($method) . '" ' . $checked . '> ' . htmlspecialchars($method);
                                echo '</label><br>';
                            }
                            echo '</td>';

                            // Follow-up date input
                            echo '<td>';
                            echo '<input type="datetime-local" name="follow_up_date" value="' . htmlspecialchars($row['follow_up_date']) . '">';
                            echo '</td>';

                            echo '<input type="hidden" name="status" value="done">';
                            echo '<input type="hidden" name="notified_user" value="' . $loggedInUsername . '">';
                            echo '<td><button class="btn btn-primary" type="submit">Save</button></td>';
                            echo '</tr>';
                            echo '</form>';
                        }

                        echo '</tbody>';
                        echo '</table>';
                    } else {
                        echo "<p>No data available for the specified status.</p>";
                    }
                }
            ?>
        </div>


        <div id="Complete" class="tabcontent">
            <h3>Complete</h3>

            <!-- Search Input -->
            <input type="text" id="recallSearch" onkeyup="searchRecall()" placeholder="Search for values..." style="margin-bottom: 20px; width: 100%; padding: 10px; font-size: 16px; font-weight: bold;">

            <?php
                // Fetch data using the cyto_recall_status_done_list function
                $recall_data = cyto_recall_status_done_list();

                // Check for errors or empty data
                if (isset($recall_data['error'])) {
                    echo "<p>Error: " . $recall_data['error'] . "</p>";
                } elseif (isset($recall_data['message'])) {
                    echo "<p>" . $recall_data['message'] . "</p>";
                } else {
                    // Display data in a table if available
                    if (count($recall_data) > 0) {
                        echo '<table id="recallTable" class="table table-bordered table-striped">';
                        echo '<thead>';
                        echo '<tr>';
                        echo '<th>Lab Number</th>';
                        echo '<th>Recall Reason</th>';
                        echo '<th>Created Date</th>';
                        echo '<th>Doctor</th>';
                        echo '<th>Notified User</th>';
                        echo '<th>Notified Method</th>';
                        echo '<th>Follow-Up Date</th>';
                        echo '<th>Status</th>';
                        echo '</tr>';
                        echo '</thead>';
                        echo '<tbody>';

                        // Loop through each record and create a table row
                        foreach ($recall_data as $row) {
                            // Decode the JSON data for recall reasons (dynamic keys)
                            $recall_data_json = json_decode($row['recall_reason'], true);

                            // Prepare the output for each person dynamically
                            $formatted_recall_reasons = '';
                            foreach ($recall_data_json as $person => $records) {
                                $formatted_recall_reasons .= '<strong style="font-size: 18px;">' . htmlspecialchars($person) . '</strong><br>';
                                foreach ($records as $record) {
                                    $reasons = isset($record['reason']) ? implode(", ", $record['reason']) : '';
                                    $timestamp = new DateTime($record['timestamp']);
                                    $timestamp->setTimezone(new DateTimeZone('Asia/Dhaka'));
                                    $formatted_recall_reasons .= 'Reasons: ' . htmlspecialchars($reasons) . '<br>';
                                    $formatted_recall_reasons .= 'Timestamp: ' . $timestamp->format('d F, Y h:i A') . '<br><br>';
                                }
                            }

                            // Format the created date
                            $created_date = new DateTime($row['created_date']);
                            $created_date->setTimezone(new DateTimeZone('Asia/Dhaka'));
                            $formatted_date = $created_date->format('d F, Y h:i A');

                            // Format notified methods
                            $notified_methods = $row['notified_method'] ? implode(", ", explode(", ", $row['notified_method'])) : '';

                            // Format follow-up date
                            $follow_up_date = $row['follow_up_date'] ? (new DateTime($row['follow_up_date']))->setTimezone(new DateTimeZone('Asia/Dhaka'))->format('d F, Y h:i A') : '';

                            // Display each row
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($row['lab_number']) . '</td>';
                            echo '<td>' . $formatted_recall_reasons . '</td>';
                            echo '<td>' . $formatted_date . '</td>';
                            echo '<td>' . htmlspecialchars($row['recalled_doctor']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['notified_user']) . '</td>';
                            echo '<td>' . $notified_methods . '</td>';
                            echo '<td>' . $follow_up_date . '</td>';
                            echo '<td>' . htmlspecialchars($row['status']) . '</td>';
                            echo '</tr>';
                        }

                        echo '</tbody>';
                        echo '</table>';
                    } else {
                        echo "<p>No data available for the specified status.</p>";
                    }
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
    function searchRecall() {
        // Get the input field and filter value
        let input = document.getElementById('recallSearch');
        let filter = input.value.toLowerCase();
        let table = document.getElementById('recallTable');
        let rows = table.getElementsByTagName('tr');

        // Loop through all table rows and hide those that don't match the search query
        for (let i = 1; i < rows.length; i++) { // Start at 1 to skip the header row
            let row = rows[i];
            let textContent = row.textContent || row.innerText;
            row.style.display = textContent.toLowerCase().includes(filter) ? '' : 'none';
        }
    }
</script>

</body>
</html>

