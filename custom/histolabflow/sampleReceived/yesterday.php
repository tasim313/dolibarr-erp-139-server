<?php

include('../connection.php');
include('../common_function.php');
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
$langs->loadLangs(array("histolabflow@histolabflow"));

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

llxHeader("", $langs->trans("Specimen Received"));

$loggedInUsername = $user->login;
$loggedInUserId = $user->id;



$isAdmin = isUserAdmin($loggedInUserId);


// Access control using switch statement
switch (true) {
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
    <link href="../../grossmodule/bootstrap-3.4.1-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-datetimepicker/2.5.20/jquery.datetimepicker.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery-datetimepicker@2.5.20/build/jquery.datetimepicker.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
    <style>
        /* Flexbox for horizontal alignment */
        .horizontal-layout {
            display: flex;
            justify-content: flex-start; /* Align items to the left */
            gap: 15px; /* Add space between elements */
            align-items: center; /* Align vertically in the center */
        }
        .search-container .form-control {
            max-width: 200px; /* Limit input field width */
        }
    </style>
</head>
<body>
<div class="container mt-3">
    <h3>Yesterday Sample Received Information</h3>

    <!-- Horizontal Layout for Buttons and Date Range Form -->
    <div class="horizontal-layout mb-4">
        <!-- Buttons -->
        <a href="./index.php"><button class="btn btn-info">Home</button></a>
        <a href="./yesterday.php"><button class="btn btn-primary">Yesterday</button></a>

        <!-- Date Range Form -->
        <form id="date-range-form" class="horizontal-layout">
            <input type="text" id="start-date" class="form-control datetimepicker" placeholder="Select start date">
            <input type="text" id="end-date" class="form-control datetimepicker" placeholder="Select end date">
            <button id="date-range-submit-btn" type="submit" class="btn btn-primary">Submit</button>
        </form>
    </div>
</div>

<?php 
    $received_list = sample_received_list(null, null, 'yesterday');
	// Initialize arrays for counting test types and specimen names
    $testCounts = [];
    $specimenCounts = [];

    if (!empty($received_list) && is_array($received_list)) {
        foreach ($received_list as $row) {
            // Count the test types
            $testType = $row['test_type'] ?? 'Unknown'; // Default to 'Unknown' if 'test_type' is not set
            if (!isset($testCounts[$testType])) {
                $testCounts[$testType] = 0;
            }
            $testCounts[$testType]++;
        
            $specimens = $row['specimens'];

            preg_match_all('/"([^"]+)"/', $specimens, $matches);

            $decodedSpecimens = array_map(function($specimen) {
                return trim($specimen); // Trim any leading/trailing spaces
            }, $matches[1]);

            // Count the specimen names
            if (is_array($decodedSpecimens)) {
                foreach ($decodedSpecimens as $specimen) {
                    // Handle missing or invalid specimen names
                    $specimen = $specimen ?? 'Unknown';
                    
                    // Count each specimen occurrence
                    if (!isset($specimenCounts[$specimen])) {
                        $specimenCounts[$specimen] = 0;
                    }
                    $specimenCounts[$specimen]++;
                }
            }
        }
    } else {
        echo "No received samples found.";
    }
?>
<br>
<div class="container mt-4">
   
    <table class="table table-bordered table-striped">
        <thead>
        <tr>
            <th>Test Name</th>
            <th>Total Received</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!empty($testCounts)): ?>
            <?php foreach ($testCounts as $testName => $count): ?>
                <tr>
                    <td><?= htmlspecialchars($testName) ?></td>
                    <td><?= htmlspecialchars($count) ?></td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <th>Total</th>
                <th><?= array_sum($testCounts) ?></th>
            </tr>
        <?php else: ?>
            <tr>
                <td colspan="2" class="text-center">No data available for today.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Specimen Names Table -->
<div class="container mt-4">
    <h4>Specimen Names Count</h4>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Specimen Name</th>
                <th>Total Received</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($specimenCounts)): ?>
                <?php foreach ($specimenCounts as $specimenName => $count): ?>
                    <tr>
                        <td><?= htmlspecialchars($specimenName) ?></td>
                        <td><?= htmlspecialchars($count) ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <th>Total</th>
                    <th><?= array_sum($specimenCounts) ?></th>
                </tr>
            <?php else: ?>
                <tr>
                    <td colspan="2" class="text-center">No specimen data available for today.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
    $(document).ready(function(){
        // Initialize datetimepicker
        $(".datetimepicker").datetimepicker({
            timepicker: false,
            format: "Y-m-d"
        });

        // Form submission event
        $("#date-range-form").submit(function(event){
            event.preventDefault(); // Prevent the default form submission

            var startDate = $("#start-date").val();
            var endDate = $("#end-date").val();

            // If end date is not provided, set it to the current date
            if (!endDate) {
                endDate = new Date().toISOString().split("T")[0]; // Get current date in YYYY-MM-DD format
                $("#end-date").val(endDate); // Update the end date input field
            }

            // Validate dates
            if (!startDate || !endDate) {
                alert("Please select both start and end dates.");
            } else {
                // Construct the URL with query parameters
                var url = "http://192.168.1.139:8881/custom/histolabflow/sampleReceived/date_range.php?start_date=" + encodeURIComponent(startDate) + "&end_date=" + encodeURIComponent(endDate);
                
                // Redirect to the constructed URL
                window.location.href = url;
            }
        });
    });
</script>

</body>
</html>