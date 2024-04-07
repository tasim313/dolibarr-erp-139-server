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

</style>");

print("<div style='display: flex;'>");

print('<form method="post" action="patient_info_update.php">'); 
print('<h4>Patient Information</h4><table style="border-collapse: collapse; border: 1px solid black; cellpadding: 2px; cellspacing: 2px;">'); // Add inline CSS for table styling
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
        <td style="border: 1px solid black; padding: 5px;">Name:</td> 
        <td style="border: 1px solid black; padding: 5px;"><input type="text" name="name[]" value="' . $list['name'] . '"></td> 
        <td style="border: 1px solid black; padding: 5px;"><input type="hidden" name="rowid[]" value="' . $list['rowid'] . '"><button type="submit" name="submit" value="name" disabled>Save</button></td> 
    </tr>
    <tr>
        <td style="border: 1px solid black; padding: 5px;">Patient Code:</td>
        <td style="border: 1px solid black; padding: 5px;"><input type="text" name="patient_code[]" value="' . $list['patient_code'] . '"></td> 
        <td style="border: 1px solid black; padding: 5px;"><input type="hidden" name="rowid[]" value="' . $list['rowid'] . '"><button type="submit" name="submit" value="patient_code" disabled>Save</button></td> 
    </tr>
    <tr>
        <td style="border: 1px solid black; padding: 5px;">Address:</td>
        <td style="border: 1px solid black; padding: 5px;"><input type="text" name="address[]" value="' . $list['address'] . '"></td>
        <td style="border: 1px solid black; padding: 5px;"><input type="hidden" name="rowid[]" value="' . $list['rowid'] . '"><button type="submit" name="submit" value="address" disabled>Save</button></td> 
    </tr>
    <tr>
        <td style="border: 1px solid black; padding: 5px;">Phone:</td>
        <td style="border: 1px solid black; padding: 5px;"><input type="text" name="phone[]" value="' . $list['phone'] . '"></td> 
        <td style="border: 1px solid black; padding: 5px;"><input type="hidden" name="rowid[]" value="' . $list['rowid'] . '"><button type="submit" name="submit" value="phone" disabled>Save</button></td> 
    </tr>
    <tr>
        <td style="border: 1px solid black; padding: 5px;">Attendant Number:</td>
        <td style="border: 1px solid black; padding: 5px;"><input type="text" name="fax[]" value="' . $list['fax'] . '"></td> 
        <td style="border: 1px solid black; padding: 5px;"><input type="hidden" name="rowid[]" value="' . $list['rowid'] . '"><button type="submit" name="submit" value="fax" disabled>Save</button></td> 
    </tr>
    <tr>
        <td style="border: 1px solid black; padding: 5px;">Date of Birth:</td>
        <td style="border: 1px solid black; padding: 5px;"><input type="text" name="date_of_birth[]" value="' . $list['date_of_birth'] . '"></td> 
        <td style="border: 1px solid black; padding: 5px;"><input type="hidden" name="rowid[]" value="' . $list['rowid'] . '"><button type="submit" name="submit" value="date_of_birth" disabled>Save</button></td> 
    </tr>
    <tr>
        <td style="border: 1px solid black; padding: 5px;">Gender:</td>
        <td style="border: 1px solid black; padding: 5px;"><input type="text" name="gender[]" value="' . $gender . '"></td> 
        <td style="border: 1px solid black; padding: 5px;"><input type="hidden" name="rowid[]" value="' . $list['rowid'] . '"><button type="submit" name="submit" value="gender" disabled>Save</button></td> 
    </tr>
    <tr>
        <td style="border: 1px solid black; padding: 5px;">Age:</td>
        <td style="border: 1px solid black; padding: 5px;"><input type="text" name="age[]" value="' . $list['Age'] . '"></td> 
        <td style="border: 1px solid black; padding: 5px;"><input type="hidden" name="rowid[]" value="' . $list['rowid'] . '"><button type="submit" name="submit" value="age" disabled>Save</button></td> 
    </tr>
    <tr>
        <td style="border: 1px solid black; padding: 5px;">Attendant Name:</td>
        <td style="border: 1px solid black; padding: 5px;"><input type="text" name="att_name[]" value="' . $list['att_name'] . '"></td> 
        <td style="border: 1px solid black; padding: 5px;"><input type="hidden" name="rowid[]" value="' . $list['rowid'] . '"><button type="submit" name="submit" value="att_name" disabled>Save</button></td> 
    </tr>
    <tr>
        <td style="border: 1px solid black; padding: 5px;">Attendant Relation:</td>
        <td style="border: 1px solid black; padding: 5px;"><input type="text" name="att_relation[]" value="' . $list['att_relation'] . '"></td> 
        <td style="border: 1px solid black; padding: 5px;"><input type="hidden" name="rowid[]" value="' . $list['rowid'] . '"><button type="submit" name="submit" value="att_relation" disabled>Save</button></td> 
    </tr>'
    );
}
print('</table>');
print('</form>');

