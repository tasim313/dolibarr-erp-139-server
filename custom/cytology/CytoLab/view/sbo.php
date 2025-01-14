<?php
include("../connection.php");
include('../../../grossmodule/gross_common_function.php');
include('../../../transcription/common_function.php');
include('../../../transcription/FNA/function.php');
include('../../../histolab/histo_common_function.php');
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
                <li><a href="./slide_prepared.php" class="tab">Slide Prepared</a></li>
                <li><a href="./new_slide_centrifuge.php" class="tab">New Slide (Centrifuge)</a></li>
                <li class="active"><a href="./sbo.php">SBO(Slide Block Order)</a></li>
                <li><a href="../recall.php">Re-Call</a></li>
                <li class="active"><a href="./doctor_instruction.php">Doctor's Instructions</a></li>
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

            <div id="List" class="tabcontent active">
                <?php $sbo_list = get_slide_block_order_list(); 
                    echo '<!-- Sub-tab contents -->
                    <div id="SBOList" class="subtabcontent">';
                
                    if (!empty($sbo_list)) {
                    // Add form to submit selected statuses 
                    echo '<form action="../insert/sbo_status.php" method="POST">
                        <input type="hidden" name="loggedInUserId" value="' . htmlspecialchars($loggedInUserId) . '">
                         <table class="table table-bordered table-striped">
                         <thead>
                        <tr>
                            <th>SBO Lab Number</th>
                            <th>Original Lab Number and Descriptions</th>
                            <th>Delivery Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>';
                
                    foreach ($sbo_list as $order) {
                        // Create a DateTime object and set the timezone
                        $dateLivraison = new DateTime($order["date_livraison"]);
                        $dateLivraison->setTimezone(new DateTimeZone('Asia/Dhaka'));
                    echo '<tr>
                            <td>' . htmlspecialchars($order["ref"]) . '</td>
                            <td>' . htmlspecialchars($order["description"]) . '</td> 
                            <td>' . $dateLivraison->format('j F, Y g:i a') . '</td>
                            <td>
                                <select name="status[' . htmlspecialchars($order["ref"]) . ']">
                                    <option value=""></option>
                                    <option value="51">SBO Ready</option>
                                </select>
                            </td>
                         </tr>';
                    }
                
                    echo ' </tbody>
                    </table>
                    <div style="display: flex; justify-content: flex-end;">
                    <input type="submit" value="Submit" class="btn btn-primary" style="margin-bottom: 10px;">
                    </div>
                    </form>';
                    } else {
                    echo '<p>No Slide Block Orders available.</p>';
                    }
                    echo '</div>';
                
                ?>
               
            </div>


            <div id="Complete" class="tabcontent">
                <?php 
                    $sbo_complete_list = get_slide_block_order_ready_list();
                    echo '
                    <div id="SBOCompleted" class="subtabcontent">';
                    if (!empty($sbo_complete_list)) {
                    // Add form to submit selected statuses
                    echo '
                    <table class="table table-bordered table-striped">
                    <thead>
                    <tr>
                    <th>SBO Lab Number</th>
                    <th>Status</th>
                    <th>User Name</th>
                    <th>Date</th>
                    </tr>
                    </thead>
                    <tbody>';
                
                    foreach ($sbo_complete_list as $order) {
                    // Create a DateTime object and set the timezone
                    $dateCreateTime = new DateTime($order["create_time"]);
                    $dateCreateTime->setTimezone(new DateTimeZone('Asia/Dhaka'));
                    echo '<tr>
                    <td>' . htmlspecialchars($order["labno"]) . '</td>
                    <td>' . htmlspecialchars($order["status_name"]) . '</td> 
                    <td>' . htmlspecialchars($order["user_name"]) . '</td> 
                    <td>' . $dateCreateTime->format('j F, Y g:i a') . '</td>
                    </tr>';
                    }
                
                    echo ' </tbody>
                    </table>
                    ';
                    } else {
                    echo '<p>No Slide Block Orders available.</p>';
                    }
                    echo '</div>'; // Close complete List of SBO subtab content
                
                    echo '</div>'; // Close tabcontent
                echo  '</div>';
                
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