<?php

include('../connection.php');
include('../function.php');

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

$loggedInUsername = $user->login;
$loggedInUserId = $user->id;

$invoice_number = $_GET['search'];
$invoice_value = get_invoice_list_delivery_point($invoice_number);

$payment_list = get_payment_list_using_invoice_number_delivery_point($invoice_number);

$patient_info =  get_patient_information_invoice($invoice_number);


?>

<!-- Bootstrap CSS -->
<link href="../bootstrap-3.4.1-dist/css/bootstrap.min.css" rel="stylesheet">



<!-- Bootstrap JS -->
<script src="../bootstrap-3.4.1-dist/js/bootstrap.min.js"></script>



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

  .popup {
        display: none;
        position: fixed;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1000;
  }

  .popup-content {
      background-color: white;
      margin: 15% auto;
      padding: 20px;
      width: 300px;
      border-radius: 5px;
  }

  .popup-close {
      color: #aaa;
      font-size: 28px;
      font-weight: bold;
      position: absolute;
      top: 5px;
      right: 10px;
  }

  .popup-close:hover,
  .popup-close:focus {
      color: black;
      text-decoration: none;
      cursor: pointer;
  }

  .popup-body p {
      margin-bottom: 10px;
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
            window.location.href = `index.php?search=${searchValue}`;
        } else {
            alert("Please enter an invoice number to search.");
        }
    }

</script>

<script>

    function initializePopupLogic() {
        const cashRadio = document.getElementById('paymentCash');
        const bkashRadio = document.getElementById('paymentBkash');
        const nagadRadio = document.getElementById('paymentNagad');
        const bkashFields = document.getElementById('bkashFields');
        const transactionInput = document.getElementById('transactionId');
        const unpaidForm = document.getElementById('unpaidForm');

        if (cashRadio && bkashRadio && bkashFields && transactionInput) {
            function toggleBkashFields() {
                if (bkashRadio.checked) {
                    bkashFields.style.display = 'block';
                    transactionInput.setAttribute('required', 'required');
                } else {
                    bkashFields.style.display = 'none';
                    transactionInput.removeAttribute('required');
                }
            }

            function togglePaymentFields() {
                if (bkashRadio?.checked || nagadRadio?.checked) {
                    bkashFields.style.display = 'block';
                    transactionInput.setAttribute('required', 'required');
                } else {
                    bkashFields.style.display = 'none';
                    transactionInput.removeAttribute('required');
                }
            }

            const radios = document.querySelectorAll('input[name="paymentMethod"]');
            radios.forEach(radio => {
                radio.addEventListener('change', togglePaymentFields);
            });

            cashRadio.addEventListener('change', toggleBkashFields);
            bkashRadio.addEventListener('change', toggleBkashFields);
            toggleBkashFields(); // Initialize on load
            togglePaymentFields(); // On load
        }
        
        document.getElementById('unpaidsubmitbtn').addEventListener('click', function(e) {
            e.preventDefault();

            // Check form validity first
            const unpaidForm = document.getElementById('unpaidForm');
            if (!unpaidForm.checkValidity()) {
                unpaidForm.reportValidity(); // Show validation UI
                return; // Stop here, don't show the pop-up
            }

            // Get form data
            const method = $('input[name="paymentMethod"]:checked').val();
            const dueAmount = $('#dueAmount').val();
            const transactionId = $('#transactionId').val();
            const referenceNumber = $('#referenceNumber').val();

            let previewHTML = `
                <p><strong>Payment Method:</strong> ${method}</p>
                <p><strong>Due Amount:</strong> ৳${parseFloat(dueAmount).toFixed(2)}</p>
            `;

            if (method === 'Bkash') {
                previewHTML += `
                    <p><strong>Transaction ID:</strong> ${transactionId}</p>
                    <p><strong>Reference Number:</strong> ${referenceNumber}</p>
                `;
            }

            // Show the details in the pop-up
            document.getElementById('popupBody').innerHTML = previewHTML;
            document.getElementById('popupMessage').style.display = 'block';

            // Show the "Back" button
            document.getElementById('popupBackBtn').style.display = 'inline-block';

            // When the checkbox is checked, enable the submit button
            document.getElementById('confirmCheckbox').addEventListener('change', function() {
                const submitBtn = document.getElementById('popupSubmitBtn');
                if (this.checked) {
                    submitBtn.style.display = 'block';  // Show the submit button
                } else {
                    submitBtn.style.display = 'none';   // Hide the submit button if unchecked
                }
            });

            // Handle Back button click
            document.getElementById('popupBackBtn').onclick = function () {
                document.getElementById('popupMessage').style.display = 'none'; // Close the pop-up
            };
        });

        // Close the pop-up
        document.getElementById('popupClose').onclick = function () {
            document.getElementById('popupMessage').style.display = 'none'; // Close the pop-up
        };

        // Submit the form when the user confirms the details
        document.getElementById('popupSubmitBtn').onclick = function () {
            document.getElementById('popupMessage').style.display = 'none'; // Close the pop-up
            setTimeout(() => {
                document.getElementById('unpaidForm').submit();  // Submit the form after a brief delay
            }, 500);
        };
    
    }

