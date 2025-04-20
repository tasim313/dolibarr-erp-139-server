<?php 
include('../connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Extract POST data
    $paymentMethod = $_POST['paymentMethod'] ?? '';
    $transactionId = $_POST['transactionId'] ?? '';
    $referenceNumber = $_POST['referenceNumber'] ?? '';
    $dueAmount = $_POST['dueAmount'] ?? '';
    $rowid = $_POST['rowid'] ?? '';  // This is fk_facture
    $ref = $_POST['ref'] ?? '';
    $username = $_POST['username'] ?? '';
    $userID = $_POST['userID'] ?? '';

    // Initialize the note array
    $note = [];

    // Set fk_payment based on the payment method
    $fkPayment = 0;
    if ($paymentMethod === 'Bkash') {
        $fkPayment = 105;
        $note['transactionId'] = $transactionId;
        $note['referenceNumber'] = $referenceNumber;
    } else if ($paymentMethod === 'Cash') {
        $fkPayment = 4;
        $note = null;
    }

    // Fetch the latest fk_bank value
    $query = "SELECT fk_bank FROM llx_paiement ORDER BY rowid DESC LIMIT 1";
    $result = pg_query($pg_con, $query);
    $fkBankPlusOne = 1;
    if ($result) {
        $row = pg_fetch_assoc($result);
        $fkBank = $row['fk_bank'] ?? 0;
        $fkBankPlusOne = $fkBank + 1;
    }

    // Generate new ref
    $refQuery = "SELECT ref FROM llx_paiement ORDER BY rowid DESC LIMIT 1";
    $refResult = pg_query($pg_con, $refQuery);
    $newRef = 'PAY2504-1';
    if ($refResult) {
        $refRow = pg_fetch_assoc($refResult);
        $latestRef = $refRow['ref'] ?? '';
        if ($latestRef) {
            preg_match('/^(.*-)(\d+)$/', $latestRef, $matches);
            if ($matches) {
                $prefix = $matches[1];
                $number = (int) $matches[2];
                $newNumber = $number + 1;
                $newRef = $prefix . $newNumber;
            }
        }
    }

    // Prepare fixed values
    $fkExportCompta = 0;
    $statut = 0;
    $fkUserCreat = $userID;
    $currentDateTime = date('Y-m-d H:i:s');
    $amount = $dueAmount;
    $datec = $currentDateTime;
    $datep = $currentDateTime;
    $noteJson = $note ? json_encode($note) : null;

    // Insert into llx_paiement and return the rowid
    $insertQuery = "INSERT INTO llx_paiement 
        (ref, datec, datep, amount, multicurrency_amount, fk_paiement, note, fk_bank, fk_user_creat, statut, fk_export_compta)
        VALUES ('$newRef', '$datec', '$datep', '$amount', '$amount', '$fkPayment', '$noteJson', '$fkBankPlusOne', '$fkUserCreat', '$statut', '$fkExportCompta')
        RETURNING rowid";
    
    $insertResult = pg_query($pg_con, $insertQuery);

    if ($insertResult) {
        $insertedRow = pg_fetch_assoc($insertResult);
        $newPaymentRowId = $insertedRow['rowid'];

        // Now insert into llx_paiement_facture
        $factureInsertQuery = "INSERT INTO llx_paiement_facture (fk_paiement, fk_facture, amount, multicurrency_amount)
                               VALUES ('$newPaymentRowId', '$rowid', '$amount', '$amount')";
        $factureResult = pg_query($pg_con, $factureInsertQuery);

        if ($factureResult) {
            // Step 1: Get total_without_tax from llx_facture using $ref
            $factureQuery = "SELECT rowid, total_ht FROM llx_facture WHERE ref = '$ref'";
            $factureResult = pg_query($pg_con, $factureQuery);
            
            if ($factureResult && pg_num_rows($factureResult) > 0) {
               $factureRow = pg_fetch_assoc($factureResult);
               $factureRowId = $factureRow['rowid'];
               $totalHT = (float)$factureRow['total_ht'];

               // Step 2: Get total allocated_amount for this invoice
               $allocatedQuery = "SELECT SUM(amount) AS total_allocated FROM llx_paiement_facture WHERE fk_facture = $factureRowId";
               $allocatedResult = pg_query($pg_con, $allocatedQuery);

               if ($allocatedResult) {
                     $allocatedRow = pg_fetch_assoc($allocatedResult);
                     $totalAllocated = (float)$allocatedRow['total_allocated'];

                     // Step 3: If fully paid, update fk_statut = 2
                     if (abs($totalHT - $totalAllocated) < 0.01) { // floating point safe comparison
                        $updateStatutQuery = "UPDATE llx_facture SET fk_statut = 2 WHERE rowid = $factureRowId";
                        pg_query($pg_con, $updateStatutQuery);
                     }
               }
            }

            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        } else {
            echo "Error inserting into llx_paiement_facture: " . pg_last_error($pg_con);
        }
    } else {
        echo "Error inserting into llx_paiement: " . pg_last_error($pg_con);
    }
}

?>