<?php 

include ("../connection.php");
include ("../function.php");
include('../../common_function.php');

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
$LabNumber = "HPL" . $_GET['LabNumber'];
$lab_number =  $_GET['LabNumber'];

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Number Search</title>
    <!-- Add Bootstrap  -->
    <link href="../../bootstrap-3.4.1-dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        
        <div class="form-group">
            <label for="labNumber">Lab Number</label>
            <input type="text" class="form-control" id="labNumber" readonly value=<?php echo htmlspecialchars($LabNumber)?>>
        </div>
        <?php 
            echo '
            <form id="duplicateReportForm" action="../../save_duplicate_report_data.php" method="POST">
                <input type="hidden" name="lab_number" value="' . htmlspecialchars($lab_number, ENT_QUOTES, 'UTF-8') . '">
                <input type="hidden" name="user_id" value="' . htmlspecialchars($loggedInUserId, ENT_QUOTES, 'UTF-8') . '">
            </form>
        ';
        
        echo '
            <button class="btn btn-primary text-white" onclick="submitAndRedirect()">Duplicate Report</button>
        ';
        
        echo '
            <script>
                function submitAndRedirect() {
                    var formData = new FormData(document.getElementById("duplicateReportForm"));
        
                    fetch("../../save_duplicate_report_data.php", {
                        method: "POST",
                        body: formData
                    })
                    .then(response => response.text()) // Handle response if needed
                    .then(() => {
                        window.open("index.php?lab_number=' . htmlspecialchars($LabNumber, ENT_QUOTES, 'UTF-8') . '&username=' . urlencode($loggedInUsername) . '", "_blank");
                    })
                    .catch(error => console.error("Error submitting form:", error));
                }
            </script>
        ';
        ?>
        
        <?php
            echo "<h2 class='text-center'>Doctor's Signature</h2>";

            // Fetch details and finalized information
            $details = get_duplicate_report_doctor_assisted_by_signature_details($LabNumber);
            $finialized_by = get_duplicate_report_doctor_finalized_by_signature_details($LabNumber);
            $information = get_doctor_degination_details();

            if (!empty($details)) {
                foreach ($details as $list) {
                    echo '<div class="panel panel-default">';
                    echo '<div class="panel-heading"><h3 class="panel-title">Assisted By</h3></div>';
                    echo '<div class="panel-body">';
                    echo '<form method="post" action="duplicate_doctor_signature_update.php" class="form-horizontal">';

                    // Doctor Username Select Field
                    echo '<div class="form-group">';
                    echo '<label for="doctor_username" class="col-sm-2 control-label">Doctor</label>';
                    echo '<div class="col-sm-10">';
                    echo '<select id="doctor_username" name="doctor_username" class="form-control">';
                    echo '<option value=""></option>';
                    foreach ($information as $list_info) {
                        $selected = ($list_info['username'] == $list['username']) ? 'selected' : '';
                        echo "<option value='{$list_info['username']}' $selected>{$list_info['username']}</option>";
                    }           
                    echo '</select>';
                    echo '</div>';
                    echo '</div>';

                    // Hidden Inputs
                    echo '<input type="hidden" name="lab_number" value="' . $LabNumber . '" readonly>';
                    echo '<input type="hidden" name="created_user" value="' . htmlspecialchars($loggedInUsername) . '">';
                    echo '<input type="hidden" name="rowid" value="' . htmlspecialchars($list['row_id']) . '">'; // Hidden input to store row_id for update

                    // Submit Button
                    echo '<div class="form-group">';
                    echo '<div class="col-sm-offset-2 col-sm-10">';
                    echo '<button type="submit" class="btn btn-primary">Update</button>';
                    echo '</div>';
                    echo '</div>';

                    echo '</form>';
                    echo '</div>';
                    echo '</div>';
                }
            } else {
                // For new creation
                echo '<div class="panel panel-default">';
                echo '<div class="panel-heading"><h3 class="panel-title">Assisted By</h3></div>';
                echo '<div class="panel-body">';
                echo '<form method="post" action="duplicate_doctor_signature_create.php" class="form-horizontal">';

                // Doctor Username Select Field
                echo '<div class="form-group">';
                echo '<label for="doctor_username" class="col-sm-2 control-label">Doctor</label>';
                echo '<div class="col-sm-10">';
                echo '<select id="doctor_username" name="doctor_username" class="form-control">';
                echo '<option value=""></option>';
                foreach ($information as $list) {
                    $selected = ($list['username'] == $loggedInUsername) ? 'selected' : '';
                    echo "<option value='{$list['username']}' $selected>{$list['username']}</option>";
                }        
                echo '</select>';
                echo '</div>';
                echo '</div>';

                // Hidden Inputs
                echo '<input type="hidden" name="lab_number" value="' . $LabNumber . '" readonly>';
                echo '<input type="hidden" name="created_user" value="' . htmlspecialchars($loggedInUsername) . '">';

                // Submit Button
                echo '<div class="form-group">';
                echo '<div class="col-sm-offset-2 col-sm-10">';
                echo '<button type="submit" class="btn btn-primary">Save</button>';
                echo '</div>';
                echo '</div>';

                echo '</form>';
                echo '</div>';
                echo '</div>';
            }

            if (!empty($finialized_by)) {
                foreach ($finialized_by as $list) {
                    echo '<div class="panel panel-default">';
                    echo '<div class="panel-heading"><h3 class="panel-title">Finalized By</h3></div>';
                    echo '<div class="panel-body">';
                    echo '<form method="post" action="duplicate_doctor_signature_finalized_update.php" class="form-horizontal">';

                    // Doctor Username Select Field
                    echo '<div class="form-group">';
                    echo '<label for="doctor_username" class="col-sm-2 control-label">Doctor</label>';
                    echo '<div class="col-sm-10">';
                    echo '<select id="doctor_username" name="doctor_username" class="form-control">';
                    echo '<option value=""></option>';
                    foreach ($information as $list_info) {
                        $selected = ($list_info['username'] == $list['username']) ? 'selected' : '';
                        echo "<option value='{$list_info['username']}' $selected>{$list_info['username']}</option>";
                    }     
                    echo '</select>';
                    echo '</div>';
                    echo '</div>';

                    // Hidden Inputs
                    echo '<input type="hidden" name="lab_number" value="' . $LabNumber . '" readonly>';
                    echo '<input type="hidden" name="created_user" value="' . htmlspecialchars($loggedInUsername) . '">';
                    echo '<input type="hidden" name="rowid" value="' . htmlspecialchars($list['row_id']) . '">'; // Hidden input to store row_id for update

                    // Submit Button
                    echo '<div class="form-group">';
                    echo '<div class="col-sm-offset-2 col-sm-10">';
                    echo '<button type="submit" class="btn btn-primary">Update</button>';
                    echo '</div>';
                    echo '</div>';

                    echo '</form>';
                    echo '</div>';
                    echo '</div>';
                }
            } else {
                // For new creation
                echo '<div class="panel panel-default">';
                echo '<div class="panel-heading"><h3 class="panel-title">Finalized By</h3></div>';
                echo '<div class="panel-body">';
                echo '<form method="post" action="duplicate_doctor_signature_finalized_create.php" class="form-horizontal">';

                // Doctor Username Select Field
                echo '<div class="form-group">';
                echo '<label for="doctor_username" class="col-sm-2 control-label">Doctor</label>';
                echo '<div class="col-sm-10">';
                echo '<select id="doctor_username" name="doctor_username" class="form-control">';
                echo '<option value=""></option>';
                foreach ($information as $list) {
                    $selected = ($list['username'] == $loggedInUsername) ? 'selected' : '';
                    echo "<option value='{$list['username']}' $selected>{$list['username']}</option>";
                }        
                echo '</select>';
                echo '</div>';
                echo '</div>';

                // Hidden Inputs
                echo '<input type="hidden" name="lab_number" value="' . $LabNumber . '" readonly>';
                echo '<input type="hidden" name="created_user" value="' . htmlspecialchars($loggedInUsername) . '">';
                echo '<input type="hidden" name="status" value="Finalized">';

                // Submit Button
                echo '<div class="form-group">';
                echo '<div class="col-sm-offset-2 col-sm-10">';
                echo '<button type="submit" class="btn btn-primary">Save</button>';
                echo '</div>';
                echo '</div>';

                echo '</form>';
                echo '</div>';
                echo '</div>';
            }
        ?>
     
        
    </div>

    <!-- Add Bootstrap and jQuery JS -->
    <script src="../../jquery/jquery.min.js"></script>
    <script src="../../bootstrap-3.4.1-dist/js/bootstrap.min.js"></script>
   
</body>
</html>