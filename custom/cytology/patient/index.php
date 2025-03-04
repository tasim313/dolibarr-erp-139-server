<?php
include('../connection.php');
include('../../grossmodule/gross_common_function.php');
include('../../transcription/common_function.php');
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
$chief_complain_list = get_cyto_chief_complain_list();
$on_examination_list = get_cyto_on_examination_list();
$clinical_history = get_cyto_clinical_history_list();
$clinical_impression = get_cyto_clinical_impression_list(); 
// Filter out empty and null values
$clinical_impression = array_filter($clinical_impression, function($item) {
    return !empty($item['clinical_impression']) && $item['clinical_impression'] !== null;
});

// Re-index the array to maintain continuous numeric indices
$clinical_impression = array_values($clinical_impression);


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
	<!-- Import the JavaScript file -->
	<link href="../../grossmodule/bootstrap-3.4.1-dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="../../grossmodule/bootstrap-3.4.1-dist/js/bootstrap.min.js"></script>
    <!-- Include Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <!-- Include jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Include Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>

    <style>
        .form-group-slide {
            display: flex;
            align-items: center; /* Vertically align the elements */
            justify-content: flex-start; /* Optional: Align the items to the start (left side) */
        }

        .form-group-slide .form-control {
            width: auto; /* Let the input fields take up only as much space as needed */
        }

        .form-group-slide .btn {
            margin-left: 10px; /* Add space between button and inputs */
        }
        .dropbtn {
            padding: 16px;
            font-size: 16px;
            border: none;
            cursor: pointer;
        }

        #myInput {
            box-sizing: border-box;
            background-position: 14px 12px;
            background-repeat: no-repeat;
            font-size: 16px;
            padding: 14px 20px 12px 45px;
            border: none;
            border-bottom: 1px solid #ddd;
        }

        #myInput:focus {outline: 3px solid #ddd;}

        .dropdown {
            position: relative;
            display: inline-block;
        }
        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #FFFFFF;
            min-width: 230px;
            overflow: auto;
            border: 1px solid #ddd;
            z-index: 1;
        }

        .dropdown-content a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
        }

        .show {display: block;}

        #dry-slides-description:focus {
            box-shadow: 0 0 10px 2px rgba(233, 54, 81, 0.7); 
            border-color:rgb(223, 14, 77); 
        }
        #clinical-history:focus{
            box-shadow: 0 0 10px 2px rgba(233, 54, 81, 0.7); 
            border-color:rgb(223, 14, 77); 
        }
        #site-of-aspiration-editor:focus{
            box-shadow: 0 0 10px 2px rgba(233, 54, 81, 0.7);
            border-color:rgb(223, 14, 77); 
        }
        #fixation-comments:focus{
            box-shadow: 0 0 10px 2px rgba(233, 54, 81, 0.7);
            border-color:rgb(223, 14, 77); 
        }
        #number-of-needle:focus{
            box-shadow: 0 0 10px 2px rgba(233, 54, 81, 0.7);
            border-color:rgb(223, 14, 77); 
        }
        #number-of-syringe:focus{
            box-shadow: 0 0 10px 2px rgba(233, 54, 81, 0.7);
            border-color:rgb(223, 14, 77); 
        }
        /* #location-input:focus{
            box-shadow: 0 0 10px 2px rgba(233, 54, 81, 0.7);
            border-color:rgb(223, 14, 77); 
        } */
        #slides-input:focus{
            box-shadow: 0 0 10px 2px rgba(233, 54, 81, 0.7);
            border-color:rgb(223, 14, 77); 
        }
        #aspiration_materials-input:focus{
            box-shadow: 0 0 10px 2px rgba(233, 54, 81, 0.7);
            border-color:rgb(223, 14, 77); 
        }
        #clinical-impression:focus{
            box-shadow: 0 0 10px 2px rgba(233, 54, 81, 0.7);
            border-color:rgb(223, 14, 77); 
        }
        /* Change to success style on hover */
        #populate-table:hover, 
        #populate-table:focus {
            background-color: #28a745; /* Bootstrap's success color */
            color: white; /* Ensure text is visible */
            outline: none; /* Optional: Remove default focus outline */
        }
        #reason-for-fnac:focus{
            box-shadow: 0 0 10px 2px rgba(233, 54, 81, 0.7); 
            border-color:rgb(223, 14, 77); 
        }
    </style>

