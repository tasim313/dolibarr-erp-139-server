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

$LabNumber = $_GET['lab_number'];


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
$reportUrl = "http://" . $host . "/custom/transcription/MFC/mfc_report.php?LabNumber=" . urlencode($LabNumber) . "&username=" . urlencode($loggedInUsername);

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

            </div>

            <?php 
                $mfc_list = get_mfc_create_list($LabNumber);
                // Ensure data is fetched properly
                if (!empty($mfc_list) && isset($mfc_list[0]['description'])) {
                    $description = htmlspecialchars($mfc_list[0]['description']); // Sanitize output for security
                } 
            ?>

            <!-- Display the description in a read-only field -->
            <div class="card shadow-lg">
                <div class="card-body">
                    <div class="mb-3">
                        <label for="description" class="form-label fw-bold">Gross Note:</label>
                        <textarea id="description" class="form-control bg-light text-dark fw-semibold" rows="4" readonly><?php echo $description; ?></textarea>
                    </div>
                </div>
            </div>
            <br>

            <!-- Diagnosis Tab -->
            <?php
                 
                $formatted_LabNumber = substr($LabNumber, 3);
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