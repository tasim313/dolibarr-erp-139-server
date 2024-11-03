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

$cassettes_count = cassettes_count_list();

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <title>Bootstrap Example</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
</head>
<body>

<div class="container">
  <h3>Swap Batch</h3>
  <ul class="nav nav-tabs">
    <li class="active"><a href="./index.php">Home</a></li>
    <li><a href="./details.php" class="tab">Details</a></li>
    <li><a href="./cassettes_number.php" class="tab">Cassettes Details</a></li>
    <li><a href="./cassettes_count.php" class="tab">Batch Cassettes Count</a></li>
  </ul>
  <br>
  <div class="content">
    <table class="table">
      <thead>
        <tr>
          <th scope="col">Batch</th>
          <th scope="col">Total Cassettes</th>
          <th scope="col">Create Date</th>
          <th scope="col">Swap</th>
        </tr>
      </thead>
      <tbody id="batchTable">
        <?php if (!empty($cassettes_count)) : ?>
          <?php foreach ($cassettes_count as $cassettes):?>
            <tr>
              <td><?php echo htmlspecialchars($cassettes['name']); ?></td>
              <td><?php echo htmlspecialchars($cassettes['total_cassettes_count']); ?></td>
              <td><?php echo date('d F, Y', strtotime($cassettes['created_date'])); ?></td>
              <td><?php 
                        if (htmlspecialchars($cassettes['description']) === "Auto-incremented cassette count") {
                                // Show an empty cell if the condition is met
                                echo ""; // or you can use `echo '&nbsp;';` for a non-breaking space
                                echo '<i class="fas fa-exchange-alt swap-icon" title="Swap Icon" 
                                data-date="' . date('Y-m-d', strtotime($cassettes['created_date'])) . '" 
                                data-batch="' . htmlspecialchars($cassettes['name']) . '" 
                                data-count="' . htmlspecialchars($cassettes['total_cassettes_count']) . '"
                                data-rowid="' . htmlspecialchars($cassettes['rowid']) . '">
                                </i>';
                        } else {
                            // Display the description if the condition is not met
                            echo htmlspecialchars($cassettes['description']);
                            echo "&nbsp;&nbsp;&nbsp;"; 
                            echo '<i class="fas fa-exchange-alt swap-icon" title="Swap Icon" 
                                data-date="' . date('Y-m-d', strtotime($cassettes['created_date'])) . '" 
                                data-batch="' . htmlspecialchars($cassettes['name']) . '" 
                                data-count="' . htmlspecialchars($cassettes['total_cassettes_count']) . '"
                                data-rowid="' . htmlspecialchars($cassettes['rowid']) . '">
                                </i>';
                        } ?>
             </td>
            </tr>
          <?php endforeach; ?>
        <?php else : ?>
          <tr>
            <td colspan="3">No batch details found.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
    <!-- Message to display when no results are found -->
    <p id="noResultsMessage" style="display:none; color: red; text-align: center;">No results found</p>
  </div>
</div>


