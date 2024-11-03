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

$batch_details = batch_details_list();

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <title>Search Functionality</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
</head>
<body>

<div class="container">
  <h3>Batch Wise LabNumber Information</h3>
  <ul class="nav nav-tabs">
    <li class="active"><a href="./index.php">Home</a></li>
    <li><a href="./details.php" class="tab">Details</a></li>
    <li><a href="./cassettes_number.php" class="tab">Cassettes Details</a></li>
    <li><a href="./cassettes_count.php" class="tab">Batch Cassettes Count</a></li>
  </ul>
  <br>

  <!-- Search Input -->
  <input type="text" id="searchInput" class="form-control" placeholder="Search by Lab Number">
  <br>

  <div class="content">
    <table class="table">
      <thead>
        <tr>
          <th scope="col">Batch</th>
          <th scope="col">Lab Number</th>
          <th scope="col">Gross Station</th>
        </tr>
      </thead>
      <tbody id="batchTable">
        <?php if (!empty($batch_details)) : ?>
          <?php foreach ($batch_details as $batch): ?>
            <tr>
              <td><?php echo htmlspecialchars($batch['batch_name']); ?></td>
              <td><?php echo htmlspecialchars($batch['lab_number']); ?></td>
              <td><?php echo htmlspecialchars($batch['gross_station']); ?></td>
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

<script>
  // JavaScript to filter table rows by Lab Number
  document.getElementById("searchInput").addEventListener("input", function() {
    const filter = this.value.toUpperCase();
    const rows = document.querySelectorAll("#batchTable tr");
    let matchesFound = false;

    rows.forEach(row => {
      const labNumberCell = row.cells[1]; // 2nd column is Lab Number
      if (labNumberCell) {
        const labNumberText = labNumberCell.textContent || labNumberCell.innerText;
        const labNumberMatches = labNumberText.toUpperCase().includes(filter) ||
                                 labNumberText.toUpperCase().includes("HPL" + filter);

        row.style.display = labNumberMatches ? "" : "none";

        if (labNumberMatches) {
          matchesFound = true;
        }
      }
    });

    // Show or hide the 'No results found' message based on matches
    document.getElementById("noResultsMessage").style.display = matchesFound ? "none" : "block";
  });
</script>

</body>
</html>