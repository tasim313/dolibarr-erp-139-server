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
    <title>Document</title>
    <link href="../../grossmodule/bootstrap-3.4.1-dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.quilljs.com/2.0.0-dev.3/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/2.0.0-dev.3/quill.js"></script>
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
    <a href="<?= $homeUrl ?>" class="btn btn-info btn-md">Home</a>&nbsp; &nbsp;&nbsp;
    <a href="<?= $reportUrl ?>" class="btn btn-info btn-md" target="_blank">Preview</a>
    <div class="container">
        <div class=" text-center mt-5 ">
            <h3>Update or View Information</h3>
        </div>
        <form id="cyto-information-update" method="post" action="../Cyto/patient_update.php">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Lab Number</th>
                        <th>Patient Code</th>
                        <th>FNA Station Type</th>
                        <th>Doctor</th>
                        <th>Assistant</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    session_start();
                    $records = get_cyto_list($LabNumber);
                    if (!empty($records)) {
                        // Assuming all rowid values are the same, take the first one
                        $_SESSION['rowid'] = $records[0]['rowid'];
                    }
                    if (isset($_SESSION['rowid'])) {
                        $cyto_id = $_SESSION['rowid']; // Access the unique rowid
                    } else {
                        echo "No rowid found in the session.";
                    }
                    foreach ($records as $record) {
                        echo "<tr>
                            
                            <td>{$record['lab_number']}</td>
                            <td>{$record['patient_code']}</td>
                            <td>
                                <select name='fna_station_type[{$record['rowid']}]' class='form-control'>
                                    <option value=''>--Select a Station--</option>
                                    <option value='One' " . ($record['fna_station_type'] === 'One' ? 'selected' : '') . ">One</option>
                                    <option value='Two' " . ($record['fna_station_type'] === 'Two' ? 'selected' : '') . ">Two</option>
                                </select>
                            </td>
                             <td>
                        <select name='doctor[{$record['rowid']}]' class='form-control'>";
                        foreach ($doctors as $doctor) {
                            $selected = $doctor['doctor_username'] === $record['doctor'] ? 'selected' : '';
                            echo "<option value='{$doctor['doctor_username']}' $selected>{$doctor['doctor_username']}</option>";
                        }
                echo "</select>
                    </td>
                    <td>
                        <select name='assistant[{$record['rowid']}]' class='form-control'>";
                        foreach ($assistants as $assistant) {
                            $selected = $assistant['username'] === $record['assistant'] ? 'selected' : '';
                            echo "<option value='{$assistant['username']}' $selected>{$assistant['username']}</option>";
                        }
                echo "</select>
                    </td>
                            <td>
                                <input type='hidden' id='updated_user' name='updated_user' value='$loggedInUsername'>
                                <button class='btn btn-primary btn-sm edit-btn' data-rowid='{$record['rowid']}'>
                                    <i class='fas fa-edit'></i> 
                                </button>
                            </td>  
                        </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </form>

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
                                        <th scope="col">Phone</th>
                                        <th scope="col">Fax</th>
                                        <th scope="col">Date of Birth</th>
                                        <th scope="col">Gender</th>
                                        <th scope="col">Age</th>
                                        <th scope="col">Attendant Name</th>
                                        <th scope="col">Attendant Relation</th>
                                    </tr>
                                </thead>
                            <tbody>
                            <?php foreach ($patient_information as $patient) { 
                                $gender = isset($genderOptions[$patient['Gender']]) ? $genderOptions[$patient['Gender']] : 'Unknown'; // Default to 'Unknown' if gender code is not in the array
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($patient['name']); ?></td>
                                <td><?php echo htmlspecialchars($patient['patient_code']); ?></td>
                                <td><?php echo htmlspecialchars($patient['address']); ?></td>
                                <td><?php echo htmlspecialchars($patient['phone']); ?></td>
                                <td><?php echo htmlspecialchars($patient['fax']); ?></td>
                                <td><?php echo htmlspecialchars($patient['date_of_birth']); ?></td>
                                <td><?php echo $gender; ?></td> <!-- Display gender using the mapped value -->
                                <td><?php echo htmlspecialchars($patient['Age']); ?></td>
                                <td><?php echo htmlspecialchars($patient['att_name']); ?></td>
                                <td><?php echo htmlspecialchars($patient['att_relation']); ?></td>
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
                    $patient_history = get_cyto_patient_history_list($trimmedLabNumber)
                    ?>
                    <div class="">
                        <?php if (!empty($patient_history)): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Previous FNAC</th>
                                            <th>Previous Biopsy Date</th>
                                            <th>Previous Biopsy Operation</th>
                                            <th>Informed</th>
                                            <th>Given</th>
                                            <th>Referred By Dr</th>
                                            <th>Referred From</th>
                                            <th>Additional History</th>
                                            <th>Other Lab No</th>
                                            <th>Prev Biopsy</th>
                                            <th>Prev FNAC Date</th>
                                            <th>Prev FNAC OP</th>
                                            <th>Referred By Dr (Text)</th>
                                            <th>Referred From (Text)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($patient_history as $index => $history): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($history['prev_fnac']) ?></td>
                                                <td><?= htmlspecialchars($history['prev_biopsy_date']) ?></td>
                                                <td><?= htmlspecialchars($history['prev_biopsy_op']) ?></td>
                                                <td>
                                                    <?php
                                                        // Mapping array for informed values
                                                        $informedLabels = [
                                                            1 => 'CT Scan Report',
                                                            2 => 'CT Scan Film',
                                                            3 => 'MRI Report',
                                                            4 => 'MRI Film',
                                                            5 => 'Others'
                                                        ];
                                                        // Process 'informed' values if they are comma-separated
                                                        $informedValues = explode(',', $history['informed']); // Split by comma
                                                        $mappedLabels = array_map(function ($value) use ($informedLabels) {
                                                            return $informedLabels[trim($value)] ?? $value; // Map to label or keep the original value
                                                        }, $informedValues);
                                                        // Join the mapped labels and display
                                                        echo htmlspecialchars(implode(', ', $mappedLabels));
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php
                                                        // Mapping array for given values
                                                        $givenLabels = [
                                                            1 => 'CT Scan Report',
                                                            2 => 'CT Scan Film',
                                                            3 => 'MRI Report',
                                                            4 => 'MRI Film',
                                                            5 => 'Others'
                                                        ];
                                                        // Process 'given' values if they are comma-separated
                                                        $givenValues = explode(',', $history['given']); // Split by comma
                                                        $mappedgivenLabels = array_map(function ($value) use ($givenLabels) {
                                                            return $givenLabels[trim($value)] ?? $value; // Map to label or keep the original value
                                                        }, $givenValues);
                                                        // Join the mapped labels and display
                                                        echo htmlspecialchars(implode(', ', $mappedgivenLabels));
                                                    ?>
                                                    
                                                </td>
                                                <td><?= htmlspecialchars($history['referred_by_dr_lastname']) ?></td>
                                                <td><?= htmlspecialchars($history['referred_from_lastname']) ?></td>
                                                <td><?= htmlspecialchars($history['add_history']) ?></td>
                                                <td><?= htmlspecialchars($history['other_labno']) ?></td>
                                                <td><?= htmlspecialchars($history['prev_biopsy']) ?></td>
                                                <td><?= htmlspecialchars($history['prev_fnac_date']) ?></td>
                                                <td><?= htmlspecialchars($history['prev_fnac_op']) ?></td>
                                                <td><?= htmlspecialchars($history['referred_by_dr_text']) ?></td>
                                                <td><?= htmlspecialchars($history['referredfrom_text']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-center text-danger">No patient history available for the provided lab number.</p>
                        <?php endif; ?>
            </div>

            <div class="mt-4">
                    <h4>Clinical Information</h4>
                    <?php 
                        $clinicalInformation = get_cyto_clinical_information($cyto_id);
                    ?>
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Chief Complain</th>
                                <th>Relevant Clinical History</th>
                                <th>On Examination</th>
                                <th>Clinical Impression</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($clinicalInformation)): ?>
                                <tr><td colspan="5">No data found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($clinicalInformation as $info): ?>
                                    <tr>
                                        <form id="cyto-clinical-information-update" method="post" action="../Cyto/patient_clinical_info_update.php">
                                            <input type="hidden" name="rowid" value="<?= $info['rowid'] ?>">
                                            <input type="hidden" name="username" value="<?= $loggedInUsername ?>">
                                            <td>
                                                <textarea class="form-control" name="chief_complain" data-rowid="<?= $info['rowid'] ?>" data-field="chief_complain"><?= htmlspecialchars($info['chief_complain']) ?></textarea>
                                            </td>
                                            <td>
                                                <textarea class="form-control" name="relevant_clinical_history" data-rowid="<?= $info['rowid'] ?>" data-field="relevant_clinical_history"><?= htmlspecialchars($info['relevant_clinical_history']) ?></textarea>
                                            </td>
                                            <td>
                                                <textarea class="form-control" name="on_examination" data-rowid="<?= $info['rowid'] ?>" data-field="on_examination"><?= htmlspecialchars($info['on_examination']) ?></textarea>
                                            </td>

                                            <td>
                                                <textarea class="form-control" name="clinical_impression" data-rowid="<?= $info['rowid'] ?>" data-field="clinical_impression"><?= htmlspecialchars($info['clinical_impression']) ?></textarea>
                                            </td>
                                     
                                            <td>
                                                <button id="clinicalInformationBtn-<?= $info['rowid'] ?>" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-edit"></i> 
                                                </button>
                                            </td>
                                        </form>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
            </div>

            <h3>Aspiration Note:</h3>
            <!-- Total Slides Prepared -->
            <div class="form-group form-group-slide d-flex align-items-center">
                <label for="location-input" class="mr-2">Aspiration:</label> &nbsp; 
                <input type="text" id="location-input" name="location_input" class="form-control mr-3" placeholder="Enter location (e.g., Proper)" > &nbsp; &nbsp; &nbsp;
                <label for="slides-input" class="mr-2">Slide:</label> &nbsp; 
                <input type="text" id="slides-input" name="slides_input" class="form-control mr-3" placeholder="Enter slide (e.g., 2+1)" > &nbsp; &nbsp; &nbsp;
                <label for="aspiration_materials-input" class="mr-2">Aspiration Materials:</label>&nbsp;
                <input type="text" id="aspiration_materials-input" name="aspiration_materials_input" class="form-control mr-3" placeholder="Enter Aspiration Materials" > &nbsp; &nbsp; &nbsp;
                <label for="special_instruction-input" class="mr-2">Special Instruction:</label>
                <input type="text" id="special-instruction-input" name="special_instruction_input" class="form-control mr-3" placeholder="Enter Special Instruction"> &nbsp; &nbsp; &nbsp;
                
                <button type="button" class="btn btn-primary" id="populate-table">Generate slide</button>
            </div>

            <!-- Slide Fixation Details -->
            <form id="fixation-form" action="../Cyto/fixation_details_update.php" method="POST">
                <div class="form-group">
                    <label>Slide Fixation Details:</label>
                    <input type="hidden" name="LabNumber" value="<?php echo $LabNumber; ?>">
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
                <button type="submit" class="btn btn-success">Save Fixation Details</button>
            </form>


            <div class="mt-4">
                <h4>Fixation Details</h4>
                <?php 
                   $fixationDetails = get_cyto_fixation_details($cyto_id)
                ?>
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Slide Number</th>
                            <th>Location</th>
                            <th>Fixation Method</th>
                            <th>Dry</th>
                            <th>Aspiration Materials</th>
                            <th>Special Instructions</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fixationDetails as $info): ?>
                        <tr>
                            <form method="post" action="../Cyto/fixation_details_update.php">
                                <td>
                                    <input type="text" name="slide_number" class="form-control" 
                                        value="<?= htmlspecialchars($info['slide_number']) ?>">
                                </td>
                                <td>
                                    <input type="text" name="location" class="form-control" 
                                        value="<?= htmlspecialchars($info['location']) ?>">
                                </td>
                                <td>
                                    <input type="text" name="fixation_method" class="form-control" 
                                        value="<?= htmlspecialchars($info['fixation_method']) ?>">
                                </td>
                                <td>
                                    <input type="text" name="dry" class="form-control" 
                                        value="<?= htmlspecialchars($info['dry']) ?>">
                                </td>
                                <td>
                                    <input type="text" name="aspiration_materials" class="form-control" 
                                        value="<?= htmlspecialchars($info['aspiration_materials']) ?>">
                                </td>
                                <td>
                                    <input type="text" name="special_instructions" class="form-control" 
                                        value="<?= htmlspecialchars($info['special_instructions']) ?>">
                                </td>
                                <td>
                                    <!-- Include the row ID as a hidden field -->
                                    <input type="hidden" name="rowid" value="<?= $info['rowid'] ?>">
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="fas fa-edit"  title="Edit"></i>
                                    </button>
                                </td>
                            </form>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php 
                   $fixationAdditionalDetails = get_cyto_fixation_additional_details($cyto_id)
                ?>
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Dry Slide Description</th>
                            <th>Additional Notes on Fixation</th>
                            <th>Number of Needle Used</th>
                            <th>Number of Syringe</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fixationAdditionalDetails as $info): ?>
                        <tr>
                            <form method="post" action="../Cyto/fixation_additional_details.php">
                                <td>
                                    <textarea class="form-control" name="dry_slides_description"><?= htmlspecialchars($info['dry_slides_description']) ?></textarea>
                                </td>
                                <td>
                                    <textarea class="form-control" name="additional_notes_on_fixation"><?= htmlspecialchars($info['additional_notes_on_fixation']) ?></textarea>
                                </td>
                                
                                <td>
                                    <input type="text" class="form-control" name="number_of_needle_used" value="<?= htmlspecialchars($info['number_of_needle_used']) ?>">
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="number_of_syringe_used" value="<?= htmlspecialchars($info['number_of_syringe_used']) ?>">
                                </td>
                                <td>
                                    <input type="hidden" name="rowid" value="<?= $info['rowid'] ?>">
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </td>
                            </form>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
    </div>
