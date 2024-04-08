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
</style>
");

$fk_gross_id = getGrossIdByLabNumber($LabNumber);
$specimens = get_gross_specimen_description($fk_gross_id);

print('<form method="post" action="update_gross_specimens.php">');
foreach ($specimens as $specimen) {
    echo '<div class="row">';
    echo '<div class="col-25">';
    echo '<label for="specimen">Specimen</label>';
    echo '</div>';
    echo '<div class="col-75">';
    echo '<input type="hidden" name="specimen_id[]" value="' . htmlspecialchars($specimen['specimen_id']) . '" readonly>';
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
}
print('<br>');
echo '<input style="margin-right:360px;" type="submit" value="Update">';
echo '</form>';
print("<br><br>");


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
echo '<input style="margin-right:360px;" type="submit" value="Update">';
echo '</form>';
print("<br><br>");

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
echo '<input style="margin-right:360px;" type="submit" value="Update">';
echo '</form>';


?>

<script>

document.addEventListener('DOMContentLoaded', function() {
  fetch('shortcuts.json')
      .then(response => response.json())
      .then(shortcuts => {
          document.querySelectorAll('textarea[name="gross_description[]"]').forEach(textarea => {
              textarea.addEventListener('input', function() {
                  let cursorPosition = this.selectionStart;
                  for (let shortcut in shortcuts) {
                      if (this.value.includes(shortcut)) {
                          this.value = this.value.replace(shortcut, shortcuts[shortcut]);
                          this.selectionEnd = cursorPosition + (shortcuts[shortcut].length - shortcut.length);
                          break;
                      }
                  }
              });
          });
      })
      .catch(error => console.error('Error loading shortcuts:', error));
});


</script>


<script>

document.addEventListener('DOMContentLoaded', function() {
  fetch('shortcuts.json')
      .then(response => response.json())
      .then(shortcuts => {
          document.querySelectorAll('textarea[name="specimen_section_description[]"]').forEach(textarea => {
              textarea.addEventListener('input', function() {
                  let cursorPosition = this.selectionStart;
                  for (let shortcut in shortcuts) {
                      if (this.value.includes(shortcut)) {
                          this.value = this.value.replace(shortcut, shortcuts[shortcut]);
                          this.selectionEnd = cursorPosition + (shortcuts[shortcut].length - shortcut.length);
                          break;
                      }
                  }
              });
          });
      })
      .catch(error => console.error('Error loading shortcuts:', error));
});


</script>


<script>
fetch('shortcuts.json')
    .then(response => response.json())
    .then(shortcuts => {
        function handleShortcutInput(inputElement) {
            let inputValue = inputElement.value.toLowerCase();
            for (let shortcut in shortcuts) {
                if (inputValue.includes(shortcut)) {
                    inputElement.value = inputValue.replace(shortcut, shortcuts[shortcut]);
                    break; 
                }
            }
        }

        document.getElementById('summary').addEventListener('input', function() {
            let textarea = this;
            let cursorPosition = textarea.selectionStart;
            for (let shortcut in shortcuts) {
                if (textarea.value.includes(shortcut)) {
                    textarea.value = textarea.value.replace(shortcut, shortcuts[shortcut]);
                    textarea.selectionEnd = cursorPosition + (shortcuts[shortcut].length - shortcut.length);
                    break; 
                }
            }
        });

        document.getElementById('shortcutInput').addEventListener('input', function() {
            handleShortcutInput(this);
        });
    })
    .catch(error => console.error('Error loading shortcuts:', error));
</script>


<script>
fetch('shortcuts.json')
    .then(response => response.json())
    .then(shortcuts => {
        function handleShortcutInput(inputElement) {
            let inputValue = inputElement.value.toLowerCase();
            for (let shortcut in shortcuts) {
                if (inputValue.includes(shortcut)) {
                    inputElement.value = inputValue.replace(shortcut, shortcuts[shortcut]);
                    break; 
                }
            }
        }

        document.getElementById('ink_code').addEventListener('input', function() {
            let textarea = this;
            let cursorPosition = textarea.selectionStart;
            for (let shortcut in shortcuts) {
                if (textarea.value.includes(shortcut)) {
                    textarea.value = textarea.value.replace(shortcut, shortcuts[shortcut]);
                    textarea.selectionEnd = cursorPosition + (shortcuts[shortcut].length - shortcut.length);
                    break; 
                }
            }
        });

        document.getElementById('shortcutInput').addEventListener('input', function() {
            handleShortcutInput(this);
        });
    })
    .catch(error => console.error('Error loading shortcuts:', error));
</script>

