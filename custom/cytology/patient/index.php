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
    </style>
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

            </div>
        
            <!-- Clinical Information -->
            <div class="container">
                    <h3>Clinical Information</h3>
                    <form id="clinical-information-form">
                        
                        <!-- Reason for FNAC -->
                        <div class="form-group dropdown">
                                <label for="chief-complain">Chief Complain:</label>
                                <button onclick="toggleDropdown()"class="form-control" style="width: 1145px;" id="selected-value">Enter Complain</button>
                                <div id="myDropdown" class="dropdown-content" style="display: none;">
                                    <input type="text" placeholder="Search.." id="search-reason" class="form-control mb-2" onkeyup="filterFunction()">
                                    <select id="reason-for-fnac" name="reason_for_fnac" class="form-control" size="4" onchange="selectOption()">
                                        <option value="Lump/Swelling">Lump/Swelling</option>
                                        <option value="Lymphadenopathy">Lymphadenopathy</option>
                                        <option value="Suspected malignancy">Suspected malignancy</option>
                                        <option value="Others">Others</option>
                                    </select>
                                </div>
                                <input type="text" id="other-reason" name="other_reason" class="form-control mt-2" placeholder="If Other, specify" style="display: none;">
                        </div>

                        <!-- Clinical History -->
                        <div class="form-group">
                            <label for="clinical-history">Relevant Clinical History:</label>
                            <textarea id="clinical-history" name="clinical_history" class="form-control" rows="3" placeholder="Enter detailed clinical notes"></textarea>
                        </div>
                        
                        <!-- Site of Aspiration -->
                        <div class="form-group">
                            <label for="site-of-aspiration">OnExamination:</label>
                            <textarea type="text" id="site-of-aspiration" name="site-of-aspiration" class="form-control" rows="3" placeholder="Enter on examination note"></textarea>
                        </div>

                        <!-- Indication for Aspiration -->
                        <div  class="form-group">
                            <label for="indication-for-aspiration">Aspiration Note:</label><br>
                            <select id="regionSelector">
                                <option value="">Select Region</option>
                                <option value="thyroid">Thyroid Region</option>
                                <option value="cervical">Cervical Region</option>
                                <option value="parotid">Parotid Region</option>
                                <option value="lymphNode">Lymph Node</option>
                                <option value="tongueAndOral">Tongue and Oral Region</option>
                                <option value="chestWall">Chest Wall</option>
                                <option value="preauricularAndPostauricularRegions">Preauricular and Postauricular Regions</option>
                                <option value="axillaryRegion">Axillary Region</option>
                                <option value="miscellaneous">Miscellaneous</option>
                                <option value="cytologySlides">Cytology Slides</option>
                            </select>
                            <textarea type="text" id="aspirationNoteEditor" name="indication_for_aspiration" class="form-control" rows="4" placeholder="Enter aspiration note"></textarea>
                        </div>

                        <!-- FNAC Collection Details -->
                        <?php
                            $slideBaseCode = preg_replace('/^[A-Za-z]{3}/', '', $LabNumber); 
                            $locationOptions = ['','Proper','Thyroid', 'Breast', 'Lymph node', 'Lung', 'Other'];
                        ?>
                        <h3>FNAC Fixation Details</h3>
                         <!-- Total Slides Prepared -->
                        <div class="form-group form-group-slide d-flex align-items-center">
                            <label for="slides-input" class="mr-2">Slide:</label> &nbsp; 
                            <input type="text" id="slides-input" name="slides_input" class="form-control mr-3" placeholder="Enter slide (e.g., 2+1)" required> &nbsp;  &nbsp;  &nbsp; 
                            <label for="location-input" class="mr-2">Location:</label> &nbsp; 
                            <input type="text" id="location-input" name="location_input" class="form-control mr-3" placeholder="Enter location (e.g., Proper)" required> &nbsp; 
                            <button type="button" class="btn btn-primary" id="populate-table">Generate slide</button>
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

