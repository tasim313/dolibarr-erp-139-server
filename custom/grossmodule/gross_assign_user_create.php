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

$LabNumber = $_GET['lab_number'];


$lab_number = substr($LabNumber, 3);

$patient_informations = get_patient_information($lab_number);

$gross_assign_user = $user->id;
$loggedInUsername = $user->login;

print("<style>");

print('h1{
    margin-left: 38px;
}');
print('label {
    display: block; 
    margin-left: 38px;
    margin-bottom: 10px; 
    font-weight: bold; 
    font-size: 26px;
    color: #333; 
}');

print(' input[type=text], select {
	width: 100%;
	padding: 12px 20px;
	margin: 8px 0;
	display: inline-block;
	border: 1px solid #ccc;
	border-radius: 4px;
	box-sizing: border-box;
}');
print('input[type=submit] {
	width: 100%;
	background-color: #4CAF50;
	color: white;
	padding: 14px 20px;
    margin-left: 24px;
	margin: 8px 0;
	border: none;
	border-radius: 4px;
	cursor: pointer;
}');
print('.row {
    display: flex;
    justify-content: space-between;
}');
print('.table-container {
        width: 48%; /* Adjust the width of each table container */
        overflow: auto; /* Add scrolling if needed */
        margin: 0 1%; /* Add some margin between the tables */
}');
print('.table-container table {
        width: 100%;
        border-collapse: collapse;
}');
print('.table-container th, .table-container td {
        border: 1px solid #ddd;
        padding: 8px;
}');
print('.table-container th {
        background-color: #f2f2f2;
}');
     
print('button {
       background-color: rgb(118, 145, 225);
       color: white;
       padding: 12px 20px;
       border: none;
       border-radius: 4px;
       cursor: pointer;
       float: right;
       transition: box-shadow 0.3s ease;
}');

print('.select2-container--default .select2-selection--single {
    border: 1px solid #ccc;
    border-radius: 5px;
    height: 38px;
    line-height: 36px;
    font-size: 16px;
    margin-top: 10px;
    margin-left: 38px;
    margin-bottom: 10px;
    padding: 0 20px; /* Adjust padding */
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 36px;
    width: 20px;
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    color: #555;
}

.select2-container--default .select2-selection--single .select2-selection__arrow b {
    border-color: #888 transparent transparent transparent;
}

.select2-container--default .select2-selection--single .select2-selection__arrow:after {
    border-color: #777 transparent transparent transparent !important;
}

.select2-container--default.select2-container--focus .select2-selection--single {
    border: 1px solid #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.select2-container--default .select2-selection--single:focus {
    outline: none;
}
');
print('#customers {
            font-family: Arial, Helvetica, sans-serif;
            border-collapse: collapse;
            width: 100%;
          }
          
          #customers td, #customers th {
            border: 1px solid #ddd;
            padding: 8px;
          }
          
          #customers tr:nth-child(even){
            background-color: #f2f2f2;
          }
          
          #customers tr:hover {
            background-color: #ddd;
          }
          
          #customers th {
            padding-top: 12px;
            padding-bottom: 12px;
            text-align: left;
            background-color: #046aaa;
            color: white;
        }');
print("</style>");

print("<div class='container'>");

print("<div class='row'>");
print('<div class="table-container">');
print('<h2>Patient Information</h2>');
print('<table id="customers">');
print('<tr><th>Name</th><th>Patient Code</th><th>Address</th><th>Patient Phone Number</th><th>Patient Attendant Phone Number</th></tr>');
foreach ($patient_informations as $patient_info) {
    print('<tr><td>' . $patient_info['name']. 
	'</td><td>'. $patient_info['patient_code']. 
	'</td><td>'. $patient_info['address'].
	'</td><td>'. $patient_info['phone'].
	'</td><td>'. $patient_info['fax']
    );
}
print('</table>');
print('</div>');

print('<div class="table-container">');
print('<h2>Patient Specimen Information</h2>');
print('<table id="customers">');
print('<tr><th>Specimen</th><th>Number of Containers</th><th>Specimen Name</th></tr>');
$specimenList = get_gross_specimens_list($lab_number);
$number_of_specimens = $specimenList[0]['number_of_specimens'];
$alphabet_string = numberToAlphabet($number_of_specimens);
print('<tr><td>'. $alphabet_string .'</td>');
foreach($specimenList as $key => $specimen){
	
	print('<td>' .$specimen['num_containers'] . '</td>');
	break;
}
print('<td>');
foreach ($specimenList as $key => $specimen) {
	print('<p> '. $specimen['specimen'] .'</p>') ;
}
print('</td>');

print('</table>');
print('</div>');


print('</div>');



print("</div>");


$gross_assign = get_gross_assign_list($LabNumber);