print('<div style="margin-right: 20px;">

</div>');
print('<form method="post" action="patient_info_update.php">'); 
print('<h4>Specimen Information</h4><table style="border-collapse: collapse; border: 1px solid black; cellpadding: 2px; cellspacing: 2px;">'); // Add inline CSS for table styling
foreach ($specimenIformation as $list) {
    print('
    <tr>
        <td style="border: 1px solid black; padding: 5px;">Site Of Specimen:</td> 
        <td style="border: 1px solid black; padding: 5px;"><input type="text" name="name[]" value="' . $list['specimen'] . '"></td> 
        <td style="border: 1px solid black; padding: 5px;"><input type="hidden" name="rowid[]" value="' . $list['rowid'] . '"><button type="submit" name="submit" value="name" disabled>Save</button></td> 
    </tr>'
    );
}
print('</table>');
print('</form>');

print("<div style='margin-right: 20px;'>
<form id='clinicalDetailsForm' method='post' action='clinical_details.php'>
    <div class='row'>
        <div class='col-25'>
            <label style='font-weight: bold;' for='clinical_details'>Clinical Details</label>
        </div>
        <div class='col-75'>
            <textarea id='clinicalDetailsTextarea' name='clinical_details' cols='60' rows='10'></textarea>
            <input type='hidden' id='labNumberInput' name='lab_number' value='.$LabNumber.'>
            <input type='hidden' id='createdUserInput' name='created_user' value='.$loggedInUsername.'>
            <button style='background-color: rgb(118, 145, 225);
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            float: right;
            transition: box-shadow 0.3s ease;' id='saveBtn' type='submit'>Save</button>
            <button id='updateBtn' type='submit' style='display: none;'>Update</button>
        </div>
    </div>
</form>
</div>");
print("</div>");

// Gross
$fk_gross_id = getGrossIdByLabNumber($LabNumber);
$specimens = get_gross_specimen_description($fk_gross_id);

print('<form method="post" action="update_gross_specimens.php">');
foreach ($specimens as $specimen) {
    echo '<div class="row">';
    echo '<div class="col-25">';
    echo '<label for="specimen">Specimen</label>';
    echo '</div>';
    echo '<div class="col-75">';
    echo '<input type="text" name="specimen[]" value="' . htmlspecialchars($specimen['specimen']) . '" readonly>';
    echo '</div>';
    echo '</div>';
    echo '<div class="row">';
    echo '<div class="col-25">';
    echo '<label for="gross_description">Gross Description</label>';
    echo '</div>';
    echo '<div class="col-75">';
    echo '<textarea name="gross_description[]" cols="60" rows="10">' . htmlspecialchars($specimen['gross_description']) . '</textarea>';
    echo '</div>';
    echo '</div>';
    echo '<input type="hidden" name="fk_gross_id[]" value="' . htmlspecialchars($fk_gross_id) . '">';
    print('<br>');
    echo '<button  type="submit" style="display: none;">Update</button>';
}
echo '</form>';



$sections = get_gross_specimen_section($fk_gross_id);

print('<form method="post" action="update_gross_specimen_section.php">');
foreach ($sections as $section) {
    echo '<div class="row">';
    echo '<div class="col-25">';
    echo '<label for="section_code">Section Code</label>';
    echo '</div>';
    echo '<div class="col-75">';
    echo '<input type="hidden" name="gross_specimen_section_Id[]" value="' . htmlspecialchars($section['gross_specimen_section_id']) . '">';
    echo '<input type="text" name="sectionCode[]" value="' . htmlspecialchars($section['section_code']) . '" readonly>';
    echo '</div>';
    echo '</div>';
    echo '<div class="row">';
    echo '<div class="col-25">';
    echo '<label for="cassette_number">Cassette Number</label>';
    echo '</div>';
    echo '<div class="col-75">';
    echo '<input type="text" name="cassetteNumber[]" value="' . htmlspecialchars($section['cassettes_numbers']) . '" readonly>';
    echo '</div>';
    echo '</div>';
    echo '<div class="row">';
    echo '<div class="col-25">';
    echo '<label for="specimen_section_description">Description</label>';
    echo '</div>';
    echo '<div class="col-75">';
    echo '<textarea name="specimen_section_description[]">' . htmlspecialchars($section['specimen_section_description']) . '</textarea>';
    echo '</div>';
    echo '</div>';
}
print('<br>');
echo '<input type="hidden" name="fk_gross_id[]" value="' . htmlspecialchars($fk_gross_id) . '">';
echo '<button  type="submit" style="display: none;">Update</button>';
echo '</form>';

$summaries = get_gross_summary_of_section($fk_gross_id);
print('<form method="post" action="update_gross_summary.php">');
foreach ($summaries as $summary) {
    echo '<div class="row">';
    echo '<div class="col-25">';
    echo '<label for="summary">Summary</label>';
    echo '</div>';
    echo '<div class="col-75">';
    print('<textarea name="summary" id="summary">'. htmlspecialchars($summary['summary']) .'</textarea>');
    echo '</div>';
    echo '</div>';
    echo '<div class="row">';
    echo '<div class="col-25">';
    echo '<label for="ink_code">Ink Code</label>';
    echo '</div>';
    echo '<div class="col-75">';
    print('<textarea name="ink_code" id="ink_code" >'.htmlspecialchars($summary['ink_code']) .'</textarea>');
    echo '</div>';
    echo '</div>';
    echo '<input type="hidden" name="gross_summary_id" value="' . htmlspecialchars($summary['gross_summary_id']) . '">';
    echo '<input type="hidden" name="fk_gross_id" value="' . htmlspecialchars($fk_gross_id) . '">';
}
print('<br>');
echo '<button  type="submit" style="display: none;">Update</button>';
echo '</form>';

$LabNumberWithoutPrefix = substr($LabNumber, 3);
?>

<script>
    document.addEventListener("DOMContentLoaded", function() {
    // Fetch existing clinical details using AJAX when the page loads
    fetchExistingClinicalDetails();

    function fetchExistingClinicalDetails() {
        // Get the lab number from the hidden input field
        var labNumber = document.getElementById("labNumberInput").value;

        // Make an AJAX request to fetch existing clinical details
        var xhr = new XMLHttpRequest();
        xhr.open("GET", "get_clinical_details.php?lab_number=" + labNumber, true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4 && xhr.status == 200) {
                // Parse the JSON response
                var response = JSON.parse(xhr.responseText);
                if (response.success) {
                    // Populate the textarea with existing clinical details
                    document.getElementById("clinicalDetailsTextarea").value = response.data.clinical_details;
                    // Toggle visibility of Save and Update buttons based on whether data exists
                    if (response.data.clinical_details) {
                        document.getElementById("saveBtn").style.display = "none";
                        document.getElementById("updateBtn").style.display = "inline-block";
                    } else {
                        document.getElementById("saveBtn").style.display = "inline-block";
                        document.getElementById("updateBtn").style.display = "none";
                    }
                } else {
                    console.error("Error fetching existing clinical details:", response.error);
                }
            }
        };
        xhr.send();
    }
});

</script>


<div class="container">
<?php 
    // Retrieve existing micro descriptions
    $existingMicroDescriptions = getExistingMicroDescriptions($LabNumber);
    $specimens_list = get_gross_specimens_list($LabNumberWithoutPrefix);
    // Ensure $existingMicroDescriptions is an array
    if (!is_array($existingMicroDescriptions)) {
        $existingMicroDescriptions = array();
    }
    print('<form id="microDescriptionForm">');
    // Loop through specimens list to generate form fields
    foreach ($specimens_list as $key => $specimen) {
        $text_area_id = 'description' . $key;
        
        // Loop through existing micro descriptions to find matching specimen
        foreach ($existingMicroDescriptions as $existingDescription) {
            // Check if the specimen in $existingDescription matches the current specimen
            if ($existingDescription['specimen'] === $specimen['specimen']) {
                echo '<div class="row">';
                echo '<div class="col-25">';
                echo '<label for="specimen">' . $specimen['specimen'] . '</label>';
                echo '</div>';
                echo '<div class="col-75">';
                echo '<textarea id="' . $text_area_id . '" name="description[]" data-index="' . $key . '" cols="60" rows="10" required>' . htmlspecialchars($existingDescription['description']) . '</textarea>';
                echo '<input type="hidden" name="specimen[]" value="' . $specimen['specimen'] . '">';
                echo '<input type="hidden" name="fk_gross_id[]" value="' . $existingDescription['fk_gross_id'] . '">';
                echo '<input type="hidden" name="created_user[]" value="' . $existingDescription['created_user'] . '">';
                echo '<input type="hidden" name="status[]" value="' . $existingDescription['status'] . '">';
                echo '<input type="hidden" name="lab_number[]" value="' . $existingDescription['lab_number'] . '">';
                echo '<input type="hidden" name="row_id[]" value="' . $existingDescription['row_id'] . '">';
                echo '</div>';
                echo '</div>';
                
                // Once a match is found, break out of the loop
                break;
            }
        }
    }
    
?>
    <div class="row">
        <input style='margin-right: 300px;' type="submit" id="microDescriptionSaveButton" value="update">
    </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    fetch('shortcuts.json')
        .then(response => response.json())
        .then(shortcuts => {
            document.querySelectorAll('textarea[name="description[]"]').forEach(textarea => {
                textarea.addEventListener('input', function() {
                    let cursorPosition = this.selectionStart;
                    let index = this.getAttribute('data-index'); // Get the index from the data attribute
                    for (let shortcut in shortcuts) {
                        if (this.value.includes(shortcut)) {
                            this.value = this.value.replace(shortcut, shortcuts[shortcut]);
                            this.selectionEnd = cursorPosition + (shortcuts[shortcut].length - shortcut.length);
                            // Update the corresponding textarea value in the formData
                            const formData = new FormData(document.getElementById('microDescriptionForm'));
                            formData.set('description[' + index + ']', this.value);
                            break;
                        }
                    }
                });
            });
        })
        .catch(error => console.error('Error loading shortcuts:', error));
});

document.getElementById("microDescriptionForm").addEventListener("submit", function(event) {
    event.preventDefault();
    const formData = new FormData(this);
    
    fetch("update_micro_descriptions.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        console.log(data); 
    })
    .catch(error => {
        console.error("Error:", error);
    });
});
</script>