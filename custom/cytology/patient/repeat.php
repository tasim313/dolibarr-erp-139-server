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
$prefixLabNumber = $LabNumber;
// Check if the LabNumber already starts with "FNA"
if (strpos($prefixLabNumber, 'FNA') !== 0) {
    $prefixLabNumber = 'FNA' . $prefixLabNumber; // Prepend "FNA" if not already present
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
        
    default:
        echo "<h1>Access Denied</h1>";
        echo "<p>You are not authorized to view this page.</p>";
        exit; // Terminate script execution
}


$host = $_SERVER['HTTP_HOST'];
$homeUrl = "http://" . $host . "/custom/cytology/cytologyindex.php";

?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<!-- Import the JavaScript file -->
	<link href="../../grossmodule/bootstrap-3.4.1-dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="../../grossmodule/bootstrap-3.4.1-dist/js/bootstrap.min.js"></script>

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

        #dry-slides-description:focus {
            box-shadow: 0 0 10px 2px rgba(233, 54, 81, 0.7); 
            border-color:rgb(223, 14, 77); 
        }
        #clinical-history:focus{
            box-shadow: 0 0 10px 2px rgba(233, 54, 81, 0.7); 
            border-color:rgb(223, 14, 77); 
        }
        #site-of-aspiration-editor:focus{
            box-shadow: 0 0 10px 2px rgba(233, 54, 81, 0.7);
            border-color:rgb(223, 14, 77); 
        }
        #fixation-comments:focus{
            box-shadow: 0 0 10px 2px rgba(233, 54, 81, 0.7);
            border-color:rgb(223, 14, 77); 
        }
        #number-of-needle:focus{
            box-shadow: 0 0 10px 2px rgba(233, 54, 81, 0.7);
            border-color:rgb(223, 14, 77); 
        }
        #number-of-syringe:focus{
            box-shadow: 0 0 10px 2px rgba(233, 54, 81, 0.7);
            border-color:rgb(223, 14, 77); 
        }
        #location-input:focus{
            box-shadow: 0 0 10px 2px rgba(233, 54, 81, 0.7);
            border-color:rgb(223, 14, 77); 
        }
        #slides-input:focus{
            box-shadow: 0 0 10px 2px rgba(233, 54, 81, 0.7);
            border-color:rgb(223, 14, 77); 
        }
        #aspiration_materials-input:focus{
            box-shadow: 0 0 10px 2px rgba(233, 54, 81, 0.7);
            border-color:rgb(223, 14, 77); 
        }
    </style>
