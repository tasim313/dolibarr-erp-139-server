<?php 

include ("../connection.php");
include ("../../grossmodule/gross_common_function.php");
include ("../../cytology/common_function.php");
include ("function.php");

$res = 0;

if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}

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

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';


$title = $langs->trans("Transcription");
$help_url = '';
llxHeader('', $title, $help_url);
$loggedInUserId = $user->id;

$loggedInUsername = $user->login;

$userGroupNames = getUserGroupNames($loggedInUserId);

$hasConsultants = false;
$hasConsultants = false;


foreach ($userGroupNames as $group) {
    if ($group['group'] === 'Consultants') {
        $hasConsultants = true;
    }
    if ($group['group'] === 'Transcription') {
        $hasTranscriptionist = true;
    }
}

// Access control using switch statement
switch (true) {

	case $hasConsultants:
		// Doctor has access, continue with the page content...
		break;

    case $hasTranscriptionist:
        // Transcription  has access, continue with the page content...
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
    <title>Document</title>
    <link href="../../grossmodule/bootstrap-3.4.1-dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
    	<h3>Fine-Needle Aspiration (FNA)</h3>
        <ul class="nav nav-tabs">
          
        </ul>
		<div id="form-box">
    			<h3>Please scan the FNA Lab Number to proceed</h3>
    			<p>After scanning the Lab Number:</p>
    		<ul>
        		<li>If it is a new Lab Number, you will be directed to the <strong>Microscopic Description New Entry</strong> page.</li>
        		<li>If the Lab Number already exists, you will be directed to the <strong>Microscopic Description Update</strong> page.</li>
    		</ul>
			<div id="input-group">
				<input id="input-field" placeholder="Scan Lab number" type="text" onkeypress="handleLabNumberScan(event)" autofocus>
				<label id="input-label">Enter or Scan the Lab Number</label>
				<!-- Error message container -->
				<div id="error-message" style="color: red; font-size: 14px; margin-top: 5px; display: none;"></div>
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
				} else if (recallList.includes(formattedLabNumber)) {
					window.location.href = `patient/recall.php?LabNumber=${encodeURIComponent(formattedLabNumber)}`;
				} else if (completeCytoList.includes(formattedLabNumber)) {
					window.location.href = `create.php?LabNumber=${encodeURIComponent(formattedLabNumber)}`;
				} 
				else {
					errorMessage.style.display = "block";
					errorMessage.textContent = "Lab Number not found in the system. Please check and try again.";
				}
			}
    	}

        // Add event listener to the input field
        document.getElementById("input-field").addEventListener("keypress", handleLabNumberScan);
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