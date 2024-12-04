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

?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<!-- Import the JavaScript file -->
	<link href="../../grossmodule/bootstrap-3.4.1-dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
            <div class=" text-center mt-5 ">
                <h3>New Patient</h3>
                <ul class="nav nav-tabs">
                    <!-- <li class="active"><a href="">New</a></li>
                    <li><a href="./recall.php" class="tab">Recall</a></li> -->
                    <!-- <li><a href="./repeat.php" class="tab">Repeat</a></li> -->
                </ul>
            </div>

            <div class="row ">
                    <div class="col-lg-7 mx-auto">
                            <div class="card mt-2 mx-auto p-4 bg-light">
                                <div class="card-body bg-light">
                                    <div class = "container">
                                            <form  role="form" style="margin-top: 20px;" id="cytoForm"  method="post" action="../Cyto/new_patient_create.php">
                                                <div class="controls">
                                                    <div class="row">
                                                        
                                                        <?php 
                                                            if (!$isCytoAssistant) { 
                                                        ?>
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label for="doctor" class="form-label">Doctor</label>
                                                                <select id="doctor_name" name="doctor_name" class="form-control" aria-label="Doctor selection" data-error="Please specify your need.">
                                                                    <option value="" selected disabled>--Select a Doctor--</option>
                                                                        <?php
                                                                            $doctors = get_doctor_list();
                                                                            $loggedInUsername = $user->login; 

                                                                            foreach ($doctors as $doctor) {
                                                                                $selected = '';
                                                                                if ($doctor['doctor_username'] == $loggedInUsername) {
                                                                                    $selected = 'selected';
                                                                                }
                                                                                echo "<option value='{$doctor['doctor_username']}' $selected>{$doctor['doctor_username']}</option>";
                                                                            }
                                                                        ?>
                                                                </select>
                                                                
                                                                
                                                            </div>
                                                        </div>
                                                        <?php 
                                                            } 
                                                        ?>
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label for="assistant" class="form-label">Assistant</label>
                                                                <select id="assistant" name="assistant" class="form-control" aria-label="Assistant selection" data-error="Please specify your need.">
                                                                    <option value="">--Select a Assistant--</option> 
                                                                        <?php
                                                                            $assistants = get_cyto_tech_list();
                                                                            $loggedInUsername = $user->login;
                                                                            foreach ($assistants as $assistant) {
                                                                                $selected = '';
                                                                                if ($assistant['username'] == $loggedInUsername) {
                                                                                    $selected = 'selected';
                                                                                    $storedAssistant = isset($_SESSION['cyto_assistant_name']) && $_SESSION['cyto_assistant_name'] === $assistant['username'] ? 'selected' : '';
                                                                                }
                                                                                echo "<option value='{$assistant['username']}' $selected>{$assistant['username']}</option>";
                                                                            }
                                                                        ?>
                                                                </select>
                                                                                
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label for="station" class="form-label">FNA Station</label>
                                                                <select  id="cyto_station_type" class="form-control" required>
                                                                    <option value="">--Select a Station--</option> 
                                                                    <option value="One" <?php echo isset($_SESSION['cyto_station_type']) && $_SESSION['cyto_station_type'] === 'One' ? 'selected' : ''; ?>>One</option>
                                                                    <option value="Two" <?php echo isset($_SESSION['cyto_station_type']) && $_SESSION['cyto_station_type'] === 'Two' ? 'selected' : ''; ?>>Two</option>
                                                                </select>
                                                            </div>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label for="lab_number" class="form-label">Lab Number</label>
                                                                <input id="lab_number" class="form-control" type="text" value="<?php echo $LabNumber; ?>">
                                                                
                                                                <input type="hidden" id="gross_status" name="gross_status" value="Done">
                                                                <input type="hidden" id="gross_created_user" name="gross_created_user" value="<?php echo $gross_created_user; ?>">              
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-12">
                                                            <div class="form-floating">
                                                                <button type="submit" class="btn btn-primary">Submit</button>
                                                            </div>
                                                        </div>
                                                    </div>


                                                </div>
                                            </form>
                                    </div>
                                </div>
                            </div>
                    </div>
            </div>
            <br>
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

            <div class="container mt-4">
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

            <!-- Patient-Specific Conditions Form -->
            <div class="mt-4">
                <h5>Patient-Specific Conditions</h5>
                <form id="patient-conditions-form">
                    <!-- General Conditions -->
                    <div class="form-group">
                        <label>General Conditions (For Everyone)</label><br>
                        <input type="checkbox" id="diabetes" name="general_conditions[]" value="Diabetes"> Diabetes<br>
                        <input type="checkbox" id="hypertension" name="general_conditions[]" value="Hypertension"> Hypertension<br>
                        <input type="checkbox" id="immunosuppression" name="general_conditions[]" value="Immunosuppression"> Immunosuppression (e.g., chemotherapy, HIV)<br><br>
                        <label for="allergies">Allergies:</label>
                        <textarea type="text" id="allergies" name="allergies" class="form-control"></textarea><br>
                        <label for="others">Others:</label>
                        <textarea type="text" id="others" name="others" class="form-control"></textarea><br>
                    </div>

                    <!-- Gender-Specific Fields (Conditional) -->
                    <div id="gender-specific-fields">
                        <!-- For Women -->
                        <div id="female-conditions" style="display: none;">
                            <h6>For Women</h6>
                            <div class="form-group">
                                <label>Pregnancy Status:</label><br>
                                <input type="radio" name="pregnancy_status" value="Yes"> Yes
                                <input type="radio" name="pregnancy_status" value="No"> No
                                <input type="radio" name="pregnancy_status" value="Unknown"> Unknown
                            </div>
                            <div class="form-group">
                                <label>Reproductive History:</label><br>
                                <input type="checkbox" name="reproductive_history[]" value="IUCD"> IUCD (Intrauterine Contraceptive Device)<br>
                                <input type="checkbox" name="reproductive_history[]" value="Breastfeeding"> Breastfeeding<br>
                            </div>
                            <div class="form-group">
                                <label>Menopausal Status:</label><br>
                                <input type="radio" name="menopausal_status" value="Pre-menopausal"> Pre-menopausal
                                <input type="radio" name="menopausal_status" value="Post-menopausal"> Post-menopausal
                            </div>
                        </div>
                        <!-- For Men -->
                        <div id="male-conditions" style="display: none;">
                            <h6>For Men</h6>
                            <div class="form-group">
                                <input type="checkbox" name="male_conditions[]" value="Prostate Enlargement (BPH)"> Prostate Enlargement (BPH)<br>
                                <input type="checkbox" name="male_conditions[]" value="Hormonal Therapy (Testosterone)"> Hormonal Therapy (e.g., Testosterone)<br>
                            </div>
                        </div>
                        <!-- For Children -->
                        <div id="children-conditions" style="display: none;">
                            <h6>For Children (Age < 18)</h6>
                            <div class="form-group">
                                <label>Congenital Conditions:</label>
                                <input type="text" id="congenital_conditions" name="congenital_conditions" class="form-control"><br>
                                <label>Vaccination History Relevant to FNAC:</label>
                                <input type="text" id="vaccination_history" name="vaccination_history" class="form-control"><br>
                                <label>Developmental Delays:</label>
                                <input type="checkbox" name="developmental_delays" value="Yes"> Yes<br>
                            </div>
                        </div>
                    </div>
                    
                        <button type="submit" class="btn btn-primary">Submit</button>
                </form>
            </div>

            <div class="container mt-4">
                    <h5>Clinical Information</h5>
                    <form id="clinical-information-form">

                        <!-- Reason for FNAC -->
                        <div class="form-group">
                            <label for="reason-for-fnac">Reason for FNAC:</label>
                            <select id="reason-for-fnac" name="reason_for_fnac" class="form-control">
                                <option value="Lump/Swelling">Lump/Swelling</option>
                                <option value="Lymphadenopathy">Lymphadenopathy</option>
                                <option value="Suspected malignancy">Suspected malignancy</option>
                                <option value="Others">Others</option>
                            </select>
                            <input type="text" id="other-reason" name="other_reason" class="form-control mt-2" placeholder="If Other, specify">
                        </div>
                        
                        <!-- Clinical History -->
                        <div class="form-group">
                            <label for="clinical-history">Clinical History:</label>
                            <textarea id="clinical-history" name="clinical_history" class="form-control" rows="4" placeholder="Enter detailed clinical notes"></textarea>
                        </div>
                        
                        <!-- Site of Aspiration -->
                        <div class="form-group">
                            <label for="site-of-aspiration">Site of Aspiration:</label>
                            <select id="site-of-aspiration" name="site_of_aspiration" class="form-control">
                                <option value="Thyroid">Thyroid</option>
                                <option value="Breast">Breast</option>
                                <option value="Lymph node">Lymph node</option>
                                <option value="Lung">Lung</option>
                                <option value="Others">Others</option>
                            </select>
                            <input type="text" id="other-site" name="other_site" class="form-control mt-2" placeholder="If Other, specify">
                        </div>

                        <!-- Indication for Aspiration -->
                        <div class="form-group">
                            <label for="indication-for-aspiration">Indication for Aspiration:</label>
                            <textarea type="text" id="indication-for-aspiration" name="indication_for_aspiration" class="form-control" placeholder="Enter indication for aspiration"></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">Submit</button>
                    </form>
            </div>

    </div>