</head>
<body>   
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <!-- Left-side buttons -->
        <div style="display: flex; gap: 10px;">
            <!-- <a href="<?= $homeUrl ?>" class="btn btn-info btn-md">Back</a> -->
            <!-- <a href="./recall.php?LabNumber=<?php echo urlencode($LabNumber); ?>" class="btn btn-info btn-md">Recall</a> -->
            <button id="togglePatientHistory" class="btn btn-secondary mb-3">Patient Previous History</button>
        </div>
        <!-- Right-side button -->
        
    </div>
            <div class="container"> 
                    
                    <!-- Patient Information -->
                        <?php
                            // Function to trim "FNA" from the LabNumber
                            function remove_prefix($lab_number) {
                                return substr($lab_number, 3); // Removes the first three characters
                            }

                            $trimmedLabNumber = remove_prefix($LabNumber); // Remove the "FNA" prefix

                            // Fetch patient information using the trimmed LabNumber
                            $patient_information = get_patient_details_information($trimmedLabNumber);
                            $genderOptions = [
                                '1' => 'Male',
                                '2' => 'Female',
                                '3' => 'Other'
                            ];
                        ?>
                        <div class="container">
                            <div class="row">
                                <div class="col-12">
                                    <?php if (!empty($patient_information)) { ?>
                                        <table class="table table-bordered table-striped">
                                            <thead class="thead-dark">
                                                <tr>
                                                    <th scope="col">Patient Name</th>
                                                    <th scope="col">Patient Code</th>
                                                    <th scope="col">Address</th>
                                                    <th scope="col">Patient Phone Number</th>
                                                    <th scope="col">Gender</th>
                                                    <th scope="col">Age</th>
                                                    <th scope="col">Attendant Name</th>
                                                    <th scope="col">Attendant Relation</th>
                                                    <th scope="col">Attendant Phone Number</th>
                                                    <th scope="col">Edit</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($patient_information as $patient) { 
                                                    $gender = isset($genderOptions[$patient['Gender']]) ? $genderOptions[$patient['Gender']] : 'Unknown';
                                                ?>
                                                <tr data-rowid="<?php echo htmlspecialchars($patient['rowid']); ?>">
                                                    <td style="width: 200px;">
                                                        <input class="form-control edit-field d-none" name="name" value="<?php echo htmlspecialchars($patient['name']); ?>">
                                                    </td>
                                                    <td style="width: 200px;">
                                                        <input class="form-control edit-field d-none" name="patient_code" value="<?php echo htmlspecialchars($patient['patient_code']); ?>" readonly>
                                                    </td>
                                                    <td style="width: 200px;">
                                                        <input class="form-control edit-field d-none" name="address" value="<?php echo htmlspecialchars($patient['address']); ?>">
                                                    </td>
                                                    <td style="width: 200px;">
                                                        <input class="form-control edit-field d-none" name="phone" value="<?php echo htmlspecialchars($patient['phone']); ?>">
                                                    </td>
                                                    <td style="width: 200px;">
                                                        <select class="form-control edit-field d-none" name="gender">
                                                            <?php foreach ($genderOptions as $key => $value) { ?>
                                                                <option value="<?php echo htmlspecialchars($key); ?>" <?php echo $patient['Gender'] == $key ? 'selected' : ''; ?>><?php echo htmlspecialchars($value); ?></option>
                                                            <?php } ?>
                                                        </select>
                                                    </td>
                                                    <td style="width: 200px;">
                                                        <input class="form-control edit-field d-none" name="age" value="<?php echo htmlspecialchars($patient['Age']); ?>">
                                                    </td>
                                                    <td style="width: 200px;">
                                                        <input class="form-control edit-field d-none" name="att_name" value="<?php echo htmlspecialchars($patient['att_name']); ?>">
                                                    </td>
                                                    <td style="width: 200px;">
                                                        <input class="form-control edit-field d-none" name="att_relation" value="<?php echo htmlspecialchars($patient['att_relation']); ?>">
                                                    </td>
                                                    <td style="width: 200px;">
                                                        <input class="form-control edit-field d-none" name="fax" value="<?php echo htmlspecialchars($patient['fax']); ?>">
                                                    </td>
                                                    <td>
                                                        
                                                        <button class="btn btn-primary save-btn d-none">Save</button>
                                                    </td>
                                                </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>

                                            <?php } else { ?>
                                                <div class="alert alert-warning" role="alert">
                                                    No patient information found for Lab Number: <?php echo htmlspecialchars($trimmedLabNumber); ?>
                                                </div>
                                            <?php } ?>
                                </div>
                            </div>
                        </div>

                        <?php 
                            $patient_history = get_cyto_patient_history_list($trimmedLabNumber);
                        ?>
                        <div id="patientHistoryContainer" style="display: none;">
                            <?php if (!empty($patient_history)): ?>
                                <form action="../Cyto/save_patient_history.php" method="POST">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th>Previous FNAC</th>
                                                    <th>Prev FNAC Date</th>
                                                    <th>Prev FNAC Operation</th>
                                                    <th>Report Informed Submit To The Lab</th>
                                                    <th>Report Collected From Patient</th>
                                                    <th>Referred By Dr</th>
                                                    <th>Referred From Hospital</th>
                                                    <th>Clinical History</th>
                                                    <th>Other Lab No</th>
                                                    <th>Prev Biopsy</th>
                                                    <th>Previous Biopsy Date</th>
                                                    <th>Previous Biopsy Operation</th>
                                                    <th>Referred By Dr (Text)</th>
                                                    <th>Referred From Hospital (Text)</th>
                                                    <th>Edit</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($patient_history as $index => $history): ?>
                                                    <tr>
                                                        <!-- Hidden input to pass the rowid -->
                                                        <input type="hidden" name="rowid[<?= $index ?>]" value="<?= htmlspecialchars($history['rowid']) ?>">

                                                        <!-- Columns -->
                                                        <td>
                                                            <input type="text" class="form-control" name="prev_fnac[<?= $index ?>]" value="<?= htmlspecialchars($history['prev_fnac']) ?>">
                                                        </td>
                                                        <td>
                                                            <input type="text" class="form-control" name="prev_fnac_date[<?= $index ?>]" value="<?= htmlspecialchars($history['prev_fnac_date']) ?>">
                                                        </td>
                                                        <td>
                                                            <input type="text" class="form-control" name="prev_fnac_op[<?= $index ?>]" value="<?= htmlspecialchars($history['prev_fnac_op']) ?>">
                                                        </td>
                                                        <td>
                                                            <select class="form-control" name="informed[<?= $index ?>]" style="width: 200px; height: 70px;" multiple>
                                                                <?php 
                                                                $informedLabels = [1 => 'CT Scan Report', 2 => 'CT Scan Film', 3 => 'MRI Report', 4 => 'MRI Film', 5 => 'Others'];
                                                                $informedValues = explode(',', $history['informed']);
                                                                foreach ($informedLabels as $key => $label) {
                                                                    $selected = in_array($key, $informedValues) ? 'selected' : '';
                                                                    echo "<option value='$key' $selected>$label</option>";
                                                                }
                                                                ?>
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <select class="form-control" name="given[<?= $index ?>]" style="width: 200px; height: 70px;" multiple>
                                                                <?php 
                                                                $givenLabels = [1 => 'CT Scan Report', 2 => 'CT Scan Film', 3 => 'MRI Report', 4 => 'MRI Film', 5 => 'Others'];
                                                                $givenValues = explode(',', $history['given']);
                                                                foreach ($givenLabels as $key => $label) {
                                                                    $selected = in_array($key, $givenValues) ? 'selected' : '';
                                                                    echo "<option value='$key' $selected>$label</option>";
                                                                }
                                                                ?>
                                                            </select>
                                                        </td>

                                                        <td>
                                                            <input type="text" class="form-control" name="referred_by_dr_lastname[<?= $index ?>]" value="<?= htmlspecialchars($history['referred_by_dr_lastname']) ?>">
                                                        </td>
                                                        <td>
                                                            <input type="text" class="form-control" name="referred_from_lastname[<?= $index ?>]" value="<?= htmlspecialchars($history['referred_from_lastname']) ?>">
                                                        </td>
                                                        <td>
                                                            <textarea class="form-control" name="add_history[<?= $index ?>]" style="width: 250px; height: 50px;"><?= htmlspecialchars($history['add_history']) ?></textarea>
                                                        </td>
                                                        <td>
                                                            <input type="text" class="form-control" name="other_labno[<?= $index ?>]" style="width: 100px; height: 50px;" value="<?= htmlspecialchars($history['other_labno']) ?>">
                                                        </td>
                                                        <td>
                                                            <input type="text" class="form-control" name="prev_biopsy[<?= $index ?>]" style="width: 100px; height: 50px;" value="<?= htmlspecialchars($history['prev_biopsy']) ?>">
                                                        </td>

                                                        <td>
                                                            <input type="text" class="form-control" name="prev_biopsy_date[<?= $index ?>]" value="<?= htmlspecialchars($history['prev_biopsy_date']) ?>">
                                                        </td>
                                                        <td>
                                                            <input type="text" class="form-control" name="prev_biopsy_op[<?= $index ?>]" value="<?= htmlspecialchars($history['prev_biopsy_op']) ?>">
                                                        </td>
                                                        <td>
                                                            <input type="text" class="form-control" name="referred_by_dr_text[<?= $index ?>]" style="width: 200px; height: 50px;" value="<?= htmlspecialchars($history['referred_by_dr_text']) ?>">
                                                        </td>
                                                        <td>
                                                            <input type="text" class="form-control" name="referredfrom_text[<?= $index ?>]" style="width: 200px; height: 50px;" value="<?= htmlspecialchars($history['referredfrom_text']) ?>">
                                                        </td>
                                                        <td>
                                                            <button type="submit" class="btn btn-primary">Save</button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </form>
                            <?php else: ?>
                                <p class="text-center text-danger">No patient history available for the provided lab number.</p>
                            <?php endif; ?>
                            <br><br>
                        </div>

                        <form id="clinical-information-form" action="../Cyto/new_patient_create.php" method="post">
                            <table class="table table-bordered table-striped">
                                <tbody>
                                <!-- Doctor Selection -->
                                    <tr>
                                        <th scope="col">
                                            <label for="doctor" class="form-label">Doctor</label>
                                        </th>
                                        <th scope="col">
                                            <label for="assistant" class="form-label">Assistant</label>
                                        </th>
                                        <th scope="col">
                                            <label for="station" class="form-label">FNA Station</label>
                                        </th>
                                        <th scope="col">
                                            <label for="lab_number" class="form-label">Lab Number</label>
                                        </th>
                                    </tr>
                                                                
                                    <tr>
                                        <td>
                                            <select id="doctor_name" name="doctor_name" class="form-control" aria-label="Doctor selection" data-error="Please specify your need." required>
                                            <option value="" selected disabled>--Select a Doctor--</option>
                                            <?php
                                                $doctors = get_doctor_list();
                                                $loggedInUsername = $user->login; 

                                                foreach ($doctors as $doctor) {
                                                    $selected = '';
                                                    if ($doctor['doctor_username'] == $loggedInUsername) {
                                                        $selected = 'selected';
                                                        $storeDoctor = isset($_SESSION['doctor_name']) && $_SESSION['doctor_name'] === $doctor['doctor_username'] ? 'selected' : '';
                                                    }
                                                    echo "<option value='{$doctor['doctor_username']}' $selected>{$doctor['doctor_username']}</option>";
                                                }
                                            ?>
                                            </select>
                                        </td>
                                                                
                                        <td>
                                            <select id="assistant" name="assistant" class="form-control" aria-label="Assistant selection" data-error="Please specify your need." required>
                                            <option value="">--Select an Assistant--</option>
                                            <?php
                                                $assistants = get_cyto_tech_list();
                                                $loggedInUsername = $user->login;
                                                foreach ($assistants as $assistant) {
                                                    $selected = '';
                                                    if ($assistant['username'] == $loggedInUsername) {
                                                        $selected = 'selected';
                                                        $storedAssistant = isset($_SESSION['assistant']) && $_SESSION['assistant'] === $assistant['username'] ? 'selected' : '';
                                                    }
                                                    echo "<option value='{$assistant['username']}' $selected>{$assistant['username']}</option>";
                                                }
                                            ?>
                                            </select>
                                        </td>
                                        <td>
                                            <select id="cyto_station_type" name='cyto_station_type' class="form-control" required>
                                                <option value="">--Select a Station--</option>
                                                <option value="One" <?php echo isset($_SESSION['cyto_station_type']) && $_SESSION['cyto_station_type'] === 'One' ? 'selected' : ''; ?>>One</option>
                                                <option value="Two" <?php echo isset($_SESSION['cyto_station_type']) && $_SESSION['cyto_station_type'] === 'Two' ? 'selected' : ''; ?>>Two</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input id="lab_number" name="lab_number" class="form-control" type="text" value="<?php echo $LabNumber; ?>" readonly>
                                            <input type="hidden" id="status" name="status" value="done">
                                            <input type="hidden" id="created_user" name="created_user" value="<?php echo $loggedInUsername; ?>">
                                        </td>
                                                    
                                    </tr>
                                </tbody>
                            </table>
                        
                            <!-- Clinical Information -->
                            <!-- Reason for FNAC -->
                            <div class="form-group">
                                    <label for="reason-for-fnac">C/C:</label>
                                    <textarea id="reason-for-fnac" name="reason_for_fnac" class="form-control" 
                                        placeholder="Enter Chief complain" rows="3" required autocomplete="off"></textarea>
                                    <ul id="suggestions-list" style="position: absolute; background: white; border: 1px solid #ccc; 
                                        max-height: 150px; overflow-y: auto; display: none; list-style: none; padding: 0; margin: 0; z-index: 10;"></ul>
                            </div>

                            <!-- Clinical History -->
                            <div class="form-group">
                                <label for="clinical-history">H/O:</label>
                                <textarea id="clinical-history" name="clinical_history" class="form-control" rows="3" placeholder="Enter detailed clinical notes" required></textarea>
                                <ul id="clinical-history-suggestions" class="suggestions-list" style="display: none; position: absolute; z-index: 1000; background: white; border: 1px solid #ccc; padding: 5px; list-style: none; max-height: 150px; overflow-y: auto;"></ul>
                            </div>
                            
                            <!-- Site of Aspiration -->
                            <div class="form-group">
                                <label for="site-of-aspiration-editor">O/E:</label>
                                <textarea id="site-of-aspiration-editor" name="site-of-aspiration-editor" class="form-control" rows="5" placeholder="Enter on examination note"></textarea>
                                <ul id="examination-suggestions-list" style="position: absolute; background: white; border: 1px solid #ccc; 
                                 max-height: 150px; overflow-y: auto; display: none; list-style: none; padding: 0; margin: 0; z-index: 10;"></ul>
                            </div>

                            
                            <!-- FNAC Collection Details -->
                            <?php
                                $slideBaseCode = preg_replace('/^[A-Za-z]{3}/', '', $LabNumber); 
                                $locationOptions = ['','Proper','Thyroid', 'Breast', 'Lymph node', 'Lung', 'Other'];
                            ?>
                            <h3>Aspiration Note:</h3>
                            <!-- Total Slides Prepared -->
                            <div class="form-group form-group-slide d-flex align-items-center">
                                <label for="location-input" class="mr-2">Aspiration:</label> &nbsp; 
                                <input type="text" id="location-input" name="location_input" class="form-control mr-3" placeholder="Enter location (e.g., Proper)" > &nbsp; &nbsp; &nbsp;
                                
                                <label for="aspiration_materials-input" class="mr-2">Aspiration Materials:</label>&nbsp;
                                <input type="text" id="aspiration_materials-input" name="aspiration_materials_input" class="form-control mr-3" placeholder="Enter Aspiration Materials" > &nbsp; &nbsp; &nbsp;
                                
                                <label for="slides-input" class="mr-2">Slide:</label> &nbsp; 
                                <input type="text" id="slides-input" name="slides_input" class="form-control mr-3" placeholder="Enter slide (e.g., 2+1)" > &nbsp; &nbsp; &nbsp; 

                                <label for="special_instruction-input" class="mr-2">Special Instruction:</label>
                                <input type="text" id="special-instruction-input" name="special_instruction_input" class="form-control mr-3" placeholder="Enter Special Instruction"> &nbsp; &nbsp; &nbsp;
                                
                                <button type="button" class="btn btn-primary" id="populate-table">Generate slide</button>
                            </div>

                            <!-- Modal -->
                            <div class="modal fade" id="specialInstructionModal" tabindex="-1" role="dialog" aria-labelledby="specialInstructionModalLabel">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                            <h4 class="modal-title" id="specialInstructionModalLabel">Select Special Instructions</h4>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <input type="checkbox" id="centrifuge-checkbox" value="Centrifuge" />&nbsp;&nbsp;<b>Centrifuge</b>
                                                </div>
                                                <div class="col-md-3">
                                                    <input type="checkbox" id="zn-checkbox" value="Zn" />&nbsp;&nbsp;<b>Zn</b>
                                                </div>
                                                <div class="col-md-3">
                                                    <input type="checkbox" id="Gram-stain-checkbox" value="Gram" />&nbsp;&nbsp;<b>Gram</b>
                                                </div>
                                                <div class="col-md-3">
                                                    <input type="checkbox" id="Fite-Faraco-checkbox" value="Fite-Faraco" />&nbsp;&nbsp;<b>Fite-Faraco</b>
                                                </div>
                                                <div class="col-md-3">
                                                    <input type="checkbox" id="leishmain-checkbox" value="Leishmain" />&nbsp;&nbsp;<b>Leishmain</b>
                                                </div>
                                                <div class="col-md-3">
                                                    <input type="checkbox" id="pap-stain-checkbox" value="Pap Stain" />&nbsp;&nbsp;<b>Pap Stain</b>
                                                </div>
                                                <div class="col-md-3">
                                                    <input type="checkbox" id="pas-stain-checkbox" value="PAS" />&nbsp;&nbsp;<b>PAS</b>
                                                </div>
                                                <div class="col-md-3">
                                                    <input type="checkbox" id="Congo-Red-checkbox" value="Congo Red" />&nbsp;&nbsp;<b>Congo Red</b>
                                                </div>
                                                <div class="col-md-3">
                                                    <input type="checkbox" id="Grocott-Methenamine-checkbox" value="GMS" />&nbsp;&nbsp;<b>GMS</b>
                                                </div>
                                                <div class="col-md-3">
                                                    <input type="checkbox" id="cell-block-checkbox" value="cell block" />&nbsp;&nbsp;<b>Cell Block</b>
                                                </div>
                                                <!-- Other Option -->
                                                <div class="col-md-3 mt-3">
                                                    <input
                                                        type="checkbox"
                                                        id="final-labInstructions-other-checkbox"
                                                        value="Other"
                                                    />&nbsp;&nbsp;<b>Other</b>
                                                </div>
                                            </div>
                                            <div id="final-Instructions-other-history" style="display: none;" class="mt-3">
                                                <label for="final-Instructions-other-history-text">Please specify:</label>
                                                <textarea
                                                    id="final-Instructions-other-history-text"
                                                    class="form-control"
                                                    rows="3"
                                                    placeholder="Specify other instruction"
                                                ></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                            <button type="button" class="btn btn-primary" data-dismiss="modal">Done</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Slide Fixation Details -->
                            <div class="form-group" style='display:none'>
                                <label>Slide Fixation Details:</label>
                                <table class="table table-bordered" id="fixation-details-table">
                                    <thead>
                                        <tr>
                                            <th>RowId</th>
                                            <th>Slide Number</th>
                                            <th>Location</th>
                                            <th>Fixation Method</th>
                                            <th>Dry</th>
                                            <th>Aspiration Materials</th>
                                            <th>Special Instruction</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="fixation-details-body">
                                        <!-- Dynamic Rows -->
                                    </tbody>
                                </table>
                            </div>

                            <div id="result-display" class="mt-3"></div>
                           
                            <!-- clinical-impression -->
                            <div class="form-group row">
                                    <label for="clinical-impression" class="col-sm-3 col-form-label">
                                        C/I:
                                    </label>
                                    <div>
                                    <textarea 
                                        required
                                        id="clinical-impression" 
                                        name="clinical_impression" 
                                        class="form-control" 
                                        rows="2" 
                                        placeholder="Enter clinical impression here..."
                                    ></textarea>
                                    <ul id="clinical-impression-suggestions" style="position: absolute; background: white; border: 1px solid #ccc; 
                                 max-height: 150px; overflow-y: auto; display: none; list-style: none; padding: 0; margin: 0; z-index: 10;"></ul>
                                    </div>
                            </div>

                            <!-- Number of Passes Performed -->
                            <div class="form-group">
                                <label for="number-of-needle">Number of Needle Used:</label>
                                <input type="number" id="number-of-needle" name="number_of_needle" class="form-control" min="0" required>
                            </div>
                            <div class="form-group">
                                <label for="number-of-syringe">Number of Syringe Used:</label>
                                <input type="number" id="number-of-syringe" name="number_of_syringe" class="form-control" min="0" required>
                            </div>

                            <!-- Dry Slides Description -->
                            <div class="form-group">
                                <label for="dry-slides-description">
                                    Dry Slides Description (if any):
                                    <button type="button" class="btn btn-link toggle-btn" data-target="#dry-slides-section">+</button>
                                </label>
                                <div id="dry-slides-section" class="toggle-section" style="display: none;">
                                    <textarea id="dry-slides-description" name="dry_slides_description" class="form-control" rows="3" placeholder="Enter Dry Slides Description"></textarea>
                                </div>
                            </div>

                            <!-- Additional Notes -->
                            <div class="form-group">
                                <label for="fixation-comments">
                                    Additional Notes on Fixation:
                                    <button type="button" class="btn btn-link toggle-btn" data-target="#fixation-comments-section">+</button>
                                </label>
                                <div id="fixation-comments-section" class="toggle-section" style="display: none;">
                                    <textarea id="fixation-comments" name="fixation_comments" class="form-control" rows="3" placeholder="Enter Additional Notes on Fixation"></textarea>
                                </div>
                            </div>

                            <button id="saveButton" type="submit" class="btn btn-primary">Submit</button>
                        </form>
            </div>
    
</body>
</html>


<!-- Doctor , Assistant and Station information -->
<!-- <script>
    window.onload = function() {
        const storeDoctor = sessionStorage.getItem('doctor_name');
        const storedAssistant = sessionStorage.getItem('assistant');
        const storedStation = sessionStorage.getItem('cyto_station_type');
        if (storedAssistant) {
            document.getElementById('assistant').value = storedAssistant;
        }
        if (storedStation) {
            document.getElementById('cyto_station_type').value = storedStation;
        }
        if(storeDoctor){
            document.getElementById('doctor_name').value = storeDoctor;
        }
    };

    
    document.getElementById('clinical-information-form').addEventListener('submit', function(event) {
        const selectedAssistant = document.getElementById('assistant').value;
        const selectedStation = document.getElementById('cyto_station_type').value;
        const selectedDoctor = document.getElementById('doctor_name').value;
        sessionStorage.setItem('assistant', selectedAssistant);
        sessionStorage.setItem('cyto_station_type', selectedStation);
        sessionStorage.setItem('doctor_name', selectedDoctor);
    });

</script> -->
<script>
    window.onload = function() {
        const storeDoctor = "<?php echo $loggedInUsername; ?>";
        const storedDoctor = sessionStorage.getItem('doctor_name') || storeDoctor;
        const storedAssistant = sessionStorage.getItem('assistant');
        const storedStation = sessionStorage.getItem('cyto_station_type');

        // Auto-fill fields if values exist
        if (storedDoctor) {
            document.getElementById('doctor_name').value = storedDoctor;
        }
        if (storedAssistant) {
            document.getElementById('assistant').value = storedAssistant;
        }
        if (storedStation) {
            document.getElementById('cyto_station_type').value = storedStation;
        }
    };

    // Store values on form submission
    document.getElementById('clinical-information-form').addEventListener('submit', function(event) {
        const selectedAssistant = document.getElementById('assistant').value;
        const selectedStation = document.getElementById('cyto_station_type').value;
        const selectedDoctor = document.getElementById('doctor_name').value;

        sessionStorage.setItem('assistant', selectedAssistant);
        sessionStorage.setItem('cyto_station_type', selectedStation);
        sessionStorage.setItem('doctor_name', selectedDoctor);
    });
</script>



<!-- Chief Information -->
<script>
    // const chiefComplainList = <?= json_encode($chief_complain_list, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    // let currentIndex = -1;

    // function showSuggestions(term) {
    //     const suggestionsList = document.getElementById('suggestions-list');
    //     const textarea = document.getElementById('reason-for-fnac');
    //     suggestionsList.innerHTML = ''; // Clear previous suggestions
    //     currentIndex = -1; // Reset index

    //     if (term.length < 2) {
    //         suggestionsList.style.display = 'none';
    //         return;
    //     }

    //     // Filter matching values
    //     const filteredList = chiefComplainList.filter(item =>
    //         item.chief_complain.toLowerCase().includes(term.toLowerCase())
    //     );

    //     if (filteredList.length > 0) {
    //         filteredList.forEach((item, index) => {
    //             const li = document.createElement('li');
    //             li.textContent = item.chief_complain;
    //             li.style.padding = '5px';
    //             li.style.cursor = 'pointer';
    //             li.style.borderBottom = '1px solid #ccc';
    //             li.dataset.index = index;

    //             // Populate textarea on click
    //             li.onclick = () => {
    //                 textarea.value = item.chief_complain;
    //                 suggestionsList.style.display = 'none';
    //             };

    //             suggestionsList.appendChild(li);
    //         });
    //         suggestionsList.style.display = 'block';
    //     } else {
    //         suggestionsList.style.display = 'none';
    //     }
    // }

    // document.getElementById('reason-for-fnac').addEventListener('input', function () {
    //     showSuggestions(this.value);
    // });

    // document.getElementById('reason-for-fnac').addEventListener('keydown', function (e) {
    //     const suggestionsList = document.getElementById('suggestions-list');
    //     const suggestions = Array.from(suggestionsList.getElementsByTagName('li'));

    //     if (suggestions.length === 0) return;

    //     if (e.key === 'ArrowDown') {
    //         e.preventDefault();
    //         currentIndex = (currentIndex + 1) % suggestions.length;
    //         highlightSuggestion(suggestions, currentIndex);
    //     } else if (e.key === 'ArrowUp') {
    //         e.preventDefault();
    //         currentIndex = (currentIndex - 1 + suggestions.length) % suggestions.length;
    //         highlightSuggestion(suggestions, currentIndex);
    //     } else if (e.key === 'Enter') {
    //         e.preventDefault();
    //         if (currentIndex >= 0) {
    //             suggestions[currentIndex].click();
    //         }
    //     } else if (e.key === 'Escape') {
    //         suggestionsList.style.display = 'none';
    //         currentIndex = -1;
    //     }
    // });

    // function highlightSuggestion(suggestions, index) {
    //     suggestions.forEach((suggestion, i) => {
    //         if (i === index) {
    //             suggestion.style.backgroundColor = '#d3d3d3';
    //             suggestion.scrollIntoView({ block: 'nearest' });
    //         } else {
    //             suggestion.style.backgroundColor = 'white';
    //         }
    //     });
    // }

    // // Hide suggestions when clicking outside
    // document.addEventListener('click', function (event) {
    //     const suggestionsList = document.getElementById('suggestions-list');
    //     if (!suggestionsList.contains(event.target) && event.target.id !== 'reason-for-fnac') {
    //         suggestionsList.style.display = 'none';
    //     }
    // });

    const chiefComplainList = <?= json_encode($chief_complain_list, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
let currentIndex = -1;

function showSuggestions(term) {
    const suggestionsList = document.getElementById('suggestions-list');
    const textarea = document.getElementById('reason-for-fnac');
    suggestionsList.innerHTML = ''; // Clear previous suggestions
    currentIndex = -1; // Reset index

    if (term.length < 2) {
        suggestionsList.style.display = 'none';
        return;
    }

    // Filter matching values
    const filteredList = chiefComplainList
        .filter(item => item.chief_complain.toLowerCase().includes(term.toLowerCase()))
        // Sort results to prioritize the best matches (optional, can be customized)
        .sort((a, b) => a.chief_complain.toLowerCase().indexOf(term.toLowerCase()) - b.chief_complain.toLowerCase().indexOf(term.toLowerCase()));

    // Limit results to top 3 matches
    const topSuggestions = filteredList.slice(0, 3);

    if (topSuggestions.length > 0) {
        topSuggestions.forEach((item, index) => {
            const li = document.createElement('li');
            li.textContent = item.chief_complain;
            li.style.padding = '5px';
            li.style.cursor = 'pointer';
            li.style.borderBottom = '1px solid #ccc';
            li.dataset.index = index;

            // Populate textarea on click
            li.onclick = () => {
                textarea.value = item.chief_complain;
                suggestionsList.style.display = 'none';
            };

            suggestionsList.appendChild(li);
        });
        suggestionsList.style.display = 'block';
    } else {
        suggestionsList.style.display = 'none';
    }
}

document.getElementById('reason-for-fnac').addEventListener('input', function () {
    showSuggestions(this.value);
});

document.getElementById('reason-for-fnac').addEventListener('keydown', function (e) {
    const suggestionsList = document.getElementById('suggestions-list');
    const suggestions = Array.from(suggestionsList.getElementsByTagName('li'));

    if (suggestions.length === 0) return;

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        currentIndex = (currentIndex + 1) % suggestions.length;
        highlightSuggestion(suggestions, currentIndex);
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        currentIndex = (currentIndex - 1 + suggestions.length) % suggestions.length;
        highlightSuggestion(suggestions, currentIndex);
    } else if (e.key === 'Enter') {
        e.preventDefault();
        if (currentIndex >= 0) {
            suggestions[currentIndex].click();
        }
    } else if (e.key === 'Escape') {
        suggestionsList.style.display = 'none';
        currentIndex = -1;
    }
});

function highlightSuggestion(suggestions, index) {
    suggestions.forEach((suggestion, i) => {
        if (i === index) {
            suggestion.style.backgroundColor = '#d3d3d3';
            suggestion.scrollIntoView({ block: 'nearest' });
        } else {
            suggestion.style.backgroundColor = 'white';
        }
    });
}

// Hide suggestions when clicking outside
document.addEventListener('click', function (event) {
    const suggestionsList = document.getElementById('suggestions-list');
    if (!suggestionsList.contains(event.target) && event.target.id !== 'reason-for-fnac') {
        suggestionsList.style.display = 'none';
    }
});

</script>


<!-- FNAC Fixation Details -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const locationOptions = [" ", "Proper", "Thyroid", "Beast", "Lymph Node", "Lung", "Other"];
        let locationCounter = { "Proper": 0 };
        let locationLetters = {};
        let rowCounter = 1;

        function updateSlideCode(row, location) {
            const slideBaseCode = "<?php echo $slideBaseCode; ?>".replace(/-/g, '');
            let slideCode;

            if (location.toLowerCase() === "proper") {
                const proCount = (locationCounter["Proper"] || 0) + 1;
                slideCode = `${slideBaseCode}FC-Pro-${proCount}`;
                locationCounter["Proper"] = proCount;
            } else {
                if (!locationLetters[location]) {
                    const letters = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
                    locationLetters[location] = letters[Object.keys(locationLetters).length];
                    locationCounter[location] = 1;
                }

                const locationLetter = locationLetters[location];
                const locationCount = locationCounter[location];
                slideCode = `${slideBaseCode}FC-${locationLetter}-${locationCount}`;
                locationCounter[location] = locationCount + 1;
            }

            row.querySelector('.slide-code').textContent = slideCode;
        }

        function addRow(location, isDry, aspirationMaterials, specialInstruction) {
            const tbody = document.getElementById('fixation-details-body');
            const newRow = document.createElement('tr');

            newRow.innerHTML = `
                <td>${rowCounter}</td>
                <td class="slide-code"></td>
                <td>${location}</td>
                <td>
                    <select class="form-control fixation-method-select">
                        <option value=""></option>
                        <option value="Alcohol">Alcohol fixation</option>
                        <option value="Formalin">Formalin fixation</option>
                        <option value="Air-dried">Air-dried</option>
                        <option value="Other">Other</option>
                    </select>
                    <input type="text" class="form-control other-fixation-input" placeholder="Specify fixation method" style="display: none;">
                </td>
                <td>
                    <input type="checkbox" class="dry-checkbox" ${isDry ? 'checked' : ''}>
                </td>
                <td>${aspirationMaterials}</td>
                <td> <textarea class="form-control special-instruction-input">${specialInstruction}</textarea></td>
                <td>
                    <button type="button" class="btn btn-danger remove-row">Remove</button>
                </td>
            `;

            const fixationDropdown = newRow.querySelector('.fixation-method-select');
            fixationDropdown.addEventListener('change', function () {
                if (this.value === "Other") {
                    newRow.querySelector('.other-fixation-input').style.display = 'block';
                } else {
                    newRow.querySelector('.other-fixation-input').style.display = 'none';
                }
            });

            newRow.querySelector('.remove-row').addEventListener('click', () => tbody.removeChild(newRow));
            tbody.appendChild(newRow);

            updateSlideCode(newRow, location);
            rowCounter++;
        }

        document.getElementById('populate-table').addEventListener('click', function () {
            const slidesInput = document.getElementById('slides-input').value.trim();
            const locationInput = document.getElementById('location-input').value.trim();
            const aspirationMaterials = document.getElementById('aspiration_materials-input').value.trim();
            const specialInstruction = document.getElementById('special-instruction-input').value.trim();

            // Validate required fields
            if (!slidesInput  || !aspirationMaterials) {
                alert('Please fill in all required fields.');
                return;
            }

            // Parse slide input
            const [fixationSlides, drySlides] = slidesInput.split('+').map(Number);
            if (isNaN(fixationSlides) || isNaN(drySlides)) {
                alert('Invalid slide input format. Use "2+1" format.');
                return;
            }

            // Generate new rows
            for (let i = 0; i < fixationSlides; i++) addRow(locationInput, false, aspirationMaterials, specialInstruction);
            for (let i = 0; i < drySlides; i++) addRow(locationInput, true, aspirationMaterials, specialInstruction);

            // Clear form fields after generating the table
            document.getElementById('location-input').value = '';
            document.getElementById('slides-input').value = '';
            document.getElementById('aspiration_materials-input').value = '';
            document.getElementById('special-instruction-input').value = '';
        });



         // When the form is submitted, collect the fixation details and add them to the form
         document.getElementById("clinical-information-form").addEventListener("submit", function(event) {
           event.preventDefault();
           

        const tbody = document.getElementById('fixation-details-body');
        const fixationData = [];
        const rows = tbody.querySelectorAll('tr');

        rows.forEach(row => {
            const slideNumber = row.querySelector('.slide-code').textContent;
            const location = row.querySelector('td:nth-child(3)').textContent;
            const fixationMethod = row.querySelector('.fixation-method-select').value;
            const isDry = row.querySelector('.dry-checkbox').checked ? 'Yes' : 'No';
            const aspirationMaterials = row.querySelector('td:nth-child(6)').textContent;
            const specialInstruction = row.querySelector('.special-instruction-input').value;

            fixationData.push({
                slideNumber,
                location,
                fixationMethod,
                isDry,
                aspirationMaterials,
                specialInstruction
            });
        });

        fixationData.forEach((data, index) => {
            const form = document.getElementById('clinical-information-form');

            for (let key in data) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `fixation_data[${index}][${key}]`;
                input.value = data[key];
                form.appendChild(input);
            }
        });

        // Create hidden inputs for each piece of data and append them to the form
        fixationData.forEach((data, index) => {
                // Create hidden input for Slide Number
                let input = document.createElement('input');
                input.type = 'hidden';
                input.name = `fixation_data[${index}][slide_number]`;
                input.value = data.slideNumber;
                document.getElementById('clinical-information-form').appendChild(input);

                // Create hidden input for Location
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = `fixation_data[${index}][location]`;
                input.value = data.location;
                document.getElementById('clinical-information-form').appendChild(input);

                // Create hidden input for Fixation Method
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = `fixation_data[${index}][fixation_method]`;
                input.value = data.fixationMethod;
                document.getElementById('clinical-information-form').appendChild(input);

                // Create hidden input for Dry
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = `fixation_data[${index}][dry]`;
                input.value = data.isDry;
                document.getElementById('clinical-information-form').appendChild(input);
            });

        this.submit();
    });
    
    });

