<?php 

include("../connection.php");
include("../gross_common_function.php");
include("./batch_common_function.php");

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

$title = $langs->trans("Gross Abbrevations Insert");
$help_url = '';
llxHeader('', $title, $help_url);

$loggedInUsername = $user->login;
$loggedInUserId = $user->id;

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

$isAdmin = isUserAdmin($loggedInUserId);

// Access control using switch statement
switch (true) {
    case $isAdmin:
        // Admin has access, continue with the page content...
        break;

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


$batch_list = cassettes_count_list();

// Filter the batch list to only include batches created today, yesterday, or tomorrow
$filtered_batch_list = array_filter($batch_list, function ($batch) {
    $created_date = new DateTime($batch['created_date']);
    $today = new DateTime();
    $yesterday = (new DateTime())->modify('-1 day')->setTime(0, 0); // Set to start of day
    $tomorrow = (new DateTime())->modify('+1 day')->setTime(0, 0); // Set to start of tomorrow

    // Check if the created date is today, yesterday, or tomorrow
    return ($created_date >= $yesterday && $created_date < $tomorrow) || $created_date->format('Y-m-d') === $today->format('Y-m-d');
});

$manual_processor_list = manual_processor_list();

?>



<!DOCTYPE html>
<html lang="en">
<head>
  <title></title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="../bootstrap-3.4.1-dist/css/bootstrap.min.css">
</head>
<body>

<div class="container">
    <h3>Batch Information For Auto Processor (YIDI)</h3>
        <ul class="nav nav-tabs">
            <li class="active"><a href="./index.php">Home</a></li>
            <li><a href="./details.php" class="tab">Details</a></li>
            <li><a href="./cassettes_number.php" class="tab">Cassettes Details</a></li>
            <li><a href="./cassettes_count.php" class="tab">Batch Cassettes Count</a></li>
            <li><a href="./auto_processor.php">Auto Processor (MYR)</a></li>
            <li><a href="./manual_processor.php">Auto Processor (YIDI)</a></li>
        </ul>
    <br>
        
</div>

<div class="container">
    <form action="./batch/add_manual_processor.php" method="POST">
        <div class="form-group">
            <label for="batchSelect">Select Batch</label>
            <select class="form-control" id="batchSelect" name="batch_name[]" multiple> <!-- Use `multiple` attribute -->
                <?php if (!empty($filtered_batch_list)): ?>
                    <?php foreach ($filtered_batch_list as $batch): ?>
                        <option value="<?php echo htmlspecialchars($batch['name']) . ' - ' . date('d F, Y', strtotime($batch['created_date'])); ?>">
                            <?php echo htmlspecialchars($batch['name']); ?> - 
                            <?php echo date('d F, Y', strtotime($batch['created_date'])); ?>
                        </option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <option>No batches available</option>
                <?php endif; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="additional-information">Additional Information</label>
            <textarea class="form-control" id="additional-information" name="description" rows="3"></textarea>
        </div>
        <input type="hidden" name="user_name" value="<?php echo htmlspecialchars($loggedInUsername); ?>">
        <button type="submit" class="btn btn-success">
            <i class="fas fa-play"></i> Start
        </button>
    </form>
</div>


<div class="container">
    <br>
    <input type="text" id="searchInput" placeholder="Search..." class="form-control" onkeyup="searchTable()">
    <br>
    <table class="table" id="manualProcessorTable">
            <thead>
                <tr>
                <th scope="col">Batch Name</th>
                <th scope="col">User Name</th>
                <th scope="col">Create Date Time</th>
                <th scope="col">Description</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($manual_processor_list  as $manual_processor): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($manual_processor['batch_name']); ?></td>
                        <td><?php echo htmlspecialchars($manual_processor['user_name']); ?></td>
                            <?php
                                // Create DateTime from UTC and set timezone to Asia/Dhaka
                                $dateTime = new DateTime($manual_processor['created_datetime'], new DateTimeZone('UTC'));
                                $dateTime->setTimezone(new DateTimeZone('Asia/Dhaka'));
                            ?>
                        <td><?php echo $dateTime->format('d F, Y h:i A'); ?></td>
                        <td><?php echo htmlspecialchars($manual_processor['description']); ?></td>
                        
                <?php endforeach; ?>
            </tbody>
    </table>
</div>


<script>
    function searchTable() {
        // Get the search input value
        const input = document.getElementById('searchInput');
        const filter = input.value.toLowerCase();
        const table = document.getElementById('manualProcessorTable');
        const rows = table.getElementsByTagName('tr');

        // Loop through all table rows (except the header)
        for (let i = 1; i < rows.length; i++) {
            const cells = rows[i].getElementsByTagName('td');
            let rowVisible = false;

            // Check each cell in the row for a match
            for (let j = 0; j < cells.length; j++) {
                if (cells[j].innerText.toLowerCase().includes(filter)) {
                    rowVisible = true;
                    break; // No need to check further cells
                }
            }

            // Toggle the visibility of the row based on the search
            rows[i].style.display = rowVisible ? '' : 'none';
        }
    }
</script>

</body>
</html>