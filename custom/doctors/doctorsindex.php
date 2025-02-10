<?php

// database connection and function file
include('connection.php');
include('../transcription/common_function.php');
include('../cytology/common_function.php');
include('../grossmodule/gross_common_function.php');
include('list_of_function.php');

// Load Dolibarr environment
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
$langs->loadLangs(array("doctors@doctors"));

$action = GETPOST('action', 'aZ09');


// Security check
// if (! $user->rights->doctors->myobject->read) {
// 	accessforbidden();
// }
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

$max = 5;
$now = dol_now();


/*
 * Actions
 */

// None

/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

llxHeader("", $langs->trans("DoctorsArea"));

// print load_fiche_titre($langs->trans("DoctorsArea"), '', 'doctors.png@doctors');


$LabNumber = $_GET['labno'];
$lab_status = get_lab_number_status_for_doctor_tracking_by_lab_number($LabNumber);
$labStatus = json_encode($lab_status);

$loggedInUserId = $user->id;
$loggedInUsername = $user->login;

$userGroupNames = getUserGroupNames($loggedInUserId);

$hasConsultants = false;



foreach ($userGroupNames as $group) {
    if ($group['group'] === 'Consultants') {
        $hasConsultants = true;
    } 
}

// Access control using switch statement
switch (true) {
	case $hasConsultants:
		// Doctor has access, continue with the page content...
		break;
	default:
		echo "<h1>Access Denied</h1>";
		echo "<p>You are not authorized to view this page.</p>";
		exit; // Terminate script execution
}

// <i class="fa fa-scissors" ></i>

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="bootstrap-4.4.1-dist/css/bootstrap.min.css">
    <script src="bootstrap-4.4.1-dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" type="text/css" href="trackwsfiles/css.css"  />
    <style>
        body {
            font-family: Verdana;
        }

        .today {
            color: red;
        }

        .tomorrow {
            color: yellow;
        }

        .flex-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between; /* Distribute space between items */
        }

        .flex-container > div {
            margin: 10px;
            padding: 20px;
            font-size: 10px;
            flex: 1; /* Make divs flexible */
        }

        .tab-content { 
            display: block; 
        }

        .tab-content.grayed-out { 
            opacity: 0.5; 
            pointer-events: none; 
        }

        .semi-bold { 
            font-weight: 300; 
        }

        .red { 
            color: red; 
        }

        .tab-buttons {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            position: relative;
        }

        .tab-buttons button { 
            margin-right: 10px; 
        }

        .tab-buttons button.inactive { 
            opacity: 0.5; 
        }

        .tab-buttons button.active { 
            font-weight: bold; 
        }

        .hidden { 
            display: none; 
        }

        .tab {
            overflow: hidden;
        }

        /* Style the buttons inside the tab */
        .tab button {
            background-color: inherit;
            font-size: 15px;
            border: none;
            cursor: pointer;
            outline: none;
            float: left;
        }

        /* Create an active/current tablink class */
        .tab button.active {
            background-color: #ccc;
        }

        /* Style the tab content */
        .tabcontent_1 {
            display: none;
            padding: 6px 12px;
            -webkit-animation: fadeEffect 1s;
            animation: fadeEffect 1s;
        }

        /* Button container styles */
        .button-container {
            display: flex;
            justify-content: space-between;
            width: 100%;
        }

        .button-container li {
            margin: 0 -250px;
        }

        .button-container li:first-child,
        .button-container li:last-child {
            flex: 0 0 auto; /* Prevent stretching */
        }

        .button-container li:nth-child(2) {
            flex: 10; /* Make the second button flexible */
        }

        .btn-group button {
            /* cursor: pointer; */
            float: left; /* Float the buttons side by side */
        }

        /* Clear floats (clearfix hack) */
        .btn-group:after {
            content: "";
            clear: both;
            display: table;
        }

        .btn-group button:not(:last-child) {
            border-right: none; /* Prevent double borders */
        }

        /* Set font size for h2 tags */
        .h2 {
            font-size: 20px;
        }

        .h3 {
            font-size: 15px;
        }

        /* Fade in tabs */
        @-webkit-keyframes fadeEffect {
            from {opacity: 0;}
            to {opacity: 1;}
        }

        @keyframes fadeEffect {
            from {opacity: 0;}
            to {opacity: 1;}
        }

        /* Responsive Styles */

        /* Extra Large Screens (Large Monitors) */
        @media only screen and (min-width: 1200px) {
            .flex-container > div {
                font-size: 14px;
            }

            .tab-buttons.button-container {
                justify-content: space-around;
            }
        }

        /* Large Screens (Desktops) */
        @media only screen and (min-width: 992px) and (max-width: 1199px) {
            .flex-container > div {
                font-size: 12px;
            }

            .tab-buttons.button-container {
                justify-content: space-around;
            }

            
        }

        /* Medium Screens (Tablets in Landscape Mode) */
        @media only screen and (min-width: 768px) and (max-width: 991px) {
            .flex-container > div {
                font-size: 10px;
            }

            .tab-buttons.button-container {
                justify-content: space-between;
            }

            .tab-buttons.button-container li {
                flex: 1 1 30%;
                text-align: center;
            }

            .tab-buttons.button-container button {
                font-size: 18px;
            }
        }

        @media only screen and (min-width: 824px) and (max-width: 1022px) {
            .flex-container {
                flex-direction: row; /* Ensure row layout for the flex container */
                justify-content: space-around; /* Adjust the space around the items */
            }

            .flex-container > div {
                font-size: 12px; /* Adjust font size for better readability */
                padding: 15px; /* Adjust padding */
            }

            .button-container {
                flex-direction: row; /* Ensure row layout for the button container */
                justify-content: space-between; /* Space between the buttons */
            }

            .button-container li {
                flex: 1 1 30%; /* Distribute space equally */
                text-align: center; /* Center-align the text */
                margin: 10px 0; /* Adjust margin for better spacing */
            }

            .button-container button {
                font-size: 18px; /* Adjust button font size */
                width: auto; /* Let the width be determined by content */
                text-align: center; /* Center-align text within buttons */
            }

            .tab-buttons {
                flex-direction: row; /* Ensure row layout for the tab buttons */
                align-items: center; /* Center-align items vertically */
            }

            .tab-buttons button {
                width: auto; /* Let the width be determined by content */
                margin: 5px; /* Margin between buttons */
            }
        }

        /* Small Screens (Tablets in Portrait Mode) */
        @media only screen and (min-width: 600px) and (max-width: 767px) {
            .flex-container > div {
                font-size: 8px;
                padding: 15px;
            }

            .button-container {
                flex-direction: column;
                align-items: flex-start;
            }

            .tab-buttons.button-container {
                flex-direction: column;
                align-items: center;
            }

            .tab-buttons.button-container li {
                width: 100%;
                margin: 5px 0;
            }

            .tab-buttons.button-container button {
                font-size: 18px;
                width: 100%;
                text-align: center;
            }
        }

        /* Mobile Phones */
        @media only screen and (max-width: 599px) {
            .flex-container {
                flex-direction: column;
                align-items: flex-start;
            }

            .flex-container > div {
                font-size: 6px;
                padding: 10px;
                width: 100%;
            }

            .tab-buttons {
                flex-direction: column;
                align-items: flex-start;
            }

            .tab button {
                width: 100%;
                text-align: left;
            }
            .tab-buttons.button-container {
                flex-direction: column;
                align-items: center;
            }

            .tab-buttons.button-container li {
                width: 100%;
                margin: 5px 0;
            }

            .tab-buttons.button-container button {
                font-size: 16px;
                width: 100%;
                text-align: center;
            }
        }

    </style>
