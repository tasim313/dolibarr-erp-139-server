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

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link href="../../grossmodule/bootstrap-3.4.1-dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <a href="<?= $homeUrl ?>" class="btn btn-info btn-md">Home</a>
    <div class="container">
        <div class=" text-center mt-5 ">
            <h3>Create Microscopic Description</h3>
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
                                <th>Aspiration Note</th>
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
                                            <td>
                                                <textarea class="form-control" data-rowid="<?= $info['rowid'] ?>" data-field="chief_complain"><?= htmlspecialchars($info['chief_complain']) ?></textarea>
                                            </td>
                                            <td>
                                                <textarea class="form-control" data-rowid="<?= $info['rowid'] ?>" data-field="relevant_clinical_history"><?= htmlspecialchars($info['relevant_clinical_history']) ?></textarea>
                                            </td>
                                            <td>
                                                <textarea class="form-control" data-rowid="<?= $info['rowid'] ?>" data-field="on_examination"><?= htmlspecialchars($info['on_examination']) ?></textarea>
                                            </td>
                                            <td>
                                                <textarea class="form-control" data-rowid="<?= $info['rowid'] ?>" data-field="aspiration_note"><?= htmlspecialchars($info['aspiration_note']) ?></textarea>
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

            
               
            <div class="mt-4">
                    <h4>Microscopic Information</h4>
                    <?php 
                        $clinicalInformation = get_cyto_clinical_information($cyto_id);
                        
                    ?>
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Microscopic Description</th>
                                <th>Conclusion</th>
                                <th>Comment</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                                <tr>
                                    <form id="cyto-microscopic-information" method="post" action="../Cyto/patient_clinical_info_update.php">
                                        <td>
                                            <textarea class="form-control" data-rowid="<?= $info['rowid'] ?>" data-field="chief_complain"><?= htmlspecialchars($info['chief_complain']) ?></textarea>
                                        </td>
                                        <td>
                                            <textarea class="form-control" data-rowid="<?= $info['rowid'] ?>" data-field="relevant_clinical_history"><?= htmlspecialchars($info['relevant_clinical_history']) ?></textarea>
                                        </td>
                                        <td>
                                            <textarea class="form-control" data-rowid="<?= $info['rowid'] ?>" data-field="on_examination"><?= htmlspecialchars($info['on_examination']) ?></textarea>
                                        </td>
                                        
                                        <td>
                                            <button id="microscopicInformationBtn" class="btn btn-primary btn-sm" title="Create New Microscopic Information">
                                                <i class="fas fa-plus"></i> 
                                            </button>
                                        </td>
                                    </form>
                                </tr>
                               
                        </tbody>
                    </table>
            </div>

            
    </div>
</body>
</html>

<script>
    $(document).ready(function() {
        // Loop through all buttons with ids like 'clinicalInformationBtn-<rowid>'
        <?php foreach ($clinicalInformation as $info): ?>
            $("#clinicalInformationBtn-<?= $info['rowid'] ?>").on("click", function(e) {
                e.preventDefault();  // Prevent form submission

                var rowid = $(this).attr("id").split('-')[1];  // Extract rowid from the button id
                var chief_complain = $("textarea[data-rowid='" + rowid + "'][data-field='chief_complain']").val();
                var relevant_clinical_history = $("textarea[data-rowid='" + rowid + "'][data-field='relevant_clinical_history']").val();
                var on_examination = $("textarea[data-rowid='" + rowid + "'][data-field='on_examination']").val();
                var aspiration_note = $("textarea[data-rowid='" + rowid + "'][data-field='aspiration_note']").val();

                // Send the data via AJAX
                $.ajax({
                    url: '../../cytology/Cyto/patient_clinical_info_update.php',  // Change this path as per your directory structure
                    type: 'POST',
                    data: {
                        rowid: rowid,
                        chief_complain: chief_complain,
                        relevant_clinical_history: relevant_clinical_history,
                        on_examination: on_examination,
                        aspiration_note: aspiration_note
                    },
                    success: function(response) {
                        if (response.trim() === 'success') {
                            alert('Clinical Information updated successfully!');
                        } else {
                            alert('Clinical Information updated successfully!');
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('An error occurred: ' + error);
                    }
                });
            });
        <?php endforeach; ?>
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