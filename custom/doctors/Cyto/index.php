<?php 

include('../connection.php');
include('../../transcription/common_function.php');
include('../../grossmodule/gross_common_function.php');
include('../../histolab/histo_common_function.php');
include('../list_of_function.php');

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


$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

$max = 5;
$now = dol_now();


$form = new Form($db);
$formfile = new FormFile($db);

llxHeader("", $langs->trans("DoctorsArea"));


$LabNumber = $_GET['labno'];
$lab_status = get_lab_number_status_for_doctor_tracking_by_lab_number($LabNumber);
$labStatus = json_encode($lab_status);

$bone_status = get_bone_status_lab_number("HPL" . $LabNumber);
$boneStatus = json_encode($bone_status);

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

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link href="../../grossmodule/bootstrap-3.4.1-dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="../../grossmodule/bootstrap-3.4.1-dist/js/bootstrap.min.js"></script>
    <style>
        .custom-flex-container {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 10px; /* Adds space between items */
        }

        .custom-btn {
            border: none;
            background-color: white;
            color: black;
            padding: 8px 16px;
            font-size: 16px;
            cursor: pointer;
        }

        .custom-form {
            display: flex;
            align-items: center;
        }

        .custom-label {
            margin-right: 8px;
        }

        .form-control {
            padding: 5px 10px;
            font-size: 16px;
        }
        .disabled-section {
            pointer-events: none;
            opacity: 0.5;
        }

    </style>
    <style>
        /* Custom Modal Styling */
        .custom-modal {
            display: none; /* Hidden by default */
            position: fixed;
            z-index: 1; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto; /* Enable scroll if needed */
            background-color: rgb(0, 0, 0); /* Fallback color */
            background-color: rgba(0, 0, 0, 0.4); /* Black with opacity */
        }

        /* Modal Content */
        .custom-modal-content {
            background-color: #fff;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%; /* Could be more or less, depending on screen size */
            max-width: 500px;
            border-radius: 8px;
        }

        /* Modal Header */
        .custom-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .custom-modal-header h5 {
            margin: 0;
        }

        /* Close Button */
        .custom-modal-close {
            font-size: 1.5rem;
            cursor: pointer;
            background: none;
            border: none;
            color: #aaa;
            transition: color 0.3s;
        }

        .custom-modal-close:hover {
            color: black;
        }

        /* Footer Buttons */
        .custom-modal-footer {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        /* Button Styling */
        button {
            padding: 10px 20px;
            background-color: #007bff;
            border: none;
            color: white;
            font-size: 1rem;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #0056b3;
        }

        button:disabled {
            background-color: #ccc;
        }


    </style>
</head>
<body>
    
    <div class=" text-center mt-5 ">
        <h3>Cytopathology</h3>
    </div>

    <div class="container custom-flex-container">
            <a href="../doctorsindex.php">
                <button style="border:none; background-color: white; color: black;" class="custom-btn">
                    <i class="fas fa-home" aria-hidden="true"></i> Doctors
                </button>
            </a>
            
            <form name="readlabno" id="readlabno" action="" class="custom-form">
                <label for="labno" class="custom-label">LabNo:</label>
                <input type="text" id="labno" name="labno" autofocus class="form-control">
            </form>

            <button style="border:none; background-color: white; color: black;" class="custom-btn" onclick="loadReport()">
                <i class="fas fa-file-alt" aria-hidden="true"></i> Report
            </button>

            <a href="../../transcription/transcription.php?lab_number=<?php echo 'HPL' . $LabNumber; ?>">
                <button style="border:none; background-color: white; color: black;" class="custom-btn">
                    <i class="fas fa-edit" aria-hidden="true"></i> Edit
                </button>
            </a>

            <button style="border:none; font-size: 20px;" id="tab-status" class="inactive custom-btn" onclick="toggleStatusTab(), showRightTab('status')">
                <i class="fa fa-search" aria-hidden="true"></i>Status
            </button>
    </div>
     
    <div class="container" style="margin-top: 20px;">
        <div class="row">
            <!-- Left Side Section -->
            <div class="col-md-6">
                
                    <div class="col-md-6" id="screening-section">
                        <div id="screening-section">
                            <h4 class="mt-3" style="cursor: pointer;" id="screening-header">
                                <i class="fas fa-microscope text-primary mr-2"></i> Screening
                            </h4>
                        </div>
                        <div id="study-history-section">
                            <h4 class="mt-3" style="cursor: pointer;" id="study-history-header">
                                <i class="fas fa-book-open mr-2"></i> Study / History
                            </h4>
                        </div>
                        <div id="lab-instructions-section">
                            <h4 class="mt-3" style="cursor: pointer;" id="lab-instructions-header">
                                <i class="fas fa-flask mr-2"></i> Lab Instructions
                            </h4>
                        </div>
                        <div id="cyto-instruction-section">
                            <h4 class="mt-3" style="cursor: pointer;" id="cyto-instruction-header">
                                <i class="fas fa-undo mr-2"></i> Recall Instruction
                            </h4>
                        </div>
                        <div id="screening-done-section">
                            <h4 class="mt-3" style="cursor: pointer;" id="screening-done-header">
                                <i class="fas fa-check-circle mr-2"></i> Screening Done
                            </h4>
                        </div>
                    </div>

                    <div class="col-md-6" id="finalization-section" class="disabled-section">
                        <div id="finalization-section">
                            <h4 class="mt-3" style="cursor: pointer;" id="finalization-header">
                                <i class="fas fa-microscope text-success mr-2"></i> Finalization
                            </h4>
                        </div>
                        <div id="final-study-history-section">
                            <h4 class="mt-3" style="cursor: pointer;" id="final-study-history-header">
                                <i class="fas fa-book-open mr-2"></i> Study / History
                            </h4>
                        </div>
                        <div id="final-lab-instructions-section">
                            <h4 class="mt-3" style="cursor: pointer;" id="final-lab-instructions-header">
                                <i class="fas fa-flask mr-2"></i> Lab Instructions
                            </h4>
                        </div>
                        <div id="final-cyto-instruction-section">
                            <h4 class="mt-3" style="cursor: pointer;" id="final-cyto-instruction-header">
                                <i class="fas fa-undo mr-2"></i> Recall Instruction
                            </h4>
                        </div>
                        <div id="finalization-done-section">
                            <h4 class="mt-3" style="cursor: pointer;" id="finalization-done-header">
                                <i class="fas fa-check-circle mr-2"></i> Finalization Done
                            </h4>
                        </div>
                    </div>
            </div>

            <!-- Right Side Section -->
            <div class="col-md-6">
                <h1>This is Right Side</h1>
                <div class="col-md-6">
                    <h3>This is inside Right Side left side</h3>
                </div>
                <div class="col-md-6">
                    <h3>This is inside Right Side right side</h3>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Screening study and history -->
    <div class="container" style="margin-top: 20px;">
           <!-- Separate Tab that appears when clicked -->
            <div class="row" id="study-history-tab" style="display: none;">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <h3>Study/History</h3>
                            <!-- Study Choice (Checkbox) -->
                            <div id="study-choice">
                                <input type="checkbox" id="study-checkbox" value="study"/>&nbsp;&nbsp;<b>Study</b>
                            </div>

                            <!-- Patient History / Investigations -->
                            <div id="patient-history" style="margin-top: 20px;">
                                <h5><b>Patient History / Investigations:</b></h5>

                                <!-- First Row of Options (Horizontal) -->
                                <div class="row" style="margin-top: 10px;">
                                    <div class="col-md-4">
                                        <label><input type="checkbox" class="history-option" value="self" /> Self</label>
                                    </div>
                                    <div class="col-md-4">
                                        <label><input type="checkbox" class="history-option" value="transcription" /> Transcription</label>
                                    </div>
                                    <div class="col-md-4">
                                        <label><input type="checkbox" class="history-option" value="dispatch-center" /> Dispatch Center</label>
                                    </div>
                                </div>

                                <!-- Second Row of Options (Horizontal) -->
                                <div class="row mt-3" style="margin-top: 10px;">
                                    <div class="col-md-4">
                                        <label><input type="checkbox" class="history-option" value="ultrasonography" /> Ultrasonography</label>
                                    </div>
                                    <div class="col-md-4">
                                        <label><input type="checkbox" class="history-option" value="xray" /> X-Ray</label>
                                    </div>
                                    <div class="col-md-4">
                                        <label><input type="checkbox" class="history-option" value="ct-scan-report" /> CT Scan Report</label>
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-4">
                                        <label><input type="checkbox" class="history-option" value="ct-scan-film" /> CT Scan Film</label>
                                    </div>
                                    <div class="col-md-4">
                                        <label><input type="checkbox" class="history-option" value="mri-report" /> MRI Report</label>
                                    </div>
                                    <div class="col-md-4">
                                        <label><input type="checkbox" class="history-option" value="mri-film" /> MRI Film</label>
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-4">
                                        <label>
                                            <input type="checkbox" id="other-checkbox" class="history-option" value="other" /> Other
                                        </label>
                                    </div>
                                </div>

                                <!-- Other Option (Textbox for Custom Input) -->
                                <div id="other-history" class="mt-3" style="display: none;">
                                    <label for="other-history-text">Please specify:</label>
                                    <textarea id="other-history-text" class="form-control" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    </div>

    
    <!-- Lab Instructions Section -->
    <div class="container" style="margin-top: 20px;">
           <!-- Separate Tab that appears when clicked -->
            <div class="row" id="Lab-instruction-tab" style="display: none;">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <!-- Special Stain  Choice (Checkbox) -->
                            <div id="stain-choice" class="row">
                                <h3>Lab Instructions</h3>
                                <br>
                                <div>
                                    &nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="save_lab_instructions" id="screening_lab_instruction_save">&nbsp;&nbsp;<b>Save</b>
                                </div>
                                <br>
                                <div class="col-md-3">
                                    <input type="checkbox" id="centrifuge-checkbox" />&nbsp;&nbsp;<b>Centrifuge</b>
                                </div>
                                <div class="col-md-3">
                                    <input type="checkbox" id="zn-checkbox">&nbsp;&nbsp;<b>Zn</b>
                                </div>
                                <div class="col-md-3">
                                    <input type="checkbox" id="Gram-stain-checkbox">&nbsp;&nbsp;<b>Gram</b>
                                </div>
                                <div class="col-md-3">
                                    <input type="checkbox" id="Fite-Faraco-checkbox">&nbsp;&nbsp;<b>Fite-Faraco</b>
                                </div>
                                <div class="col-md-3">
                                    <input type="checkbox" id="leishmain-checkbox">&nbsp;&nbsp;<b>Leishmain</b>
                                </div>
                                <div class="col-md-3">
                                    <input type="checkbox" id="pap-stain-checkbox">&nbsp;&nbsp;<b>Pap Stain</b>
                                </div>
                                <div class="col-md-3">
                                    <input type="checkbox" id="pas-stain-checkbox">&nbsp;&nbsp;<b>PAS</b>
                                </div>
                                
                                <div class="col-md-3">
                                    <input type="checkbox" id="Congo-Red-checkbox">&nbsp;&nbsp;<b>Congo Red</b>
                                </div>
                                
                                <div class="col-md-3">
                                    <input type="checkbox" id="Grocott-Methenamine-checkbox">&nbsp;&nbsp;<b>GMS</b>
                                </div>
                               
                                <!-- Other Option (Textbox for Custom Input) -->
                                <div class="row">
                                    <div class="col-md-3 mt-3">
                                        <input type="checkbox" id="labInstructions-other-checkbox" class="labInstructions-history-option" value="other" />&nbsp;&nbsp;<b>Other</b>
                                    </div>
                                </div>
                                
                                <div id="Instructions-other-history"  style="display: none;">
                                    <label for="Instructions-other-history-text">Please specify:</label>
                                    <textarea id="Instructions-other-history-text" class="form-control" rows="3"></textarea>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
    </div>
    
    <!-- Recall instruction -->
    <div class="container" style="margin-top: 20px;">
           <!-- Separate Tab that appears when clicked -->
            <div class="row" id="cyto-instruction-tab" style="display: none;">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <!-- cyto Intruction Choice (Checkbox) -->
                            <div id="recall-choice">
                                <div class="row">
                                    <h3>Recall Instruction</h3>
                                    <!-- Submit Button -->
                                    <div class="row mt-3">
                                        <div class="col-md-12">
                                            <button id="submit-recall-data" class="btn btn-primary">Submit</button>
                                        </div>
                                    </div>
                                    <br>
                                    <div class="col-sm-md-1">
                                        <input type="checkbox" id="sample-quality-inadequate-checkbox">&nbsp;&nbsp;<b>Sample Quality Inadequate</b>
                                    </div>
                                    <div class="col-sm-md-1">
                                        <input type="checkbox" id="wrong-site-collected-checkbox">&nbsp;&nbsp;<b>Wrong Site Collected</b>
                                    </div>
                                    <div class="row">
                                        <!-- Other Option (Textbox for Custom Input) -->
                                        <div class="col-md-3 mt-3">
                                            <input type="checkbox" id="recall-other-checkbox" class="recall-history-option" value="other" />&nbsp;&nbsp;<b>Other</b> 
                                        </div>
                                    </div>
                                    <div id="recall-other-history"  style="display: none;">
                                        <label for="recall-other-history-text">Please specify:</label>
                                        <textarea id="recall-other-history-text" class="form-control" rows="3"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    </div>

     <!-- Finalization study and history -->
     <div class="container" style="margin-top: 20px;">
           <!-- Separate Tab that appears when clicked -->
            <div class="row" id="final-study-history-tab" style="display: none;">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <h3>Finalization Study/History</h3>
                            <!-- Study Choice (Checkbox) -->
                            <div id="final-study-choice">
                                <input type="checkbox" id="study-checkbox" value="study"/>&nbsp;&nbsp;<b>Study</b>
                            </div>

                            <!-- Patient History / Investigations -->
                            <div id="final-ipatient-history" style="margin-top: 20px;">
                                <h5><b>Patient History / Investigations:</b></h5>

                                <!-- First Row of Options (Horizontal) -->
                                <div class="row" style="margin-top: 10px;">
                                    <div class="col-md-4">
                                        <label><input type="checkbox" class="history-option" value="self" /> Self</label>
                                    </div>
                                    <div class="col-md-4">
                                        <label><input type="checkbox" class="history-option" value="transcription" /> Transcription</label>
                                    </div>
                                    <div class="col-md-4">
                                        <label><input type="checkbox" class="history-option" value="it-space" /> IT Space</label>
                                    </div>
                                </div>

                                <!-- Second Row of Options (Horizontal) -->
                                <div class="row mt-3" style="margin-top: 10px;">
                                    <div class="col-md-4">
                                        <label><input type="checkbox" class="history-option" value="ultrasonography" /> Ultrasonography</label>
                                    </div>
                                    <div class="col-md-4">
                                        <label><input type="checkbox" class="history-option" value="xray" /> X-Ray</label>
                                    </div>
                                    <div class="col-md-4">
                                        <label><input type="checkbox" class="history-option" value="ct-scan-report" /> CT Scan Report</label>
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-4">
                                        <label><input type="checkbox" class="history-option" value="ct-scan-film" /> CT Scan Film</label>
                                    </div>
                                    <div class="col-md-4">
                                        <label><input type="checkbox" class="history-option" value="mri-report" /> MRI Report</label>
                                    </div>
                                    <div class="col-md-4">
                                        <label><input type="checkbox" class="history-option" value="mri-film" /> MRI Film</label>
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-4">
                                        <label>
                                            <input type="checkbox" id="final-other-checkbox" class="history-option" value="other" /> Other
                                        </label>
                                    </div>
                                </div>

                                <!-- Other Option (Textbox for Custom Input) -->
                                <div id="final-other-history" class="mt-3" style="display: none;">
                                    <label for="final-other-history-text">Please specify:</label>
                                    <textarea id="final-other-history-text" class="form-control" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    </div>

    <!-- Finalization Lab Instructions Section -->
    <div class="container" style="margin-top: 20px;">
           <!-- Separate Tab that appears when clicked -->
            <div class="row" id="final-Lab-instruction-tab" style="display: none;">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <!-- Special Stain  Choice (Checkbox) -->
                            <div id="stain-choice" class="row">
                                <h3>Finalizationl Lab Instructions</h3>
                                <div class="col-md-3">
                                    <input type="checkbox" id="centrifuge-checkbox" />&nbsp;&nbsp;<b>Centrifuge</b>
                                </div>
                                <div class="col-md-3">
                                    <input type="checkbox" id="zn-checkbox">&nbsp;&nbsp;<b>Zn</b>
                                </div>
                                <div class="col-md-3">
                                    <input type="checkbox" id="Gram-stain-checkbox">&nbsp;&nbsp;<b>Gram</b>
                                </div>
                                <div class="col-md-3">
                                    <input type="checkbox" id="Fite-Faraco-checkbox">&nbsp;&nbsp;<b>Fite-Faraco</b>
                                </div>
                                <div class="col-md-3">
                                    <input type="checkbox" id="leishmain-checkbox">&nbsp;&nbsp;<b>Leishmain</b>
                                </div>
                                <div class="col-md-3">
                                    <input type="checkbox" id="pap-stain-checkbox">&nbsp;&nbsp;<b>Pap Stain</b>
                                </div>
                                <div class="col-md-3">
                                    <input type="checkbox" id="pas-stain-checkbox">&nbsp;&nbsp;<b>PAS</b>
                                </div>
                                
                                <div class="col-md-3">
                                    <input type="checkbox" id="Congo-Red-checkbox">&nbsp;&nbsp;<b>Congo Red</b>
                                </div>
                                
                                <div class="col-md-3">
                                    <input type="checkbox" id="Grocott-Methenamine-checkbox">&nbsp;&nbsp;<b>GMS</b>
                                </div>
                                <!-- Other Option (Textbox for Custom Input) -->
                                <div class="row">
                                    <div class="col-md-3 mt-3">
                                        <input type="checkbox" id="final-labInstructions-other-checkbox" class="labInstructions-history-option" value="other" />&nbsp;&nbsp;<b>Other</b>
                                    </div>
                                </div>
                                <div id="final-Instructions-other-history"  style="display: none;">
                                    <label for="final-Instructions-other-history-text">Please specify:</label>
                                    <textarea id="final-Instructions-other-history-text" class="form-control" rows="3"></textarea>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
    </div>

    <!-- Finalization Recall instruction -->
    <div class="container" style="margin-top: 20px;">
           <!-- Separate Tab that appears when clicked -->
            <div class="row" id="final-cyto-instruction-tab" style="display: none;">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <!-- cyto Intruction Choice (Checkbox) -->
                            <div id="recall-choice">
                                <div class="row">
                                    <h3>Finalization Recall Instruction</h3>
                                    <div class="col-sm-md-1">
                                        <input type="checkbox" id="sample-quality-inadequate-checkbox">&nbsp;&nbsp;<b>Sample Quality Inadequate</b>
                                    </div>
                                    <div class="col-sm-md-1">
                                        <input type="checkbox" id="wrong-site-collected-checkbox">&nbsp;&nbsp;<b>Wrong Site Collected</b>
                                    </div>
                                    <div class="row">
                                        <!-- Other Option (Textbox for Custom Input) -->
                                        <div class="col-md-3 mt-3">
                                            <input type="checkbox" id="final-recall-other-checkbox" class="recall-history-option" value="other" />&nbsp;&nbsp;<b>Other</b> 
                                        </div>
                                    </div>
                                    <div id="final-recall-other-history"  style="display: none;">
                                        <label for="final-recall-other-history-text">Please specify:</label>
                                        <textarea id="final-recall-other-history-text" class="form-control" rows="3"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    </div>

    <!-- this div to display the success message -->
    <div id="screening-message-screening" style="display:none; margin-top: 10px; padding: 10px; background-color: #28a745; color: white; border-radius: 5px;">
        Screening has commenced.
    </div>
    <!-- Success/Error Message Div -->
    <div id="screening-message" style="display: none;" class="alert"></div>

</body>
</html>

<!-- lab number wise page redirect -->
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
                        window.location.href = 'index.php?labno=' + labno;
                    } else {    
                        window.location.href = '../lab_status.php?labno=' + labno;
                    }
                } 
                else {
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

<!-- Study/History -->
<script>
    // Toggle visibility of the new Study / History tab when clicked
    document.getElementById("study-history-header").addEventListener("click", function() {
        var studyHistoryTab = document.getElementById("study-history-tab");
        // Toggle between showing and hiding the separate tab
        if (studyHistoryTab.style.display === "none") {
            studyHistoryTab.style.display = "block"; // Show the tab
        } else {
            studyHistoryTab.style.display = "none"; // Hide the tab
        }
    });

    // Show or hide the 'Other' textbox based on selected checkboxes
    document.querySelectorAll(".history-option").forEach(function(option) {
        option.addEventListener("change", function() {
            var otherHistory = document.getElementById("other-history");
            // Check if 'Other' is selected
            if (this.value === "other" && this.checked) {
                otherHistory.style.display = "block"; // Show textarea for 'Other'
            } else if (this.value === "other" && !this.checked) {
                otherHistory.style.display = "none"; // Hide textarea for 'Other'
            }
        });
    });

    document.getElementById('other-checkbox').addEventListener('change', function () {
        const otherHistoryDiv = document.getElementById('other-history');
        if (this.checked) {
            otherHistoryDiv.style.display = 'block';
        } else {
            otherHistoryDiv.style.display = 'none';
        }
    });
</script>

<!-- Lab Related Instruction -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Toggle visibility of the Lab instruction tab when clicked
        document.getElementById("lab-instructions-header").addEventListener("click", function() {
            var labInstructionsTab = document.getElementById("Lab-instruction-tab");
            if (labInstructionsTab.style.display === "none") {
                labInstructionsTab.style.display = "block"; // Show the tab
            } else {
                labInstructionsTab.style.display = "none"; // Hide the tab
            }
        });

        // Show or hide the 'Other' textbox based on selected checkboxes
        document.getElementById('labInstructions-other-checkbox').addEventListener('change', function () {
            const otherHistoryDiv = document.getElementById('Instructions-other-history');
            if (this.checked) {
                otherHistoryDiv.style.display = 'block'; // Show the textarea for 'Other'
            } else {
                otherHistoryDiv.style.display = 'none'; // Hide the textarea for 'Other'
            }
        });
    });