</script>


<!-- FNAC Collection Details -->
<script>
    // Show "Other" input when "Others" is selected for Collection Site
    document.getElementById("collection-site").addEventListener("change", function() {
        const otherInput = document.getElementById("other-location");
        if (this.value === "Other") {
            otherInput.style.display = "inline-block";
        } else {
            otherInput.style.display = "none";
        }
    });

    document.querySelectorAll('.toggle-btn').forEach(button => {
        button.addEventListener('click', function () {
            const target = document.querySelector(this.dataset.target);
            if (target.style.display === 'none' || target.style.display === '') {
                target.style.display = 'block';
                this.textContent = '-'; // Change the "+" to "-"
            } else {
                target.style.display = 'none';
                this.textContent = '+'; // Change the "-" back to "+"
            }
        });
    });
</script>

<!-- Dry Slides Description/Additional Notes/Special Instructions or Tests Required -->
<script>
    // Toggle visibility of sections
    document.querySelectorAll('.toggle-btn').forEach(button => {
        button.addEventListener('click', function () {
            const target = document.querySelector(this.dataset.target);
            if (window.getComputedStyle(target).display === 'none') {
                target.style.display = 'block';
                this.textContent = '-'; // Change "+" to "-"
            } else {
                target.style.display = 'none';
                this.textContent = '+'; // Change "-" back to "+"
            }
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Save button functionality
        document.querySelectorAll('.save-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const row = this.closest('tr');
                const rowid = row.getAttribute('data-rowid');
                const formData = new FormData();
                formData.append('rowid', rowid);

                row.querySelectorAll('.edit-field').forEach(input => {
                    formData.append(input.name, input.value);
                });

                fetch('../Cyto/update_patient.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    alert('Data updated successfully!');
                    // Update the displayed values and reset visibility
                    row.querySelectorAll('.text').forEach(el => {
                        const name = el.nextElementSibling.name;
                        el.textContent = formData.get(name);
                        el.classList.remove('d-none');
                    });
                    row.querySelector('.save-btn').classList.add('d-none');
                   
                })
                .catch(err => console.error('Error:', err));
            });
        });
    });

