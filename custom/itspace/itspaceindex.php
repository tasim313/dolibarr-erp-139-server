<?php
include('connection.php');
include('common_function.php');

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
$langs->loadLangs(array("itspace@itspace"));

$action = GETPOST('action', 'aZ09');


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

$loggedInUserId = $user->id;
$loggedInUsername = $user->login;

llxHeader("", $langs->trans("Report Ready and Delivery"));

print load_fiche_titre($langs->trans(""), '', '');

print '<div class="fichecenter"><div class="fichethirdleft">';

// Fetching the lab number from the request (if needed for initialization)
$labnumber = isset($_POST['lab_number']) ? $_POST['lab_number'] : '';

// Fetch the status data
$lab_status = get_summary_list($labnumber);

// Prepare status data for JavaScript
$statuses = [];
foreach ($lab_status as $status) {
    $statuses[$status['status_name']] = true;
}



print("
<style>
	.container {
	display: flex;
	justify-content: space-between;
	align-items: center;
	float: left;
	background-color: #fff;
	border-radius: 1px;
	box-shadow: 0 1px 1px rgba(0, 0, 0, 0.1);
	padding: 20px;
	width: 600px;
	margin-left: auto; 
	margin-right: auto;
	}
	.input-field {
	border: 1px solid #ccc;
	border-radius: 4px;
	padding: 10px;
	width: 180px;
	font-size: 16px;
	outline: none;
	}
	.input-field:focus {
	border-color: dodgerblue;
	}
	.btn {
	background-color: dodgerblue;
	color: #fff;
	border: none;
	border-radius: 4px;
	padding: 10px 20px;
	cursor: pointer;
	font-size: 16px;
	outline: none;
	}
	.btn:hover {
	background-color: #007bff;
	}
	#histoGrossTable {
		width: 100%;
		border-collapse: collapse;
	}
	
	#histoGrossTable th, #histoGrossTable td {
		border: 1px solid #ddd;
		padding: 8px;
		text-align: left;
	}
	
	#histoGrossTable th {
		background-color: #046aaa;
		color: white;
	}

	/* Style the tab container */
	.tab-container {
		width: 100%;
	}

	/* Style the tabs */
	.tabs {
		display: flex;
		justify-content: center;
		margin-bottom: 20px;
	}

	/* Style the buttons inside the tab */
	.tabs .tablink {
		background-color: #f1f1f1;
		border: 2px solid #ccc;
		outline: none;
		cursor: pointer;
		padding: 10px;
		margin: 0 5px;
		width: 100px;
		height: 100px;
		border-radius: 100%;
		text-align: center;
		font-size: 14px; /* Adjust font size for better fit */
		transition: background-color 0.3s ease;
		display: flex;
		align-items: center;
		justify-content: center;
		overflow: hidden;
		white-space: pre-wrap; /* Allow text to wrap within the button */
		word-wrap: break-word; /* Break long words if necessary */
	}

	/* Change background color of buttons on hover */
	.tabs .tablink:hover {
		background-color: #ddd;
	}

	/* Create an active/current tablink class */
	.tabs .tablink.active {
		background-color: #ccc;
		border-color: #888;
	}

	/* Style the tab content */
	.tabcontent {
		display: none;
	}

	/* Style for the sub-tabs container */
	.sub-tabs {
		width: 100%;
		padding: 10px;
	}

	/* Style for the sub-tab links */
	.sub-tab-links {
		display: flex;
		justify-content: space-between;
		margin-bottom: 10px;
	}

	.sub-tab-links .sub-tablink {
		background-color: #f1f1f1;
		border: 2px solid #ccc;
		outline: none;
		cursor: pointer;
		padding: 10px;
		margin: 0 5px;
		width: 100px;
		height: 100px;
		border-radius: 100%;
		text-align: center;
		font-size: 14px; /* Adjust font size for better fit */
		transition: background-color 0.3s ease;
		display: flex;
		align-items: center;
		justify-content: center;
		overflow: hidden;
		white-space: pre-wrap; /* Allow text to wrap within the button */
		word-wrap: break-word; /* Break long words if necessary */
	}

	.sub-tab-links .sub-tablink:hover {
		background-color: #ddd;
	}

	.sub-tab-links .sub-tablink.active {
		background-color: #ccc;
		border-color: #888;
	}

	/* Style for the sub-tab controls (inputs and button) */
	.sub-tab-controls {
		display: flex;
		justify-content: center;
		align-items: center;
		gap: 10px; /* Space between input fields and button */
	}

	/* Style for sub-tab content */
	.subtabcontent {
		display: none; /* Hide all sub-tab contents by default */
		padding: 20px;
		border: 1px solid #ccc;
		border-radius: 5px;
	}

	/* Style for the active sub-tab */
	.sub-tablink.active {
		background-color: #ccc;
		border-color: #888;
	}

	/* Responsive styles */
	@media (max-width: 768px) {
		.tabs {
			flex-wrap: wrap;
		}
		.tabs .tablink {
			width: 60px; /* Adjust size for smaller screens */
			height: 60px;
			font-size: 12px; /* Adjust font size for smaller screens */
			line-height: normal;
		}
	}

</style>");

