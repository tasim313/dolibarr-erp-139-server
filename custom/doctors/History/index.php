<?php

// database connection and function file
include('../connection.php');
include('../../transcription/common_function.php');
include('../../grossmodule/gross_common_function.php');
include('../list_of_function.php');

// Load Dolibarr environment
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
$langs->loadLangs(array("doctors@doctors"));

$action = GETPOST('action', 'aZ09');


// Security check
// if (! $user->rights->doctors->myobject->read) {
// 	accessforbidden();
// }
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

llxHeader("", $langs->trans("DoctorsArea"));

$LabNumber = $_GET['labno'];
$lab_status = get_lab_number_status_for_doctor_tracking_by_lab_number($LabNumber);
$labStatus = json_encode($lab_status);

$loggedInUserId = $user->id;
$loggedInUsername = $user->login;

$userGroupNames = getUserGroupNames($loggedInUserId);

$today_history = get_histo_doctor_today_history_list($loggedInUserId);

echo'<script> 
const todayHistory = ' . json_encode($today_history) . ';

</script>';

// Extract unique status names
$statusNames = array();
foreach ($today_history as $history) {
    if (!in_array($history['Status Name'], $statusNames)) {
        $statusNames[] = $history['Status Name'];
    }
}

$yesterday_history = get_histo_doctor_yesterday_history_list($loggedInUserId);
echo'<script> 
const yesterday_history = ' . json_encode($yesterday_history) . ';
</script>';

// Extract unique status names for yesterday status name
$yesterday_statusNames = array();
foreach ($yesterday_history as $previous_history) {
    if (!in_array($previous_history['Status Name'], $yesterday_statusNames)) {
        $yesterday_statusNames[] = $previous_history['Status Name'];
    }
}


$doctor_list_instruction = get_histo_doctor_instruction_history_list($loggedInUserId);
echo'<script> 
const doctor_history = ' . json_encode($doctor_list_instruction) . ';
</script>';

// Extract unique status names for yesterday status name
$doctor_statusNames = array();
foreach ($doctor_list_instruction as $doctor_history) {
    if (!in_array($doctor_history['Status Name'], $doctor_statusNames)) {
        $doctor_statusNames[] = $doctor_history['Status Name'];
    }
}



$hasConsultants = false;

foreach ($userGroupNames as $group) {
    if ($group['group'] === 'Consultants') {
        $hasConsultants = true;
    } 
}

// Access control using switch statement
switch (true) {
	case $hasConsultants:
		// Doctor has access, continue with the page content...
		break;
	default:
		echo "<h1>Access Denied</h1>";
		echo "<p>You are not authorized to view this page.</p>";
		exit; // Terminate script execution
}

