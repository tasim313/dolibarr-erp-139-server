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

llxHeader("", $langs->trans("A I Khan Lab"));

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

// Check if 'group' parameter exists in the URL
$groupName = isset($_GET['group']) ? htmlspecialchars($_GET['group']) : 'Unknown Group';
$users = get_users_by_group($groupName);
$group_name = $_GET['group'];

$reception = reception_sample_received_list($start_date, $end_date);
$receptionJson = json_encode($reception);

$gross = gross_complete_list($start_date, $end_date);

$grossJson = json_encode($gross);

$worksheet_tracking = worksheet_tracking_list($start_date, $end_date);
$worksheetTrackingJson = json_encode($worksheet_tracking);

$transcription = transcription_complete_list($start_date, $end_date);
$transcriptionJson = json_encode($transcription);

$invoice = invoice_list($start_date, $end_date);
// Extract 'invoice_rowid' values from the $invoice array
$invoiceIds = array_column($invoice, 'invoice_rowid');

$payment = payment_list($invoiceIds, $start_date, $end_date, 'range');
$cyto_doctor_complete_case = cyto_doctor_complete_case($start_date, $end_date);
$cyto_doctor_complete_json = json_encode($cyto_doctor_complete_case);

$cyto_aspiration_list = cyto_doctor_aspiration_history($start_date, $end_date);
$cyto_aspiration_list_json = json_encode($cyto_aspiration_list);

$cyto_transcription_list = cyto_transcription_entery_list($start_date, $end_date);
$cyto_transcription_list_json = json_encode($cyto_transcription_list);

$cyto_slide_prepare_list = cyto_slide_prepared_list($start_date, $end_date);
$cyto_slide_prepare_list_json = json_encode($cyto_slide_prepare_list);

$cyto_special_instruction_list = cyto_special_list($start_date, $end_date);
$cyto_special_instruction_json = json_encode($cyto_special_instruction_list);