</body>
</html>

<script>
    function selectLabNumber(searchText) {
        var select = document.getElementById('lab_number');
        var options = select.getElementsByTagName('option');

        for (var i = 0; i < options.length; i++) {
            var option = options[i];
            if (option.textContent.toLowerCase().indexOf(searchText.toLowerCase()) !== -1) {
                option.selected = true;
                break;
            }
        }
    }

    window.onload = function() {
        const storedAssistant = sessionStorage.getItem('cyto_assistant_name');
        const storedStation = sessionStorage.getItem('cyto_station_type');
        if (storedAssistant) {
            document.getElementById('cyto_assistant_name').value = storedAssistant;
        }
        if (storedStation) {
            document.getElementById('cyto_station_type').value = storedStation;
        }
    };

    
    document.getElementById('cytoForm').addEventListener('submit', function(event) {
        const selectedAssistant = document.getElementById('cyto_assistant_name').value;
        const selectedStation = document.getElementById('cyto_station_type').value;
        sessionStorage.setItem('gross_assistant_name', selectedAssistant);
        sessionStorage.setItem('gross_station_type', selectedStation);
    });

    document.addEventListener("DOMContentLoaded", function() {
            var searchInput = document.getElementById("searchLabNumber");
            if (searchInput) {
                searchInput.focus(); 
            }
        }
    );
