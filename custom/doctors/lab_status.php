<?php

// database connection and function file
include('connection.php');
include('../transcription/common_function.php');
include('../transcription/preliminary_report/preliminary_report_function.php');
include('../grossmodule/gross_common_function.php');
include('../cytology/common_function.php');
include('../histolab/histo_common_function.php');
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

$bone_status = get_bone_status_lab_number("HPL" . $LabNumber);
$boneStatus = json_encode($bone_status);

$loggedInUserId = $user->id;
$loggedInUsername = $user->login;

$userGroupNames = getUserGroupNames($loggedInUserId);

$hasConsultants = false;

$LabNumberWithPrefix = "HPL" . $LabNumber;
$fk_gross_id = getGrossIdByLabNumber($LabNumber);



// Check if "Bones" status exists in the $bone_status array
$showBoneSlideReady = false;


foreach ($bone_status as $status) {
    if ($status['bones_status'] === 'yes') {
        $showBoneSlideReady = true;
    }
    
}

// Flag to check if "Bones Slide Ready" is found
$showTable = false;

// Check if any status_name is "Bones Slide Ready"
foreach ($bone_status as $status) {
    if ($status['status_name'] === 'Bones Slide Ready') {
        $showTable = true;
        break; // Exit loop if found
    }
}


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
        
        /* Ensure the parent and children elements take full height, if necessary */
        html, body, .container {
            height: 100%;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: Verdana;
        }

        /* Container for two-column layout */
        .container {
            display: flex;
            justify-content: space-between;
            width: 100%;
            height: 100vh; /* Take full height of the viewport */
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
            padding: 10px;
            font-size: 20px;
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

        
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }

            .left-side, .right-side {
                width: 100%;
            }
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

        /* Styles for making the button smaller */
        .small-button {
            font-size: 12px;  /* Makes the text smaller */
            padding: 5px 10px;  /* Adjust padding to reduce the size */
            width: auto;  /* Automatically adjust the width to fit content */
            height: auto;  /* Automatically adjust the height */
            display: inline-flex;  /* Align icon and text side by side */
            align-items: center;  /* Align icon and text vertically */
            border-radius: 5px;  /* Optional: gives rounded corners */
            background-color: #f0f0f0; /* Optional: background color */
        }

        .button-text {
            padding-left: 5px;  /* Adds space between the icon and text */
            font-size: 12px;
        }

        .panel {
            height: 100vh;
            overflow-y: auto;
            border-right: 0.5px;
            padding: 10px;
        }
        .pdf-viewer {
            width: 100%;
            height: 600px;
            border: 0.5px;
        }

        .modal-body {
            max-height: 80vh;
            overflow-y: auto;
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
    </button>
</a>
&nbsp; &nbsp; &nbsp; &nbsp; 
<form name="readlabno" id="readlabno" action="">
    <label for="labno">Lab No:</label>
    <input type="text" id="labno" name="labno" autofocus>
</form>

<button style="border:none; background-color: white; color: black;" onclick="loadReport()">
        <i class="fas fa-file-alt" aria-hidden="true"></i> Final Report
</button>

<a href="../transcription/transcription.php?lab_number=<?php echo 'HPL' . $LabNumber; ?>">
    <button style="border:none; background-color: white; color: black;">
            <i class="fas fa-edit" aria-hidden="true"></i> Final Report Edit
    </button>
</a>&ensp;&ensp;&ensp;&ensp;

<button style="border:none; background-color: white; color: black;" onclick="loadPreliminaryReport()">
    <i class="fas fa-file-alt" aria-hidden="true"></i> Preliminary Report
</button>

<a href="../transcription/preliminary_report/hpl/index.php?LabNumber=<?php echo 'HPL' . $LabNumber; ?>">
    <button style="border:none; background-color: white; color: black;">
            <i class="fas fa-edit" aria-hidden="true"></i> Preliminary Report Edit
    </button>
</a>&ensp;&ensp;&ensp;&ensp;

<button style="border:none; font-size: 20px;" id="tab-status" data-toggle="modal" data-target="#exampleModalCenter">
<i class="fa fa-search" aria-hidden="true"></i>Status</button>



<div class="container-fluid">
  <div class="row">

    <!-- Left Panel: Option List -->
    <div class="col-md-3 panel ">
            <ul class="nav nav-tabs process-model more-icon-preocess" role="tablist">
                <div class="tab-buttons button-container">                          
                    <button style="border:none; font-size: 20px;" id="tab-screening" class="inactive" onclick="showTab('screening')">
                        <i class="fas fa-microscope" aria-hidden="true"></i> Preliminary Report</button>                       
                    <button style="border:none; font-size: 20px;" id="tab-final-screening" class="inactive" onclick="showTab('final-screening')">
                        <i class="fas fa-microscope" aria-hidden="true"></i> Final Report</button>                        
                </div>
            </ul>

            <div class="flex-container" style="margin-top:-50px;">
                    <div id="screening" class="tab-content tab btn-group grayed-out">
                        <ul class="nav nav-tabs process-model more-icon-preocess" role="tablist" style=" justify-content: space-between">
                            <button class="small-button"  onclick="openTab(event, 'Screening-Study')">
                                <i class="fas fa-book" style="font-size: 18px; vertical-align: middle;">
                                <span class="button-text">Study / History</span></i>
                            </button>
                            
                            <button class="small-button"  onclick="openTab(event, 'Screening-LabInstructions')">
                                <i class="fas fa-flask" style="font-size: 18px; vertical-align: middle;">
                                <span class="button-text">Lab Instructions</span>
                                </i> 
                            </button>
                            
                            <button class="small-button" onclick="openTab(event, 'Screening-GrossInstructions')">
                                <i class="fas fa-cut" style="font-size: 18px; vertical-align: middle;">
                                <span class="button-text">Gross Instructions</span>
                                </i>
                            </button>
                            
                            <?php if ($showBoneSlideReady): ?>
                                
                                    <button class="small-button" id="screening_bones_ready" onclick="openTab(event, 'ScreeningBoneRelatedInstructions')">
                                        <i class="fas fa-bone vertical-icon" style="font-size: 18px; vertical-align: middle;">
                                        <span class="button-text">Bone Status</span>
                                        </i> 
                                    </button>
                                
                            <?php endif; ?>

                            
                            <button class="small-button" id="screening_done"  onclick="openTab(event, 'Screening-Done')">
                                <i class="fas fa-check" style="font-size: 18px; vertical-align: middle;">
                                <span class="button-text">Report Issued</span>
                                </i>
                            </button>
                            
                        </ul>
                    </div>

                    <div id="final-screening" class="tab-content tab btn-group grayed-out">
                       
                        <ul class="nav nav-tabs process-model more-icon-preocess" role="tablist" style=" justify-content: space-between">
                        
                        
                            <button class="small-button" onclick="openTab(event, 'Final-Screening-Study')">
                            <i class="fas fa-book" style="font-size: 18px; vertical-align: middle;">
                            <span class="button-text">Study / History</span>
                            </i></button>
                        
                            <button class="small-button" onclick="openTab(event, 'Final-Screening-LabInstructions')">
                            <i class="fas fa-flask" style="font-size: 18px; vertical-align: middle;">
                            <span class="button-text">Lab Instructions</span>
                            </i></button> 
                        
                            <button class="small-button" onclick="openTab(event, 'Final-Screening-GrossInstructions')">
                            <i class="fas fa-cut" style="font-size: 18px; vertical-align: middle;">
                            <span class="button-text">Gross Instructions</span>
                            </i></button>
                        
                        <?php if ($showBoneSlideReady): ?>
                               
                                    <button class="small-button" id="screening_bones_ready" onclick="openTab(event, 'ScreeningBoneRelatedInstructions')">
                                        <i class="fas fa-bone vertical-icon" style="font-size: 18px; vertical-align: middle;">
                                            <span class="button-text">Bone Status</span>
                                        </i>
                                    </button>
                                
                        <?php endif; ?>
                       
                            <button class="small-button" id='Final_Screening_Done' onclick="openTab(event, 'Final-Screening-Done')">
                                <i class="fas fa-check" style="font-size: 18px; vertical-align: middle;">
                                <span class="button-text">Report Issued</span>
                            </i></button>
                       
                        </ul>
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
    
      
                <div id="screening-bones" class="tabcontent_1">
                    <p>Wating For Bones</p>
                </div>
                <div id="ScreeningBoneRelatedInstructions" class="tabcontent_1">
                        <?php if ($showTable): ?>
                            <table border="1" cellpadding="10" cellspacing="0" style="width:100%; border-collapse: collapse;">
                                <thead>
                                    <tr>
                                        <th>Section Code</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bone_status as $status): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($status['block_number']); ?></td>
                                            <td>
                                                <?php if ($status['status_name'] === 'Bones Slide Ready'): ?>
                                                    <?php echo htmlspecialchars($status['status_name']); ?>
                                                <?php else: ?>
                                                    <span style="color: red;">Bones Slide are not Ready</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p style="color: red; font-size: 18px; font-family: Arial, sans-serif; font-weight: bold; text-align: left;">Bones are not ready.</p>
                        <?php endif; ?>
                </div>


                <div id="Screening-Done" class="tabcontent_1">
                    <p>Preliminary Report Issued</p>
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

                <div id="final-screening-bones" class="tabcontent_1">
                    <p>Wating For Bones</p>
                </div>
    
                <div id="Final-Screening-Done" class="tabcontent_1">
                    <p>Final Report Issued</p>
                </div>
    </div>

    <!-- Middle Panel: PDF View -->
    <div class="col-md-6 panel">
      <iframe id="reportFrame" style="width:100%; height:800px; border:none; display:none;"></iframe>
    </div>

    <!-- Right Panel: Form -->
    <div class="col-md-3 panel ">
      <h5>Edit / Insert Form</h5>

        <!-- Choice Dropdown -->
        <div class="form-group">
            <label for="formTypeSelect">Select Report Type</label>
            <select class="form-control" id="formTypeSelect">
            <option value="preliminary">Preliminary</option>
            <option value="final">Final</option>
            </select>
        </div>

        <!-- Preliminary Form -->
        <div id="preliminaryForm">
            <div class="form-group">
                <label for="formEditTypeSelect">Select Edit Type</label>
                <select class="form-control" id="formEditTypeSelect">
                <option value="Clinical Details">Clinical Details</option>
                <option value="Site Of Specimen">Site Of Specimen</option>
                <option value="Gross">Gross</option>
                <option value="Microscopic">Microscopic</option>
                <option value="Diagnosis">Diagnosis</option>
                </select>
            </div>

            <div id="ClinicalDetailsForm">
                <form id='clinicalDetailsForm' method='post' action='../transcription/clinical_details.php'>
                    <div class='form-group'>
                        <h2 class='heading'>Clinical Details</h2>
                            <div class='controls'>
                                <textarea id='clinicalDetailsTextarea' name='clinical_details' cols='40' rows='2'></textarea>
                                <input type='hidden' id='labNumberInput' name='lab_number' value='<?php echo htmlspecialchars($LabNumberWithPrefix); ?>'>
                                <input type='hidden' id='createdUserInput' name='created_user' value='<?php echo htmlspecialchars($loggedInUsername); ?>'>
                            </div>
                            <div class='grid'>
                                <button style='background-color: rgb(118, 145, 225);
                                color: white;
                                padding: 12px 20px;
                                border: none;
                                border-radius: 4px;
                                cursor: pointer;
                                float: right;
                                transition: box-shadow 0.3s ease;' id='saveBtn' type='submit'>Save</button>
                                <button id='updateBtn' type='submit' style='display: none;'>Update</button>
                            </div>  
                    </div>
                </form>
            </div>
            <div id=""></div>
            <div id=""></div>
            <div id=""></div>

            <div id="MicroscopicFrom">
                <?php 
                    // Preliminary Report Micro Description
                    $existingMicroDescriptions = getExistingPreliminaryReportMicroDescriptions($LabNumberWithPrefix);
                    $specimens_list = get_gross_specimens_list($LabNumber);
                    // Ensure $existingMicroDescriptions is an array
                    if (!is_array($existingMicroDescriptions)) {
                        $existingMicroDescriptions = array();
                    }
                    echo '<h2 class="heading">Preliminary Microscopic Description</h2>';
                    if (empty($existingMicroDescriptions)) {
                        // Show Insert Form when no records exist
                        ?>
                        <form action="../transcription/preliminary_report/hpl/insert_micro_description.php" method="POST" class="micro-description-insert-form">
                            <?php foreach ($specimens_list as $index => $specimen) { ?>
                                <div class="form-group">
                                    <label for="specimen_<?php echo $index; ?>" class="bold-label">Specimen:</label>
                                    <textarea class="specimen-textarea" name="specimen[]" readonly><?php echo htmlspecialchars((string) $specimen['specimen']); ?></textarea>

                                    <div id="quill-editor-new-<?php echo $index; ?>" class="editor"></div>

                                    <!-- Hidden textarea to store Quill content -->
                                    <textarea style="display:none;" id="hidden_description_new_<?php echo $index; ?>" name="description[]"></textarea>
                                </div>
                            <?php } ?>

                            <!-- Hidden input fields -->
                            <input type="hidden" name="lab_number[]" value="<?php echo htmlspecialchars($LabNumberWithPrefix); ?>">
                            <input type="hidden" name="fk_gross_id[]" value="<?php echo htmlspecialchars($fk_gross_id); ?>">
                            <input type="hidden" name="created_user[]" value="<?php echo htmlspecialchars($loggedInUsername); ?>">
                            <input type="hidden" name="status[]" value="Done">
                            

                            <div class="grid">
                                <button type="submit" class="btn btn-success">Save</button>
                            </div>
                        </form>

                        <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
                        <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>

                        <script>
                            document.addEventListener("DOMContentLoaded", function () {
                                var forms = document.querySelectorAll(".micro-description-insert-form");

                                forms.forEach(function (form) {
                                    form.addEventListener("submit", function () {
                                        <?php foreach ($specimens_list as $index => $specimen) { ?>
                                            var quillEditor = document.querySelector("#quill-editor-new-<?php echo $index; ?> .ql-editor");
                                            if (quillEditor) {
                                                document.getElementById("hidden_description_new_<?php echo $index; ?>").value = quillEditor.innerHTML;
                                            }
                                        <?php } ?>
                                    });
                                });

                                <?php foreach ($specimens_list as $index => $specimen) { ?>
                                    var quill<?php echo $index; ?> = new Quill("#quill-editor-new-<?php echo $index; ?>", {
                                        theme: "snow"
                                    });

                                    quill<?php echo $index; ?>.on("text-change", function () {
                                        document.getElementById("hidden_description_new_<?php echo $index; ?>").value = quill<?php echo $index; ?>.root.innerHTML;
                                    });
                                <?php } ?>
                            });
                        </script>

                        <?php
                    }else {
                        foreach ($existingMicroDescriptions as $key => $existingDescription) {
                            $formId = 'microDescriptionForm' . $key;
                            ?>
                            <form action="../transcription/preliminary_report/hpl/update_micro_descriptions.php" id="<?php echo $formId; ?>" class="micro-description-form" method="POST">
                                <div class="form-group">
                                    <textarea class="specimen-textarea" row='1' name="specimen[]" readonly><?php echo htmlspecialchars($existingDescription['specimen']); ?></textarea>
                                    <!-- Quill Editor Container -->
                                    <div id="quill-editor-<?php echo $key; ?>" class="editor"></div>
                                    
                                    <!-- Hidden textarea for form submission -->
                                    <textarea style="display:none;" id="hidden_description<?php echo $key; ?>" name="description[]">
                                        <?php 
                                        $micro_pre_define_text = "Sections Show";
                                        $descriptionValue = !empty($existingDescription['description']) ? 
                                            htmlspecialchars($existingDescription['description']) : 
                                            $micro_pre_define_text;
                                        echo $descriptionValue; 
                                        ?>
                                    </textarea>
                                    
                                    <!-- Hidden fields -->
                                    <input type="hidden" name="fk_gross_id[]" value="<?php echo htmlspecialchars($existingDescription['fk_gross_id']); ?>">
                                    <input type="hidden" name="created_user[]" value="<?php echo htmlspecialchars($loggedInUsername); ?>">
                                    <input type="hidden" name="lab_number[]" value="<?php echo htmlspecialchars($existingDescription['lab_number']); ?>">
                                    <input type="hidden" name="row_id[]" value="<?php echo htmlspecialchars($existingDescription['row_id']); ?>">
                                </div>
                                <div class="grid">
                                    <button type="submit" class="btn btn-primary">Save</button>
                                </div><br>
                            </form>
                            <?php
                        }
                    }
                ?>
            </div>
            <div id="DiagnosisFrom">
                <!-- Diagnosis Description -->
                <?php
                    $existingDiagnosisDescriptions = getExistingPreliminaryReportDiagnosisDescriptions($LabNumberWithPrefix);
                    $specimens_list = get_gross_specimens_list($LabNumber);

                    // Ensure $existingDiagnosisDescriptions is an array
                    if (!is_array($existingDiagnosisDescriptions)) {
                        $existingDiagnosisDescriptions = array();
                    }

                    echo '<h2 class="heading text-center mb-4">Diagnosis Description</h2>';

                    if (empty($existingDiagnosisDescriptions)) {
                        // Show Insert Form when no records exist
                        ?>
                        <form action="../transcription/preliminary_report/hpl/insert_diagnosis_description.php" id="diagnosisInsertDescriptionForm" method="POST" class="diagnosisInsertDescriptionForm">
                            <?php foreach ($specimens_list as $index => $specimen) { ?>
                                <div class="form-group row">
                                    <label for="diagnosis_specimen_<?php echo $index; ?>" class="col-sm-3 col-form-label font-weight-bold">Specimen:</label>
                                    <div class="col-sm-9">
                                        <textarea class="form-control diagnosis-specimen-textarea" name="specimen[]" readonly><?php echo htmlspecialchars((string) $specimen['specimen']); ?></textarea>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="title" class="col-sm-3 col-form-label font-weight-bold">Title:</label>
                                    <div class="col-sm-9">
                                        <?php $titleValue = !empty($specimen['title']) ? htmlspecialchars($specimen['title']) : 'biopsy'; ?>
                                        <input type="text" name="title[]" class="form-control" value="<?php echo $titleValue; ?>">
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="description" class="col-sm-3 col-form-label font-weight-bold">Description:</label>
                                    <div class="col-sm-9">
                                        <div id="diagnosis-quill-editor-new-<?php echo $index; ?>" class="editor mb-3"></div>
                                        <textarea style="display:none;" id="diagnosis_hidden_description_new_<?php echo $index; ?>" name="description[]"></textarea>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="comment" class="col-sm-3 col-form-label font-weight-bold">Comment:</label>
                                    <div class="col-sm-9">
                                        <div id="comment-quill-editor-<?php echo $index; ?>" class="editor mb-3"></div>
                                        <textarea name="comment[]" id="comment-textarea-<?php echo $index; ?>" style="display:none;"><?php echo htmlspecialchars($comment); ?></textarea>
                                    </div>
                                </div>
                            <?php } ?>

                            <!-- Hidden input fields -->
                            <input type="hidden" name="lab_number[]" value="<?php echo htmlspecialchars($LabNumberWithPrefix); ?>">
                            <input type="hidden" name="fk_gross_id[]" value="<?php echo htmlspecialchars($fk_gross_id); ?>">
                            <input type="hidden" name="created_user[]" value="<?php echo htmlspecialchars($loggedInUsername); ?>">
                            <input type="hidden" name="status[]" value="Done">

                            <div class="text-center">
                                <button type="submit" class="btn btn-success btn-lg">Save</button>
                            </div>
                        </form>

                        <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
                        <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>

                        <script>
                            document.addEventListener("DOMContentLoaded", function () {
                                var forms = document.querySelectorAll(".diagnosisInsertDescriptionForm");

                                forms.forEach(function (form) {
                                    form.addEventListener("submit", function () {
                                        <?php foreach ($specimens_list as $index => $specimen) { ?>
                                            var quillEditor = document.querySelector("#diagnosis-quill-editor-new-<?php echo $index; ?> .ql-editor");
                                            if (quillEditor) {
                                                document.getElementById("diagnosis_hidden_description_new_<?php echo $index; ?>").value = quillEditor.innerHTML;
                                            }

                                            var commentEditor = document.querySelector("#comment-quill-editor-<?php echo $index; ?> .ql-editor");
                                            if (commentEditor) {
                                                document.getElementById("comment-textarea-<?php echo $index; ?>").value = commentEditor.innerHTML;
                                            }
                                        <?php } ?>
                                    });
                                });

                                <?php foreach ($specimens_list as $index => $specimen) { ?>
                                    var quill<?php echo $index; ?> = new Quill("#diagnosis-quill-editor-new-<?php echo $index; ?>", {
                                        theme: "snow"
                                    });

                                    quill<?php echo $index; ?>.on("text-change", function () {
                                        document.getElementById("diagnosis_hidden_description_new_<?php echo $index; ?>").value = quill<?php echo $index; ?>.root.innerHTML;
                                    });

                                    var commentQuill<?php echo $index; ?> = new Quill("#comment-quill-editor-<?php echo $index; ?>", {
                                        theme: "snow"
                                    });

                                    commentQuill<?php echo $index; ?>.on("text-change", function () {
                                        document.getElementById("comment-textarea-<?php echo $index; ?>").value = commentQuill<?php echo $index; ?>.root.innerHTML;
                                    });
                                <?php } ?>
                            });
                        </script>

                        <?php
                    } else {
                        ?>
                        <form action="" id="diagnosisDescriptionForm" method="POST">
                            <?php foreach ($existingDiagnosisDescriptions as $index => $specimen): ?>
                                <?php
                                    // Prepare fallback values if some fields are missing
                                    $description = $specimen['description'] ?? '';
                                    $title = $specimen['title'] ?? 'biopsy';
                                    $comment = $specimen['comment'] ?? '';
                                    $fk_gross_id = $specimen['fk_gross_id'] ?? '';
                                    $status = $specimen['status'] ?? '';
                                    $lab_number = $specimen['lab_number'] ?? '';
                                    $row_id = $specimen['row_id'] ?? '';
                                    $specimen_text = $specimen['specimen'] ?? '';
                                    $specimen_id = $specimen['specimen_id'] ?? '';
                                ?>

                                <!-- Specimen display -->
                                <div class="form-group row">
                                    <label for="specimen" class="col-sm-3 col-form-label font-weight-bold">Specimen:</label>
                                    <div class="col-sm-9">
                                        <input type="hidden" name="specimen_id[]" value="<?= htmlspecialchars($specimen_id) ?>" readonly>
                                        <input type="text" name="specimen[]" class="form-control" value="<?= htmlspecialchars($specimen_text) ?>" readonly>
                                    </div>
                                </div>

                                <!-- Title field -->
                                <div class="form-group row">
                                    <label for="title" class="col-sm-3 col-form-label font-weight-bold">Title:</label>
                                    <div class="col-sm-9">
                                        <input type="text" name="title[]" class="form-control" value="<?= htmlspecialchars($title) ?>">
                                    </div>
                                </div>

                                <!-- Description field with Quill editor -->
                                <div class="form-group row">
                                    <label for="description" class="col-sm-3 col-form-label font-weight-bold">Description:</label>
                                    <div class="col-sm-9">
                                        <div id="diagnosis-quill-editor-<?= $index ?>" class="editor mb-3"><?= $description ?></div>
                                        <textarea name="description[]" id="diagnosis-textarea-<?= $index ?>" style="display:none;"><?= htmlspecialchars($description) ?></textarea>
                                    </div>
                                </div>

                                <!-- Comment field with Quill editor -->
                                <div class="form-group row">
                                    <label for="comment" class="col-sm-3 col-form-label font-weight-bold">Comment:</label>
                                    <div class="col-sm-9">
                                        <div id="comment-quill-editor-<?= $index ?>" class="editor mb-3"><?= $comment ?></div>
                                        <textarea name="comment[]" id="comment-textarea-<?= $index ?>" style="display:none;"><?= htmlspecialchars($comment) ?></textarea>
                                    </div>
                                </div>

                                <!-- Hidden fields for additional metadata -->
                                <input type="hidden" name="fk_gross_id[]" value="<?= htmlspecialchars($fk_gross_id) ?>">
                                <input type="hidden" name="created_user[]" value="<?php echo htmlspecialchars($loggedInUsername); ?>">
                                <input type="hidden" name="status[]" value="<?= htmlspecialchars($status) ?>">
                                <input type="hidden" name="lab_number[]" value="<?= htmlspecialchars($lab_number) ?>">
                                <input type="hidden" name="row_id[]" value="<?= htmlspecialchars($row_id) ?>">

                            <?php endforeach; ?>

                            <div class="text-center">
                            <br><br><button id="diagnosisDescriptionSaveButton" type="submit" name="submit" value="att_relation" class="btn btn-primary btn-lg">Save</button>
                            </div>
                        </form>
                        <?php
                    }
                ?>
            </div>
            
            

        </div>

        <!-- Final Form -->
        <div id="finalForm" style="display: none;">
            <div class="form-group">
            <label for="finalDataInput">Enter Data (Final)</label>
            <input type="text" class="form-control" id="finalDataInput" name="finalDataInput">
            </div>
            <div class="form-group">
            <label for="finalDescriptionInput">Description</label>
            <textarea class="form-control" id="finalDescriptionInput" name="finalDescriptionInput"></textarea>
            </div>
            <button type="submit" class="btn btn-success">Save Final</button>
        </div>

    </div>

    
  </div>
