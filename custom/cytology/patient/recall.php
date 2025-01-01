<?php
include('../connection.php');
include('../../grossmodule/gross_common_function.php');
include('../../transcription/common_function.php');
include('../common_function.php');

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



$loggedInUsername = $user->login;
$loggedInUserId = $user->id;

$isCytoAssistant = false;
$isDoctor = false;

$isAdmin = isUserAdmin($loggedInUserId);

$LabNumber = $_GET['LabNumber'];


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

$host = $_SERVER['HTTP_HOST'];
$homeUrl = "http://" . $host . "/custom/cytology/cytologyindex.php";
$reportUrl = "http://" . $host . "/custom/transcription/FNA/fna_report.php?LabNumber=" . urlencode($LabNumber) . "&username=" . urlencode($loggedInUsername);


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../../grossmodule/bootstrap-3.4.1-dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <title>Recall Patient</title>
    <style>
        .mt-3 {
            margin-top: 15px;
        }
    </style>
</head>
<body>
<button class="btn btn-primary" onclick="history.back()">Back</button>&nbsp; &nbsp;&nbsp;
<a href="<?= $homeUrl ?>" class="btn btn-info btn-md">New</a>
<div class="container mt-5">
    <h1 style="margin-bottom: 30px;">Recall Patient</h1>
    <form id="recallForm" method="post">
        <div class="form-group">
            <!-- Sample Quality Inadequate -->
            <div class="col-12 mb-3">
                <input type="checkbox" id="sample-quality-inadequate-checkbox" value="Sample Quality Inadequate">
                <b>Sample Quality Inadequate</b>
            </div>
            <!-- Wrong Site Collected -->
            <div class="col-12 mb-3">
                <input type="checkbox" id="wrong-site-collected-checkbox" value="Wrong Site Collected">
                <b>Wrong Site Collected</b>
            </div>
            <!-- Other Option -->
            <div class="col-12 mb-3">
                <input type="checkbox" id="final-recall-other-checkbox" value="Other">
                <b>Other</b>
            </div>
            <!-- Text Area for Other -->
            <div class="col-12 mb-3" id="final-recall-other-history" style="display: none;">
                <label for="final-recall-other-history-text">Please specify:</label>
                <textarea id="final-recall-other-history-text" class="form-control" rows="3"></textarea>
            </div>

			<div class="form-group">
				<!-- Notified Method -->
				<div class="col-12 mb-3" id="notified-method-section" style="margin-top: 30px;">
					<label><b>Notified Method</b></label><br>
					<input type="checkbox" id="notified_method_whatsapp" value="Whatsapp">
					<label for="notified_method_whatsapp">Whatsapp</label><br>
					
					<input type="checkbox" id="notified_method_by_call" value="By Call">
					<label for="notified_method_by_call">By Call</label><br>
					
					<input type="checkbox" id="notified_method_message" value="Message">
					<label for="notified_method_message">Message</label><br>
					
					<input type="checkbox" id="notified_method_face_to_face" value="Face-to-face conversations">
					<label for="notified_method_face_to_face">Face-to-face conversations</label><br>
				</div>
			</div>


			<div class="form-group">
				<!-- Follow-Up Date -->
				<div class="col-12 mb-3">
					<label for="follow_up_date"><b>Follow-Up Date</b></label>
					<input type="date" id="follow_up_date" class="form-control">
				</div>
			</div>
        </div>

        <div class="form-group mt-4">
            <button type="button" class="btn btn-primary" id="submitRecallForm">Submit</button>
        </div>
    </form>
</div>

<?php 

   // Remove the first three characters from the LabNumber
   $processedLabNumber = substr($LabNumber, 3);
   $recall = get_cyto_recall_management($processedLabNumber);

    // Get the recall data
	$recall = get_cyto_recall_management($processedLabNumber);

?>

