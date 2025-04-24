<?php

include('connection.php');
include('function.php');

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
$langs->loadLangs(array("deliverypoint@deliverypoint"));

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

llxHeader("", $langs->trans("DeliveryPointArea"));

print load_fiche_titre($langs->trans(""), '', '');

?>

<link href="bootstrap-3.4.1-dist/css/bootstrap.min.css" rel="stylesheet">


<style>
  body {
    background-color: #f7f9fc;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  }

  .search-container {
    
  }

  .invoice-label {
    font-weight: 600;
    font-size: 16px;
    margin-bottom: 10px;
    color: #333;
  }

  .input-group .form-control {
    height: 45px;
    border-radius: 8px 0 0 8px;
  }

  .input-group .input-group-btn .btn {
    border-radius: 0 8px 8px 0;
    height: 45px;
    background-color: #007bff;
    color: #fff;
    border: none;
  }

  .input-group .input-group-btn .btn:hover {
    background-color: #0056b3;
  }
</style>

<div>
	<div class="row justify-content-center">
		<div class="col-md-6">
		<div class="search-container">
			<label for="invoiceSearch" class="invoice-label">Insert Invoice Number</label>
			<div class="input-group">
			<input type="text" 
					id="invoiceSearch" 
					class="form-control" 
					placeholder="Search by invoice number...">
			<span class="input-group-btn">
				<button class="btn btn-primary" type="button" onclick="searchInvoice()">
				<i class="fa fa-search"></i>
				</button>
			</span>
			</div>
		</div>

		<!-- Result Tab Panel -->
		<div id="invoiceResultTab" class="result-tab" style="margin-top:20px;"></div>

		</div>
	</div>
</div>


<script>

    document.addEventListener('DOMContentLoaded', function () {
            const invoiceInput = document.getElementById("invoiceSearch");

            // Trigger searchInvoice() on Enter key
            invoiceInput.addEventListener("keypress", function (e) {
                if (e.key === "Enter") {
                    e.preventDefault();
                    searchInvoice();
                }
            });

            // Optional: focus the input field on page load for barcode scanning
            invoiceInput.focus();
    });

    function searchInvoice() {
        let input = document.getElementById("invoiceSearch").value.trim();

        // Prepend "SI" if not already there
        if (input && !input.toUpperCase().startsWith("SI")) {
            input = "SI" + input;
        }

        // Validate invoice length (7 to 30 characters)
        if (input.length < 7 || input.length > 30) {
            alert("Invoice number must be between 7 and 30 characters.");
            return;
        }

        if (input) {
            let searchValue = encodeURIComponent(input);
            window.location.href = `view/index.php?search=${searchValue}`;
        } else {
            alert("Please enter an invoice number to search.");
        }
    }

</script>

<?php
$NBMAX = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;
$max = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;


print '</div></div>';

// End of page
llxFooter();

$db->close();
