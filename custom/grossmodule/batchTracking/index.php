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


$batch_list = batch_list();

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
    <h3>Batch Information For Tissue Processor</h3>
        <ul class="nav nav-tabs">
            <li class="active"><a href="./index.php">Home</a></li>
            <li><a href="./page1.php" class="tab">Tab 1</a></li>
            <li><a href="./page2.php" class="tab">Tab 2</a></li>
            <li><a href="./page3.php" class="tab">Tab 3</a></li>
        </ul>
    <br>
        <div class="content">
            <h1>Create New Batch </h1>
                <form action="./batch/create_batch.php" method="POST" class="row g-3 needs-validation" novalidate>
                        <!-- Batch Name -->
                        <label for="name"  class="form-label">Batch Name:</label>
                        <input type="text" id="name" name="name" class="form-control" >
                        <br>

                        <!-- Created User (Hidden) -->
                        <input type="hidden" name="created_user" value="<?php echo htmlspecialchars($loggedInUsername); ?>">

                        <!-- Submit Button -->
                        <button class="btn btn-primary" type="submit">Save</button>
                </form>
        </div>
</div>

<div class="container">
    <br>
    <table class="table">
            <thead>
                <tr>
                <th scope="col">Name</th>
                <th scope="col">Create User</th>
                <th scope="col">Create Date</th>
                <th scope="col">Create Time</th>
                <th scope="col">Update User</th>
                <th scope="col">Update Date</th>
                <th scope="col">Update Time</th>
                <th scope="col">Edit</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($batch_list as $batch): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($batch['name']); ?></td>
                        <td><?php echo htmlspecialchars($batch['created_user']); ?></td>
                        <td><?php echo date('d F, Y', strtotime($batch['created_date'])); ?></td>
                        <td><?php echo date('h:i A', strtotime($batch['created_time'])); ?></td>
                        <td><?php echo htmlspecialchars($batch['updated_user']); ?></td>
                        <td><?php echo date('d F, Y', strtotime($batch['updated_time'])); ?></td>
                        <td><?php echo date('h:i A', strtotime($batch['updated_time'])); ?></td>
                        <td>
                             <!-- Edit button with data attributes to pass values -->
                            <button class="btn btn-success btn-sm rounded-0 edit-btn"
                                data-rowid="<?php echo $batch['rowid']; ?>"
                                data-name="<?php echo htmlspecialchars($batch['name']); ?>"
                                data-toggle="modal" data-target="#editModal">
                                <i class="fa fa-edit"></i>
                            </button>
                        </td>
                <?php endforeach; ?>
            </tbody>
    </table>
</div>


<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editForm" method="post" action="./batch/edit_batch.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit Batch</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Hidden fields for rowid and updated_user -->
                    <input type="hidden" id="editRowid" name="rowid">
                    <input type="hidden" id="editUpdatedUser" name="updated_user" value="<?php echo htmlspecialchars($loggedInUsername); ?>">
                    
                    <div class="form-group">
                        <label for="editName">Name</label>
                        <input type="text" class="form-control" id="editName" name="name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>


<script>
    document.addEventListener('DOMContentLoaded', function() {
        const editButtons = document.querySelectorAll('.edit-btn');

        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                const rowid = button.getAttribute('data-rowid');
                const name = button.getAttribute('data-name');
                
                document.getElementById('editRowid').value = rowid;
                document.getElementById('editName').value = name;
            });
        });
    });
</script>