</script>

<!-- Cyto Related Instruction -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Toggle visibility of the Lab instruction tab when clicked
        document.getElementById("cyto-instruction-header").addEventListener("click", function() {
            var cytoInstructionsTab = document.getElementById("cyto-instruction-tab");
            if (cytoInstructionsTab.style.display === "none") {
                cytoInstructionsTab.style.display = "block"; // Show the tab
            } else {
                cytoInstructionsTab.style.display = "none"; // Hide the tab
            }
        });

        // Show or hide the 'Other' textbox based on selected checkboxes
        document.getElementById('recall-other-checkbox').addEventListener('change', function () {
            const otherHistoryDiv = document.getElementById('recall-other-history');
            if (this.checked) {
                otherHistoryDiv.style.display = 'block'; // Show the textarea for 'Other'
            } else {
                otherHistoryDiv.style.display = 'none'; // Hide the textarea for 'Other'
            }
        });
    });
</script>


<!-- Finalization Study/History -->
<script>
    // Toggle visibility of the new Study / History tab when clicked
    document.getElementById("final-study-history-header").addEventListener("click", function() {
        var finalstudyHistoryTab = document.getElementById("final-study-history-tab");
        // Toggle between showing and hiding the separate tab
        if (finalstudyHistoryTab.style.display === "none") {
            finalstudyHistoryTab.style.display = "block"; // Show the tab
        } else {
            finalstudyHistoryTab.style.display = "none"; // Hide the tab
        }
    });

    // Show or hide the 'Other' textbox based on selected checkboxes
    document.querySelectorAll(".final-history-option").forEach(function(option) {
        option.addEventListener("change", function() {
            var finalotherHistory = document.getElementById("final-other-history");
            // Check if 'Other' is selected
            if (this.value === "other" && this.checked) {
                finalotherHistory.style.display = "block"; // Show textarea for 'Other'
            } else if (this.value === "other" && !this.checked) {
                finalotherHistory.style.display = "none"; // Hide textarea for 'Other'
            }
        });
    });

    document.getElementById('final-other-checkbox').addEventListener('change', function () {
        const otherHistoryDiv = document.getElementById('final-other-history');
        if (this.checked) {
            otherHistoryDiv.style.display = 'block';
        } else {
            otherHistoryDiv.style.display = 'none';
        }
    });