</body>
</html>


<!-- FNAC Fixation Details -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const LabNumber = "<?php echo $LabNumber; ?>";  // Fetch LabNumber from PHP
        let locationCounter = {};
        let locationLetters = {};
        let rowCounter = 1;

        function updateSlideCode(row, location) {
            let slideCode;

            if (!locationCounter[location]) {
                locationCounter[location] = 1;
            } else {
                locationCounter[location]++;
            }

            slideCode = `${LabNumber}FC-${location}-${locationCounter[location]}`;
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
                <td><input type="checkbox" class="dry-checkbox" ${isDry ? 'checked' : ''}></td>
                <td><input type="text" class="form-control aspiration-materials-input" value="${aspirationMaterials}"></td>
                <td><textarea class="form-control special-instruction-input">${specialInstruction}</textarea></td>
                <td><button type="button" class="btn btn-danger remove-row">Remove</button></td>
            `;

            newRow.querySelector('.fixation-method-select').addEventListener('change', function () {
                const otherInput = newRow.querySelector('.other-fixation-input');
                otherInput.style.display = this.value === "Other" ? 'block' : 'none';
            });

            newRow.querySelector('.remove-row').addEventListener('click', () => {
                tbody.removeChild(newRow);
                rowCounter--; // Adjust row count
            });

            tbody.appendChild(newRow);
            updateSlideCode(newRow, location);
            rowCounter++;
        }

        document.getElementById('populate-table').addEventListener('click', function () {
            const slidesInput = document.getElementById('slides-input').value.trim();
            const locationInput = document.getElementById('location-input').value.trim();
            const aspirationMaterials = document.getElementById('aspiration_materials-input').value.trim();
            const specialInstruction = document.getElementById('special-instruction-input').value.trim();

            if (!slidesInput || !aspirationMaterials || !locationInput) {
                alert('Please fill in all required fields.');
                return;
            }

            const [fixationSlides, drySlides] = slidesInput.split('+').map(Number);
            if (isNaN(fixationSlides) || isNaN(drySlides)) {
                alert('Invalid slide input format. Use "2+1" format.');
                return;
            }

            for (let i = 0; i < fixationSlides; i++) addRow(locationInput, false, aspirationMaterials, specialInstruction);
            for (let i = 0; i < drySlides; i++) addRow(locationInput, true, aspirationMaterials, specialInstruction);

            document.getElementById('location-input').value = '';
            document.getElementById('slides-input').value = '';
            document.getElementById('aspiration_materials-input').value = '';
            document.getElementById('special-instruction-input').value = '';
        });

        document.getElementById("fixation-form").addEventListener("submit", function (event) {
            event.preventDefault();
            const tbody = document.getElementById('fixation-details-body');
            const rows = tbody.querySelectorAll('tr');

            document.querySelectorAll('.hidden-fixation-input').forEach(input => input.remove());

            rows.forEach((row, index) => {
                const slideNumber = row.querySelector('.slide-code').textContent;
                const location = row.querySelector('td:nth-child(3)').textContent;
                const fixationMethod = row.querySelector('.fixation-method-select').value;
                const isDry = row.querySelector('.dry-checkbox').checked ? 'Yes' : 'No';
                const aspirationMaterials = row.querySelector('.aspiration-materials-input').value;
                const specialInstruction = row.querySelector('.special-instruction-input').value;

                ['slideNumber', 'location', 'fixationMethod', 'isDry', 'aspirationMaterials', 'specialInstruction'].forEach(key => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = `fixation_data[${index}][${key}]`;
                    input.value = eval(key);
                    input.classList.add('hidden-fixation-input');
                    document.getElementById("fixation-form").appendChild(input);
                });
            });

            this.submit();
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