</script>

<script>
    // Get patient gender and age from the table data
    const patientGender = '<?php echo htmlspecialchars($gender); ?>';
    const patientAge = <?php echo htmlspecialchars($patient['Age']); ?>;
    console.log(patientGender); 

    // Show/Hide gender-specific fields based on gender
    if (patientGender === 'Female') {
        document.getElementById('female-conditions').style.display = 'block';
        document.getElementById('male-conditions').style.display = 'none';
    } else if (patientGender === 'Male') {
        document.getElementById('male-conditions').style.display = 'block';
        document.getElementById('female-conditions').style.display = 'none';
    }

    // Show/Hide child-specific fields based on age
    if (patientAge < 18) {
        document.getElementById('children-conditions').style.display = 'block';
    } else {
        document.getElementById('children-conditions').style.display = 'none';
    }
</script>

<script>
    // Show/hide "Others" text inputs based on the selection of dropdown options
    document.getElementById('reason-for-fnac').addEventListener('change', function() {
        if (this.value === 'Others') {
            document.getElementById('other-reason').style.display = 'block';
        } else {
            document.getElementById('other-reason').style.display = 'none';
        }
    });

    document.getElementById('site-of-aspiration').addEventListener('change', function() {
        if (this.value === 'Others') {
            document.getElementById('other-site').style.display = 'block';
        } else {
            document.getElementById('other-site').style.display = 'none';
        }
    });

    // Trigger change event on page load to hide the "Others" inputs initially
    document.getElementById('reason-for-fnac').dispatchEvent(new Event('change'));
    document.getElementById('site-of-aspiration').dispatchEvent(new Event('change'));
</script>

<?php 
$NBMAX = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;
$max = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;


print '</div></div>';

// End of page
llxFooter();
$db->close();
?>