// echo('<pre>');
// var_dump($payment);
// echo('</pre>');

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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
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

        /* Ensure the container wraps the items into rows */
        .worksheet-details-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px; /* Adds spacing between items */
        }

        /* Define the size of each item so that 12 fit in a row */
        .worksheet-detail-item {
            flex: 0 0 calc(100% / 12 - 10px); /* 100% divided by 12 columns, minus gap space */
            box-sizing: border-box;
            margin-bottom: 10px; /* Adds space between rows */
        }

        /* Optional: Style for individual detail items */
        .worksheet-detail-item p {
            margin: 0;
            padding: 5px;
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
        <h3><?php echo  htmlspecialchars($groupName); ?></h3>

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
    <br>
    <div class="container mt-4">
        <!-- Display the total user count -->
        <h4 class="h4">Users: <?php echo count($users); ?></h4>

        <?php if (!empty($users)) : ?>
            <!-- Display the users in a table -->
            <table class="table table-bordered table-striped mt-3">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $index => $user) : ?>
                        <tr>
                            <td>
                                <a href="#" 
                                    class="username-link" 
                                    data-username="<?php echo htmlspecialchars($user['login']); ?>">
                                        <?php echo htmlspecialchars($user['login']); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($user['firstname']); ?></td>
                            <td><?php echo htmlspecialchars($user['lastname']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p>No users found in the group '<?php echo htmlspecialchars($groupName); ?>'.</p>
        <?php endif; ?>

        <!-- Tab container for displaying user details -->
        <div class="mt-4">
            <div id="userTabs" class="mt-4"></div>
        </div>
    </div>

    <div id="feature-message"></div>

    <script>
         var hostname = window.location.hostname;
    </script>

   <!-- Reception Data visualization -->
    <script>
        
        const receptionData = <?php echo $receptionJson; ?>;
        const grossdata = <?php echo $grossJson; ?>;
        const worksheetdata = <?php echo $worksheetTrackingJson; ?>;
        const transcriptiondata = <?php echo $transcriptionJson; ?>;
        const cytoDoctorCompletedata = <?php echo $cyto_doctor_complete_json; ?>;
        const cytoAspirationCompletedata = <?php echo $cyto_aspiration_list_json ?>;
        const cytoTranscriptiondata = <?php echo $cyto_transcription_list_json ?>;
        const cytoSlidePreparedata = <?php echo $cyto_slide_prepare_list_json ?>;
        const cytoSpecialInstructiondata = <?php echo $cyto_special_instruction_json ?>;

        // Handle the click event on username links
        document.querySelectorAll('.username-link').forEach(link => {
            link.addEventListener('click', function (event) {
                event.preventDefault();

                // Get the username from the data attribute
                const username = this.dataset.username;

                // Find all objects where author_login matches the username
                const userReceptions = receptionData.filter(reception => reception.author_login === username);

                // Find all objects where gross_doctor_name or gross_assistant_name  matches the username
                const normalizedUsername = username.trim().toLowerCase();
                const userGrossInformation = grossdata.filter(gross => {
                    const assistantNameMatch = gross.gross_assistant_name?.trim().toLowerCase() === normalizedUsername;
                    const doctorNameMatch = gross.gross_doctor_name?.trim().toLowerCase() === normalizedUsername;
                    return assistantNameMatch || doctorNameMatch;
                });

                // Find all objects where user_login matches the username
                const userWorksheetInformation = worksheetdata.filter(worksheet => worksheet.user_login?.trim().toLowerCase() === normalizedUsername);
                const userTranscriptionInformation = transcriptiondata.filter(transcription => transcription.created_user?.trim().toLowerCase() === normalizedUsername);

                // Filter cytoDoctorCompletedata based on username
                const userCytoDoctorData = cytoDoctorCompletedata.filter(entry => {
                    try {
                        // Parse JSON data from strings
                        const screeningData = entry.screening_done_count_data ? JSON.parse(entry.screening_done_count_data) : {};
                        const finalizationData = entry.finalization_done_count_data ? JSON.parse(entry.finalization_done_count_data) : {};

                        // Check if the username exists in either screening or finalization data
                        return screeningData.hasOwnProperty(username) || finalizationData.hasOwnProperty(username);
                    } catch (error) {
                        console.error("Error parsing JSON data:", error);
                        return false;
                    }
                });

                // Ensure JSON data exists before filtering
                // if (!Array.isArray(cytoAspirationCompletedata) || cytoAspirationCompletedata.length === 0) {
                //     console.error("cytoAspirationCompletedata is empty or not an array.");
                //     return;
                // }

                // Filter data based on doctor, assistant, or created_user field
                const userCytoAspirationData = cytoAspirationCompletedata.filter(entry => {
                    return entry.doctor === username || entry.created_user === username || entry.assistant === username;
                });

                const userCytoTranscriptionData = cytoTranscriptiondata.filter(entry =>{
                    return entry.created_user === username;
                });

                const userCytoSlidePrepareData = cytoSlidePreparedata.filter(entry =>{
                    return entry.created_user === username;
                });

                const userCytoSpecialInstructionData = cytoSpecialInstructiondata.filter(entry =>{
                    return entry.created_user === username;
                });
                
                // Check if there are any matches
                if (userReceptions.length > 0) {
                    // Process and display the data for the matched user
                    displayUserDetails(userReceptions);
                }
                if(userGrossInformation.length > 0){
                    // Process and display the data for the matched user
                    displayGrossDetails(userGrossInformation);
                }
                if(userWorksheetInformation.length >0){
                    // Process and display the data for the matched user
                    displayWorksheetDetails(userWorksheetInformation);
                }
                if(userTranscriptionInformation.length >0){
                    displayTranscriptionDetails(userTranscriptionInformation);
                }
                if (userCytoDoctorData.length > 0) {
                    displayCytoDoctorDetails(userCytoDoctorData, username);
                }
                if (userCytoAspirationData.length > 0) {
                    displayCytoAspirationDetails(userCytoAspirationData, username);
                }
                if(userCytoTranscriptionData.length >0){
                    displayCytoTranscriptionDetails(userCytoTranscriptionData, username)
                }
                if(userCytoSlidePrepareData.length >0){
                    displayCytoSlidePrepareDetails(userCytoSlidePrepareData, username)
                }
                if(userCytoSpecialInstructionData.length >0){
                    displayCytoSpecialInstructionDetails(userCytoSpecialInstructionData, username)
                }
                else {
                    console.log('No reception data found for ' + username);
                }
            });
        });

        function getStatusLabel(status) {
            switch (status) {
                case '1':
                    return 'Validated';
                case '0':
                    return 'Draft';
                case '-1':
                    return 'Cancel';
                case '3':
                    return 'Delivered';
                default:
                    return 'Unknown';
            }
        }

        function getBatchLabel(batchNumber) {
                const batchNames = [
                    "First Batch", 
                    "Second Batch", 
                    "Third Batch", 
                    "Fourth Batch", 
                    "Fifth Batch",
                    "Sixth Batch",
                    "Seventh Batch",
                    "Eighth Batch"
                ]; // Extend this array if more batches are needed
    
                return batchNames[batchNumber - 1] || `Batch ${batchNumber}`;
        }

        function formatTrackCreateTime(trackCreateTime) {
                // Check if TrackCreateTime is provided
                if (!trackCreateTime) {
                    return "Not Provided Date";
                }

                // Convert the timestamp string into ISO format
                // Assuming 'trackCreateTime' is in the format 'Y-m-d H:i:s.u'
                let isoFormattedTime = trackCreateTime.replace(' ', 'T'); // Convert to 'YYYY-MM-DDTHH:mm:ss'
                isoFormattedTime = isoFormattedTime + 'Z'; // Add 'Z' for UTC time (Optional: Adjust if needed)

                // Create a Date object from the ISO formatted time
                let date = new Date(isoFormattedTime);

                // Check if the date is invalid
                if (isNaN(date)) {
                    return "Not Provided Date";
                }

                // Format options for date and time
                let options = {
                    year: "numeric",
                    month: "long",
                    day: "numeric",
                    hour: "numeric",
                    minute: "numeric",
                    second: "numeric",
                    hour12: true,
                    timeZone: "Asia/Dhaka"  // Specify the Asia/Dhaka timezone
                };

                // Create the formatter
                let formatter = new Intl.DateTimeFormat("en-GB", options);

                // Format the date and return
                return formatter.format(date);
        }

        function formatWorksheetTime(trackCreateTime) {
            const dhakaDateTime = new Date(trackCreateTime);

            // Format the date and time using Intl.DateTimeFormat
            const formattedDateTime = dhakaDateTime.toLocaleString('en-US', {
                timeZone: 'Asia/Dhaka',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: 'numeric',
                minute: 'numeric',
                hour12: true
            });

            return formattedDateTime;
        }


        // Function to display the user's reception details
        function displayUserDetails(userReceptions) {
            // Create a new tab dynamically (if needed)
            const tab = document.createElement('div');
            tab.classList.add('user-tab', 'p-3', 'mb-3', 'border', 'position-relative');
            tab.style.borderRadius = '5px';

            // Calculate total received count and counts for each test_type
            const totalReceived = userReceptions.length;
            const testTypeCount = userReceptions.reduce((acc, reception) => {
                acc[reception.test_type] = (acc[reception.test_type] || 0) + 1;
                return acc;
            }, {});

            // Format the dates for each userReception
            const formattedUserReceptions = userReceptions.map(userReception => {
                return {
                    dateLivraison: new Date(userReception.date_livraison).toLocaleString('en-BD', {
                        timeZone: 'Asia/Dhaka',
                        day: '2-digit', month: 'short', year: 'numeric',
                        hour: '2-digit', minute: '2-digit', hour12: true
                    }),
                    dateCreation: new Date(userReception.date_creation).toLocaleString('en-BD', {
                        timeZone: 'Asia/Dhaka',
                        day: '2-digit', month: 'short', year: 'numeric',
                        hour: '2-digit', minute: '2-digit', hour12: true
                    }),
                    authorLogin: userReception.author_login,
                    testType: userReception.test_type,
                    ref: userReception.ref,
                    notePublic: userReception.note_public,
                    statusLabel: getStatusLabel(userReception.fk_statut)
                };
            });

            // Insert the content into the tab
            tab.innerHTML = `
                <span class="position-absolute top-0 end-0 m-2 text-danger" style="cursor: pointer;" aria-label="Remove Tab" onclick="closeTab()">
                    <i class="bi bi-trash"></i>
                </span>
                <h1>${userReceptions[0].author_login}</h1>
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Total Received: ${totalReceived}</h5>
                        <ul>
                            ${Object.entries(testTypeCount).map(([testType, count]) => `<li>${testType}: ${count}</li>`).join('')}
                        </ul>
                        
                    </div>
                </div>
                
                <h5>Reception Details:</h5>
                ${formattedUserReceptions.map(reception => `
                    <div class="card my-2">
                        <div class="card-body">
                            <div style="display: flex; justify-content: space-between;">
                                <div><strong>Lab Number:</strong> ${reception.ref}</div>
                                <div><strong>Test Type:</strong> ${reception.testType}</div>
                                <div><strong>Delivery Date:</strong> ${reception.dateLivraison}</div>
                                <div><strong>Created At:</strong> ${reception.dateCreation}</div>
                                <div><strong>Note:</strong> ${reception.notePublic}</div>
                                <div><strong>Status:</strong> ${reception.statusLabel}</div>
                            </div>
                        </div>
                    </div>
                `).join('')}
            `;

            // Append the tab to the container
            document.getElementById('userTabs').appendChild(tab);
        }
        
        // Gross Information Visualization 
        function displayGrossDetails(userGrossInformation) {
            // Create a new tab dynamically (if needed)
            const tab = document.createElement('div');
            tab.classList.add('user-tab', 'p-3', 'mb-3', 'border', 'position-relative');
            tab.style.borderRadius = '5px';

            // Calculate unique gross station types
            const uniqueGrossStations = Array.from(
                new Set(userGrossInformation.map(info => info.gross_station_type))
            );

            // Count gross station occurrences
            const grossStationCount = uniqueGrossStations.length;

            // Collect gross assistant names as a list
            const grossAssistantNames = Array.from(
                new Set(userGrossInformation.map(info => info.gross_assistant_name))
            );

            // Calculate the total count of all lab numbers
            const totalLabNumbers = userGrossInformation.length;

            // Group by batch and sort by creation time
            const groupedByBatch = userGrossInformation.reduce((acc, info) => {
                acc[info.batch] = acc[info.batch] || [];
                acc[info.batch].push(info);
                return acc;
            }, {});

            // Track how many times each assistant has grossed with and without a doctor
            const assistantCounts = {};

            userGrossInformation.forEach(info => {
                const assistant = info.gross_assistant_name;
                const doctor = info.gross_doctor_name;
                
                // Ensure we are only counting when assistant name is present
                if (assistant) {
                    // Initialize the assistant's entry if not already present
                    if (!assistantCounts[assistant]) {
                        assistantCounts[assistant] = { withDoctor: 0, withoutDoctor: 0 };
                    }

                    // Check if a doctor is associated, count accordingly
                    if (doctor && doctor.trim() !== "") {
                        assistantCounts[assistant].withDoctor++;
                    } else {
                        assistantCounts[assistant].withoutDoctor++;
                    }
                }
            });

            // Format and show the data for grouped batches
            const formattedData = Object.entries(groupedByBatch).map(([batch, entries]) => `
                <div class="card my-2">
                    <div class="card-body">
                        <h5><strong>Batch:</strong> ${getBatchLabel(batch)}</h5>
                        <ul>
                            ${entries.map(entry => {
                                // Use the existing formatTrackCreateTime function to format the time for gross_create_date
                                const formattedTime = formatTrackCreateTime(entry.gross_create_date); // Call the function to format the time

                                return `
                                    <li>
                                        <strong>Time:</strong> ${formattedTime},
                                        <strong>Lab Number:</strong> ${entry.lab_number}
                                    </li>
                                `;
                            }).join('')}
                        </ul>
                    </div>
                </div>
            `).join('');

            // Insert the content into the tab
            tab.innerHTML = `
                <span class="position-absolute top-0 end-0 m-2 text-danger" style="cursor: pointer;" aria-label="Remove Tab" onclick="closeTab()">
                    <i class="bi bi-trash"></i>
                </span>
                <h1>${userGrossInformation[0].gross_doctor_name || 'No Doctor Assigned'}</h1>
                <h5>Gross Information</h5>
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Total Gross : ${totalLabNumbers}</h5>
                        <h5 class="card-title">Gross Station:</h5>
                        <ul>
                            ${uniqueGrossStations.map(station => `<li>${station}</li>`).join('')}
                        </ul>
                        <h5 class="card-title">Gross Assistant Names:</h5>
                        <ul>
                            ${grossAssistantNames.map(name => {
                                const { withDoctor, withoutDoctor } = assistantCounts[name] || { withDoctor: 0, withoutDoctor: 0 };
                                return `<li>${name} - With Doctor: ${withDoctor}, Without Doctor: ${withoutDoctor}</li>`;
                            }).join('')}
                        </ul>
                    </div>
                </div>
                <h5>Batch: ${formattedData}</h5>
            `;

            // Append the tab to the container
            document.getElementById('userTabs').appendChild(tab);
        }

        function displayWorksheetDetails(userWorksheetInformation) {
                // Create a new tab dynamically
                const tab = document.createElement('div');
                tab.classList.add('user-tab', 'p-3', 'mb-3', 'border', 'position-relative');
                tab.style.borderRadius = '5px';

                // Create a container to hold the content
                let content = '';

                // Count occurrences of each unique status_name
                const statusCount = userWorksheetInformation.reduce((acc, entry) => {
                    const statusName = entry.status_name?.trim(); // Get the status name
                    if (statusName) {
                        acc[statusName] = acc[statusName] || 0;
                        acc[statusName]++;
                    }
                    return acc;
                }, {});

                // Format the status names and counts
                const statusNames = Object.entries(statusCount).map(([status, count]) => `
                    <li>${status} (Total : ${count})</li>
                `).join('');

                // Display the status names and counts
                content += `
                    <h5>Status Summary:</h5>
                    <ul>
                        ${statusNames}
                    </ul>
                `;

                // Format and show create time and description, if available
                const createTimeAndDescription = userWorksheetInformation.map(entry => {
                    const createTime = entry.create_time ? formatWorksheetTime(entry.create_time) : 'N/A'; // Format the create time
                    const description = entry.description ? `<strong>Description:</strong> ${entry.description}` : ''; // Check for description
                    const labRoomStatus = entry.lab_room_status ? `<strong>Lab Room Status:</strong> ${entry.lab_room_status}` : ''; // Check for lab room status
                    const labNo = entry.labno ? `<strong>Lab No:</strong> ${entry.labno}` : 'N/A'; // Check for labno

                    return `
                        <div class="worksheet-detail-item">
                            <p><strong>Time:</strong> ${createTime}</p>
                            ${description}
                            ${labRoomStatus}
                            <p>${labNo}</p>
                        </div>
                    `;
                }).join('');

                // Display the create time and description cards in horizontal layout
                content += `
                    <h5>Details:</h5>
                    <div class="worksheet-details-container">
                        ${createTimeAndDescription}
                    </div>
                `;

                const userLogin = userWorksheetInformation[0]?.user_login || 'N/A';

                // Insert the content into the tab
                tab.innerHTML = `
                    <span class="position-absolute top-0 end-0 m-2 text-danger" style="cursor: pointer;" aria-label="Remove Tab" onclick="closeTab()">
                        <i class="bi bi-trash"></i>
                    </span>
                    <h1>${userLogin}</h1>
                    <h1>Worksheet Details</h1>
                    ${content}
                `;

                // Append the tab to the container
                document.getElementById('userTabs').appendChild(tab);
        }

        function displayTranscriptionDetails(userTranscriptionInformation) {
            // Ensure the userTabs container exists
            const userTabs = document.getElementById('userTabs');
            if (!userTabs) {
                console.error('User tabs container not found in the DOM.');
                return;
            }

            // Create a new tab dynamically
            const tab = document.createElement('div');
            tab.classList.add('user-tab', 'p-3', 'mb-3', 'border', 'position-relative');
            tab.style.borderRadius = '5px';

            // Build the tab content dynamically
            const user = userTranscriptionInformation[0]?.created_user || 'Unknown User';

            // Use a Set to track unique Lab Number and Time combinations
            const uniqueEntries = new Set();

            // Collect unique entries
            const uniqueRecords = userTranscriptionInformation.filter(record => {
                const labNumber = record.lab_number || 'N/A';
                const createDate = record.create_date ? formatTrackCreateTime(record.create_date) : 'N/A';
                const uniqueKey = `${labNumber}-${createDate}`;
                if (!uniqueEntries.has(uniqueKey)) {
                    uniqueEntries.add(uniqueKey);
                    return true; // Include in the filtered list
                }
                return false; // Exclude duplicate entries
            });

            const totalCount = uniqueRecords.length; // Count only unique records

            // Start the table structure
            let content = `
                <h5>Total Micro Entry: ${totalCount}</h5>
                <table class="table table-bordered table-striped mt-3">
                    <thead>
                        <tr>
                            <th>Lab Number</th>
                            <th>Times</th>
                            <th>Lab Number</th>
                            <th>Times</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            // Add unique records to the table with alternating layout
            for (let i = 0; i < uniqueRecords.length; i += 2) {
                const record1 = uniqueRecords[i];
                const record2 = uniqueRecords[i + 1] || {}; // Handle odd number of records

                const labNumber1 = record1.lab_number || 'N/A';
                const createDate1 = record1.create_date ? formatTrackCreateTime(record1.create_date) : 'N/A';

                const labNumber2 = record2.lab_number || 'N/A';
                const createDate2 = record2.create_date ? formatTrackCreateTime(record2.create_date) : 'N/A';

                content += `
                    <tr>
                        <td>${labNumber1}</td>
                        <td>${createDate1}</td>
                        <td>${labNumber2}</td>
                        <td>${createDate2}</td>
                    </tr>
                `;
            }

            content += `
                    </tbody>
                </table>
            `;

            // Add close button and user-transcription content
            tab.innerHTML = `
                <span class="position-absolute top-0 end-0 m-2 text-danger" style="cursor: pointer;" aria-label="Remove Tab" onclick="closeTab(this)">
                    <i class="bi bi-trash"></i>
                </span>
                <h1>Transcription Details</h1>
                <h1>${user}</h1>
                ${content}
            `;

            // Append the tab to the userTabs container
            userTabs.appendChild(tab);
        }

        function displayCytoDoctorDetails(userCytoDoctorData, username) {
            const userTabs = document.getElementById('userTabs');
            if (!userTabs) {
                console.error('User tabs container not found in the DOM.');
                return;
            }

            // Create a new tab dynamically
            const tab = document.createElement('div');
            tab.classList.add('user-tab', 'p-3', 'mb-3', 'border', 'position-relative');
            tab.style.borderRadius = '5px';

            // Objects to store categorized lab numbers
            const screeningLabs = new Set();
            const finalizationLabs = new Set();
            const uniqueLabNumbers = new Set(); // Track all unique lab numbers

            // Iterate over user data and categorize lab numbers
            userCytoDoctorData.forEach(entry => {
                try {
                    const screeningData = entry.screening_done_count_data ? JSON.parse(entry.screening_done_count_data) : {};
                    const finalizationData = entry.finalization_done_count_data ? JSON.parse(entry.finalization_done_count_data) : {};

                    const labNumber = entry.lab_number;

                    if (screeningData.hasOwnProperty(username)) {
                        screeningLabs.add(labNumber);
                        uniqueLabNumbers.add(labNumber); // Add to unique count
                    }
                    if (finalizationData.hasOwnProperty(username)) {
                        finalizationLabs.add(labNumber);
                        uniqueLabNumbers.add(labNumber); // Add to unique count
                    }
                } catch (error) {
                    console.error("Error parsing JSON data:", error);
                }
            });

            // Extract username properly
            const user = username || 'Unknown User';

            // Start building the content for the tab
            let content = `<h2>${user}</h2>`;
            content += `<h5>Total Lab Numbers: ${uniqueLabNumbers.size}</h5>`; // Use unique count
            content += `<h5>Screening Done: ${screeningLabs.size}</h5>`; // Screening count
            content += `<h5>Finalization Done: ${finalizationLabs.size}</h5>`; // Finalization count

            if (screeningLabs.size > 0) {
                content += `<h5>Screening Done</h5><ul>`;
                screeningLabs.forEach(lab => {
                    content += `<li>${lab}</li>`;
                });
                content += `</ul>`;
            }

            if (finalizationLabs.size > 0) {
                content += `<h5>Finalization Done</h5><ul>`;
                finalizationLabs.forEach(lab => {
                    content += `<li>${lab}</li>`;
                });
                content += `</ul>`;
            }

            tab.innerHTML = `
                <span class="position-absolute top-0 end-0 m-2 text-danger" style="cursor: pointer;" aria-label="Remove Tab" onclick="closeTab(this)">
                    <i class="bi bi-trash"></i>
                </span>
                <h1>Cyto</h1>
                ${content}
            `;

            userTabs.appendChild(tab);
        }

        function displayCytoAspirationDetails(userCytoAspirationData, username) {
                const userTabs = document.getElementById('userTabs');
                if (!userTabs) {
                    console.error('User tabs container not found in the DOM.');
                    return;
                }

                // Create a new tab dynamically
                const tab = document.createElement('div');
                tab.classList.add('user-tab', 'p-3', 'mb-3', 'border', 'position-relative');
                tab.style.borderRadius = '5px';

                // Objects to store categorized data
                const labNumbers = new Set();
                const doctorCounts = {};
                const assistantCounts = {};

                // Iterate over user data and categorize values
                userCytoAspirationData.forEach(entry => {
                    labNumbers.add(entry.lab_number); // Track unique lab numbers

                    // Count doctor occurrences
                    if (entry.doctor) {
                        doctorCounts[entry.doctor] = (doctorCounts[entry.doctor] || 0) + 1;
                    }

                    // Count assistant occurrences
                    if (entry.assistant) {
                        assistantCounts[entry.assistant] = (assistantCounts[entry.assistant] || 0) + 1;
                    }
                });

                // Extract username properly
                const user = username || 'Unknown User';

                // Start building the content for the tab
                let content = `<h2>${user}</h2>`;
                content += `<h5>Total Lab Numbers: ${labNumbers.size}</h5>`; 

                if (labNumbers.size > 0) {
                    content += `<h5>Lab Numbers</h5><ul>`;
                    labNumbers.forEach(lab => {
                        content += `<li>${lab}</li>`;
                    });
                    content += `</ul>`;
                }

                if (Object.keys(doctorCounts).length > 0) {
                    content += `<h5>Doctors Involved</h5><ul>`;
                    for (const [doctor, count] of Object.entries(doctorCounts)) {
                        content += `<li>${doctor} (Count: ${count})</li>`;
                    }
                    content += `</ul>`;
                }

                if (Object.keys(assistantCounts).length > 0) {
                    content += `<h5>Assistants Involved</h5><ul>`;
                    for (const [assistant, count] of Object.entries(assistantCounts)) {
                        content += `<li>${assistant} (Count: ${count})</li>`;
                    }
                    content += `</ul>`;
                }

                tab.innerHTML = `
                    <span class="position-absolute top-0 end-0 m-2 text-danger" style="cursor: pointer;" aria-label="Remove Tab" onclick="closeTab(this)">
                        <i class="bi bi-trash"></i>
                    </span>
                    <h1>Cyto Aspiration</h1>
                    ${content}
                `;

                userTabs.appendChild(tab);
        }

        function displayCytoTranscriptionDetails(userCytoTranscriptionData, username) {
            const userTabs = document.getElementById('userTabs');
            if (!userTabs) {
                console.error('User tabs container not found in the DOM.');
                return;
            }

            // Create a new tab dynamically
            const tab = document.createElement('div');
            tab.classList.add('user-tab', 'p-3', 'mb-3', 'border', 'position-relative');
            tab.style.borderRadius = '5px';

            // Object to store unique lab numbers
            const labNumbers = new Set();

            // Iterate over user data and collect lab numbers
            userCytoTranscriptionData.forEach(entry => {
                labNumbers.add(entry.lab_number); // Track unique lab numbers
            });

            // Extract username properly
            const user = username || 'Unknown User';

            // Start building the content for the tab
            let content = `<h2>${user}</h2>`;
            content += `<h5>Total Lab Numbers: ${labNumbers.size}</h5>`;

            if (labNumbers.size > 0) {
                content += `<h5>Lab Numbers</h5><ul>`;
                labNumbers.forEach(lab => {
                    content += `<li>${lab}</li>`;
                });
                content += `</ul>`;
            }

            tab.innerHTML = `
                <span class="position-absolute top-0 end-0 m-2 text-danger" style="cursor: pointer;" aria-label="Remove Tab" onclick="closeTab(this)">
                    <i class="bi bi-trash"></i>
                </span>
                <h1>Cyto Transcription</h1>
                ${content}
            `;

            userTabs.appendChild(tab);
        }

        function displayCytoSlidePrepareDetails(userCytoSlidePrepareData, username) {
            const userTabs = document.getElementById('userTabs');
            if (!userTabs) {
                console.error('User tabs container not found in the DOM.');
                return;
            }

            // Create a new tab dynamically
            const tab = document.createElement('div');
            tab.classList.add('user-tab', 'p-3', 'mb-3', 'border', 'position-relative');
            tab.style.borderRadius = '5px';

            // Object to store unique lab numbers
            const labNumbers = new Set();

            // Iterate over user data and collect lab numbers
            userCytoSlidePrepareData.forEach(entry => {
                labNumbers.add(entry.lab_number); // Track unique lab numbers
            });

            // Extract username properly
            const user = username || 'Unknown User';

            // Start building the content for the tab
            let content = `<h2>${user}</h2>`;
            content += `<h5>Total Lab Numbers: ${labNumbers.size}</h5>`;

            if (labNumbers.size > 0) {
                content += `<h5>Lab Numbers</h5><ul>`;
                labNumbers.forEach(lab => {
                    content += `<li>${lab}</li>`;
                });
                content += `</ul>`;
            }

            tab.innerHTML = `
                <span class="position-absolute top-0 end-0 m-2 text-danger" style="cursor: pointer;" aria-label="Remove Tab" onclick="closeTab(this)">
                    <i class="bi bi-trash"></i>
                </span>
                <h1>Cyto Slide Prepare</h1>
                ${content}
            `;

            userTabs.appendChild(tab);
        }

        function displayCytoSpecialInstructionDetails(userCytoSpec custom/histolabflow/List/group.phpialInstructionData, username){
            const userTabs = document.getElementById('userTabs');
            if (!userTabs) {
                console.error('User tabs container not found in the DOM.');
                return;
            }

            // Create a new tab dynamically
            const tab = document.createElement('div');
            tab.classList.add('user-tab', 'p-3', 'mb-3', 'border', 'position-relative');
            tab.style.borderRadius = '5px';

            // Object to store unique lab numbers
            const labNumbers = new Set();

            // Iterate over user data and collect lab numbers
            userCytoSpecialInstructionData.forEach(entry => {
                labNumbers.add(entry.lab_number); // Track unique lab numbers
            });

            // Extract username properly
            const user = username || 'Unknown User';

            // Start building the content for the tab
            let content = `<h2>${user}</h2>`;
            content += `<h5>Total Lab Numbers: ${labNumbers.size}</h5>`;

            if (labNumbers.size > 0) {
                content += `<h5>Lab Numbers</h5><ul>`;
                labNumbers.forEach(lab => {
                    content += `<li>${lab}</li>`;
                });
                content += `</ul>`;
            }

            tab.innerHTML = `
                <span class="position-absolute top-0 end-0 m-2 text-danger" style="cursor: pointer;" aria-label="Remove Tab" onclick="closeTab(this)">
                    <i class="bi bi-trash"></i>
                </span>
                <h1>Cyto Special Instruction Complete</h1>
                ${content}
            `;

            userTabs.appendChild(tab);
        }


        // Function to close a tab
        function closeTab() {
            const tab = event.target.closest('.user-tab');
            if (tab) {
                tab.remove();
            }
        }

    </script>
    
    <!-- JavaScript to show the message if the group name is "Accounting" -->
    <script>
        var groupName = "<?php echo htmlspecialchars($group_name); ?>";
        // Check if the group name is "Accounting"
        if (groupName === "Accounting") {
            // Dynamically inject the HTML content for the table
            document.body.innerHTML += `
                <div class="container mt-4">
                    <!-- Totals Section -->
                    <div id="totalsSection" class="table-responsive mb-4">
                            <table class="table table-bordered text-center">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Total Due</th>
                                        <th>Total Due Collection(Previous Sample Collection)</th>
                                        <th>Total Amount Received(Yesterday Sample Collection)</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td id="totalDue">0</td>
                                        <td id="totalDueCollection">0</td>
                                        <td id="totalAmountReceived">0</td>
                                        <td id="total">0</td>
                                    </tr>
                                </tbody>
                            </table>
                    </div>

                    <!-- Search Filter -->
                    <div style="margin-bottom:20px;">
                        <input type="text" id="searchInput" class="form-control mb-3" placeholder="Search ...">
                    </div>

                    <!-- Table -->
                    <table class="table table-striped table-bordered">
                        <thead class="thead-dark">
                            <tr>
                                <th>Payment Row ID</th>
                                <th>Payment Ref</th>
                                <th>Payment Amount</th>
                                <th>Payment Date</th>
                                <th>pf_rowid</th>
                                <th>fk_payment</th>
                                <th>fk_invoice</th>
                                <th>allocated_amount</th>
                                <th>Invoice Ref</th>
                                <th>invoice_date_created</th>
                                <th>Invoice Due Date</th>
                                <th>invoice_validation_date</th>
                                <th>invoice_closing_date</th>
                                <th>amount_paid</th>
                                <th>discount_percent</th>
                                <th>absolute_discount</th>
                                <th>total_discount</th>
                                <th>closing_code</th>
                                <th>closing_note</th>
                                <th>total_without_tax</th>
                                <th>status_numeric</th>
                                <th>Status Text</th>
                                <th>Author User</th>
                                <th>Closer User</th>
                                <th>private_note</th>
                                <th>public_note</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody"></tbody>
                    </table>
                </div>
            `;

            // Payment data
            var payments = <?php echo json_encode($payment); ?>;
            // Calculate Totals
            var totalDue = 0;
            var totalDueCollection = 0;
            var totalAmountReceived = 0;
            payments.forEach(payment => {
                var closingDate = new Date(payment.invoice_closing_date).toISOString().split("T")[0];
                var createdDate = new Date(payment.invoice_date_created).toISOString().split("T")[0];
            
                // Total Due
                if (payment.status_text === "Unpaid") {
                    totalDue += parseFloat(payment.total_without_tax) - parseFloat(payment.payment_amount);
                }
                // Total Due Collection (Invoice Closing Date > Invoice Created Date)
                if (closingDate > createdDate) {
                    totalDueCollection += parseFloat(payment.payment_amount);
                }
                // Total Amount Received
                if (createdDate >= closingDate) {
                    totalAmountReceived += parseFloat(payment.payment_amount);
                }
                
            });

            // Calculate the Total (Due Collection + Amount Received)
            var total = totalDueCollection + totalAmountReceived;

            // Update Totals Section
            document.getElementById("totalDue").textContent = totalDue.toLocaleString();
            document.getElementById("totalDueCollection").textContent = totalDueCollection.toLocaleString();
            document.getElementById("totalAmountReceived").textContent = totalAmountReceived.toLocaleString();
            // Display the Total
            document.getElementById("total").textContent = total.toLocaleString();

            // Dynamically populate the table
            var tableBody = document.getElementById("tableBody");
            payments.forEach(payment => {
                var row = document.createElement("tr");
                row.innerHTML = `
                    <td>${payment.payment_rowid}</td>
                    <td>${payment.payment_ref}</td>
                    <td>${parseFloat(payment.payment_amount).toLocaleString()}</td>
                    <td>${payment.payment_date}</td>
                    <td>${payment.pf_rowid}</td>
                    <td>${payment.fk_payment}</td>
                    <td>${payment.fk_invoice}</td>
                    <td>${payment.allocated_amount}</td>
                    <td>${payment.invoice_ref}</td>
                    <td>${payment.invoice_date_created}</td>
                    <td>${payment.invoice_due_date}</td>
                    <td>${payment.invoice_validation_date}</td>
                    <td>${payment.invoice_closing_date}</td>
                    <td>${payment.amount_paid}</td>
                    <td>${payment.discount_percent}</td>
                    <td>${payment.absolute_discount}</td>
                    <td>${payment.total_discount}</td>
                    <td>${payment.closing_code}</td>
                    <td>${payment.closing_note}</td>
                    <td>${parseFloat(payment.total_without_tax).toLocaleString()}</td>
                    <td>${payment.status_numeric}</td>
                    <td>${payment.status_text}</td>
                    <td>${payment.author_user_login}</td>
                    <td>${payment.closer_user_login}</td>
                    <td>${payment.private_note}</td>
                    <td>${payment.public_note}</td>
                `;
                tableBody.appendChild(row);
            });

            // Search Filter Functionality
            document.getElementById("searchInput").addEventListener("input", function () {
                var filter = this.value.toLowerCase();
                var rows = tableBody.getElementsByTagName("tr");
                for (var i = 0; i < rows.length; i++) {
                    var cells = rows[i].getElementsByTagName("td");
                    var match = false;
                    for (var j = 0; j < cells.length; j++) {
                        if (cells[j].textContent.toLowerCase().includes(filter)) {
                            match = true;
                            break;
                        }
                    }
                    rows[i].style.display = match ? "" : "none";
                }
            });
        }
    </script>

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