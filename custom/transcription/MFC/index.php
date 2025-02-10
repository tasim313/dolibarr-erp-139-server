<?php 

include ("../connection.php");
include ("../../grossmodule/gross_common_function.php");
include ("../../cytology/common_function.php");


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
		<?php 
            $mfc_list = get_mfc_labnumber_list(); 
            $lab_numbers = json_encode(array_column($mfc_list, 'lab_number')); // Extract lab numbers as an array
        ?>
		<div class="container">
			<div class="row">
				<div id="input-group" style="margin-top:45px;" >
					<label id="input-label" >Enter or Scan the Lab Number : </label>
					<input id="input-field" placeholder="Scan Lab number" type="text" onkeypress="handleLabNumberScan(event)" autofocus>
					<!-- Error message container -->
					<div id="error-message" style="color: red; font-size: 14px; margin-top: 5px; display: none;"></div>
				</div>
			</div>
		</div>
		
       

        <script>
            // Parse PHP array into JavaScript
            const mfcLabNumbers = <?php echo $lab_numbers; ?>; 

            function handleLabNumberScan(event) {
                if (event.key === 'Enter') {
                    const inputField = document.getElementById('input-field');
                    let labNumber = inputField.value.trim(); 

                    // Validate input
                    if (labNumber === '') {
                        showError('Lab number cannot be empty!');
                        return;
                    }

                    // Add prefix 'MFC' if not already present
                    if (!labNumber.startsWith('MFC')) {
                        labNumber = 'MFC' + labNumber;
                    }

                    // Check if the lab number exists in the list
                    if (mfcLabNumbers.includes(labNumber)) {
                        // Redirect to mfc_create.php
                        window.location.href = `./mfc_create.php?LabNumber=${encodeURIComponent(labNumber)}`;
                    } else {
                        showError('This lab number is not MFC. Please insert a valid MFC lab number.');
                    }
                }
            }

            function showError(message) {
                const errorMessage = document.getElementById('error-message');
                errorMessage.textContent = message;
                errorMessage.style.display = 'block';
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