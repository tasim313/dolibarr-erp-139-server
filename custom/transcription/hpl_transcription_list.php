<?php 
// Load Dolibarr environment
include('connection.php');
include('common_function.php');
include('../grossmodule/gross_common_function.php');
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
$langs->loadLangs(array("transcription@transcription"));

$action = GETPOST('action', 'aZ09');


// Security check
// if (! $user->rights->transcription->myobject->read) {
// 	accessforbidden();
// }
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

$max = 5;
$now = dol_now();


/*
 * Actions
 */

// None


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

llxHeader("", $langs->trans("TranscriptionArea"));

$loggedInUserId = $user->id;
$loggedInUsername = $user->login;

$userGroupNames = getUserGroupNames($loggedInUserId);

$hasTranscriptionist = false;
$hasConsultants = false;

foreach ($userGroupNames as $group) {
    if ($group['group'] === 'Transcription') {
        $hasTranscriptionist = true;
    } elseif ($group['group'] === 'Consultants') {
        $hasConsultants = true;
    }
}

// Access control using switch statement
switch (true) {
  case $hasTranscriptionist:
      // Transcription  has access, continue with the page content...
      break;
  case $hasConsultants:
      // Doctor has access, continue with the page content...
      break;
  default:
      echo "<h1>Access Denied</h1>";
      echo "<p>You are not authorized to view this page.</p>";
      exit; // Terminate script execution
}
$LabNumber = $_GET['lab_number'];
$LabNumberWithoutPrefix = substr($LabNumber, 3);
$patient_information = get_patient_details_information($LabNumberWithoutPrefix);
$specimenIformation   = get_gross_specimens_list($LabNumberWithoutPrefix);
print("<style>

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
    margin-left: 30px;
}

.col-75 {
    float: left;
    width: 75%;
    margin-top: 6px;
    margin-left: 30px;
}

.row::after {
    content: '';
    display: table;
    clear: both;
}

table {
    border-collapse: collapse;
    border-spacing: 0;
    width: 100%;
    border: 1px solid #ddd;
  }
  
  th, td {
    text-align: left;
    padding: 16px;
  }
  
  tr:nth-child(even) {
    background-color: #f2f2f2;
  }'

.table-container {
    width: 48%; /* Adjust the width of each table container */
    overflow: auto; /* Add scrolling if needed */
    margin: 0 1%; /* Add some margin between the tables */
}
.table-container {
    width: 48%; /* Adjust the width of each table container */
    overflow: auto; /* Add scrolling if needed */
    margin: 0 1%; /* Add some margin between the tables */
}
.table-container table {
    width: 100%;
    border-collapse: collapse;
}
.table-container th, .table-container td {
    border: 1px solid #ddd;
    padding: 8px;
}  
#customers {
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
}
button {
    background-color: rgb(118, 145, 225);
    color: white;
    padding: 12px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    float: right;
    transition: box-shadow 0.3s ease;
}
#pendingTable {
    font-family: Arial, Helvetica, sans-serif;
    border-collapse: collapse;
    width: 100%;
  }
  
#pendingTable td, #pendingTable th {
    border: 1px solid #ddd;
    padding: 8px;
  }
  
#pendingTable tr:nth-child(even){
    background-color: #f2f2f2;
  }
  
#pendingTable tr:hover {
    background-color: #ddd;
  }
  
#pendingTable th {
    padding-top: 12px;
    padding-bottom: 12px;
    text-align: left;
    background-color: #046aaa;
    color: white;
}
#searchInput {
    width: 100%;
    padding: 12px 20px;
    margin: 8px 0;
    box-sizing: border-box;
    border: 2px solid #ccc;
    border-radius: 4px;
    background-color: #f8f8f8;
    font-size: 16px;
    outline: none;
  }
  
#searchInput:focus {
    border-color: #007bff; 
  }
#searchInputAssign {
    width: 100%;
    padding: 12px 20px;
    margin: 8px 0;
    box-sizing: border-box;
    border: 2px solid #ccc;
    border-radius: 4px;
    background-color: #f8f8f8;
    font-size: 16px;
    outline: none;
  }
  
#searchInputAssign:focus {
    border-color: #007bff; 
  }
</style>");


if ($hasTranscriptionist) {
    print '<div class="row">';
    print('<div class="column">');
    print('<table id="pendingTable">');
    print('<tr>
	<th>Histopathology Report Modify</th>
	
	<th>Action</th></tr>');
    print('
        <tr>
            <td>Patient Information</td>
            <td><a href="patient_info.php?lab_number=' . $LabNumber. '"><button class="btn btn-primary">Edit</button></td>
        </tr>
        <tr>
            <td>Specimen Information</td>
            <td><a href="specimen_info.php?lab_number=' . $LabNumber. '"><button class="btn btn-primary">Edit</button></td>
        </tr>
        <tr>
            <td>Clinical Details</td>
            <td><a href="clinical_info.php?lab_number=' . $LabNumber. '"><button class="btn btn-primary">Edit</button></td>
        </tr>
        <tr>
            <td>Gross Description</td>
            <td><a href="gross_info.php?lab_number=' . $LabNumber. '"><button class="btn btn-primary">Edit</button></td>
        </tr>
        <tr>
            <td>Micro Description</td>
            <td><a href="micro_info.php?lab_number=' . $LabNumber. '"><button class="btn btn-primary">Edit</button></td>
        </tr>
        <tr>
            <td>Diagnosis Description</td>
            <td><a href="diagnosis_info.php?lab_number=' . $LabNumber. '"><button class="btn btn-primary">Edit</button></td>
        </tr>
        <tr>
            <td>Doctor Signature</td>
            <td><a href="doctor_signature.php?lab_number=' . $LabNumber. '"><button class="btn btn-primary">Edit</button></td>
        </tr>
        <tr>
            <td>Report</td>
            <td><a href="../grossmodule/hpl_report.php?lab_number=' . $LabNumber. '" target="_blank"><button class="btn btn-primary">Preview</button></td>
        </tr>
        
        ');
  
    print('</table>');
    print('</div>');
    print '</div>';
}

?>