// <i class="fa fa-scissors" ></i>

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../bootstrap-4.4.1-dist/css/bootstrap.min.css">
    <script src="../bootstrap-4.4.1-dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" type="text/css" href="../trackwsfiles/css.css"  />
    <style>
        body {
            font-family: Verdana;
        }

        .today {
            color: red;
        }

        .tomorrow {
            color: yellow;
        }

        .flex-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-start; /* Align items to the start */
            gap: 2px; /* Reduce gap further if needed */
        }

        .flex-container > div {
            margin: 2px; /* Slight margin around each tab */
            padding: 10px; /* Adjust padding for content inside tabs */
            flex: 1;
        }


        .tab-content { 
            display: block; 
        }

        .tab-content.grayed-out { 
            opacity: 0.5; 
            pointer-events: none; 
        }

        .semi-bold { 
            font-weight: 300; 
        }

        .red { 
            color: red; 
        }

        .tab-buttons {
            display: flex;
            justify-content: flex-star;
            align-items: center;
            width: 100%;
            position: relative;
        }

        .tab-buttons button { 
            margin-right: 10px; 
        }

        .tab-buttons button.inactive { 
            opacity: 0.5; 
        }

        .tab-buttons button.active { 
            font-weight: bold; 
        }

        .hidden { 
            display: none; 
        }

        .tab {
            overflow: hidden;
        }

        /* Style the buttons inside the tab */
        .tab button {
            background-color: inherit;
            font-size: 15px;
            border: none;
            cursor: pointer;
            outline: none;
            float: left;
        }

        /* Create an active/current tablink class */
        .tab button.active {
            background-color: #ccc;
        }

        /* Style the tab content */
        .tabcontent_1 {
            display: none;
            padding: 6px 12px;
            -webkit-animation: fadeEffect 1s;
            animation: fadeEffect 1s;
        }

        /* Button container styles */
        .button-container {
            display: flex;
            justify-content: space-between;
            width: 100%;
        }

        .button-container li {
            margin: 0 -250px;
        }

        .button-container li:first-child,
        .button-container li:last-child {
            flex: 0 0 auto; /* Prevent stretching */
        }

        .button-container li:nth-child(2) {
            flex: 10; /* Make the second button flexible */
        }

        .btn-group button {
            /* cursor: pointer; */
            float: left; /* Float the buttons side by side */
        }

        /* Clear floats (clearfix hack) */
        .btn-group:after {
            content: "";
            clear: both;
            display: table;
        }

        .btn-group button:not(:last-child) {
            border-right: none; /* Prevent double borders */
        }

        /* Set font size for h2 tags */
        .h2 {
            font-size: 20px;
        }

        .h3 {
            font-size: 15px;
        }


        .nav-tabs.process-model {
            display: flex;
            flex-wrap: wrap;
            justify-content: center; /* Center the tabs */
            padding: 0; /* Remove padding */
            margin: 0; /* Remove margin */
            list-style-type: none; /* Remove default list styling */
        }

        .nav-tabs.process-model li {
            margin: 0 5px; /* Add minimal space between tabs */
            padding: 0;
        }

        .nav-tabs.process-model li button {
            padding: 5px 10px; /* Adjust padding to make buttons more compact */
            font-size: 18px; /* Adjust font size if needed */
            border-radius: 5px; /* Optional: Add border radius */
        }

        .tab-buttons.button-container {
            display: flex;
            justify-content: space-between;
            width: 100%; /* Ensure buttons fill the container */
        }

        .button-container li {
            flex-grow: 1; /* Make each button flexible */
            text-align: center;
        }

        .button-container button {
            width: 100%; /* Make the button take full width of its container */
            border: none;
            background-color: transparent; /* Optional: Transparent background */
            font-size: 18px; /* Adjust the font size as needed */
            cursor: pointer;
        }

        .button-container span {
            display: block;
        }

        .button-container i {
            margin-right: 5px; /* Adjust icon spacing */
        }

        /* Fade in tabs */
        @-webkit-keyframes fadeEffect {
            from {opacity: 0;}
            to {opacity: 1;}
        }

        @keyframes fadeEffect {
            from {opacity: 0;}
            to {opacity: 1;}
        }

        /* Responsive Styles */

        /* Extra Large Screens (Large Monitors) */
        @media only screen and (min-width: 1200px) {
            .flex-container > div {
                font-size: 14px;
            }
        }

        /* Large Screens (Desktops) */
        @media only screen and (min-width: 992px) and (max-width: 1199px) {
            .flex-container > div {
                font-size: 12px;
            }
        }

        /* Medium Screens (Tablets in Landscape Mode) */
        @media only screen and (min-width: 768px) and (max-width: 991px) {
            .flex-container > div {
                font-size: 10px;
            }
        }

        /* Small Screens (Tablets in Portrait Mode) */
        @media only screen and (min-width: 600px) and (max-width: 767px) {
            .flex-container > div {
                font-size: 8px;
                padding: 15px;
            }
        }

        /* Mobile Phones */
        @media only screen and (max-width: 599px) {
            .flex-container {
                flex-direction: column;
                align-items: flex-start;
            }

            .flex-container > div {
                font-size: 6px;
                padding: 10px;
                width: 100%;
            }

            .tab-buttons.button-container {
                flex-direction: column;
                align-items: center;
            }

            .tab-buttons.button-container li {
                width: 100%;
                margin: 5px 0;
            }

            .tab-buttons.button-container button {
                font-size: 16px;
                width: 100%;
                text-align: center;
            }
        }


    </style>
</head>
<body>

<!-- This is main Tab Feature -->

<ul class="nav nav-tabs process-model more-icon-preocess" role="tablist">
    <div class="tab-buttons button-container">

        <li role="presentation">
            <span style="color: red;">
                <button style="border:none; font-size: 20px;" id="tab-list" class="inactive" onclick="showTab('list')">
                    <i id="icon-list" class="fas fa-list" aria-hidden="true"></i> My Request List</button>
            </span>
        </li>

        <li role="presentation">
            <span style="color: red;">
                <button style="border:none; font-size: 20px;" id="tab-today" class="inactive" onclick="showTab('today')">
                    <i id="icon-today" class="fas fa-calendar-day" aria-hidden="true"></i> Today</button>
            </span>
        </li>

        <li role="presentation">
            <span style="color:green">
                <button style="border:none; font-size: 20px;" id="tab-yesterday" class="inactive" onclick="showTab('yesterday')">
                <i id="icon-yesterday" class="fas fa-history" aria-hidden="true"></i>Yesterday</button>
            </span>
        </li>
    </div>
