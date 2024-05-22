<?php 

include("connection.php");

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

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
dol_include_once('/grossmodule/class/gross.class.php');
dol_include_once('/grossmodule/lib/grossmodule_gross.lib.php');


$title = $langs->trans("Gross");
$help_url = '';
llxHeader('', $title, $help_url);


print '<form method="post" style="visibility: hidden;" action="gross_summary_of_section_create.php">';
$GrossId = $_GET['fk_gross_id'];
print '<input type="hidden" name="fk_gross_id" value="' . $GrossId . '">';
// print('<label for="summary">Gross Summary Of Sections</label>');
print('<textarea name="summary" id="summary" style="display: none;">'.'</textarea>');
// print('<label for="ink_code">Ink Code</label>');
print('<textarea name="ink_code" id="ink_code" style="display: none;">'.'</textarea>');

print '<input type="submit" style="visibility: hidden;" value="Save">';
print '</form>';



print("<script>
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
</script>");


print("<script>
document.addEventListener('DOMContentLoaded', function() {
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
});
</script>
");

print('<script>
document.addEventListener("DOMContentLoaded", function() {
    // Simulate click on the "Save" button
    document.querySelector(\'input[type="submit"]\').click();
});
</script>');

?>


<style>
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
</style>