</script>

<!-- Finalization Lab Related Instruction -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Toggle visibility of the Lab instruction tab when clicked
        document.getElementById("final-lab-instructions-header").addEventListener("click", function() {
            var finallabInstructionsTab = document.getElementById("final-Lab-instruction-tab");
            if (finallabInstructionsTab.style.display === "none") {
                finallabInstructionsTab.style.display = "block"; // Show the tab
            } else {
                finallabInstructionsTab.style.display = "none"; // Hide the tab
            }
        });

        // Show or hide the 'Other' textbox based on selected checkboxes
        document.getElementById('final-labInstructions-other-checkbox').addEventListener('change', function () {
            const finalotherHistoryDiv = document.getElementById('final-Instructions-other-history');
            if (this.checked) {
                finalotherHistoryDiv.style.display = 'block'; // Show the textarea for 'Other'
            } else {
                finalotherHistoryDiv.style.display = 'none'; // Hide the textarea for 'Other'
            }
        });
    });
</script>

<!-- Finalization Cyto Related Instruction -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Toggle visibility of the Lab instruction tab when clicked
        document.getElementById("final-cyto-instruction-header").addEventListener("click", function() {
            var finalcytoInstructionsTab = document.getElementById("final-cyto-instruction-tab");
            if (finalcytoInstructionsTab.style.display === "none") {
                finalcytoInstructionsTab.style.display = "block"; // Show the tab
            } else {
                finalcytoInstructionsTab.style.display = "none"; // Hide the tab
            }
        });

        // Show or hide the 'Other' textbox based on selected checkboxes
        document.getElementById('final-recall-other-checkbox').addEventListener('change', function () {
            const finalotherHistoryDiv = document.getElementById('final-recall-other-history');
            if (this.checked) {
                finalotherHistoryDiv.style.display = 'block'; // Show the textarea for 'Other'
            } else {
                finalotherHistoryDiv.style.display = 'none'; // Hide the textarea for 'Other'
            }
        });
    });
