<?php 
include('connection.php');
include('common_function.php');
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
dol_include_once('/grossmodule/class/gross.class.php');
dol_include_once('/grossmodule/lib/grossmodule_gross.lib.php');

$title = $langs->trans("Transcription");
$help_url = '';
llxHeader('', $title, $help_url);
$lab_number = $_GET['lab_number'];
 

print('<style>
* {
    box-sizing: border-box;
}

.content {
    margin-left: 200px;
    padding: 15px;
}

input[type=text], select, textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #ccc;
    border-radius: 4px;
    resize: vertical;
}

label {
    padding: 12px 12px 12px 0;
    display: inline-block;
}

input[type=submit] {
    background-color: rgb(118, 145, 225);
    color: white;
    padding: 12px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    float: right;
    transition: box-shadow 0.3s ease;
}

input[type=submit]:hover {
    background-color: rgb(118, 145, 225);
    box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.5);
}

.container {
    border-radius: 5px;
    background-color: #f2f2f2;
    padding: 20px;
}

.col-25 {
    float: left;
    width: 25%;
    margin-top: 6px;
}

.col-75 {
    float: left;
    width: 75%;
    margin-top: 6px;
}

.row::after {
    content: "";
    display: table;
    clear: both;
}

#doctor_username:required {
    box-shadow: none; 
    border: 1px solid black;
}

#doctor_username {
    font-size: 15px; 
    font-weight: bold;
    color: black;
}

#doctor_username option {
    font-size: 18px; 
    font-weight: bold; 
    color: black;
}

/* Custom styles for dropdown options */
select {
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    background-image: url("data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path fill="currentColor" d="M7 10l5 5 5-5z"/></svg>");
    background-repeat: no-repeat;
    background-position-x: 100%;
    background-position-y: 50%;
    padding-right: 32px;
    border: 1px solid #ccc;
    border-radius: 4px;
    padding: 12px;
}

/* Hover effect for dropdown options */
select:hover {
    background-color: #f0f0f0;
}

@media screen and (max-width: 600px) {
    .col-25, .col-75, input[type=submit] {
        width: 100%;
        margin-top: 0;
    }
}
</style>'); 



$loggedInUsername = $user->login;
$details = get_doctor_assisted_by_signature_details($lab_number);
$finialized_by = get_doctor_finalized_by_signature_details($lab_number);
$information = get_doctor_degination_details();
    
    if (!empty($details)) {
        foreach($details as $list){
            print '<div class="content">';
            print('<p>Assisted By </p>');
            print('<form method="post" action="doctor_signature_update.php">');
            echo '<div class="row">';
            echo '<label for="doctor_username">Doctor</label>';
            echo '<select id="doctor_username" name="doctor_username">';
            echo '<option value=""></option>';
            foreach ($information as $list_info) {
                $selected = '';
                if ($list_info['username'] == $list['username']) {
                    $selected = 'selected';
                }
                echo "<option value='{$list_info['username']}' $selected>{$list_info['username']}</option>";
            }           
            echo '</select>';
            echo '</div>';
            echo '<label for="lab_number">Labnumber</label>';
            echo '<input  type="text" name="lab_number"  value="' . $lab_number . '" readonly>';
            echo '<input type="hidden" name="created_user" value="' . htmlspecialchars($loggedInUsername) . '">';
            echo '<input type="hidden" name="row_id" value="' . htmlspecialchars($list['row_id']) . '">'; // Hidden input to store row_id for update
                
                        // If the status is 'Assist', allow user to update
            echo '<input type="submit" value="Update">';
            }
            print('</form>');
            echo '</div>';
    } else {
        // For new creation 
            print '<div class="content">';
            print('<p>Assisted By </p>');
            print('<form method="post" action="doctor_signature_create.php">');
            echo '<div class="row">';
            echo '<label for="doctor_username">Doctor</label>';
            echo '<select id="doctor_username" name="doctor_username">';
            echo '<option value=""></option>';
            foreach ($information as $list) {
                $selected = '';
                if ($list['username'] == $loggedInUsername) {
                    $selected = 'selected';
                }
                echo "<option value='{$list['username']}' $selected>{$list['username']}</option>";
            }        
            echo '</select>';
            echo '</div>';
            echo '<label for="lab_number">Labnumber</label>';
            echo '<input  type="text" name="lab_number"  value="' . $lab_number . '" readonly>';
            echo '<input type="hidden" name="created_user" value="' . htmlspecialchars($loggedInUsername) . '">';
            echo '<input type="submit" value="Save">';
            print('</form>');
            echo '</div>';
        }
        
       
   
    if (!empty($finialized_by)) {
        foreach($finialized_by as $list){
        print '<div class="content">';
        print('<p>Finalized By </p>');
        print('<form method="post" action="doctor_signature_finalized_update.php">');
        echo '<div class="row">';
        echo '<label for="doctor_username">Doctor</label>';
        echo '<select id="doctor_username" name="doctor_username">';
        echo '<option value=""></option>';
        
        foreach ($information as $list_info) {
            $selected = '';
            if ($list_info['username'] == $list['username']) {
                $selected = 'selected';
            }
            echo "<option value='{$list_info['username']}' $selected>{$list_info['username']}</option>";
        }     
                
            echo '</select>';
            echo '</div>';
            echo '<label for="lab_number">Labnumber</label>';
            echo '<input  type="text" name="lab_number"  value="' . $lab_number . '" readonly>';
            echo '<input type="hidden" name="created_user" value="' . htmlspecialchars($loggedInUsername) . '">';
            echo '<input type="hidden" name="row_id" value="' . htmlspecialchars($list['row_id']) . '">'; // Hidden input to store row_id for update
            // If the status is 'Assist', allow user to update
            echo '<input type="submit" value="Update">';
    }
        print('</form>');
        echo '</div>';
    } else {
        // For new creation 
        print '<div class="content">';
        print('<p>Finalized By </p>');
        print('<form method="post" action="doctor_signature_finalized_create.php">');
        echo '<div class="row">';
        echo '<label for="doctor_username">Doctor</label>';
        echo '<select id="doctor_username" name="doctor_username">';
        echo '<option value=""></option>';
        foreach ($information as $list) {
            $selected = '';
            if ($list['username'] == $loggedInUsername) {
                $selected = 'selected';
            }
            echo "<option value='{$list['username']}' $selected>{$list['username']}</option>";
        }        
        echo '</select>';
        echo '</div>';
        echo '<label for="lab_number">Labnumber</label>';
        echo '<input  type="text" name="lab_number"  value="' . $lab_number . '" readonly>';
        echo '<input type="hidden" name="created_user" value="' . htmlspecialchars($loggedInUsername) . '">';
        echo '<input type="hidden" name="status" value="Finalized">';
        echo '<input type="submit" value="Save">';
        print('</form>');
        echo '</div>';
    }
    
    


?>