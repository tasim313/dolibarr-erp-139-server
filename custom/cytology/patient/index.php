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
                <div class="row">
                    <div class="col-lg-7 mx-auto">
                        <div class="card mt-2 mx-auto p-4 bg-light">
                            <div class="container card-body bg-light">
                                <form role="form" style="margin-top: 20px;" id="cytoForm" method="post" action="../Cyto/new_patient_create.php">
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
                                                                $storedAssistant = isset($_SESSION['cyto_assistant_name']) && $_SESSION['cyto_assistant_name'] === $assistant['username'] ? 'selected' : '';
                                                            }
                                                            echo "<option value='{$assistant['username']}' $selected>{$assistant['username']}</option>";
                                                        }
                                                    ?>
                                                    </select>
                                                </td>
                                                <td>
                                                    <select id="cyto_station_type" class="form-control" required>
                                                        <option value="">--Select a Station--</option>
                                                        <option value="One" <?php echo isset($_SESSION['cyto_station_type']) && $_SESSION['cyto_station_type'] === 'One' ? 'selected' : ''; ?>>One</option>
                                                        <option value="Two" <?php echo isset($_SESSION['cyto_station_type']) && $_SESSION['cyto_station_type'] === 'Two' ? 'selected' : ''; ?>>Two</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input id="lab_number" class="form-control" type="text" value="<?php echo $LabNumber; ?>">
                                                    <input type="hidden" id="gross_status" name="gross_status" value="Done">
                                                    <input type="hidden" id="gross_created_user" name="gross_created_user" value="<?php echo $gross_created_user; ?>">
                                                </td>
                                                <!-- <td colspan="2" class="text-center">
                                                    <button type="submit" class="btn btn-primary">Submit</button>
                                                </td> -->
                                            </tr>
                                        </tbody>
                                    </table>
                                </form>
                                        
                            </div>
                        </div>
                    </div>
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
                <div class="container ">
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
                                            <td><?= htmlspecialchars($history['referredby_dr']) ?></td>
                                            <td><?= htmlspecialchars($history['referred_from']) ?></td>
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

            </div>
        
            <!-- Clinical Information -->
            <div class="container">
                    <h3>Clinical Information</h3>
                    <form id="clinical-information-form">

                        <!-- Reason for FNAC -->
                        <div class="form-group">
                            <label for="reason-for-fnac">Cheif Complain:</label>
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
                            <label for="clinical-history">Relevant Clinical History:</label>
                            <textarea id="clinical-history" name="clinical_history" class="form-control" rows="4" placeholder="Enter detailed clinical notes"></textarea>
                        </div>
                        
                        <!-- Site of Aspiration -->
                        <div class="form-group">
                            <label for="site-of-aspiration">OnExamination:</label>
                            <textarea type="text" id="site-of-aspiration" name="site-of-aspiration" class="form-control" placeholder="Enter on examination note"></textarea>
                        </div>

                        <!-- Sample Type Collected -->
                        <div class="form-group">
                            <label>Sample Type Collected:</label>
                            <div class="form-check">
                                <input type="checkbox" id="solid-tissue" name="sample_type[]" value="Solid tissue" class="form-check-input">
                                <label class="form-check-label" for="solid-tissue">Solid tissue</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" id="cystic-fluid" name="sample_type[]" value="Cystic fluid" class="form-check-input">
                                <label class="form-check-label" for="cystic-fluid">Cystic fluid</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" id="blood-stained-fluid" name="sample_type[]" value="Blood-stained fluid" class="form-check-input">
                                <label class="form-check-label" for="blood-stained-fluid">Blood-stained fluid</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" id="other-sample-type" name="sample_type[]" value="Others" class="form-check-input">
                                <label class="form-check-label" for="other-sample-type">Others:</label>
                            </div>
                            <input type="text" id="other-sample-input" name="other_sample" class="form-control mt-2" placeholder="If Other, specify">
                        </div>

                        <!-- Indication for Aspiration -->
                        <div class="form-group">
                            <label for="indication-for-aspiration">Aspiration Note:</label>
                            <textarea type="text" id="indication-for-aspiration" name="indication_for_aspiration" class="form-control" placeholder="Enter aspiration note"></textarea>
                        </div>

                        <!-- <button type="submit" class="btn btn-primary">Submit</button> -->
                    </form>
            </div>

            
            <!-- FNAC Collection Details -->
            <?php

                $slideBaseCode = preg_replace('/^[A-Za-z]{3}/', '', $LabNumber); 
                $locationOptions = ['','Proper','Thyroid', 'Breast', 'Lymph node', 'Lung', 'Other'];
            ?>
            <div class="container mt-4">
                <h3>FNAC Fixation Details</h3>
                <form id="fnac-fixation-form">
                    <!-- Total Slides Prepared -->
                    <div class="form-group">
                        <label for="total-slides">Total Slides Prepared:</label>
                        <input type="number" id="total-slides" name="total_slides" class="form-control" min="1" required>
                    </div>

                    <!-- Slide Fixation Details -->
                    <div class="form-group">
                        <label>Slide Fixation Details:</label>
                        <table class="table table-bordered" id="fixation-details-table">
                            <thead>
                                <tr>
                                    <th>Slide Number</th>
                                    <th>Slide Code</th>
                                    <th>Location</th>
                                    <th>Fixation Method</th>
                                    <th>Dry</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="fixation-details-body">
                                <!-- Dynamic Rows -->
                            </tbody>
                        </table>
                        <button type="button" class="btn btn-success" id="add-row">+ Add More</button>
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

                    <!-- Special Instructions or Tests Required -->
                    <div class="form-group">
                        <label for="special-instructions">
                            Special Instructions or Tests Required:
                            <button type="button" class="btn btn-link toggle-btn" data-target="#special-instructions-section">+</button>
                        </label>
                        <div id="special-instructions-section" class="toggle-section" style="display: none;">
                            <textarea id="special-instructions" name="special_instructions" class="form-control" rows="3" placeholder="Enter tests like special stains, immunocytochemistry, etc."></textarea>
                        </div>
                    </div>
                    
                    
                    <!-- Number of Passes Performed -->
                    <div class="form-group">
                        <label for="number-of-needle">Number of Needle Used:</label>
                        <input type="number" id="number-of-needle" name="number_of_needle" class="form-control" min="0">
                    </div>
                    <div class="form-group">
                        <label for="number-of-syringe">Number of Syringe Used:</label>
                        <input type="number" id="number-of-syringe" name="number_of_syringe" class="form-control" min="0">
                    </div>

                    <button type="submit" class="btn btn-primary">Submit</button>
                </form>
            </div>
            
           

    </div>
