<?php 
include("function.php");

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
$langs->loadLangs(array("CytoInformation"));

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

llxHeader("", $langs->trans("CytoInformationArea"));

print load_fiche_titre($langs->trans(""), '', 'cytology.png@cytology');

$loggedInUsername = $user->login;
$loggedInUserId = $user->id;

$host = $_SERVER['HTTP_HOST'];
$homeUrl = "http://" . $host . "/custom/cytology/cytologyindex.php";

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
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
          <h3>Cyto Pathology</h3>
          <ul class="nav nav-tabs">
               <li><a href="index.php">Home</a></li>
               <li class="active"><a href="study.php">Study/History</a></li>
               <li><a href="recall.php">Recall Instructions</a></li>
               <li><a href="patient_report.php">Patient Report</a></li>
               <li><a href="report_ready.php">Report Ready</a></li>
               
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
                        $doctor_study_info = cyto_study_patient_info();

                        // Check for errors or empty data
                        if (isset($doctor_study_info['error'])) {
                            echo "<p>Error: " . $doctor_study_info['error'] . "</p>";
                        } elseif (isset($doctor_study_info['message'])) {
                            echo "<p>" . $doctor_study_info['message'] . "</p>";
                        } else {
                                if (count($doctor_study_info) > 0) {
                                    echo("<h3 style='color:red;'>Study / Patient History</h3><br>");
                                    echo '<table class="table table-bordered table-striped">';
                                    echo '<thead>';
                                    echo '<tr>';
                                    echo '<th>Screening Study</th>';
                                    echo '<th>Screening Patient History</th>';
                                    echo '<th>Finalization Study</th>';
                                    echo '<th>Finalization Patient History</th>';
                                    echo '<th>Comment</th>';
                                    echo '<th>Status</th>';
                                    echo '</tr>';
                                    echo '</thead>';
                                    echo '<tbody>';
                                    foreach ($doctor_study_info as $row) {
                                        // Decode JSON fields
                                        $screening_study_data = json_decode($row['screening_study_count_data'], true);
                                        $screening_patient_history_data = json_decode($row['screening_patient_history'], true);
                                        $finalization_study_data = json_decode($row['finalization_study_count_data'], true);
                                        $finalization_patient_history_data = json_decode($row['finalization_patient_history'], true);
                                        // Format the data
                                        $formatted_screening_study = formatSimpleData($screening_study_data);
                                        $formatted_screening_history = formatNestedArrayData($screening_patient_history_data);
                                        $formatted_finalization_study = formatSimpleData($finalization_study_data);
                                        $formatted_finalization_history = formatComplexData($finalization_patient_history_data);
                                        // Display the formatted data in the table
                                        echo '<tr>';
                                        echo '<td>' . $formatted_screening_study . '</td>';
                                        echo '<td>' . $formatted_screening_history . '</td>';
                                        echo '<td>' . $formatted_finalization_study . '</td>';
                                        echo '<td>' . $formatted_finalization_history . '</td>';
                                        echo '<td>';
                                        echo '<form action="insert/comment.php" method="POST" style="display:inline;">';
                                        echo '<input type="hidden" name="lab_number" value="' . htmlspecialchars($row['lab_number']) . '">';
                                        echo '<input type="hidden" name="username" value="' . htmlspecialchars($loggedInUsername) . '">';
                                        echo '<input type="hidden" name="rowid" value="' . htmlspecialchars($row['id']) . '">';
                                        echo '<textarea name="comment" placeholder="Add comment" rows="3"></textarea><br>';
                                        echo '</td>';
                                        echo '<td>';
                                        echo '<button type="submit" name="action" value="complete" class="btn btn-primary">Complete</button>';
                                        echo '</form>';
                                        echo '</td>';
                                        echo '</tr>';
                                    }
                                    echo '</tbody>';
                                    echo '</table>';
                                } else {
                                    echo "";
                                }
                            }
                            // Function to format simple timestamp arrays
                            function formatSimpleData($data) {
                                if (!is_array($data)) return '';
                                $output = '';
                                foreach ($data as $person => $timestamps) {
                                    $output .= '<strong>' . htmlspecialchars($person) . '</strong><br>';
                                    foreach ($timestamps as $timestamp) {
                                        $output .= 'Timestamp: ' . formatTimestamp($timestamp) . '<br>';
                                    }
                                }
                                return $output;
                            }

                            // Function to format nested array data (e.g., screening patient history)
                            function formatNestedArrayData($data) {
                                if (!is_array($data)) return '';
                                $output = '';
                                foreach ($data as $person => $entries) {
                                    $output .= '<strong>' . htmlspecialchars($person) . '</strong><br>';
                                    foreach ($entries as $entry) {
                                        if (is_array($entry)) {
                                            $history = implode(", ", array_slice($entry, 0, -1));
                                            $timestamp = formatTimestamp(end($entry));
                                            $output .= 'History: ' . htmlspecialchars($history) . '<br>';
                                            $output .= 'Timestamp: ' . $timestamp . '<br>';
                                        }
                                    }
                                }
                                return $output;
                            }

                            // Function to format complex objects (e.g., finalization patient history)
                            function formatComplexData($data) {
                                if (!is_array($data)) return '';
                                $output = '';
                                foreach ($data as $person => $records) {
                                    $output .= '<strong>' . htmlspecialchars($person) . '</strong><br>';
                                    foreach ($records as $record) {
                                        $history = isset($record['history']) ? implode(", ", $record['history']) : 'No history available';
                                        $timestamp = isset($record['timestamp']) ? formatTimestamp($record['timestamp']) : 'No timestamp available';
                                        $output .= 'History: ' . htmlspecialchars($history) . '<br>';
                                        $output .= 'Timestamp: ' . htmlspecialchars($timestamp) . '<br>';
                                    }
                                }
                                return $output;
                            }

                            // Function to format timestamps to Asia/Dhaka timezone
                            function formatTimestamp($timestamp) {
                                try {
                                    $date = new DateTime($timestamp, new DateTimeZone('UTC'));
                                    $date->setTimezone(new DateTimeZone('Asia/Dhaka'));
                                    return $date->format('d F, Y h:i A');
                                } catch (Exception $e) {
                                    return 'Invalid timestamp';
                                }
                            }
                    ?>
                </div>

                <div id="Complete" class="tabcontent active">
                    <?php

                        // Fetch the data
                        $studyData = cyto_study_patient_info_complete();
                        function format_patient_history($jsonData) {
                            $decodedData = json_decode($jsonData, true);

                            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decodedData)) {
                                return "N/A"; // Invalid JSON or empty data
                            }

                            $output = "";
                            foreach ($decodedData as $key => $entries) {
                                $output .= "<strong>" . htmlspecialchars($key) . ":</strong><br>"; // Display key (e.g., "tasim")

                                foreach ($entries as $entry) {
                                    if (is_array($entry)) {
                                        foreach ($entry as $item) {
                                            if (is_array($item)) {
                                                // Handle objects inside array (e.g., {"other": "test"})
                                                foreach ($item as $subKey => $subValue) {
                                                    $output .= "<em>" . htmlspecialchars($subKey) . ":</em> " . htmlspecialchars($subValue) . "<br>";
                                                }
                                            } else {
                                                $output .= htmlspecialchars($item) . "<br>";
                                            }
                                        }
                                    } else {
                                        $output .= htmlspecialchars($entry) . "<br>";
                                    }
                                }
                                $output .= "<hr>"; // Separate different keys if multiple exist
                            }

                            return $output;
                        }

                        if (isset($studyData['error'])) {
                            echo "<p>Error: " . htmlspecialchars($studyData['error']) . "</p>";
                        } elseif (empty($studyData)) {
                            echo "<p>No data available.</p>";
                        } else {
                            echo "<table class=\"table table-bordered table-striped\" width='100%'>";
                            echo "<thead>
                                    <tr>
                                        <th>Lab Number</th>
                                        <th>Screening Patient History</th>
                                        
                                        <th>Finalization Patient History</th>
                                        
                                    
                                        <th>Status</th>
                                        <th>Comments</th>
                                        <th>Status List</th>
                                    </tr>
                                </thead>
                                <tbody>";

                            foreach ($studyData as $row) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['lab_number']) . "</td>";
                            
                                echo "<td>" . format_patient_history($row['screening_patient_history']) . "</td>";
                            
                            
                                
                                echo "<td>" . format_patient_history($row['finalization_patient_history']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['status']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['comment']) . "</td>";

                                // Decode JSON columns safely
                                $statusList = json_decode($row['status_list'], true);
                                if (json_last_error() === JSON_ERROR_NONE && is_array($statusList)) {
                                    echo "<td>";
                                    foreach ($statusList as $status) {
                                        echo "<strong>" . htmlspecialchars($status['status']) . "</strong> - ";
                                        echo "<em>" . htmlspecialchars($status['timestamp']) . "</em> (by " . htmlspecialchars($status['user']) . ")<br>";
                                    }
                                    echo "</td>";
                                } else {
                                    echo "<td>N/A</td>";
                                }

                                echo "</tr>";
                            }

                            echo "</tbody></table>";
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

<?php 
    $NBMAX = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;
    $max = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;


    print '</div></div>';

    // End of page
    llxFooter();
    $db->close();
?>