<!-- FNAC Fixation Details -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const locationOptions = [" ", "proper", "thyroid", "beast", "lymph node", "lung", "other"];
        let locationCounter = { "Proper": 0 };
        let locationLetters = {};
        let rowCounter = 1;

        // Function to update slide code dynamically
        function updateSlideCode(row, location) {
                const slideBaseCode = "<?php echo $slideBaseCode; ?>".replace(/-/g, '');
                let slideCode;

                if (location.toLowerCase() === "proper") {
                    // Handle Proper location separately
                    const proCount = (locationCounter["Proper"] || 0) + 1;
                    slideCode = `${slideBaseCode}FC-Pro-${proCount}`;
                    locationCounter["Proper"] = proCount; // Increment the count for "Proper"
                } else {
                    // For other locations, use letters like A, B, C, etc.
                    if (!locationLetters[location]) {
                        // Assign letters for each unique location type (A, B, C, etc.)
                        const letters = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
                        locationLetters[location] = letters[Object.keys(locationLetters).length];
                        locationCounter[location] = 1; // Start counting for the new location
                    }

                    const locationLetter = locationLetters[location]; // A, B, C, etc.
                    const locationCount = locationCounter[location];
                    slideCode = `${slideBaseCode}FC-${locationLetter}-${locationCount}`;
                    locationCounter[location] = locationCount + 1; // Increment the count for that location
                }

                // Update the slide code in the respective row
                row.querySelector('.slide-code').textContent = slideCode;
        }

        // Function to handle changes in Location and Fixation Method
        function handleLocationOrFixationChange(row, locationDropdown, fixationDropdown, dryCheckbox) {
            const otherLocationInput = row.querySelector('.other-location-input');
            const otherFixationInput = row.querySelector('.other-fixation-input');

            // Show or hide the "other" input fields based on selection
            otherLocationInput.style.display = locationDropdown.value === "Other" ? "inline-block" : "none";
            otherFixationInput.style.display = fixationDropdown.value === "Other" ? "inline-block" : "none";

            // Disable fixation dropdown if Dry is selected
            if (dryCheckbox.checked) {
                fixationDropdown.disabled = true;
                fixationDropdown.style.display = "none";  // Hide fixation dropdown
            } else {
                fixationDropdown.disabled = false;
                fixationDropdown.style.display = "inline-block";  // Show fixation dropdown
            }
        }

        // Function to add a row to the table
        function addRow(location, isDry) {
                const tbody = document.getElementById('fixation-details-body');
                const newRow = document.createElement('tr');

                newRow.innerHTML = `
                    <td>${rowCounter}</td>
                    <td class="slide-code"></td>
                    <td>
                        <select class="form-control location-select">
                            ${locationOptions.map(opt => `<option value="${opt}">${opt}</option>`).join('')}
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
                        <input type="checkbox" class="dry-checkbox" ${isDry ? 'checked' : ''}>
                    </td>
                    <td>
                        <button type="button" class="btn btn-danger remove-row">Remove</button>
                    </td>
                `;

                const locationDropdown = newRow.querySelector('.location-select');
                const fixationDropdown = newRow.querySelector('.fixation-method-select');
                const dryCheckbox = newRow.querySelector('.dry-checkbox');

                // Set default location and handle other inputs
                locationDropdown.value = location;
                handleLocationOrFixationChange(newRow, locationDropdown, fixationDropdown, dryCheckbox);

                // Location change handler
                locationDropdown.addEventListener('change', () => {
                    const locationValue = locationDropdown.value.trim().toLowerCase();
                    const matchingLocation = locationOptions.find(option => option.toLowerCase() === locationValue);

                    if (matchingLocation) {
                        locationDropdown.value = matchingLocation; // Set the matched location
                    } else {
                        locationDropdown.value = "Other"; // If no match, select "Other"
                    }

                    updateSlideCode(newRow, locationDropdown.value);
                });

                // Dry checkbox and fixation method handling
                dryCheckbox.addEventListener('change', () => handleLocationOrFixationChange(newRow, locationDropdown, fixationDropdown, dryCheckbox));
                fixationDropdown.addEventListener('change', () => handleLocationOrFixationChange(newRow, locationDropdown, fixationDropdown, dryCheckbox));

                newRow.querySelector('.remove-row').addEventListener('click', () => tbody.removeChild(newRow));

                tbody.appendChild(newRow);
                updateSlideCode(newRow, location);
                rowCounter++;
            }

        // Populate table based on user input
        document.getElementById('populate-table').addEventListener('click', function () {
            const slidesInput = document.getElementById('slides-input').value.trim();
            let locationInput = document.getElementById('location-input').value.trim();

            if (!slidesInput || !locationInput) {
                alert('Please fill in all fields.');
                return;
            }

            const [fixationSlides, drySlides] = slidesInput.split('+').map(Number);
            if (isNaN(fixationSlides) || isNaN(drySlides)) {
                alert('Invalid slide input format. Use "2+1" format.');
                return;
            }

            // Case-insensitive matching for location
            const matchingLocation = locationOptions.find(opt => opt.toLowerCase() === locationInput.toLowerCase());
            if (matchingLocation) {
                locationInput = matchingLocation; // Set matched location
            } else {
                alert(`Location "${locationInput}" not found. Using "Other" as default.`);
                locationInput = "Other";
            }

            // Add rows for fixation slides
            for (let i = 0; i < fixationSlides; i++) addRow(locationInput, false);

            // Add rows for dry slides
            for (let i = 0; i < drySlides; i++) addRow(locationInput, true);

            // If locationInput was set to "Other", focus the input for custom location
            if (locationInput === "Other") {
                const otherInputs = document.querySelectorAll('.other-location-input');
                otherInputs[otherInputs.length - 1].style.display = "inline-block";
            }
        });
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