</body>
</html>

<!-- Doctor , Assistant and Station information -->
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


<!-- Clinical Information -->
<script>
    // Show/hide "Others" text inputs based on the selection of dropdown options
    document.getElementById('reason-for-fnac').addEventListener('change', function() {
        if (this.value === 'Others') {
            document.getElementById('other-reason').style.display = 'block';
        } else {
            document.getElementById('other-reason').style.display = 'none';
        }
    });

    // Trigger change event on page load to hide the "Others" inputs initially
    document.getElementById('reason-for-fnac').dispatchEvent(new Event('change'));
</script>

<!-- Procedure Details -->
<script>
    // Show/hide "Others" text inputs based on checkbox selection
    document.getElementById('other-sample-type').addEventListener('change', function() {
        document.getElementById('other-sample-input').style.display = this.checked ? 'block' : 'none';
    });

    // Hide "Others" inputs by default
    document.getElementById('other-sample-input').style.display = 'none';
    
</script>

<!-- FNAC Fixation Details -->
<script>
    const locationOptions = [" ", "Proper", "Thyroid", "Breast", "Lymph node", "Lung", "Other"]; // Available locations
    let locationCounter = { "Proper": 0 }; // Keeps track of counts for "Proper" location, initialized to 0
    let locationLetters = {}; // Keeps track of the assigned letters for other locations
    let rowCounter = 1; // Row counter to track the rows

    // Function to update slide code dynamically based on location
    function updateSlideCode(row, location) {
        let slideCode;
        const slideBaseCode = "<?php echo $slideBaseCode; ?>".replace(/-/g, ''); // Use PHP to insert the slide base code

        if (location === "Proper") {
            // For "Proper", use "Pro" and increment sequentially
            const proCount = (locationCounter['Proper'] || 0) + 1;
            slideCode = `${slideBaseCode}FC-Pro-${proCount}`;
            locationCounter['Proper'] = proCount;
        } else {
            // For other locations, assign letters A, B, C, etc. and increment sequentially
            if (!locationLetters[location]) {
                // If it's the first time this location is selected, assign a letter
                const letters = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
                locationLetters[location] = letters[Object.keys(locationLetters).length]; // Assign letter to location
                locationCounter[location] = 1; // Start counting from 1
            }

            const locationLetter = locationLetters[location]; // Use the assigned letter for the location
            const locationCount = locationCounter[location];

            slideCode = `${slideBaseCode}FC-${locationLetter}-${locationCount}`;
            locationCounter[location] = locationCount + 1;
        }

        // Update the slide code in the row
        const slideCodeCell = row.querySelector('.slide-code');
        slideCodeCell.textContent = slideCode;
    }

    // Function to handle changes in the Location or Fixation Method selection
    function handleLocationOrFixationChange(row, locationDropdown, fixationDropdown, dryCheckbox) {
        const otherLocationInput = row.querySelector('.other-location-input');
        const otherFixationInput = row.querySelector('.other-fixation-input');
        
        // Handle "Other" Location input
        if (locationDropdown.value === "Other") {
            otherLocationInput.style.display = "inline-block"; // Show input field for "Other"
        } else {
            otherLocationInput.style.display = "none"; // Hide input field for "Other"
        }

        // Handle "Other" Fixation Method input
        if (fixationDropdown.value === "Other") {
            otherFixationInput.style.display = "inline-block"; // Show input field for "Other"
        } else {
            otherFixationInput.style.display = "none"; // Hide input field for "Other"
        }

        // Handle Dry checkbox - Hide Fixation Method if selected
        if (dryCheckbox.checked) {
            fixationDropdown.style.display = "none"; // Hide Fixation Method dropdown
            fixationDropdown.disabled = true; // Disable the Fixation Method dropdown
        } else {
            fixationDropdown.style.display = "inline-block"; // Show Fixation Method dropdown
            fixationDropdown.disabled = false; // Enable the Fixation Method dropdown
        }
    }

    // Add row to table
    document.getElementById('add-row').addEventListener('click', function () {
        const tbody = document.getElementById('fixation-details-body');
        const newRow = document.createElement('tr');

        newRow.innerHTML = `
            <td>${rowCounter}</td>
            <td class="slide-code"></td>
            <td>
                <select class="form-control location-select">
                    ${locationOptions.map(option => `<option value="${option}">${option}</option>`).join('')}
                </select>
                <input type="text" class="form-control other-location-input" placeholder="Specify other location" style="display: none;">
            </td>
            <td>
                <select class="form-control fixation-method-select">
                    <option value="Alcohol">Alcohol fixation</option>
                    <option value="Formalin">Formalin fixation</option>
                    <option value="Air-dried">Air-dried</option>
                    <option value="Other">Other</option>
                </select>
                <input type="text" class="form-control other-fixation-input" placeholder="Specify fixation method" style="display: none;">
            </td>
            <td>
                <input type="checkbox" class="dry-checkbox">
            </td>
            <td>
                <button type="button" class="btn btn-danger remove-row">Remove</button>
            </td>
        `;

        const locationDropdown = newRow.querySelector('.location-select');
        const fixationDropdown = newRow.querySelector('.fixation-method-select');
        const dryCheckbox = newRow.querySelector('.dry-checkbox');
        
        // Handle changes in location and fixation method selection
        locationDropdown.addEventListener('change', function () {
            updateSlideCode(newRow, this.value);
            handleLocationOrFixationChange(newRow, locationDropdown, fixationDropdown, dryCheckbox);
        });

        fixationDropdown.addEventListener('change', function () {
            handleLocationOrFixationChange(newRow, locationDropdown, fixationDropdown, dryCheckbox);
        });

        dryCheckbox.addEventListener('change', function () {
            handleLocationOrFixationChange(newRow, locationDropdown, fixationDropdown, dryCheckbox);
        });

        newRow.querySelector('.remove-row').addEventListener('click', function () {
            tbody.removeChild(newRow);
        });

        tbody.appendChild(newRow);
        rowCounter++;
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


<?php 
$NBMAX = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;
$max = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;


print '</div></div>';

// End of page
llxFooter();
$db->close();
?>