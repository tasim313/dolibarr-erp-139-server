<?php 

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
$langs->loadLangs(array("CytoInformation"));

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

llxHeader("", $langs->trans("CytoInformationArea"));

print load_fiche_titre($langs->trans(""), '', 'cytology.png@cytology');

$host = $_SERVER['HTTP_HOST'];
$homeUrl = "http://" . $host . "/custom/cytology/cytologyindex.php";

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="bootstrap-3.4.1-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="bootstrap-3.4.1-dist/js/bootstrap.min.js">
</head>
<body>
    <div class="container">
          <h3>Cyto Pathology</h3>
          <ul class="nav nav-tabs">
               <li class="active"><a href="index.php">Home</a></li>
               <li><a href="study.php">Study/History</a></li>
               <li><a href="recall.php">Recall Instructions</a></li>
               <li><a href="patient_report.php">Patient Report</a></li>
               <li><a href="report_ready.php">Report Ready</a></li>
               <li><a href="view/doctor_instruction.php">Doctor's Instructions</a></li>
               <li><a href="view/cancel_information.php">Cancel Information</a></li>
               <li><a href="view/postpone_information.php">Postpone</a></li>
               
          </ul>
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