<!-- Dry Slides Description/Additional Notes/Special Instructions or Tests Required -->
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

<!-- Aspiration Note -->
<script>
    document.getElementById('regionSelector').addEventListener('change', function () {
    const editor = document.getElementById('aspirationNoteEditor');
    const region = this.value;

    // Predefined templates
    const templates = {
        thyroid: `Firm and mobile nodule in the [right/left] lobe of thyroid, moved with deglutition, measuring: [__x__  cm] and yielded [__cc straw-colored fluid].
Swelling in the isthmus of thyroid, moved with deglutition, measuring: [__x__ cm] and yielded [blood mixed materials].`,
            cervical: `Soft to firm, less mobile, non-tender swelling at left cervical level IIA, measuring: __x__ cm and yielded __cc blood.
Firm, non-tender and mobile swelling at right cervical region, measuring: __x__  cm and yielded blood mixed materials.
Firm and mobile swelling at left level II region, measuring: __x__ cm and yielded pus.`,
            parotid:`Firm, non-tender and mobile swelling in right/left parotid region, measuring: __x__ cm and yielded blood mixed materials.
Soft to firm, less mobile, and mildly tender swelling in left parotid region, measuring: __x__ cm and yielded blood mixed materials.`,
            lymphNode: `Firm, mobile, and non-tender swelling in [right/left] cervical lymph node at level-[V], measuring: [__x__ cm] and yielded [blood mixed material].
Multiple mobile and non-tender lymph nodes at [right/left] supraclavicular region, the largest one measuring: [__x__ cm] and yielded [grayish brown materials].
Firm, matted, mobile lymph nodes in [right/left] cervical region at levels [IIA/III], largest measuring: [__x__ cm] and yielded [grayish brown material].`,
            tongueAndOral:`Mobile swelling in the [right/left lateral border of tongue], measuring: [__x__ cm] and yielded [blood mixed fluid].`,
            chestWall: `Two firm, non-tender, mobile swellings at the [right/left] chest wall, larger one measuring: [__x__ cm] and smaller one measuring: [__x__ cm], yielded [grayish brown materials].`,
            preauricularAndPostauricularRegions:`Firm, mobile, and non-tender swelling in [preauricular/postauricular] region, measuring: [__x__  cm] and yielded [whitish materials].`,
            axillaryRegion:`Soft to firm, diffuse, and tender swelling in [left/right] axilla, measuring: [__x__ cm] and yielded [blood mixed materials]`,
            miscellaneous:`One ill-defined, soft, non-tender, non-mobile, subcutaneous swelling in [suprasternal region], measuring: [__x__  cm] and yielded [scant pus].
Aspiration yielded [whitish materials] from a mobile, non-tender, firm swelling in the [left preauricular region]`,
            cytologySlides: `[Ten/Two/Three] unstained cytology slides received without labels, collected outside the laboratory.
Stained cytology slides labeled [ALC: D6014/24 (The Alpha Laboratory)] received for review.`,
            // Add other regions with their respective templates
        };

        if (region && templates[region]) {
            editor.value = templates[region];
        } else {
            editor.value = ''; // Clear editor if no region is selected
        }
    });