</head>
<body>
	<div style="display: flex; justify-content: space-between; align-items: center;">
        <!-- Left-side buttons -->
        <div style="display: flex; gap: 10px;">
            <a href="<?= $homeUrl ?>" class="btn btn-info btn-md">Back</a>
            <a href="./recall.php?LabNumber=<?php echo urlencode($LabNumber); ?>" class="btn btn-info btn-md">Recall</a>
        </div>
        <!-- Right-side button -->
        <div>
            <a href="./postponed.php?LabNumber=<?php echo urlencode($LabNumber); ?>" class="btn btn-warning btn-md">Postpone FNAC</a> &nbsp; &nbsp; &nbsp;
            <a href="./cancel.php?LabNumber=<?php echo urlencode($LabNumber); ?>" class="btn btn-danger btn-md">Cancel FNAC</a>
        </div>
    </div>

	<div class="container">
    	<div class="text-center mt-5 ">
            <h3>Recall Patient</h3>
        </div>
        <!-- Patient Information -->
		<?php

        	// Fetch patient information using the trimmed LabNumber
        	$patient_information = get_patient_details_information($LabNumber);
        	$genderOptions = [
        	    '1' => 'Male',
        	    '2' => 'Female',
        	    '3' => 'Other'
        	];
        ?>
        <div>
            <div class="row">
                <div class="col-12">
                	<?php if (!empty($patient_information)) { ?>
					<table class="table table-bordered table-striped">
						<thead class="thead-dark">
							<tr>
								<th scope="col">Patient Name</th>
								<th scope="col">Patient Code</th>
								<th scope="col">Address</th>
								<th scope="col">Patient Phone Number</th>
								<th scope="col">Gender</th>
								<th scope="col">Age</th>
								<th scope="col">Attendant Name</th>
								<th scope="col">Attendant Relation</th>
								<th scope="col">Attendant Phone Number</th>
								<th scope="col">Edit</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($patient_information as $patient) { 
								$gender = isset($genderOptions[$patient['Gender']]) ? $genderOptions[$patient['Gender']] : 'Unknown';
							?>
							<tr data-rowid="<?php echo htmlspecialchars($patient['rowid']); ?>">
								<td style="width: 200px;">
									<input class="form-control edit-field d-none" name="name" value="<?php echo htmlspecialchars($patient['name']); ?>">
								</td>
								<td style="width: 200px;">
									<input class="form-control edit-field d-none" name="patient_code" value="<?php echo htmlspecialchars($patient['patient_code']); ?>" readonly>
								</td>
								<td style="width: 200px;">
									<input class="form-control edit-field d-none" name="address" value="<?php echo htmlspecialchars($patient['address']); ?>">
								</td>
								<td style="width: 200px;">
									<input class="form-control edit-field d-none" name="phone" value="<?php echo htmlspecialchars($patient['phone']); ?>">
								</td>
								<td style="width: 200px;">
									<select class="form-control edit-field d-none" name="gender">
										<?php foreach ($genderOptions as $key => $value) { ?>
											<option value="<?php echo htmlspecialchars($key); ?>" <?php echo $patient['Gender'] == $key ? 'selected' : ''; ?>><?php echo htmlspecialchars($value); ?></option>
										<?php } ?>
									</select>
								</td>
								<td style="width: 200px;">
									<input class="form-control edit-field d-none" name="age" value="<?php echo htmlspecialchars($patient['Age']); ?>">
								</td>
								<td style="width: 200px;">
									<input class="form-control edit-field d-none" name="att_name" value="<?php echo htmlspecialchars($patient['att_name']); ?>">
								</td>
								<td style="width: 200px;">
									<input class="form-control edit-field d-none" name="att_relation" value="<?php echo htmlspecialchars($patient['att_relation']); ?>">
								</td>
								<td style="width: 200px;">
									<input class="form-control edit-field d-none" name="fax" value="<?php echo htmlspecialchars($patient['fax']); ?>">
								</td>
								<td>
									
									<button class="btn btn-primary save-btn d-none">Save</button>
								</td>
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
            $patient_history = get_cyto_patient_history_list($LabNumber);
        ?>
        <div>
            <?php if (!empty($patient_history)): ?>
            <form action="../Cyto/save_patient_history.php" method="POST">
            	<div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th>Previous FNAC</th>
                                <th>Prev FNAC Date</th>
                                <th>Prev FNAC Operation</th>
                                <th>Report Informed Submit To The Lab</th>
                                <th>Report Collected From Patient</th>
                                <th>Referred By Dr</th>
                                <th>Referred From Hospital</th>
                                <th>Clinical History</th>
                                <th>Other Lab No</th>
                                <th>Prev Biopsy</th>
                                <th>Previous Biopsy Date</th>
                                <th>Previous Biopsy Operation</th>
                                <th>Referred By Dr (Text)</th>
                                <th>Referred From Hospital (Text)</th>
                                <th>Edit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($patient_history as $index => $history): ?>
                            <tr>
                                <!-- Hidden input to pass the rowid -->
                                <input type="hidden" name="rowid[<?= $index ?>]" value="<?= htmlspecialchars($history['rowid']) ?>">

                                <!-- Columns -->
                                <td>
                                    <input type="text" class="form-control" name="prev_fnac[<?= $index ?>]" value="<?= htmlspecialchars($history['prev_fnac']) ?>">
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="prev_fnac_date[<?= $index ?>]" value="<?= htmlspecialchars($history['prev_fnac_date']) ?>">
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="prev_fnac_op[<?= $index ?>]" value="<?= htmlspecialchars($history['prev_fnac_op']) ?>">
                                </td>
                                <td>
                                    <select class="form-control" name="informed[<?= $index ?>]" style="width: 200px; height: 70px;" multiple>
                                        <?php 
                                        $informedLabels = [1 => 'CT Scan Report', 2 => 'CT Scan Film', 3 => 'MRI Report', 4 => 'MRI Film', 5 => 'Others'];
                                        $informedValues = explode(',', $history['informed']);
                                        foreach ($informedLabels as $key => $label) {
                                            $selected = in_array($key, $informedValues) ? 'selected' : '';
                                            echo "<option value='$key' $selected>$label</option>";
                                        }
                                        ?>
                                    </select>
                                </td>
                                <td>
                                    <select class="form-control" name="given[<?= $index ?>]" style="width: 200px; height: 70px;" multiple>
                                        <?php 
                                        $givenLabels = [1 => 'CT Scan Report', 2 => 'CT Scan Film', 3 => 'MRI Report', 4 => 'MRI Film', 5 => 'Others'];
                                        $givenValues = explode(',', $history['given']);
                                        foreach ($givenLabels as $key => $label) {
                                            $selected = in_array($key, $givenValues) ? 'selected' : '';
                                            echo "<option value='$key' $selected>$label</option>";
                                        }
                                        ?>
                                    </select>
                                </td>

                                <td>
                                    <input type="text" class="form-control" name="referred_by_dr_lastname[<?= $index ?>]" value="<?= htmlspecialchars($history['referred_by_dr_lastname']) ?>">
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="referred_from_lastname[<?= $index ?>]" value="<?= htmlspecialchars($history['referred_from_lastname']) ?>">
                                </td>
                                <td>
                                    <textarea class="form-control" name="add_history[<?= $index ?>]" style="width: 250px; height: 50px;"><?= htmlspecialchars($history['add_history']) ?></textarea>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="other_labno[<?= $index ?>]" style="width: 100px; height: 50px;" value="<?= htmlspecialchars($history['other_labno']) ?>">
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="prev_biopsy[<?= $index ?>]" style="width: 100px; height: 50px;" value="<?= htmlspecialchars($history['prev_biopsy']) ?>">
                                </td>

                                <td>
                                    <input type="text" class="form-control" name="prev_biopsy_date[<?= $index ?>]" value="<?= htmlspecialchars($history['prev_biopsy_date']) ?>">
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="prev_biopsy_op[<?= $index ?>]" value="<?= htmlspecialchars($history['prev_biopsy_op']) ?>">
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="referred_by_dr_text[<?= $index ?>]" style="width: 200px; height: 50px;" value="<?= htmlspecialchars($history['referred_by_dr_text']) ?>">
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="referredfrom_text[<?= $index ?>]" style="width: 200px; height: 50px;" value="<?= htmlspecialchars($history['referredfrom_text']) ?>">
                                </td>
                                <td>
                                    <button type="submit" class="btn btn-primary">Save</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </form>
            <?php else: ?>
                <p class="text-center text-danger">No patient history available for the provided lab number.</p>
            <?php endif; ?>
        </div>
        
		<div>
			<h4 style="text-align: center; margin-top: 15px;">Previous Information Was Taken From This Patient</h4>
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
									$records = get_cyto_list($prefixLabNumber);
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
						
						<?php 
							$clinicalInformation = get_cyto_clinical_information($cyto_id);
						?>
						<table class="table table-bordered table-striped">
							<thead>
								<tr>
									<th>Chief Complain</th>
									<th>Relevant Clinical History</th>
									<th>On Examination</th>
								</tr>
							</thead>
							<tbody>
								<?php if (empty($clinicalInformation)): ?>
									<tr><td colspan="5">No data found.</td></tr>
								<?php else: ?>
									<?php foreach ($clinicalInformation as $info): ?>
										<tr>
												<input type="hidden" name="rowid" value="<?= $info['rowid'] ?>">
												<td>
													<textarea readonly class="form-control" name="chief_complain" data-rowid="<?= $info['rowid'] ?>" data-field="chief_complain"><?= htmlspecialchars($info['chief_complain']) ?></textarea>
												</td>
												<td>
													<textarea readonly class="form-control" name="relevant_clinical_history" data-rowid="<?= $info['rowid'] ?>" data-field="relevant_clinical_history"><?= htmlspecialchars($info['relevant_clinical_history']) ?></textarea>
												</td>
												<td>
													<textarea readonly class="form-control" name="on_examination" data-rowid="<?= $info['rowid'] ?>" data-field="on_examination"><?= htmlspecialchars($info['on_examination']) ?></textarea>
												</td>
										</tr>
									<?php endforeach; ?>
								<?php endif; ?>
							</tbody>
						</table>
                        
						<?php 
                   			$fixationDetails = get_cyto_fixation_details($cyto_id)
                		?>
						<table class="table table-bordered table-striped">
                    		<thead>
								<tr>
									<th>Slide Number</th>
									<th>Location</th>
									<th>Fixation Method</th>
									<th>Dry</th>
									<th>Aspiration Materials</th>
									<th>Special Instructions</th>
								</tr>
                    		</thead>
							<tbody>
								<?php foreach ($fixationDetails as $info): ?>
								<tr>
										<td>
											<input type="text" name="slide_number" class="form-control" 
												value="<?= htmlspecialchars($info['slide_number']) ?>" readonly>
										</td>
										<td>
											<input type="text" name="location" class="form-control" 
												value="<?= htmlspecialchars($info['location']) ?>" readonly>
										</td>
										<td>
											<input type="text" name="fixation_method" class="form-control" 
												value="<?= htmlspecialchars($info['fixation_method']) ?>" readonly>
										</td>
										<td>
											<input type="text" name="dry" class="form-control" 
												value="<?= htmlspecialchars($info['dry']) ?>" readonly>
										</td>
										<td>
											<input type="text" name="aspiration_materials" class="form-control" 
												value="<?= htmlspecialchars($info['aspiration_materials']) ?>" readonly>
										</td>
										<td>
											<input type="text" name="special_instructions" class="form-control" 
												value="<?= htmlspecialchars($info['special_instructions']) ?>" readonly>
										</td>
										
								</tr>
								<?php endforeach; ?>
							</tbody>
                		</table>

						<?php 
                   			$fixationAdditionalDetails = get_cyto_fixation_additional_details($cyto_id)
                		?>
						<table class="table table-bordered table-striped">
							<thead>
								<tr>
									<th>Dry Slide Description</th>
									<th>Additional Notes on Fixation</th>
									<th>Number of Needle Used</th>
									<th>Number of Syringe</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($fixationAdditionalDetails as $info): ?>
								<tr>
										<td>
											<textarea readonly class="form-control" name="dry_slides_description"><?= htmlspecialchars($info['dry_slides_description']) ?></textarea>
										</td>
										<td>
											<textarea readonly class="form-control" name="additional_notes_on_fixation"><?= htmlspecialchars($info['additional_notes_on_fixation']) ?></textarea>
										</td>
										
										<td>
											<input type="text" class="form-control" name="number_of_needle_used" value="<?= htmlspecialchars($info['number_of_needle_used']) ?>" readonly>
										</td>
										<td>
											<input type="text" class="form-control" name="number_of_syringe_used" value="<?= htmlspecialchars($info['number_of_syringe_used']) ?>" readonly>
										</td>
								</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
            
		</div>

		<div>
			<h4 style="text-align: center; margin-top: 15px;">Insert Recall Patient Information</h4>
			<form id="clinical-information-form" action="../Cyto/recall/recall_patient_create.php" method="post">
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
                                                        $storeDoctor = isset($_SESSION['doctor_name']) && $_SESSION['doctor_name'] === $doctor['doctor_username'] ? 'selected' : '';
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
                                                        $storedAssistant = isset($_SESSION['assistant']) && $_SESSION['assistant'] === $assistant['username'] ? 'selected' : '';
                                                    }
                                                    echo "<option value='{$assistant['username']}' $selected>{$assistant['username']}</option>";
                                                }
                                            ?>
                                            </select>
                                        </td>
                                        <td>
                                            <select id="cyto_station_type" name='cyto_station_type' class="form-control" required>
                                                <option value="">--Select a Station--</option>
                                                <option value="One" <?php echo isset($_SESSION['cyto_station_type']) && $_SESSION['cyto_station_type'] === 'One' ? 'selected' : ''; ?>>One</option>
                                                <option value="Two" <?php echo isset($_SESSION['cyto_station_type']) && $_SESSION['cyto_station_type'] === 'Two' ? 'selected' : ''; ?>>Two</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input id="lab_number" name="lab_number" class="form-control" type="text" value="<?php echo $LabNumber; ?>" readonly>
                                            <input type="hidden" id="status" name="status" value="done">
                                            <input type="hidden" id="created_user" name="created_user" value="<?php echo $loggedInUsername; ?>">
                                        </td>
                                                    
                                    </tr>
                                </tbody>
                            </table>
                        
                            <!-- Clinical Information -->
                            <!-- Reason for FNAC -->
                            <!-- <div class="form-group dropdown">
                                    <label for="chief-complain">Chief Complain:</label>
                                    <button onclick="toggleDropdown()"class="form-control" style="width: 1145px;" id="selected-value">Enter Complain</button>
                                    <div id="myDropdown" class="dropdown-content" style="display: none;">
                                        <input type="text" placeholder="Search.." id="search-reason" class="form-control mb-2" onkeyup="filterFunction()">
                                        <select id="reason-for-fnac" name="reason_for_fnac" class="form-control" size="4" onchange="selectOption()" required>
                                            <option value="Others">Others</option>
                                            <?php $chief_complain_list = get_cyto_chief_complain_list(); ?>
                                            <?php foreach ($chief_complain_list as $complain): ?>
                                                <option value="<?= htmlspecialchars($complain['chief_complain']); ?>">
                                                    <?= htmlspecialchars($complain['chief_complain']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <input type="text" id="other-reason" name="other_reason" class="form-control mt-2" placeholder="If Other, specify" style="display: none;">
                            </div> -->

                            <!-- Clinical History -->
                            <div class="form-group">
                                <label for="clinical-history">Additional Relevant Clinical History:</label>
                                <textarea id="clinical-history" name="clinical_history" class="form-control" rows="3" placeholder="Enter detailed clinical notes" required></textarea>
                            </div>
                            
                            <!-- Site of Aspiration -->
                            <div class="form-group">
                                <select id="onExaminationSelector">
                                    <option value=""><spain><b>Additional OnExamination</b>&nbsp;&nbsp;</spain> Select Format</option>
                                    <option value="format1">General Examination</option>
                                    <option value="format2">Default</option>
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
                                <textarea type="text" id="site-of-aspiration-editor" name="site-of-aspiration-editor" class="form-control" rows="10" placeholder="Enter on examination note"></textarea>
                            </div>

                            <!-- FNAC Collection Details -->
                            <?php
                                $slideBaseCode = preg_replace('/^[A-Za-z]{3}/', '', $LabNumber); 
                                $locationOptions = ['','Proper','Thyroid', 'Breast', 'Lymph node', 'Lung', 'Other'];
                            ?>
                            <h3>Aspiration Note:</h3>
                            <!-- Total Slides Prepared -->
                            <div class="form-group form-group-slide d-flex align-items-center">
                                <label for="location-input" class="mr-2">Location:</label> &nbsp; 
                                <input type="text" id="location-input" name="location_input" class="form-control mr-3" placeholder="Enter location (e.g., Proper)" > &nbsp; &nbsp; &nbsp;

                                <label for="slides-input" class="mr-2">Slide:</label> &nbsp; 
                                <input type="text" id="slides-input" name="slides_input" class="form-control mr-3" placeholder="Enter slide (e.g., 2+1)" > &nbsp; &nbsp; &nbsp; 

                                <label for="aspiration_materials-input" class="mr-2">Aspiration Materials:</label>&nbsp;
                                <input type="text" id="aspiration_materials-input" name="aspiration_materials_input" class="form-control mr-3" placeholder="Enter Aspiration Materials" > &nbsp; &nbsp; &nbsp;

                                <label for="special_instruction-input" class="mr-2">Special Instruction:</label>
                                <input type="text" id="special-instruction-input" name="special_instruction_input" class="form-control mr-3" placeholder="Enter Special Instruction"> &nbsp; &nbsp; &nbsp;
                                
                                <button type="button" class="btn btn-primary" id="populate-table">Generate slide</button>
                            </div>

                            <!-- Modal -->
                            <div class="modal fade" id="specialInstructionModal" tabindex="-1" role="dialog" aria-labelledby="specialInstructionModalLabel">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                            <h4 class="modal-title" id="specialInstructionModalLabel">Select Special Instructions</h4>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <input type="checkbox" id="centrifuge-checkbox" value="Centrifuge" />&nbsp;&nbsp;<b>Centrifuge</b>
                                                </div>
                                                <div class="col-md-3">
                                                    <input type="checkbox" id="zn-checkbox" value="Zn" />&nbsp;&nbsp;<b>Zn</b>
                                                </div>
                                                <div class="col-md-3">
                                                    <input type="checkbox" id="Gram-stain-checkbox" value="Gram" />&nbsp;&nbsp;<b>Gram</b>
                                                </div>
                                                <div class="col-md-3">
                                                    <input type="checkbox" id="Fite-Faraco-checkbox" value="Fite-Faraco" />&nbsp;&nbsp;<b>Fite-Faraco</b>
                                                </div>
                                                <div class="col-md-3">
                                                    <input type="checkbox" id="leishmain-checkbox" value="Leishmain" />&nbsp;&nbsp;<b>Leishmain</b>
                                                </div>
                                                <div class="col-md-3">
                                                    <input type="checkbox" id="pap-stain-checkbox" value="Pap Stain" />&nbsp;&nbsp;<b>Pap Stain</b>
                                                </div>
                                                <div class="col-md-3">
                                                    <input type="checkbox" id="pas-stain-checkbox" value="PAS" />&nbsp;&nbsp;<b>PAS</b>
                                                </div>
                                                <div class="col-md-3">
                                                    <input type="checkbox" id="Congo-Red-checkbox" value="Congo Red" />&nbsp;&nbsp;<b>Congo Red</b>
                                                </div>
                                                <div class="col-md-3">
                                                    <input type="checkbox" id="Grocott-Methenamine-checkbox" value="GMS" />&nbsp;&nbsp;<b>GMS</b>
                                                </div>
                                                <!-- Other Option -->
                                                <div class="col-md-3 mt-3">
                                                    <input
                                                        type="checkbox"
                                                        id="final-labInstructions-other-checkbox"
                                                        value="Other"
                                                    />&nbsp;&nbsp;<b>Other</b>
                                                </div>
                                            </div>
                                            <div id="final-Instructions-other-history" style="display: none;" class="mt-3">
                                                <label for="final-Instructions-other-history-text">Please specify:</label>
                                                <textarea
                                                    id="final-Instructions-other-history-text"
                                                    class="form-control"
                                                    rows="3"
                                                    placeholder="Specify other instruction"
                                                ></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                            <button type="button" class="btn btn-primary" data-dismiss="modal">Done</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Slide Fixation Details -->
                            <div class="form-group">
                                <label>Slide Fixation Details:</label>
                                <table class="table table-bordered" id="fixation-details-table">
                                    <thead>
                                        <tr>
                                            <th>RowId</th>
                                            <th>Slide Number</th>
                                            <th>Location</th>
                                            <th>Fixation Method</th>
                                            <th>Dry</th>
                                            <th>Aspiration Materials</th>
                                            <th>Special Instruction</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="fixation-details-body">
                                        <!-- Dynamic Rows -->
                                    </tbody>
                                </table>
                            </div>

                            <!-- clinical-impression -->
                            <div class="form-group row">
                                    <label for="clinical-impression" class="col-sm-3 col-form-label">
                                        Additional Clinical Impression:
                                    </label>
                                    <div>
                                    <textarea 
                                        required
                                        id="clinical-impression" 
                                        name="additional_clinical_impression" 
                                        class="form-control" 
                                        rows="5" 
                                        placeholder="Enter clinical impression here..."
                                        style="resize: none;"
                                    ></textarea>
                                    </div>
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

                            
                        
                            <!-- Number of Passes Performed -->
                            <div class="form-group">
                                <label for="number-of-needle">Number of Needle Used:</label>
                                <input type="number" id="number-of-needle" name="number_of_needle" class="form-control" min="0" required>
                            </div>
                            <div class="form-group">
                                <label for="number-of-syringe">Number of Syringe Used:</label>
                                <input type="number" id="number-of-syringe" name="number_of_syringe" class="form-control" min="0" required>
                            </div>

                            <!-- Modal Popup -->
                            <div id="exitModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="exitModalLabel" aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                        <div class="modal-header">
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                            </button>
                                            <h4 class="modal-title" id="exitModalLabel">Are you sure you want to leave?</h4>
                                        </div>
                                        <div class="modal-body">
                                            <p>You have unsaved changes. If you exit, your changes will not be saved.</p>
                                            <div class="checkbox">
                                            <label>
                                                <input type="checkbox" id="confirmExitCheckbox"> I confirm I want to exit
                                            </label>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" id="stayButton" class="btn btn-default" data-dismiss="modal">Stay</button>
                                            <button type="button" id="exitButton" class="btn btn-danger" disabled>Exit</button>
                                        </div>
                                        </div>
                                    </div>
                            </div>

                            <button id="saveButton" type="submit" class="btn btn-primary">Submit</button>
            </form>
		</div>

	</div>
</body>
</html>

<!-- update patient information -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Save button functionality
        document.querySelectorAll('.save-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const row = this.closest('tr');
                const rowid = row.getAttribute('data-rowid');
                const formData = new FormData();
                formData.append('rowid', rowid);

                row.querySelectorAll('.edit-field').forEach(input => {
                    formData.append(input.name, input.value);
                });

                fetch('../Cyto/update_patient.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    alert('Data updated successfully!');
                    // Update the displayed values and reset visibility
                    row.querySelectorAll('.text').forEach(el => {
                        const name = el.nextElementSibling.name;
                        el.textContent = formData.get(name);
                        el.classList.remove('d-none');
                    });
                    row.querySelector('.save-btn').classList.add('d-none');
                   
                })
                .catch(err => console.error('Error:', err));
            });
        });
    });