</script>


<!-- On Examination -->
<!-- <script>
    const onExaminationList = <?php echo json_encode($on_examination_list); ?>;
    let currentExamIndex = -1;

    function showExaminationSuggestions(term) {
        const suggestionsList = document.getElementById('examination-suggestions-list');
        const textarea = document.getElementById('site-of-aspiration-editor');
        suggestionsList.innerHTML = ''; // Clear previous suggestions
        currentExamIndex = -1; // Reset index

        // Don't show suggestions for less than 2 characters
        if (term.length < 2) {
            suggestionsList.style.display = 'none';
            return;
        }

        // Filter matching values from the list
        const filteredList = onExaminationList.filter(item =>
            item.on_examination.toLowerCase().includes(term.toLowerCase())
        );

        if (filteredList.length > 0) {
            filteredList.forEach((item, index) => {
                const li = document.createElement('li');
                li.textContent = item.on_examination;
                li.style.padding = '5px';
                li.style.cursor = 'pointer';
                li.setAttribute('data-index', index);

                // On click, populate the textarea and hide suggestions
                li.onclick = () => {
                    textarea.value = item.on_examination;
                    suggestionsList.style.display = 'none';
                };

                suggestionsList.appendChild(li);
            });
            suggestionsList.style.display = 'block';
        } else {
            suggestionsList.style.display = 'none';
        }
    }

    // Handle key events for navigation in suggestions
    document.getElementById('site-of-aspiration-editor').addEventListener('keydown', function (e) {
        const suggestionsList = document.getElementById('examination-suggestions-list');
        const suggestions = suggestionsList.getElementsByTagName('li');

        if (suggestions.length === 0) return;

        if (e.key === 'ArrowDown' || e.key === 'Tab') {
            e.preventDefault();
            currentExamIndex = (currentExamIndex + 1) % suggestions.length;
            highlightExaminationSuggestion(suggestions, currentExamIndex);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            currentExamIndex = (currentExamIndex - 1 + suggestions.length) % suggestions.length;
            highlightExaminationSuggestion(suggestions, currentExamIndex);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (currentExamIndex >= 0) {
                suggestions[currentExamIndex].click();
            }
        } else if (e.key === 'Escape') {
            suggestionsList.style.display = 'none';
            currentExamIndex = -1;
        }
    });

    // Highlight the currently selected suggestion
    function highlightExaminationSuggestion(suggestions, index) {
        for (let i = 0; i < suggestions.length; i++) {
            if (i === index) {
                suggestions[i].style.backgroundColor = '#d3d3d3';
                suggestions[i].scrollIntoView({ block: 'nearest' });
            } else {
                suggestions[i].style.backgroundColor = 'white';
            }
        }
    }

    // Handle input for real-time suggestions
    document.getElementById('site-of-aspiration-editor').addEventListener('input', function () {
        const term = this.value;
        showExaminationSuggestions(term);
    });
