<?php 
include('connection.php');
include('common_function.php');

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


$title = $langs->trans("Transcription");
$help_url = '';
llxHeader('', $title, $help_url);
$loggedInUserId = $user->id;

$loggedInUsername = $user->login;

$doctor_instruction_list = get_histo_doctor_instruction_list();
$progress_instruction_list = get_histo_doctor_instruction_progress_list();
$complete_instruction_list = get_histo_doctor_instruction_done_list();
$on_hold_instruction_list = get_histo_doctor_instruction_on_hold_list();
$case_summary_list = get_histo_case_summary_list();

// Time complexity check

// function measure_execution_time($function_name) {
//     $start_time = microtime(true); // Start time
//     $result = call_user_func($function_name); // Call the function
//     $end_time = microtime(true); // End time
    
//     $execution_time = $end_time - $start_time;
//     echo "Execution time of {$function_name}: " . $execution_time . " seconds\n";
    
//     return $result;
// }

// // Measure execution times
// $doctor_instruction_list = measure_execution_time('get_histo_doctor_instruction_list');
// $progress_instruction_list = measure_execution_time('get_histo_doctor_instruction_progress_list');
// $complete_instruction_list = measure_execution_time('get_histo_doctor_instruction_done_list');
// $on_hold_instruction_list = measure_execution_time('get_histo_doctor_instruction_on_hold_list');
// $case_summary_list = measure_execution_time('get_histo_case_summary_list');


function filter_data($data) {
    $filtered_data = [];

    foreach ($data as $row) {
        if ($row['status_name'] === 'Screening Done') {
            // Check if the Lab Number should be excluded
            $exclude_lab_number = false;

            foreach ($data as $inner_row) {
                if ($inner_row['labno'] === $row['labno'] && 
                    in_array($inner_row['status_name'], ['Finalized', 'Diagnosis Completed', 'Report Ready', 'Report Delivered'])) {
                    $exclude_lab_number = true;
                    break;
                }
            }

            if (!$exclude_lab_number) {
                $filtered_data[] = $row;
            }
        }
    }

    return $filtered_data;
}

$filtered_data = filter_data($case_summary_list);



function finalized_filter_data($data) {
    $finalized_filtered_data = [];

    foreach ($data as $row) {
        if ($row['status_name'] === 'Finalized') {
            // Check if the Lab Number should be excluded
            $exclude_lab_number = false;

            foreach ($data as $inner_row) {
                if ($inner_row['labno'] === $row['labno'] && 
                    in_array($inner_row['status_name'], ['Report Ready', 'Report Delivered'])) {
                    $exclude_lab_number = true;
                    break;
                }
            }

            if (!$exclude_lab_number) {
                $finalized_filtered_data[] = $row;
            }
        }
    }

    return $finalized_filtered_data;
}

$finalized_filtered_data = finalized_filter_data($case_summary_list);


function unseen_filter_data($data) {
    $unseen_filtered_data = [];

    foreach ($data as $row) {
        // Check if the status name is one of the specified ones
        if (in_array($row['status_name'], ['Gross Completed', 'Gross Entry Done', 'Slides Prepared'])) {
            // Check if the Lab Number should be excluded
            $exclude_lab_number = false;

            foreach ($data as $inner_row) {
                if ($inner_row['labno'] === $row['labno'] && in_array($inner_row['status_name'], ['Screening Done', 'Finalized', 'Diagnosis Completed', 'Report Ready', 'Report Delivered'])) {
                    $exclude_lab_number = true;
                    break;
                }
            }

            if (!$exclude_lab_number) {
                $unseen_filtered_data[] = $row;
            }
        }
    }

    return $unseen_filtered_data;
}

// Assuming $case_summary_list is your dataset
$unseen_filtered_data = unseen_filter_data($case_summary_list);

