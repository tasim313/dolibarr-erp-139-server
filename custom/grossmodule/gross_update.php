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
$loggedInUserId = $user->id;

$loggedInUsername = $user->login;

$fk_gross_id = $_GET['fk_gross_id'];

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
echo '<input type="submit" value="Update">';
echo '</form>';

$sections = get_gross_specimen_section($fk_gross_id);

print('<div id="form-container">');
print('<form id="section-code-form" method="post" action="update_gross_specimen_section.php">');
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
echo '<input type="hidden" name="fk_gross_id[]" value="' . htmlspecialchars($fk_gross_id) . '">';
echo '<input type="submit" value="Update">';
echo '</form>';
print("</div>");

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
echo '<input type="submit" value="Update">';
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

              textarea.addEventListener('keydown', function(event) {
                    if (event.key === 'Enter' && !event.shiftKey) {
                        event.preventDefault(); // Prevent default behavior of Enter key
                        this.closest('form').submit(); // Submit the form containing the textarea
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
            textarea.addEventListener('keydown', function(event) {
                    if (event.key === 'Enter' && !event.shiftKey) {
                        event.preventDefault(); // Prevent default behavior of Enter key
                        this.closest('form').submit(); // Submit the form containing the textarea
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
        // Listen for Enter key press event
        document.querySelectorAll('textarea').forEach(textarea => {
                textarea.addEventListener('keydown', function(event) {
                    if (event.key === 'Enter' && !event.shiftKey) {
                        event.preventDefault(); // Prevent default behavior of Enter key
                        this.closest('form').submit(); // Submit the form containing the textarea
                    }
                });
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
        
        document.querySelectorAll('textarea').forEach(textarea => {
                textarea.addEventListener('keydown', function(event) {
                    if (event.key === 'Enter' && !event.shiftKey) {
                        event.preventDefault(); // Prevent default behavior of Enter key
                        this.closest('form').submit(); // Submit the form containing the textarea
                    }
                });
            });
        })
    .catch(error => console.error('Error loading shortcuts:', error));
</script>