</script> -->
<script>
    const onExaminationList = <?php echo json_encode($on_examination_list); ?>;
    let currentExamIndex = -1;

    function showExaminationSuggestions(term) {
        const suggestionsList = document.getElementById('examination-suggestions-list');
        const textarea = document.getElementById('site-of-aspiration-editor');
        suggestionsList.innerHTML = ''; // Clear previous suggestions
        currentExamIndex = -1; // Reset index

        // Don't show suggestions for less than 2 characters
        if (term.length < 2) {
            suggestionsList.style.display = 'none';
            return;
        }

        // Filter matching values from the list
        const filteredList = onExaminationList.filter(item =>
            item.on_examination.toLowerCase().includes(term.toLowerCase())
        );

        // Sort the filtered list by the relevance of the match (can be customized)
        const sortedList = filteredList.sort((a, b) => 
            a.on_examination.toLowerCase().indexOf(term.toLowerCase()) - 
            b.on_examination.toLowerCase().indexOf(term.toLowerCase())
        );

        // Limit the list to top 3 matches
        const topSuggestions = sortedList.slice(0, 1);

        // Display the top 3 suggestions
        if (topSuggestions.length > 0) {
            topSuggestions.forEach((item, index) => {
                const li = document.createElement('li');
                li.textContent = item.on_examination;
                li.style.padding = '5px';
                li.style.cursor = 'pointer';
                li.setAttribute('data-index', index);

                // On click, populate the textarea and hide suggestions
                li.onclick = () => {
                    textarea.value = item.on_examination;
                    suggestionsList.style.display = 'none';
                };

                suggestionsList.appendChild(li);
            });
            suggestionsList.style.display = 'block';
        } else {
            suggestionsList.style.display = 'none';
        }
    }

    // Handle key events for navigation in suggestions
    document.getElementById('site-of-aspiration-editor').addEventListener('keydown', function (e) {
        const suggestionsList = document.getElementById('examination-suggestions-list');
        const suggestions = suggestionsList.getElementsByTagName('li');

        if (suggestions.length === 0) return;

        if (e.key === 'ArrowDown' || e.key === 'Tab') {
            e.preventDefault();
            currentExamIndex = (currentExamIndex + 1) % suggestions.length;
            highlightExaminationSuggestion(suggestions, currentExamIndex);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            currentExamIndex = (currentExamIndex - 1 + suggestions.length) % suggestions.length;
            highlightExaminationSuggestion(suggestions, currentExamIndex);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (currentExamIndex >= 0) {
                suggestions[currentExamIndex].click();
            }
        } else if (e.key === 'Escape') {
            suggestionsList.style.display = 'none';
            currentExamIndex = -1;
        }
    });

    // Highlight the currently selected suggestion
    function highlightExaminationSuggestion(suggestions, index) {
        for (let i = 0; i < suggestions.length; i++) {
            if (i === index) {
                suggestions[i].style.backgroundColor = '#d3d3d3';
                suggestions[i].scrollIntoView({ block: 'nearest' });
            } else {
                suggestions[i].style.backgroundColor = 'white';
            }
        }
    }

    // Handle input for real-time suggestions
    document.getElementById('site-of-aspiration-editor').addEventListener('input', function () {
        const term = this.value;
        showExaminationSuggestions(term);
    });