</script>

<!-- here modal logic -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
            let isScreeningDone = false; // Default state: Screening is not done
            let isLeaving = false; // Track if the user is leaving the page

            // Get references to necessary elements
            const screeningDoneHeader = document.getElementById("screening-done-header");
            const finalizationSection = document.getElementById("finalization-section");
            const customModal = document.getElementById("customModal");

            const modalConfirmLeave = document.getElementById("modalConfirmLeave");
            const modalCancelLeave = document.getElementById("modalCancelLeave");
            const modalClose = document.getElementById("modalClose");

            // Add a click event listener to "Screening Done"
            screeningDoneHeader.addEventListener("click", function () {
                isScreeningDone = true; // Mark screening as done
                finalizationSection.classList.remove("disabled-section"); // Enable finalization section
                finalizationSection.style.pointerEvents = "auto"; // Allow clicks
                finalizationSection.style.opacity = "1"; // Make it fully visible
            });

            // Disable finalization options initially
            finalizationSection.classList.add("disabled-section");
            finalizationSection.style.pointerEvents = "none"; // Prevent clicks
            finalizationSection.style.opacity = "0.5"; // Add a shadow effect

            // Add click event listeners to finalization options
            finalizationSection.addEventListener("click", function (event) {
                if (!isScreeningDone) {
                    event.preventDefault(); // Prevent default action
                    customModal.style.display = "block"; // Show the custom modal with the message
                }
            });

            // Custom page exit confirmation (without showing the default alert)
            // window.addEventListener('beforeunload', function (event) {
            //     if (!isScreeningDone && !isLeaving) {
            //         event.preventDefault(); // Prevent page unload/reload
            //         customModal.style.display = "block"; // Show the custom modal
            //         isLeaving = true; // Mark that the user is attempting to leave
            //         return false; // For most browsers, cancel the unload
            //     }
            // });

            // // Handle modal button clicks
            // modalConfirmLeave.addEventListener("click", function() {
            //     isLeaving = true; // Allow leaving the page
            //     window.location.href = "../doctorsindex.php"; // Navigate to doctorsindex.php
            // });

            // modalCancelLeave.addEventListener("click", function() {
            //     isLeaving = false; // Cancel leaving, keep the page as it is
            //     customModal.style.display = "none"; // Hide the custom modal
            // });

            // // Close modal on 'X' click
            // modalClose.addEventListener("click", function() {
            //     customModal.style.display = "none"; // Hide the custom modal
            // });

            // // Close modal if user clicks outside the modal content
            // window.addEventListener("click", function(event) {
            //     if (event.target == customModal) {
            //         customModal.style.display = "none"; // Hide the modal if user clicks outside
            //     }
            // });

            // // Prevent default behavior for form submission if Screening is not done
            // const form = document.getElementById("readlabno");
            // form.addEventListener("submit", function(event) {
            //     if (!isScreeningDone) {
            //         event.preventDefault(); // Prevent form submission
            //         customModal.style.display = "block"; // Show the custom modal with the message
            //     }
            // });

            // Prevent default behavior for the leave link if Screening is not done
            // const leaveLink = document.getElementById("leaveLink");
            // leaveLink.addEventListener("click", function(event) {
            //     if (!isScreeningDone) {
            //         event.preventDefault(); // Prevent the link from being followed
            //         customModal.style.display = "block"; // Show the custom modal with the message
            //     }
            // });
    });
