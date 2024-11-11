<?php 

include ("../connection.php");
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
        <h3>Lab Number Search</h3>
        <!-- Search Field -->
        <div class="form-group">
            <label for="labNumber">Enter Lab Number</label>
            <input type="text" class="form-control" id="labNumber" placeholder="Enter Lab Number">
        </div>
        <button class="btn btn-primary" id="searchBtn">Search</button>
        
        <!-- Message for showing results -->
        <div id="message" class="mt-4"></div>
    </div>

    <!-- Add Bootstrap and jQuery JS -->
    <script src="../../grossmodule/jquery/jquery.min.js"></script>
    <script src="../../grossmodule/bootstrap-3.4.1-dist/js/bootstrap.min.js"></script>
    <script>
        // JavaScript to handle the search logic
        document.getElementById('searchBtn').addEventListener('click', function() {
            const labNumber = document.getElementById('labNumber').value;
            
            if (labNumber) {
                // Use AJAX to call PHP function and check lab number
                $.ajax({
                    url: 'diagonsis_micro_complete_by_lab.php', // Replace with the PHP file where the function is defined
                    type: 'GET',
                    data: { lab_number: labNumber },
                    success: function(response) {
                        if (response === 'OK') {
                            document.getElementById('message').innerHTML = `<div class="alert alert-success">Lab number ${labNumber} is ready for the next step.</div>`;
                            // Proceed to next step (you can redirect or perform other actions)
                        } else {
                            document.getElementById('message').innerHTML = `<div class="alert alert-warning">Please complete the micro description and diagnosis for lab number ${labNumber} before proceeding.</div>`;
                        }
                    },
                    error: function() {
                        document.getElementById('message').innerHTML = `<div class="alert alert-danger">Error processing your request. Please try again.</div>`;
                    }
                });
            } else {
                document.getElementById('message').innerHTML = `<div class="alert alert-danger">Please enter a lab number.</div>`;
            }
        });
    </script>
</body>
</html>