</script>


<!-- update patient information -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Save button functionality
        document.querySelectorAll('.save-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const row = this.closest('tr');
                const rowid = row.getAttribute('data-rowid');
                const formData = new FormData();
                formData.append('rowid', rowid);

                row.querySelectorAll('.edit-field').forEach(input => {
                    formData.append(input.name, input.value);
                });

                fetch('../Cyto/update_patient.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    alert('Data updated successfully!');
                    // Update the displayed values and reset visibility
                    row.querySelectorAll('.text').forEach(el => {
                        const name = el.nextElementSibling.name;
                        el.textContent = formData.get(name);
                        el.classList.remove('d-none');
                    });
                    row.querySelector('.save-btn').classList.add('d-none');
                   
                })
                .catch(err => console.error('Error:', err));
            });
        });
    });

</script>

<script>
    document.getElementById('togglePatientHistory').addEventListener('click', function () {
        const container = document.getElementById('patientHistoryContainer');
        if (container.style.display === 'none' || container.style.display === '') {
            container.style.display = 'block';
        } else {
            container.style.display = 'none';
        }
    });
</script>

<!-- show values for aspiration note this value is not go in database just show in html-->
<script>
    document.getElementById("populate-table").addEventListener("click", function () {
        // Get input values
        const location = document.getElementById("location-input").value.trim();
        const slides = document.getElementById("slides-input").value.trim();
        const aspirationMaterials = document.getElementById("aspiration_materials-input").value.trim();
        const specialInstruction = document.getElementById("special-instruction-input").value.trim();

        // Initialize an array to collect non-empty values
        const result = [];

        // Check and add non-empty values
        if (location) result.push(`<div><strong>Aspiration:</strong> ${location}</div>`);
        if (aspirationMaterials) result.push(`<div><strong>Aspiration Materials:</strong> ${aspirationMaterials}</div>`);
        if (slides) result.push(`<div><strong>Slide:</strong> ${slides}</div>`);
        if (specialInstruction) result.push(`<div><strong>Special Instruction:</strong> ${specialInstruction}</div>`);

        // Append the result to the display area
        const resultDisplay = document.getElementById("result-display");

        if (result.length > 0) {
            const newEntry = document.createElement("div");
            newEntry.classList.add("alert", "alert-success", "mt-2");

            // Add a container for the result values to align them horizontally
            const horizontalContainer = document.createElement("div");
            horizontalContainer.style.display = 'flex';
            horizontalContainer.style.flexWrap = 'wrap'; // Allow wrapping if there are too many items

            result.forEach(item => {
                const itemDiv = document.createElement("div");
                itemDiv.style.marginRight = '20px'; // Add some space between each item
                itemDiv.innerHTML = item;
                horizontalContainer.appendChild(itemDiv);
            });

            newEntry.appendChild(horizontalContainer); // Append the container to the alert box
            resultDisplay.appendChild(newEntry); // Append the new entry to the display
        } else {
            const warningMessage = document.createElement("div");
            warningMessage.classList.add("alert", "alert-warning", "mt-2");
            warningMessage.innerText = "No values entered. Please fill in the fields.";
            resultDisplay.appendChild(warningMessage);
        }
    });
