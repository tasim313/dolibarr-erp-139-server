<?php
include("../connection.php");
include('../../../grossmodule/gross_common_function.php');
include('../../../transcription/common_function.php');
include('../../../transcription/FNA/function.php');
include('../../common_function.php');


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

$LabNumber = $_GET['labNumber'];


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
$reportUrl = "http://" . $host . "/custom/transcription/FNA/fna_report.php?LabNumber=" . urlencode($LabNumber) . "&username=" . urlencode($loggedInUsername);


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../bootstrap-3.4.1-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../bootstrap-3.4.1-dist/js/bootstrap.min.js">
</head>
<body>
<div class="container">
        <h3>Cyto Lab WorkFlow</h3>
            <ul class="nav nav-tabs">
                <li><a href="../index.php">Home</a></li>
                <li class="active"><a href="./mfc.php">MFC</a></li>
                <li><a href="./special_instruction.php" class="tab">Special Instructions</a></li>
                <li><a href="./slide_prepared.php" class="tab">Slide Prepared</a></li>
                <li><a href="./new_slide_centrifuge.php" class="tab">New Slide (Centrifuge)</a></li>
                <li><a href="./sbo.php">SBO(Slide Block Order)</a></li>
                <li><a href="../recall.php">Re-Call</a></li>
                <li><a href="./doctor_instruction.php">Doctor's Instructions</a></li>
                <li><a href="./cancel_information.php">Cancel Information</a></li>
                <li><a href="./postpone_information.php">Postpone</a></li>
            </ul>
        <br>

        <br>
    <h4>MFC</h4>

        <div id="input-group">
			<label id="input-label">Enter or Scan the Lab Number : </label>
			<input id="input-field" placeholder="Scan Lab number" type="text" onkeypress="handleLabNumberScan(event)" autofocus>
			<!-- Error message container -->
			<div id="error-message" style="color: red; font-size: 14px; margin-top: 5px; display: none;"></div>
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
                    if (!labNumber.startsWith('MFC')) {
                        labNumber = 'MFC' + labNumber;
                    }
                    // Clear error message if input is valid
                    document.getElementById('error-message').style.display = 'none';
                    // Redirect to the mfc_create.php page with the LabNumber as a query parameter
                    window.location.href = `./mfc_create.php?LabNumber=${encodeURIComponent(labNumber)}`;
                }
            }
        </script>
</div>
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