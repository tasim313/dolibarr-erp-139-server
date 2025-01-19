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

print load_fiche_titre($langs->trans(""), '', '');

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
            <div id="input-group-recall">
                <label id="input-label-recall">Enter or Scan the Lab Number: </label>
				<input id="input-field-recall" placeholder="Scan Lab number" type="text" onkeypress="handleRecallLabNumberScan(event)" autofocus>
				<!-- Error message container -->
				<div id="error-message-recall" style="color: red; font-size: 14px; margin-top: 5px; display: none;"></div>
			</div>
        
	</div>

	<script>
        // Embed the lab numbers from PHP as JavaScript variables
        const recallList = <?php
            $recallNumbers = get_cyto_recall_list();
            echo json_encode(array_column($recallNumbers, 'lab_number'));
        ?>;


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
