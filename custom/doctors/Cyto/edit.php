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
        .fixation-row {
            display: flex;
            flex-wrap: wrap; /* Allows the content to wrap to the next line if needed */
            margin-bottom: 10px; /* Adds space between rows */
        }

        .fixation-item {
            flex: 1 1 200px; /* Allows the items to take equal space, with a minimum width of 200px */
            margin: 5px;
            padding: 10px;  
            font-size: 16px;
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
                                            <form method="POST" action="insert/update_clinical_information.php">
                                                <table class="table" style="border-collapse: collapse; width: 100%; border-top: none;">
                                                    <?php if (empty($clinicalInformation)): ?>
                                                        <tr><td colspan="2" style="border: none; text-align: center;"></td></tr>
                                                    <?php else: ?>
                                                        <?php foreach ($clinicalInformation as $info): ?>
                                                            <input type="hidden" name="rowid" value="<?= $info['rowid'] ?>">
                                                            <input type="hidden" name="username" value="<?= $loggedInUsername ?>">
                                                            <!-- Chief Complain (Editable) & Previous Chief Complain (Readonly) -->
                                                            <tr>
                                                                <th style="text-align: left; padding: 8px; border: none; font-size:18px; width: 5%;">C/C:</th>
                                                                <td style="padding: 8px; border: none; font-size:16px;">
                                                                    <textarea name="chief_complain" class="form-control" style="width: 45%; display: inline-block;"><?= htmlspecialchars($info['chief_complain']) ?></textarea>
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
                                                                <th style="text-align: left; padding: 8px; border: none; font-size:16px; width: 5%;">H/O:</th>
                                                                <td style="padding: 8px; border: none; font-size:16px;">
                                                                    <textarea name="relevant_clinical_history" class="form-control" style="width: 45%; display: inline-block;"><?= htmlspecialchars($info['relevant_clinical_history']) ?></textarea>
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
                                                                <th style="text-align: left; padding: 8px; border: none; font-size:16px; width: 5%;">O/E:</th>
                                                                <td style="padding: 8px; border: none; font-size:16px;">
                                                                    <textarea name="on_examination" class="form-control" style="width: 45%; display: inline-block;"><?= htmlspecialchars($info['on_examination']) ?></textarea>
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

                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </table>
                                                <button type="submit" class="btn btn-primary" style="margin-bottom:30px;">Save</button>
                                            </form>
                                        </div>


            <!-- Fixation Details -->
            
            <div >
                <?php
                    // Fetch fixation details
                    $fixationInformation = get_cyto_fixation_details($cyto_id);

                    // Initialize arrays for storing counts and details
                    $dryYesCounts = [];
                    $dryNoCounts = [];
                    $aspirationMaterials = [];
                    $specialInstructions = [];
                    $locations = [];
                    $locationPrefixCount = [];  // To track the occurrence of each location

                    if (!empty($fixationInformation)) {
                        foreach ($fixationInformation as $info_fixation) {
                            // Collect unique locations, aspiration materials, and special instructions
                            $location = trim($info_fixation['location']) ?: 'Proper';
                            $aspirationMaterial = $info_fixation['aspiration_materials'];
                            $specialInstruction = $info_fixation['special_instructions'];

                            // Ensure unique entries for locations
                            if (!in_array($location, $locations)) {
                                $locations[] = $location;
                            }

                            if (!in_array($aspirationMaterial, $aspirationMaterials)) {
                                $aspirationMaterials[] = $aspirationMaterial;
                            }
                            if (!in_array($specialInstruction, $specialInstructions)) {
                                $specialInstructions[] = $specialInstruction;
                            }

                            // Count the dry values by location
                            if ($info_fixation['dry'] === 'Yes') {
                                if (!isset($dryYesCounts[$location])) {
                                    $dryYesCounts[$location] = 0;
                                }
                                $dryYesCounts[$location]++;
                            } elseif ($info_fixation['dry'] === 'No') {
                                if (!isset($dryNoCounts[$location])) {
                                    $dryNoCounts[$location] = 0;
                                }
                                $dryNoCounts[$location]++;
                            }
                        }
                    }

                    // Clean up the arrays by trimming empty values
                    $cleanedLocations = array_filter(array_map('trim', $locations));
                    $cleanedSpecialInstructions = array_filter(array_map('trim', $specialInstructions));

                    // Initialize a variable to keep track of the letter to append as a prefix
                    $prefixIndex = 0;  // Start with 'P-' for the first location

                    // We will iterate over the cleaned locations and display each location, A/M, slide, and special instructions in the required format
                    for ($i = 0; $i < count($cleanedLocations); $i++):
                        // Get values for the current location
                        $location = htmlspecialchars($cleanedLocations[$i]);
                        $aspirationMaterial = htmlspecialchars($aspirationMaterials[$i] ?? ''); // Default empty string if no aspiration material
                        
                        // Get slide counts for the specific location
                        $dryNo = isset($dryNoCounts[$location]) ? $dryNoCounts[$location] : 0;
                        $dryYes = isset($dryYesCounts[$location]) ? $dryYesCounts[$location] : 0;
                        $slide = htmlspecialchars("$dryNo+$dryYes"); // Combine slide counts

                        $specialInstruction = htmlspecialchars($cleanedSpecialInstructions[$i] ?? ''); // Default empty if no special instruction

                        // Generate the prefix for the location
                        if ($prefixIndex === 0) {
                            $prefix = 'P-'; // First unique location gets "P-"
                        } else {
                            $prefix = chr(64 + $prefixIndex) . "-"; // Generate prefix (A, B, C, etc.)
                        }

                        // Increment the prefix index
                        $prefixIndex++;

                        // Combine the prefix with the location name
                        $prefixedLocation = $prefix . $location;
                ?>
                <!-- Display each section for a location horizontally -->
                <div class="fixation-row">
                    <?php if (!empty($prefixedLocation)): ?>
                        <div class="fixation-item">
                            <b>Location:</b> <?= $prefixedLocation ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($aspirationMaterial)): ?>
                        <div class="fixation-item">
                            <b>A/M:</b> <?= $aspirationMaterial ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($slide)): ?>
                        <div class="fixation-item">
                            <b>Slide:</b> <?= $slide ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($specialInstruction)): ?>
                        <div class="fixation-item">
                            <b>Special Instructions:</b> <?= $specialInstruction ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endfor; ?>


                <form method="POST" action="insert/update_clinical_information.php" style="margin-top:20px;">
                    <table class="table" style="border-collapse: collapse; width: 100%; border-top: none; margin-top:-15px;">
                            <?php if (empty($clinicalInformation)): ?>
                                <tr><td colspan="2" style="border: none; text-align: center;"></td></tr>
                            <?php else: ?>
                                <?php foreach ($clinicalInformation as $info): ?>
                                    <input type="hidden" name="rowid" value="<?= $info['rowid'] ?>">
                                    <input type="hidden" name="username" value="<?= $loggedInUsername ?>">
                                    <!-- Clinical Impression (Editable) & Previous Clinical Impression (Readonly) -->
                                    <tr>
                                        <th style="text-align: left; padding: 8px; border: none; font-size:16px; width: 5%;">C/I:</th>
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
                    </table><br>
                    <button type="submit" class="btn btn-primary" style="margin-bottom:30px;">Save</button>
                </form>

            </div>

          
            <!-- Recall + Recall fixation details-->
           
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
                            echo '<div class="alert alert-warning">No clinical information found.</div>';
                        }

                        // Fetch fixation details
                        $fixationInformation_recall = cyto_recall_fixation_details($recall_cyto_id);

                        // Initialize arrays for storing counts and details
                        $dryYesCounts_recall = [];
                        $dryNoCounts_recall = [];
                        $aspirationMaterials_recall = [];
                        $specialInstructions_recall = [];
                        $locations_recall = [];

                        if (!empty($fixationInformation_recall)) {
                            foreach ($fixationInformation_recall as $info_fixation_recall) {
                                // Ensure variable is correctly used
                                $location_recall = trim($info_fixation_recall['location']) ?: 'Proper';
                                $aspirationMaterial_recall = $info_fixation_recall['aspiration_materials'];
                                $specialInstruction_recall = $info_fixation_recall['special_instructions'];

                                // Ensure unique entries for locations
                                if (!in_array($location_recall, $locations_recall)) {
                                    $locations_recall[] = $location_recall;
                                }
                                if (!in_array($aspirationMaterial_recall, $aspirationMaterials_recall)) {
                                    $aspirationMaterials_recall[] = $aspirationMaterial_recall;
                                }
                                if (!in_array($specialInstruction_recall, $specialInstructions_recall)) {
                                    $specialInstructions_recall[] = $specialInstruction_recall;
                                }

                                // Count the dry values by location
                                if ($info_fixation_recall['dry'] === 'Yes') {
                                    $dryYesCounts_recall[$location_recall] = ($dryYesCounts_recall[$location_recall] ?? 0) + 1;
                                } elseif ($info_fixation_recall['dry'] === 'No') {
                                    $dryNoCounts_recall[$location_recall] = ($dryNoCounts_recall[$location_recall] ?? 0) + 1;
                                }
                            }
                        }

                        // Clean up the arrays by trimming empty values
                        $cleanedLocations_recall = array_filter(array_map('trim', $locations_recall));
                        $cleanedSpecialInstructions_recall = array_filter(array_map('trim', $specialInstructions_recall));

                        // Initialize a variable to keep track of the letter to append as a prefix
                        $prefixIndex_recall = 0; // Start with 'P-' for the first location

                        // We will iterate over the cleaned locations and display each location, A/M, slide, and special instructions
                        foreach ($cleanedLocations_recall as $i => $location_recall):
                                // Get values for the current location
                                $aspirationMaterial_recall = htmlspecialchars($aspirationMaterials_recall[$i] ?? ''); // Default empty string if no aspiration material
                                $dryNo_recall = $dryNoCounts_recall[$location_recall] ?? 0;
                                $dryYes_recall = $dryYesCounts_recall[$location_recall] ?? 0;
                                $slide_recall = "$dryNo_recall+$dryYes_recall"; // Combine slide counts
                                $specialInstruction_recall = htmlspecialchars($cleanedSpecialInstructions_recall[$i] ?? ''); // Default empty if no special instruction

                                // Generate the prefix for the location
                                $prefix_recall = ($prefixIndex_recall === 0) ? 'P-' : chr(64 + $prefixIndex_recall) . "-";
                                $prefixIndex_recall++;

                                // Combine the prefix with the location name
                                $prefixedLocation_recall = $prefix_recall . $location_recall;
                                ?>
                                
                                <div class="fixation-row">
                                    <?php if (!empty($prefixedLocation_recall)): ?>
                                        <div class="fixation-item">
                                            <b>Location:</b> <?= $prefixedLocation_recall ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($aspirationMaterial_recall)): ?>
                                        <div class="fixation-item">
                                            <b>A/M:</b> <?= $aspirationMaterial_recall ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($slide_recall)): ?>
                                        <div class="fixation-item">
                                            <b>Slide:</b> <?= $slide_recall ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($specialInstruction_recall)): ?>
                                        <div class="fixation-item">
                                            <b>Special Instructions:</b> <?= $specialInstruction_recall ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                        <?php endforeach;
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