</script>

<!-- Search feature for dropdown Reason for FNAC -->
<script>
    // Toggle dropdown visibility
    function toggleDropdown() {
        const dropdown = document.getElementById("myDropdown");
        dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
    }

    // Function to filter dropdown options
    function filterFunction() {
        const input = document.getElementById("search-reason");
        const filter = input.value.toUpperCase();
        const select = document.getElementById("reason-for-fnac");
        const options = select.options;

        for (let i = 0; i < options.length; i++) {
            const txtValue = options[i].textContent || options[i].innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                options[i].style.display = ""; // Show matching options
            } else {
                options[i].style.display = "none"; // Hide non-matching options
            }
        }
    }

    // Update the selected value when an option is chosen
    function selectOption() {
        const select = document.getElementById("reason-for-fnac");
        const selectedValue = select.options[select.selectedIndex].text;
        document.getElementById("selected-value").innerText = selectedValue;

        // Show "Other" input if "Others" is selected
        const otherReasonInput = document.getElementById("other-reason");
        if (select.value === "Others") {
            otherReasonInput.style.display = "block";
        } else {
            otherReasonInput.style.display = "none";
        }

        // Hide dropdown after selection
        document.getElementById("myDropdown").style.display = "none";
    }
</script>

<!-- Aspiration Note -->
<script>
    // Function to toggle the dropdown visibility
    function toggleAspirationNoteDropdown() {
        const dropdown = document.getElementById("myDropdown");
        dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
    }

    // Function to filter regions in the dropdown
    function filterRegions() {
        const input = document.getElementById("search-region");
        const filter = input.value.toUpperCase();
        const select = document.getElementById("regionSelector");
        const options = select.options;

        for (let i = 0; i < options.length; i++) {
            const txtValue = options[i].textContent || options[i].innerText;
            options[i].style.display = txtValue.toUpperCase().includes(filter) ? "" : "none";
        }
    }

    // Function to handle region selection
    function selectRegionOption() {
        const select = document.getElementById("regionSelector");
        const selectedValue = select.options[select.selectedIndex].text;
        const button = document.getElementById("selected-value");
        const otherRegionInput = document.getElementById("other-region");

        // Update button text with the selected value
        button.textContent = selectedValue;

        // Show or hide the "Other" input field based on the selection
        if (select.value === "Others") {
            otherRegionInput.style.display = "block";
        } else {
            otherRegionInput.style.display = "none";
            otherRegionInput.value = ""; // Clear the "Other" input field
        }

        // Hide the dropdown after selection
        document.getElementById("myDropdown").style.display = "none";
    }
</script>


<?php 
$NBMAX = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;
$max = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;


print '</div></div>';

// End of page
llxFooter();
$db->close();
?>