<div class="container mt-5">
<h1 style="margin-top: 15px; margin-bottom: 30px;">History</h1>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                
                <th>Lab Number</th>
                <th>Recall Reason</th>
                <th>Notified User</th>
                <th>Notified Method</th>
                <th>Follow-Up Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recall as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['lab_number']); ?></td>
                    <td>
                        <?php
                        // Decode the nested recall_reason JSON
                        $recallReason = json_decode($row['recall_reason'], true);
                        if ($recallReason) {
                            foreach ($recallReason as $key => $entries) {
                                echo "<strong>" . htmlspecialchars($key) . ":</strong><br>";
                                foreach ($entries as $entry) {
                                    echo "Reasons: " . implode(", ", $entry['reason']) . "<br>";
                                    // Format the timestamp to Asia/Dhaka time zone
                        			$timestamp = new DateTime($entry['timestamp'], new DateTimeZone('UTC'));
                        			$timestamp->setTimezone(new DateTimeZone('Asia/Dhaka'));
                        			echo "Timestamp: " . $timestamp->format('d F, Y g:i A') . "<br>";  
                                }
                                echo "<br>";
                            }
                        } else {
                            echo "No Recall Reason";
                        }
                        ?>
                    </td>
                    <td><?php echo htmlspecialchars($row['notified_user'] ?? 'N/A'); ?></td>
					<td>
						<?php
							// Check if notified_method is a valid JSON string and decode it
							$notifiedMethod = isset($row['notified_method']) ? json_decode($row['notified_method'], true) : [];

							// If it's an array, implode to create a comma-separated string, otherwise show 'N/A'
							if (is_array($notifiedMethod) && !empty($notifiedMethod)) {
								echo htmlspecialchars(implode(', ', $notifiedMethod)); // Show as comma-separated values
							} else {
								echo 'N/A'; // If the array is empty or not set, show 'N/A'
							}
						?>
					</td>
                    <td>
						<?php
							// Format the follow_up_date to '24 December, 2024' in Asia/Dhaka timezone
							$date = new DateTime($row['follow_up_date'], new DateTimeZone('Asia/Dhaka'));
							echo $date->format('d F, Y'); // Output format: 24 December, 2024
						?>
					</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>


  
	<script>
			// Show/Hide "Other" text area
			$('#final-recall-other-checkbox').change(function () {
				if ($(this).is(':checked')) {
					$('#final-recall-other-history').show();
				} else {
					$('#final-recall-other-history').hide();
					$('#final-recall-other-history-text').val(''); // Clear textarea
				}
			});

			// Form Submission Logic
			$('#submitRecallForm').click(function () {
				const fullLabNumber = "<?php echo htmlspecialchars($LabNumber ?? ''); ?>";
				const labNumber = fullLabNumber.substring(3); 
				const username = "<?php echo htmlspecialchars($loggedInUsername ?? ''); ?>";
				const timestamp = new Date().toISOString();
				let reasons = [];
				let notifiedMethods = [];

				// Collect notified methods from checkboxes
				$('#notified-method-section input[type="checkbox"]:checked').each(function() {
					notifiedMethods.push($(this).val());
				});

				// Collect follow-up date
				let followUpDate = document.getElementById('follow_up_date').value; // Assuming you have an input with id="follow_up_date"

				// Collect reasons from checkboxes
				if ($('#sample-quality-inadequate-checkbox').is(':checked')) {
					reasons.push($('#sample-quality-inadequate-checkbox').val());
				}
				if ($('#wrong-site-collected-checkbox').is(':checked')) {
					reasons.push($('#wrong-site-collected-checkbox').val());
				}
				if ($('#final-recall-other-checkbox').is(':checked')) {
					const otherReason = $('#final-recall-other-history-text').val();
					if (otherReason.trim() !== '') {
						reasons.push(`Other: ${otherReason}`);
					}
				}

				// Basic Validation: Ensure all required fields are filled
				if (reasons.length === 0 || notifiedMethods.length === 0 || !followUpDate) {
					alert("Please fill out all required fields.");
					return; // Prevent form submission if data is missing
				}

				// Prepare Data for submission
				const data = {
					lab_number: labNumber,
					recalled_doctor: username,
					recall_reason: reasons,
					timestamp: timestamp,
					notified_method: notifiedMethods,
					follow_up_date: followUpDate
				};

				
				// AJAX to submit form
				$.ajax({
					url: '../Cyto/recall_save.php', // Replace with your server-side script
					type: 'POST',
					data: JSON.stringify(data),
					contentType: 'application/json',
					success: function (response) {
						alert(response.message); 
						window.location.reload();
					},
					error: function (xhr, status, error) {
						alert(`Error: ${error}`); // Add error handling based on the response
						window.location.reload();
					}
				});
			});
	</script>

	<!-- <script>
		// Show/Hide "Other" text area
$('#final-recall-other-checkbox').change(function () {
    if ($(this).is(':checked')) {
        $('#final-recall-other-history').show();
    } else {
        $('#final-recall-other-history').hide();
        $('#final-recall-other-history-text').val(''); // Clear textarea
    }
});

// Form Submission Logic
$('#submitRecallForm').click(function () {
    const fullLabNumber = "<?php echo htmlspecialchars($LabNumber ?? ''); ?>";
    const labNumber = fullLabNumber.substring(3);
    const username = "<?php echo htmlspecialchars($loggedInUsername ?? ''); ?>";
    const timestamp = new Date().toISOString();
    let reasons = [];
    let notifiedMethods = [];

    // Collect notified methods from checkboxes
    $('#notified-method-section input[type="checkbox"]:checked').each(function () {
        notifiedMethods.push($(this).val());
    });

    // Collect follow-up date
    let followUpDate = document.getElementById('follow_up_date').value;

    // Collect reasons from checkboxes
    if ($('#sample-quality-inadequate-checkbox').is(':checked')) {
        reasons.push($('#sample-quality-inadequate-checkbox').val());
    }
    if ($('#wrong-site-collected-checkbox').is(':checked')) {
        reasons.push($('#wrong-site-collected-checkbox').val());
    }
    if ($('#final-recall-other-checkbox').is(':checked')) {
        const otherReason = $('#final-recall-other-history-text').val();
        if (otherReason.trim() !== '') {
            reasons.push(`Other: ${otherReason}`);
        }
    }

    // Basic Validation
    if (reasons.length === 0 || notifiedMethods.length === 0 || !followUpDate) {
        alert("Please fill out all required fields.");
        return;
    }

    // Prepare Data for submission
    const data = {
        lab_number: labNumber,
        recalled_doctor: username,
        recall_reason: reasons,
        timestamp: timestamp,
        notified_method: notifiedMethods,
        follow_up_date: followUpDate
    };

    console.log('data', data);

    // AJAX to submit form
    $.ajax({
        url: '../Cyto/recall_save.php',
        type: 'POST',
        data: JSON.stringify(data),
        contentType: 'application/json',
        success: function (response) {
            console.log("response :", response);
            alert(response.message);
        },
        error: function (xhr, status, error) {
            alert(`Error: ${error}`);
        }
    });
});

	</script> -->

</body>
</html>

<?php 
$NBMAX = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;
$max = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;


print '</div></div>';

// End of page
llxFooter();
$db->close();
?>