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
$homeUrl = "http://" . $host . "/custom/doctors/doctorsindex.php";
$reportUrl = "http://" . $host . "/custom/doctors/Cyto/Report.php?LabNumber=" . urlencode($LabNumber) . "&username=" . urlencode($loggedInUsername);

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
    <style>
        .left-aligned {
            text-align: left;
            padding-left: 0;
        }
    </style>
</head>
<body>
    <a href="<?= $homeUrl ?>" class="btn btn-info btn-md">Home</a>&nbsp;&nbsp;&nbsp;
    <a href="<?= $reportUrl ?>" class="btn btn-info btn-md" target="_blank">Preview</a>&nbsp;&nbsp;&nbsp;
    <button class="btn btn-info btn-md" onclick="history.back()">Back</button>
    <div class="container">
        <div class=" text-center mt-5 ">
            <h3>Microscopic Details</h3>
        </div>

        <table class="table" style="border-collapse: collapse; width: 100%; border-top: none;">
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
                            
                            <td style='padding: 8px; border: none; font-size:20px;'><b>Aspirate By</b>: {$record['doctor']}&nbsp;&nbsp;&nbsp;&nbsp;<b>Assistant</b>:   {$record['assistant']}</td>
                           
                        </tr>";
                    }
                    ?>
                </tbody>
        </table>
        
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
            <div style="margin-top:-20px;">
                    <?php 
                       
                        foreach ($patient_information as $list) {
                            $genderOptions = [
                                '1' => 'Male',
                                '2' => 'Female',
                                '3' => 'Other'
                            ];
                            $currentGender = $list['Gender'];
                            
                            print('
                            <table class="table" style="border-collapse: collapse; width: 100%; border-top: none;">
                                <tbody>
                                    <tr>
                                        <td style="padding: 8px; border: none; font-size:20px;"><b>Lab Number:</b>&nbsp;'. $trimmedLabNumber .'</td>
                                        <td style="padding: 8px; border: none; font-size:20px;">
                                           <b> Name: '.$list['name'].'</b>
                                        </td> 
                                        <td style="padding: 8px; border: none; font-size:20px;">
                                           <b>Age: ' . $list['Age'] . '</b>
                                        </td>
                                        <td style="padding: 8px; border: none; font-size:20px;">
                                           <b> Gender: '.htmlspecialchars($genderOptions[$currentGender]).' </b>
                                        </td>
                                    
                                        
                                        
                                    </tr>
                                </tbody>
                            </table>
                            ');
                        }
                      
                    ?>
            </div>

            <!-- <td style="padding: 8px; border: none;">
                                            Address: ' . $list['address'] . '
                                        </td>
                                        <td style="padding: 8px; border: none;">
                                            Phone: ' . $list['phone'] . '
                                        </td> 
                                        <td style="padding: 8px; border: none;">
                                            Attendant Number: ' . $list['fax'] . '
                                        </td>  -->

                                        <div style="margin-top:-20px;"> 
                                            <?php 
                                                $clinicalInformation = get_cyto_clinical_information($cyto_id);    
                                            ?>
                                            <table class="table" style="border-collapse: collapse; width: 100%; border-top: none;">
                                                <?php if (empty($clinicalInformation)): ?>
                                                    <tr><td colspan="2" style="border: none; text-align: center;"></td></tr>
                                                <?php else: ?>
                                                    <?php foreach ($clinicalInformation as $info): ?>
                                                        <!-- Chief Complain (Editable) & Previous Chief Complain (Readonly) -->
                                                        <tr>
                                                            <th style="text-align: left; padding: 8px; border: none; font-size:18px;">C/C:</th>
                                                            <td style="padding: 8px; border: none; font-size:16px;">
                                                                <input type="text" name="chief_complain" value="<?= htmlspecialchars($info['chief_complain']) ?>" class="form-control" style="width: 45%; display: inline-block;">
                                                                <?php if (!empty($info['previous_chief_complain'])): ?>
                                                                    <?php 
                                                                        $prevDataArray = json_decode($info['previous_chief_complain'], true); // Decode JSON
                                                                        
                                                                        if (!empty($prevDataArray) && is_array($prevDataArray)) {
                                                                            date_default_timezone_set('Asia/Dhaka'); // Set timezone
                                                                            
                                                                            // Reverse the array to show the last element first
                                                                            $prevDataArray = array_reverse($prevDataArray);
                                                                            
                                                                            // Open a single div to hold all data
                                                                            echo '<div style="width: 45%; display: inline-block; background-color: #f5f5f5; padding: 8px; border-radius: 5px;">';
                                                                            
                                                                            // Loop through all elements and display them
                                                                            foreach ($prevDataArray as $prevData) {
                                                                                if (!empty($prevData['update_date'])) {
                                                                                    $formattedDate = date('j F, Y h:i A', strtotime($prevData['update_date']));
                                                                                    
                                                                                    // Display each entry's data within the single div
                                                                                    echo htmlspecialchars($prevData['value']) . '<br>';
                                                                                    echo '<small style="color: gray;">' . $formattedDate . '<br>';
                                                                                    echo htmlspecialchars($prevData['updated_user']) . '</small><br><br>'; // Add <br> to separate each item
                                                                                }
                                                                            }
                                                                            
                                                                            // Close the div
                                                                            echo '</div>';
                                                                        }
                                                                    ?>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>

                                                        <!-- Relevant Clinical History (Editable) & Previous History (Readonly) -->
                                                        <tr>
                                                            <th style="text-align: left; padding: 8px; border: none; font-size:16px;">H/O:</th>
                                                            <td style="padding: 8px; border: none; font-size:16px;">
                                                                <input type="text" name="relevant_clinical_history" value="<?= htmlspecialchars($info['relevant_clinical_history']) ?>" class="form-control" style="width: 45%; display: inline-block;">
                                                                <?php if (!empty($info['previous_history'])): ?>
                                                                    <?php 
                                                                        $prevHistoryArray = json_decode($info['previous_history'], true); // Decode JSON
                                                                        
                                                                        if (!empty($prevHistoryArray) && is_array($prevHistoryArray)) {
                                                                            date_default_timezone_set('Asia/Dhaka'); // Set timezone
                                                                            
                                                                            // Reverse the array to display the last element first
                                                                            $prevHistoryArray = array_reverse($prevHistoryArray);
                                                                            
                                                                            // Open a single div to hold all data
                                                                            echo '<div style="width: 45%; display: inline-block; background-color: #f5f5f5; padding: 8px; border-radius: 5px;">';
                                                                            
                                                                            // Loop through all elements and display them
                                                                            foreach ($prevHistoryArray as $prevHistory) {
                                                                                if (!empty($prevHistory['update_date'])) {
                                                                                    $formattedDate = date('j F, Y h:i A', strtotime($prevHistory['update_date']));
                                                                                    
                                                                                    echo htmlspecialchars($prevHistory['value']) . '<br>';
                                                                                    echo '<small style="color: gray;">' . $formattedDate . '<br>';
                                                                                    echo htmlspecialchars($prevHistory['updated_user']) . '</small><br><br>';  // Add <br> to separate each item
                                                                                }
                                                                            }
                                                                            
                                                                            // Close the div
                                                                            echo '</div>';
                                                                        }
                                                                    ?>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>

                                                        <!-- On Examination (Editable) & Previous On Examination (Readonly) -->
                                                        <tr>
                                                            <th style="text-align: left; padding: 8px; border: none; font-size:16px;">O/E:</th>
                                                            <td style="padding: 8px; border: none; font-size:16px;">
                                                                <input type="text" name="on_examination" value="<?= htmlspecialchars($info['on_examination']) ?>" class="form-control" style="width: 45%; display: inline-block;">
                                                                <?php if (!empty($info['previous_on_examination'])): ?>
                                                                    <?php 
                                                                        $prevExaminationArray = json_decode($info['previous_on_examination'], true); // Decode JSON
                                                                        
                                                                        if (!empty($prevExaminationArray) && is_array($prevExaminationArray)) {
                                                                            date_default_timezone_set('Asia/Dhaka'); // Set timezone
                                                                            
                                                                            // Reverse the array to show the last element first
                                                                            $prevExaminationArray = array_reverse($prevExaminationArray);
                                                                            
                                                                            // Open a single div to hold all data
                                                                            echo '<div style="width: 45%; display: inline-block; background-color: #f5f5f5; padding: 8px; border-radius: 5px;">';
                                                                            
                                                                            // Loop through all elements and display them
                                                                            foreach ($prevExaminationArray as $prevExamination) {
                                                                                if (!empty($prevExamination['update_date'])) {
                                                                                    $formattedDate = date('j F, Y h:i A', strtotime($prevExamination['update_date']));
                                                                                    
                                                                                    // Display each entry's data within the single div
                                                                                    echo htmlspecialchars($prevExamination['value']) . '<br>';
                                                                                    echo '<small style="color: gray;">' . $formattedDate . '<br>';
                                                                                    echo htmlspecialchars($prevExamination['updated_user']) . '</small><br><br>'; // Add <br> to separate each item
                                                                                }
                                                                            }
                                                                            
                                                                            // Close the div
                                                                            echo '</div>';
                                                                        }
                                                                    ?>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>

                                                        <!-- Clinical Impression (Editable) & Previous Clinical Impression (Readonly) -->
                                                        <tr>
                                                            <th style="text-align: left; padding: 8px; border: none; font-size:16px;">C/I:</th>
                                                            <td style="padding: 8px; border: none; font-size:16px;">
                                                                <input type="text" name="clinical_impression" value="<?= htmlspecialchars($info['clinical_impression']) ?>" class="form-control" style="width: 45%; display: inline-block;">
                                                                <?php if (!empty($info['previous_clinical_impression'])): ?>
                                                                    <?php 
                                                                        $prevClinicalImpressionArray = json_decode($info['previous_clinical_impression'], true); // Decode JSON
                                                                        
                                                                        if (!empty($prevClinicalImpressionArray) && is_array($prevClinicalImpressionArray)) {
                                                                            date_default_timezone_set('Asia/Dhaka'); // Set timezone
                                                                            
                                                                            // Reverse the array to show the last element first
                                                                            $prevClinicalImpressionArray = array_reverse($prevClinicalImpressionArray);
                                                                            
                                                                            // Open a single div to hold all data
                                                                            echo '<div style="width: 45%; display: inline-block; background-color: #f5f5f5; padding: 8px; border-radius: 5px;">';
                                                                            
                                                                            // Loop through all elements and display them
                                                                            foreach ($prevClinicalImpressionArray as $prevClinicalImpression) {
                                                                                if (!empty($prevClinicalImpression['update_date'])) {
                                                                                    $formattedDate = date('j F, Y h:i A', strtotime($prevClinicalImpression['update_date']));
                                                                                    
                                                                                    // Display each entry's data within the single div
                                                                                    echo htmlspecialchars($prevClinicalImpression['value']) . '<br>';
                                                                                    echo '<small style="color: gray;">' . $formattedDate . '<br>';
                                                                                    echo htmlspecialchars($prevClinicalImpression['updated_user']) . '</small><br><br>'; // Add <br> to separate each item
                                                                                }
                                                                            }
                                                                            
                                                                            // Close the div
                                                                            echo '</div>';
                                                                        }
                                                                    ?>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>

                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </table>
                                        </div>


            <!-- Fixation Details -->
            
            <div >
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

                <table class="table" id="fixation-details-table" style="border-collapse: collapse; width: 100%; border-top: none;  margin-top:-15px;">
                    <tbody>
                        <?php if (empty($fixationInformation)): ?>
                            <tr><td colspan="2"></td></tr>
                        <?php else: ?>
                            <tr>
                                
                                <td  style="padding: 8px; border: none; font-size:16px;">
                                    <b>A/M:</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?= implode(', ', $aspirationMaterials) ?>
                                </td>
                              
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <table class="table" style="border-collapse: collapse; width: 100%; border-top: none; margin-top:-15px;">
                        <?php if (empty($clinicalInformation)): ?>
                            <tr><td colspan="2" style="border: none; text-align: center;"></td></tr>
                        <?php else: ?>
                            <?php foreach ($clinicalInformation as $info): ?>
                                <tr id="clinical_impression">
                                    <th style="text-align: left; padding: 2px; font-size:16px; white-space: nowrap; width: 5%; border: none;">C/I:</th>
                                    <td style="padding: 2px; font-size:16px; border: none;"><?= htmlspecialchars($info['clinical_impression']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                </table><br>

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
                                    <?php if ($showLocation): ?>
                                        <td style="padding: 8px; border: none; font-size:18px;">
                                            <b>Location:</b> <?= htmlspecialchars(implode(', ', $cleanedLocations)) ?>
                                        </td>
                                    <?php endif; ?>

                                    <?php if ($showSlide): ?>
                                        <td style="padding: 8px; border: none; font-size:18px;">
                                            <b>Slide:</b> <?= htmlspecialchars($dryNoCount) ?>+<?= htmlspecialchars($dryYesCount) ?>
                                        </td>
                                    <?php endif; ?>

                                    <?php if ($showSpecialInstructions): ?>
                                        <td style="padding: 8px; border: none; font-size:18px;">
                                            <b>Special Instructions:</b> <?= htmlspecialchars(implode(', ', $cleanedSpecialInstructions)) ?>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            </tbody>
                        </table>
                <?php endif; ?>


       
            </div>

          
            <!-- Recall -->
           
            <?php 
                $formatted_LabNumber = substr($LabNumber, 3);
                $recall_status = cyto_recall_lab_number($formatted_LabNumber);

                if ($recall_status) {
                    $recall_cyto_id = $recall_status['rowid'];
                    
                    // Recall clinical information
                    $recall_clinical_information = cyto_recall_clinical_information($recall_cyto_id);

                    if (is_array($recall_clinical_information)) {
                        // Exclude specific fields
                        $exclude_fields = ['rowid', 'cyto_id', 'chief_complain'];

                        // Create a vertical table for clinical information
                        echo '<table class="table" style="border-collapse: collapse; width: 100%; border-top: none; margin-top:-20px;">';
                        
                        foreach ($recall_clinical_information as $field => $value) {
                            if (in_array($field, $exclude_fields) || empty($value)) {
                                continue;
                            }

                            // Create a row for each field and its value
                            echo '<tr>';
                            echo '<th style="text-align: left; padding: 8px; border: none; font-size:20px;">' . ucfirst(str_replace('_', ' ', $field)) . '</th>';
                            echo '<td style="padding: 8px; border: none; font-size:20px;">' . htmlspecialchars($value) . '</td>';
                            echo '</tr>';
                        }

                        echo '</table>';
                    } else {
                        echo '<div class="alert alert-warning"></div>';
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

                        // Iterate through records to count "dry" values and aggregate data
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

                        // Create a vertical table for fixation details
                        echo '<table class="table" style="border-collapse: collapse; width: 100%; border-top: none;" margin-top:400px;>';

                        echo '<tr>';
                        echo '<th style="text-align: left; padding: 8px; border: none; font-size:20px;">Location:</th>';
                        echo '<td style="padding: 8px; border: none; font-size:20px;">' . htmlspecialchars(implode(', ', $aggregated_data['location'])) . '</td>';
                        echo '<th style="text-align: left; padding: 8px; border: none; font-size:20px;">Slide:</th>';
                        echo '<td style="padding: 8px; border: none; font-size:20px;">' . $dry_no_count . ' + ' . $dry_yes_count . '</td>'; // No count + Yes count
                        echo '<th style="text-align: left; padding: 8px; border: none; font-size:20px;">Aspiration Materials:</th>';
                        echo '<td style="padding: 8px; border: none; font-size:20px;">' . htmlspecialchars(implode(', ', $aggregated_data['aspiration_materials'])) . '</td>';
                        echo '<th style="text-align: left; padding: 8px; border: none; font-size:20px;">Special Instructions:</th>';
                        echo '<td style="padding: 8px; border: none; font-size:20px;">' . htmlspecialchars(implode(', ', $aggregated_data['special_instructions'])) . '</td>';
                        echo '</tr>';
                        echo '</table>';
                    } else {
                        echo '<div class="alert alert-warning"></div>';
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
                            <label for="previous-diagnosis">Previous Microscopic Description and Diagnosis:</label>
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
                                    echo "No previous  microscopic description and diagnosis available.";
                                }
                                ?>
                            </textarea>
                        </div>

                       
                        
                        <!--  Diagnosis -->
                        <div class="form-group" style="flex: 1;">
                            <label for="diagnosis">Edit Microscopic Description and Diagnosis:</label>
                            <textarea id="diagnosis" name="diagnosis" class="form-control left-aligned" rows="3" required autofocus><?php
                                if (!empty($diagnosis_entry['diagnosis'])) {
                                    echo htmlspecialchars(preg_replace('/\s+/', ' ', trim($diagnosis_entry['diagnosis'])));
                                }
                            ?></textarea>
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