<div class="modal fade" id="swapModal" tabindex="-1" aria-labelledby="swapModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="swapModalLabel">Swap Batch</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>
                    Current Batch to Swap: 
                    <span id="batchToSwap" data-count=""></span>
                    (<span id="batchTotalCountDisplay">0</span> Cassettes)
                </p>
                <label for="swapWithBatch">Choose a Batch to Swap With:</label>
                <select id="swapWithBatch" class="form-control">
                    <option disabled selected>Select a batch</option>
                    <?php
                    // Generating options for swapWithBatch based on today’s date
                    $todayDate = date('Y-m-d');
                    if (!empty($cassettes_count)) {
                        $optionsGenerated = false;

                        foreach ($cassettes_count as $cassettes) {
                            $batchDate = date('Y-m-d', strtotime($cassettes['created_date']));
                            if ($batchDate === $todayDate) {
                                echo '<option value="' . htmlspecialchars($cassettes['name']) . '" data-count="' . htmlspecialchars($cassettes['total_cassettes_count']) . '" data-rowid="' . htmlspecialchars($cassettes['rowid']) . '">' . htmlspecialchars($cassettes['name']) . '</option>';
                                $optionsGenerated = true;
                            }
                        }

                        if (!$optionsGenerated) {
                            echo '<option disabled>No batches available for today</option>';
                        }
                    } else {
                        echo '<option disabled>No batches available for today</option>';
                    }
                    ?>
                </select>

                <div class="mt-3">
                    <label for="swapCount">Number of Cassettes to Swap:</label>
                    <p id="selectedBatchCount" class="text-muted">Total Cassettes in Selected Batch: <span id="batchTotalCount">0</span></p>
                    <input type="number" id="swapCount" class="form-control" min="1" placeholder="Enter number of cassettes">
                </div>

                <div class="mt-3">
                    <p>Total Cassettes after Swap:</p>
                    <p id="totalAfterSwap">0</p>
                </div>

                <!-- Hidden inputs to store the row IDs -->
                <input type="hidden" id="sourceBatchRowId" value="">
                <input type="hidden" id="targetBatchRowId" value="">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-warning" data-bs-dismiss="modal" onclick="event.preventDefault();">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmSwap">Confirm Swap</button>
            </div>
        </div>
    </div>
</div>





</body>
</html>


<!-- <script>
    document.addEventListener('DOMContentLoaded', () => {
        // Handle click on swap icon
        document.querySelectorAll('.swap-icon').forEach(icon => {
            icon.addEventListener('click', (event) => {
                const batchDate = icon.getAttribute('data-date');
                const batchName = icon.getAttribute('data-batch');
                const todayDate = new Date().toISOString().split('T')[0]; // Get today's date in 'YYYY-MM-DD' format

                if (batchDate !== todayDate) {
                    alert("Cannot swap with a previous date's batch.");
                } else {
                    // Set batch names in modal
                    document.getElementById('batchToSwap').textContent = batchName;

                    // Show the modal using Bootstrap 3
                    $('#swapModal').modal('show');
                }
            });
        });

        // Handle Confirm Swap button click
        document.getElementById('confirmSwap').addEventListener('click', () => {
            const selectedBatch = document.getElementById('batchToSwap').textContent;
            const swapWithBatch = document.getElementById('swapWithBatch').value;

            // AJAX request to save the swap action
            fetch('save_swap.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ selectedBatch, swapWithBatch })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert("Batch swap successful.");
                    location.reload(); // Reload the page or update table data as needed
                } else {
                    alert("Batch swap failed.");
                }
            })
            .catch(error => console.error("Error:", error));

            // Close the modal after confirming swap
            $('#swapModal').modal('hide');
        });
    });
</script> -->

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Handle click on swap icon
        document.querySelectorAll('.swap-icon').forEach(icon => {
            icon.addEventListener('click', (event) => {
                const batchDate = icon.getAttribute('data-date');
                const batchName = icon.getAttribute('data-batch');
                const todayDate = new Date().toISOString().split('T')[0]; // Get today's date in 'YYYY-MM-DD' format

                if (batchDate !== todayDate) {
                    alert("Cannot swap with a previous date's batch.");
                } else {
                    // Set batch names in modal
                    document.getElementById('batchToSwap').textContent = batchName;

                    // Show the modal using Bootstrap 3
                    $('#swapModal').modal('show');
                }
            });
        });

    });
</script> 