if ($gross_assign) {
    echo "<div class='row'>";
    echo "<div class='table-container'>";
    echo "<h2>Gross Assign Details</h2>";
    echo "<table id='customers'>";
    echo "<tr><th>Doctor</th><th>Assistant</th><th>Assign Person</th><th>Date</th><th>Status</th><th>Action</th></tr>";
    echo "<tr>";
    echo "<td>{$gross_assign['Doctor']}</td>";
    echo "<td>{$gross_assign['Assistant']}</td>";
    echo "<td>{$gross_assign['AssignPerson']}</td>";
    $dateString = $gross_assign['Date'];
    $date = new DateTime($dateString);
    $formattedDate = $date->format('d F l Y ');
    echo "<td>{$formattedDate}</td>";
    echo "<td>{$gross_assign['Status']}</td>";
    
  
    if ($gross_assign['Status'] === 'Pending') {
        echo('<td><a href="gross_assign_user_update_function.php?assign=' . $gross_assign['assign_id'] . '&lab_number=' . $LabNumber . '"><button>Edit</button></a></td>');
    } else {
        echo('<td><button hidden></button></td>');
    }
    
    echo "</tr>";
    echo "</table>";
    echo "</div>";
    echo "</div>";
} else {
    echo "<div class='row'>";
    echo "<div class='table-container'>";
    echo "<h2>Gross Assignment Details</h2>";
    echo "<table id='customers'>";
    echo "<tr><th>Doctor</th><th>Assistant</th><th>Assign Person</th><th>Date</th><th>Status</th></tr>";
    echo "<tr>";
    echo "<td>Not Assign</td>";
    echo "<td>Not Assign</td>";
    echo "<td>Not Assign</td>";
    echo "<td>Not Assign</td>";
    echo "<td>Not Assign</td>";
    echo "</tr>";
    echo "</table>";
    echo "</div>";
    echo "</div>";
}


print("</div>");


print("<div class='row'>");
print('<div class="">');
print("<h1>Assign Doctor and Gross Assistant</h1>");
print('<form id="grossForm"  method="post" action="gross_assign_user_create_function.php">');

// Doctor Dropdown
print('<label for="gross_doctor_name">Doctor</label>');
print('<select id="gross_doctor_name" name="gross_doctor_name" style="width: 100%;" class="doctor-dropdown">');
print('<option value=""></option>');
$doctors = get_doctor_list();
foreach ($doctors as $doctor) {
    echo "<option value='{$doctor['doctor_username']}'>{$doctor['doctor_username']}</option>";
}
print('</select>');

// Gross Assistant Dropdown
print('<label for="gross_assistant_name">Gross Assistant</label>');
print('<select id="gross_assistant_name" name="gross_assistant_name" style="width: 100%;" class="assistant-dropdown">');
print('<option value=""></option>');
$assistants = get_gross_assistant_list();
foreach ($assistants as $assistant) {
    echo "<option value='{$assistant['username']}'>{$assistant['username']}</option>";
}
print('</select>');





print('<input type="text" id="lab_number" name="lab_number" placeholder="Insert Lab Number" value="' . htmlspecialchars($LabNumber) . '" readonly style="margin-left: 38px;">');
print('<input type="hidden" id="gross_status" name="gross_status" value="Pending">');
print('<input type="hidden" id="gross_assign_created_user" name="gross_assign_created_user" value="'.$loggedInUsername .'">');
print('<br><br>');
print('<button type="submit">Save</button>');
print('</form>');
print("</div>");
print("</div>");
print("</div>");




print('<script>
document.addEventListener("DOMContentLoaded", function() {
    var gridItems = document.querySelectorAll(".grid-item");
    var gross_doctor_name = document.getElementById("gross_doctor_name");

    gridItems.forEach(function(item) {
        item.addEventListener("click", function() {
            
            gridItems.forEach(function(item) {
                item.classList.remove("selected");
            });

           
            this.classList.toggle("selected");
            
            
            var doctorName = this.dataset.name;
            gross_doctor_name.value = doctorName;
        });
    });
});
document.addEventListener("DOMContentLoaded", function() {
    var gridItems = document.querySelectorAll(".grid-item-assistant");
    var gross_assistant_name = document.getElementById("gross_assistant_name");

    gridItems.forEach(function(item) {
        item.addEventListener("click", function() {
            
            gridItems.forEach(function(item) {
                item.classList.remove("selected");
            });

           
            this.classList.toggle("selected");
            
            
            var assistantName = this.dataset.name;
            gross_assistant_name.value = assistantName;
        });
    });
});

document.addEventListener("DOMContentLoaded", function() {
    var LabNumberInput = document.getElementById("lab_number");
    
    
    LabNumberInput.value = "' . htmlspecialchars($LabNumber) . '";
});

$(document).ready(function() {
    $(".doctor-dropdown").select2({
        placeholder: "Search or select a doctor",
        allowClear: true
    });

    $(".assistant-dropdown").select2({
        placeholder: "Search or select an assistant",
        allowClear: true
    });
});

</script>');

?>