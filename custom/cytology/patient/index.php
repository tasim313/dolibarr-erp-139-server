<?php
include('../connection.php');
include('../../grossmodule/gross_common_function.php');

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
$langs->loadLangs(array("cytology@cytology"));

$action = GETPOST('action', 'aZ09');


$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

$max = 5;
$now = dol_now();


$form = new Form($db);
$formfile = new FormFile($db);

llxHeader("", $langs->trans("CytologyArea"));

print load_fiche_titre($langs->trans("CytologyArea"), '', 'cytology.png@cytology');

$loggedInUsername = $user->login;
$loggedInUserId = $user->id;

$isCytoAssistant = false;
$isDoctor = false;

$isAdmin = isUserAdmin($loggedInUserId);


$assistants = get_cyto_tech_list();
foreach ($assistants as $assistant) {
    if ($assistant['username'] == $loggedInUsername) {
        $isCytoAssistant = true;
        break;
    }
}

$doctors = get_doctor_list();
foreach ($doctors as $doctor) {
    if ($doctor['doctor_username'] == $loggedInUsername) {
        $isDoctor = true;
        break;
    }
}

// Access control using switch statement
switch (true) {
    case $isCytoAssistant:
        // cyto Assistant has access, continue with the page content...
        break;
    case $isDoctor:
        // Doctor has access, continue with the page content...
        break;
    case $isAdmin:
        // Admin has access, continue with the page content...
        break;
        
    default:
        echo "<h1>Access Denied</h1>";
        echo "<p>You are not authorized to view this page.</p>";
        exit; // Terminate script execution
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<!-- Import the JavaScript file -->
	<link href="../../grossmodule/bootstrap-3.4.1-dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
	
	<div class="container">
    	<h3>For New Patient </h3>
        <ul class="nav nav-tabs">
            <li class="active"><a href="">New</a></li>
            <li><a href="./recall.php" class="tab">Recall</a></li>
            <li><a href="./repeat.php" class="tab">Repeat</a></li>
        </ul>
	    
                <form style="margin-top: 20px;" id="cytoForm"  method="post" action="cyto_create_function.php">
                    <div class="row g-2">
                        <?php 
                            if (!$isCytoAssistant) { 

                        ?>
                            <div class="col-sm-2">
                                <div class="form-floating">
                                    <label for="doctor" class="form-label">Doctor</label>
                                    <select id="doctor_name" name="doctor_name" class="form-select form-select-lg mb-3" aria-label="Doctor selection">
                                        <option value="">Select a Doctor</option> 
                                        <?php
                                            $doctors = get_doctor_list();
                                            $loggedInUsername = $user->login; 

                                            foreach ($doctors as $doctor) {
                                                $selected = '';
                                                if ($doctor['doctor_username'] == $loggedInUsername) {
                                                    $selected = 'selected';
                                                }
                                                echo "<option value='{$doctor['doctor_username']}' $selected>{$doctor['doctor_username']}</option>";
                                            }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        <?php 
                            } 
                        ?>
                            <div class="col-sm-2">
                                <div class="form-floating">
                                    <label for="assistant" class="form-label">Assistant</label>
                                    <select id="assistant" name="assistant" class="form-select" aria-label="Assistant selection">
                                        <option value="">Select a Assistant</option> 
                                        <?php
                                            $assistants = get_cyto_tech_list();
                                            $loggedInUsername = $user->login; 

                                            foreach ($assistants as $assistant) {
                                                $selected = '';
                                                if ($assistant['username'] == $loggedInUsername) {
                                                    $selected = 'selected';
                                                    $storedAssistant = isset($_SESSION['cyto_assistant_name']) && $_SESSION['cyto_assistant_name'] === $assistant['username'] ? 'selected' : '';
                                                }
                                                echo "<option value='{$assistant['username']}' $selected>{$assistant['username']}</option>";
                                            }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-2">
                                <div class="form-floating">
                                        <label for="station" class="form-label">FNA Station</label>
                                        <select  id="cyto_station_type" class="form-select" required>
                                            <option value="">Select a Station</option> 
                                                <option value="One" <?php echo isset($_SESSION['cyto_station_type']) && $_SESSION['cyto_station_type'] === 'One' ? 'selected' : ''; ?>>One</option>
                                                <option value="Two" <?php echo isset($_SESSION['cyto_station_type']) && $_SESSION['cyto_station_type'] === 'Two' ? 'selected' : ''; ?>>Two</option>
                                        </select>
                                </div>
                            </div>

                            <div class="col-sm-2">
                                <div class="form-floating">
                                    <input type="text" id="searchLabNumber" class="form-control" placeholder="Lab Number" onchange="selectLabNumber(this.value)">
                                </div>
                            </div>
                            <div class="col-sm-2">
                                <div class="form-floating">
                                    <label for="lab_number" class="form-label">Selected Lab Number</label>
                                        <select id="lab_number" class="form-select"required>
                                            <option value=""></option>
                                                <?php
                                                    $labnumbers = get_labnumber_list();

                                                    foreach ($labnumbers as $labnumber) {
                                                        $selected = $labnumber['lab_number'] === $labNumber ? 'selected' : '';
                                                        echo "<option value='{$labnumber['lab_number']}' $selected>{$labnumber['lab_number']}</option>";
                                                    }
                                                ?>
                                        </select>
        
                                        <input type="hidden" id="gross_status" name="gross_status" value="Done">
                                        <input type="hidden" id="gross_created_user" name="gross_created_user" value="<?php echo $gross_created_user; ?>">
                                </div>
                            </div>
                            <div class="col-sm-2">
                                <button type="submit" class="btn btn-primary">Submit</button>
                            </div>
                            
                    </div>
                </form>
    </div>
</body>
</html>

<script>
    function selectLabNumber(searchText) {
        var select = document.getElementById('lab_number');
        var options = select.getElementsByTagName('option');

        for (var i = 0; i < options.length; i++) {
            var option = options[i];
            if (option.textContent.toLowerCase().indexOf(searchText.toLowerCase()) !== -1) {
                option.selected = true;
                break;
            }
        }
    }

    window.onload = function() {
        const storedAssistant = sessionStorage.getItem('cyto_assistant_name');
        const storedStation = sessionStorage.getItem('cyto_station_type');
        if (storedAssistant) {
            document.getElementById('cyto_assistant_name').value = storedAssistant;
        }
        if (storedStation) {
            document.getElementById('cyto_station_type').value = storedStation;
        }
    };

    
    document.getElementById('cytoForm').addEventListener('submit', function(event) {
        const selectedAssistant = document.getElementById('cyto_assistant_name').value;
        const selectedStation = document.getElementById('cyto_station_type').value;
        sessionStorage.setItem('gross_assistant_name', selectedAssistant);
        sessionStorage.setItem('gross_station_type', selectedStation);
    });

    document.addEventListener("DOMContentLoaded", function() {
            var searchInput = document.getElementById("searchLabNumber");
            if (searchInput) {
                searchInput.focus(); 
            }
        }
    );
</script>


<?php 
$NBMAX = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;
$max = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;


print '</div></div>';

// End of page
llxFooter();
$db->close();
?>