</script>

 <!-- Custom Modal -->
<div id="customModal" class="custom-modal">
    <div class="custom-modal-content">
        <div class="custom-modal-header">
            <span class="custom-modal-close" id="modalClose">&times;</span>
            
        </div>
        <div class="custom-modal-body">
            You haven't completed the "Screening Done" step. Are you sure you want to leave or reload the page?
        </div>
        <div class="custom-modal-footer">
            <button id="modalCancelLeave">Cancel</button>
            <button id="modalConfirmLeave">Leave</button>
        </div>
    </div>
</div>

<!-- Screening Start data store -->
<script>
    document.getElementById('screening-header').addEventListener('click', function() {
            const labNumber = "<?php echo $LabNumber; ?>"; 
            const username = "<?php echo $loggedInUsername; ?>"; 
            const currentTimestamp = new Date().toISOString(); // Get current timestamp

            // Send an AJAX request to the PHP backend
            fetch('insert/screening_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    lab_number: labNumber,
                    username: username,
                    timestamp: currentTimestamp
                })
            })
            .then(response => response.text()) // Get raw text response
            .then(text => {
                // console.log("Raw Response from PHP:", text); // Log raw response
                try {
                    // Attempt to parse the response as JSON
                    const data = JSON.parse(text);
                    // console.log("Parsed JSON:", data);
                    if (data.status === 'success') {
                        const messageDiv = document.getElementById('screening-message-screening');
                        messageDiv.style.display = 'block';
                        messageDiv.textContent = 'Screening Started';
                        setTimeout(() => { messageDiv.style.display = 'none'; }, 6000);
                    } else {
                        console.log('Error:', data.message);
                    }
                } catch (error) {
                    // Handle any errors with JSON parsing
                    // console.error('JSON Parsing Error:', error.message);
                    // console.log('Raw Response:', text); // Log raw response
                    // alert('Invalid JSON response');
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                // console.log('json data:', JSON.stringify({
                //     lab_number: labNumber,
                //     username: username,
                //     timestamp: currentTimestamp
                // }));
                const messageDiv = document.getElementById('screening-message');
                messageDiv.style.display = 'block';
                messageDiv.textContent = 'An error occurred. Please try again.';
            });
    });