</script>


<!-- Doctor , Assistant and Station information -->
<script>
    window.onload = function() {
        const storeDoctor = sessionStorage.getItem('doctor_name');
        const storedAssistant = sessionStorage.getItem('assistant');
        const storedStation = sessionStorage.getItem('cyto_station_type');
        if (storedAssistant) {
            document.getElementById('assistant').value = storedAssistant;
        }
        if (storedStation) {
            document.getElementById('cyto_station_type').value = storedStation;
        }
        if(storeDoctor){
            document.getElementById('doctor_name').value = storeDoctor;
        }
    };

    
    document.getElementById('clinical-information-form').addEventListener('submit', function(event) {
        const selectedAssistant = document.getElementById('assistant').value;
        const selectedStation = document.getElementById('cyto_station_type').value;
        const selectedDoctor = document.getElementById('doctor_name').value;
        sessionStorage.setItem('assistant', selectedAssistant);
        sessionStorage.setItem('cyto_station_type', selectedStation);
        sessionStorage.setItem('doctor_name', selectedDoctor);
    });

</script>


<!-- Clinical Information -->
<!-- <script>
    // Show/hide "Others" text input based on the selection of dropdown options
    document.getElementById('reason-for-fnac').addEventListener('change', function() {
        var otherReasonField = document.getElementById('other-reason');
        if (this.value === 'Others') {
            otherReasonField.style.display = 'block';
            otherReasonField.style.border = '2px solid red';  // Red border when visible
        } else {
            otherReasonField.style.display = 'none';
            otherReasonField.style.border = '';  // Remove border when hidden
        }
    });

    // Trigger change event on page load to hide the "Others" inputs initially
    document.getElementById('reason-for-fnac').dispatchEvent(new Event('change'));