print('<link href="../bootstrap-3.4.1-dist/css/bootstrap.min.css" rel="stylesheet">');
print("<style>
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
    border: 1px solid #ccc;
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
    border: 1px solid #ccc;
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
        
        <div class="container text-center" style="margin-top: 20px;">
                <div class="row">

                    <div class="col-sm-4">
                    <button class="btn btn-primary btn-lg tablink" style="border-radius: 50%; width: 100px; height: 100px;" onclick="openTab(event, \'DoctorRelatedInstructions\')">
                        <i class="glyphicon glyphicon-user" style="font-size: 24px;"></i><br>Notes
                    </button>
                    </div>

                    <div class="col-sm-4">
                    <button class="btn btn-success btn-lg tablink" style="border-radius: 50%; width: 100px; height: 100px;" onclick="openTab(event, \'CaseStatus\')">
                        <i class="glyphicon glyphicon-bell" style="font-size: 24px;"></i><br>Status
                    </button>
                    </div>

                    <div class="col-sm-4">
                    <button class="btn btn-info btn-lg tablink" style="border-radius: 50%; width: 100px; height: 100px;" onclick="openTab(event, \'ReportCompleteStatus\')">
                        <i class="glyphicon glyphicon-ok-circle" style="font-size: 24px;"></i><br>Done
                    </button>
                    </div>

                </div>
        </div>
       <br><br><br><br><br><br><br><br>

       
        <!-- Tab Content for Doctor Related Instructions -->
        <div id="DoctorRelatedInstructions" class="tabcontent">
              <!-- Sub-tab Links -->
              <div class="sub-tabs">
                <div class="sub-tab-links">
                  <button style="border:none" class="sub-tablink btn-info" onclick="openSubTab(event, \'List\')">
                  <i class="fas fa-list" style="font-size: 25px;"></i><b>&nbspList</b></button>
                  <button style="border:none" class="sub-tablink btn-success" onclick="openSubTab(event, \'InProgress\')">
                  <i class="fas fa-spinner" style="font-size: 35px;"></i>In Progress</button>
                  <button style="border:none" class="sub-tablink btn-secondary" onclick="openSubTab(event, \'Completed\')">
                  <i class="fas fa-check-circle" style="font-size: 35px;"></i>Done</button>
                  <button style="border:none" class="sub-tablink btn btn-warning" onclick="openSubTab(event, \'OnHold\')">
                    <i class="fas fa-pause-circle" style="font-size: 35px;"></i>On Hold
                  </button>
                </div>
                
                <!-- Sub-tab contents -->
                <div id="List" class="subtabcontent">
                    <table id="doctorInstructionTable" class="table">
                        <thead>
                            <div style="display: flex; justify-content: flex-end;">
                                <button id="submitStatusChanges" class="btn btn-primary" style="margin-bottom: 10px;">Submit</button>  
                            </div>
                            <tr>
                                <th>Lab Number</th>
                                <th>Instructions</th>
                                <th>Patient History</th>
                                <th>Doctor Name</th>
                                <th>Date</th>
                                <th>Notified Method</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>

                    <script>
                        const doctorInstructionList = ' . json_encode($doctor_instruction_list) . ';
                        console.log("doctor Instructions : ", doctorInstructionList);
                        const loggedInUserId = ' . json_encode($loggedInUserId) . ';
                        const loggedInUsername = ' . json_encode($loggedInUsername) . ';

                        let statusChanges = {};

                        // Function to format date and time
                        function formatDateTime(dateTimeStr) {
                            const date = new Date(dateTimeStr);
                            const optionsDate = { day: "numeric", month: "long", year: "numeric" };
                            const optionsTime = { hour: "numeric", minute: "numeric", hour12: true };
                            const formattedDate = date.toLocaleDateString("en-GB", optionsDate);
                            const formattedTime = date.toLocaleTimeString("en-GB", optionsTime);
                            return `${formattedDate} ${formattedTime}`;
                        }

                        function groupByLabNumber(data) {
                            const groupedData = {};
                            data.forEach(item => {
                                const labNumber = item["Lab Number"];
                                if (!groupedData[labNumber]) {
                                    groupedData[labNumber] = [];
                                }
                                groupedData[labNumber].push(item);
                            });
                            return groupedData;
                        }

                        // Function to filter and display the data
                        function generateFilteredTableRows(groupedData) {
                            let rows = "";
                            Object.keys(groupedData).forEach(labNumber => {
                                const descriptions = groupedData[labNumber].map(item => item["Description"]);

                                // If "Transcription" is found, show all descriptions for that lab number
                                if (descriptions.includes("Transcription")) {
                                    groupedData[labNumber].forEach(item => {
                                        if (!["IT Space", "4", "Self", "Transcription"].includes(item["Description"])) {
                                            rows += `
                                                <tr>
                                                    <td>${item["Lab Number"]}</td>
                                                    <td>${item["Description"]}</td>
                                                    <td>${item["Status Name"]}</td>
                                                    <td>${item["User Name"]}</td>
                                                    <td>${formatDateTime(item.TrackCreateTime)}</td>
                                                     <td>
                                                        <select data-communication-type="${item.track_id}">
                                                            <option value="">Select Communication</option>
                                                            <option value="Whatsapp">Whatsapp</option>
                                                            <option value="By Call">By Call</option>
                                                            <option value="Message">Message</option>
                                                            <option value="Face-to-face conversations">Face-to-face conversations</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <select data-track-id="${item.track_id}" data-lab-number="${item["Lab Number"]}">
                                                            <option value="">Select</option>
                                                            <option value="In-Progress">In-Progress</option>
                                                            <option value="On-Hold">On-Hold</option>
                                                            <option value="Done">Done</option>
                                                        </select>
                                                    </td>
                                                </tr>
                                            `;
                                        }
                                    });
                                } 
                                // Filter out descriptions that include "IT Space," "4," or "Self"
                                else {
                                    groupedData[labNumber].forEach(item => {
                                        if (!["IT Space", "4", "Self"].includes(item["Description"])) {
                                            rows += `
                                                <tr>
                                                    <td>${item["Lab Number"]}</td>
                                                    <td>${item["Description"]}</td>
                                                    <td>${item["Status Name"]}</td>
                                                    <td>${item["User Name"]}</td>
                                                    <td>${formatDateTime(item.TrackCreateTime)}</td>
                                                    <td>
                                                        <select data-communication-type="${item.track_id}">
                                                            <option value="">Select Communication</option>
                                                            <option value="Whatsapp">Whatsapp</option>
                                                            <option value="By Call">By Call</option>
                                                            <option value="Message">Message</option>
                                                            <option value="Face-to-face conversations">Face-to-face conversations</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <select data-track-id="${item.track_id}" data-lab-number="${item["Lab Number"]}">
                                                            <option value="">Select</option>
                                                            <option value="In-Progress">In-Progress</option>
                                                            <option value="On-Hold">On-Hold</option>
                                                            <option value="Done">Done</option>
                                                        </select>
                                                    </td>
                                                </tr>
                                            `;
                                        }
                                    });
                                }
                            });
                            return rows;
                        }

                        // Define the trackStatusChange function
                        function trackStatusChange(trackId, labNumber, statusValue, communicationMethod) {
                                if (!statusValue && !communicationMethod) {
                                    delete statusChanges[trackId];
                                } else {
                                    if (!statusChanges[trackId]) {
                                        statusChanges[trackId] = {
                                            labNumber: labNumber,
                                            username: loggedInUsername
                                        };
                                    }
                                    if (statusValue) {
                                        statusChanges[trackId].status = statusValue;
                                    }
                                    if (communicationMethod) {
                                        statusChanges[trackId].communication = communicationMethod;
                                    }
                                }

                            console.log("Status Changes:", statusChanges); // For debugging
                        }


                       // Add the event listener for the select elements
                        document.addEventListener("DOMContentLoaded", () => {
                                const groupedData = groupByLabNumber(doctorInstructionList);
                                document.querySelector("#doctorInstructionTable tbody").innerHTML = generateFilteredTableRows(groupedData);

                                // Status dropdown listener
                                document.querySelectorAll("select[data-track-id]").forEach(selectElement => {
                                    selectElement.addEventListener("change", function () {
                                        const trackId = this.getAttribute("data-track-id");
                                        const labNumber = this.getAttribute("data-lab-number");
                                        const statusValue = this.value;

                                        // Get matching communication dropdown
                                        const communicationSelect = document.querySelector(`select[data-communication-type=\'${trackId}\']`);
                                        const communicationMethod = communicationSelect ? communicationSelect.value : "";

                                        trackStatusChange(trackId, labNumber, statusValue, communicationMethod);
                                    });
                                });

                                // Communication dropdown listener
                                document.querySelectorAll("select[data-communication-type]").forEach(selectElement => {
                                    selectElement.addEventListener("change", function () {
                                        const trackId = this.getAttribute("data-communication-type");

                                        // Get the lab number from matching status select
                                        const statusSelect = document.querySelector(`select[data-track-id=\'${trackId}\']`);
                                        const labNumber = statusSelect ? statusSelect.getAttribute("data-lab-number") : "";
                                        const statusValue = statusSelect ? statusSelect.value : "";
                                        const communicationMethod = this.value;

                                        trackStatusChange(trackId, labNumber, statusValue, communicationMethod);
                                    });
                                });
                        });

                        // Submit the status changes
                        document.getElementById("submitStatusChanges").addEventListener("click", function() {
                            fetch("patient_history_status.php", {
                                method: "POST",
                                headers: {
                                    "Content-Type": "application/json"
                                },
                                body: JSON.stringify({
                                    loggedInUserId: loggedInUserId,
                                    values: statusChanges
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    console.log("Status changes saved successfully.");
                                    statusChanges = {}; // Clear the changes after successful save
                                    // Optionally, reload the page or refresh the table to reflect the changes
                                    window.location.reload();
                                } else {
                                    alert(data.message || "Failed to save status changes.");
                                }
                            })
                            .catch(error => {
                                console.error("Error:", error);
                            });
                        });

                    </script>
                </div>


                <div id="InProgress" class="subtabcontent">
                     <table id="progressInstructionTable" class="table">
                        <thead>
                            <div style="display: flex; justify-content: flex-end;">
                                <button id="progress_submitStatusChanges" class="btn btn-primary" style="margin-bottom: 10px;">Submit</button>  
                            </div>
                            <tr>
                                <th>Lab Number</th>
                                <th>Instructions</th>
                                <th>Patient History</th>
                                <th>Doctor Name</th>
                                <th>Date</th>
                                <th>Previous Status Update User</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>

                    <script>
                        const progressInstructionList = ' . json_encode($progress_instruction_list) . ';
                        console.log("progress Instructions : ", progressInstructionList);
                        

                        let progress_statusChanges = {};

                        // Function to format date and time
                        function formatDateTime(dateTimeStr) {
                            const date = new Date(dateTimeStr);
                            const optionsDate = { day: "numeric", month: "long", year: "numeric" };
                            const optionsTime = { hour: "numeric", minute: "numeric", hour12: true };
                            const formattedDate = date.toLocaleDateString("en-GB", optionsDate);
                            const formattedTime = date.toLocaleTimeString("en-GB", optionsTime);
                            return `${formattedDate} ${formattedTime}`;
                        }

                        function groupByLabNumber(data) {
                            const groupedData = {};
                            data.forEach(item => {
                                const labNumber = item["Lab Number"];
                                if (!groupedData[labNumber]) {
                                    groupedData[labNumber] = [];
                                }
                                groupedData[labNumber].push(item);
                            });
                            return groupedData;
                        }

                        // Function to filter and display the data
                        // function progress_generateFilteredTableRows(groupedData) {
                        //         let rows = "";
                        //         Object.keys(groupedData).forEach(labNumber => {
                        //             const descriptions = groupedData[labNumber].map(item => item["Description"]);
                                    
                        //             // If "Transcription" is found, show all descriptions for that lab number
                        //             if (descriptions.includes("Transcription")) {
                        //                 groupedData[labNumber].forEach(item => {
                        //                     rows += `
                        //                         <tr>
                        //                             <td>${item["Lab Number"]}</td>
                        //                             <td>${item["Description"]}</td>
                        //                             <td>${item["Status Name"]}</td>
                        //                             <td>${item["User Name"]}</td>
                        //                             <td>${formatDateTime(item.TrackCreateTime)}</td>
                        //                             <td>${item["StatusUpdateUser"]}</td>
                        //                             <td>
                        //                                 <select data-track-id="${item.track_id}" data-lab-number="${item["Lab Number"]}">
                        //                                     <option value="">Select</option>
                        //                                     <option value="In-Progress">In-Progress</option>
                        //                                     <option value="On-Hold">On-Hold</option>
                        //                                     <option value="Done">Done</option>
                        //                                 </select>
                        //                             </td>
                        //                         </tr>
                        //                     `;
                        //                 });
                        //             } 
                        //             // If any of "IT Space," "4," or "Self" is found, skip this lab number entirely
                        //             else if (!descriptions.some(desc => ["IT Space", "4", "Self"].includes(desc))) {
                        //                 groupedData[labNumber].forEach(item => {
                        //                     rows += `
                        //                         <tr>
                        //                             <td>${item["Lab Number"]}</td>
                        //                             <td>${item["Description"]}</td>
                        //                             <td>${item["Status Name"]}</td>
                        //                             <td>${item["User Name"]}</td>
                        //                             <td>${formatDateTime(item.TrackCreateTime)}</td>
                        //                             <td>${item["StatusUpdateUser"]}</td>
                        //                             <td>
                        //                                 <select data-track-id="${item.track_id}" data-lab-number="${item["Lab Number"]}">
                        //                                     <option value="">Select</option>
                        //                                     <option value="On-Hold">On-Hold</option>
                        //                                     <option value="Done">Done</option>
                        //                                 </select>
                        //                             </td>
                        //                         </tr>
                        //                     `;
                        //                 });
                        //             }
                        //         });
                        //         return rows;
                        // }

                        function progress_generateFilteredTableRows(groupedData) {
                                   let rows = "";
                                    Object.keys(groupedData).forEach(labNumber => {
                                        const descriptions = groupedData[labNumber].map(item => item["Description"]);

                                        if (descriptions.includes("Transcription")) {
                                            groupedData[labNumber].forEach(item => {
                                                rows += generateRow(item);
                                            });
                                        } else if (!descriptions.some(desc => ["IT Space", "4", "Self"].includes(desc))) {
                                            groupedData[labNumber].forEach(item => {
                                                rows += generateRow(item);
                                            });
                                        }
                                    });
                                    return rows;

                                    function generateRow(item) {
                                            let statusUpdateUserFormatted = "";
                                            try {
                                                const userArr = JSON.parse(item["StatusUpdateUser"]);
                                                if (Array.isArray(userArr) && userArr.length > 0) {
                                                    const userObj = userArr[0]; // first object in array
                                                    const key = Object.keys(userObj)[0]; // e.g., "in_progress"
                                                    const value = userObj[key]; // e.g., "tasim"
                                                    const readableKey = key.replace(/_/g, " ").replace(/\b\w/g, c => c.toUpperCase()); // "In Progress"
                                                    statusUpdateUserFormatted = `${readableKey} - ${value}`;
                                                }
                                            } catch (e) {
                                                statusUpdateUserFormatted = item["StatusUpdateUser"]; // fallback
                                            }

                                            return `
                                                <tr>
                                                    <td>${item["Lab Number"]}</td>
                                                    <td>${item["Description"]}</td>
                                                    <td>${item["Status Name"]}</td>
                                                    <td>${item["User Name"]}</td>
                                                    <td>${formatDateTime(item.TrackCreateTime)}</td>
                                                    <td>${statusUpdateUserFormatted}</td>
                                                    <td>
                                                        <select data-track-id="${item.track_id}" data-lab-number="${item["Lab Number"]}">
                                                            <option value="">Select</option>
                                                            <option value="On-Hold">On-Hold</option>
                                                            <option value="Done">Done</option>
                                                        </select>
                                                    </td>
                                                </tr>
                                            `;
                                    }

                        }


                        // Define the trackStatusChange function
                        function progress_trackStatusChange(trackId, labNumber, statusValue) {
                            if (!statusValue) {
                                delete progress_statusChanges[trackId];
                            } else {
                                statusChanges[trackId] = {
                                    labNumber: labNumber,
                                    status: statusValue,
                                    username: loggedInUsername
                                };
                            }

                            console.log("Status Changes:", progress_statusChanges); // For debugging
                        }


                       // Add the event listener for the select elements
                        document.addEventListener("DOMContentLoaded", () => {
                            const groupedData = groupByLabNumber(progressInstructionList);
                            document.querySelector("#progressInstructionTable tbody").innerHTML = progress_generateFilteredTableRows(groupedData);

                            document.querySelectorAll("select").forEach(selectElement => {
                                selectElement.addEventListener("change", function() {
                                    const trackId = this.getAttribute("data-track-id");
                                    const labNumber = this.getAttribute("data-lab-number");
                                    const statusValue = this.value;
                                    trackStatusChange(trackId, labNumber, statusValue);
                                });
                            });
                        });


                        // Submit the status changes
                        document.getElementById("progress_submitStatusChanges").addEventListener("click", function() {
                            fetch("patient_history_status.php", {
                                method: "POST",
                                headers: {
                                    "Content-Type": "application/json"
                                },
                                body: JSON.stringify({
                                    loggedInUserId: loggedInUserId,
                                    values: statusChanges
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    console.log("Status changes saved successfully.");
                                    statusChanges = {}; // Clear the changes after successful save
                                    // Optionally, reload the page or refresh the table to reflect the changes
                                    window.location.reload();
                                } else {
                                    alert(data.message || "Failed to save status changes.");
                                }
                            })
                            .catch(error => {
                                console.error("Error:", error);
                            });
                        });

                    </script>
                </div>

                <div id="Completed" class="subtabcontent">
                        <table id="CompletedInstructionTable" class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Lab Number</th>
                                    <th>Instruction</th>
                                    <th>User Name</th>
                                    <th>Previous Status Update User</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>

                        <!-- Pagination Controls -->
                        <div id="paginationControls"></div>

                        <script>
                            const completed_instruction_list = ' . json_encode($complete_instruction_list) . ';
                            
                            let completed_statusChanges = {};

                            // Pagination variables
                            const rowsPerPage = 20; // Number of rows per page
                            let currentPage = 1;
                            let totalPages = 0;

                            

                            // Filter the list based on the conditions
                            const completed_filteredDoctorInstructionList = completed_instruction_list.filter(item =>
                                (item["Section"]) &&
                                (item["Status Name"])
                            );
                            
                            // Calculate total pages
                            totalPages = Math.ceil(completed_filteredDoctorInstructionList.length / rowsPerPage);

                            // Function to format date and time
                            function formatDateTime(dateTimeStr) {
                                const date = new Date(dateTimeStr);
                                const optionsDate = { day: "numeric", month: "long", year: "numeric" };
                                const optionsTime = { hour: "numeric", minute: "numeric", hour12: true };
                                const formattedDate = date.toLocaleDateString("en-GB", optionsDate);
                                const formattedTime = date.toLocaleTimeString("en-GB", optionsTime);
                                return `${formattedDate} ${formattedTime}`;
                            }

                            // Function to generate table rows with pagination
                            // function completed_generateTableRows(data, page = 1) {
                            //     const start = (page - 1) * rowsPerPage;
                            //     const end = start + rowsPerPage;
                            //     const paginatedData = data.slice(start, end);

                            //     return paginatedData.map(item => `
                            //         <tr>
                            //             <td>${formatDateTime(item["TrackCreateTime"])}</td>
                            //             <td>${item["Lab Number"]}</td>
                            //             <td>${item["Status Name"]}</td>
                            //             <td>${item["User Name"]}</td>
                            //             <td>${item["StatusUpdateUser"]}</td>
                            //         </tr>
                            //     `).join("");
                            // }

                            function completed_generateTableRows(data, page = 1) {
                                    const start = (page - 1) * rowsPerPage;
                                    const end = start + rowsPerPage;
                                    const paginatedData = data.slice(start, end);

                                    return paginatedData.map(item => {
                                        let statusUpdateUserFormatted = "";
                                        try {
                                            const userArr = JSON.parse(item["StatusUpdateUser"]);
                                            if (Array.isArray(userArr)) {
                                                statusUpdateUserFormatted = userArr.map(obj => {
                                                    const key = Object.keys(obj)[0]; // e.g., "done"
                                                    const value = obj[key]; // e.g., "tasim"
                                                    const readableKey = key.replace(/_/g, " ").replace(/\b\w/g, c => c.toUpperCase()); // "Done"
                                                    return `${readableKey} - ${value}`;
                                                }).join(", ");
                                            }
                                        } catch (e) {
                                            statusUpdateUserFormatted = item["StatusUpdateUser"]; // fallback
                                        }

                                        return `
                                            <tr>
                                                <td>${formatDateTime(item["TrackCreateTime"])}</td>
                                                <td>${item["Lab Number"]}</td>
                                                <td>${item["Status Name"]}</td>
                                                <td>${item["User Name"]}</td>
                                                <td>${statusUpdateUserFormatted}</td>
                                            </tr>
                                        `;
                                    }).join("");
                            }


                            // Function to render pagination controls
                            function renderPaginationControls() {
                                let controls = "";

                                for (let i = 1; i <= totalPages; i++) {
                                    controls += `<button class="pagination-btn btn btn-primary" data-page="${i}">${i}</button>`;
                                }

                                document.getElementById("paginationControls").innerHTML = controls;

                                // Add event listeners to pagination buttons
                                document.querySelectorAll(".pagination-btn").forEach(button => {
                                    button.addEventListener("click", function() {
                                        currentPage = parseInt(this.getAttribute("data-page"));
                                        renderTable();
                                    });
                                });
                            }

                            // Function to render the table and pagination
                            function renderTable() {
                                document.querySelector("#CompletedInstructionTable tbody").innerHTML = completed_generateTableRows(completed_filteredDoctorInstructionList, currentPage);

                                // Add event listeners to all select elements after rows are inserted
                                document.querySelectorAll("select").forEach(selectElement => {
                                    selectElement.addEventListener("change", function() {
                                        trackStatusChange(this.getAttribute("data-lab-number"), this.value);
                                    });
                                });
                            }

                            // Initialize the table and pagination
                            renderTable();
                            renderPaginationControls();
                        </script>
                </div>


                <div id="OnHold" class="subtabcontent">
                   <table id="onHoldInstructionTable" class="table">
                        <thead>
                            <div style="display: flex; justify-content: flex-end;">
                                <button id="onHoldsubmitStatusChanges" class="btn btn-primary" style="margin-bottom: 10px;">Submit</button>  
                            </div>
                            <tr>
                                <th>Date</th>
                                <th>Lab Number</th>
                                <th>Section</th>
                                <th>Instruction</th>
                                <th>Doctor Name</th>
                                <th>Previous Status Update User</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                    <script>
                        const onHold_instruction_list = ' . json_encode($on_hold_instruction_list) . ';
                        

                        let onHoldstatusChanges = {};

                        

                        // Filter the list based on the conditions
                        const onHoldfilteredDoctorInstructionList = onHold_instruction_list.filter(item =>
                            (item["Section"]) &&
                            (item["Status Name"])
                        );

                        // Function to format date and time
                        function formatDateTime(dateTimeStr) {
                            const date = new Date(dateTimeStr);
                            const optionsDate = { day: "numeric", month: "long", year: "numeric" };
                            const optionsTime = { hour: "numeric", minute: "numeric", hour12: true };
                            const formattedDate = date.toLocaleDateString("en-GB", optionsDate);
                            const formattedTime = date.toLocaleTimeString("en-GB", optionsTime);
                            return `${formattedDate} ${formattedTime}`;
                        }

                        // Function to generate table rows
                        // function onHoldgenerateTableRows(data) {
                        //     return data.map(item => `
                        //         <tr>
                        //             <td>${formatDateTime(item["TrackCreateTime"])}</td>
                        //             <td>${item["Lab Number"]}</td>
                        //             <td>${item["Description"]}</td>
                        //             <td>${item["Status Name"]}</td>
                        //             <td>${item["User Name"]}</td>
                        //             <td>${item["StatusUpdateUser"]}</td>
                        //             <td>
                        //                 <select data-track-id="${item["track_id"]}" data-lab-number="${item["Lab Number"]}">
                        //                     <option value="">Select</option>
                        //                     <option value="In-Progress">In-Progress</option>
                        //                 </select>
                        //             </td>
                        //         </tr>
                        //     `).join("");
                        // }

                        function onHoldgenerateTableRows(data) {
                                return data.map(item => {
                                    let statusUpdateUserFormatted = "";
                                    try {
                                        const userArr = JSON.parse(item["StatusUpdateUser"]);
                                        if (Array.isArray(userArr)) {
                                            statusUpdateUserFormatted = userArr.map(obj => {
                                                const key = Object.keys(obj)[0]; // e.g., "in_progress"
                                                const value = obj[key]; // e.g., "tasim"
                                                const readableKey = key.replace(/_/g, " ").replace(/\b\w/g, c => c.toUpperCase()); // "In Progress"
                                                return `${readableKey} - ${value}`;
                                            }).join(", ");
                                        }
                                    } catch (e) {
                                        statusUpdateUserFormatted = item["StatusUpdateUser"]; // fallback
                                    }

                                    return `
                                        <tr>
                                            <td>${formatDateTime(item["TrackCreateTime"])}</td>
                                            <td>${item["Lab Number"]}</td>
                                            <td>${item["Description"]}</td>
                                            <td>${item["Status Name"]}</td>
                                            <td>${item["User Name"]}</td>
                                            <td>${statusUpdateUserFormatted}</td>
                                            <td>
                                                <select data-track-id="${item["track_id"]}" data-lab-number="${item["Lab Number"]}">
                                                    <option value="">Select</option>
                                                    <option value="In-Progress">In-Progress</option>
                                                </select>
                                            </td>
                                        </tr>
                                    `;
                                }).join("");
                        }

                        // Track status changes with unique track_id
                        function onHoldtrackStatusChange(trackId, labNumber, status) {
                            onHoldstatusChanges[trackId] = { labNumber: labNumber, status: status };
                        }

                        // Add event listeners to all select elements
                        document.addEventListener("DOMContentLoaded", () => {
                            document.querySelector("#onHoldInstructionTable tbody").innerHTML = onHoldgenerateTableRows(onHoldfilteredDoctorInstructionList);

                            document.querySelectorAll("select").forEach(selectElement => {
                                selectElement.addEventListener("change", function() {
                                    const trackId = this.getAttribute("data-track-id");
                                    const labNumber = this.getAttribute("data-lab-number");
                                    trackStatusChange(trackId, labNumber, this.value);
                                });
                            });
                        });

                        // Submit the status changes
                        document.getElementById("onHoldsubmitStatusChanges").addEventListener("click", function() {
                            fetch("patient_history_status.php", {
                                method: "POST",
                                headers: {
                                    "Content-Type": "application/json"
                                },
                                body: JSON.stringify({
                                    loggedInUserId: loggedInUserId,
                                    values: statusChanges,
                                    username: loggedInUsername
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    console.log("Status changes saved successfully.");
                                    statusChanges = {}; // Clear the changes after successful save
                                    // Optionally, reload the page or refresh the table to reflect the changes
                                    window.location.reload();
                                } else {
                                    alert(data.message || "Failed to save status changes.");
                                }
                            })
                            .catch(error => {
                                console.error("Error:", error);
                            });
                        });
                    </script>
                </div>
                
              </div>
        </div>
         
        <div id="CaseStatus" class="tabcontent">
                    
            
                
            <div class="form-group mb-3">
                <label for="status-type-filter">Status Type:</label>
                <select id="status-type-filter" class="form-control">
                    <option value="">All</option>
                    <option value="ScreeningDoneList">Screening Done List</option>
                    <option value="FinalizedList">Finalized List</option>
                    <option value="UnSeen">UnSeen</option>
                    <option value="ReportEnteryCompleted">Report Entry Completed</option>
                </select>
            </div>

                <div id="all-status-contents">

                    <!-- Sub-tab contents -->
                    <div id="ScreeningDoneList" class="subtabcontent">
                            <!-- Filter Controls -->
                            <div class="mb-4">
                                <div class="d-flex justify-content-start align-items-center mb-3">
                                    <div class="form-group">
                                        <label for="date-filter">Date:</label>
                                        <input type="date" id="date-filter" class="form-control">
                                    </div>

                                    <div class="form-group mr-3">
                                        <label for="delivery-date-filter">Delivery Date:</label>
                                        <input type="date" id="delivery-date-filter" class="form-control">
                                    </div>

                                    <button id="apply-filter" class="btn btn-primary ml-3">Apply Filter</button>
                                </div>
                            </div>

                            <!-- Data Table -->
                            <div id="screening-done-count-display-container" class="mb-4"></div>
                            <div id="list-tab-content-container"></div>
                    </div>
          
                    <div id="FinalizedList" class="subtabcontent">
                            <!-- Filter Controls -->
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    
                                    <!-- Date Filter -->
                                    <div class="form-group">
                                        <label for="final-date-filter">Date:</label>
                                        <input type="date" id="final-date-filter" class="form-control">
                                    </div>

                                    <!-- Delivery Date Filter -->
                                    <div class="form-group">
                                        <label for="final-delivery-date-filter">Delivery Date:</label>
                                        <input type="date" id="final-delivery-date-filter" class="form-control">
                                    </div>

                                    <!-- Apply Filter Button -->
                                    <button id="final-apply-filter" class="btn btn-primary ml-3">Apply Filter</button>
                                </div>
                            </div>

                        <!-- Data Table -->
                        <div id="doctor-wise-count-container" class="mb-4"></div>
                        <div id="final-screening-done-count-display-container" class="mb-4"></div>
                        <div id="final-list-tab-content-container"></div>
                    </div>

                    <div id="UnSeen" class="subtabcontent">
                            <!-- Filter Controls -->
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                                
                                <!-- Date Filter -->
                                <div class="form-group">
                                    <label for="unseen-date-filter">Date:</label>
                                    <input type="date" id="unseen-date-filter" class="form-control">
                                </div>

                                <!-- Delivery Date Filter -->
                                <div class="form-group">
                                    <label for="unseen-delivery-date-filter">Delivery Date:</label>
                                    <input type="date" id="unseen-delivery-date-filter" class="form-control">
                                </div>

                                <!-- Apply Filter Button -->
                                <button id="unseen-apply-filter" class="btn btn-primary ml-3 mt-2 mt-md-0">Apply Filter</button>
                                </div>
                            </div>

                            <!-- Data Table -->
                            <div id="unseen-count-display-container" class="mb-4"></div>
                            <div id="unseen-list-tab-content-container"></div>
                    </div>


                    <div id="ReportEnteryCompleted" class="subtabcontent">
                        <h3>Completed List</h3>
                    </div>

                </div>
                
                   
        </div>
       
        <div id="ReportCompleteStatus" class="tabcontent">
                    <div class="container">
                        <input type="date" id="fromDateTime" class="input-field" placeholder="From">
                        <input type="date" id="toDateTime" class="input-field" placeholder="To">
                        <button id="submitBtn" class="btn">Submit</button>
                    </div>
                   
        </div>
</div>';

echo '
<script>
      function openTab(evt, tabName) {
        var i, tabcontent, tablink;
        tabcontent = document.getElementsByClassName("tabcontent");
        for (i = 0; i < tabcontent.length; i++) {
          tabcontent[i].style.display = "none";
        }
        tablink = document.getElementsByClassName("tablink");
        for (i = 0; i < tablink.length; i++) {
          tablink[i].className = tablink[i].className.replace(" active", "");
        }
        document.getElementById(tabName).style.display = "block";
        evt.currentTarget.className += " active";
      }

      function openSubTab(evt, subTabName) {
        var i, subtabcontent, subtablink;
        subtabcontent = document.getElementsByClassName("subtabcontent");
        for (i = 0; i < subtabcontent.length; i++) {
            subtabcontent[i].style.display = "none";
        }
        subtablink = document.getElementsByClassName("sub-tablink");
        for (i = 0; i < subtablink.length; i++) {
            subtablink[i].className = subtablink[i].className.replace(" active", "");
        }
        document.getElementById(subTabName).style.display = "block";
        evt.currentTarget.className += " active";
      }

      // Set the default tab to be open
      document.getElementsByClassName("tablink")[0].click();

      // Set the default sub-tab to be open
      // Ensure no sub-tab is active by default
      window.onload = function() {
        var subtabcontent = document.getElementsByClassName("subtabcontent");
        for (var i = 0; i < subtabcontent.length; i++) {
            subtabcontent[i].style.display = "none";
        }
      };
</script>';


?>

<script>
    // Function to count unique labno values
    function countUniqueLabnos(data) {
        const labnoSet = new Set();
        data.forEach(item => {
            if (item['labno']) {
                labnoSet.add(item['labno']);
            }
        });
        return labnoSet.size;
    }
</script>

<!-- <script>
        const case_summary_list = <?php echo json_encode($filtered_data); ?> 

        // Initialize filter options
        function generateFilterOptions() {
            const statusNames = new Set();
            const labRoomStatuses = new Set();

            case_summary_list.forEach(item => {
                statusNames.add(item['status_name']);
                labRoomStatuses.add(item['LabRoomStatus']); // Adjust if 'LabRoomStatus' is not in your data
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

            const filteredData = case_summary_list.filter(item => {
                const matchesStatus = selectedStatus ? item['status_name'] === selectedStatus : true;
                const matchesLabRoom = selectedLabRoomStatus ? item['LabRoomStatus'] === selectedLabRoomStatus : true;
                const matchesDate = selectedDate ? new Date(item['create_time']).toISOString().split('T')[0] === selectedDate : true;
                return matchesStatus && matchesLabRoom && matchesDate;
            });

            
            // Get count of unique lab numbers
            const uniqueLabnoCount = countUniqueLabnos(filteredData);

            displayListData(filteredData, uniqueLabnoCount);
        });

        // Display the data in a table format
        function displayListData(data, uniqueLabnoCount) {
                const countDisplayContainer = document.getElementById('screening-done-count-display-container');
                const listDetailsContainer = document.getElementById('list-tab-content-container');
                listDetailsContainer.innerHTML = ''; // Clear existing details

                const total_case_summary_list = countUniqueLabnos(case_summary_list);
                
                // Display the total count of unique lab numbers
                if (countDisplayContainer) {
                    // Check if uniqueLabnoCount is undefined or null
                    if (uniqueLabnoCount == null) {
                        // Ensure total_case_summary_list is defined and used properly
                        countDisplayContainer.innerHTML = `<p>Total Screening Done: ${total_case_summary_list || 0}</p>`;
                    } else {
                        // Show the unique lab number count
                        countDisplayContainer.innerHTML = `<p>Total Screening Done: ${uniqueLabnoCount}</p>`;
                    }
                } else {
                    console.error('Count display container not found.');
                }

                
                const listTable = document.createElement('table');
                listTable.classList.add('table');
                listTable.style.borderCollapse = 'collapse'; // Optional: To remove space between borders
                listTable.style.width = '100%'; // Optional: To make the table use full width

                // Create table header
                const headerRow = document.createElement('tr');
                ['Lab Number', 'Status Name', 'Date', 'Doctor'].forEach(headerText => {
                    const th = document.createElement('th');
                    th.textContent = headerText;
                    th.style.border = '1px solid black'; // Add border to header cells
                    th.style.padding = '8px'; // Optional: Add padding to cells
                    headerRow.appendChild(th);
                });
                listTable.appendChild(headerRow);

                // Populate table rows
                data.forEach(item => {
                    const row = document.createElement('tr');
                    ['labno', 'status_name', 'create_time', 'user_name'].forEach(key => {
                        const td = document.createElement('td');
                        if (key === 'create_time') {
                            const date = new Date(item[key]);
                            const options = { day: 'numeric', month: 'short', year: 'numeric' };
                            td.textContent = date.toLocaleDateString('en-GB', options);
                        } else {
                            td.textContent = item[key] || 'N/A';
                        }
                        td.style.border = '1px solid black'; // Add border to data cells
                        td.style.padding = '8px'; // Optional: Add padding to cells
                        row.appendChild(td);
                    });
                    listTable.appendChild(row);
                });

                listDetailsContainer.appendChild(listTable);
        }

       

        // Initialize the page
        generateFilterOptions();
        displayListData(case_summary_list);
</script> -->

<script>
    const case_summary_list = <?php echo json_encode($filtered_data); ?>;

    // Apply filters (only by date)
    document.getElementById('apply-filter').addEventListener('click', () => {
        const selectedCreateDate = document.getElementById('date-filter').value;
        const selectedDeliveryDate = document.getElementById('delivery-date-filter').value;

        function formatDateToInputString(date) {
            const d = new Date(date);
            const year = d.getFullYear();
            const month = (`0${d.getMonth() + 1}`).slice(-2);
            const day = (`0${d.getDate()}`).slice(-2);
            return `${year}-${month}-${day}`;
        }

        const filteredData = case_summary_list.filter(item => {
            const createDate = formatDateToInputString(item['create_time']);
            const deliveryDate = item['date_livraison'] ? formatDateToInputString(item['date_livraison']) : '';

            const createDateMatch = selectedCreateDate ? (createDate === selectedCreateDate) : true;
            const deliveryDateMatch = selectedDeliveryDate ? (deliveryDate === selectedDeliveryDate) : true;

            return createDateMatch && deliveryDateMatch;
        });

        const uniqueLabnoCount = countUniqueLabnos(filteredData);
        displayListData(filteredData, uniqueLabnoCount);
    });

    function countUniqueLabnos(data) {
        const uniqueLabnos = new Set();
        data.forEach(item => uniqueLabnos.add(item['labno']));
        return uniqueLabnos.size;
    }

    function displayListData(data, uniqueLabnoCount) {
            const countDisplayContainer = document.getElementById('screening-done-count-display-container');
            const listDetailsContainer = document.getElementById('list-tab-content-container');
            listDetailsContainer.innerHTML = '';

            const total_case_summary_list = countUniqueLabnos(case_summary_list);

            // Count how many times each doctor appears
            const doctorCounts = {};
            data.forEach(item => {
                const doctor = item['user_name'] || 'Unknown';
                doctorCounts[doctor] = (doctorCounts[doctor] || 0) + 1;
            });

            // Generate doctor count display HTML
            let doctorCountHTML = '<ul>';
            for (const doctor in doctorCounts) {
                doctorCountHTML += `<li>${doctor}: ${doctorCounts[doctor]}</li>`;
            }
            doctorCountHTML += '</ul>';

            if (countDisplayContainer) {
                countDisplayContainer.innerHTML = `
                    <br><br>
                    <p>Total Screening Done: ${uniqueLabnoCount ?? total_case_summary_list}</p>
                    <p><strong>Doctor-wise Count:</strong></p>
                    ${doctorCountHTML}
                `;
            }

            const listTable = document.createElement('table');
            listTable.classList.add('table');
            listTable.style.borderCollapse = 'collapse';
            listTable.style.width = '100%';

            const headerRow = document.createElement('tr');
            ['Doctor', 'Lab Number', 'Delivery Date', 'Status Name', 'Date'].forEach(headerText => {
                const th = document.createElement('th');
                th.textContent = headerText;
                th.style.border = '1px solid black';
                th.style.padding = '8px';
                headerRow.appendChild(th);
            });
            listTable.appendChild(headerRow);

            data.forEach(item => {
                const row = document.createElement('tr');
                ['user_name', 'labno', 'date_livraison', 'status_name', 'create_time'].forEach(key => {
                    const td = document.createElement('td');
                    if (key === 'create_time' || key === 'date_livraison') {
                        const date = new Date(item[key]);
                        const options = { day: 'numeric', month: 'short', year: 'numeric' };
                        td.textContent = isNaN(date.getTime()) ? 'N/A' : date.toLocaleDateString('en-GB', options);
                    } else {
                        td.textContent = item[key] || 'N/A';
                    }
                    td.style.border = '1px solid black';
                    td.style.padding = '8px';
                    row.appendChild(td);
                });
                listTable.appendChild(row);
            });

            listDetailsContainer.appendChild(listTable);
    }


    displayListData(case_summary_list, countUniqueLabnos(case_summary_list));
</script>



<!-- <script>
        // Sample Data
        const finalCaseSummaryList = <?php echo json_encode($finalized_filtered_data); ?>;

        // Initialize filter options
        function finalGenerateFilterOptions() {
            const statusNames = new Set();
            const labRoomStatuses = new Set();

            finalCaseSummaryList.forEach(item => {
                statusNames.add(item['status_name']);
                labRoomStatuses.add(item['LabRoomStatus']); // Adjust if 'LabRoomStatus' is not in your data
            });

            populateFinalSelectOptions('final-status-filter', Array.from(statusNames));
            populateFinalSelectOptions('labroom-filter', Array.from(labRoomStatuses));
        }

        // Populate select options
        function populateFinalSelectOptions(selectId, options) {
            const selectElement = document.getElementById(selectId);
            options.forEach(option => {
                const opt = document.createElement('option');
                opt.value = option;
                opt.text = option;
                selectElement.add(opt);
            });
        }

        // Apply filters
        document.getElementById('final-apply-filter').addEventListener('click', () => {
        const selectedDate = document.getElementById('final-date-filter').value;
        const selectedDeliveryDate = document.getElementById('delivery-date-filter').value;

        const filteredData = finalCaseSummaryList.filter(item => {
            const matchesScreeningDate = selectedDate
                ? new Date(item['create_time']).toISOString().split('T')[0] === selectedDate
                : true;

            const matchesDeliveryDate = selectedDeliveryDate
                ? new Date(item['date_livraison']).toISOString().split('T')[0] === selectedDeliveryDate
                : true;

            return matchesScreeningDate && matchesDeliveryDate;
        });

        const FinalizeduniqueLabnoCount = countUniqueLabnos(filteredData);
        renderFinalizedListTable(filteredData, FinalizeduniqueLabnoCount);
    });

        // Display the data in a table format
        function renderFinalizedListTable(data, FinalizeduniqueLabnoCount) {
            const countDisplayContainer = document.getElementById('final-screening-done-count-display-container');
            const listDetailsContainer = document.getElementById('final-list-tab-content-container');
            listDetailsContainer.innerHTML = ''; // Clear existing details

            const total_case_summary_list = countUniqueLabnos(finalCaseSummaryList);

            // Display the total count of unique lab numbers
            if (countDisplayContainer) {
                // Check if FinalizeduniqueLabnoCount is undefined or null
                if (FinalizeduniqueLabnoCount == null) {
                    // Ensure total_case_summary_list is defined and used properly
                    countDisplayContainer.innerHTML = `<p>Total Screening Done: ${total_case_summary_list || 0}</p>`;
                } else {
                    // Show the unique lab number count
                    countDisplayContainer.innerHTML = `<p>Total Screening Done: ${FinalizeduniqueLabnoCount}</p>`;
                }
            } else {
                console.error('Count display container not found.');
            }

            const listTable = document.createElement('table');
            listTable.classList.add('table');
            listTable.style.borderCollapse = 'collapse'; // Optional: To remove space between borders
            listTable.style.width = '100%'; // Optional: To make the table use full width

            // Create table header
            const headerRow = document.createElement('tr');
            ['Doctor', 'Lab Number', 'Delivery Date', 'Status Name', 'Date'].forEach(headerText => {
                const th = document.createElement('th');
                th.textContent = headerText;
                th.style.border = '1px solid black'; // Add border to header cells
                th.style.padding = '8px'; // Optional: Add padding to cells
                headerRow.appendChild(th);
            });
            listTable.appendChild(headerRow);

            // Populate table rows
            data.forEach(item => {
                const row = document.createElement('tr');
                ['user_name', 'labno', 'date_livraison', 'status_name', 'create_time'].forEach(key => {
                    const td = document.createElement('td');
                    if (key === 'create_time' || key === 'date_livraison') {
                        const date = new Date(item[key]);
                        const options = { day: 'numeric', month: 'short', year: 'numeric' };
                        td.textContent = isNaN(date.getTime()) ? 'N/A' : date.toLocaleDateString('en-GB', options);
                    } else {
                        td.textContent = item[key] || 'N/A';
                    }
                    td.style.border = '1px solid black'; // Add border to data cells
                    td.style.padding = '8px'; // Optional: Add padding to cells
                    row.appendChild(td);
                });
                listTable.appendChild(row);
            });

            listDetailsContainer.appendChild(listTable);
        }

        // Initialize the page
        finalGenerateFilterOptions();
        renderFinalizedListTable(finalCaseSummaryList);
</script> -->

<script>
    const finalCaseSummaryList = <?php echo json_encode($finalized_filtered_data); ?>;

    document.getElementById('final-apply-filter').addEventListener('click', () => {
        const selectedScreeningDate = document.getElementById('final-date-filter').value;
        const selectedDeliveryDate = document.getElementById('final-delivery-date-filter').value;

        function formatDateToInputString(dateStr) {
            const date = new Date(dateStr);
            if (isNaN(date)) return '';
            const year = date.getFullYear();
            const month = (`0${date.getMonth() + 1}`).slice(-2);
            const day = (`0${date.getDate()}`).slice(-2);
            return `${year}-${month}-${day}`;
        }

        const filteredData = finalCaseSummaryList.filter(item => {
            const screeningDate = item['create_time'] ? formatDateToInputString(item['create_time']) : '';
            let deliveryDate = '';

            if (item['date_livraison']) {
                const parts = item['date_livraison'].split('/');
                if (parts.length === 3) {
                    // Format: DD/MM/YYYY
                    deliveryDate = formatDateToInputString(`${parts[2]}-${parts[1]}-${parts[0]}`);
                } else {
                    // Try direct format
                    deliveryDate = formatDateToInputString(item['date_livraison']);
                }
            }

            const matchesScreeningDate = selectedScreeningDate
                ? (screeningDate === selectedScreeningDate)
                : true;

            const matchesDeliveryDate = selectedDeliveryDate
                ? (deliveryDate === selectedDeliveryDate)
                : true;

            return matchesScreeningDate && matchesDeliveryDate;
        });

        const uniqueLabnoCount = countUniqueLabnos(filteredData);
        renderFinalizedListTable(filteredData, uniqueLabnoCount);
    });

    function countUniqueLabnos(data) {
        const labnoSet = new Set(data.map(item => item.labno));
        return labnoSet.size;
    }

    function renderFinalizedListTable(data, uniqueLabnoCount) {
        const countDisplayContainer = document.getElementById('final-screening-done-count-display-container');
        const listDetailsContainer = document.getElementById('final-list-tab-content-container');
        const doctorCountContainer = document.getElementById('doctor-wise-count-container');

        listDetailsContainer.innerHTML = '';
        doctorCountContainer.innerHTML = '';
        countDisplayContainer.innerHTML = '';

        const doctorLabnoMap = {};
        data.forEach(item => {
            const doctor = item.user_name || 'Unknown';
            if (!doctorLabnoMap[doctor]) doctorLabnoMap[doctor] = new Set();
            doctorLabnoMap[doctor].add(item.labno);
        });

        let summaryText = `<br><p><strong>Total Finalization Done:</strong> ${uniqueLabnoCount}</p>`;
        summaryText += `<p><strong>Doctor-wise Count:</strong></p>`;
        Object.entries(doctorLabnoMap).forEach(([doctor, labnos]) => {
            summaryText += `<p>${doctor}: ${labnos.size}</p>`;
        });

        doctorCountContainer.innerHTML = summaryText;

        const listTable = document.createElement('table');
        listTable.classList.add('table');
        listTable.style.borderCollapse = 'collapse';
        listTable.style.width = '100%';

        const headerRow = document.createElement('tr');
        ['Doctor', 'Lab Number', 'Delivery Date', 'Status Name', 'Screening Date'].forEach(headerText => {
            const th = document.createElement('th');
            th.textContent = headerText;
            th.style.border = '1px solid black';
            th.style.padding = '8px';
            headerRow.appendChild(th);
        });
        listTable.appendChild(headerRow);

        data.forEach(item => {
            const row = document.createElement('tr');
            ['user_name', 'labno', 'date_livraison', 'status_name', 'create_time'].forEach(key => {
                const td = document.createElement('td');
                if (key === 'create_time' || key === 'date_livraison') {
                    const date = new Date(item[key]);
                    const options = { day: 'numeric', month: 'short', year: 'numeric' };
                    td.textContent = isNaN(date.getTime()) ? 'N/A' : date.toLocaleDateString('en-GB', options);
                } else {
                    td.textContent = item[key] || 'N/A';
                }
                td.style.border = '1px solid black';
                td.style.padding = '8px';
                row.appendChild(td);
            });
            listTable.appendChild(row);
        });

        listDetailsContainer.appendChild(listTable);
    }

    // Initial load
    const initialCount = countUniqueLabnos(finalCaseSummaryList);
    renderFinalizedListTable(finalCaseSummaryList, initialCount);
</script>




<!-- <script>
        // Sample Data
        const unSeenCaseSummaryList = <?php echo json_encode($unseen_filtered_data); ?>;

        // Initialize filter options
        function unSeenGenerateFilterOptions() {
            const statusNames = new Set();
            const labRoomStatuses = new Set();

            unSeenCaseSummaryList.forEach(item => {
                statusNames.add(item['status_name']);
                if (item['LabRoomStatus']) {
                    labRoomStatuses.add(item['LabRoomStatus']); // Adjust if 'LabRoomStatus' is present in your data
                }
            });

            populateSelectOptions('unseen-status-filter', Array.from(statusNames));
            populateSelectOptions('labroom-filter', Array.from(labRoomStatuses));
        }

        // Populate select options
        function populateSelectOptions(selectId, options) {
            const selectElement = document.getElementById(selectId);
            selectElement.innerHTML = ''; // Clear existing options
            options.forEach(option => {
                const opt = document.createElement('option');
                opt.value = option;
                opt.text = option;
                selectElement.add(opt);
            });
        }

        // Apply filters
        document.getElementById('unseen-apply-filter').addEventListener('click', () => {
            const selectedCreateDate = document.getElementById('unseen-date-filter').value;
            const selectedDeliveryDate = document.getElementById('unseen-delivery-date-filter').value;

            function formatDateToInputString(date) {
                const d = new Date(date);
                const year = d.getFullYear();
                const month = (`0${d.getMonth() + 1}`).slice(-2);
                const day = (`0${d.getDate()}`).slice(-2);
                return `${year}-${month}-${day}`;
            }

            const filteredData = unSeenCaseSummaryList.filter(item => {
                const createDate = item['create_time'] ? formatDateToInputString(item['create_time']) : '';
                const deliveryDate = item['date_livraison'] ? formatDateToInputString(item['date_livraison']) : '';

                const createDateMatch = selectedCreateDate ? (createDate === selectedCreateDate) : true;
                const deliveryDateMatch = selectedDeliveryDate ? (deliveryDate === selectedDeliveryDate) : true;

                return createDateMatch && deliveryDateMatch;
            });

            const unseenuniqueLabnoCount = countUniqueLabnos(filteredData);
            renderunSeenListTable(filteredData, unseenuniqueLabnoCount);
        });


        // Display the data in a table format
        function renderunSeenListTable(data, unseenuniqueLabnoCount) {
            const countDisplayContainer = document.getElementById('unseen-count-display-container');
            const listDetailsContainer = document.getElementById('unseen-list-tab-content-container');
            listDetailsContainer.innerHTML = ''; // Clear existing details

            const total_case_summary_list = countUniqueLabnos(unSeenCaseSummaryList);

            // Display the total count of unique lab numbers
            if (countDisplayContainer) {
                // Check if unseenuniqueLabnoCount is undefined or null
                if (unseenuniqueLabnoCount == null) {
                    // Ensure total_case_summary_list is defined and used properly
                    countDisplayContainer.innerHTML = `<p>Total UnSeen: ${total_case_summary_list || 0}</p>`;
                } else {
                    // Show the unique lab number count
                    countDisplayContainer.innerHTML = `<p>Total UnSeen: ${unseenuniqueLabnoCount}</p>`;
                }
            } else {
                console.error('Count display container not found.');
            }

            const listTable = document.createElement('table');
            listTable.classList.add('table');
            listTable.style.borderCollapse = 'collapse'; // Optional: To remove space between borders
            listTable.style.width = '100%'; // Optional: To make the table use full width

            // Create table header
            const headerRow = document.createElement('tr');
            ['User', 'Lab Number', 'Delivery Date', 'Status Name', 'Date'].forEach(headerText => {
                const th = document.createElement('th');
                th.textContent = headerText;
                th.style.border = '1px solid black'; // Add border to header cells
                th.style.padding = '8px'; // Optional: Add padding to cells
                headerRow.appendChild(th);
            });
            listTable.appendChild(headerRow);

            // Populate table rows
            data.forEach(item => {
                const row = document.createElement('tr');
                ['user_name', 'labno', 'date_livraison', 'status_name', 'create_time'].forEach(key => {
                    const td = document.createElement('td');
                    if (key === 'create_time' || key === 'date_livraison') {
                        const date = new Date(item[key]);
                        const options = { day: 'numeric', month: 'short', year: 'numeric' };
                        td.textContent = isNaN(date.getTime()) ? 'N/A' : date.toLocaleDateString('en-GB', options);
                    } else {
                        td.textContent = item[key] || 'N/A';
                    }
                    td.style.border = '1px solid black'; // Add border to data cells
                    td.style.padding = '8px'; // Optional: Add padding to cells
                    row.appendChild(td);
                });
                listTable.appendChild(row);
            });

            listDetailsContainer.appendChild(listTable);
        }

        // Initialize the page
        unSeenGenerateFilterOptions();
        renderunSeenListTable(unSeenCaseSummaryList);
</script> -->

<script>
    // Sample Data
    const unSeenCaseSummaryList = <?php echo json_encode($unseen_filtered_data); ?>;

    // Helper function: Count unique lab numbers
    function countUniqueLabnos(data) {
        const uniqueLabNos = new Set(data.map(item => item.labno));
        return uniqueLabNos.size;
    }

    // Initialize filter options
    function unSeenGenerateFilterOptions() {
        const statusNames = new Set();
        const labRoomStatuses = new Set();

        unSeenCaseSummaryList.forEach(item => {
            statusNames.add(item['status_name']);
            if (item['LabRoomStatus']) {
                labRoomStatuses.add(item['LabRoomStatus']);
            }
        });

        populateSelectOptions('unseen-status-filter', Array.from(statusNames));
        populateSelectOptions('labroom-filter', Array.from(labRoomStatuses));
    }

    // Populate select options
    function populateSelectOptions(selectId, options) {
        const selectElement = document.getElementById(selectId);
        selectElement.innerHTML = ''; // Clear existing options

        // Add a default empty option
        const defaultOpt = document.createElement('option');
        defaultOpt.value = '';
        defaultOpt.text = '-- Select --';
        selectElement.appendChild(defaultOpt);

        options.forEach(option => {
            const opt = document.createElement('option');
            opt.value = option;
            opt.text = option;
            selectElement.add(opt);
        });
    }

    // Apply filters
    document.getElementById('unseen-apply-filter').addEventListener('click', () => {
        const selectedCreateDate = document.getElementById('unseen-date-filter').value;
        const selectedDeliveryDate = document.getElementById('unseen-delivery-date-filter').value;

        function formatDateToInputString(date) {
            const d = new Date(date);
            const year = d.getFullYear();
            const month = (`0${d.getMonth() + 1}`).slice(-2);
            const day = (`0${d.getDate()}`).slice(-2);
            return `${year}-${month}-${day}`;
        }

        const filteredData = unSeenCaseSummaryList.filter(item => {
            const createDate = item['create_time'] ? formatDateToInputString(item['create_time']) : '';
            const deliveryDate = item['date_livraison'] ? formatDateToInputString(item['date_livraison']) : '';

            const createDateMatch = selectedCreateDate ? (createDate === selectedCreateDate) : true;
            const deliveryDateMatch = selectedDeliveryDate ? (deliveryDate === selectedDeliveryDate) : true;

            return createDateMatch && deliveryDateMatch;
        });

        const unseenuniqueLabnoCount = countUniqueLabnos(filteredData);
        renderunSeenListTable(filteredData, unseenuniqueLabnoCount);
    });

    // Display the data in a table format
    function renderunSeenListTable(data, unseenuniqueLabnoCount) {
        const countDisplayContainer = document.getElementById('unseen-count-display-container');
        const listDetailsContainer = document.getElementById('unseen-list-tab-content-container');
        listDetailsContainer.innerHTML = ''; // Clear existing details

        // Display the total count of unique lab numbers
        if (countDisplayContainer) {
            countDisplayContainer.innerHTML = `<p>Total UnSeen: ${unseenuniqueLabnoCount || 0}</p>`;
        } else {
            console.error('Count display container not found.');
        }

        const listTable = document.createElement('table');
        listTable.classList.add('table');
        listTable.style.borderCollapse = 'collapse';
        listTable.style.width = '100%';

        // Create table header
        const headerRow = document.createElement('tr');
        ['User', 'Lab Number', 'Delivery Date', 'Status Name', 'Date'].forEach(headerText => {
            const th = document.createElement('th');
            th.textContent = headerText;
            th.style.border = '1px solid black';
            th.style.padding = '8px';
            headerRow.appendChild(th);
        });
        listTable.appendChild(headerRow);

        // Populate table rows
        data.forEach(item => {
            const row = document.createElement('tr');
            ['user_name', 'labno', 'date_livraison', 'status_name', 'create_time'].forEach(key => {
                const td = document.createElement('td');
                if (key === 'create_time' || key === 'date_livraison') {
                    const date = new Date(item[key]);
                    const options = { day: 'numeric', month: 'short', year: 'numeric' };
                    td.textContent = isNaN(date.getTime()) ? 'N/A' : date.toLocaleDateString('en-GB', options);
                } else {
                    td.textContent = item[key] || 'N/A';
                }
                td.style.border = '1px solid black';
                td.style.padding = '8px';
                row.appendChild(td);
            });
            listTable.appendChild(row);
        });

        listDetailsContainer.appendChild(listTable);
    }

    // Initialize the page
    unSeenGenerateFilterOptions();

    // ✅ Render default table and count on page load
    const defaultUnseenUniqueLabnoCount = countUniqueLabnos(unSeenCaseSummaryList);
    renderunSeenListTable(unSeenCaseSummaryList, defaultUnseenUniqueLabnoCount);
</script>


<!-- Filter by Status Type (Dropdown) -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const statusFilter = document.getElementById("status-type-filter");
        const allSubTabs = document.querySelectorAll(".subtabcontent");

        function filterStatusType() {
            const selected = statusFilter.value;

            allSubTabs.forEach(tab => {
                if (selected === "" || tab.id === selected) {
                    tab.style.display = "block";
                } else {
                    tab.style.display = "none";
                }
            });
        }

        // Initial call
        filterStatusType();

        // Event listener for dropdown
        statusFilter.addEventListener("change", filterStatusType);
    });
</script>