</script>

<!-- clicnical information -->
<!-- <script>
    // Load clinical history list from PHP
    const clinicalHistoryList = <?= json_encode($clinical_history, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

    // Variables to track suggestions and index
    let currentClinicalIndex = -1;

    // Show suggestions based on input
    function showClinicalHistorySuggestions(term) {
        const suggestionsList = document.getElementById('clinical-history-suggestions');
        const textarea = document.getElementById('clinical-history');
        suggestionsList.innerHTML = ''; // Clear previous suggestions
        currentClinicalIndex = -1; // Reset the index

        // Don't show suggestions for less than 2 characters
        if (term.length < 2) {
            suggestionsList.style.display = 'none';
            return;
        }

        // Filter matching values from clinical history list
        const filteredList = clinicalHistoryList.filter(item =>
            item.relevant_clinical_history.toLowerCase().includes(term.toLowerCase())
        );

        if (filteredList.length > 0) {
            filteredList.forEach((item, index) => {
                const li = document.createElement('li');
                li.textContent = item.relevant_clinical_history;
                li.style.padding = '5px';
                li.style.cursor = 'pointer';
                li.setAttribute('data-index', index);

                // On click, populate the textarea and hide suggestions
                li.onclick = () => {
                    textarea.value = item.relevant_clinical_history;
                    suggestionsList.style.display = 'none';
                };

                suggestionsList.appendChild(li);
            });
            suggestionsList.style.display = 'block';
        } else {
            suggestionsList.style.display = 'none';
        }
    }

    // Handle key events for navigation in suggestions
    document.getElementById('clinical-history').addEventListener('keydown', function (e) {
        const suggestionsList = document.getElementById('clinical-history-suggestions');
        const suggestions = suggestionsList.getElementsByTagName('li');

        if (suggestions.length === 0) return;

        if (e.key === 'ArrowDown' || e.key === 'Tab') {
            e.preventDefault();
            currentClinicalIndex = (currentClinicalIndex + 1) % suggestions.length;
            highlightSuggestion(suggestions, currentClinicalIndex);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            currentClinicalIndex = (currentClinicalIndex - 1 + suggestions.length) % suggestions.length;
            highlightSuggestion(suggestions, currentClinicalIndex);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (currentClinicalIndex >= 0) {
                suggestions[currentClinicalIndex].click();
            }
        } else if (e.key === 'Escape') {
            suggestionsList.style.display = 'none';
            currentClinicalIndex = -1;
        }
    });

    // Highlight the currently selected suggestion
    function highlightSuggestion(suggestions, index) {
        for (let i = 0; i < suggestions.length; i++) {
            if (i === index) {
                suggestions[i].style.backgroundColor = '#d3d3d3';
                suggestions[i].scrollIntoView({ block: 'nearest' });
            } else {
                suggestions[i].style.backgroundColor = 'white';
            }
        }
    }

    // Handle input for real-time suggestions
    document.getElementById('clinical-history').addEventListener('input', function () {
        const term = this.value;
        showClinicalHistorySuggestions(term);
    });

</script> -->
<script>
    // Load clinical history list from PHP
    const clinicalHistoryList = <?= json_encode($clinical_history, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

    // Variables to track suggestions and index
    let currentClinicalIndex = -1;

    // Show suggestions based on input
    function showClinicalHistorySuggestions(term) {
        const suggestionsList = document.getElementById('clinical-history-suggestions');
        const textarea = document.getElementById('clinical-history');
        suggestionsList.innerHTML = ''; // Clear previous suggestions
        currentClinicalIndex = -1; // Reset the index

        // Don't show suggestions for less than 2 characters
        if (term.length < 2) {
            suggestionsList.style.display = 'none';
            return;
        }

        // Filter matching values from clinical history list
        const filteredList = clinicalHistoryList.filter(item =>
            item.relevant_clinical_history.toLowerCase().includes(term.toLowerCase())
        );

        // Sort the filtered list by the relevance of the match (can be customized)
        const sortedList = filteredList.sort((a, b) => 
            a.relevant_clinical_history.toLowerCase().indexOf(term.toLowerCase()) - 
            b.relevant_clinical_history.toLowerCase().indexOf(term.toLowerCase())
        );

        // Limit the list to top 3 matches
        const topSuggestions = sortedList.slice(0, 3);

        // Display the top 3 suggestions
        if (topSuggestions.length > 0) {
            topSuggestions.forEach((item, index) => {
                const li = document.createElement('li');
                li.textContent = item.relevant_clinical_history;
                li.style.padding = '5px';
                li.style.cursor = 'pointer';
                li.setAttribute('data-index', index);

                // On click, populate the textarea and hide suggestions
                li.onclick = () => {
                    textarea.value = item.relevant_clinical_history;
                    suggestionsList.style.display = 'none';
                };

                suggestionsList.appendChild(li);
            });
            suggestionsList.style.display = 'block';
        } else {
            suggestionsList.style.display = 'none';
        }
    }

    // Handle key events for navigation in suggestions
    document.getElementById('clinical-history').addEventListener('keydown', function (e) {
        const suggestionsList = document.getElementById('clinical-history-suggestions');
        const suggestions = suggestionsList.getElementsByTagName('li');

        if (suggestions.length === 0) return;

        if (e.key === 'ArrowDown' || e.key === 'Tab') {
            e.preventDefault();
            currentClinicalIndex = (currentClinicalIndex + 1) % suggestions.length;
            highlightSuggestion(suggestions, currentClinicalIndex);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            currentClinicalIndex = (currentClinicalIndex - 1 + suggestions.length) % suggestions.length;
            highlightSuggestion(suggestions, currentClinicalIndex);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (currentClinicalIndex >= 0) {
                suggestions[currentClinicalIndex].click();
            }
        } else if (e.key === 'Escape') {
            suggestionsList.style.display = 'none';
            currentClinicalIndex = -1;
        }
    });

    // Highlight the currently selected suggestion
    function highlightSuggestion(suggestions, index) {
        for (let i = 0; i < suggestions.length; i++) {
            if (i === index) {
                suggestions[i].style.backgroundColor = '#d3d3d3';
                suggestions[i].scrollIntoView({ block: 'nearest' });
            } else {
                suggestions[i].style.backgroundColor = 'white';
            }
        }
    }

    // Handle input for real-time suggestions
    document.getElementById('clinical-history').addEventListener('input', function () {
        const term = this.value;
        showClinicalHistorySuggestions(term);
    });
</script>


<!-- clinical impression -->
<!-- <script>
    // Ensure the JavaScript is executed after the DOM is fully loaded
    document.addEventListener('DOMContentLoaded', function() {
        const clinicalImpressionList = <?= json_encode($clinical_impression, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
        let currentClinicalImpressionIndex = -1;

        // Function to show suggestions based on the user's input
        function showClinicalImpressionSuggestions(term) {
            const suggestionsList = document.getElementById('clinical-impression-suggestions');
            const textarea = document.getElementById('clinical-impression');
            suggestionsList.innerHTML = ''; 
            currentClinicalImpressionIndex = -1;

            if (term.length < 2) {
                suggestionsList.style.display = 'none';
                return;
            }

            const filteredList = clinicalImpressionList.filter(item =>
                item.clinical_impression.toLowerCase().includes(term.toLowerCase())
            );

            if (filteredList.length > 0) {
                filteredList.forEach((item, index) => {
                    const li = document.createElement('li');
                    li.textContent = item.clinical_impression;
                    li.style.padding = '5px';
                    li.style.cursor = 'pointer';
                    li.setAttribute('data-index', index);

                    // Set the click event to fill the textarea with the suggestion
                    li.onclick = function() {
                        textarea.value = item.clinical_impression;
                        suggestionsList.style.display = 'none';
                    };

                    suggestionsList.appendChild(li);
                });
                suggestionsList.style.display = 'block';
            } else {
                suggestionsList.style.display = 'none';
            }
        }

        // Event listener for input in the textarea
        document.getElementById('clinical-impression').addEventListener('input', function () {
            const term = this.value;
            showClinicalImpressionSuggestions(term);
        });

        // Event listener for keyboard navigation (Up and Down Arrow, Enter, Escape)
        document.getElementById('clinical-impression').addEventListener('keydown', function (e) {
            const suggestionsList = document.getElementById('clinical-impression-suggestions');
            const suggestions = suggestionsList.getElementsByTagName('li');

            if (suggestions.length === 0) return;

            if (e.key === 'ArrowDown' || e.key === 'Tab') {
                e.preventDefault();
                currentClinicalImpressionIndex = (currentClinicalImpressionIndex + 1) % suggestions.length;
                highlightClinicalImpressionSuggestion(suggestions, currentClinicalImpressionIndex);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                currentClinicalImpressionIndex = (currentClinicalImpressionIndex - 1 + suggestions.length) % suggestions.length;
                highlightClinicalImpressionSuggestion(suggestions, currentClinicalImpressionIndex);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (currentClinicalImpressionIndex >= 0) {
                    suggestions[currentClinicalImpressionIndex].click();
                }
            } else if (e.key === 'Escape') {
                suggestionsList.style.display = 'none';
                currentClinicalImpressionIndex = -1;
            }
        });

        // Function to highlight the currently selected suggestion
        function highlightClinicalImpressionSuggestion(suggestions, index) {
            for (let i = 0; i < suggestions.length; i++) {
                if (i === index) {
                    suggestions[i].style.backgroundColor = '#d3d3d3';
                    suggestions[i].scrollIntoView({ block: 'nearest' });
                } else {
                    suggestions[i].style.backgroundColor = 'white';
                }
            }
        }
    });
</script> -->
<script>
    // Ensure the JavaScript is executed after the DOM is fully loaded
    document.addEventListener('DOMContentLoaded', function() {
        const clinicalImpressionList = <?= json_encode($clinical_impression, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
        let currentClinicalImpressionIndex = -1;

        // Function to show suggestions based on the user's input
        function showClinicalImpressionSuggestions(term) {
            const suggestionsList = document.getElementById('clinical-impression-suggestions');
            const textarea = document.getElementById('clinical-impression');
            suggestionsList.innerHTML = ''; 
            currentClinicalImpressionIndex = -1;

            if (term.length < 2) {
                suggestionsList.style.display = 'none';
                return;
            }

            // Filter matching values from the list
            const filteredList = clinicalImpressionList.filter(item =>
                item.clinical_impression.toLowerCase().includes(term.toLowerCase())
            );

            // Sort the filtered list by the relevance of the match (can be customized)
            const sortedList = filteredList.sort((a, b) => 
                a.clinical_impression.toLowerCase().indexOf(term.toLowerCase()) - 
                b.clinical_impression.toLowerCase().indexOf(term.toLowerCase())
            );

            // Limit the list to top 3 matches
            const topSuggestions = sortedList.slice(0, 3);

            // Display the top 3 suggestions
            if (topSuggestions.length > 0) {
                topSuggestions.forEach((item, index) => {
                    const li = document.createElement('li');
                    li.textContent = item.clinical_impression;
                    li.style.padding = '5px';
                    li.style.cursor = 'pointer';
                    li.setAttribute('data-index', index);

                    // Set the click event to fill the textarea with the suggestion
                    li.onclick = function() {
                        textarea.value = item.clinical_impression;
                        suggestionsList.style.display = 'none';
                    };

                    suggestionsList.appendChild(li);
                });
                suggestionsList.style.display = 'block';
            } else {
                suggestionsList.style.display = 'none';
            }
        }

        // Event listener for input in the textarea
        document.getElementById('clinical-impression').addEventListener('input', function () {
            const term = this.value;
            showClinicalImpressionSuggestions(term);
        });

        // Event listener for keyboard navigation (Up and Down Arrow, Enter, Escape)
        document.getElementById('clinical-impression').addEventListener('keydown', function (e) {
            const suggestionsList = document.getElementById('clinical-impression-suggestions');
            const suggestions = suggestionsList.getElementsByTagName('li');

            if (suggestions.length === 0) return;

            if (e.key === 'ArrowDown' || e.key === 'Tab') {
                e.preventDefault();
                currentClinicalImpressionIndex = (currentClinicalImpressionIndex + 1) % suggestions.length;
                highlightClinicalImpressionSuggestion(suggestions, currentClinicalImpressionIndex);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                currentClinicalImpressionIndex = (currentClinicalImpressionIndex - 1 + suggestions.length) % suggestions.length;
                highlightClinicalImpressionSuggestion(suggestions, currentClinicalImpressionIndex);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (currentClinicalImpressionIndex >= 0) {
                    suggestions[currentClinicalImpressionIndex].click();
                }
            } else if (e.key === 'Escape') {
                suggestionsList.style.display = 'none';
                currentClinicalImpressionIndex = -1;
            }
        });

        // Function to highlight the currently selected suggestion
        function highlightClinicalImpressionSuggestion(suggestions, index) {
            for (let i = 0; i < suggestions.length; i++) {
                if (i === index) {
                    suggestions[i].style.backgroundColor = '#d3d3d3';
                    suggestions[i].scrollIntoView({ block: 'nearest' });
                } else {
                    suggestions[i].style.backgroundColor = 'white';
                }
            }
        }
    });
</script>


<!-- Local Storage allows you to store user input in the browser  -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const labNumber = "<?php echo $_GET['LabNumber'] ?? ''; ?>"; // Get LabNumber from PHP
        const storageKey = `formData_${labNumber}`; // Unique key for each LabNumber
        const inputFields = document.querySelectorAll("input, textarea, select"); // Select all form fields

        // Load saved data for this LabNumber
        if (localStorage.getItem(storageKey)) {
            const savedData = JSON.parse(localStorage.getItem(storageKey));
            inputFields.forEach(field => {
                if (savedData[field.id]) {
                    field.value = savedData[field.id];
                }
            });
            console.log(`Data restored for LabNumber: ${labNumber}`);
        } else {
            console.log(`No stored data for LabNumber: ${labNumber}, showing empty form.`);
        }

        // Save data on input change
        inputFields.forEach(field => {
            field.addEventListener("input", () => {
                const formData = {};
                inputFields.forEach(f => {
                    formData[f.id] = f.value;
                });
                localStorage.setItem(storageKey, JSON.stringify(formData));
            });
        });

        // Clear stored data when the form is submitted
        const form = document.querySelector("form");
        if (form) {
            form.addEventListener("submit", function () {
                localStorage.removeItem(storageKey);
                console.log(`LocalStorage cleared for LabNumber: ${labNumber}`);
            });
        }
    });
</script>

<!-- auto slide generate -->
<script>
    document.getElementById("clinical-impression").addEventListener("focus", function() {
        let aspirationInput = document.getElementById("slides-input").value.trim();
        
        if (aspirationInput !== "") {
            document.getElementById("populate-table").click(); // Automatically trigger the button click
        }
    });
</script>


<script>
  // Wait until the DOM is fully loaded
  document.addEventListener("DOMContentLoaded", function() {
        // Get references to the form and the submit button
        const form = document.getElementById('clinical-information-form');
        const saveButton = document.getElementById('saveButton');

        // Prevent form submission if the active element is not the submit button.
        form.addEventListener('submit', function(event) {
        
        if (document.activeElement !== saveButton) {
            event.preventDefault();
            console.log("Form submission prevented: Please use the Submit button to save data.");
            alert("Please click the Submit button to save the form data.");
        }
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