</script>

<!-- Finalization Start data store -->
<script>
    document.getElementById('finalization-header').addEventListener('click', function() {
            const labNumber = "<?php echo $LabNumber; ?>";
            const username = "<?php echo $loggedInUsername; ?>";
            const currentTimestamp = new Date().toISOString(); // Get current timestamp

            // // Log data before sending
            // console.log("Sending data to PHP:", {
            //     lab_number: labNumber,
            //     username: username,
            //     timestamp: currentTimestamp
            // });

            // Send an AJAX request to the PHP backend
            fetch('insert/final_screening_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    lab_number: labNumber,
                    username: username,
                    timestamp: currentTimestamp
                })
            })
            .then(response => {
                // Log the response status and body
                // console.log('Response received from PHP:', response);
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                // Log the received data for debugging
                // console.log('Data received from PHP:', data);

                const messageDiv = document.getElementById('screening-message-screening');

                // Display success or error message
                messageDiv.style.display = 'block';
                messageDiv.textContent = data.message;

                if (data.status === 'success') {
                    // Hide the message after 1 second
                    setTimeout(() => { messageDiv.style.display = 'none'; }, 1000);
                } else {
                    // console.error('Error:', data.message);
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                // console.error('Fetch Error:', error);
                const messageDiv = document.getElementById('screening-message');
                messageDiv.style.display = 'block';
                messageDiv.textContent = 'An error occurred. Please try again.';
            });
    });