</div>



<!-- Modal for Status-->
<div class="modal fade" id="exampleModalCenter" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
    
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalCenterTitle">
           Current Status: <?php echo htmlspecialchars($LabNumber); ?>
        </h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      
      <div class="modal-body">
        <!-- Content Starts -->
            <div class="tab-content">
                <table border="0">
                    <thead>
                    <tr>
                        <th>Status</th>
                        <th>Descriptions</th>
                        <th>Time</th>
                        <th>User</th>
                        <th>Delete</th>
                    </tr>
                    </thead>
                    <tbody id="status-table-body">
                    <?php 
                    $shownStatuses = [];
                    $statusValues = array_column($lab_status, 'WSStatusName');
                    $sortedRows = [];

                    foreach ($lab_status as $list) {
                        $statusName = $list['WSStatusName'];

                        // Skip duplicates
                        $uniqueKey = $statusName . $list['description'] . $list['TrackCreateTime'];
                        if (in_array($uniqueKey, $shownStatuses)) {
                        continue;
                        }
                        $shownStatuses[] = $uniqueKey;

                        // Skip unwanted statuses
                        if (in_array($statusName, [
                        'Diagnosis Completed', 'Start Screening', 'Final Screening Start'
                        ])) {
                        continue;
                        }

                        // Only process certain sections
                        if (!in_array($list['section'], ['Gross', 'Lab', 'Microscopy', 'Screening', 'description'])) {
                        continue;
                        }

                        // Determine color
                        $statusColor = '';
                        $requestedCompletedPairs = [
                        'Special Stain others requested' => 'Special Stain others Completed',
                        'IHC-Block-Markers-requested' => 'IHC-Block-Markers-completed',
                        'R/C requested' => 'R/C Completed',
                        'M/R/C requested' => 'M/R/C Completed',
                        'Deeper Cut requested' => 'Deeper Cut Completed',
                        'Serial Sections requested' => 'Serial Sections Completed',
                        'Block D/C & R/C requested' => 'Block D/C & R/C Completed',
                        'Special Stain AFB requested' => 'Special Stain AFB Completed',
                        'Special Stain GMS requested' => 'Special Stain GMS Completed',
                        'Special Stain PAS requested' => 'Special Stain PAS Completed',
                        'Special Stain PAS with Diastase requested' => 'Special Stain PAS with Diastase Completed',
                        'Special Stain Fite Faraco requested' => 'Special Stain Fite Faraco Completed',
                        'Special Stain Brown-Brenn requested' => 'Special Stain Brown-Brenn Completed',
                        'Special Stain Congo-Red requested' => 'Special Stain Congo-Red Completed',
                        'Special Stain Bone Decalcification requested' => 'Special Stain Bone Decalcification Completed',
                        'Re-gross Requested' => 'Regross Completed'
                        ];

                        if (array_key_exists($statusName, $requestedCompletedPairs)) {
                        if (!in_array($requestedCompletedPairs[$statusName], $statusValues)) {
                            $statusColor = 'red';
                        }
                        } elseif (in_array($statusName, array_values($requestedCompletedPairs))) {
                        $statusColor = 'green';
                        }

                        // Date formatting
                        try {
                        $dateTime = new DateTime($list['TrackCreateTime'], new DateTimeZone('UTC'));
                        $dateTime->setTimezone(new DateTimeZone('Asia/Dhaka'));
                        $formattedTime = $dateTime->format('F j, Y g:i A');
                        } catch (Exception $e) {
                        $formattedTime = 'Invalid date';
                        }

                        // Store cleaned data for output
                        $sortedRows[] = [
                        'statusName' => htmlspecialchars($statusName),
                        'description' => htmlspecialchars($list['description']),
                        'trackCreateTime' => $formattedTime,
                        'user' => htmlspecialchars($list['TrackUserName']),
                        'color' => $statusColor,
                        'track_id' => htmlspecialchars($list['track_id'])
                        ];
                    }

                    // Sort by color: red first, then green, then others
                    usort($sortedRows, function($a, $b) {
                        $priority = ['red' => 0, 'green' => 1, '' => 2];
                        return $priority[$a['color']] <=> $priority[$b['color']];
                    });

                    // Show all rows
                    foreach ($sortedRows as $row) {
                        echo "<tr>";
                        echo "<td><p style='font-size: 15px; color: {$row['color']};'>{$row['statusName']}</p></td>";
                        echo "<td><p style='font-size: 15px;'>{$row['description']}</p></td>";
                        echo "<td><p style='font-size: 15px;'>{$row['trackCreateTime']}</p></td>";
                        echo "<td><p style='font-size: 15px;'>{$row['user']}</p></td>";
                        echo "<td><p style='font-size: 15px;'>
                                <a href='#' onclick='confirmDelete({$row['track_id']})'>
                                <i class='fas fa-trash-alt' style='color: red; cursor: pointer;' title='Delete'></i>
                                </a>
                            </p></td>";
                        echo "</tr>";
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        <!-- Content Ends -->
      </div>
    </div>
  </div>
</div>







<!-- Handel form for user select value -->
<script>
    // Handle form type selection (Preliminary vs Final)
    document.addEventListener('DOMContentLoaded', function () {
        const formTypeSelect = document.getElementById('formTypeSelect');
        const prelimForm = document.getElementById('preliminaryForm');
        const finalForm = document.getElementById('finalForm');

        formTypeSelect.addEventListener('change', function () {
            const selected = this.value;
            if (selected === 'preliminary') {
                prelimForm.style.display = 'block';
                finalForm.style.display = 'none';
            } else if (selected === 'final') {
                prelimForm.style.display = 'none';
                finalForm.style.display = 'block';
            }
        });

        // Handle sub-form selection inside Preliminary form (Microscopic vs Diagnosis)
        const editTypeSelect = document.getElementById("formEditTypeSelect");
        const microscopicForm = document.getElementById("MicroscopicFrom");
        const diagnosisForm = document.getElementById("DiagnosisFrom");
        const clinicalForm = document.getElementById("ClinicalDetailsForm");

        function toggleSubForms() {
            const selectedType = editTypeSelect.value;
            if (selectedType === "Microscopic") {
                microscopicForm.style.display = "block";
                diagnosisForm.style.display = "none";
                clinicalForm.style.display = "none";
            } else if (selectedType === "Diagnosis") {
                microscopicForm.style.display = "none";
                diagnosisForm.style.display = "block";
                clinicalForm.style.display = "none";
            }
            else if (selectedType === "Clinical Details") {
                microscopicForm.style.display = "none";
                diagnosisForm.style.display = "none";
                clinicalForm.style.display = "block";
            }
        }

        if (editTypeSelect) {
            toggleSubForms(); // Initial call
            editTypeSelect.addEventListener("change", toggleSubForms);
        }
    });
</script>


   

    
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
                                        // alert("Data saved successfully.");
                                        // window.location.reload();
                                        // Create success message at the bottom
                                        showSuccessMessage("Data saved successfully.");

                                        // Optionally, reload the page after a short delay
                                        setTimeout(function() {
                                            window.location.reload();
                                        }, 2000);
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
                                    showSuccessMessage("Data saved successfully.");

                                        // Optionally, reload the page after a short delay
                                        setTimeout(function() {
                                            window.location.reload();
                                        }, 2000);
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

            // Function to show success message at the bottom
            function showSuccessMessage(message) {
                // Create a new div element for the message
                var successMessageDiv = document.createElement('div');
                successMessageDiv.classList.add('success-message');
                successMessageDiv.textContent = message;

                // Style the success message
                successMessageDiv.style.position = 'fixed';
                successMessageDiv.style.bottom = '20px';
                successMessageDiv.style.left = '50%';
                successMessageDiv.style.transform = 'translateX(-50%)';
                successMessageDiv.style.backgroundColor = '#4CAF50';
                successMessageDiv.style.color = 'white';
                successMessageDiv.style.padding = '10px 20px';
                successMessageDiv.style.borderRadius = '5px';
                successMessageDiv.style.fontSize = '16px';
                successMessageDiv.style.zIndex = '9999';

                // Append the success message to the body
                document.body.appendChild(successMessageDiv);

                // Remove the success message after 3 seconds
                setTimeout(function() {
                    successMessageDiv.style.display = 'none';
                }, 3000);
            }

        </script>
        
        <script>
            $(document).ready(function() {
                // Retrieve the lab numbers from PHP
                const cytoLab = <?php echo json_encode(get_cyto_labnumber_list()); ?>;

                function checkLabNumberAndRedirect(labno) {
                    if (labno) {
                        
                        // Check if the labno exists in cytoLab
                        const found = cytoLab.some(lab => lab.lab_number === labno);

                        if (found) {
                            
                            // Redirect to cytoindex.php if labno is valid
                            window.location.href = 'Cyto/index.php?labno=' + labno;
                        } else {
                            
                            window.location.href = 'lab_status.php?labno=' + labno;
                        }
                    } else {
                        console.error("Lab number is empty. No redirection performed.");
                    }
                }

                $('#readlabno').on('submit', function(e) {
                    e.preventDefault();
                    let labno = $('#labno').val();
                    checkLabNumberAndRedirect(labno);
                });

                $('#tab-screening, #tab-final-screening, #tab-status').on('click', function() {
                    let labno = $('#labno').val();
                    checkLabNumberAndRedirect(labno);
                });
            });
        </script>
        
        <script>
            function confirmDelete(track_id) {
                if (confirm('Are you sure you want to delete this entry?')) {
                    // Get loggedInUserId from PHP session (make sure it's defined in your session)
                    const loggedInUserId = "<?php echo isset($loggedInUserId) ? $loggedInUserId : ''; ?>";
                    const labnumber = "<?php echo isset($LabNumber) ? $LabNumber : '' ; ?>";

                    // Prepare data to send
                    const data = {
                        loggedInUserId: loggedInUserId,
                        values: {
                            [track_id]: {
                                labNumber: labnumber,
                                status: 'Delete'
                            }
                        }
                    };

                    // Send data via AJAX to delete_entry.php
                    fetch("insert/delete_entry.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json"
                        },
                        body: JSON.stringify(data)
                    })
                    .then(response => response.text())
                    .then(text => {
                        return JSON.parse(text);
                    })
                    .then(result => {
                        if (result.success) {
                            alert(result.message);
                            window.location.reload(); // Reload the page to reflect the deletion
                        } else {
                            alert('Error: ' + (result.message || 'Failed to delete the entry.'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
                } else {
                    return false; // Do nothing if the user cancels
                }
            }
        </script>

        <script>
            // Function to show only the report iframe and hide the status
            function loadReport() {
                var labNumber = "<?php echo htmlspecialchars('HPL' . $LabNumber, ENT_QUOTES, 'UTF-8'); ?>";
                var iframe = document.getElementById('reportFrame');
                
                // Set the report URL with the lab number and make the iframe visible
                iframe.src = "../grossmodule/hpl_report.php?lab_number=" + labNumber;
                iframe.style.display = "block";  // Show the report iframe

                // Hide the status section when the report is shown
                document.getElementById('status').style.display = 'none';
            }

            // Function to show only the status table and hide the report
            function showRightTab(tabId) {
                if (tabId === 'status') {
                    // Hide the report iframe
                    document.getElementById('reportFrame').style.display = 'none';

                    // Show the status section
                    var statusSection = document.getElementById('status');
                    statusSection.style.display = 'block'; // Ensure it's visible
                }
            }

            // Function to toggle the status tab (if needed for active/inactive state)
            // function toggleStatusTab() {
            //             var statusTab = document.getElementById('status');
            //             var statusButton = document.getElementById('tab-status');
                        
            //             if (statusTab.classList.contains('grayed-out')) {
            //                 statusTab.classList.remove('grayed-out');
            //                 statusButton.classList.remove('inactive');
            //                 statusButton.classList.add('active');
            //             } else {
            //                 statusTab.classList.add('grayed-out');
            //                 statusButton.classList.add('inactive');
            //                 statusButton.classList.remove('active');
            //             }
                        
            //             // Show or hide the status tab content based on its state
            //             var isGrayedOut = statusTab.classList.contains('grayed-out');
            //             var rows = document.querySelectorAll('#status-table-body .status-row');
                        
            //             rows.forEach(function(row, index) {
            //                 if (isGrayedOut && index < rows.length - 2) {
            //                     row.classList.add('hidden');
            //                 } else {
            //                     row.classList.remove('hidden');
            //                 }
            //             });
                        
            // }

            function loadPreliminaryReport() {
                var labNumber = "<?php echo htmlspecialchars('HPL' . $LabNumber, ENT_QUOTES, 'UTF-8'); ?>";
                var iframe = document.getElementById('reportFrame');
                
                // Set the report URL with the lab number and make the iframe visible
                iframe.src = "../transcription/preliminary_report/hpl/report.php?lab_number=" + labNumber;
                iframe.style.display = "block";  // Show the report iframe

                // Hide the status section when the report is shown
                document.getElementById('status').style.display = 'none';
            }

             // Automatically load preliminary report on page load

             document.addEventListener('DOMContentLoaded', loadPreliminaryReport);
        </script>


<!-- Clinical Details -->
<script>

    // Clinical Details
    document.addEventListener('keydown', function(event) {
        // Check for Ctrl + S (or Command + S on Mac)
        if (event.ctrlKey && event.key === 's') {
            event.preventDefault(); // Prevent default behavior of Enter key
            var updateBtn = document.getElementById("updateBtn");
            var saveBtn = document.getElementById("saveBtn");
                if (updateBtn.style.display === "inline-block") {
                    updateBtn.click();
                } else {
                    saveBtn.click();
                } 
        }
    });

    document.addEventListener("DOMContentLoaded", function() {
        // Fetch existing clinical details using AJAX when the page loads
        fetchExistingClinicalDetails();

        function fetchExistingClinicalDetails() {
            // Get the lab number from the hidden input field
            var labNumber = document.getElementById("labNumberInput").value;

            // Make an AJAX request to fetch existing clinical details
            var xhr = new XMLHttpRequest();
            xhr.open("GET", "../transcription/get_clinical_details.php?lab_number=" + labNumber, true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    // Parse the JSON response
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        // Populate the textarea with existing clinical details
                        document.getElementById("clinicalDetailsTextarea").value = response.data.clinical_details;
                        // Toggle visibility of Save and Update buttons based on whether data exists
                        if (response.data.clinical_details) {
                            document.getElementById("saveBtn").style.display = "none";
                            document.getElementById("updateBtn").style.display = "inline-block";
                        } else {
                            document.getElementById("saveBtn").style.display = "inline-block";
                            document.getElementById("updateBtn").style.display = "none";
                        }
                    } else {
                        console.error("Error fetching existing clinical details:", response.error);
                    }
                }
            };
            xhr.send();
        }
    });
</script>


<!-- Micro Description Update  -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script>
    // Micro Description Update 

    document.addEventListener('keydown', function(event) {
            // Check for Ctrl + S (or Command + S on Mac)
            if (event.ctrlKey && event.key === 's') {
                event.preventDefault(); // Prevent default behavior of Enter key
                document.getElementById('micro-button').click(); // Submit the form 
            }
    });


    document.addEventListener('DOMContentLoaded', function() {
        fetch('shortcuts.json')
            .then(response => response.json())
            .then(shortcuts => {
                function handleShortcutInput(inputElement, cursorPosition) {
                    let text = inputElement.value;
                    let wordStart = text.lastIndexOf(' ', cursorPosition - 1) + 1;
                    let wordEnd = cursorPosition;

                    let word = text.substring(wordStart, wordEnd).trim();

                    if (shortcuts[word]) {
                        inputElement.value = text.substring(0, wordStart) + shortcuts[word] + text.substring(wordEnd);
                        inputElement.selectionEnd = wordStart + shortcuts[word].length;
                    }
                }

                document.querySelectorAll('textarea').forEach(textarea => {
                    textarea.addEventListener('keydown', function(event) {
                        if (event.key === 'Insert') { // Insert key
                            let cursorPosition = this.selectionStart;
                            handleShortcutInput(this, cursorPosition);
                        }
                    });
                });
            })
            .catch(error => console.error('Error loading shortcuts:', error));
    });


    document.addEventListener('DOMContentLoaded', function() {
        // Initialize all Quill editors
        <?php foreach ($existingMicroDescriptions as $key => $existingDescription): ?>
            var quillEditor<?php echo $key; ?> = new Quill('#quill-editor-<?php echo $key; ?>', {
                theme: 'snow',
                modules: {
                    toolbar: [
                        ['bold', 'italic', 'underline'],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        ['clean']
                    ]
                }
            });

            // Set the initial content from hidden textarea
            var hiddenTextarea<?php echo $key; ?> = document.getElementById('hidden_description<?php echo $key; ?>');
            quillEditor<?php echo $key; ?>.root.innerHTML = hiddenTextarea<?php echo $key; ?>.value;

            // Update hidden textarea when editor content changes
            quillEditor<?php echo $key; ?>.on('text-change', function() {
                hiddenTextarea<?php echo $key; ?>.value = quillEditor<?php echo $key; ?>.root.innerHTML;
            });
        <?php endforeach; ?>

        // Update your form submission handler
        document.querySelectorAll("form[id^='microDescriptionForm']").forEach(function(form) {
            form.addEventListener("submit", function(event) {
                event.preventDefault();
                
                // Update all hidden textareas before submission
                var formId = this.id;
                var key = formId.replace('microDescriptionForm', '');
                var quillEditor = new Quill('#quill-editor-' + key);
                document.getElementById('hidden_description' + key).value = quillEditor.root.innerHTML;

                const formData = new FormData(this);
                
                fetch("../transcription/preliminary_report/hpl/update_micro_descriptions.php", {
                    method: "POST",
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    window.location.reload();
                })
                .catch(error => {
                    console.error("Error:", error);
                });
            });
        });
    });
</script>

<!-- Diagnosis Description -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Ctrl+S to submit the form globally
        document.addEventListener('keydown', function (event) {
            if ((event.ctrlKey || event.metaKey) && event.key === 's') {
                event.preventDefault();
                document.getElementById('diagnosisDescriptionSaveButton').click();
            }
        });

        // Load and handle text shortcuts
        fetch('shortcuts.json')
            .then(response => response.json())
            .then(shortcuts => {
                function handleShortcutInput(inputElement, cursorPosition) {
                    let text = inputElement.value;
                    let wordStart = text.lastIndexOf(' ', cursorPosition - 1) + 1;
                    let wordEnd = cursorPosition;
                    let word = text.substring(wordStart, wordEnd).trim();

                    if (shortcuts[word]) {
                        inputElement.value = text.substring(0, wordStart) + shortcuts[word] + text.substring(wordEnd);
                        inputElement.selectionEnd = wordStart + shortcuts[word].length;
                    }
                }

                document.querySelectorAll('textarea').forEach(textarea => {
                    textarea.addEventListener('keydown', function (event) {
                        if (event.key === 'Insert') {
                            let cursorPosition = this.selectionStart;
                            handleShortcutInput(this, cursorPosition);
                        }

                        if ((event.ctrlKey || event.metaKey) && event.key === 's') {
                            event.preventDefault();
                            this.closest('form').submit();
                        }
                    });
                });
            })
            .catch(error => console.error('Error loading shortcuts:', error));

        // Initialize Quill editors and set their content
        <?php foreach ($existingDiagnosisDescriptions as $index => $specimen): ?>
            var descEditor<?= $index ?> = new Quill('#diagnosis-quill-editor-<?= $index ?>', {
                theme: 'snow',
                modules: {
                    toolbar: [
                        
                    ]
                }
            });

            var commentEditor<?= $index ?> = new Quill('#comment-quill-editor-<?= $index ?>', {
                theme: 'snow',
                modules: {
                    toolbar: [
                        
                    ]
                }
            });

            // Set unique content for each editor
            descEditor<?= $index ?>.root.innerHTML = <?= json_encode($specimen['description'] ?? '') ?>;
            commentEditor<?= $index ?>.root.innerHTML = <?= json_encode($specimen['comment'] ?? '') ?>;
        <?php endforeach; ?>

        // Handle form submission
        document.getElementById("diagnosisDescriptionForm").addEventListener("submit", function (event) {
            event.preventDefault();

            // Update hidden fields with Quill content before submitting
            <?php foreach ($existingDiagnosisDescriptions as $index => $specimen): ?>
                document.getElementById('diagnosis-textarea-<?= $index ?>').value =
                    descEditor<?= $index ?>.root.innerHTML;
                document.getElementById('comment-textarea-<?= $index ?>').value =
                    commentEditor<?= $index ?>.root.innerHTML;
            <?php endforeach; ?>

            const formData = new FormData(this);

            fetch("../transcription/preliminary_report/hpl/update_diagnosis_descriptions.php", {
                method: "POST",
                body: formData
            })
                .then(response => response.text())
                .then(data => {
                    window.location.reload();
                })
                .catch(error => {
                    console.error("Error:", error);
                });
        });
    });
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