echo '<div class="tab-container">
    <!-- Tab Links -->
    <div class="tabs">
        <button style="border:none" class="tablink" onclick="openTab(event, \'ReportReadyInstructions\')">
            <i class="fa fa-envelope-o" style="font-size: 35px;"></i> Report Ready
        </button>
        
    </div>

    <!-- Tab Content for Report Ready Instructions -->
    <div id="ReportReadyInstructions" class="tabcontent" style="display:none;">
        <form id="reportReadyForm" action="insert/report_ready_submit.php" method="post">
            <!-- Report Ready Form -->
            <label for="lab_number_ready">Lab Number:</label>
            <input type="text" id="lab_number_ready" name="lab_number"><br><br>
            <input type="hidden" id="user_id_ready" name="user_id" value="' . $loggedInUserId . '">
            <input type="hidden" id="fk_status_id_ready" name="fk_status_id" value="11">
        </form>
    </div>
    
	<!-- Tab Content for Ready to Report -->
    <div id="ReadyToReport" class="tabcontent" style="display:none;">
        <p id="reportStatus"></p>
        <p id="reportLabNumber"></p>
    </div>
    
</div>';

print '</div><div class="fichetwothirdright">';



$NBMAX = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;
$max = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;



print '</div></div>';


// End of page
llxFooter();
$db->close();

?>

<script>
    function openTab(evt, tabName) {
        // Hide all tab content
        var tabcontent = document.getElementsByClassName("tabcontent");
        for (var i = 0; i < tabcontent.length; i++) {
            tabcontent[i].style.display = "none";
        }
        
        // Remove "active" class from all tab links
        var tablink = document.getElementsByClassName("tablink");
        for (var i = 0; i < tablink.length; i++) {
            tablink[i].className = tablink[i].className.replace(" active", "");
        }
        
        // Show the selected tab content and add "active" class to the clicked tab link
        document.getElementById(tabName).style.display = "block";
        evt.currentTarget.className += " active";

        // Save the selected tab to sessionStorage
        sessionStorage.setItem('selectedTab', tabName);

        // Autofocus the first input field in the opened tab
        var inputField = document.getElementById("lab_number_ready");
        if (inputField) {
            inputField.focus();
        }
    }

    // Fetch lab status from the server
    function fetchLabStatus(labNumber, callback) {
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "fetch_lab_status.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                var response = JSON.parse(xhr.responseText);
                callback(response);
            }
        };
        xhr.send("lab_number=" + encodeURIComponent(labNumber));
    }

    // Automatically handle lab_number_ready input change
    document.getElementById("lab_number_ready").onchange = function() {
        var labNumber = this.value;
        fetchLabStatus(labNumber, function(response) {
            var form = document.getElementById("reportReadyForm");
            if (form) {
                if (response.status_name === 'Diagnosis Completed' || response.fk_status_id === 11) {
                    // Use AJAX to submit the form and handle success response
                    var xhr = new XMLHttpRequest();
                    xhr.open("POST", form.action, true);
                    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                    xhr.onreadystatechange = function() {
                        if (xhr.readyState === 4 && xhr.status === 200) {
                            // Handle successful form submission
                            var reportStatusElement = document.getElementById("reportStatus");
                            var reportLabNumberElement = document.getElementById("reportLabNumber");
                            
                            // Show the "Ready to Report" tab
                            openTab({ currentTarget: document.querySelector('[onclick="openTab(event, \'ReadyToReport\')"]') }, 'ReadyToReport');
                            
                            // Update the new tab content
                            reportStatusElement.textContent = "Report is ready to be processed.";
                            reportLabNumberElement.textContent = "Lab Number: " + labNumber;
                        } else if (xhr.readyState === 4) {
                            // Handle error response
                            alert("An error occurred while submitting the form.");
                        }
                    };
                    // Serialize form data for submission
                    var formData = new URLSearchParams(new FormData(form)).toString();
                    xhr.send(formData);
                } else {
                    alert("Lab status must be 'Diagnosis Completed' to submit Report Ready.");
                }
            }
        });
    };

    // On page load, show the last opened tab
    window.onload = function() {
        var selectedTab = sessionStorage.getItem('selectedTab') || 'ReportReadyInstructions';  // Default to Report Ready tab
        document.getElementById(selectedTab).style.display = "block";  // Show the saved tab
        var defaultTab = document.querySelector('[onclick="openTab(event, \'' + selectedTab + '\')"]');
        if (defaultTab) {
            defaultTab.className += " active";
        }

        // Autofocus the input field if it exists
        var inputField = document.getElementById("lab_number_ready");
        if (inputField) {
            inputField.focus();
        }
    };
</script>


<!-- <button style="border:none" class="tablink" onclick="openTab(event, \'ReportDeliveredInstructions\')">
            <i class="fa fa-share" style="font-size: 35px;"></i> Report Delivered
        </button> -->
<!-- Tab Content for Report Delivered Instructions -->
<!-- <div id="ReportDeliveredInstructions" class="tabcontent" style="display:none;">
        <form id="reportDeliveredForm" action="insert/report_delivered_submit.php" method="post">
            <label for="lab_number_delivered">Lab Number:</label>
            <input type="text" id="lab_number_delivered" name="lab_number"><br><br>
            <input type="hidden" id="user_id_delivered" name="user_id" value="' . $loggedInUserId . '">
            <input type="hidden" id="fk_status_id_delivered" name="fk_status_id" value="13">
        </form>
    </div> -->


	<!-- // Automatically handle lab_number_delivered input change -->
    <!-- document.getElementById("lab_number_delivered").onchange = function() {
        var labNumber = this.value;
        fetchLabStatus(labNumber, function(response) {
            var form = document.getElementById("reportDeliveredForm");
            if (form) {
                if (response.status_name === 'Report Ready' || response.fk_status_id === 13) {
                    form.submit();
                } else {
                    alert("Lab status must be 'Report Ready' to submit Report Delivered.");
                }
            }
        });
    }; -->