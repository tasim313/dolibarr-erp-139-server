<?php
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

		<div id="input-group">
			<label id="input-label">Enter or Scan the Lab Number : </label>
			<input id="input-field" placeholder="Scan Lab number" type="text" onkeypress="handleLabNumberScan(event)" autofocus>
			<!-- Error message container -->
			<div id="error-message" style="color: red; font-size: 14px; margin-top: 5px; display: none;"></div>
		</div>
    	
	</div>

	<script>
        function handleLabNumberScan(event) {
            // Check if the Enter key is pressed
            if (event.key === 'Enter') {
                const inputField = document.getElementById('input-field');
                let labNumber = inputField.value.trim(); // Get the scanned lab number
                
                // Validate input
                if (labNumber === '') {
                    const errorMessage = document.getElementById('error-message');
                    errorMessage.textContent = 'Lab number cannot be empty!';
                    errorMessage.style.display = 'block';
                    return;
                }

                // Add prefix 'FNA' if it's not already present
                if (!labNumber.startsWith('FNA')) {
                    labNumber = 'FNA' + labNumber;
                }

                // Clear error message if input is valid
                document.getElementById('error-message').style.display = 'none';

                // Redirect to the postponed.php page with the LabNumber as a query parameter
                window.location.href = `./postponed.php?LabNumber=${encodeURIComponent(labNumber)}`;
            }
        }
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