</script> -->

<!-- FNAC Fixation Details -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const locationOptions = [" ", "Proper", "Thyroid", "Beast", "Lymph Node", "Lung", "Other"];
        let locationCounter = { "Proper": 0 };
        let locationLetters = {};
        let rowCounter = 1;

        function updateSlideCode(row, location) {
            const slideBaseCode = "<?php echo $slideBaseCode; ?>".replace(/-/g, '');
            let slideCode;

            if (location.toLowerCase() === "proper") {
                const proCount = (locationCounter["Proper"] || 0) + 1;
                slideCode = `${slideBaseCode}FC-Pro-${proCount}`;
                locationCounter["Proper"] = proCount;
            } else {
                if (!locationLetters[location]) {
                    const letters = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
                    locationLetters[location] = letters[Object.keys(locationLetters).length];
                    locationCounter[location] = 1;
                }

                const locationLetter = locationLetters[location];
                const locationCount = locationCounter[location];
                slideCode = `${slideBaseCode}FC-${locationLetter}-${locationCount}`;
                locationCounter[location] = locationCount + 1;
            }

            row.querySelector('.slide-code').textContent = slideCode;
        }

        function addRow(location, isDry, aspirationMaterials, specialInstruction) {
            const tbody = document.getElementById('fixation-details-body');
            const newRow = document.createElement('tr');

            newRow.innerHTML = `
                <td>${rowCounter}</td>
                <td class="slide-code"></td>
                <td>${location}</td>
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
                <td>${aspirationMaterials}</td>
                <td> <textarea class="form-control special-instruction-input">${specialInstruction}</textarea></td>
                <td>
                    <button type="button" class="btn btn-danger remove-row">Remove</button>
                </td>
            `;

            const fixationDropdown = newRow.querySelector('.fixation-method-select');
            fixationDropdown.addEventListener('change', function () {
                if (this.value === "Other") {
                    newRow.querySelector('.other-fixation-input').style.display = 'block';
                } else {
                    newRow.querySelector('.other-fixation-input').style.display = 'none';
                }
            });

            newRow.querySelector('.remove-row').addEventListener('click', () => tbody.removeChild(newRow));
            tbody.appendChild(newRow);

            updateSlideCode(newRow, location);
            rowCounter++;
        }

        document.getElementById('populate-table').addEventListener('click', function () {
            const slidesInput = document.getElementById('slides-input').value.trim();
            const locationInput = document.getElementById('location-input').value.trim();
            const aspirationMaterials = document.getElementById('aspiration_materials-input').value.trim();
            const specialInstruction = document.getElementById('special-instruction-input').value.trim();

            // Validate required fields
            if (!slidesInput || !locationInput || !aspirationMaterials) {
                alert('Please fill in all required fields.');
                return;
            }

            // Parse slide input
            const [fixationSlides, drySlides] = slidesInput.split('+').map(Number);
            if (isNaN(fixationSlides) || isNaN(drySlides)) {
                alert('Invalid slide input format. Use "2+1" format.');
                return;
            }

            // Generate new rows
            for (let i = 0; i < fixationSlides; i++) addRow(locationInput, false, aspirationMaterials, specialInstruction);
            for (let i = 0; i < drySlides; i++) addRow(locationInput, true, aspirationMaterials, specialInstruction);

            // Clear form fields after generating the table
            document.getElementById('location-input').value = '';
            document.getElementById('slides-input').value = '';
            document.getElementById('aspiration_materials-input').value = '';
            document.getElementById('special-instruction-input').value = '';
        });


         // When the form is submitted, collect the fixation details and add them to the form
        document.getElementById("clinical-information-form").addEventListener("submit", function(event) {
        		event.preventDefault();

				const tbody = document.getElementById('fixation-details-body');
				const fixationData = [];
				const rows = tbody.querySelectorAll('tr');

				rows.forEach(row => {
					const slideNumber = row.querySelector('.slide-code').textContent;
					const location = row.querySelector('td:nth-child(3)').textContent;
					const fixationMethod = row.querySelector('.fixation-method-select').value;
					const isDry = row.querySelector('.dry-checkbox').checked ? 'Yes' : 'No';
					const aspirationMaterials = row.querySelector('td:nth-child(6)').textContent;
					const specialInstruction = row.querySelector('.special-instruction-input').value;

					fixationData.push({
						slideNumber,
						location,
						fixationMethod,
						isDry,
						aspirationMaterials,
						specialInstruction
					});
				});

				fixationData.forEach((data, index) => {
					const form = document.getElementById('clinical-information-form');

					for (let key in data) {
						const input = document.createElement('input');
						input.type = 'hidden';
						input.name = `fixation_data[${index}][${key}]`;
						input.value = data[key];
						form.appendChild(input);
					}
				});

				// Create hidden inputs for each piece of data and append them to the form
				fixationData.forEach((data, index) => {
						// Create hidden input for Slide Number
						let input = document.createElement('input');
						input.type = 'hidden';
						input.name = `fixation_data[${index}][slide_number]`;
						input.value = data.slideNumber;
						document.getElementById('clinical-information-form').appendChild(input);

						// Create hidden input for Location
						input = document.createElement('input');
						input.type = 'hidden';
						input.name = `fixation_data[${index}][location]`;
						input.value = data.location;
						document.getElementById('clinical-information-form').appendChild(input);

						// Create hidden input for Fixation Method
						input = document.createElement('input');
						input.type = 'hidden';
						input.name = `fixation_data[${index}][fixation_method]`;
						input.value = data.fixationMethod;
						document.getElementById('clinical-information-form').appendChild(input);

						// Create hidden input for Dry
						input = document.createElement('input');
						input.type = 'hidden';
						input.name = `fixation_data[${index}][dry]`;
						input.value = data.isDry;
						document.getElementById('clinical-information-form').appendChild(input);
					});

				this.submit();
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


<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Save button functionality
        document.querySelectorAll('.save-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const row = this.closest('tr');
                const rowid = row.getAttribute('data-rowid');
                const formData = new FormData();
                formData.append('rowid', rowid);

                row.querySelectorAll('.edit-field').forEach(input => {
                    formData.append(input.name, input.value);
                });

                fetch('../Cyto/update_patient.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    alert('Data updated successfully!');
                    // Update the displayed values and reset visibility
                    row.querySelectorAll('.text').forEach(el => {
                        const name = el.nextElementSibling.name;
                        el.textContent = formData.get(name);
                        el.classList.remove('d-none');
                    });
                    row.querySelector('.save-btn').classList.add('d-none');
                   
                })
                .catch(err => console.error('Error:', err));
            });
        });
    });

