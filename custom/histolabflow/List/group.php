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

// Check if 'group' parameter exists in the URL
$groupName = isset($_GET['group']) ? htmlspecialchars($_GET['group']) : 'Unknown Group';
$users = get_users_by_group($groupName);

$reception = reception_sample_received_list(null, null, 'today');
$receptionJson = json_encode($reception);

$gross = gross_complete_list(null, null, 'today');

$grossJson = json_encode($gross);

$worksheet_tracking = worksheet_tracking_list(null, null, 'today');
$worksheetTrackingJson = json_encode($worksheet_tracking);

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
                <a href="./index.php"><button class="btn btn-primary">Home</button></a>
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
                    $groupUrl = "http://192.168.1.139:8881/custom/histolabflow/List/group.php?group=" . urlencode($group['nom']);
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

   <!-- Reception Data visualization -->
    <script>
        
        const receptionData = <?php echo $receptionJson; ?>;
        const grossdata = <?php echo $grossJson; ?>;
        const worksheetdata = <?php echo $worksheetTrackingJson; ?>;
        console.log('worksheet tracking', worksheetdata);

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
                else {
                    alert('No reception data found for ' + username);
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


        // Function to close a tab
        function closeTab() {
            const tab = event.target.closest('.user-tab');
            if (tab) {
                tab.remove();
            }
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
                    var url = "http://192.168.1.139:8881/custom/histolabflow/sampleReceived/date_range.php?start_date=" + encodeURIComponent(startDate) + "&end_date=" + encodeURIComponent(endDate);
                    
                    // Redirect to the constructed URL
                    window.location.href = url;
                }
            });
        });
    </script>
</body>
</html>