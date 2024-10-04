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

$labNumber = isset($_GET['lab_number']) ? $_GET['lab_number'] : '';

$gross_created_user = $user->id;
$loggedInUsername = $user->login;

$isGrossAssistant = false;
$isDoctor = false;


$assistants = get_gross_assistant_list();
foreach ($assistants as $assistant) {
    if ($assistant['username'] == $loggedInUsername) {
        $isGrossAssistant = true;
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
    case $isGrossAssistant:
        // Gross Assistant has access, continue with the page content...
        break;
    case $isDoctor:
        // Doctor has access, continue with the page content...
        break;
    default:
        echo "<h1>Access Denied</h1>";
        echo "<p>You are not authorized to view this page.</p>";
        exit; // Terminate script execution
}



?>

<style>
    
    
    .content {
        margin-left: 200px;
        padding: 15px;
    }

    #lab_number:required {
        box-shadow: none; 
        border: 1px solid black;
    }

    #lab_number {
        font-size: 15px; 
        font-weight: bold;
        color: black;
    }

    #lab_number option {
        font-size: 18px; 
        font-weight: bold; 
        color: black;
    }

    #gross_doctor_name:required {
        box-shadow: none; 
        border: 1px solid black;
    }

    #gross_doctor_name {
        font-size: 15px; 
        font-weight: bold;
        color: black;
    }

    #gross_doctor_name option {
        font-size: 18px; 
        font-weight: bold; 
        color: black;
    }

    #gross_assistant_name:required {
        box-shadow: none; 
        border: 1px solid black;
    }

    #gross_assistant_name {
        font-size: 15px; 
        font-weight: bold;
        color: black;
    }

    #gross_assistant_name option {
        font-size: 18px; 
        font-weight: bold; 
        color: black;
    }
    
    #gross_status:required {
        box-shadow: none; 
        border: 1px solid black;
    }
    #gross_status {
        font-size: 15px; 
        font-weight: bold;
        color: black;
    }

    #gross_status option {
        font-size: 18px; 
        font-weight: bold; 
        color: black;
    }
    
    #gross_station_type {
        font-size: 15px; 
        font-weight: bold;
        color: black;
    }

    #gross_station_type option {
        font-size: 18px; 
        font-weight: bold; 
        color: black;
    }

    #gross_station_type:required {
        box-shadow: none; 
        border: 1px solid black;
    }

    input[type=text], select {
        width: 100%;
        padding: 12px 20px;
        margin: 8px 0;
        display: inline-block;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-sizing: border-box;
	}

	input[type=submit] {
        width: 100%;
        background-color: #4CAF50;
        color: white;
        padding: 14px 20px;
        margin: 8px 0;
        border: none;
        border-radius: 4px;
        cursor: pointer;
	}

	button[type=submit] {
        background-color: rgb(118, 145, 225);
        color: white;
        padding: 12px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        float: right;
        transition: box-shadow 0.3s ease;
    }
    button[type=submit]:hover {
         box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.5); 
    }

</style>

<div class="content">
    <h1>Gross</h1>
    <div>
      <form id="grossForm"  method="post" action="gross_create_function.php">


      <label for="gross_doctor_name">Doctor</label>
            <select id="gross_doctor_name" name="gross_doctor_name">
                <option value=""></option>
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
      
    
        <label for="gross_assistant_name">Gross Assistant</label>
            <select name="gross_assistant_name" id="gross_assistant_name">
                <option value=""></option>
                    <?php
                        $assistants = get_gross_assistant_list();
                        $loggedInUsername = $user->login; // Assuming $user is the object representing the logged-in user

                        foreach ($assistants as $assistant) {
                            $selected = '';
                            if ($assistant['username'] == $loggedInUsername) {
                                $selected = 'selected';
                                $storedAssistant = isset($_SESSION['gross_assistant_name']) && $_SESSION['gross_assistant_name'] === $assistant['username'] ? 'selected' : '';
                            }
                            echo "<option value='{$assistant['username']}' $selected>{$assistant['username']}</option>";
                        }
                    ?>
            </select>


        <label for="gross_station_type">Gross Station</label>
        <select name="gross_station_type" id="gross_station_type" required>
        <option value=""></option>
        <option value="One" <?php echo isset($_SESSION['gross_station_type']) && $_SESSION['gross_station_type'] === 'One' ? 'selected' : ''; ?>>One</option>
        <option value="Two" <?php echo isset($_SESSION['gross_station_type']) && $_SESSION['gross_station_type'] === 'Two' ? 'selected' : ''; ?>>Two</option>
        </select>


        <input type="text" id="searchLabNumber" placeholder="Insert Lab Number" onchange="selectLabNumber(this.value)">

            <label for="lab_number">Selected Lab Number</label>
            <select id="lab_number" name="lab_number" required>
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
        <br><br>
        <button type="submit">NEXT</button>
      </form>
    </div>
    </div>

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
        
        const storedAssistant = sessionStorage.getItem('gross_assistant_name');
        const storedStation = sessionStorage.getItem('gross_station_type');

        if (storedAssistant) {
            document.getElementById('gross_assistant_name').value = storedAssistant;
        }

        if (storedStation) {
            document.getElementById('gross_station_type').value = storedStation;
        }
    };

    
    document.getElementById('grossForm').addEventListener('submit', function(event) {
        const selectedAssistant = document.getElementById('gross_assistant_name').value;
        const selectedStation = document.getElementById('gross_station_type').value;

        sessionStorage.setItem('gross_assistant_name', selectedAssistant);
        sessionStorage.setItem('gross_station_type', selectedStation);
    });

    document.addEventListener("DOMContentLoaded", function() {
    var searchInput = document.getElementById("searchLabNumber");
    if (searchInput) {
        searchInput.focus(); 
    }
});
</script>


<?php 

// if (!$isGrossAssistant) { 

?>
        <!-- <label for="gross_doctor_name">Doctor</label>
            <select id="gross_doctor_name" name="gross_doctor_name">
                <option value=""></option> -->
                <?php
                // $doctors = get_doctor_list();
                // $loggedInUsername = $user->login; 

                // foreach ($doctors as $doctor) {
                //     $selected = '';
                //     if ($doctor['doctor_username'] == $loggedInUsername) {
                //         $selected = 'selected';
                //     }
                //     echo "<option value='{$doctor['doctor_username']}' $selected>{$doctor['doctor_username']}</option>";
                // }
                ?>
            <!-- </select> -->

<?php 
        // } 

?>


