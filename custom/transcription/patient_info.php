<?php 
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
.customers {
    font-family: Arial, sans-serif;
    border-collapse: collapse;
    width: 100%;
  }
  
  .customers td,
  .customers th {
    border: 1px solid #ddd;
    padding: 8px;
  }
  
  .customers tr:nth-child(even) {
    background-color: #f2f2f2;
  }
  
  .customers tr:hover {
    background-color: #ddd;
  }
  
  .customers th {
    padding-top: 12px;
    padding-bottom: 12px;
    text-align: left;
    background-color: #04AA6D;
    color: white;
  }
  
  /* Optional: Additional styles based on dynamic class (if used) */
  
  .transcriptionist-table {
    /* Specific styles for transcriptionists */
  }
  
  .consultant-table {
    /* Specific styles for consultants */
  }
  table {
    font-family: Arial, sans-serif;
    border-collapse: collapse;
    width: 100%;
    margin: 0 auto; /* Center the table horizontally */
  }
  
  /* Table cell styles */
  td, th {
    border: 1px solid #ddd;
    padding: 8px;
  }
  
  /* Alternate row background color */
  tr:nth-child(even) {
    background-color: #f2f2f2;
  }
  
  /* Hover effect for rows */
  tr:hover {
    background-color: #ddd;
  }
  
  /* Table header styles */
  th {
    padding-top: 12px;
    padding-bottom: 12px;
    text-align: left;
    background-color: #04AA6D;
    color: white;
  }
  
  /* Optional: Styles based on dynamic class (if used) */
  .transcriptionist-table,
  .consultant-table {
    /* Add specific styles for different user roles here */
  }
  .btn {
    /* Base styles for all buttons */
    display: inline-block; /* Ensures buttons behave like text inline */
    padding: 6px 12px; /* Padding for button content */
    font-size: 14px; /* Font size for button text */
    cursor: pointer; /* Indicates clickable element */
    border: none; /* Removes default button border */
    border-radius: 4px; /* Rounded corners for a modern look */
    text-align: center; /* Center text within the button */
    text-decoration: none; /* Removes underline from text */
    transition: background-color 0.2s ease-in-out; /* Smooth transition effect on hover */
  }
  
  .btn-primary {
    /* Styles for primary buttons */
    background-color: #038faf;/* Blue color */
    color: white; /* White text color */
  }
  
  .btn-primary:hover {
    /* Hover effect for primary buttons */
    background-color: #038faf; /* Darker blue on hover */
  }
  
  /* Add additional button styles as needed */
  
  .btn-secondary {
    background-color: #ddd; /* Gray color */
    color: black; /* Black text color */
  }
  
  .btn-secondary:hover {
    background-color: #ccc; /* Lighter gray on hover */
  }
</style>
");


print('<form method="post" action="patient_info_update.php">'); 
print('<h1>Patient Information</h1>');
print('<table class="customers">'); 
foreach ($patient_information as $list) {
    $gender = '';
    if ($list['Gender'] == '1') {
        $gender = 'Male';
    } elseif ($list['Gender'] == '2') {
        $gender = 'Female';
    } else {
        $gender = 'Other';
    }
    print('
    <tr>
        <td >Name</td> 
        <td><input type="text" name="name[]" value="' . $list['name'] . '"></td> 
        <td><input type="hidden" name="rowid[]" value="' . $list['rowid'] . '"><button type="submit" name="submit" value="name" class="btn btn-primary">Save</button></td> 
    </tr>
    <tr>
        <td>Patient Code</td>
        <td><input type="text" name="patient_code[]" value="' . $list['patient_code'] . '"></td> 
        <td><input type="hidden" name="rowid[]" value="' . $list['rowid'] . '"><button type="submit" name="submit" value="patient_code" class="btn btn-primary">Save</button></td> 
    </tr>
    <tr>
        <td>Address</td>
        <td><input type="text" name="address[]" value="' . $list['address'] . '"></td>
        <td><input type="hidden" name="rowid[]" value="' . $list['rowid'] . '"><button type="submit" name="submit" value="address" class="btn btn-primary">Save</button></td> 
    </tr>
    <tr>
        <td>Phone</td>
        <td><input type="text" name="phone[]" value="' . $list['phone'] . '"></td> 
        <td><input type="hidden" name="rowid[]" value="' . $list['rowid'] . '"><button type="submit" name="submit" value="phone" class="btn btn-primary">Save</button></td> 
    </tr>
    <tr>
        <td>Attendant Number</td>
        <td><input type="text" name="fax[]" value="' . $list['fax'] . '"></td> 
        <td><input type="hidden" name="rowid[]" value="' . $list['rowid'] . '"><button type="submit" name="submit" value="fax" class="btn btn-primary">Save</button></td> 
    </tr>
    <tr>
        <td>Date of Birth</td>
        <td><input type="text" name="date_of_birth[]" value="' . $list['date_of_birth'] . '"></td> 
        <td><input type="hidden" name="rowid[]" value="' . $list['rowid'] . '"><button type="submit" name="submit" value="date_of_birth" class="btn btn-primary">Save</button></td> 
    </tr>
    <tr>
        <td>Gender:</td>
        <td><input type="text" name="gender[]" value="' . $gender . '"></td> 
        <td><input type="hidden" name="rowid[]" value="' . $list['rowid'] . '"><button type="submit" name="submit" value="gender" class="btn btn-primary">Save</button></td> 
    </tr>
    <tr>
        <td>Age</td>
        <td><input type="text" name="age[]" value="' . $list['Age'] . '"></td> 
        <td><input type="hidden" name="rowid[]" value="' . $list['rowid'] . '"><button type="submit" name="submit" value="age" class="btn btn-primary">Save</button></td> 
    </tr>
    <tr>
        <td>Attendant Name</td>
        <td><input type="text" name="att_name[]" value="' . $list['att_name'] . '"></td> 
        <td><input type="hidden" name="rowid[]" value="' . $list['rowid'] . '"><button type="submit" name="submit" value="att_name" class="btn btn-primary">Save</button></td> 
    </tr>
    <tr>
        <td>Attendant Relation</td>
        <td><input type="text" name="att_relation[]" value="' . $list['att_relation'] . '"></td> 
        <td><input type="hidden" name="rowid[]" value="' . $list['rowid'] . '"><button type="submit" name="submit" value="att_relation" class="btn btn-primary">Save</button></td> 
    </tr>'
    );
}
print('</table>');
print('</form>');


?>