</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const DOL_URL_ROOT = '<?php echo DOL_URL_ROOT; ?>';
        var phpInvoiceValue = <?php echo json_encode($invoice_value); ?>;
        const paymentData = <?php echo json_encode($payment_list, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
        const patientData = <?php echo json_encode($patient_info, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
        var resultTab = document.getElementById('invoiceResultTab');
        const loggedInUsername = '<?php echo $loggedInUsername; ?>';
        const loggedInUserId = <?php echo $loggedInUserId; ?>;
        const token = '<?php echo newToken(); ?>';

        let specimenList = '';
        patientData.forEach(item => {
            specimenList += `<li>${item.specimen}</li>`;
        });

        // Use the first patient entry for other values
        const firstEntry = patientData[0] || {};
    
    
        let globalLastEntry = null;

        window.globalLastEntry = null;
    
        if (paymentData && paymentData.length > 0) {
            window.globalLastEntry = paymentData[paymentData.length - 1];
        }

        if (resultTab) {
            if (phpInvoiceValue && Array.isArray(phpInvoiceValue) && phpInvoiceValue.length > 0 && phpInvoiceValue[0].ref) {
                resultTab.innerHTML = `
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            <h3 class="panel-title">Invoice Matched</h3>
                        </div>
                        <div class="panel-body">
                            <p><strong>Invoice Reference:</strong> <span class="text-success" style="font-size:16px;">${phpInvoiceValue[0].ref}</span></p>
                            <p><strong>What would you like to do?</strong></p>
                            <div class="btn-group">
                                <button id="deliverReportBtn" class="btn btn-success" style="margin-right: 20px;">
                                    <span class="glyphicon glyphicon-download-alt"></span> Deliver Report
                                </button>
                                <button id="collectHistoryBtn" class="btn btn-info">
                                    <span class="glyphicon glyphicon-list-alt"></span> Collect Patient History
                                </button>
                            </div>
                            <div id="reportTypeOptions" style="margin-top: 20px; display: none;"></div>
                            <div id="historyOptions" style="margin-top: 20px; display: none;"></div>
                            <div id="preliminaryReportMessage" style="margin-top: 20px; display: none;" ></div>
                            <div id="finalReportMessage" style="margin-top: 20px; display: none;" ></div>
                            <div id="NoteMessage" style="margin-top: 20px; display: none;" ></div>
                        </div>
                    </div>
                `;

                const deliverReportBtn = document.getElementById('deliverReportBtn');
                const collectHistoryBtn = document.getElementById('collectHistoryBtn');
                const reportOptions = document.getElementById('reportTypeOptions');
                const historyOptions = document.getElementById('historyOptions');
                const preliminaryReportMessage = document.getElementById('preliminaryReportMessage');
                const finalReportMessage = document.getElementById('finalReportMessage');
                const noteMessage = document.getElementById('NoteMessage');

                deliverReportBtn.addEventListener('click', function () {
                    reportOptions.style.display = 'block';
                    historyOptions.style.display = 'none';
                    preliminaryReportMessage.style.display = 'none';
                    finalReportMessage.style.display = 'none';
                    noteMessage.style.display = 'none';

                    reportOptions.innerHTML = `
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4 class="panel-title">Choose Report Type</h4>
                            </div>
                            <div class="panel-body">
                                <button class="btn btn-warning" style="margin-right: 10px;" id="preliminaryBtn">
                                    <span class="glyphicon glyphicon-file"></span> Preliminary Report
                                </button>
                                <button class="btn btn-primary" style="margin-right: 220px;" id="finalBtn">
                                    <span class="glyphicon glyphicon-ok"></span> Final Report
                                </button>
                                <button class="btn btn-primary" id="noteBtn">
                                    <span class="glyphicon glyphicon-pencil"></span> Note
                                </button>
                            </div>
                        </div>
                    `;

                    // Bind new buttons after render
                    document.getElementById('preliminaryBtn').addEventListener('click', function () {
                              finalReportMessage.style.display = 'none';
                              noteMessage.style.display = 'none';

                              let totalPayment = 0;
                              let rowsHtml = '';

                                if (!paymentData || paymentData.length === 0) {
                                  preliminaryReportMessage.innerHTML = `<p>No payment information available.</p>`;
                                  preliminaryReportMessage.style.display = 'block';
                                  return;
                                }

                                paymentData.forEach(payment => {
                                    totalPayment += parseFloat(payment.payment_amount) || 0;
                                    rowsHtml += `
                                        <tr>
                                            <td class="text-center">${payment.payment_ref ?? ''}</td>
                                            <td class="text-center">${payment.payment_date ? new Date(payment.payment_date).toLocaleString() : ''}</td>
                                            <td class="text-center">৳${payment.payment_amount ? parseFloat(payment.payment_amount).toFixed(2) : ''}</td>
                                        </tr>
                                    `;
                                });


                              const lastEntry = paymentData[paymentData.length - 1];
                              
                              let totalAmount = parseFloat(lastEntry.total_without_tax) || 0;
                              let sanitizedTotalPayment = parseFloat(totalPayment);

                              // Fallback to 0 if totalPayment is NaN, null, undefined, or empty
                              if (isNaN(sanitizedTotalPayment)) {
                                  sanitizedTotalPayment = 0;
                              }

                              let currencySymbol = '৳';

                              let dueOrExcess = (sanitizedTotalPayment - totalAmount).toFixed(2);
                              let paymentMessage = '';

                              if (dueOrExcess > 0) {
                                  paymentMessage = `<div class="alert alert-danger"><strong>We will provide</strong> ${currencySymbol}${Math.abs(dueOrExcess)} to the patient (overpaid).</div>`;
                              } else if (dueOrExcess < 0) {
                                  paymentMessage = `<div class="alert alert-danger"><strong>We will get</strong> ${currencySymbol}${Math.abs(dueOrExcess)} from the patient (due amount).</div>`;
                              } else {
                                  paymentMessage = `<div class="alert alert-danger"><strong>Payment Complete</strong></div>`;
                              }

                              let privateNoteHtml = '';
                              let publicNoteHtml = '';

                              if (lastEntry.private_note && lastEntry.private_note.trim() !== '') {
                                  privateNoteHtml = `<p><strong>Private Note:</strong> ${lastEntry.private_note}</p>`;
                              }

                              if (lastEntry.public_note && lastEntry.public_note.trim() !== '') {
                                  publicNoteHtml = `<p><strong>Public Note:</strong> ${lastEntry.public_note}</p>`;
                              }

                              let dueColor = '';
                              let statusLower = lastEntry.status_text?.toLowerCase();

                              if (statusLower === 'unpaid') {
                                  dueColor = 'color: red;';
                              } else if (statusLower === 'paid') {
                                  dueColor = 'color: green;';
                              } else {
                                  dueColor = 'color: orange;';
                              }

                              let formHtml = '';

                             
                              if (statusLower === 'unpaid') {
                                  formHtml = `
                                        <div class="mb-3">
                                            <label class="form-label">Payment Options</label>
                                            <div>
                                                <a href="${DOL_URL_ROOT}/compta/paiement.php?facid=${lastEntry.invoice_rowid}&action=create&accountid=" 
                                                class="btn btn-primary">
                                                Enter Payment
                                                </a>
                                            </div>
                                        </div>

                                  `;
                              } else if (statusLower === 'paid') {
                                formHtml = `
                                            <!-- Button trigger modal -->
                                            <button id="openDeliveryModalBtn" type="button" class="btn btn-success" data-toggle="modal" data-target="#deliveryConfirmationModal">
                                                Delivered
                                            </button>

                                            <!-- Bootstrap Modal -->
                                            <div class="modal fade" id="deliveryConfirmationModal" tabindex="-1" role="dialog" aria-labelledby="deliveryModalLabel">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                            <h4 class="modal-title" id="deliveryModalLabel">Delivery Confirmation</h4>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p><strong>Invoice:</strong> <span id="invoiceNumberDisplay">${firstEntry.invoice_number || 'N/A'}</span></p>
                                                            <p><strong>Lab No:</strong> <span id="labNumberDisplay"><strong>${firstEntry.lab_number || 'N/A'}</strong></span></p>
                                                            <p><strong>Patient Name:</strong> <span id="patientNameDisplay">${firstEntry.customer_name || 'N/A'}</span></p>
                                                            <p><strong>Mobile Number:</strong> <span id="patientPhoneDisplay">${firstEntry.customer_phone || 'N/A'}</span></p>
                                                            <p><strong>Address:</strong> <span id="patientAddressDisplay">${firstEntry.customer_address || 'N/A'}</span></p>
                                                            <p><strong>Specimen Name:</strong> <span id="specimenDisplay">
                                                                    <ul style="margin: 5px 0 0 15px;">
                                                                      ${specimenList}
                                                                    </ul></span></p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                                            <form id="deliveredForm" method="POST" action="mark_delivered.php" style="display: inline;">
                                                                <input type="hidden" name="lab_number" value="${firstEntry.lab_number}">
                                                                <input type="hidden" name="report_type" value="preliminary report">
                                                                <input type="hidden" name="user_id" value="${loggedInUserId}">
                                                                <input type="hidden" name="username" value="${loggedInUsername}">
                                                                <button type="submit" id="confirmDeliverySubmit" class="btn btn-primary">
                                                                    Submit Delivery
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        `;
                                 
                              }

                              preliminaryReportMessage.innerHTML = `
                                      <p><strong>Preliminary Report</strong></p>

                                        <!-- Invoice Table -->
                                        <table class="table table-bordered mb-4">
                                            <thead>
                                                <tr>
                                                    <th class="text-center">Ref</th>
                                                    <th class="text-center">Date</th>
                                                    <th class="text-center">Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td class="text-center"><strong>${lastEntry.invoice_ref}</strong></td>
                                                    <td class="text-center"><strong>${lastEntry.invoice_date_created}</strong></td>
                                                    <td class="text-center"><strong>৳${parseFloat(lastEntry.total_without_tax).toFixed(2)}</strong></td>
                                                     ${rowsHtml}
                                                     <td></td>
                                                     <td class="text-center">Total</td>
                                                     <td class="text-center">৳${parseFloat(lastEntry.total_without_tax).toFixed(2)}</td>
                                                    
                                                </tr>
                                                    <td></td>
                                                     <td class="text-center">Paid</td>
                                                     <td class="text-center">৳${totalPayment.toFixed(2)}</td>
                                                     <tr>
                                                        <td class="text-center" style="font-size:30px;"><strong>${lastEntry.status_text}</strong></td>
                                                        <td class="text-center">Due</td>
                                                        <td class="text-center" style="${dueColor}">৳${Math.abs(dueOrExcess).toFixed(2)}</td>
                                                     </tr>
                                            </tbody>
                                        </table>
                                        <br><br>
                                        ${privateNoteHtml}
                                        ${publicNoteHtml}
                                     
                                        ${formHtml}
                              `;


                              preliminaryReportMessage.style.display = 'block';
                              // Then immediately initialize modal logic
                              initializePopupLogic();
                             
                              

                    });

                    document.getElementById('finalBtn').addEventListener('click', function () {
                        preliminaryReportMessage.style.display = 'none';
                        noteMessage.style.display = 'none';
                        let totalPayment = 0;
                        let rowsHtml = '';

                        if (!paymentData || paymentData.length === 0) {
                          preliminaryReportMessage.innerHTML = `<p>No payment information available.</p>`;
                          preliminaryReportMessage.style.display = 'block';
                          return;
                        }

                        paymentData.forEach(payment => {
                            totalPayment += parseFloat(payment.payment_amount) || 0;
                            rowsHtml += `
                                <tr>
                                    <td class="text-center">${payment.payment_ref ?? ''}</td>
                                    <td class="text-center">${payment.payment_date ? new Date(payment.payment_date).toLocaleString() : ''}</td>
                                    <td class="text-center">৳${payment.payment_amount ? parseFloat(payment.payment_amount).toFixed(2) : ''}</td>
                                </tr>
                            `;
                        });


                        const lastEntry = paymentData[paymentData.length - 1];
                              
                        let totalAmount = parseFloat(lastEntry.total_without_tax) || 0;
                        let sanitizedTotalPayment = parseFloat(totalPayment);

                        // Fallback to 0 if totalPayment is NaN, null, undefined, or empty
                        if (isNaN(sanitizedTotalPayment)) {
                            sanitizedTotalPayment = 0;
                        }

                        let currencySymbol = '৳';

                        let dueOrExcess = (sanitizedTotalPayment - totalAmount).toFixed(2);
                        let paymentMessage = '';
                        if (dueOrExcess > 0) {
                            paymentMessage = `<div class="alert alert-danger"><strong>We will provide</strong> ${currencySymbol}${Math.abs(dueOrExcess)} to the patient (overpaid).</div>`;
                        } else if (dueOrExcess < 0) {
                            paymentMessage = `<div class="alert alert-danger"><strong>We will get</strong> ${currencySymbol}${Math.abs(dueOrExcess)} from the patient (due amount).</div>`;
                        } else {
                            paymentMessage = `<div class="alert alert-danger"><strong>Payment Complete</strong></div>`;
                        }

                        let privateNoteHtml = '';
                        let publicNoteHtml = '';

                        if (lastEntry.private_note && lastEntry.private_note.trim() !== '') {
                            privateNoteHtml = `<p><strong>Private Note:</strong> ${lastEntry.private_note}</p>`;
                        }

                        if (lastEntry.public_note && lastEntry.public_note.trim() !== '') {
                            publicNoteHtml = `<p><strong>Public Note:</strong> ${lastEntry.public_note}</p>`;
                        }

                        let dueColor = '';
                        let statusLower = lastEntry.status_text?.toLowerCase();

                        if (statusLower === 'unpaid') {
                            dueColor = 'color: red;';
                        } else if (statusLower === 'paid') {
                            dueColor = 'color: green;';
                        } else {
                            dueColor = 'color: orange;';
                        }

                        let formHtml = '';

                             
                        if (statusLower === 'unpaid') {
                            formHtml = `
                                <div class="mb-3">
                                    <label class="form-label">Payment Options</label>
                                        <div>
                                            <a href="${DOL_URL_ROOT}/compta/paiement.php?facid=${lastEntry.invoice_rowid}&action=create&accountid=" 
                                                class="btn btn-primary">
                                                Enter Payment
                                            </a>
                                        </div>
                                </div>

                                  `;
                        } else if (statusLower === 'paid') {
                                formHtml = `
                                    <!-- Button trigger modal -->
                                    <button id="openDeliveryModalBtn" type="button" class="btn btn-success" data-toggle="modal" data-target="#deliveryConfirmationModal">
                                        Delivered
                                    </button>

                                    <!-- Bootstrap Modal -->
                                    <div class="modal fade" id="deliveryConfirmationModal" tabindex="-1" role="dialog" aria-labelledby="deliveryModalLabel">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                    <h4 class="modal-title" id="deliveryModalLabel">Delivery Confirmation</h4>
                                                </div>
                                                <div class="modal-body">
                                                    <p><strong>Invoice:</strong> <span id="invoiceNumberDisplay">${firstEntry.invoice_number || 'N/A'}</span></p>
                                                    <p><strong>Lab No:</strong> <span id="labNumberDisplay"><strong>${firstEntry.lab_number || 'N/A'}</strong></span></p>
                                                    <p><strong>Patient Name:</strong> <span id="patientNameDisplay">${firstEntry.customer_name || 'N/A'}</span></p>
                                                    <p><strong>Mobile Number:</strong> <span id="patientPhoneDisplay">${firstEntry.customer_phone || 'N/A'}</span></p>
                                                    <p><strong>Address:</strong> <span id="patientAddressDisplay">${firstEntry.customer_address || 'N/A'}</span></p>
                                                    <p><strong>Specimen Name:</strong> <span id="specimenDisplay">
                                                            <ul style="margin: 5px 0 0 15px;">
                                                              ${specimenList}
                                                            </ul></span></p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                                    <form id="deliveredForm" method="POST" action="mark_delivered.php" style="display: inline;">
                                                        <input type="hidden" name="lab_number" value="${firstEntry.lab_number}">
                                                        <input type="hidden" name="report_type" value="final report">
                                                        <input type="hidden" name="user_id" value="${loggedInUserId}">
                                                        <input type="hidden" name="username" value="${loggedInUsername}">
                                                        <button type="submit" id="confirmDeliverySubmit" class="btn btn-primary">
                                                            Submit Delivery
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                `;
                                 
                              }
                        finalReportMessage.innerHTML = `
                                        <p><strong>Final Report</strong></p>

                                         <!-- Invoice Table -->
                                        <table class="table table-bordered mb-4">
                                            <thead>
                                                <tr>
                                                    <th class="text-center">Ref</th>
                                                    <th class="text-center">Date</th>
                                                    <th class="text-center">Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td class="text-center"><strong>${lastEntry.invoice_ref}</strong></td>
                                                    <td class="text-center"><strong>${lastEntry.invoice_date_created}</strong></td>
                                                    <td class="text-center"><strong>৳${parseFloat(lastEntry.total_without_tax).toFixed(2)}</strong></td>
                                                     ${rowsHtml}
                                                     <td></td>
                                                     <td class="text-center">Total</td>
                                                     <td class="text-center">৳${parseFloat(lastEntry.total_without_tax).toFixed(2)}</td>
                                                    
                                                </tr>
                                                    <td></td>
                                                     <td class="text-center">Paid</td>
                                                     <td class="text-center">৳${totalPayment.toFixed(2)}</td>
                                                     <tr>
                                                        <td class="text-center" style="font-size:30px;"><strong>${lastEntry.status_text}</strong></td>
                                                        <td class="text-center">Due</td>
                                                        <td class="text-center" style="${dueColor}">৳${Math.abs(dueOrExcess).toFixed(2)}</td>
                                                     </tr>
                                            </tbody>
                                        </table>
                                        <br><br>


                                        ${privateNoteHtml}
                                        ${publicNoteHtml}
                                        ${formHtml}
                        `;
                        finalReportMessage.style.display = 'block';
                        initializePopupLogic();
                    });

                    document.getElementById('noteBtn').addEventListener('click', function () {
                          preliminaryReportMessage.style.display = 'none';
                          finalReportMessage.style.display = 'none';

                          const existingNote = window.globalLastEntry?.public_note ?? '';
                          const rowid = window.globalLastEntry?.invoice_rowid
                          
                          noteMessage.innerHTML = `
                              <form id="noteForm" action="save_note.php" method="POST">
                                  <div class="form-group">
                                      <label for="note_public"><strong>Note</strong></label>
                                      <textarea class="form-control" id="note_public" name="note_public" rows="4" placeholder="Enter your note here...">${existingNote}</textarea>
                                  </div>
                                  <input type="hidden" name="rowid" value="${rowid}">
                                  <button type="submit" class="btn btn-primary">
                                      <span class="glyphicon glyphicon-send"></span> Submit Note
                                  </button>
                              </form>
                              <div id="noteSuccessMessage" class="alert alert-success mt-3" style="display: none;"></div>
                          `;

                          noteMessage.style.display = 'block';

                          document.getElementById('noteForm').addEventListener('submit', function (e) {
                              e.preventDefault();
                              const noteValue = document.getElementById('note_public').value;
                              if (noteValue.trim() !== '') {
                                  fetch('save_note.php', {
                                      method: 'POST',
                                      headers: {
                                          'Content-Type': 'application/x-www-form-urlencoded'
                                      },
                                      body: 'note_public=' + encodeURIComponent(noteValue) + '&rowid=' + encodeURIComponent(rowid)
                                  })
                                      .then(response => response.text())
                                      .then(data => {
                                        location.reload();
                                      })
                                      .catch(error => {
                                          console.error('Error submitting note:', error);
                                      });
                              }
                          });
                    });



                  // here it is closed delivery report part code.
                });

                collectHistoryBtn.addEventListener('click', function () {
                    reportOptions.style.display = 'none';
                    preliminaryReportMessage.style.display = 'none';
                    finalReportMessage.style.display = 'none';
                    historyOptions.style.display = 'block';

                    historyOptions.innerHTML = `
                        <div class="panel panel-info">
                            <div class="panel-heading">
                                <h4 class="panel-title">Patient History</h4>
                            </div>
                            <div class="panel-body">
                                <p>Redirecting to patient history section...</p>
                                <button class="btn btn-info" onclick="window.open('patient_history.php?invoice=${phpInvoiceValue[0].ref}', '_blank')">
                                    <span class="glyphicon glyphicon-eye-open"></span> View History
                                </button>
                            </div>
                        </div>
                    `;
                });

            } else {
                resultTab.innerHTML = "<em>No invoice information found.</em>";
            }
        } else {
            console.error("Element with ID 'invoiceResultTab' not found.");
        }
    });
</script>




<?php
$NBMAX = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;
$max = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;


print '</div></div>';

// End of page
llxFooter();
$db->close();
