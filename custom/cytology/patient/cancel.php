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
$processedLabNumber = substr($LabNumber, 3);

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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../../grossmodule/bootstrap-3.4.1-dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../../grossmodule/bootstrap-3.4.1-dist/js/bootstrap.min.js"></script>
    <title>Cancel Confirmation</title>
</head>
<body>
    <div class="container">
        <h4>Please confirm if you want to cancel this action. You can either exit this page or proceed with cancellation by providing additional information.</h4>
        <br>
        <!-- Button trigger modal -->
        <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#cancelModal">
            Proceed
        </button>

        <!-- Modal -->
        <div class="modal fade" id="cancelModal" tabindex="-1" role="dialog" aria-labelledby="cancelModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="cancelModalLabel">Confirm Cancellation</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        Are you sure you want to cancel this action?
                    </div>
                    <div class="modal-footer">
                        <!-- <button type="button" class="btn btn-secondary" onclick="history.back()">Exit</button> -->
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='index.php?LabNumber=<?php echo $LabNumber; ?>';">Exit</button>
                        <button type="button" class="btn btn-primary" id="confirmCancel">Confirm</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form (hidden initially) -->
        <div id="cancelFormContainer" style="display: none; margin-top: 20px;">
            <form action="../Cyto/process_cancel.php" method="POST">
                <div class="form-group">
                    <label for="note">Note</label>
                    <textarea class="form-control" id="note" name="note_public" rows="3" required></textarea>
                </div>
                <input type="hidden" name="fk_status" value="-1">
                <input type="hidden" name="username" value="<?php echo htmlspecialchars($loggedInUsername); ?>">
                <input type="hidden" name="ref" value="<?php echo htmlspecialchars($processedLabNumber); ?>">
                <button type="submit" class="btn btn-success">Submit</button>
            </form>
        </div>
    </div>

    <script>
        // Show the form when "Confirm" is clicked
        document.getElementById('confirmCancel').addEventListener('click', function () {
            // Hide the modal
            $('#cancelModal').modal('hide');
            // Show the form
            document.getElementById('cancelFormContainer').style.display = 'block';
        });
    </script>
</body>
</html>