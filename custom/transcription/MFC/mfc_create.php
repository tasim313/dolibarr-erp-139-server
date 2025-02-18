<?php 

include("../connection.php");
include("../../grossmodule/gross_common_function.php");
include('../../transcription/common_function.php');
include ("../../cytology/common_function.php");
include ("../FNA/function.php");
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
$hasTranscriptionist = false;

$isAdmin = isUserAdmin($loggedInUserId);
$userGroupNames = getUserGroupNames($loggedInUserId);

$LabNumber = $_GET['LabNumber'];

foreach ($userGroupNames as $group) {
    if ($group['group'] === 'Transcription') {
        $hasTranscriptionist = true;
    } elseif ($group['group'] === 'Consultants') {
        $hasConsultants = true;
    }
}


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
    case $hasTranscriptionist:
        // Transcription  has access, continue with the page content...
        break;

    default:
        echo "<h1>Access Denied</h1>";
        echo "<p>You are not authorized to view this page.</p>";
        exit; // Terminate script execution
}

$host = $_SERVER['HTTP_HOST'];
$homeUrl = "http://" . $host . "/custom/transcription/FNA/index.php";
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
</head>
<body>
    <a href="<?= $homeUrl ?>" class="btn btn-info btn-md">Home</a>&nbsp; &nbsp;&nbsp;
    <a href="<?= $reportUrl ?>" class="btn btn-info btn-md" target="_blank">Preview</a>&nbsp; &nbsp;&nbsp;
    <button class="btn btn-info btn-md" onclick="history.back()">Back</button>
    <div class="container">
        <div class=" text-center mt-5 ">
            <h3>Microscopic Details</h3>
        </div>
        

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
            
            <?php
                // Call the function to get diagnosis data by lab number
                $diagnosis_by_doctor = cyto_diagnosis_by_lab_number($trimmedLabNumber);

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
                                <th>Specimen Name</th>
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
                                        <?= htmlspecialchars_decode(!empty($data['chief_complain']) ? $data['chief_complain'] : ($info['chief_complain'] ?? '')); ?>
                                    </div>
                                </td>

                                <!-- specimen_name -->
                                <td>
                                    <div id="specimen_name-container" class="quill-editor">
                                        <?= htmlspecialchars_decode($data['specimen_name'] ?? ''); ?>
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

        const SpecimenNameEditor = new Quill('#specimen_name-container', {
            theme: 'snow',
            readOnly: true,
            placeholder: 'Specimen Name',
            modules: { toolbar: false }
        });

        SpecimenNameEditor.root.innerHTML = `<?= htmlspecialchars_decode($data['specimen_name']  ?? ''); ?>`;

        const grossNoteEditor = new Quill('#gross-note-container', {
            theme: 'snow',
            readOnly: true,
            placeholder: 'Gross Note',
            modules: { toolbar: false }
        });

        grossNoteEditor.root.innerHTML = `<?= htmlspecialchars_decode($data['gross_note'] ?? htmlspecialchars_decode($description) ?? ''); ?>`;


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
        chiefComplainEditor.root.innerHTML = `<?= htmlspecialchars_decode($data['chief_complain'] ?? htmlspecialchars($info['chief_complain'] ?? '')); ?>`;

        // Edit button handler
        document.getElementById('editMicroscopicBtn').addEventListener('click', function () {
            if (!isEditing) {
                // Enable editing
                SpecimenNameEditor.enable(); 
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
                const SpecimenNames = SpecimenNameEditor.root.innerHTML.trim();
                const grossNote = grossNoteEditor.root.innerHTML.trim(); 
                const microscopicDescription = microscopicDescriptionEditor.root.innerHTML.trim();
                const conclusionDescription = conclusionDescriptionEditor.root.innerHTML.trim();
                const commentDescription = commentDescriptionEditor.root.innerHTML.trim();
                const recallDescription = recallDescriptionEditor.root.innerHTML.trim();
                const chiefComplainDescription = chiefComplainEditor.root.innerHTML.trim(); // Get data from Chief Complain

                // Disable editing
                SpecimenNameEditor.disable();
                grossNoteEditor.disable();
                microscopicDescriptionEditor.disable();
                conclusionDescriptionEditor.disable();
                commentDescriptionEditor.disable();
                recallDescriptionEditor.disable();
                chiefComplainEditor.disable();

                // Prepare data for submission
                const formData = new FormData();
                formData.append('specimen_name', SpecimenNames);
                formData.append('gross-note', grossNote);
                formData.append('microscopic-description', microscopicDescription);
                formData.append('conclusion-description', conclusionDescription);
                formData.append('chief-complain', chiefComplainDescription);
                formData.append('comment-description', commentDescription);
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