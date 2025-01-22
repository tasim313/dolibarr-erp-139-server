<?php 

include('../connection.php');
include('../../transcription/common_function.php');
include('../../grossmodule/gross_common_function.php');
include('../../histolab/histo_common_function.php');
include('../list_of_function.php');
include('../../cytology/common_function.php');
include('../../transcription/FNA/function.php');

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

llxHeader("", $langs->trans("Edit FNAC"));



$loggedInUsername = $user->login;
$loggedInUserId = $user->id;

$isCytoAssistant = false;
$isDoctor = false;

$isAdmin = isUserAdmin($loggedInUserId);

$LabNumber = $_GET['labno'];


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
            <div class="mt-4">
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
                                    <th>Address</th>
                                    <th>Phone</th>
                                    <th>Attendant Number</th>
                                    <th>Age</th>
                                    <th>Gender</th>			
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <input type="text" name="name[]" class="form-control" value="' . $list['name'] . '" placeholder="Patient Name">
                                        <input type="hidden" name="rowid[]" value="' . $list['rowid'] . '">
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
                                        <input type="number" name="age[]" class="form-control" value="' . $list['Age'] . '" placeholder="Age">
                                    </td> 
                                    
                                    <td>
                                        <select name="gender[]" class="form-select">');
                                        foreach ($genderOptions as $value => $label) {
                                            echo '<option value="' . $value . '" ' . ($currentGender == $value ? 'selected' : '') . '>' . $label . '</option>';
                                        }
                                        print('</select>
                                    </td>
                                    
                                </tr>
                            </tbody>
                            </table>
                            ');
                        }
                        print('</form>');
                    ?>
            </div>

            <!-- Clinical Information -->
            <div class="mt-4"> 
                    <?php 
                        $clinicalInformation = get_cyto_clinical_information($cyto_id);    
                    ?>
                    <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Chief Complain</th>
                                    <th>Clinical History</th>
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

            </diV>

            <!-- Fixation Details -->
            
            <div class="mt-4">
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

                <table class="table table-bordered table-striped">
                    <tbody>
                        <?php if (empty($fixationInformation)): ?>
                            <tr><td colspan="6">No data found.</td></tr>
                        <?php else: ?>
                            <tr>
                                <!-- Location: Show all unique locations as a comma-separated list -->
                                <td colspan="2">
                                    Location: <?= implode(', ', $locations) ?>
                                </td>
                                <td colspan="2">
                                    Slide: <?= $dryNoCount ?>+<?= $dryYesCount ?>
                                </td>
                                <td>
                                    Aspiration Materials: <?= implode(', ', $aspirationMaterials) ?>
                                </td>
                                <td>
                                    Special Instructions: <?= implode(', ', $specialInstructions) ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
       
            </div>

          
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
                            echo '<table class="table table-bordered table-striped">';
                            
                            // List of fields to exclude
                            $exclude_fields = ['rowid', 'cyto_id', 'chief_complain'];

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
                            echo '';
                        }
                        
                        // Recall fixation details
                        $recall_fixation_details = cyto_recall_fixation_details($recall_cyto_id);

                        if (is_array($recall_fixation_details) && !empty($recall_fixation_details)) {
                            $dry_no_count = 0;
                            $dry_yes_count = 0;
                            $exclude_fields = ['rowid', 'cyto_id', 'fixation_method', 'dry'];

                            // Initialize arrays to aggregate data
                            $aggregated_data = [
                                'location' => [],
                                'aspiration_materials' => [],
                                'special_instructions' => [],
                            ];

                            // Iterate through all records to count "dry" values and aggregate data
                            foreach ($recall_fixation_details as $record) {
                                if (isset($record['dry'])) {
                                    $dry_value = strtolower($record['dry']);
                                    if ($dry_value === 'no') {
                                        $dry_no_count++;
                                    } elseif ($dry_value === 'yes') {
                                        $dry_yes_count++;
                                    }
                                }

                                // Aggregate specific fields
                                if (!empty($record['location']) && !in_array($record['location'], $aggregated_data['location'])) {
                                    $aggregated_data['location'][] = $record['location'];
                                }
                                if (!empty($record['aspiration_materials']) && !in_array($record['aspiration_materials'], $aggregated_data['aspiration_materials'])) {
                                    $aggregated_data['aspiration_materials'][] = $record['aspiration_materials'];
                                }
                                if (!empty($record['special_instructions']) && !in_array($record['special_instructions'], $aggregated_data['special_instructions'])) {
                                    $aggregated_data['special_instructions'][] = $record['special_instructions'];
                                }
                            }

                            // Display table
                            echo '<table class="table table-bordered table-striped">';
                            echo '<thead><tr>';
                            echo '<th>Location</th>';
                            echo '<th>Slide</th>';
                            echo '<th>Aspiration Materials</th>';
                            echo '<th>Special Instructions</th>';
                            echo '</tr></thead><tbody>';

                            // Combine aggregated data for display
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars(implode(', ', $aggregated_data['location'])) . '</td>';
                            echo '<td>' . $dry_no_count . ' + ' . $dry_yes_count . '</td>'; // Show No count + Yes count
                            echo '<td>' . htmlspecialchars(implode(', ', $aggregated_data['aspiration_materials'])) . '</td>';
                            echo '<td>' . htmlspecialchars(implode(', ', $aggregated_data['special_instructions'])) . '</td>';
                            echo '</tr>';

                            echo '</tbody></table>';
                        } else {
                            // If no data was found or there's an error
                            echo '<div class="alert alert-warning">No fixation details found.</div>';
                            error_log('No data or invalid response from cyto_recall_fixation_details.');
                        }
 
                } 
            ?>
           
            

            


            <!-- Diagnosis Tab -->
            <?php

                // Fetch data using the function
                $diagnosis_data = cyto_diagnosis_doctor_module($formatted_LabNumber);

                // Check for errors
                if (isset($diagnosis_data['error'])) {
                    $error_message = $diagnosis_data['error'];
                } else {
                    $diagnosis_entry = $diagnosis_data[0] ?? null; // Fetch the first row or null if empty
                }
            ?>

           
            <div>
               
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php else: ?>
                <form id="diagnosis-form" method="post" action="insert/update_diagnosis.php">
                    <!-- Hidden input for lab number -->
                    <input type="hidden" name="lab_number" value="<?php echo htmlspecialchars($formatted_LabNumber); ?>">
                    <input type="hidden" name="user" value="<?php echo htmlspecialchars($loggedInUsername); ?>">

                    <!-- Flex container for diagnosis fields and button -->
                    <div class="diagnosis-container" style="display: flex; gap: 20px; align-items: flex-start;">
                        <!-- Previous Diagnosis -->
                        <div class="form-group" style="flex: 1;">
                            <label for="previous-diagnosis">Previous Diagnosis:</label>
                            <textarea id="previous-diagnosis" name="previous_diagnosis" class="form-control" rows="3" readonly>
                                <?php
                                if (!empty($diagnosis_entry['previous_diagnosis'])) {
                                    $previousDiagnosisData = json_decode($diagnosis_entry['previous_diagnosis'], true);
                                    foreach ($previousDiagnosisData as $entry) {
                                        echo "\n";
                                        echo "Previous: " . $entry['previous'] . "\n";
                                        echo "Date: " . $entry['Date'] . "\n";
                                        echo "Created by: " . $entry['created_user'] . "\n";
                                        echo "Updated by: " . $entry['updated_user'] . "\n\n";
                                    }
                                } else {
                                    echo "No previous diagnosis available.";
                                }
                                ?>
                            </textarea>
                        </div>

                        <!-- Current Diagnosis -->
                        <div class="form-group" style="flex: 1;">
                            <label for="current-diagnosis">Current Diagnosis:</label>
                            <textarea id="current-diagnosis" name="current_diagnosis" class="form-control" rows="3" readonly>
                                <?php
                                if (!empty($diagnosis_entry['diagnosis'])) {
                                    echo htmlspecialchars($diagnosis_entry['diagnosis']);
                                } else {
                                    echo "No current diagnosis available.";
                                }
                                ?>
                            </textarea>
                        </div>

                        <!-- New Diagnosis -->
                        <div class="form-group" style="flex: 1;">
                            <label for="diagnosis">New Diagnosis:</label>
                            <textarea id="diagnosis" name="diagnosis" class="form-control" rows="3" required></textarea>
                        </div>

                        <!-- Save Button -->
                        <div class="form-group" style="display: flex; align-items: center; justify-content: center; width: 100px; margin-top: 40px;">
                            <button type="submit" class="btn btn-primary">Save</button>
                        </div>
                    </div>

                </form>
                <?php endif; ?>
            </div>



    </div>
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