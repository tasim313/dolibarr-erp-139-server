<?php 

include ("connection.php");
include ("preliminary_report_function.php");

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


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Number Search</title>
    <!-- Add Bootstrap  -->
    <link href="../../grossmodule/bootstrap-3.4.1-dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h3>Preliminary Report</h3>
        <!-- Search Field -->
        <div class="form-group">
            <label for="labNumber">Enter Lab Number</label>
            <input type="text" class="form-control" id="labNumber" placeholder="Enter Lab Number">
        </div>
        <button class="btn btn-primary" id="searchBtn">Submit</button>
        
        <!-- Message for showing results -->
        <div id="message" class="mt-4"></div>
    </div>

    <!-- Add Bootstrap and jQuery JS -->
    <script src="../../grossmodule/jquery/jquery.min.js"></script>
    <script src="../../grossmodule/bootstrap-3.4.1-dist/js/bootstrap.min.js"></script>
    <script>
            // Fetch the lab numbers for HPL and IHC test types
            const preliminaryLabNumbers = <?php echo json_encode(get_preliminary_report_labnumber_list()); ?>;

            // Create separate lists for HPL and IHC
            const hplLabNumbers = [];
            const ihcLabNumbers = [];

            preliminaryLabNumbers.forEach(item => {
                if (item.test_type === 'HPL') {
                    hplLabNumbers.push(item.lab_number.trim());
                } else if (item.test_type === 'IHC') {
                    ihcLabNumbers.push(item.lab_number.trim());
                }
            });

            document.getElementById('searchBtn').addEventListener('click', function () {
                const labNumber = document.getElementById('labNumber').value.trim().toLowerCase();
                
                if (labNumber) {
                    if (hplLabNumbers.includes(labNumber)) {
                        window.location.href = 'hpl/index.php?LabNumber=' + encodeURIComponent("HPL" + labNumber);
                    } else if (ihcLabNumbers.includes(labNumber)) {
                        window.location.href = 'ihc/index.php?LabNumber=' + encodeURIComponent(labNumber);
                    } else {
                        document.getElementById('message').innerHTML = 
                            `<div class="alert alert-danger">This lab number does not match. Please enter a valid lab number.</div>`;
                    }
                } else {
                    document.getElementById('message').innerHTML = 
                        `<div class="alert alert-danger">Please enter a lab number.</div>`;
                }
            });
    </script>

</body>
</html>