</head>
<body>

    <a href="doctorsindex.php">
    <button style="border:none; background-color: white; color: black;">
        <i class="fas fa-home" aria-hidden="true"></i> Doctors
    </button></a>
    &nbsp; &nbsp; &nbsp; &nbsp; 
    <form name="readlabno" id="readlabno" action="">
              <label for="labno">Lab No:</label>
              <input type="text" id="labno" name="labno" autofocus>
    </form>

    <ul class="nav nav-tabs process-model more-icon-preocess" role="tablist">
                <div class="tab-buttons button-container">
                    
                        <li role="presentation">
                            <span style="color: red;">
                                <button style="border:none; font-size: 20px;" id="tab-screening" class="inactive" onclick="showTab('screening')">
                                    <i class="fas fa-microscope" aria-hidden="true"></i>Screening</button>
                            </span>
                        </li>
                        <li role="presentation">
                            <span style="color:green">
                                <button style="border:none; font-size: 20px;" id="tab-final-screening" class="inactive" onclick="showTab('final-screening')">
                                <i class="fas fa-microscope" aria-hidden="true"></i>Finalization</button>
                            </span>
                        </li>
                        <li role="presentation">
                            <span style="color:blue">
                                <button style="border:none; font-size: 20px;" id="tab-status" class="inactive" onclick="toggleStatusTab(), showTab('status')">
                                <i class="fa fa-search" aria-hidden="true"></i>Status</button>
                            </span>
                        </li> 
                    
                </div>
    </ul>

    <div class="flex-container">

        <div id="screening" class="tab-content tab btn-group grayed-out">
            <center><h6>Screening</h6></center>
            <ul class="nav nav-tabs process-model more-icon-preocess" role="tablist">
                <!-- <li role="presentation" class=""> 
                        <button style="display:hidden"  onclick="openTab(event, 'Screening-Start')">
                        <i class="fas fa-play"></i>Start</button>
                </li> -->
                <li role="presentation" class="">
                        <button  onclick="openTab(event, 'Screening-Study')">
                        <i class="fas fa-book"></i>Study / History</button>
                </li><li role="presentation" class="">
                        <button  onclick="openTab(event, 'Screening-LabInstructions')">
                        <i class="fas fa-flask"></i> Lab Related Instructions</button>
                </li><li role="presentation" class="">
                        <button  onclick="openTab(event, 'Screening-GrossInstructions')">
                        <i class="fas fa-cut"></i>Gross Related Instructions</button>
                </li>
                <li role="presentation" class="">
                        <button id="screening_done"  onclick="openTab(event, 'Screening-Done')">
                        <i class="fas fa-check"></i>Screening Done</button>
                </li>
            </ul>
        </div>

        <div id="final-screening" class="tab-content tab btn-group grayed-out">
            <center><h6>Final Screening</h6></center>
            <ul class="nav nav-tabs process-model more-icon-preocess" role="tablist">
            <!-- <li role="presentation" class="">
                <button onclick="openTab(event, 'Final-Screening-Start')">
                <i class="fas fa-play"></i> Start</button>
            </li> -->
            <li role="presentation" class="">
                <button onclick="openTab(event, 'Final-Screening-Study')">
                <i class="fas fa-book"></i>Study / History</button>
            </li>
            <li role="presentation" class="">
                <button onclick="openTab(event, 'Final-Screening-LabInstructions')">
                <i class="fas fa-flask"></i>Lab Related Instructions</button> 
            </li>
            <li role="presentation" class="">
                <button onclick="openTab(event, 'Final-Screening-GrossInstructions')">
                <i class="fas fa-cut"></i>Gross Related Instructions</button>
            </li>
            <li role="presentation" class="">
                <button id='Final_Screening_Done' onclick="openTab(event, 'Final-Screening-Done')">
                <i class="fas fa-check"></i>Finalization Done</button>
            </li>
            </ul>
        </div>

        <div id="status" class="tab-content grayed-out">
            <h3 class="semi-bold">
                <center>Current  Status: <?php echo htmlspecialchars($LabNumber); ?></center>
            </h3>
            <table border="0">
                <thead>
                    <tr>
                        <!-- <th>Section</th> -->
                        <th>Status</th>
                        <th>Descriptions</th>
                        <th>Time</th>
                        <th>User</th>
                    </tr>
                </thead>
                <tbody id="status-table-body">
                <?php 
                    $statusValues = array_column($lab_status, 'WSStatusName');
                    $sortedRows = [];

                    foreach ($lab_status as $list) {
                        $statusColor = '';
                        if (in_array($list['section'], ['Gross', 'Lab', 'Microscopy', 'Screening', 'description'])) {

                            // Check if statusName is 'Diagnosis Completed' and skip this row
                            if ($list['WSStatusName'] === 'Diagnosis Completed') {
                                continue;
                            }
                            

                            if ($list['WSStatusName'] == 'Special Stain others requested' && !in_array('Special Stain others Completed', $statusValues)) {
                                $statusColor = 'red';
                            } elseif ($list['WSStatusName'] == 'IHC-Block-Markers-requested' && !in_array('IHC-Block-Markers-completed', $statusValues)) {
                                $statusColor = 'red';
                            } elseif ($list['WSStatusName'] == 'R/C requested' && !in_array('R/C Completed', $statusValues)) {
                                $statusColor = 'red';
                            } elseif ($list['WSStatusName'] == 'M/R/C requested' && !in_array('M/R/C Completed', $statusValues)) {
                                $statusColor = 'red';
                            } elseif ($list['WSStatusName'] == 'Deeper Cut requested' && !in_array('Deeper Cut Completed', $statusValues)) {
                                $statusColor = 'red';
                            } elseif ($list['WSStatusName'] == 'Serial Sections requested' && !in_array('Serial Sections Completed', $statusValues)) {
                                $statusColor = 'red';
                            } elseif ($list['WSStatusName'] == 'Block D/C & R/C requested' && !in_array('Block D/C & R/C Completed', $statusValues)) {
                                $statusColor = 'red';
                            } elseif ($list['WSStatusName'] == 'Special Stain AFB requested' && !in_array('Special Stain AFB Completed', $statusValues)) {
                                $statusColor = 'red';
                            } elseif ($list['WSStatusName'] == 'Special Stain GMS requested' && !in_array('Special Stain GMS Completed', $statusValues)) {
                                $statusColor = 'red';
                            } elseif ($list['WSStatusName'] == 'Special Stain PAS requested' && !in_array('Special Stain PAS Completed', $statusValues)) {
                                $statusColor = 'red';
                            } elseif ($list['WSStatusName'] == 'Special Stain PAS with Diastase requested' && !in_array('Special Stain PAS with Diastase Completed', $statusValues)) {
                                $statusColor = 'red';
                            } elseif ($list['WSStatusName'] == 'Special Stain Fite Faraco requested' && !in_array('Special Stain Fite Faraco Completed', $statusValues)) {
                                $statusColor = 'red';
                            } elseif ($list['WSStatusName'] == 'Special Stain Brown-Brenn requested' && !in_array('Special Stain Brown-Brenn Completed', $statusValues)) {
                                $statusColor = 'red';
                            } elseif ($list['WSStatusName'] == 'Special Stain Congo-Red requested' && !in_array('Special Stain Congo-Red Completed', $statusValues)) {
                                $statusColor = 'red';
                            } elseif ($list['WSStatusName'] == 'Special Stain Bone Decalcification requested' && !in_array('Special Stain Bone Decalcification Completed', $statusValues)) {
                                $statusColor = 'red';
                            } elseif ($list['WSStatusName'] == 'Re-gross Requested' && !in_array('Regross Completed', $statusValues)) {
                                $statusColor = 'red';
                            } elseif (in_array($list['WSStatusName'], [
                                'Special Stain others Completed',
                                'IHC-Block-Markers-completed',
                                'R/C Completed',
                                'M/R/C Completed',
                                'Deeper Cut Completed',
                                'Serial Sections Completed',
                                'Block D/C & R/C Completed',
                                'Special Stain AFB Completed',
                                'Special Stain GMS Completed',
                                'Special Stain PAS Completed',
                                'Special Stain PAS with Diastase Completed',
                                'Special Stain Fite Faraco Completed',
                                'Special Stain Brown-Brenn Completed',
                                'Special Stain Congo-Red Completed',
                                'Special Stain Bone Decalcification Completed',
                                'Regross Completed'
                            ])) {
                                $statusColor = 'green';
                            }

                            $dateTime = DateTime::createFromFormat('Y-m-d H:i:s.uP', $list['TrackCreateTime']);
                            if ($dateTime === false) {
                                $trackCreateTimeFormatted = 'Invalid date';
                            } else {
                                $trackCreateTimeFormatted = $dateTime->format('F j, Y h:i A');
                            }

                            $sortedRows[] = [
                                // 'section' => htmlspecialchars($list['section']),
                                'statusName' => htmlspecialchars($list['WSStatusName']),
                                'description' => htmlspecialchars($list['description']),
                                'color' => $statusColor,
                                'trackCreateTime' => $trackCreateTimeFormatted,
                                'user' => htmlspecialchars($list['TrackUserName'])
                            ];
                        }
                    }

                    usort($sortedRows, function($a, $b) {
                        $colorOrder = ['red', 'green', ''];
                        $aColorIndex = array_search($a['color'], $colorOrder);
                        $bColorIndex = array_search($b['color'], $colorOrder);
                        return $aColorIndex - $bColorIndex;
                    });

                    $isGrayedOut = true; // Set this based on actual tab state
                    $displayCount = 2;

                    foreach ($sortedRows as $index => $row) {
                        // $section = htmlspecialchars($row['section']);
                        $statusName = htmlspecialchars($row['statusName']);
                        $description = htmlspecialchars($row['description']);
                        $statusColor = htmlspecialchars($row['color']);
                        $trackCreateTime = htmlspecialchars($row['trackCreateTime']);
                        $user = htmlspecialchars($row['user']);

                        $rowClass = '';
                        if ($isGrayedOut) {
                            if ($index < count($sortedRows) - 2) {
                                $rowClass = 'hidden';
                            }
                        }

                        echo "<tr class='status-row {$rowClass}'>";
                        // echo "<td><p style='font-size: 15px;'>{$section}</p></td>";
                        echo "<td><p  style='font-size: 15px; color: {$statusColor};'>{$statusName}</p></td>";
                        echo "<td><p  style='font-size: 15px;'>{$description}</p></td>";
                        echo "<td><p style='font-size: 15px;'>{$trackCreateTime}</p></td>";
                        echo "<td><p style='font-size: 15px;'>{$user}</p></td>";
                        echo "</tr>";
                    }
                ?>
                </tbody>
            </table>
        </div>

        <div id="error" style="display:none;color:red;">
            Wrong lab number. Please enter the correct one.
        </div>

    
    </div>

    <div id="Screening-Start" class="tabcontent_1">
        <p>Screening Start</p>
    </div>

    <div id="Screening-Study" class="tabcontent_1">
        <div class="modal-content">
           
            <div class="design-process-content" id="lookuplabno4">
                <button id="history-button" type="button" class="btn btn-primary" onclick="logHistoryValue(event)">Save</button>
                <br><br>
                <h4>
                    <div class="form-waiting">
                        <input class="form-waiting-input" type="checkbox" name="flexRadioWaiting" id="status5" value="5">
                        <label class="form-waiting-label" for="status5">
                            <h2 class="h2">Study</h2>
                        </label>     
                    </div>
                    <div class="form-waiting">
                        <input class="form-waiting-input" type="checkbox" name="flexRadioWaiting" id="status4" value="4" onclick="toggleOptions()">
                        <label class="form-waiting-label" for="status4">
                            <h2 class="h2">Patient History / Investigations</h2>
                        </label>
                    </div>
                </h4>
                <ul id="history_collect" style="display: none;">
                    <h3 class="h2">
                        <input class="form-waiting-input" type="checkbox" name="flexRadioWaiting" id="status4-Self" value="Self"> Self &nbsp;&nbsp;
                        <input class="form-waiting-input" type="checkbox" name="flexRadioWaiting" id="status4-Transcription" value="Transcription"> Transcription &nbsp;&nbsp;
                        <input class="form-waiting-input" type="checkbox" name="flexRadioWaiting" id="status4-It_Space" value="IT Space"> IT Space &nbsp;&nbsp;
                    </h3>
                </ul>
                <ul id="additional-options" style="display: none;">
                    <h3 class="h3">
                        <input class="form-waiting-input" type="checkbox" name="flexRadioWaiting" id="status4-Ct_Scan" value="CT Scan Report"> CT Scan Report<br>
                        <input class="form-waiting-input" type="checkbox" name="flexRadioWaiting" id="status4-Ct_Scan_film" value="CT Scan Film"> CT Scan Film<br>
                        <input class="form-waiting-input" type="checkbox" name="flexRadioWaiting" id="status4-Mri_Report" value="MRI Report"> MRI Report<br>
                        <input class="form-waiting-input" type="checkbox" name="flexRadioWaiting" id="status4-Mri_Film" value="MRI Film"> MRI Film<br>
                        <input class="form-waiting-input" type="checkbox" name="flexRadioWaiting" id="status4-others" value="" onclick="toggleTextarea()"> Others
                    </h3>
                </ul>
                <textarea id="others-textarea" style="display: none; margin-top: 10px;" placeholder="Please specify..."></textarea>
            </div>
        </div>
    </div>



    <div role="tabpanel" class="tabcontent_1" id="Screening-LabInstructions">
        <div class="design-process-content" id="lookuplabno1">
            <button id="lab-button" type="button" class="btn btn-primary" onclick="logLabInstructionsValue()">Save</button>
            <br><br>
            <ul id="screeningOptions">
                <h3 class="bold h3">Section Instructions</h3>
                <li>
                    <h3 class="h3"><label>
                        <input type="checkbox" id="option1" onclick="toggleVisibility('input1')" value="16"> R/C
                    </label></h3>
                    <div id="input1" class="hidden">
                        <h4 class="h3"><label>Section Code or Block Number</label>
                        <input id="lab_option_1" type="text" name="text"></h4>
                    </div>
                </li>
                <li>
                    <h3 class="h3"><label>
                        <input type="checkbox" id="option2" onclick="toggleVisibility('input2')" value="18"> M/R/C
                    </label></h3>
                    <div id="input2" class="hidden">
                        <h4 class="h3"><label>Section Code or Block Number</label>
                        <input id="lab_option_2" type="text" name="text"></h4>
                    </div>
                </li>
                <li>
                    <h3 class="h3"><label>
                        <input type="checkbox" id="option3" onclick="toggleVisibility('input3')" value="20"> Deeper Cut
                    </label></h3>
                    <div id="input3" class="hidden">
                        <h4 class="h3"><label>Section Code or Block Number</label>
                        <input id="lab_option_3" type="text" name="text"></h4>
                    </div>
                </li>
                <li>
                    <h3 class="h3"><label>
                        <input type="checkbox" id="option4" onclick="toggleVisibility('input4')" value="24"> Block D/C & R/C
                    </label></h3>
                    <div id="input4" class="hidden">
                        <h4 class="h3"><label>Section Code or Block Number</label>
                        <input id="lab_option_4" type="text" name="text"></h4>
                    </div>
                </li>
                <li>
                    <h3 class="h3"><label>
                        <input type="checkbox" id="option5" onclick="toggleVisibility('input5')" value="22"> Serial Sections
                    </label></h3>
                    <div id="input5" class="hidden">
                        <h4 class="h3"><label>Section Code or Block Number</label>
                        <input id="lab_option_5" type="text" name="text"></h4>
                    </div>
                </li>
            </ul>
            <br>
            <ul>
                <h3 class="h3"><b>Special&nbsp;&nbsp;Stain&nbsp;&nbsp;Instructions</b></h3>
                <li>
                    <h3 class="h3"><label>
                        <input type="checkbox" id="stain1" onclick="toggleVisibility('stainInput1')" value="26">  AFB
                    </label></h3>
                    <div id="stainInput1" class="hidden">
                        <h4 class="h3"><label>Section Code or Block Number</label>
                        <input id="lab_stain_1" type="text" name="text"></h4>
                    </div>
                </li>
                <li>
                    <h3 class="h3"><label>
                        <input type="checkbox" id="stain2" onclick="toggleVisibility('stainInput2')" value="28">  GMS
                    </label></h3>
                    <div id="stainInput2" class="hidden">
                        <h4 class="h3"><label>Section Code or Block Number</label>
                        <input id="lab_stain_2" type="text" name="text"></h4>
                    </div>
                </li>
                <li>
                    <h3 class="h3"><label>
                        <input type="checkbox" id="stain3" onclick="toggleVisibility('stainInput3')" value="30">  PAS
                    </label></h3>
                    <div id="stainInput3" class="hidden">
                        <h4 class="h3"><label>Section Code or Block Number</label>
                        <input id="lab_stain_3" type="text" name="text"></h4>
                    </div>
                </li>
                <li>
                    <h3 class="h3"><label>
                        <input type="checkbox" id="stain4" onclick="toggleVisibility('stainInput4')" value="32">  PAS with Diastase
                    </label></h3>
                    <div id="stainInput4" class="hidden">
                        <h4 class="h3"><label>Section Code or Block Number</label>
                        <input id="lab_stain_4" type="text" name="text"></h4>
                    </div>
                </li>
                <li>
                    <h3 class="h3"><label>
                        <input type="checkbox" id="stain5" onclick="toggleVisibility('stainInput5')" value="34">  Fite Faraco
                    </label></h3>
                    <div id="stainInput5" class="hidden">
                        <h4 class="h3"><label>Section Code or Block Number</label>
                        <input id="lab_stain_5" type="text" name="text"></h4>
                    </div>
                </li>
                <li>
                    <h3 class="h3"><label>
                        <input type="checkbox" id="stain6" onclick="toggleVisibility('stainInput6')" value="36">  Brown-Brenn
                    </label></h3>
                    <div id="stainInput6" class="hidden">
                        <h4 class="h3"><label>Section Code or Block Number</label>
                        <input id="lab_stain_6" type="text" name="text"></h4>
                    </div>
                </li>
                <li>
                    <h3 class="h3"><label>
                        <input type="checkbox" id="stain7" onclick="toggleVisibility('stainInput7')" value="38">  Congo-Red
                    </label></h3>
                    <div id="stainInput7" class="hidden">
                        <h4 class="h3"><label>Section Code or Block Number</label>
                        <input id="lab_stain_7" type="text" name="text"></h4>
                    </div>
                </li>
                <li>
                    <h3 class="h3"><label>
                        <input type="checkbox" id="stain8" onclick="toggleVisibility('stainInput8')" value="40">  others
                    </label></h3>
                    <div id="stainInput8" class="hidden">
                        <h4 class="h3"><label>Special Stain Name </label>
                        <input id="stain_name_input" type="text" name="text">
                        <label>Block Number</label>
                        <input id="lab_stain_8" type="text" name="text"></h4>
                    </div>
                </li>
            </ul> 
            <br>  
            <ul>
                <h3 class="h3"><b>Immunohistochemistry(IHC)&nbsp;&nbsp;Instructions</b></h3>
                <h3 class="h3"><label>
                    <input type="checkbox" id="stain14" onclick="toggleVisibility('stainInput14')" value="44"> Block Number
                </label>
                <div id="stainInput14" class="hidden">
                    <textarea id="lab_stain_14" type="text" name="text"></textarea>
                </div>
                <br><label>
                    <input type="checkbox" id="stain15" onclick="toggleVisibility('stainInput15')" value="44"> Markers
                </label>
                <div id="stainInput15" class="hidden">
                    <textarea id="lab_stain_15" type="text" name="text"></textarea>
                </div>
                </h3>
            </ul> 
        </div>
    </div>
    

    <div role="tabpanel" class="tabcontent_1" id="Screening-GrossInstructions">
        <div class="design-process-content">
        <button id="gross_related_instructions_screening" type="button" class="btn btn-primary">Save</button>
          <br><br>
          <h4 class="h3">
            <div class="form-addreq">
               <label>
                    <input type="checkbox" id="stain9" onclick="toggleVisibility('stainInput9')" value="6"> Gross Check & Re-gross for/of
              </label>
              <div id="stainInput9" class="hidden">
                  <textarea id="gross_check_re_gross_screening" type="text" name="text" rows="4" cols="50"></textarea>
              </div>
            </div>
          </h4>
        </div>
      </div>
     

    <div id="Screening-Done" class="tabcontent_1">
        <p>Screening Done</p>
    </div>  

    <div id="Final-Screening-Start" class="tabcontent_1">
        <p>Final Screening Start</p>
    </div>

    <div id="Final-Screening-Study" class="tabcontent_1">
        <div class="modal-content">
           <div class="design-process-content" id="lookuplabno4">
               <button id="final-history-button" type="button" class="btn btn-primary" onclick="logHistoryValueFinalScreening(event)">Save</button>
               <br><br>
               <h4>
                   <div class="form-waiting-final-screening">
                       <input class="form-waiting-input" type="checkbox" name="flexRadioWaiting" id="status5" value="5">
                       <label class="form-waiting-label" for="status5">
                           <h2 class="h2">Study</h2>
                       </label>     
                   </div>
                   <div class="form-waiting-final-screening">
                       <input class="form-waiting-input" type="checkbox" name="flexRadioWaiting" id="status4" value="4" onclick="toggleOptionsFinalScreening()">
                       <label class="form-waiting-label" for="status4">
                           <h2 class="h2">Patient History / Investigations</h2>
                       </label>
                   </div>
               </h4>
               <ul id="history_collect-final-screening" style="display: none;">
                   <h3 class="h2">
                       <input class="form-waiting-input" type="checkbox" name="flexRadioWaiting" id="status4-Self" value="Self"> Self &nbsp;&nbsp;
                       <input class="form-waiting-input" type="checkbox" name="flexRadioWaiting" id="status4-Transcription" value="Transcription"> Transcription &nbsp;&nbsp;
                       <input class="form-waiting-input" type="checkbox" name="flexRadioWaiting" id="status4-It_Space" value="IT Space"> IT Space &nbsp;&nbsp;
                   </h3>
               </ul>
               <ul id="additional-options-final-screening" style="display: none;">
                   <h3 class="h3">
                       <input class="form-waiting-input" type="checkbox" name="flexRadioWaiting" id="status4-Ct_Scan" value="CT Scan Report"> CT Scan Report<br>
                       <input class="form-waiting-input" type="checkbox" name="flexRadioWaiting" id="status4-Ct_Scan_film" value="CT Scan Film"> CT Scan Film<br>
                       <input class="form-waiting-input" type="checkbox" name="flexRadioWaiting" id="status4-Mri_Report" value="MRI Report"> MRI Report<br>
                       <input class="form-waiting-input" type="checkbox" name="flexRadioWaiting" id="status4-Mri_Film" value="MRI Film"> MRI Film<br>
                       <input class="form-waiting-input" type="checkbox" name="flexRadioWaiting" id="status4-others" value="" onclick="toggleTextareaFinalScreening()"> Others
                   </h3>
               </ul>
               <textarea id="others-textarea-final-screening" style="display: none; margin-top: 10px;" placeholder="Please specify..."></textarea>
           </div>
       </div>
       
    </div>

    <div role="tabpanel" class="tabcontent_1" id="Final-Screening-LabInstructions">
            <div class="design-process-content" id="finalScreeningLabContent">
                <button id="final-screening-save-button" type="button" class="btn btn-primary" onclick="logFinalScreeningLabInstructionsValue()">Save</button>
                <br><br>
                <ul id="finalScreeningOptions">
                    <h3 class="bold h3">Section Instructions</h3>
                    <li>
                        <h3 class="h3"><label>
                            <input type="checkbox" id="finalScreeningOption1" onclick="toggleVisibilityFinalScreening('finalScreeningInput1')" value="16"> R/C
                        </label></h3>
                        <div id="finalScreeningInput1" class="hidden">
                            <h4 class="h3"><label class="bold">Section Code or Block Number</label>
                            <input id="finalScreeningLabOption1" type="text" name="finalScreeningText1"></h4>
                        </div>
                    </li>
                    <li>
                        <h3 class="h3"><label>
                            <input type="checkbox" id="finalScreeningOption2" onclick="toggleVisibilityFinalScreening('finalScreeningInput2')" value="18"> M/R/C
                        </label></h3>
                        <div id="finalScreeningInput2" class="hidden">
                            <h4 class="h3"><label>Section Code or Block Number</label>
                            <input id="finalScreeningLabOption2" type="text" name="finalScreeningText2"></h4>
                        </div>
                    </li>
                    <li>
                        <h3 class="h3"><label>
                            <input type="checkbox" id="finalScreeningOption3" onclick="toggleVisibilityFinalScreening('finalScreeningInput3')" value="20"> Deeper Cut
                        </label></h3>
                        <div id="finalScreeningInput3" class="hidden">
                            <h4 class="h3"><label>Section Code or Block Number</label>
                            <input id="finalScreeningLabOption3" type="text" name="finalScreeningText3"></h4>
                        </div>
                    </li>
                    <li>
                        <h3 class="h3"><label>
                            <input type="checkbox" id="finalScreeningOption4" onclick="toggleVisibilityFinalScreening('finalScreeningInput4')" value="24"> Block D/C & R/C
                        </label></h3>
                        <div id="finalScreeningInput4" class="hidden">
                            <h4 class="h3"><label>Section Code or Block Number</label>
                            <input id="finalScreeningLabOption4" type="text" name="finalScreeningText4"></h4>
                        </div>
                    </li>
                    <li>
                        <h3 class="h3"><label>
                            <input type="checkbox" id="finalScreeningOption5" onclick="toggleVisibilityFinalScreening('finalScreeningInput5')" value="22"> Serial Sections
                        </label></h3>
                        <div id="finalScreeningInput5" class="hidden">
                            <h4 class="h3"><label>Section Code or Block Number</label>
                            <input id="finalScreeningLabOption5" type="text" name="finalScreeningText5"></h4>
                        </div>
                    </li>
                </ul>
                <br>
                <ul>
                    <h3 class="h3"><b>Special&nbsp;&nbsp;Stain&nbsp;&nbsp;Instructions</b></h3>
                    <li>
                        <h3 class="h3"><label>
                            <input type="checkbox" id="finalScreeningStain1" onclick="toggleVisibilityFinalScreening('finalScreeningStainInput1')" value="26"> AFB
                        </label></h3>
                        <div id="finalScreeningStainInput1" class="hidden">
                            <h4 class="h3"><label>Section Code or Block Number</label>
                            <input id="finalScreeningStainOption1" type="text" name="finalScreeningStainText1"></h4>
                        </div>
                    </li>
                    <li>
                        <h3 class="h3"><label>
                            <input type="checkbox" id="finalScreeningStain2" onclick="toggleVisibilityFinalScreening('finalScreeningStainInput2')" value="28"> GMS
                        </label></h3>
                        <div id="finalScreeningStainInput2" class="hidden">
                            <h4 class="h3"><label>Section Code or Block Number</label>
                            <input id="finalScreeningStainOption2" type="text" name="finalScreeningStainText2"></h4>
                        </div>
                    </li>
                    <li>
                        <h3 class="h3"><label>
                            <input type="checkbox" id="finalScreeningStain3" onclick="toggleVisibilityFinalScreening('finalScreeningStainInput3')" value="30"> PAS
                        </label></h3>
                        <div id="finalScreeningStainInput3" class="hidden">
                            <h4 class="h3"><label>Section Code or Block Number</label>
                            <input id="finalScreeningStainOption3" type="text" name="finalScreeningStainText3"></h4>
                        </div>
                    </li>
                    <li>
                        <h3 class="h3"><label>
                            <input type="checkbox" id="finalScreeningStain4" onclick="toggleVisibilityFinalScreening('finalScreeningStainInput4')" value="32"> PAS with Diastase
                        </label></h3>
                        <div id="finalScreeningStainInput4" class="hidden">
                            <h4 class="h3"><label>Section Code or Block Number</label>
                            <input id="finalScreeningStainOption4" type="text" name="finalScreeningStainText4"></h4>
                        </div>
                    </li>
                    <li>
                        <h3 class="h3"><label>
                            <input type="checkbox" id="finalScreeningStain5" onclick="toggleVisibilityFinalScreening('finalScreeningStainInput5')" value="34"> Fite Faraco
                        </label></h3>
                        <div id="finalScreeningStainInput5" class="hidden">
                            <h4 class="h3"><label>Section Code or Block Number</label>
                            <input id="finalScreeningStainOption5" type="text" name="finalScreeningStainText5"></h4>
                        </div>
                    </li>
                    <li>
                        <h3 class="h3"><label>
                            <input type="checkbox" id="finalScreeningStain6" onclick="toggleVisibilityFinalScreening('finalScreeningStainInput6')" value="36"> Brown-Brenn
                        </label></h3>
                        <div id="finalScreeningStainInput6" class="hidden">
                            <h4 class="h3"><label>Section Code or Block Number</label>
                            <input id="finalScreeningStainOption6" type="text" name="finalScreeningStainText6"></h4>
                        </div>
                    </li>
                    <li>
                        <h3 class="h3"><label>
                            <input type="checkbox" id="finalScreeningStain7" onclick="toggleVisibilityFinalScreening('finalScreeningStainInput7')" value="38"> Congo-Red
                        </label></h3>
                        <div id="finalScreeningStainInput7" class="hidden">
                            <h4 class="h3"><label>Section Code or Block Number</label>
                            <input id="finalScreeningStainOption7" type="text" name="finalScreeningStainText7"></h4>
                        </div>
                    </li>
                    <li>
                        <h3 class="h3"><label>
                            <input type="checkbox" id="finalScreeningStain8" onclick="toggleVisibilityFinalScreening('finalScreeningStainInput8')" value="40"> Others
                        </label></h3>
                        <div id="finalScreeningStainInput8" class="hidden">
                            <h4 class="h3"><label>Special Stain Name</label>
                            <input id="finalScreeningStainNameInput" type="text" name="finalScreeningStainText8">
                            <label>Block Number</label>
                            <input id="finalScreeningStainOption8" type="text" name="finalScreeningStainBlockNumber"></h4>
                        </div>
                    </li>
                </ul> 
                <br>  
                <ul>
                    <h3 class="h3"><b>Immunohistochemistry (IHC) Instructions</b></h3>
                    <h3 class="h3"><label>
                        <input type="checkbox" id="finalScreeningIHC1" onclick="toggleVisibilityFinalScreening('finalScreeningIHCInput1')" value="44"> Block Number
                    </label>
                    <div id="finalScreeningIHCInput1" class="hidden">
                        <textarea id="finalScreeningIHCLabInput" type="text" name="finalScreeningIHCText1"></textarea>
                    </div>
                    <br><label>
                        <input type="checkbox" id="finalScreeningIHC2" onclick="toggleVisibilityFinalScreening('finalScreeningIHCInput2')" value="44"> Markers
                    </label>
                    <div id="finalScreeningIHCInput2" class="hidden">
                        <textarea id="finalScreeningIHCLabMarkers" type="text" name="finalScreeningIHCText2"></textarea>
                    </div>
                    </h3>
                </ul> 
            </div>
    </div>


    <div role="tabpanel" class="tabcontent_1" id="Final-Screening-GrossInstructions">
        <div class="design-process-content">
            <button id="final_screening_gross_related_instructions" type="button" class="btn btn-primary">Save</button>
            <br><br>
            <h4 class="semi-bold"> 
                <div class="form-addreq">
                    <label>
                        Gross Check & Re-gross for/of
                    </label>
                    <div id="stainInput9" class="">
                        <textarea id="gross_check_re_gross_fianl_screening" name="text" rows="4" cols="50"></textarea>
                        <input type="hidden" value="6"/>
                    </div>
                </div>
            </h4>
        </div>
    </div>
    
    <div id="Final-Screening-Done" class="tabcontent_1">
        <p>Final Screening Done</p>
    </div>

    
        <script>
            function showTab(tabId) {
                // Remove grayed-out class from all tab contents
                var tabs = document.getElementsByClassName('tab-content');
                for (var i = 0; i < tabs.length; i++) {
                    tabs[i].classList.remove('grayed-out');
                }
    
                // Set inactive class to all buttons and remove from the selected button
                var buttons = document.getElementsByClassName('tab-buttons')[0].getElementsByTagName('button');
                for (var j = 0; j < buttons.length; j++) {
                    buttons[j].classList.remove('active');
                    buttons[j].classList.add('inactive');
                }
    
                // Set the selected tab button to active
                document.getElementById(tabId).style.display = 'block';
                document.getElementById('tab-' + tabId).classList.remove('inactive');
                document.getElementById('tab-' + tabId).classList.add('active');
    
                // Grayout all contents except the active one
                for (var i = 0; i < tabs.length; i++) {
                    if (tabs[i].id !== tabId) {
                        tabs[i].classList.add('grayed-out');
                    }
                }
               

                // Log the active and inactive tabs
                logTabStates();
                startScreeningAndFinalScreening();
            }


            function logTabStates() {
                // Find and log the active tab
                var activeTab = document.querySelector('.tab-buttons button.active');
                if (activeTab) {
                    console.log('Active Tab:', activeTab.textContent.trim());
                }
                
                // Find and log the inactive tabs
                var inactiveTabs = document.querySelectorAll('.tab-buttons button.inactive');
                    inactiveTabs.forEach(function(tab) {
                        var statusTab = document.getElementById('status');
                        var statusButton = document.getElementById('tab-status');
                    
                        if (statusTab.classList.contains('grayed-out')) {
                            statusTab.classList.remove('grayed-out');
                            statusButton.classList.remove('inactive');
                            statusButton.classList.add('active');
                        } else {
                            statusTab.classList.add('grayed-out');
                            statusButton.classList.add('inactive');
                            statusButton.classList.remove('active');
                        }
                    
                        // Show or hide the status tab content based on its state
                        var isGrayedOut = statusTab.classList.contains('grayed-out');
                        var rows = document.querySelectorAll('#status-table-body .status-row');
                    
                        rows.forEach(function(row, index) {
                            if (isGrayedOut && index < rows.length - 2) {
                                row.classList.add('hidden');
                            } else {
                                row.classList.remove('hidden');
                            }
                        });
                    
                });
            }
    

            function logButtonClick(event) {
                console.log("Button clicked: " + event.target.innerText);
            }
    
           
            function toggleOptions() {
                var status4Checkbox = document.getElementById('status4');
                var historyCollect = document.getElementById('history_collect');
                var additionalOptions = document.getElementById('additional-options');
    
                if (status4Checkbox.checked) {
                    historyCollect.style.display = 'block';
                    additionalOptions.style.display = 'block';
                } else {
                    historyCollect.style.display = 'none';
                    additionalOptions.style.display = 'none';
                }
            }
    
            function toggleTextarea() {
                var othersCheckbox = document.getElementById('status4-others');
                var othersTextarea = document.getElementById('others-textarea');
    
                if (othersCheckbox.checked) {
                    othersTextarea.style.display = 'block';
                } else {
                    othersTextarea.style.display = 'none';
                }
            }

            function toggleVisibility(inputId) {
                var inputField = document.getElementById(inputId);
                if (inputField.classList.contains("hidden")) {
                    inputField.classList.remove("hidden");
                } else {
                    inputField.classList.add("hidden");
                }
            }
    
            function logHistoryValue(event) {
                // Function to handle the Save button click in the modal
                console.log("Save button clicked.");
            }

            function logHistoryValueFinalScreening(event) {
                // Function to handle the Save button click in the modal
                console.log("Save button clicked.");
            }


            function toggleOptionsFinalScreening() {
                    var checkbox = document.getElementById('status4');
                    var historyCollect = document.getElementById('history_collect-final-screening');
                    var additionalOptions = document.getElementById('additional-options-final-screening');

                    if (checkbox.checked) {
                        historyCollect.style.display = 'block';
                        additionalOptions.style.display = 'block';
                    } else {
                        historyCollect.style.display = 'none';
                        additionalOptions.style.display = 'none';
                    }
            }

            function toggleTextareaFinalScreening() {
                var othersCheckbox = document.getElementById('status4-others');
                var textarea = document.getElementById('others-textarea-final-screening');

                if (othersCheckbox.checked) {
                    textarea.style.display = 'block';
                } else {
                    textarea.style.display = 'none';
                }
            }

            function toggleVisibilityFinalScreening(id) {
                    var element = document.getElementById(id);
                    if (element.classList.contains('hidden')) {
                        element.classList.remove('hidden');
                    } else {
                        element.classList.add('hidden');
                    }
            }

        </script>

        <script>
            function openTab(evt, tabName) {
                var i, tabcontent_1, tablinks;
                tabcontent_1 = document.getElementsByClassName("tabcontent_1");
                for (i = 0; i < tabcontent_1.length; i++) {
                    tabcontent_1[i].style.display = "none";
                }
                tablinks = document.getElementsByClassName("tablinks");
                for (i = 0; i < tablinks.length; i++) {
                    tablinks[i].className = tablinks[i].className.replace(" active", "");
                }
                document.getElementById(tabName).style.display = "block";
                evt.currentTarget.className += " active";
            }
        </script>

        <script>
           function toggleStatusTab() {
                var statusTab = document.getElementById('status');
                var statusButton = document.getElementById('tab-status');
                
                if (statusTab.classList.contains('grayed-out')) {
                    statusTab.classList.remove('grayed-out');
                    statusButton.classList.remove('inactive');
                    statusButton.classList.add('active');
                } else {
                    statusTab.classList.add('grayed-out');
                    statusButton.classList.add('inactive');
                    statusButton.classList.remove('active');
                }
                
                // Show or hide the status tab content based on its state
                var isGrayedOut = statusTab.classList.contains('grayed-out');
                var rows = document.querySelectorAll('#status-table-body .status-row');
                
                rows.forEach(function(row, index) {
                    if (isGrayedOut && index < rows.length - 2) {
                        row.classList.add('hidden');
                    } else {
                        row.classList.remove('hidden');
                    }
                });
                
            }
        </script>

        <script>
            let selectedValues = [];

            // Function to log selected values when an active tab is present
            function startScreeningAndFinalScreening() {
                    var activeTab = document.querySelector('.tab-buttons button.active');
                    if (activeTab) {
                        console.log('Selected Values:', selectedValues);
                        const active_tab = activeTab.textContent.trim();
                        console.log('Active Tab:', active_tab);
                        const labNumber = '<?php echo isset($_GET['labno']) ? htmlspecialchars($_GET['labno']) : ''; ?>';
                        const loggedInUserId = '<?php echo $loggedInUserId; ?>';
                        var labStatus = <?php echo json_encode($lab_status); ?>;

                        if (active_tab === 'Screening') {
                            // Send data to screening_start.php
                            var xhr = new XMLHttpRequest();
                            xhr.open("POST", "insert/screening_start.php", true);
                            xhr.setRequestHeader("Content-Type", "application/json");

                            const data = {
                                labNumber: labNumber,
                                loggedInUserId: loggedInUserId,
                                values: selectedValues // Assuming selectedValues contains the necessary data
                            };

                            console.log("Data being sent for Screening:", data);

                            xhr.send(JSON.stringify(data));

                            xhr.onload = function() {
                                if (xhr.status === 200) {
                                    console.log("Screening started successfully:", xhr.responseText);
                                    // alert("Data saved successfully.");
                                    // window.location.reload();
                                } else {
                                    console.error("Error starting screening:", xhr.statusText);
                                }
                            };

                        } else if (active_tab === 'Finalization') {
                            // Check if any WSStatusName in labStatus array is 'Screening Done'
                            var screeningDone = labStatus.some(status => status.WSStatusName && status.WSStatusName.trim() === 'Screening Done');

                            if (screeningDone) {
                                // Send data to final_screening_start.php
                                var xhr = new XMLHttpRequest();
                                xhr.open("POST", "insert/final_screening_start.php", true);
                                xhr.setRequestHeader("Content-Type", "application/json");

                                const data = {
                                    labNumber: labNumber,
                                    loggedInUserId: loggedInUserId,
                                    values: selectedValues // Assuming selectedValues contains the necessary data
                                };

                                console.log("Data being sent for Finalization:", data);

                                xhr.send(JSON.stringify(data));

                                xhr.onload = function() {
                                    if (xhr.status === 200) {
                                        console.log("Final screening started successfully:", xhr.responseText);
                                        // alert("Data saved successfully.");
                                        // window.location.reload();
                                    } else {
                                        console.error("Error starting final screening:", xhr.statusText);
                                    }
                                };

                            } else {
                                // Alert the user to complete screening first
                                alert('Please first complete screening before starting finalization.');
                                grayOutFinalizationTab();
                            }

                        } else {
                            console.error('Unrecognized active tab:', active_tab);
                        }
                    } else {
                        console.error('No active tab found.');
                    }
            }

            function grayOutFinalizationTab() {
                // Find the finalization tab and gray it out
                var finalizationTab = document.getElementById('final-screening');
                if (finalizationTab) {
                    finalizationTab.classList.add('grayed-out');
                    finalizationTab.querySelectorAll('button').forEach(button => {
                        button.disabled = true;
                    });
                }
            }

            let selectedLabInstructionsValues = [];

            const checkboxLabInstructionsMap = {
                'option1': { fk_status_id: 16, inputId: 'lab_option_1' },
                'option2': { fk_status_id: 18, inputId: 'lab_option_2' },
                'option3': { fk_status_id: 20, inputId: 'lab_option_3' },
                'option4': { fk_status_id: 24, inputId: 'lab_option_4' },
                'option5': { fk_status_id: 22, inputId: 'lab_option_5' },
                'stain1': { fk_status_id: 26, inputId: 'lab_stain_1' },
                'stain2': { fk_status_id: 28, inputId: 'lab_stain_2' },
                'stain3': { fk_status_id: 30, inputId: 'lab_stain_3' },
                'stain4': { fk_status_id: 32, inputId: 'lab_stain_4' },
                'stain5': { fk_status_id: 34, inputId: 'lab_stain_5' },
                'stain6': { fk_status_id: 36, inputId: 'lab_stain_6' },
                'stain7': { fk_status_id: 38, inputId: 'lab_stain_7' },
                'stain8': { fk_status_id: 40, inputId: 'lab_stain_8', additionalInputId: 'stain_name_input' },
                'stain14': { fk_status_id: 44, inputId: 'lab_stain_14' },
                'stain15': { fk_status_id: 44, inputId: 'lab_stain_15' }
            };

        
            function logLabInstructionsValue() {
                    selectedLabInstructionsValues = []; // Clear previous values

                    // Combine 'Block Number' and 'Markers' values if both are checked
                    const blockCheckbox = document.getElementById('stain14');
                    const markersCheckbox = document.getElementById('stain15');
                    const blockValue = document.getElementById('lab_stain_14').value.trim();
                    const markersValue = document.getElementById('lab_stain_15').value.trim();

                    if (blockCheckbox.checked || markersCheckbox.checked) {
                        let combinedDescription = '';

                        if (blockCheckbox.checked && blockValue !== '') {
                            combinedDescription += `Block Number: ${blockValue}`;
                        }

                        if (markersCheckbox.checked && markersValue !== '') {
                            if (combinedDescription !== '') {
                                combinedDescription += ' / ';
                            }
                            combinedDescription += `Markers: ${markersValue}`;
                        }

                        if (combinedDescription !== '') {
                            // Push combined entry only once
                            selectedLabInstructionsValues.push({ fk_status_id: 44, description: combinedDescription, checkboxId: 'stain14-stain15' });
                        }
                    }

                    Object.keys(checkboxLabInstructionsMap).forEach(checkboxId => {
                        const { fk_status_id, inputId, additionalInputId } = checkboxLabInstructionsMap[checkboxId];
                        const checkbox = document.getElementById(checkboxId);
                        const inputElement = document.getElementById(inputId);
                        const additionalInputElement = additionalInputId ? document.getElementById(additionalInputId) : null;

                        if (checkbox.checked && checkboxId !== 'stain14' && checkboxId !== 'stain15') { // Exclude stain14 and stain15 from individual processing
                            let description = inputElement ? inputElement.value : '';
                            if (additionalInputElement) {
                                description = `${additionalInputElement.value}:${inputElement.value}`;
                            }

                            if (description.trim() === '' || (additionalInputElement && additionalInputElement.value.trim() === '')) {
                                checkbox.checked = false; // Uncheck the checkbox if the input is empty
                            } else {
                                selectedLabInstructionsValues.push({ fk_status_id, description, checkboxId });
                            }
                        }
                    });

                    console.log("Selected Values: ", selectedLabInstructionsValues);
            }


            // Function to log selected values (called on "Save" button click)
            function logHistoryValue(event) {
                startScreeningAndFinalScreening();
            }

            // Event listeners for checkbox changes
            document.querySelectorAll('.form-waiting-input').forEach(checkbox => {
                checkbox.addEventListener('change', function (event) {
                    const checkboxId = event.target.id;
                    const isChecked = event.target.checked;

                    if (checkboxId === 'status5') {
                        if (isChecked) {
                            selectedValues.push({ fk_status_id: 5, description: 'Study' });
                        } else {
                            const studyIndex = selectedValues.findIndex(obj => obj.fk_status_id === 5);
                            if (studyIndex !== -1) {
                                selectedValues.splice(studyIndex, 1);
                            }
                        }
                    } else if (checkboxId.startsWith('status4')) {
                        const description = event.target.value;

                        if (isChecked) {
                            selectedValues.push({ fk_status_id: 4, description: description });
                        } else {
                            const matchingIndex = selectedValues.findIndex(obj => obj.fk_status_id === 4 && obj.description === description);
                            if (matchingIndex !== -1) {
                                selectedValues.splice(matchingIndex, 1);
                            }
                        }
                    }
                });
            });

            // Function to toggle visibility of additional options
            function toggleOptions() {
                const historyCollect = document.getElementById('history_collect');
                const additionalOptions = document.getElementById('additional-options');

                if (historyCollect.style.display === 'none') {
                    historyCollect.style.display = 'block';
                    additionalOptions.style.display = 'block';
                } else {
                    historyCollect.style.display = 'none';
                    additionalOptions.style.display = 'none';
                }
            }

            // Function to toggle visibility of textarea and update selectedValues
            function toggleTextarea() {
                const othersTextarea = document.getElementById('others-textarea');
                othersTextarea.style.display = othersTextarea.style.display === 'none' ? 'block' : 'none';

                // Update selectedValues when "Others" checkbox is toggled
                const othersCheckbox = document.getElementById('status4-others');
                if (othersCheckbox.checked) {
                    othersTextarea.addEventListener('input', function () {
                        const othersIndex = selectedValues.findIndex(obj => obj.fk_status_id === 4 && obj.description.startsWith('Others: '));
                        if (othersIndex !== -1) {
                            selectedValues[othersIndex].description = `Others: ${othersTextarea.value}`;
                        } else {
                            selectedValues.push({ fk_status_id: 4, description: `Others: ${othersTextarea.value}` });
                        }
                    });
                } else {
                    const othersIndex = selectedValues.findIndex(obj => obj.fk_status_id === 4 && obj.description.startsWith('Others: '));
                    if (othersIndex !== -1) {
                        selectedValues.splice(othersIndex, 1);
                    }
                }
            }

            // Adding event listener for "Save" button
            document.getElementById('history-button').addEventListener('click', logHistoryValue);

            // Add event listener for the 'status4' checkbox to display additional options
            document.getElementById('status4').addEventListener('change', function(event) {
                if (event.target.checked) {
                    document.getElementById('additional-options').style.display = 'block';
                } else {
                    document.getElementById('additional-options').style.display = 'none';
                }
            });

            let selected_gross_related_instructions_screening_values = [];

            const checkboxMap = {
                'stain9': { fk_status_id: 6, inputId: 'gross_check_re_gross_screening' }
            };

            function logGrossRelatedScreeningInstructionsValue(event) {
                const checkbox = event.target.type === 'checkbox' ? event.target : document.querySelector(`input[type="checkbox"][data-input-id="${event.target.id}"]`);
                if (!checkbox) return;

                const checkboxId = checkbox.id;
                const isChecked = checkbox.checked;

                if (checkboxMap.hasOwnProperty(checkboxId)) {
                    const { fk_status_id, inputId } = checkboxMap[checkboxId];
                    const inputElement = document.getElementById(inputId);

                    // Always update the value after the user has finished typing
                    let description = inputElement ? inputElement.value : '';

                    // Proceed only if description is not empty
                    if (description.trim() === '' && isChecked) {
                        checkbox.checked = false;
                        return;
                    }

                    // Check if the item already exists and update it
                    const existingIndex = selected_gross_related_instructions_screening_values.findIndex(item => item.checkboxId === checkboxId);
                    if (existingIndex !== -1) {
                        selected_gross_related_instructions_screening_values[existingIndex] = { fk_status_id, description, checkboxId };
                    } else {
                        selected_gross_related_instructions_screening_values.push({ fk_status_id, description, checkboxId });
                    }

                    if (!isChecked) {
                        selected_gross_related_instructions_screening_values = selected_gross_related_instructions_screening_values.filter(item => item.checkboxId !== checkboxId);
                    }
                }
            }

            function handleInputChange(event) {
                const inputElement = event.target;
                const inputId = inputElement.id;
                const checkboxId = Object.keys(checkboxMap).find(key => checkboxMap[key].inputId === inputId);

                if (checkboxId) {
                    const checkbox = document.getElementById(checkboxId);
                    if (inputElement.value.trim() !== '') {
                        checkbox.checked = true;
                        logGrossRelatedScreeningInstructionsValue({ target: checkbox });
                    } else if (inputElement.value.trim() === '') {
                        checkbox.checked = false;
                        logGrossRelatedScreeningInstructionsValue({ target: checkbox });
                    }
                }
            }

            function attachEventListeners() {
                Object.keys(checkboxMap).forEach(checkboxId => {
                    const checkbox = document.getElementById(checkboxId);
                    if (checkbox) {
                        checkbox.addEventListener('change', logGrossRelatedScreeningInstructionsValue);
                    }
                });

                Object.values(checkboxMap).forEach(({ inputId }) => {
                    const inputElement = document.getElementById(inputId);
                    if (inputElement) {
                        inputElement.addEventListener('input', handleInputChange);
                    }
                });

                // Event listener for the "Save" button
                const saveButton = document.getElementById('gross_related_instructions_screening');
                if (saveButton) {
                    saveButton.addEventListener('click', function () {
                        console.log("Selected Gross Related Instructions Screening Values:", selected_gross_related_instructions_screening_values);
                    });
                }
            }

            // Set up event listeners after the DOM is loaded
            document.addEventListener('DOMContentLoaded', attachEventListeners);
            
            document.addEventListener('DOMContentLoaded', function() {
                    var historyButton = document.getElementById('history-button');

                    if (!historyButton) {
                        console.error('History button not found.');
                        return;
                    }

                    historyButton.addEventListener('click', function() {
                        var labNumber = '<?php echo $_GET['labno']; ?>';
                        var loggedInUserId = '<?php echo $loggedInUserId; ?>';
                        
                        // Ensure proper escaping of JSON data
                        var labStatusJSON = <?php echo json_encode($labStatus); ?>;

                        try {
                            var labStatus = JSON.parse(labStatusJSON);
                        } catch (e) {
                            console.error("Failed to parse labStatus JSON:", e);
                            return;
                        }

                        if (labNumber && loggedInUserId) {
                            var validStatusFound = true;

                            if (Array.isArray(labStatus)) {
                                labStatus = labStatus.filter(entry => entry.WSStatusName && entry.WSStatusName.trim() !== '');
                                
                                for (var i = 0; i < labStatus.length; i++) {
                                    var currentStatus = labStatus[i]['WSStatusName'];

                                    if (currentStatus) {
                                        currentStatus = currentStatus.trim().toLowerCase();

                                        
                                    }
                                }
                            } else {
                                console.error("labStatus is not an array.");
                            }

                            if (validStatusFound) {
                                console.log("Valid status found. Proceeding with data submission.");

                                const xhr = new XMLHttpRequest();
                                xhr.open("POST", "insert/screening_info.php", true);
                                xhr.setRequestHeader("Content-Type", "application/json");

                                const data = {
                                    labNumber: labNumber,
                                    loggedInUserId: loggedInUserId,
                                    values: selectedValues // Ensure this is correctly defined
                                };

                                xhr.send(JSON.stringify(data));

                                xhr.onload = function() {
                                    if (xhr.status === 200) {
                                        console.log("Data saved successfully:", xhr.responseText);

                                        // Display success message and reload window
                                        alert("Data saved successfully.");
                                        window.location.reload();
                                    } else {
                                        console.error("Error saving data:", xhr.status, xhr.statusText);
                                    }
                                };

                                xhr.onerror = function() {
                                    console.error("Request failed.");
                                };

                            } else {
                                alert("Please first start screening  before Study/History.");
                            }

                        } else {
                            console.error("Lab number and User ID are required.");
                        }
                    });
            });


            document.getElementById('lab-button').addEventListener('click', function() {
                    const labNumber = '<?php echo isset($_GET['labno']) ? htmlspecialchars($_GET['labno']) : ''; ?>';
                    const loggedInUserId = '<?php echo $loggedInUserId; ?>';
                    var labStatus = <?php echo json_encode($lab_status); ?>;

                    if (labNumber && loggedInUserId) {

                        // Check if any WSStatusName in labStatus array is 'Assisted' or 'Final Screening Start'
                        var validStatusFound = true;
                        // for (var i = 0; i < labStatus.length; i++) {
                        //     var currentStatus = labStatus[i]['WSStatusName'];
                        //     if ((currentStatus && currentStatus.trim() === '') ||
                        //         (currentStatus && currentStatus.trim() === 'Assisted') || 
                        //         (currentStatus && currentStatus.trim() === 'Final Screening Start')) {
                        //         validStatusFound = true;
                        //         break;
                        //     }
                        // }

                        if (validStatusFound) {
                            const xhr = new XMLHttpRequest();
                            xhr.open("POST", "insert/lab_instructions.php", true);
                            xhr.setRequestHeader("Content-Type", "application/json");

                            const data = {
                                labNumber: labNumber,
                                loggedInUserId: loggedInUserId,
                                values: selectedLabInstructionsValues
                            };

                            xhr.send(JSON.stringify(data));

                            xhr.onload = function() {
                                if (xhr.status === 200) {
                                    console.log("Data saved successfully:", xhr.responseText);
                                    alert("Data saved successfully.");
                                    window.location.reload();
                                } else {
                                    console.error("saving data:");
                                }
                            };
                            
                        } else {
                            alert('Please First Start Screening Before Given Lab Related Instructions.');
                            
                        }
                        
                    } else {
                        console.error("Lab number and User ID are required.");
                    }
            });


            document.getElementById('gross_related_instructions_screening').addEventListener('click', function() {
                    const labNumber = '<?php echo isset($_GET['labno']) ? htmlspecialchars($_GET['labno']) : ''; ?>';
                    const loggedInUserId = '<?php echo $loggedInUserId; ?>';
                    var labStatus = <?php echo json_encode($lab_status); ?>;

                    if (labNumber && loggedInUserId) {

                        // Check if any WSStatusName in labStatus array is 'Assisted' or 'Final Screening Start'
                        var validStatusFound = true;
                        // for (var i = 0; i < labStatus.length; i++) {
                        //     var currentStatus = labStatus[i]['WSStatusName'];
                        //     if ((currentStatus && currentStatus.trim() === 'Assisted') || 
                        //         (currentStatus && currentStatus.trim() === 'Final Screening Start')) {
                        //         validStatusFound = true;
                        //         break;
                        //     }
                        // }

                        if (validStatusFound) {
                            const xhr = new XMLHttpRequest();
                            xhr.open("POST", "insert/gross_related_instructions.php", true);
                            xhr.setRequestHeader("Content-Type", "application/json");

                            const data = {
                                labNumber: labNumber,
                                loggedInUserId: loggedInUserId,
                                values: selected_gross_related_instructions_screening_values
                            };

                            xhr.send(JSON.stringify(data));

                            xhr.onload = function() {
                                if (xhr.status === 200) {
                                    console.log("Data saved successfully:", xhr.responseText);
                                    alert("Data saved successfully.");
                                    window.location.reload();
                                } else {
                                    console.error("Error saving data:", xhr.statusText);
                                }
                            };
                            
                        } else {
                            alert('Please First Start Screening  Before Given Gross Related Instructions.');
                            
                        }

                        
                    } else {
                        console.error("Lab number and User ID are required.");
                    }
            });


            document.getElementById('screening_done').addEventListener('click', function() {
                    const labNumber = '<?php echo isset($_GET['labno']) ? htmlspecialchars($_GET['labno']) : ''; ?>';
                    const loggedInUserId = '<?php echo $loggedInUserId; ?>';
                    var labStatus = <?php echo json_encode($lab_status); ?>;
                    let value = {
                        'description' : " ",
                        'fk_status_id' : 46
                    }
                    

                    if (labNumber && loggedInUserId) {

                        // Check if any WSStatusName in labStatus array is 'Assisted' 
                        var validStatusFound = true;
                        // for (var i = 0; i < labStatus.length; i++) {
                        //     var currentStatus = labStatus[i]['WSStatusName'];
                        //     if ((currentStatus && currentStatus.trim() === 'Assisted')) {
                        //         validStatusFound = true;
                        //         break;
                        //     }
                        // }

                        if (validStatusFound) {
                            const xhr = new XMLHttpRequest();
                            xhr.open("POST", "insert/screening_done.php", true);
                            xhr.setRequestHeader("Content-Type", "application/json");

                            const data = {
                                labNumber: labNumber,
                                loggedInUserId: loggedInUserId,
                                values: value
                            };

                            console.log("data : ", data)

                            xhr.send(JSON.stringify(data));

                            xhr.onload = function() {
                                if (xhr.status === 200) {
                                    console.log("Data saved successfully:", xhr.responseText);
                                    alert("Data saved successfully.");
                                    window.location.reload();
                                } else {
                                    console.error("Error saving data:", xhr.statusText);
                                }
                            };
                            
                        } else {
                            alert('Please First Start Screening Before Screening Done.');
                            
                        }

                        
                    } else {
                        console.error("Lab number and User ID are required.");
                    }
            });

            
            document.getElementById('final-history-button').addEventListener('click', logHistoryValueFinalScreening);
            document.addEventListener('DOMContentLoaded', function() {
                    var historyButton = document.getElementById('final-history-button');

                    if (!historyButton) {
                        console.error('History button not found.');
                        return;
                    }

                    historyButton.addEventListener('click', function() {
                        var labNumber = '<?php echo $_GET['labno']; ?>';
                        var loggedInUserId = '<?php echo $loggedInUserId; ?>';
                        
                        // Ensure proper escaping of JSON data
                        var labStatusJSON = <?php echo json_encode($labStatus); ?>;

                        try {
                            var labStatus = JSON.parse(labStatusJSON);
                        } catch (e) {
                            console.error("Failed to parse labStatus JSON:", e);
                            return;
                        }

                        if (labNumber && loggedInUserId) {
                            var validStatusFound = true;

                            if (Array.isArray(labStatus)) {
                                labStatus = labStatus.filter(entry => entry.WSStatusName && entry.WSStatusName.trim() !== '');
                                
                                for (var i = 0; i < labStatus.length; i++) {
                                    var currentStatus = labStatus[i]['WSStatusName'];

                                    if (currentStatus) {
                                        currentStatus = currentStatus.trim().toLowerCase();
                                    }
                                }
                            } else {
                                console.error("labStatus is not an array.");
                            }

                            if (validStatusFound) {
                                console.log("Valid status found. Proceeding with data submission.");

                                const xhr = new XMLHttpRequest();
                                xhr.open("POST", "insert/final_screening_info.php", true);
                                xhr.setRequestHeader("Content-Type", "application/json");

                                const data = {
                                    labNumber: labNumber,
                                    loggedInUserId: loggedInUserId,
                                    values: selectedValues // Ensure this is correctly defined
                                };

                                xhr.send(JSON.stringify(data));

                                xhr.onload = function() {
                                    if (xhr.status === 200) {
                                        console.log("Data saved successfully:", xhr.responseText);

                                        // Display success message and reload window
                                        alert("Data saved successfully.");
                                        window.location.reload();
                                    } else {
                                        console.error("Error saving data:", xhr.status, xhr.statusText);
                                    }
                                };

                                xhr.onerror = function() {
                                    console.error("Request failed.");
                                };

                            } else {
                                alert("Please first start FinalScreening  before Study/History.");
                            }

                        } else {
                            console.error("Lab number and User ID are required.");
                        }
                    });
            });
            
            let selectedFinalScreeningLabInstructionsValues = [];

            const checkboxFinalScreeningLabInstructionsMap = {
                'finalScreeningOption1': { fk_status_id: '16', inputId: 'finalScreeningLabOption1' },
                'finalScreeningOption2': { fk_status_id: '18', inputId: 'finalScreeningLabOption2' },
                'finalScreeningOption3': { fk_status_id: '20', inputId: 'finalScreeningLabOption3' },
                'finalScreeningOption4': { fk_status_id: '24', inputId: 'finalScreeningLabOption4' },
                'finalScreeningOption5': { fk_status_id: '22', inputId: 'finalScreeningLabOption5' },
                'finalScreeningStain1': { fk_status_id: '26', inputId: 'finalScreeningStainOption1' },
                'finalScreeningStain2': { fk_status_id: '28', inputId: 'finalScreeningStainOption2' },
                'finalScreeningStain3': { fk_status_id: '30', inputId: 'finalScreeningStainOption3' },
                'finalScreeningStain4': { fk_status_id: '32', inputId: 'finalScreeningStainOption4' },
                'finalScreeningStain5': { fk_status_id: '34', inputId: 'finalScreeningStainOption5' },
                'finalScreeningStain6': { fk_status_id: '36', inputId: 'finalScreeningStainOption6' },
                'finalScreeningStain7': { fk_status_id: '38', inputId: 'finalScreeningStainOption7' },
                'finalScreeningStain8': { fk_status_id: '40', inputId: 'finalScreeningStainOption8', additionalInputId: 'finalScreeningStainNameInput' },
                'finalScreeningIHC1': { fk_status_id: '44', inputId: 'finalScreeningIHCLabInput' },
                'finalScreeningIHC2': { fk_status_id: '44', inputId: 'finalScreeningIHCLabMarkers',}
            };

            function logFinalScreeningLabInstructionsValue() {
                selectedFinalScreeningLabInstructionsValues = []; // Clear previous values

                let ihcBlockValue = '';
                let ihcMarkersValue = '';
                let ihcDescription = '';

                Object.keys(checkboxFinalScreeningLabInstructionsMap).forEach(checkboxId => {
                    const { fk_status_id, inputId, additionalInputId } = checkboxFinalScreeningLabInstructionsMap[checkboxId];
                    const checkbox = document.getElementById(checkboxId);
                    const inputElement = document.getElementById(inputId);
                    const additionalInputElement = additionalInputId ? document.getElementById(additionalInputId) : null;

                    if (checkbox.checked) {
                        let description = inputElement ? inputElement.value : '';
                        if (additionalInputElement) {
                            description = `${additionalInputElement.value}:${inputElement.value}`;
                        }

                        if (checkboxId === 'finalScreeningIHC1') {
                            ihcBlockValue = inputElement.value;
                        }

                        if (checkboxId === 'finalScreeningIHC2') {
                            ihcMarkersValue = inputElement.value;
                        }

                        // Combine Block Number and Markers
                        if (ihcBlockValue || ihcMarkersValue) {
                            if (ihcBlockValue && ihcMarkersValue) {
                                ihcDescription = `Block Number: ${ihcBlockValue}\nMarkers: ${ihcMarkersValue}`;
                            } else if (ihcBlockValue && !ihcMarkersValue) {
                                ihcDescription = `Block Number: ${ihcBlockValue}`;
                            } else if (!ihcBlockValue && ihcMarkersValue) {
                                ihcDescription = `Markers: ${ihcMarkersValue}`;
                            }

                            // Save only once if not already saved
                            const existingIHCEntry = selectedFinalScreeningLabInstructionsValues.find(
                                item => item.fk_status_id === '44'
                            );

                            if (!existingIHCEntry) {
                                selectedFinalScreeningLabInstructionsValues.push({ fk_status_id: '44', description: ihcDescription.trim(), checkboxId: 'finalScreeningIHC' });
                            } else {
                                existingIHCEntry.description = ihcDescription.trim();
                            }
                        } else {
                            if (description.trim() === '' || (additionalInputElement && additionalInputElement.value.trim() === '')) {
                                checkbox.checked = false; // Uncheck the checkbox if the input is empty
                            } else {
                                selectedFinalScreeningLabInstructionsValues.push({ fk_status_id, description, checkboxId });
                            }
                        }
                    }
                });

                console.log("Selected Values After Processing: ", selectedFinalScreeningLabInstructionsValues);
            }
            
            document.getElementById('final-screening-save-button').addEventListener('click', function() {
                    const labNumber = '<?php echo isset($_GET['labno']) ? htmlspecialchars($_GET['labno']) : ''; ?>';
                    const loggedInUserId = '<?php echo $loggedInUserId; ?>';
                    var labStatus = <?php echo json_encode($lab_status); ?>;

                    if (labNumber && loggedInUserId) {

                        // Check if any WSStatusName in labStatus array is 'Assisted' or 'Final Screening Start'
                        var validStatusFound = true;
                        // for (var i = 0; i < labStatus.length; i++) {
                        //     var currentStatus = labStatus[i]['WSStatusName'];
                        //     if ((currentStatus && currentStatus.trim() === 'Assisted') || 
                        //         (currentStatus && currentStatus.trim() === 'Final Screening Start')) {
                        //         validStatusFound = true;
                        //         break;
                        //     }
                        // }

                        if (validStatusFound) {
                            const xhr = new XMLHttpRequest();
                            xhr.open("POST", "insert/final_lab_instructions.php", true);
                            xhr.setRequestHeader("Content-Type", "application/json");

                            const data = {
                                labNumber: labNumber,
                                loggedInUserId: loggedInUserId,
                                values: selectedFinalScreeningLabInstructionsValues
                            };

                            xhr.send(JSON.stringify(data));
                            console.log("Selected Values: ", data);

                            xhr.onload = function() {
                                if (xhr.status === 200) {
                                    console.log("Data saved successfully:", xhr.responseText);
                                    alert("Data saved successfully.");
                                    window.location.reload();
                                } else {
                                    console.error("saving data:");
                                }
                            };
                            
                        } else {
                            alert('Please First Start Screening Before Given Lab Related Instructions.');
                            
                        }
                        
                    } else {
                        console.error("Lab number and User ID are required.");
                    }
            });
            

            document.getElementById('final_screening_gross_related_instructions').addEventListener('click', function() {
                    const textarea = document.getElementById('gross_check_re_gross_fianl_screening');
                    const hiddenInput = document.querySelector('#stainInput9 input[type="hidden"]');
    
                    const description = textarea.value.trim();
                    const fk_status_id = hiddenInput.value;

                    const labNumber = '<?php echo isset($_GET['labno']) ? htmlspecialchars($_GET['labno']) : ''; ?>';
                    const loggedInUserId = '<?php echo $loggedInUserId; ?>';
                    var labStatus = <?php echo json_encode($lab_status); ?>;

                    if (description) {
                        fetch('insert/final_gross_related_instructions.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                labNumber: labNumber,
                                loggedInUserId: loggedInUserId,
                                values: [
                                    {
                                        fk_status_id: fk_status_id,
                                        description: description
                                    }
                                ]
                            })
                        })
                        .then(response => response.text())
                        .then(result => {
                            console.log(result);
                            alert('Data saved successfully!');
                            window.location.reload();
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Failed to save data.');
                        });
                    } else {
                        alert('Please enter a description.');
                    }
            });


            document.getElementById('Final_Screening_Done').addEventListener('click', function() {
                    const labNumber = '<?php echo isset($_GET['labno']) ? htmlspecialchars($_GET['labno']) : ''; ?>';
                    const loggedInUserId = '<?php echo $loggedInUserId; ?>';
                    var labStatus = <?php echo json_encode($lab_status); ?>;
                    let value = {
                        'description' : " ",
                        'fk_status_id' : 15
                    }
                    

                    if (labNumber && loggedInUserId) {

                        // Check if any WSStatusName in labStatus array is 'Assisted' 
                        var validStatusFound = true;
                        // for (var i = 0; i < labStatus.length; i++) {
                        //     var currentStatus = labStatus[i]['WSStatusName'];
                        //     if ((currentStatus && currentStatus.trim() === 'Assisted')) {
                        //         validStatusFound = true;
                        //         break;
                        //     }
                        // }

                        if (validStatusFound) {
                            const xhr = new XMLHttpRequest();
                            xhr.open("POST", "insert/final_screening_done.php", true);
                            xhr.setRequestHeader("Content-Type", "application/json");

                            const data = {
                                labNumber: labNumber,
                                loggedInUserId: loggedInUserId,
                                values: value
                            };

                            console.log("data : ", data)

                            xhr.send(JSON.stringify(data));

                            xhr.onload = function() {
                                if (xhr.status === 200) {
                                    console.log("Data saved successfully:", xhr.responseText);
                                    alert("Data saved successfully.");
                                    window.location.reload();
                                } else {
                                    console.error("Error saving data:", xhr.statusText);
                                }
                            };
                            
                        } else {
                            alert('Please First Start Screening Before Screening Done.');
                            
                        }

                        
                    } else {
                        console.error("Lab number and User ID are required.");
                    }
            });

           
        </script>


        <!-- <script>
            $(document).ready(function() {
                $('#readlabno').on('submit', function(e) {
                    e.preventDefault();
                    let labno = $('#labno').val();
                    if (labno) {
                        $('#lookuplabno').html('<h3 class="semi-bold">Lookup Current Status for Lab No: ' + labno + '</h3>');
                        // Construct the URL with labno parameter
                        let url = 'lab_status.php?labno=' + labno;

                        // Redirect to the constructed URL
                        window.location.href = url;
                    }
                });

                $('#tab-screening, #tab-final-screening, #tab-status').on('click', function() {
                    let labno = $('#labno').val();
                    if (labno) {
                        $('#lookuplabno').html('<h3 class="semi-bold">Lookup Current Status for Lab No: ' + labno + '</h3>');
                        // Construct the URL with labno parameter
                        let url = 'lab_status.php?labno=' + labno;

                        // Redirect to the constructed URL
                        window.location.href = url;
                    }
                });
            });
        </script> -->

        <script>
            $(document).ready(function() {
                // Retrieve the lab numbers from PHP
                const cytoLab = <?php echo json_encode(get_cyto_labnumber_list_doctor_module()); ?>;
                const mfcLab = <?php echo json_encode(get_mfc_labnumber_list()); ?>;
                console.log('mfc lab number :', mfcLab);

                function checkLabNumberAndRedirect(labno) {
                    if (labno) {
                        
                        // Check if the labno exists in cytoLab
                        const foundCyto = cytoLab.some(lab => lab.lab_number === labno);
                        // Check if the labno exists in mfcLab
                        const foundMfc = mfcLab.some(lab => lab.lab_number === 'MFC' + labno);
                       

                        if (foundCyto) {
                            // Redirect to cytoindex.php if labno is in cytoLab
                            window.location.href = 'Cyto/index.php?labno=' + labno;
                        } else if (foundMfc) {
                            // Redirect to mfc_lab_status.php if labno is in mfcLab
                            window.location.href = 'mfc_lab_status.php?labno=' + labno;
                        } else {
                            // Redirect to lab_status.php if labno is not found in either list
                            window.location.href = 'lab_status.php?labno=' + labno;
                        }
                    } else {
                        console.error("Lab number is empty. No redirection performed.");
                    }
                }

                // Handle the form submission
                $('#readlabno').on('submit', function(e) {
                    e.preventDefault();
                    let labno = $('#labno').val();
                    checkLabNumberAndRedirect(labno);
                });

                // Handle click events for the tabs
                $('#tab-screening, #tab-final-screening, #tab-status').on('click', function() {
                    let labno = $('#labno').val();
                    checkLabNumberAndRedirect(labno);
                });
            });
        </script>


  
</body>
</html>