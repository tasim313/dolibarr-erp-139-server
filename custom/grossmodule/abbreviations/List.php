<?php

include("../connection.php");
include("../gross_common_function.php");
include("common_function_for_abbreviations.php");

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

$abbreviations = abbreviations_list();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 24px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td {
            padding: 5px 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f8f8f8;
            color: #333;
            font-weight: bold;
        }

        /* Adjust column widths */
        th:first-child, td:first-child {
            width: 15%; /* Reduced width for the Key column */
        }

        th:nth-child(2), td:nth-child(2) {
            width: 65%; /* More space for the Full Text column */
        }

        td input[type="text"] {
            width: 100%;
            padding: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .editor-container {
            height: 120px;
            border: 1px solid #ccc;
            padding: 10px;
            border-radius: 4px;
            background-color: #fff;
        }

        button[type="submit"] {
            background-color: #566ef5;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button[type="submit"]:hover {
            background-color: #45a049;
        }

        /* Pagination styling */
        .pagination {
            display: flex;
            justify-content: flex-start;
            margin: 10px 0;
        }

        .pagination button {
            background-color: #f2f2f2;
            border: 1px solid #ddd;
            color: #333;
            padding: 6px 10px;
            margin-right: 5px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .pagination button:hover {
            background-color: #ddd;
        }

        .pagination button.active {
            background-color: #4CAF50;
            color: white;
        }
    </style>
</head>
<body>
    <h1>Abbreviations List</h1>
    <table id="abbreviationsTable">
        <thead>
            <tr>
                <th>Key</th>
                <th>Full Text</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="tableBody">
            <?php foreach ($abbreviations as $index => $abbreviation): ?>
                <tr class="table-row">
                    <td>
                        <form method="post" action="update_abbreviation.php" id="form-<?php echo $index; ?>">
                            <input type="hidden" name="rowid" value="<?php echo htmlspecialchars($abbreviation['rowid']); ?>">
                            <input type="hidden" name="loggedInUserId" value="<?php echo htmlspecialchars($loggedInUserId); ?>">

                            <input style='border:none' type="text" name="abbreviation_key" value="<?php echo htmlspecialchars($abbreviation['abbreviation_key']); ?>" required>
                    </td>
                    <td>
                        <!-- Display the rich text editor for each row -->
                        <div id="editor-container-<?php echo $index; ?>" class="editor-container"></div>
                        <input type="hidden" id="abbreviation_full_text-<?php echo $index; ?>" name="abbreviation_full_text">
                    </td>
                    <td>
                        <button type="submit" name="update_abbreviation">Update</button>
                        </form>
                    </td>
                </tr>
                <script>
                    // Initialize Quill editor for each row
                    var quill<?php echo $index; ?> = new Quill('#editor-container-<?php echo $index; ?>', {
                        theme: 'snow',
                        modules: {
                            toolbar: [
                                ['bold', 'italic', 'underline'],
                                [{'list': 'ordered'}, {'list': 'bullet'}],
                                [{'align': []}],
                                ['link']
                            ]
                        }
                    });

                    // Populate the editor with the current abbreviation full text
                    quill<?php echo $index; ?>.root.innerHTML = '<?php echo addslashes(htmlspecialchars_decode($abbreviation['abbreviation_full_text'])); ?>';

                    // Update hidden field before form submission
                    document.getElementById('form-<?php echo $index; ?>').addEventListener('submit', function() {
                        document.getElementById('abbreviation_full_text-<?php echo $index; ?>').value = quill<?php echo $index; ?>.root.innerHTML;
                    });
                </script>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="pagination" id="paginationControls"></div>

    <script>
        var rowsPerPage = 20;
        var tableBody = document.getElementById("tableBody");
        var tableRows = tableBody.getElementsByClassName("table-row");
        var totalRows = tableRows.length;
        var paginationControls = document.getElementById("paginationControls");
        var currentPage = 1;

        function renderPagination() {
            paginationControls.innerHTML = '';
            var totalPages = Math.ceil(totalRows / rowsPerPage);
            for (let i = 1; i <= totalPages; i++) {
                let button = document.createElement("button");
                button.innerText = i;
                button.addEventListener("click", function () {
                    goToPage(i);
                });
                paginationControls.appendChild(button);
            }
        }

        function goToPage(page) {
            currentPage = page;
            var startRow = (currentPage - 1) * rowsPerPage;
            var endRow = startRow + rowsPerPage;

            for (let i = 0; i < totalRows; i++) {
                if (i >= startRow && i < endRow) {
                    tableRows[i].style.display = "";
                } else {
                    tableRows[i].style.display = "none";
                }
            }
        }

        // Initialize the table
        function initTable() {
            goToPage(1);
            renderPagination();
        }

        initTable();
    </script>
</body>
</html>