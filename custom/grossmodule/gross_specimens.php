<?php 

include('connection.php');
include('gross_common_function.php');

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

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
dol_include_once('/grossmodule/class/gross.class.php');
dol_include_once('/grossmodule/lib/grossmodule_gross.lib.php');

$title = $langs->trans("Gross");
$help_url = '';
llxHeader('', $title, $help_url);

$LabNumber = $_GET['LabNumber'];
$fk_gross_id = get_gross_instance($LabNumber);


?>


<style>
  .hidden {
    display: none;
}
    * {
  box-sizing: border-box;
}

*,*:after, *:before{
  -webkit-box-sizing: border-box;
  -moz-box-sizing: border-box;
  -ms-box-sizing: border-box;
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
  display: none;
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

#gross_description{
  width: 100%;
  height: 200px;
  border-radius: 10px;
  resize: none;
  padding: 10px;
  font-size: 20px;
  margin-bottom: 10px;
}

button{
  padding: 12px 20px;
  background: #0ea4da;
  border:0;
  border-radius: 5px;
  cursor: pointer;
  color: #fff;
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

<div>
    <?php 
    $specimens = get_gross_specimen_description($fk_gross_id);
    
    $lab_number = get_gross_specimens_list($LabNumber);
    $number_of_specimens = $lab_number[0]['number_of_specimens'];
    $alphabet_string = numberToAlphabet($number_of_specimens);
    print('<form method="post" action="gross_specimens_create.php">');
          foreach ($lab_number as $key => $specimen) {

            $button_id = 'click_to_convert' . $key;
            $text_area_id = 'gross_description' . $key;

            echo '<textarea  id="' . $text_area_id . '" name="gross_description[]" cols="60" rows="10" style="display: none;">';
            print('</textarea>');
            echo '<input type="hidden" name="specimen[]" value="' . $specimen['specimen'] . '">';
            $gross_instances = get_gross_instance($LabNumber);
            $current_gross_instance = array_shift($gross_instances);
            echo '<input type="hidden" name="fk_gross_id[]" value="' . $current_gross_instance['gross_id'] . '">';
            echo "<script>
                document.getElementById('$button_id').addEventListener('click', function(event) {
                  event.preventDefault();
                  var speech = true;
                  window.SpeechRecognition = window.webkitSpeechRecognition;
                  const recognition = new SpeechRecognition();
                  recognition.interimResults = true;
                  
                  recognition.addEventListener('result', e=>{
                      const transcript = Array.from(e.results).map(result => result[0]).map(result => result.transcript)
                      document.getElementById('$text_area_id').innerHTML = transcript;
                  })

                  if(speech == true){
                      recognition.start();
                  }
                });
                </script>";
        }
        print '<input type="submit" style="visibility: hidden;" value="Next">';
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

            document.getElementById('$text_area_id').addEventListener('input', function() {
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
        })
        .catch(error => console.error('Error loading shortcuts:', error));
</script>");
    ?>
</div>

<!-- 
<script>
document.getElementById('click_to_convert').addEventListener(
    'click',  function(event){
        event.preventDefault();
        var speech = true;
        window.SpeechRecognition = window.webkitSpeechRecognition;
        const recognition = new SpeechRecognition();
        recognition.interimResults = true;
        
        recognition.addEventListener('result', e=>{
            const transcript = Array.from(e.results).map(result => result[0]).map(result => result.transcript)
            document.getElementById('gross_description').innerHTML = transcript;
        })

        if(speech == true){
            recognition.start();
        }
    }
);
</script> -->




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
                    // Check if Enter key is pressed
                    if (event.key === 'Enter') {
                        // Prevent default behavior of Enter key (new line)
                        event.preventDefault();
                        // Submit the form containing the textarea
                        this.closest('form').submit();
                    }
                });
            });

           

            // Automatically click the "Next" button when the page loads
            document.querySelector('input[type="submit"]').click();
        })
        .catch(error => console.error('Error loading shortcuts:', error));
});
</script>

