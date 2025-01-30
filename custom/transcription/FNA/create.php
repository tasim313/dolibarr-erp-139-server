<?php 

include("../connection.php");
include("../../grossmodule/gross_common_function.php");
include('../../transcription/common_function.php');
include ("../../cytology/common_function.php");
include ("function.php");
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

llxHeader("", $langs->trans("Trancription FNAC"));



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
$homeUrl = "http://" . $host . "/custom/transcription/FNA/index.php";
$reportUrl = "http://" . $host . "/custom/transcription/FNA/fna_report.php?LabNumber=" . urlencode($LabNumber) . "&username=" . urlencode($loggedInUsername);

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link href="../../grossmodule/bootstrap-3.4.1-dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Include Quill's CSS and JS -->
    <link href="https://cdn.quilljs.com/2.0.0-dev.3/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/2.0.0-dev.3/quill.js"></script>
</head>
<body>
    <a href="<?= $homeUrl ?>" class="btn btn-info btn-md">Home</a>&nbsp;&nbsp;&nbsp;
    <a href="<?= $reportUrl ?>" class="btn btn-info btn-md" target="_blank">Preview</a>&nbsp;&nbsp;&nbsp;
    <button class="btn btn-info btn-md" onclick="history.back()">Back</button>
    <div class="container">
        <div class=" text-center mt-5 ">
            <h3>Microscopic Details</h3>
        </div>
        <form id="cyto-information-update" method="post" action="../../cytology/Cyto/patient_update.php">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Lab Number</th>
                        <th>Patient Code</th>
                        <th>FNA Station Type</th>
                        <th>Doctor</th>
                        <th>Assistant</th>
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
                                <select name='fna_station_type[{$record['rowid']}]' class='form-control' readonly>
                                    <option value=''>--Select a Station--</option>
                                    <option value='One' " . ($record['fna_station_type'] === 'One' ? 'selected' : '') . ">One</option>
                                    <option value='Two' " . ($record['fna_station_type'] === 'Two' ? 'selected' : '') . ">Two</option>
                                </select>
                            </td>
                             <td>
                        <select name='doctor[{$record['rowid']}]' class='form-control' readonly>";
                        foreach ($doctors as $doctor) {
                            $selected = $doctor['doctor_username'] === $record['doctor'] ? 'selected' : '';
                            echo "<option value='{$doctor['doctor_username']}' $selected>{$doctor['doctor_username']}</option>";
                        }
                echo "</select>
                    </td>
                    <td>
                        <select name='assistant[{$record['rowid']}]' class='form-control' readonly>";
                        foreach ($assistants as $assistant) {
                            $selected = $assistant['username'] === $record['assistant'] ? 'selected' : '';
                            echo "<option value='{$assistant['username']}' $selected>{$assistant['username']}</option>";
                        }
                echo "</select>
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
                
            ?>
            
            <!-- Patient Information -->
            <div>
                <div class="row">
                    <div class="col-12">
                        <h4 class="mb-4">Patient Information</h4>
                        <?php 
                        print('<form id="patientForm" method="post" action="../patient_info_update.php">'); 
                        foreach ($patient_information as $list) {
                            $genderOptions = [
                                '1' => 'Male',
                                '2' => 'Female',
                                '3' => 'Other'
                            ];
                            $currentGender = $list['Gender'];
                            
                            print('
                            <table class="table table-bordered table-striped">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Name</th> 
                                    <th>Patient Code</th>
                                    <th>Address</th>
                                    <th>Phone</th>
                                    <th>Attendant Number</th>
                                    <th>Date of Birth</th>
                                    <th>Age</th>
                                    <th>Attendant Name</th>
                                    <th>Attendant Relation</th>
                                    <th>Gender</th>			
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <input type="text" name="name[]" class="form-control" value="' . $list['name'] . '" placeholder="Patient Name">
                                        <input type="hidden" name="rowid[]" value="' . $list['rowid'] . '">
                                    </td> 
                                    <td>
                                        <input type="text" name="patient_code[]" class="form-control" value="' . $list['patient_code'] . '" placeholder="Patient Code">
                                    </td> 
                                    <td>
                                        <input type="text" name="address[]" class="form-control" value="' . $list['address'] . '" placeholder="Patient Address">
                                    </td>
                                    <td>
                                        <input type="text" name="phone[]" class="form-control" value="' . $list['phone'] . '" placeholder="Mobile Number">
                                    </td> 
                                    <td>
                                        <input type="text" name="fax[]" class="form-control" value="' . $list['fax'] . '" placeholder="Attendant Number">
                                    </td> 
                                    <td>
                                        <input type="date" name="date_of_birth[]" class="form-control" value="' . $list['date_of_birth'] . '">
                                    </td> 
                                    <td>
                                        <input type="number" name="age[]" class="form-control" value="' . $list['Age'] . '" placeholder="Age">
                                    </td> 
                                    <td>
                                        <input type="text" name="att_name[]" class="form-control" value="' . $list['att_name'] . '" placeholder="Attendant Name">
                                    </td>
                                    <td>
                                        <input type="text" name="att_relation[]" class="form-control" value="' . $list['att_relation'] . '" placeholder="Attendant Relation">
                                    </td>
                                    <td>
                                        <select name="gender[]" class="form-select">');
                                        foreach ($genderOptions as $value => $label) {
                                            echo '<option value="' . $value . '" ' . ($currentGender == $value ? 'selected' : '') . '>' . $label . '</option>';
                                        }
                                        print('</select>
                                    </td>
                                    <td>
                                        <button type="submit" name="submit" value="update" class="btn btn-primary btn-sm">Save</button>
                                    </td>
                                </tr>
                            </tbody>
                            </table>
                            ');
                        }
                        print('</form>');
                        ?>
                    </div>
                </div>
            </div>

            <!-- Clinical Information -->
            <div> 
                    <!-- Clinical Information -->
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
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($clinicalInformation)): ?>
                                    <tr><td colspan="5">No data found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($clinicalInformation as $info): ?>
                                        <tr>
                                                <td>
                                                    <?= htmlspecialchars($info['chief_complain']) ?>
                                                </td>
                                                <td>
                                                   <?= htmlspecialchars($info['relevant_clinical_history']) ?>
                                                </td>
                                                
                                                <td>
                                                    <?= htmlspecialchars($info['on_examination']) ?>
                                                </td>
                                                <td>
                                                    <?= htmlspecialchars($info['clinical_impression']) ?>
                                                </td>
                                               
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

            </diV>

            <!-- Fixation Details -->
            
        
                    <!-- <div class="mt-4">
                        <?php 
                            $fixationInformation = get_cyto_fixation_details($cyto_id);
                            
                        ?>
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Location</th>
                                    <th>Slide Number</th>
                                    <th>Aspiration Materials</th>
                                    <th>Special Instruction</th>
                                    <th>Fixation Method</th>
                                    <th>Dry</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($fixationInformation)): ?>
                                    <tr><td colspan="5">No data found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($fixationInformation as $info_fixation): ?>
                                        <tr>
                                                <td>
                                                    <?= htmlspecialchars($info_fixation['location']) ?>
                                                </td>
                                                <td>
                                                   <?= htmlspecialchars($info_fixation['slide_number']) ?>
                                                </td>
                                                
                                                <td>
                                                    <?= htmlspecialchars($info_fixation['aspiration_materials']) ?>
                                                </td>

                                                <td>
                                                    <?= htmlspecialchars($info_fixation['special_instructions']) ?>
                                                </td>

                                                <td>
                                                    <?= htmlspecialchars($info_fixation['fixation_method']) ?>
                                                </td>

                                                <td>
                                                    <?= htmlspecialchars($info_fixation['dry']) ?>
                                                </td>
                                               
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div> -->
                <?php 
                        $fixationInformation = get_cyto_fixation_details($cyto_id);

                        // Initialize variables for counting and concatenating
                        $dryYesCount = 0;
                        $dryNoCount = 0;
                        $aspirationMaterials = [];
                        $specialInstructions = [];
                        $locations = [];

                        if (!empty($fixationInformation)) {
                            foreach ($fixationInformation as $info_fixation) {
                                // Count the dry values
                                if ($info_fixation['dry'] === 'Yes') {
                                    $dryYesCount++;
                                } elseif ($info_fixation['dry'] === 'No') {
                                    $dryNoCount++;
                                }

                                // Collect aspiration materials and special instructions for later display
                                if (!in_array($info_fixation['aspiration_materials'], $aspirationMaterials)) {
                                    $aspirationMaterials[] = $info_fixation['aspiration_materials'];
                                }
                                if (!in_array($info_fixation['special_instructions'], $specialInstructions)) {
                                    $specialInstructions[] = $info_fixation['special_instructions'];
                                }

                                // Collect unique locations
                                if (!in_array($info_fixation['location'], $locations)) {
                                    $locations[] = $info_fixation['location'];
                                }
                            }
                        }
                ?>
                <?php
                    // Remove empty values from the array
                    $cleanedLocations = array_filter(array_map('trim', $locations));
                    $cleanedSpecialInstructions = array_filter(array_map('trim', $specialInstructions));

                    // Ensure values are properly checked
                    $showLocation = !empty($cleanedLocations);
                    $showSlide = !empty($dryNoCount) || !empty($dryYesCount);
                    $showSpecialInstructions = !empty($cleanedSpecialInstructions);

                    // If at least one section has data, display the table
                    if ($showLocation || $showSlide || $showSpecialInstructions): ?>
                        <table class="table" style="border-collapse: collapse; width: 100%; border-top: none; margin-top:-20px;">
                            <tbody>
                                <tr>
                                    <td  style="padding: 8px; border: none; font-size:16px;">
                                        <b>A/M:</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?= implode(', ', $aspirationMaterials) ?>
                                    </td>

                                    <?php if ($showSlide): ?>
                                        <td style="padding: 8px; border: none; font-size:18px;">
                                            <b>Slide:</b> <?= htmlspecialchars($dryNoCount) ?>+<?= htmlspecialchars($dryYesCount) ?>
                                        </td>
                                    <?php endif; ?>

                                    
                                </tr>
                            </tbody>
                        </table>
                <?php endif; ?>
            
            <!-- Recall -->
            <?php 
        
             $formatted_LabNumber = substr($LabNumber, 3);
             $recall_status = cyto_recall_lab_number($formatted_LabNumber);
             
                if ($recall_status ) {
                    $recall_cyto_id = $recall_status['rowid'];
                    // recall clinical information
                    $recall_clinical_information = cyto_recall_clinical_information($recall_cyto_id);
                        // Check if we have valid data or an error message
                        if (is_array($recall_clinical_information)) {
                            // If data is found, create a table to display it
                            echo("<h3>Recall Information</h3><br>");
                            echo '<table class="table table-bordered table-striped">';
                            
                            // List of fields to exclude
                            $exclude_fields = ['rowid', 'cyto_id', 'aspiration_note'];

                            // Create a row for the field names (headers)
                            echo '<tr>';
                            foreach ($recall_clinical_information as $field => $value) {
                                // Skip the fields we want to exclude
                                if (in_array($field, $exclude_fields)) {
                                    continue;
                                }

                                // If the field exists and is not empty, display the field name
                                if (!empty($value)) {
                                    echo '<th>' . ucfirst(str_replace('_', ' ', $field)) . '</th>'; // Field Name
                                }
                            }
                            echo '</tr>';

                            // Create a row for the field values
                            echo '<tr>';
                            foreach ($recall_clinical_information as $field => $value) {
                                // Skip the fields we want to exclude
                                if (in_array($field, $exclude_fields)) {
                                    continue;
                                }

                                // If the field exists and is not empty, display the value
                                if (!empty($value)) {
                                    echo '<td>' . htmlspecialchars($value) . '</td>'; // Value
                                }
                            }
                            echo '</tr>';

                            echo '</table>';
                        } else {
                            // If no data was found or there's an error
                            echo '<p>' . $recall_clinical_information . '</p>';
                        }
                        
                        // recall fixation details
                        $recall_fixation_details = cyto_recall_fixation_details($recall_cyto_id);
                        if (is_array($recall_fixation_details)) {
                            // If data is found, create a table to display it
                            echo '<table class="table table-bordered table-striped">';
                            
                            // List of fields to exclude
                            $exclude_fields = ['rowid', 'cyto_id'];
                        
                            // Create a row for the field names (headers)
                            echo '<tr>';
                            foreach ($recall_fixation_details as $field => $value) {
                                // Skip the fields we want to exclude
                                if (in_array($field, $exclude_fields)) {
                                    continue;
                                }
                        
                                // If the field exists and is not empty, display the field name
                                if (!empty($value)) {
                                    echo '<th>' . ucfirst(str_replace('_', ' ', $field)) . '</th>'; // Field Name
                                }
                            }
                            echo '</tr>';
                        
                            // Create a row for the field values
                            echo '<tr>';
                            foreach ($recall_fixation_details as $field => $value) {
                                // Skip the fields we want to exclude
                                if (in_array($field, $exclude_fields)) {
                                    continue;
                                }
                        
                                // If the field exists and is not empty, display the value
                                if (!empty($value)) {
                                    echo '<td>' . htmlspecialchars($value) . '</td>'; // Value
                                }
                            }
                            echo '</tr>';
                        
                            echo '</table>';
                        } else {
                            // If no data was found or there's an error
                            echo '<p>' . $recall_fixation_details . '</p>';
                        }
                    
                } 
            ?>
           
            

            <?php
                // Call the function to get diagnosis data by lab number
                $diagnosis_by_doctor = cyto_diagnosis_by_lab_number($formatted_LabNumber);

                // Check if data is found (i.e., $diagnosis_by_doctor is not an empty array)
                if (!empty($diagnosis_by_doctor) && isset($diagnosis_by_doctor[0])) {
                    // Display the data in a table
                    echo '<table class="table table-bordered table-striped">';
                    echo '<tr><th>Microscopic Description And Diagnosis</th></tr>';

                    // Loop through the diagnosis data and display in the table
                    foreach ($diagnosis_by_doctor as $diagnosis) {
                        echo '<tr>';
                        echo '<td><b>' . htmlspecialchars($diagnosis['diagnosis']) . '</b></td>';

                        // Check if there is previous diagnosis data
                        if (!empty($diagnosis['previous_diagnosis'])) {
                            $previousDiagnosisData = json_decode($diagnosis['previous_diagnosis'], true);
                            $previousDiagnosisText = '';

                            // Loop through each entry in the previous diagnosis data
                            foreach ($previousDiagnosisData as $entry) {
                                $previousDiagnosisText .= "Previous: " . htmlspecialchars($entry['previous']) . "<br>";
                                $previousDiagnosisText .= "Date: " . htmlspecialchars($entry['Date']) . "<br>";
                                $previousDiagnosisText .= "Created by: " . htmlspecialchars($entry['created_user']) . "<br>";
                                $previousDiagnosisText .= "Updated by: " . htmlspecialchars($entry['updated_user']) . "<br><br>";
                            }

                            // Display the formatted previous diagnosis data
                            // echo '<td>' . $previousDiagnosisText . '</td>';
                        } else {
                            echo '<td></td>';
                        }

                        echo '</tr>';
                    }

                    echo '</table>';
                } else {
                    // If no data is found, do not display the table
                    echo '<p></p>';
                }
            ?>



            <?php
                // Fetch data using the function
                $data = cyto_microscopic_description_lab($LabNumber);
            ?>
            <!-- Microscopic Decscription-->
            <div class="container">
                <div class="mt-4">
                    <h4>Microscopic Information</h4>
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Chief Complain</th>
                                <th>Aspiration Notes</th>
                                <th>Gross Note</th>
                                <th>Microscopic Description</th>
                                <th>Conclusion</th>
                                <th>Comment</th>
                                <th>Recall</th>
                                <th>Edit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <!-- Chief Complain (New Field) -->
                                <td>
                                    <div id="chief-complain-container" class="quill-editor">
                                        <?= htmlspecialchars_decode($data['chief_complain'] ?? ''); ?>
                                    </div>
                                </td>

                                <!-- Aspiration Notes -->
                                <td>
                                    <div id="aspiration-notes-container" class="quill-editor">
                                        <?= htmlspecialchars_decode(!empty($data['aspiration_notes']) ? $data['aspiration_notes'] : ($info['on_examination'] ?? '')); ?>
                                    </div>
                                </td>

                                <!-- Gross Note -->
                                <td>
                                    <div id="gross-note-container" class="quill-editor">
                                        <?= htmlspecialchars_decode($data['gross_note'] ?? ''); ?>
                                    </div>
                                </td>

                                <!-- Microscopic Description -->
                                <td>
                                    <div id="microscopic-description-container" class="quill-editor">
                                        <?= htmlspecialchars_decode($data['microscopic_description'] ?? ''); ?>
                                    </div>
                                </td>

                                <!-- Conclusion -->
                                <td>
                                    <div id="conclusion-description-container" class="quill-editor">
                                        <?= htmlspecialchars_decode($data['conclusion'] ?? ''); ?>
                                    </div>
                                </td>

                                <!-- Comment -->
                                <td>
                                    <div id="comment-description-container" class="quill-editor">
                                        <?= htmlspecialchars_decode($data['comment'] ?? ''); ?>
                                    </div>
                                </td>

                                <!-- Recall (New Field) -->
                                <td>
                                    <div id="recall-description-container" class="quill-editor">
                                        <?= htmlspecialchars_decode($data['recall'] ?? ''); ?>
                                    </div>
                                </td>

                                <!-- Action -->
                                <td>
                                    <button id="editMicroscopicBtn" class="btn btn-primary btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button id="saveMicroscopicBtn" class="btn btn-primary btn-sm" style="display: none;">
                                        <i class="fas fa-save"></i> Save
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            
            <?php 
                    $details = get_doctor_assisted_by_signature_details($LabNumber);
                    $finalized_by = get_doctor_finalized_by_signature_details($LabNumber);
                    $information = get_doctor_degination_details();
            ?>

            <style>
                .container {
                    margin-top: 20px;
                }
                .form-control {
                    margin-bottom: 15px;
                }
                .row {
                    display: flex;
                    flex-direction: column;
                    margin-bottom: 15px;
                }
                .row label {
                    font-weight: bold;
                    margin-bottom: 5px;
                }
                .btn-primary {
                    width: 100%;
                }
            </style>

            <div class="container">
                <?php if (!empty($details)): ?>
                    <?php foreach ($details as $list): ?>
                        <div class="card mb-4">
                            <div class="card-header ">
                                <h5 class="mb-0">Assisted By</h5>
                            </div>
                            <div class="card-body">
                                <form method="post" action="./insert/doctor_signature_update.php">
                                    <div class="form-group">
                                        <label for="doctor_username">Doctor</label>
                                        <select id="doctor_username" name="doctor_username" class="form-control">
                                            <option value=""></option>
                                            <?php foreach ($information as $list_info): ?>
                                                <option value="<?= $list_info['username'] ?>" <?= $list_info['username'] == $list['username'] ? 'selected' : '' ?>>
                                                    <?= $list_info['username'] ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <input type="hidden" name="lab_number" value="<?= $LabNumber ?>" readonly>
                                    <input type="hidden" name="created_user" value="<?= htmlspecialchars($loggedInUsername) ?>">
                                    <input type="hidden" name="row_id" value="<?= htmlspecialchars($list['row_id']) ?>">
                                    <button type="submit" class="btn btn-primary">Update</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Create Assisted By</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" action="./insert/doctor_assist_create.php">
                                <div class="form-group">
                                    <label for="doctor_username">Doctor</label>
                                    <select id="doctor_username" name="doctor_username" class="form-control">
                                        <option value=""></option>
                                        <?php foreach ($information as $list): ?>
                                            <option value="<?= $list['username'] ?>" <?= $list['username'] == $loggedInUsername ? 'selected' : '' ?>>
                                                <?= $list['username'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <input type="hidden" name="lab_number" value="<?= $LabNumber ?>" readonly>
                                <input type="hidden" name="created_user" value="<?= htmlspecialchars($loggedInUsername) ?>">
                                <button type="submit" class="btn btn-success">Save</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($finalized_by)): ?>
                    <?php foreach ($finalized_by as $list): ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Finalized By</h5>
                            </div>
                            <div class="card-body">
                                <form method="post" action="./insert/doctor_signature_finalized_update.php">
                                    <div class="form-group">
                                        <label for="doctor_username">Doctor</label>
                                        <select id="doctor_username" name="doctor_username" class="form-control">
                                            <option value=""></option>
                                            <?php foreach ($information as $list_info): ?>
                                                <option value="<?= $list_info['username'] ?>" <?= $list_info['username'] == $list['username'] ? 'selected' : '' ?>>
                                                    <?= $list_info['username'] ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <input type="hidden" name="lab_number" value="<?= $LabNumber ?>" readonly>
                                    <input type="hidden" name="created_user" value="<?= htmlspecialchars($loggedInUsername) ?>">
                                    <input type="hidden" name="row_id" value="<?= htmlspecialchars($list['row_id']) ?>">
                                    <button type="submit" class="btn btn-primary">Update</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Create Finalized By</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" action="./insert/doctor_finalized_create.php">
                                <div class="form-group">
                                    <label for="doctor_username">Doctor</label>
                                    <select id="doctor_username" name="doctor_username" class="form-control">
                                        <option value=""></option>
                                        <?php foreach ($information as $list): ?>
                                            <option value="<?= $list['username'] ?>" <?= $list['username'] == $loggedInUsername ? 'selected' : '' ?>>
                                                <?= $list['username'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <input type="hidden" name="lab_number" value="<?= $LabNumber ?>" readonly>
                                <input type="hidden" name="created_user" value="<?= htmlspecialchars($loggedInUsername) ?>">
                                <input type="hidden" name="status" value="Finalized">
                                <button type="submit" class="btn btn-success">Save</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>


    </div>
</body>
</html>



<!-- Rich text editor for Microscopic Information -->
<script src="https://cdn.quilljs.com/2.0.0-dev.3/quill.js"></script>
<script>
        let isEditing = false;

        const aspirationNotesEditor = new Quill('#aspiration-notes-container', {
            theme: 'snow',
            readOnly: true,
            placeholder: 'Aspiration Notes',
            modules: { toolbar: false }
        });

        aspirationNotesEditor.root.innerHTML = `<?= htmlspecialchars_decode($data['aspiration_notes'] ?? htmlspecialchars($info['on_examination'] ?? '')); ?>`;

        const grossNoteEditor = new Quill('#gross-note-container', {
            theme: 'snow',
            readOnly: true,
            placeholder: 'Gross Note',
            modules: { toolbar: false }
        });


        // Initialize Quill editors
        const microscopicDescriptionEditor = new Quill('#microscopic-description-container', {
            theme: 'snow',
            readOnly: true,
            placeholder: 'Microscopic Description',
            modules: { toolbar: false }
        });
        microscopicDescriptionEditor.root.innerHTML = `<?= htmlspecialchars_decode($data['microscopic_description'] ?? ''); ?>`;

        const conclusionDescriptionEditor = new Quill('#conclusion-description-container', {
            theme: 'snow',
            readOnly: true,
            placeholder: 'Conclusion',
            modules: { toolbar: false }
        });
        conclusionDescriptionEditor.root.innerHTML = `<?= htmlspecialchars_decode($data['conclusion'] ?? ''); ?>`;

        const commentDescriptionEditor = new Quill('#comment-description-container', {
            theme: 'snow',
            readOnly: true,
            placeholder: 'Comment',
            modules: { toolbar: false }
        });
        commentDescriptionEditor.root.innerHTML = `<?= htmlspecialchars_decode($data['comment'] ?? ''); ?>`;

        const recallDescriptionEditor = new Quill('#recall-description-container', {
            theme: 'snow',
            readOnly: true,
            placeholder: 'Recall',
            modules: { toolbar: false }
        });
        recallDescriptionEditor.root.innerHTML = `<?= htmlspecialchars_decode($data['recall'] ?? ''); ?>`;

        // Initialize Quill editor for Chief Complain (New Field)
        const chiefComplainEditor = new Quill('#chief-complain-container', {
            theme: 'snow',
            readOnly: true,
            placeholder: 'Chief Complain',
            modules: { toolbar: false }
        });
        chiefComplainEditor.root.innerHTML = `<?= htmlspecialchars_decode($data['chief_complain'] ?? ''); ?>`;

        // Edit button handler
        document.getElementById('editMicroscopicBtn').addEventListener('click', function () {
            if (!isEditing) {
                // Enable editing
                aspirationNotesEditor.enable(); 
                grossNoteEditor.enable();  
                microscopicDescriptionEditor.enable();
                conclusionDescriptionEditor.enable();
                commentDescriptionEditor.enable();
                recallDescriptionEditor.enable();
                chiefComplainEditor.enable();

                // Show Save button
                document.getElementById('editMicroscopicBtn').style.display = 'none';
                document.getElementById('saveMicroscopicBtn').style.display = 'inline-block';

                isEditing = true;
            }
        });

        // Save button handler
        document.getElementById('saveMicroscopicBtn').addEventListener('click', function () {
            if (isEditing) {
                // Gather data from editors (HTML content)
                const aspirationNotes = aspirationNotesEditor.root.innerHTML.trim();
                const grossNote = grossNoteEditor.root.innerHTML.trim(); 
                const microscopicDescription = microscopicDescriptionEditor.root.innerHTML.trim();
                const conclusionDescription = conclusionDescriptionEditor.root.innerHTML.trim();
                const commentDescription = commentDescriptionEditor.root.innerHTML.trim();
                const recallDescription = recallDescriptionEditor.root.innerHTML.trim();
                const chiefComplainDescription = chiefComplainEditor.root.innerHTML.trim(); // Get data from Chief Complain

                // Disable editing
                aspirationNotesEditor.disable();
                grossNoteEditor.disable();
                microscopicDescriptionEditor.disable();
                conclusionDescriptionEditor.disable();
                commentDescriptionEditor.disable();
                recallDescriptionEditor.disable();
                chiefComplainEditor.disable();

                // Prepare data for submission
                const formData = new FormData();
                formData.append('aspiration-notes', aspirationNotes);
                formData.append('gross-note', grossNote);
                formData.append('microscopic-description', microscopicDescription);
                formData.append('conclusion-description', conclusionDescription);
                formData.append('comment-description', commentDescription);
                formData.append('chief-complain', chiefComplainDescription);
                formData.append('recall-description', recallDescription);
                formData.append('LabNumber', '<?= htmlspecialchars($LabNumber); ?>');
                formData.append('created_user', '<?= htmlspecialchars($loggedInUsername); ?>');

                // AJAX to submit data
                fetch('./insert/microscopic_create.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(result => {
                    alert('Microscopic information updated successfully!');
                    console.log(result);
                })
                .catch(error => {
                    console.error('Error:', error);
                });

                // Reset buttons
                document.getElementById('saveMicroscopicBtn').style.display = 'none';
                document.getElementById('editMicroscopicBtn').style.display = 'inline-block';

                isEditing = false;
            }
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