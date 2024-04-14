<?php 
include('connection.php');
include('gross_common_function.php');
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

$title = $langs->trans("Gross");
$help_url = '';
llxHeader('', $title, $help_url);
$username = $_GET['username'];

print('<style>
* {
box-sizing: border-box;
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


@media screen and (max-width: 600px) {
.col-25, .col-75, input[type=submit] {
width: 100%;
margin-top: 0;
}
}
</style>'); 

$loggedInUsername = $user->login;
$information = get_single_doctor_information($username);
$details = get_single_doctor_details($username);

foreach ($information as $list) {
    print('<form method="post" action="doctor_designation_create.php">');
    echo '<div class="row">';
    echo '<div class="col-25">';
    echo '<label for="username">UserName</label>';
    echo '</div>';
   
    echo '<div class="col-75">';
    echo '<input type="text" name="username" value="' . htmlspecialchars($list['doctor_username']) . '" readonly>';
    echo '</div>';
    echo '</div>';
    echo '<div class="row">';
    echo '<div class="col-25">';
    echo '<label for="doctor_name">Doctor Name</label>';
    echo '</div>';
    echo '<div class="col-75">';
    echo '<input  type="text" name="doctor_name"  value="' . htmlspecialchars($list['doctor_name']) . '" readonly>';
    echo '</div>';
    
    // Check if details are available
    if (!empty($details)) {
        foreach ($details as $detail) {
            echo '<div class="col-25">';
            echo '<label for="education">Education</label>';
            echo '</div>';
            echo '<div class="col-75">';
            echo '<input  type="text" name="education"  value="'. htmlspecialchars($detail['education']) .'" >';
            echo '</div>';
            echo '<div class="col-25">';
            echo '<label for="designation">Designation</label>';
            echo '</div>';
            echo '<div class="col-75">';
            echo '<input  type="text" name="designation"  value="' . htmlspecialchars($detail['designation']) . '" >';
            echo '</div>';
            echo '<input type="hidden" name="created_user" value="' . htmlspecialchars($loggedInUsername) . '">';
        }
        // Show the "Update" button if details are available
        echo '<input type="submit" value="Update">';
    } else {
        // Show input fields for education and designation if details are not available
        echo '<div class="col-25">';
        echo '<label for="education">Education</label>';
        echo '</div>';
        echo '<div class="col-75">';
        echo '<input  type="text" name="education"  value="" >';
        echo '</div>';
        echo '<div class="col-25">';
        echo '<label for="designation">Designation</label>';
        echo '</div>';
        echo '<div class="col-75">';
        echo '<input  type="text" name="designation"  value="" >';
        echo '</div>';
        echo '<input type="hidden" name="created_user" value="' . htmlspecialchars($loggedInUsername) . '">';
        // Show the "Save" button if details are not available
        echo '<input type="submit" value="Save">';
    }
    
    echo '</div>';
    print('</form>');
    
}



?>