</script>


<!-- Screening Study & History data store-->
<script>
    document.addEventListener('DOMContentLoaded', () => {
            // Submit the screening study
            document.getElementById('study-checkbox').addEventListener('change', function () {
                if (this.checked) {
                    submitStudyData();
                }
            });

            function submitStudyData() {
                // Collect patient history checkbox values
                let historyOptions = [];
                document.querySelectorAll('.history-option:checked').forEach(el => {
                    if (el.value === 'other') {
                        const otherInput = document.getElementById('other-history-text').value;
                        historyOptions.push({ "other": otherInput });
                    } else {
                        historyOptions.push(el.value);
                    }
                });

            // Prepare data to send
            const labNumber = "<?php echo $LabNumber; ?>"; // PHP echo
            const username = "<?php echo $loggedInUsername; ?>"; // PHP echo
            const timestamp = new Date().toISOString(); // Current timestamp
            const data = {
                lab_number: labNumber,
                username: username,
                timestamp: timestamp,
                screening_study: true,
                screening_patient_history: historyOptions
            };

            // Send data to server via Fetch API
            fetch('insert/screening_study_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.status === 'success') {
                    showMessage('This case has been moved to the Study/History section.', 'success');
                } else {
                    showMessage('An error occurred: ' + result.message, 'error');
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                showMessage('An error occurred. Please try again.', 'error');
            });
        }

        // Show/Hide Other History Textbox
        document.getElementById('other-checkbox').addEventListener('change', function () {
            document.getElementById('other-history').style.display = this.checked ? 'block' : 'none';
        });

        // Function to show success/error messages
        function showMessage(message, type) {
            const messageDiv = document.getElementById('screening-message');
            messageDiv.style.display = 'block';
            messageDiv.textContent = message;

            // Set the class for styling
            if (type === 'success') {
                messageDiv.className = 'alert alert-success';
                // Reload the page after 3 seconds
                setTimeout(() => { 
                    location.reload(); 
                }, 3000);
            } else {
                messageDiv.className = 'alert alert-danger';
                // Hide the error message after 6 seconds
                setTimeout(() => { messageDiv.style.display = 'none'; }, 6000);
            }
        }

    });
</script>


