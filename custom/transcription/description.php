<?php 
include('connection.php');
include('common_function.php');
include('../grossmodule/gross_common_function.php');
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


$title = $langs->trans("Transcription");
$help_url = '';
llxHeader('', $title, $help_url);
$loggedInUserId = $user->id;

$loggedInUsername = $user->login;

$fk_gross_id = $_GET['fk_gross_id'];
$LabNumber = get_lab_number($fk_gross_id);
$LabNumberWithoutPrefix = substr($LabNumber, 3);

print('<style>
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
box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.5);`
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

#description{
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
</style>');
?>


<div class="container">
<?php 
    $specimens_list = get_gross_specimens_list($LabNumberWithoutPrefix);
    $lab_number = $LabNumber;
    $created_user = $loggedInUsername;
    $status = "Done";
    
    $number_of_specimens = $specimens_list[0]['number_of_specimens'];
    
    $alphabet_string = numberToAlphabet($number_of_specimens);
    print('<div class="row">');
    print('<h4>Micro Description</h4>');
    print('<div class="col-25">');
    print('<label for="Specimen">Specimen</label>');
    print('</div>');
    print('<div class="col-75">');
    print("<p>".$alphabet_string."</p>");
    print('</div>');
    print('</div>');
    print('<form id="microDescriptionForm" method="post" action="micro_description_create.php">');
    foreach ($specimens_list as $key => $specimen) {

      $button_id = 'click_to_convert' . $key;
      $text_area_id = 'description' . $key;


      echo '<div class="row">';
     
    //   echo '<label for="specimen">'.$specimen['specimen'].'</label>';
    //   echo '<textarea id="' . $text_area_id . '" name="description[]" cols="60" rows="10" placeholder="Microscopic description" required></textarea>';
    //   echo '<label for="histologic_type">Histologic Type</label>';
    //   echo '<textarea name="histologic_type[]" cols="6" rows="1" placeholder="Histologic Type"></textarea>';
    //   echo '<label for="hitologic_grade">Histologic Grade</label>';
    //   echo '<textarea name="hitologic_grade[]" cols="6" rows="1" placeholder="Histologic Grade"></textarea>';
    //   echo '<label for="resection_margin">Resection Margin</label>';
    //   echo '<textarea name="resection_margin[]" cols="6" rows="1" placeholder="Resection Margin"></textarea>';
    //   echo '<label for="pattern_of_growth">Pattern Of Growth</label>';
    //   echo '<textarea name="pattern_of_growth[]" cols="6" rows="1" placeholder="Pattern Of Growth"></textarea>';
    //   echo '<label for="stromal_reaction">Stromal Reaction</label>';
    //   echo '<textarea name="stromal_reaction[]" cols="6" rows="1" placeholder="Stromal Reaction"></textarea>';
    //   echo '<label for="Lymphovascular_invasion">Lymphovascular Invasion</label>';
    //   echo '<textarea name="Lymphovascular_invasion[]" cols="6" rows="1" placeholder="Lymphovascular Invasion"></textarea>';
    //   echo '<label for="depth_of_invasion">Depth Of Invasion</label>';
    //   echo '<textarea name="depth_of_invasion[]" cols="6" rows="1" placeholder="Depth Of Invasion"></textarea>';
    //   echo '<label for="perineural_invasion">Perineural Invasion</label>';
    //   echo '<textarea name="perineural_invasion[]" cols="6" rows="1" placeholder="Perineural Invasion"></textarea>';
    //   echo '<label for="bone">Bone</label>';
    //   echo '<textarea name="bone[]" cols="6" rows="1" placeholder="Bone"></textarea>';
    //   echo '<label for="lim_node">Lim Node</label>';
    //   echo '<textarea name="lim_node[]" cols="6" rows="1" placeholder="Lim Node"></textarea>';
    //   echo '<label for="ptnm_title">Ptnm Title</label>';
    //   echo '<textarea name="ptnm_title[]" cols="6" rows="1" placeholder="Ptnm Title"></textarea>';
    //   echo '<label for="pt2">PT2</label>';
    //   echo '<textarea name="pt2[]" cols="6" rows="1" placeholder="PT2"></textarea>';
    //   echo '<label for="pnx">PNx</label>';
    //   echo '<textarea name="pnx[]" cols="6" rows="1" placeholder="pnx"></textarea>';
    //   echo '<label for="pmx">PMX</label>';
    //   echo '<textarea name="pmx[]" cols="6" rows="1" placeholder="pmx"></textarea>';
      echo '<input type="hidden" name="specimen[]" value="' . $specimen['specimen'] . '">';
      echo '<input type="hidden" name="fk_gross_id[]" value="' . $fk_gross_id . '">';
      echo '<input type="hidden" name="created_user[]" value="' . $created_user . '">';
      echo '<input type="hidden" name="status[]" value="' . $status . '">';
      echo '<input type="hidden" name="lab_number[]" value="' . $lab_number . '">';
      echo '</div>';
      
     
  }

    echo '<div class="row">';
    print '<br>';
    print '<input type="submit" id="microDescriptionSaveButton" value="Next" >';
    print '</div>';
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

<script>

document.addEventListener('DOMContentLoaded', function() {
  fetch('shortcuts.json')
      .then(response => response.json())
      .then(shortcuts => {
          document.querySelectorAll('textarea[name="description[]"]').forEach(textarea => {
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