</script>

<script>
    $(document).ready(function () {
            const $inputField = $("#special-instruction-input");
            const $checkboxes = $("#specialInstructionModal input[type='checkbox']");
            const $otherCheckbox = $("#final-labInstructions-other-checkbox");
            const $otherTextarea = $("#final-Instructions-other-history");
            const $otherTextInput = $("#final-Instructions-other-history-text");

            const updateInputField = () => {
                const selectedValues = $checkboxes
                    .filter(":checked")
                    .map(function () {
                        return this.value !== "Other" ? this.value : null;
                    })
                    .get();

                if ($otherCheckbox.is(":checked") && $otherTextInput.val().trim() !== "") {
                    selectedValues.push($otherTextInput.val().trim());
                }

                $inputField.val(selectedValues.join(", "));
            };

            $checkboxes.on("change", function () {
                if (this === $otherCheckbox[0]) {
                    $otherTextarea.toggle($otherCheckbox.is(":checked"));
                }
                updateInputField();
            });

            $otherTextInput.on("input", updateInputField);

            $inputField.on("click", function () {
                $("#specialInstructionModal").modal("show");
            });
    });
</script>



<!-- On Examination -->
<script>
    document.getElementById('onExaminationSelector').addEventListener('change', function () {
    const editor = document.getElementById('site-of-aspiration-editor'); // Corrected ID
    const format = this.value;

    // Predefined templates
    const templates = {
        format1: `Location:
Side: 
Level: 
Appearance:
     - Consistency: 
     - Surface: 
     - Mobility: 
     - Tenderness: 
Number of Swelling: 
Size:`,

        format2: `Location:
Side: Right/Left
Level: 

Appearance:
     - Consistency: Hard/Soft/Firm/Rubbery/Matted
     - Surface: Regular/ Smooth/Irregular/Ulcerated/Nodular
     - Mobility: Mobile/ Nonmobile/ Moves With Deglutition
     - Tenderness: Tender/ Nontender

Number of Swelling: 
Size : [__cm to __cm]`,
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
    };

    if (format && templates[format]) {
        editor.value = templates[format]; // Update textarea value
    } else {
        editor.value = ''; // Clear textarea if no format is selected
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