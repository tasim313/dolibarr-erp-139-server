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

$host = $_SERVER['HTTP_HOST'];

$isAdmin = isUserAdmin($loggedInUserId);


// Access control using switch statement
switch (true) {
    case $isAdmin:
        // Admin has access, continue with the page content...
        break;
    
    default:
        echo "<h1 class='h1' style='color:red'>Access Denied</h1>";
        echo "<p>You are not authorized to view this page.</p>";
        exit; // Terminate script execution
} 

$start_date = $_GET['start_date'];
$end_date = $_GET['end_date'];

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
            justify-content: flex-start;
            gap: 15px;
            align-items: center;
        }

        .search-container .form-control {
            max-width: 200px;
        }

        /* Flexbox container for user groups */
        .user-group-list {
            display: flex;
            flex-wrap: wrap; /* Enable wrapping for multiple rows */
            gap: 15px; /* Space between items */
            list-style-type: none;
            padding: 0;
            margin: 0;
        }

        .user-group-list li {
            flex: 0 0 calc(16.66% - 15px); /* 6 items per row (16.66% width) */
            box-sizing: border-box;
            padding: 10px 20px;
            border-radius: 50px; /* Rounded shape */
            text-align: center;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 14px;
            color: #333;
        }

        .user-group-list li i {
            color: #007bff;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .user-group-list li {
                flex: 0 0 calc(33.33% - 15px); /* 3 items per row on tablets */
            }
        }

        @media (max-width: 576px) {
            .user-group-list li {
                flex: 0 0 calc(50% - 15px); /* 2 items per row on mobile */
            }
        }
   
    </style>
</head>
<body>
    <div class="container mt-3">
        <?php  echo "<h3 class='h1'> $start_date To $end_date  Summary Information</h3>"; ?>

        <!-- Horizontal Layout for Buttons and Date Range Form -->
        <div class="horizontal-layout mb-4">
            <!-- Buttons -->
            <a href="./index.php"><button class="btn btn-info">Home</button></a>
            <a href="./yesterday.php"><button class="btn btn-info">Yesterday</button></a>

            <!-- Date Range Form -->
            <form id="date-range-form" class="horizontal-layout">
                <input type="text" id="start-date" class="form-control datetimepicker" placeholder="Select start date">
                <input type="text" id="end-date" class="form-control datetimepicker" placeholder="Select end date">
                <button id="date-range-submit-btn" type="submit" class="btn btn-primary">Submit</button>
            </form>
        </div>
    </div>

    <div class="container mt-4">
        <h3>Departments of A I Khan Lab Ltd.</h3>
            <ul class="user-group-list">
                <?php
                // Display user groups
                $userGroups = get_user_groups();

                foreach ($userGroups as $group) {
                    $groupName = htmlspecialchars($group['nom']); // Sanitize the group name
                    $groupUrl = "http://" . $host . "/custom/histolabflow/List/date_range_group.php?group=" 
                            . urlencode($group['nom']) 
                            . "&start_date=" . urlencode($start_date) 
                            . "&end_date=" . urlencode($end_date);
                    echo '<li><i class="fas fa-users"></i><a href="' . $groupUrl . '">' . $groupName . '</a></li>';
                }
                ?>
            </ul>
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
                    var url = "http://" + hostname + ":8881/custom/histolabflow/List/date_range.php?start_date=" + encodeURIComponent(startDate) + "&end_date=" + encodeURIComponent(endDate);
                    
                    // Redirect to the constructed URL
                    window.location.href = url;
                }
            });
        });
    </script>
</body>
</html>