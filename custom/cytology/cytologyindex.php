<?php
include('common_function.php');

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

print load_fiche_titre($langs->trans("CytologyArea"), '', 'cytology.png@cytology');

?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<!-- Import the JavaScript file -->
	<link href="../grossmodule/bootstrap-3.4.1-dist/css/bootstrap.min.css" rel="stylesheet">
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
  	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
</head>
<body>
	
	<div class="container">
    	<h3>Cytopathology</h3>
        <ul class="nav nav-tabs">
			<li class="active"><a data-toggle="tab" href="#regular">Regular</a></li>
			<li><a data-toggle="tab" href="#recall">Recall</a></li>
			
        </ul>

		<div class="tab-content">
			<div id="regular" class="tab-pane fade in active">
				<div id="form-box">
    				<h3>Please scan the FNA Lab Number to proceed</h3>
    				<p>After scanning the Lab Number:</p>
					<ul>
						<li>If it is a new Lab Number, you will be directed to the <strong>Patient Registration</strong> page.</li>
						<li>If the Lab Number already exists, you will be directed to the <strong>Patient Edit</strong> page.</li>
					</ul>
					<div id="input-group">
						<input id="input-field" placeholder="Scan Lab number" type="text" onkeypress="handleLabNumberScan(event)" autofocus>
						<label id="input-label">Enter or Scan the Lab Number</label>
						<!-- Error message container -->
						<div id="error-message" style="color: red; font-size: 14px; margin-top: 5px; display: none;"></div>
					</div>
				</div>
			</div>
			<div id="recall" class="tab-pane fade">
					<div id="form-box-recall">
						<h3>Please scan the <strong>Recall</strong> FNA Lab Number to proceed</h3>
						<div id="input-group-recall">
							<input id="input-field-recall" placeholder="Scan Lab number" type="text" onkeypress="handleRecallLabNumberScan(event)" autofocus>
							<label id="input-label-recall">Enter or Scan the Lab Number</label>
							<!-- Error message container -->
							<div id="error-message-recall" style="color: red; font-size: 14px; margin-top: 5px; display: none;"></div>
						</div>
					</div>
			</div>
		</div>
		

	</div>

	<script>
        // Embed the lab numbers from PHP as JavaScript variables
        const labList = <?php
            $labNumbers = get_cyto_labnumber_list();
            echo json_encode(array_column($labNumbers, 'lab_number'));
        ?>;

        const recallList = <?php
            $recallNumbers = get_cyto_recall_list();
            echo json_encode(array_column($recallNumbers, 'lab_number'));
        ?>;

		const completeCytoList = <?php
            $completeLabNumbers = get_cyto_complete_labnumber_list();
            echo json_encode(array_column($completeLabNumbers, 'lab_number'));
        ?>;

        // Function to handle scanning of Lab Number
		function handleLabNumberScan(event) {
			if (event.key === "Enter") {
				const inputField = document.getElementById("input-field");
				const labNumber = inputField.value.trim();
				const errorMessage = document.getElementById("error-message");

				// Clear previous error message
				errorMessage.style.display = "none";
				errorMessage.textContent = "";

				if (!labNumber) {
					errorMessage.style.display = "block";
					errorMessage.textContent = "Please scan or enter a valid Lab Number.";
					return;
				}

				// Prepend "FNA" if not already included
				let formattedLabNumber = labNumber;
				if (!labNumber.startsWith("FNA")) {
					formattedLabNumber = `FNA${labNumber}`;
					inputField.value = formattedLabNumber; // Update the input field
				}

				// Check against lab lists (mocked here for demonstration)
				if (labList.includes(formattedLabNumber)) {
					window.location.href = `patient/index.php?LabNumber=${encodeURIComponent(formattedLabNumber)}`;
				}else if (completeCytoList.includes(formattedLabNumber)) {
					window.location.href = `patient/patient_info_update.php?LabNumber=${encodeURIComponent(formattedLabNumber)}`;
				} 
				else {
					errorMessage.style.display = "block";
					errorMessage.textContent = "Lab Number not found in the system. Please check and try again.";
				}
			}
    	}

		function handleRecallLabNumberScan(event) {
			if (event.key === "Enter") {
				const inputRecallField = document.getElementById("input-field-recall");
				const labRecallNumber = inputRecallField.value.trim();
				const errorRecallMessage = document.getElementById("error-message-recall");

				// Clear previous error message
				errorRecallMessage.style.display = "none";
				errorRecallMessage.textContent = "";

				if (!labRecallNumber) {
					errorRecallMessage.style.display = "block";
					errorRecallMessage.textContent = "Please scan or enter a valid Lab Number.";
					return;
				}

				// Check against lab lists (mocked here for demonstration)
				if (recallList.includes(labRecallNumber)) {
					window.location.href = `patient/repeat.php?LabNumber=${encodeURIComponent(labRecallNumber)}`;
				} 
				else {
					errorRecallMessage.style.display = "block";
					errorRecallMessage.textContent = "Lab Number not found in the system. Please check and try again.";
				}
			}
    	}

        // Add event listener to the input field
        document.getElementById("input-field").addEventListener("keypress", handleLabNumberScan);
		document.getElementById("input-field-recall").addEventListener("keypress", handleLabNumberScan);
    </script>

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