<!-- Screening Lab Instruction -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Event Listener for the Checkbox Submission
        document.getElementById('screening_lab_instruction_save').addEventListener('change', function () {
            if (this.checked) {
                submitLabInstructions();
            }
        });

        // Function to Submit Lab Instructions
        function submitLabInstructions() {
            // Collect Lab Instructions data
            let stainOptions = [];
            document.querySelectorAll('#stain-choice input[type="checkbox"]:checked').forEach(el => {
                
                // Exclude the "Save" checkbox
                if (el.id === 'screening_lab_instruction_save') {
                    return; // Skip this checkbox
                }

                if (el.id === 'labInstructions-other-checkbox') {
                    const otherInput = document.getElementById('Instructions-other-history-text').value.trim();
                    if (otherInput) stainOptions.push({ "other": otherInput });
                } else {
                    stainOptions.push(el.id.replace('-checkbox', '').replace(/-/g, ' '));
                }
            });

            // Prepare Data for Submission
            const labNumber = "<?php echo htmlspecialchars($LabNumber); ?>"; // Safe PHP echo
            const username = "<?php echo htmlspecialchars($loggedInUsername); ?>";
            const timestamp = new Date().toISOString();

            const data = {
                lab_number: labNumber,
                username: username,
                timestamp: timestamp,
                screening_stain_name: stainOptions
            };

            // Fetch API Call to the Server
            fetch('insert/lab_instruction_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => response.text()) // Ensure raw text to debug any issues
            .then(rawData => {
                console.log('Raw Response:', rawData); // Log raw response for debugging

                try {
                    const result = JSON.parse(rawData); // Attempt to parse JSON
                    
                    if (result.status === 'success') {
                        showMessage(result.message, 'success');
                    } else {
                        showMessage('Error: ' + result.message, 'error');
                    }
                } catch (e) {
                    console.error('JSON Parse Error:', e, 'Raw Response:', rawData);
                    showMessage('Invalid server response. Please contact support.', 'error');
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                showMessage('An error occurred. Please try again.', 'error');
            });
        }

        // Show/Hide "Other" Input Box
        document.getElementById('labInstructions-other-checkbox').addEventListener('change', function () {
            document.getElementById('Instructions-other-history').style.display = this.checked ? 'block' : 'none';
        });

        // Function to Display Messages
        function showMessage(message, type) {
            const messageDiv = document.getElementById('screening-message');
            messageDiv.style.display = 'block';
            messageDiv.textContent = message;

            if (type === 'success') {
                messageDiv.className = 'alert alert-success';
                setTimeout(() => { location.reload(); }, 3000);
            } else {
                messageDiv.className = 'alert alert-danger';
                setTimeout(() => { messageDiv.style.display = 'none'; }, 6000);
            }
        }
    });
</script>

<!-- recall instruction -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Show/Hide the "Other" input box
        document.getElementById('recall-other-checkbox').addEventListener('change', function () {
            const otherHistoryDiv = document.getElementById('recall-other-history');
            otherHistoryDiv.style.display = this.checked ? 'block' : 'none';
        });

        // Submit recall data
        document.getElementById('submit-recall-data').addEventListener('click', function () {
            // Fetch user and lab details
            const username = "<?php echo htmlspecialchars($loggedInUsername ?? ''); ?>";
            const labNumber = "<?php echo htmlspecialchars($LabNumber ?? ''); ?>";
            const timestamp = new Date().toISOString();

            if (!username || !labNumber) {
                alert("User or Lab Number is missing.");
                return;
            }

            // Collect recall reasons
            let reasons = [];
            document.querySelectorAll('#recall-choice input[type="checkbox"]:checked').forEach(el => {
                if (el.id === 'recall-other-checkbox') {
                    const otherReason = document.getElementById('recall-other-history-text').value.trim();
                    if (otherReason) {
                        reasons.push(`Other: ${otherReason}`);
                    } else {
                        alert("Please enter a reason for 'Other'.");
                    }
                } else if (el.id !== 'screening_recall_instruction') {
                    const reasonText = el.nextElementSibling ? el.nextElementSibling.textContent.trim() : '';
                    if (reasonText) reasons.push(reasonText);
                }
            });

            if (reasons.length === 0) {
                alert('Please select at least one reason.');
                return;
            }

            // Prepare the data payload
            const data = {
                lab_number: labNumber,
                recalled_doctor: username,
                recall_reason: reasons,
                timestamp: timestamp
            };

            console.log("Sending Data:", data); // Log payload for debugging

            // Send data to the server
            fetch('insert/recall_instruction_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Server returned an error.');
                }
                return response.json();
            })
            .then(result => {
                console.log("Response:", result); // Log server response
                if (result.status === 'success') {
                    alert('Recall instruction saved successfully.');
                    location.reload(); // Optional: Reload the page after success
                } else {
                    alert('Error: ' + (result.message || 'Unknown error occurred.'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while saving the recall instruction. Please try again.');
            });
        });
    });
</script>


<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Constants for data
        const labNumber = "<?php echo htmlspecialchars($LabNumber ?? ''); ?>";
        const username = "<?php echo htmlspecialchars($loggedInUsername ?? ''); ?>";

        // Click event listener
        document.getElementById('screening-done-header').addEventListener('click', function () {
            const timestamp = new Date().toISOString(); // Current timestamp

            // Prepare data to send
            const requestData = {
                lab_number: labNumber,
                username: username,
                timestamp: timestamp
            };

            // Send data to the backend
            fetch('insert/screening_done_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(requestData)
            })
            .then(response => response.json())
            .then(result => {
                if (result.status === 'success') {
                    alert('Screening updated successfully!');
                } else {
                    alert('Error: ' + result.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        });
    });
</script>







<?php 
$NBMAX = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;
$max = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;


print '</div></div>';

// End of page
llxFooter();
$db->close();
?>