<script>
        document.querySelectorAll('.swap-icon').forEach(icon => {
            icon.addEventListener('click', () => {
                const batchDate = icon.getAttribute('data-date');
                const batchName = icon.getAttribute('data-batch');
                const totalCassettes = icon.getAttribute('data-count');
                const rowid = icon.getAttribute('data-rowid');
                const todayDate = new Date().toISOString().split('T')[0];

                if (batchDate !== todayDate) {
                    alert("Cannot swap with a previous date's batch.");
                } else {
                    document.getElementById('batchToSwap').textContent = batchName;
                    document.getElementById('batchToSwap').setAttribute('data-count', totalCassettes);
                    document.getElementById('batchTotalCountDisplay').innerText = totalCassettes; 
                    document.getElementById('sourceBatchRowId').value = rowid; // Store rowid
                    $('#swapModal').modal('show');
                }
            });
        });

        // Update count based on selected batch
        document.getElementById('swapWithBatch').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const totalCount = selectedOption.getAttribute('data-count');
            document.getElementById('batchTotalCount').innerText = totalCount; // Update count based on selected option
            document.getElementById('swapCount').setAttribute('max', totalCount); // Set max input value
        });

        // Calculate total cassettes after swap
        document.getElementById('swapCount').addEventListener('input', function() {
            const swapCount = parseInt(this.value) || 0;
            const currentBatchCount = parseInt(document.getElementById('batchToSwap').getAttribute('data-count')) || 0;
            const selectedOption = document.getElementById('swapWithBatch').selectedOptions[0];
            const targetBatchCount = parseInt(selectedOption ? selectedOption.getAttribute('data-count') : 0) || 0;

            // Calculate total cassettes after swap
            const newTotal = (currentBatchCount - swapCount >= 0) ? 
                (targetBatchCount + swapCount) : targetBatchCount; // Adjusted logic to add swapCount to target

            document.getElementById('totalAfterSwap').innerText = newTotal; // Update total after swap
        });

        document.getElementById('confirmSwap').addEventListener('click', () => {
                const swapCount = parseInt(document.getElementById('swapCount').value) || 0; // Ensure swapCount is a number
                const sourceBatch = document.getElementById('batchToSwap').innerText;
                const sourceRowId = document.getElementById('sourceBatchRowId').value; // Get source rowid
                const targetBatch = document.getElementById('swapWithBatch').value;
                const targetRowId = document.querySelector(`#swapWithBatch option[value="${targetBatch}"]`).getAttribute('data-rowid'); // Get target rowid

                // Fetch current total counts
                const currentSourceCount = parseInt(document.getElementById('batchToSwap').getAttribute('data-count')) || 0;
                const currentTargetCount = parseInt(document.getElementById('batchTotalCount').innerText) || 0;

                // Calculate new counts
                const newSourceCount = currentSourceCount - swapCount;
                const newTargetCount = currentTargetCount + swapCount;

                // Prepare the data structure for the request
                const requestData = {
                    source_batch_name: sourceBatch, // Key updated to match PHP expectations
                    source_rowid: sourceRowId, // Key updated to match PHP expectations
                    source_total_count: newSourceCount, // Key updated to match PHP expectations
                    target_batch_name: targetBatch, // Key updated to match PHP expectations
                    target_rowid: targetRowId, // Key updated to match PHP expectations
                    target_total_count: newTargetCount, // Key updated to match PHP expectations
                    swap_count: swapCount, // Key updated to match PHP expectations
                    source_description: `Transferred ${swapCount} cassettes to batch '${targetBatch}'`, // Source description
                    target_description: `Received ${swapCount} cassettes from batch '${sourceBatch}'` // Target description
                };

                // AJAX request to save the swap action
                fetch('batch/batch_swap.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(requestData)
                })
                .then(response => response.text()) // Get response as text first
                .then(text => {
                    console.log('Response Text:', text); // Log the raw response
                    const data = JSON.parse(text); // Now parse the JSON
                    if (data.success) {
                        alert("Batch swap successful.");
                        location.reload(); // Reload the page or update table data as needed
                    } else {
                        alert("Batch swap failed: " + data.message);
                    }
                })
                .catch(error => console.error("Error:", error));

                $('#swapModal').modal('hide');
        });

</script>