</ul>

        <!-- This is Sub Tab Feature -->
        <div class="flex-container">
                <div id="today" class="tab-content tab btn-group grayed-out">
                    <ul id="dynamic-sub-tabs" class="nav nav-tabs process-model more-icon-preocess" role="tablist">
                        <!-- Dynamic sub-tabs will be generated here -->
                    </ul>

                    <!-- Container for displaying tab content -->
                    <div id="tab-content-container">
                        <!-- Dynamic content for each tab will be injected here -->
                    </div>
                </div>

                <div id="yesterday" class="tab-content tab btn-group grayed-out">

                        <ul id="yesterday-dynamic-sub-tabs" class="nav nav-tabs process-model more-icon-preocess" role="tablist">
                                <!-- Dynamic sub-tabs will be generated here -->
                        </ul>

                        <!-- Container for displaying tab content -->
                        <div id="yesterday-tab-content-container">
                            <!-- Dynamic content for each tab will be injected here -->
                        </div>
                </div>

                <div id="list" class="tab-content tab btn-group grayed-out">
                        <div id="filter-container">
                                <label for="status-filter">Instructions Name:</label>
                                <select id="status-filter">
                                    <option value="">All</option>
                                    <!-- Options will be dynamically generated -->
                                </select>

                                <label for="labroom-filter">&nbsp;&nbsp;Status:</label>
                                <select id="labroom-filter">
                                    <option value="">All</option>
                                    <!-- Options will be dynamically generated -->
                                </select>

                                <label for="date-filter">&nbsp;&nbsp;Date:</label>
                                <input type="date" id="date-filter">

                                <button id="apply-filter">Apply Filter&nbsp;&nbsp;</button>
                        </div>

                        <div id="list-tab-content-container"></div>

                </div>

                <div id="error" style="display:none;color:red;">
                    Wrong lab number. Please enter the correct one.
                </div>
        </div>

        <!-- This script for today history value shows  and tab functionality-->

        <script>
            
            function showTab(tabId) {
                // Hide all tab contents
                var tabs = document.getElementsByClassName('tab-content');
                for (var i = 0; i < tabs.length; i++) {
                    tabs[i].style.display = 'none'; // Hide all tabs
                    tabs[i].classList.add('grayed-out');
                }

                // Set inactive class to all buttons and remove from the selected button
                var buttons = document.getElementsByClassName('tab-buttons')[0].getElementsByTagName('button');
                for (var j = 0; j < buttons.length; j++) {
                    buttons[j].classList.remove('active');
                    buttons[j].classList.add('inactive');
                }

                // Check if the tab element with the given ID exists
                var tabElement = document.getElementById(tabId);
                if (tabElement) {
                    tabElement.style.display = 'block'; // Show the selected tab
                    tabElement.classList.remove('grayed-out');
                } else {
                    console.error(`Element with ID '${tabId}' not found.`);
                }

                // Set the clicked button to active
                var tabButtonElement = document.getElementById('tab-' + tabId);
                if (tabButtonElement) {
                    tabButtonElement.classList.remove('inactive');
                    tabButtonElement.classList.add('active');
                } else {
                    console.error(`Button with ID 'tab-${tabId}' not found.`);
                }

                // Call generateDynamicSubTabs only when the "today" tab is clicked
                if (tabId === 'today') {
                    generateDynamicSubTabs();
                }
                if(tabId === 'yesterday'){
                    generateDynamicSubTabsForYesterdayHistoryList();
                }
                if(tabId === 'list'){
                    // generateDynamicSubTabsForListHistory();
                }
            }

            // Pass the status names to JavaScript
            const statusNames = <?php echo json_encode($statusNames); ?>;
            
            // Now we can use statusNames in our JavaScript function
            generateDynamicSubTabs(statusNames);
            document.getElementById('tab-today').addEventListener('click', onClickTodayTab);


            function onClickTodayTab() {
                if (statusNames && statusNames.length > 0) {
                    generateDynamicSubTabs(statusNames);
                } else {
                    console.error("No status names available to generate tabs.");
                }
            }


            document.getElementById('tab-today').addEventListener('click', onClickTodayTab);
            
            
            function generateDynamicSubTabs(statusNames) {
                    const subTabsContainer = document.getElementById('dynamic-sub-tabs');
                    subTabsContainer.innerHTML = ''; 

                    // Define custom order
                    const customOrder = [
                        'Screening Done', 'Finalized', 'R/C requested', 'Deeper Cut requested',
                        'Re-gross Requested', 'IHC-Block-Markers-requested'
                    ];

                    // Sort statusNames based on customOrder
                    const sortedStatusNames = statusNames.sort((a, b) => {
                        const indexA = customOrder.indexOf(a);
                        const indexB = customOrder.indexOf(b);
                        
                        if (indexA !== -1 && indexB !== -1) {
                            // Both are in the customOrder array
                            return indexA - indexB;
                        } else if (indexA !== -1) {
                            // a is in the customOrder array, b is not
                            return -1;
                        } else if (indexB !== -1) {
                            // b is in the customOrder array, a is not
                            return 1;
                        } else {
                            // Neither are in the customOrder array
                            return a.localeCompare(b);
                        }
                    });

                    if (Array.isArray(sortedStatusNames) && sortedStatusNames.length > 0) {
                        const displayedStatusNames = new Set();

                        sortedStatusNames.forEach(statusName => {
                            if (!displayedStatusNames.has(statusName)) {
                                displayedStatusNames.add(statusName);

                                const li = document.createElement('li');
                                li.setAttribute('role', 'presentation');

                                const button = document.createElement('button');
                                button.style.border = "none";
                                button.style.fontSize = "14px"; // Adjust font size as needed
                                button.className = getButtonClass(statusName);
                                button.setAttribute('onclick', `openTab(event, '${statusName.replace(/\s+/g, '-')}-Instructions')`);
                                button.innerHTML = `<i class="${getIconClass(statusName)}"></i> ${statusName}`;

                                li.appendChild(button);
                                subTabsContainer.appendChild(li);

                                // Create a content div for each tab
                                const contentDiv = document.createElement('div');
                                contentDiv.id = `${statusName.replace(/\s+/g, '-')}-Instructions`;
                                contentDiv.className = 'tab-content';
                                contentDiv.style.display = 'none'; // Hide initially
                                
                                // Override styles to remove grayed-out effect
                                contentDiv.style.backgroundColor = 'white';
                                contentDiv.style.color = 'black'; // Ensure black text color
                                contentDiv.style.opacity = '1'; // Ensure full opacity
                                document.getElementById('tab-content-container').appendChild(contentDiv);

                                console.log(`Created tab for: ${statusName}`);
                            }
                        });
                    } else {
                        const li = document.createElement('li');
                        li.innerText = 'No statuses available';
                        subTabsContainer.appendChild(li);
                    }
            }


            function openTab(evt, tabName) {
                // Hide all tab contents
                const tabContents = document.querySelectorAll('#tab-content-container .tab-content');
                tabContents.forEach(content => content.style.display = 'none');

                // Remove active class from all tabs
                const tabLinks = document.querySelectorAll('#dynamic-sub-tabs button');
                tabLinks.forEach(button => button.classList.remove('active'));

                // Show the selected tab content
                const selectedTabContent = document.getElementById(tabName);
                if (selectedTabContent) {
                    selectedTabContent.style.display = 'block';
                    evt.currentTarget.classList.add('active');
                } else {
                    console.error(`Content div with ID '${tabName}' not found.`);
                }

                // Load content for the tab
                displayStatusDetails(tabName);
            }

           
            function normalizeStatusName(statusName) {
                    return statusName.replace(/ /g, '-').toLowerCase();
            }


            function displayStatusDetails(statusTab) {
                    if (!todayHistory) {
                        console.error("Today history data is not available.");
                        return;
                    }

                    console.log("Today History : ", todayHistory);

                    // Convert statusTab to a status name
                    const statusName = normalizeStatusName(statusTab.replace(/-Instructions$/, '').replace(/-/g, ' '));

                    // Log all status names in todayHistory for comparison
                    todayHistory.forEach(item => {
                        console.log("Available Status Name:", normalizeStatusName(item['Status Name']));
                    });

                    // Filter data based on the normalized status name
                    const filteredData = todayHistory.filter(item => normalizeStatusName(item['Status Name']) === statusName);

                    const detailsContainer = document.getElementById(statusTab);
                    detailsContainer.innerHTML = ''; // Clear existing details

                    // Create a table for the sub-tab summary
                    const summaryTable = document.createElement('table');
                    summaryTable.style.fontFamily = 'arial, sans-serif';
                    summaryTable.style.borderCollapse = 'collapse';
                    summaryTable.style.width = '100%';
                    summaryTable.style.marginBottom = '20px'; // Space between tables

                    // Create the table header for the summary table
                    const summaryThead = document.createElement('thead');
                    const summaryHeaderRow = document.createElement('tr');
                    const summaryHeaders = ['Name', 'Total'];
                    summaryHeaders.forEach(headerText => {
                        const th = document.createElement('th');
                        th.textContent = headerText;
                        th.style.border = '1px solid #dddddd'; // Border color for header
                        th.style.textAlign = 'left'; // Text alignment for header
                        th.style.padding = '8px'; // Padding for header
                        th.style.backgroundColor = '#fff'; // Background color for header
                        th.style.color = 'black'; // Text color for header
                        summaryHeaderRow.appendChild(th);
                    });
                    summaryThead.appendChild(summaryHeaderRow);
                    summaryTable.appendChild(summaryThead);

                    // Create the table body for the summary table
                    const summaryTbody = document.createElement('tbody');

                    // Calculate the unique lab number count
                    const uniqueLabNumbers = new Set(filteredData.map(item => item['Lab Number']));
                    const totalCount = uniqueLabNumbers.size;

                    // Add a row for the summary table
                    const summaryRow = document.createElement('tr');
                    const subTabCell = document.createElement('td');
                    subTabCell.textContent = statusName; // Sub-tab name
                    subTabCell.style.border = '1px solid #dddddd'; // Border color for cell
                    subTabCell.style.textAlign = 'left'; // Text alignment for cell
                    subTabCell.style.padding = '8px'; // Padding for cell
                    subTabCell.style.color = 'black'; // Text color for cell
                    summaryRow.appendChild(subTabCell);

                    const countCell = document.createElement('td');
                    countCell.textContent = totalCount; // Total count value
                    countCell.style.border = '1px solid #dddddd'; // Border color for cell
                    countCell.style.textAlign = 'left'; // Text alignment for cell
                    countCell.style.padding = '8px'; // Padding for cell
                    countCell.style.color = 'black'; // Text color for cell
                    summaryRow.appendChild(countCell);

                    summaryTbody.appendChild(summaryRow);
                    summaryTable.appendChild(summaryTbody);

                    // Append the summary table to the details container
                    detailsContainer.appendChild(summaryTable);

                    if (filteredData.length > 0) {
                        // Create a table element for the details
                        const table = document.createElement('table');
                        table.style.fontFamily = 'arial, sans-serif';
                        table.style.borderCollapse = 'collapse';
                        table.style.width = '100%';

                        // Create the table header for details
                        const thead = document.createElement('thead');
                        const headerRow = document.createElement('tr');
                        const headers = ['Lab Number', 'Description', 'Status'];
                        headers.forEach(headerText => {
                            const th = document.createElement('th');
                            th.textContent = headerText;
                            th.style.border = '1px solid #dddddd'; // Border color for header
                            th.style.textAlign = 'left'; // Text alignment for header
                            th.style.padding = '8px'; // Padding for header
                            th.style.backgroundColor = '#fff'; // Background color for header
                            th.style.color = 'black'; // Text color for header
                            headerRow.appendChild(th);
                        });
                        thead.appendChild(headerRow);
                        table.appendChild(thead);

                        // Create the table body for details
                        const tbody = document.createElement('tbody');
                        filteredData.forEach((item, index) => {
                            const row = document.createElement('tr');
                            if (index % 2 === 0) {
                                row.style.backgroundColor = '#dddddd'; // Background color for even rows
                            }

                            const labNumberCell = document.createElement('td');
                            labNumberCell.textContent = item['Lab Number'];
                            labNumberCell.style.border = '1px solid #dddddd'; // Border color for cell
                            labNumberCell.style.textAlign = 'left'; // Text alignment for cell
                            labNumberCell.style.padding = '8px'; // Padding for cell
                            labNumberCell.style.color = 'black'; // Text color for cell
                            row.appendChild(labNumberCell);

                            const descriptionCell = document.createElement('td');
                            descriptionCell.textContent = item['Description'] || 'No Description'; // Handle missing description
                            descriptionCell.style.border = '1px solid #dddddd'; // Border color for cell
                            descriptionCell.style.textAlign = 'left'; // Text alignment for cell
                            descriptionCell.style.padding = '8px'; // Padding for cell
                            descriptionCell.style.color = 'black'; // Text color for cell
                            row.appendChild(descriptionCell);

                            const statusCell = document.createElement('td');
                            // Set the status text and background color based on the value of 'LabRoomStatus'
                            let statusText = '';
                            let backgroundColor = '';

                            if (item['LabRoomStatus'] === 'in_progress') {
                                statusText = 'In Progress';
                                backgroundColor = 'yellow';
                            } else if (item['LabRoomStatus'] === 'done') {
                                statusText = 'Completed';
                                backgroundColor = 'green';
                            } else if (item['LabRoomStatus'] === 'on-hold') {
                                statusText = 'Hold';
                                backgroundColor = 'red';
                            } else {
                                statusText = item['LabRoomStatus'] || ''; // Handle missing description
                                backgroundColor = ''; // No background color for unrecognized statuses
                            }

                            // Set the status text and styles
                            statusCell.textContent = statusText;
                            statusCell.style.border = '1px solid #dddddd'; // Border color for cell
                            statusCell.style.textAlign = 'left'; // Text alignment for cell
                            statusCell.style.padding = '8px'; // Padding for cell
                            statusCell.style.backgroundColor = backgroundColor; // Set the background color
                            statusCell.style.color = 'black'; // Ensure text color is black
                            row.appendChild(statusCell);

                            tbody.appendChild(row);
                        });
                        table.appendChild(tbody);

                        // Append the details table to the details container
                        detailsContainer.appendChild(table);
                    } else {
                        detailsContainer.innerHTML += '<p>No details available for this status.</p>';
                    }
            }

            function getButtonClass(statusName) {
                return 'tab-button'; // Add specific classes if needed
            }

            function getIconClass(statusName) {
                switch (statusName) {
                    case 'Finalized': return 'fa fa-gavel'; // Example icon class
                    case 'Re-gross Requested': return 'fa fa-scissors'; // Example icon class
                    case 'Special Stain AFB requested': return 'fa fa-flask'; // Example icon class
                    case 'Waiting - Study': return 'fa fa-book'; // Example icon class
                    case 'Waiting - Patient History / Investigation': return 'fas fa-users'; // Example icon class
                    case 'Screening Done' : return 'fa fa-check';
                    case 'IHC-Block-Markers-requested' : return 'fas fa-vials';
                    case 'Deeper Cut requested' : return 'fa fa-flask';
                    // Add more cases for each status with appropriate icon classes
                    default: return 'fa fa-circle'; // Default icon class
                }
            }
        </script>

        <!-- This script for yesterday history value shows  and tab functionality-->
        <script>
            // Pass the status names to JavaScript
            const YesterdaystatusNames = <?php echo json_encode($yesterday_statusNames); ?>;
            const yesterdayHistory = yesterday_history
            console.log("Yesterday : ", yesterdayHistory);

            document.getElementById('tab-yesterday').addEventListener('click', onClickYesterdayTab);

            function onClickYesterdayTab() {
                if (YesterdaystatusNames && YesterdaystatusNames.length > 0) {
                    generateDynamicSubTabsForYesterdayHistoryList(YesterdaystatusNames);
                } else {
                    console.error("No status names available to generate tabs.");
                }
            }

            function generateDynamicSubTabsForYesterdayHistoryList(statusNames) {
                const subTabsContainer = document.getElementById('yesterday-dynamic-sub-tabs');
                if (!subTabsContainer) {
                    console.error("Element with ID 'yesterday-dynamic-sub-tabs' not found.");
                    return;
                }

                subTabsContainer.innerHTML = ''; // Clear existing tabs

                const customOrder = [
                    'Screening Done', 'Finalized', 'R/C requested', 'Deeper Cut requested',
                    'Re-gross Requested', 'IHC-Block-Markers-requested'
                ];

                const sortedStatusNames = statusNames.sort((a, b) => {
                    const indexA = customOrder.indexOf(a);
                    const indexB = customOrder.indexOf(b);
                    
                    if (indexA !== -1 && indexB !== -1) {
                        return indexA - indexB;
                    } else if (indexA !== -1) {
                        return -1;
                    } else if (indexB !== -1) {
                        return 1;
                    } else {
                        return a.localeCompare(b);
                    }
                });

                if (Array.isArray(sortedStatusNames) && sortedStatusNames.length > 0) {
                    const displayedStatusNames = new Set();

                    sortedStatusNames.forEach(statusName => {
                        if (!displayedStatusNames.has(statusName)) {
                            displayedStatusNames.add(statusName);

                            const li = document.createElement('li');
                            li.setAttribute('role', 'presentation');

                            const button = document.createElement('button');
                            button.style.border = "none";
                            button.style.fontSize = "14px"; 
                            button.className = getYesterdayButtonClass(statusName);
                            button.setAttribute('onclick', `openYesterdayTab(event, '${statusName.replace(/\s+/g, '-')}-Instructions-Yesterday')`);
                            button.innerHTML = `<i class="${getYesterdayIconClass(statusName)}"></i> ${statusName}`;

                            li.appendChild(button);
                            subTabsContainer.appendChild(li);

                            const contentDiv = document.createElement('div');
                            contentDiv.id = `${statusName.replace(/\s+/g, '-')}-Instructions-Yesterday`;
                            contentDiv.className = 'tab-content';
                            contentDiv.style.display = 'none';
                            contentDiv.style.backgroundColor = 'white';
                            contentDiv.style.color = 'black'; 
                            contentDiv.style.opacity = '1';
                            
                            const tabContentContainer = document.getElementById('yesterday-tab-content-container');
                            if (!tabContentContainer) {
                                console.error("Element with ID 'yesterday-tab-content-container' not found.");
                                return;
                            }
                            tabContentContainer.appendChild(contentDiv);

                            console.log(`Created tab for Yesterday: ${statusName}`);
                        }
                    });
                } else {
                    const li = document.createElement('li');
                    li.innerText = 'No statuses available';
                    subTabsContainer.appendChild(li);
                }
            }

            function openYesterdayTab(evt, tabName) {
                const tabContents = document.querySelectorAll('#yesterday-tab-content-container .tab-content');
                tabContents.forEach(content => content.style.display = 'none');

                const tabLinks = document.querySelectorAll('#yesterday-dynamic-sub-tabs button');
                tabLinks.forEach(button => button.classList.remove('active'));

                const selectedTabContent = document.getElementById(tabName);
                if (selectedTabContent) {
                    selectedTabContent.style.display = 'block';
                    evt.currentTarget.classList.add('active');
                } else {
                    console.error(`Content div with ID '${tabName}' not found.`);
                }

                displayYesterdayStatusDetails(tabName);
            }

           

            function displayYesterdayStatusDetails(statusTab) {
                        if (!yesterdayHistory) {
                            console.error("Yesterday history data is not available.");
                            return;
                        }

                        console.log("Yesterday History: ", yesterdayHistory);

                        // Normalize the status tab name
                        const yesterday_statusName = normalizeYesterdayStatusName(statusTab.replace(/-Instructions-Yesterday$/, '').replace(/-/g, ' '));

                        // Log all status names in yesterdayHistory for comparison
                        yesterdayHistory.forEach(item => {
                            console.log("Available Status Name (Yesterday):", normalizeYesterdayStatusName(item['Status Name']));
                        });

                        // Filter data based on the normalized status name
                        const yesterday_filteredData = yesterdayHistory.filter(item => normalizeYesterdayStatusName(item['Status Name']) === yesterday_statusName);

                        const yesterday_detailsContainer = document.getElementById(statusTab);
                        if (!yesterday_detailsContainer) {
                            console.error(`Details container with ID '${statusTab}' not found.`);
                            return;
                        }
                        yesterday_detailsContainer.innerHTML = ''; // Clear existing details

                        // Create a summary table
                        const yesterday_summaryTable = document.createElement('table');
                        yesterday_summaryTable.style.fontFamily = 'arial, sans-serif';
                        yesterday_summaryTable.style.borderCollapse = 'collapse';
                        yesterday_summaryTable.style.width = '100%';
                        yesterday_summaryTable.style.marginBottom = '20px'; // Space between tables

                        const yesterday_summaryThead = document.createElement('thead');
                        const yesterday_summaryHeaderRow = document.createElement('tr');
                        const yesterday_summaryHeaders = ['Name', 'Total'];
                        yesterday_summaryHeaders.forEach(headerText => {
                            const yesterday_th = document.createElement('th');
                            yesterday_th.textContent = headerText;
                            yesterday_th.style.border = '1px solid #dddddd';
                            yesterday_th.style.textAlign = 'left';
                            yesterday_th.style.padding = '8px';
                            yesterday_th.style.backgroundColor = '#fff';
                            yesterday_th.style.color = 'black';
                            yesterday_summaryHeaderRow.appendChild(yesterday_th);
                        });
                        yesterday_summaryThead.appendChild(yesterday_summaryHeaderRow);
                        yesterday_summaryTable.appendChild(yesterday_summaryThead);

                        const yesterday_summaryTbody = document.createElement('tbody');

                        // Calculate unique lab numbers and total count
                        const yesterday_uniqueLabNumbers = new Set(yesterday_filteredData.map(item => item['Lab Number']));
                        const yesterday_totalCount = yesterday_uniqueLabNumbers.size;

                        const yesterday_summaryRow = document.createElement('tr');
                        const yesterday_subTabCell = document.createElement('td');
                        yesterday_subTabCell.textContent = yesterday_statusName;
                        yesterday_subTabCell.style.border = '1px solid #dddddd';
                        yesterday_subTabCell.style.textAlign = 'left';
                        yesterday_subTabCell.style.padding = '8px';
                        yesterday_subTabCell.style.color = 'black';
                        yesterday_summaryRow.appendChild(yesterday_subTabCell);

                        const yesterday_countCell = document.createElement('td');
                        yesterday_countCell.textContent = yesterday_totalCount;
                        yesterday_countCell.style.border = '1px solid #dddddd';
                        yesterday_countCell.style.textAlign = 'left';
                        yesterday_countCell.style.padding = '8px';
                        yesterday_countCell.style.color = 'black';
                        yesterday_summaryRow.appendChild(yesterday_countCell);

                        yesterday_summaryTbody.appendChild(yesterday_summaryRow);
                        yesterday_summaryTable.appendChild(yesterday_summaryTbody);

                        yesterday_detailsContainer.appendChild(yesterday_summaryTable);

                        // Create a detailed table if there is filtered data
                        if (yesterday_filteredData.length > 0) {
                            const yesterday_detailsTable = document.createElement('table');
                            yesterday_detailsTable.style.fontFamily = 'arial, sans-serif';
                            yesterday_detailsTable.style.borderCollapse = 'collapse';
                            yesterday_detailsTable.style.width = '100%';

                            const yesterday_detailsThead = document.createElement('thead');
                            const yesterday_detailsHeaderRow = document.createElement('tr');
                            const yesterday_detailsHeaders = ['Lab Number', 'Description', 'Status'];
                            yesterday_detailsHeaders.forEach(headerText => {
                                const yesterday_th = document.createElement('th');
                                yesterday_th.textContent = headerText;
                                yesterday_th.style.border = '1px solid #dddddd';
                                yesterday_th.style.textAlign = 'left';
                                yesterday_th.style.padding = '8px';
                                yesterday_th.style.backgroundColor = '#fff';
                                yesterday_th.style.color = 'black';
                                yesterday_detailsHeaderRow.appendChild(yesterday_th);
                            });
                            yesterday_detailsThead.appendChild(yesterday_detailsHeaderRow);
                            yesterday_detailsTable.appendChild(yesterday_detailsThead);

                            const yesterday_detailsTbody = document.createElement('tbody');
                            yesterday_filteredData.forEach((item, index) => {
                                const yesterday_row = document.createElement('tr');
                                if (index % 2 === 0) {
                                    yesterday_row.style.backgroundColor = '#dddddd';
                                }

                                const yesterday_labNumberCell = document.createElement('td');
                                yesterday_labNumberCell.textContent = item['Lab Number'];
                                yesterday_labNumberCell.style.border = '1px solid #dddddd';
                                yesterday_labNumberCell.style.textAlign = 'left';
                                yesterday_labNumberCell.style.padding = '8px';
                                yesterday_labNumberCell.style.color = 'black';
                                yesterday_row.appendChild(yesterday_labNumberCell);

                                const yesterday_descriptionCell = document.createElement('td');
                                yesterday_descriptionCell.textContent = item['Description'] || 'No Description';
                                yesterday_descriptionCell.style.border = '1px solid #dddddd';
                                yesterday_descriptionCell.style.textAlign = 'left';
                                yesterday_descriptionCell.style.padding = '8px';
                                yesterday_descriptionCell.style.color = 'black';
                                yesterday_row.appendChild(yesterday_descriptionCell);


                                const yesterday_statusCell = document.createElement('td');
                                // Set the status text and background color based on the value of 'LabRoomStatus'
                                let yesterday_statusText = '';
                                let yesterday_backgroundColor = '';

                                if (item['LabRoomStatus'] === 'in_progress') {
                                    yesterday_statusText = 'In Progress';
                                    yesterday_backgroundColor = 'yellow';
                                } else if (item['LabRoomStatus'] === 'done') {
                                    yesterday_statusText = 'Completed';
                                    yesterday_backgroundColor = 'green';
                                } else if (item['LabRoomStatus'] === 'on-hold') {
                                    yesterday_statusText = 'Hold';
                                    yesterday_backgroundColor = 'red';
                                } else {
                                    yesterday_statusText = item['LabRoomStatus'] || ''; // Handle missing description
                                    yesterday_backgroundColor = ''; // No background color for unrecognized statuses
                                }

                                // Set the status text and styles
                                yesterday_statusCell.textContent = yesterday_statusText;
                                yesterday_statusCell.style.border = '1px solid #dddddd'; // Border color for cell
                                yesterday_statusCell.style.textAlign = 'left'; // Text alignment for cell
                                yesterday_statusCell.style.padding = '8px'; // Padding for cell
                                yesterday_statusCell.style.backgroundColor = yesterday_backgroundColor; // Set the background color
                                yesterday_statusCell.style.color = 'black'; // Ensure text color is black
                                yesterday_row.appendChild(yesterday_statusCell);

                                yesterday_detailsTbody.appendChild(yesterday_row);
                            });
                            yesterday_detailsTable.appendChild(yesterday_detailsTbody);

                            yesterday_detailsContainer.appendChild(yesterday_detailsTable);
                        } else {
                            yesterday_detailsContainer.innerHTML += '<p>No data available for this status.</p>';
                        }
            }


            function getYesterdayButtonClass(statusName) {
            // Add logic to return a class based on the statusName
            return 'tab-button-class';
            }

            function getYesterdayIconClass(statusName) {
                switch (statusName) {
                            case 'Finalized': return 'fa fa-gavel'; // Example icon class
                            case 'Re-gross Requested': return 'fa fa-scissors'; // Example icon class
                            case 'Special Stain AFB requested': return 'fa fa-flask'; // Example icon class
                            case 'Waiting - Study': return 'fa fa-book'; // Example icon class
                            case 'Waiting - Patient History / Investigation': return 'fas fa-users'; // Example icon class
                            case 'Screening Done' : return 'fa fa-check';
                            case 'IHC-Block-Markers-requested' : return 'fas fa-vials';
                            case 'Deeper Cut requested' : return 'fa fa-flask';
                            // Add more cases for each status with appropriate icon classes
                            default: return 'fa fa-circle'; // Default icon class
                        }
            }

            function normalizeYesterdayStatusName(statusName) {
                // Implement normalization logic if needed
                return statusName.trim().toLowerCase();
            }


        </script>
       
        <!-- This script for List history value shows  and filter requested value, status value and date value-->
        <script>
                const ListHistory = doctor_history;

                // Generate filter options
                function generateFilterOptions() {
                    const statusNames = new Set();
                    const labRoomStatuses = new Set();

                    ListHistory.forEach(item => {
                        statusNames.add(item['Status Name']);
                        labRoomStatuses.add(item['LabRoomStatus']);
                    });

                    populateSelectOptions('status-filter', Array.from(statusNames));
                    populateSelectOptions('labroom-filter', Array.from(labRoomStatuses));
                }

                function populateSelectOptions(selectId, options) {
                    const selectElement = document.getElementById(selectId);
                    options.forEach(option => {
                        const opt = document.createElement('option');
                        opt.value = option;
                        opt.text = option;
                        selectElement.add(opt);
                    });
                }

                // Apply filters
                document.getElementById('apply-filter').addEventListener('click', () => {
                    const selectedStatus = document.getElementById('status-filter').value;
                    const selectedLabRoomStatus = document.getElementById('labroom-filter').value;
                    const selectedDate = document.getElementById('date-filter').value;

                    const filteredData = ListHistory.filter(item => {
                        const matchesStatus = selectedStatus ? item['Status Name'] === selectedStatus : true;
                        const matchesLabRoom = selectedLabRoomStatus ? item['LabRoomStatus'] === selectedLabRoomStatus : true;
                        const matchesDate = selectedDate ? new Date(item['TrackCreateTime']).toISOString().split('T')[0] === selectedDate : true;
                        return matchesStatus && matchesLabRoom && matchesDate;
                    });

                    displayListData(filteredData);
                });

                // Display the data in a table format with additional logic
              
                function displayListData(data) {
                        const listDetailsContainer = document.getElementById('list-tab-content-container');
                        listDetailsContainer.innerHTML = ''; // Clear existing details

                        const listTable = document.createElement('table');
                        listTable.style.width = '100%';
                        listTable.style.borderCollapse = 'collapse';
                        listTable.style.marginBottom = '20px'; 

                        // Create table header
                        const headerRow = document.createElement('tr');
                        ['Lab Number', 'Instructions Name', 'Description', 'Status', 'Date', 'Doctor Name'].forEach(headerText => {
                            const th = document.createElement('th');
                            th.textContent = headerText;
                            th.style.border = '1px solid #dddddd';
                            th.style.textAlign = 'left';
                            th.style.padding = '8px';
                            th.style.backgroundColor = '#fff';
                            th.style.color = 'black';
                            headerRow.appendChild(th);
                        });
                        listTable.appendChild(headerRow);

                        // Populate table rows
                        data.forEach(item => {
                            const row = document.createElement('tr');
                            
                            // Fill cells
                            ['Lab Number', 'Status Name', 'Description', 'LabRoomStatus', 'TrackCreateTime', 'User Name'].forEach(key => {
                                const td = document.createElement('td');
                                td.style.border = '1px solid #dddddd';
                                td.style.textAlign = 'left';
                                td.style.padding = '8px';
                                td.style.color = 'black';

                                if (key === 'Lab Number') {
                                    // Create hyperlink for Lab Number
                                    const a = document.createElement('a');
                                    a.href = `../lab_status.php?labno=${encodeURIComponent(item[key])}`;
                                    a.textContent = item[key] || 'N/A';
                                    a.style.color = 'blue'; // Optional: change link color
                                    td.appendChild(a);
                                } else if (key === 'LabRoomStatus') {
                                    // Apply specific logic based on LabRoomStatus
                                    switch (item[key]) {
                                        case 'done':
                                            td.textContent = 'Done';
                                            td.style.backgroundColor = 'green';
                                            td.style.color = 'black';
                                            break;
                                        case 'on-hold':
                                            td.textContent = 'Hold';
                                            td.style.backgroundColor = 'red';
                                            td.style.color = 'black';
                                            break;
                                        case 'in_progress':
                                            td.textContent = 'Progress';
                                            td.style.backgroundColor = 'yellow';
                                            td.style.color = 'black';
                                            break;
                                        default:
                                            td.textContent = item[key] || 'N/A';
                                            break;
                                    }
                                } else if (key === 'TrackCreateTime') {
                                    // Format the TrackCreateTime to "1 Sep 2024"
                                    const date = new Date(item[key]);
                                    const options = { day: 'numeric', month: 'short', year: 'numeric' };
                                    td.textContent = date.toLocaleDateString('en-GB', options);
                                } else {
                                    td.textContent = item[key] || 'N/A';
                                }

                                row.appendChild(td);
                            });

                            listTable.appendChild(row);
                        });

                        listDetailsContainer.appendChild(listTable);
                }

                // Initialize the page by generating filter options and displaying the full list
                generateFilterOptions();
                displayListData(ListHistory);
        </script>
</body>
</html>