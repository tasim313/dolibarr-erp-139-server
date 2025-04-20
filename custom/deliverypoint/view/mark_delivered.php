<?php
include('../connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reportType = isset($_POST['report_type']) ? strtolower(trim($_POST['report_type'])) : '';
    $labNumber = isset($_POST['lab_number']) ? trim($_POST['lab_number']) : '';
    $user_id = isset($_POST['user_id']) ? trim($_POST['user_id']) : '';
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';

    // Check if required fields are present
    if (empty($reportType) || empty($labNumber)) {
        die(json_encode(['status' => 'error', 'message' => 'Required fields are missing']));
    }

    // For preliminary reports, check if report exists first
    if ($reportType === 'preliminary report') {
        // Check if preliminary report exists (fk_status_id = 70)
        $query = "SELECT * FROM llx_commande_trackws WHERE labno = $1 AND fk_status_id = 70";
        $result = pg_query_params($pg_con, $query, array($labNumber));
    
        if (!$result) {
            die("Error in SQL query: " . pg_last_error($pg_con));
        }
    
        if (pg_num_rows($result) === 0) {
            echo '
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>Report Not Ready</title>
                        <link rel="stylesheet" href="../bootstrap/bootstrap.min.css">
                        <script src="../bootstrap/jquery-3.5.1.slim.min.js"></script>
                        <script src="../bootstrap/bootstrap.bundle.min.js"></script>
                    </head>
                    <body>

                    <!-- Report Not Ready Modal -->
                    <div class="modal fade" id="notReadyModal" tabindex="-1" role="dialog" aria-labelledby="notReadyModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                        <div class="modal-content">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title" id="notReadyModalLabel">Report Not Ready</h5>
                        </div>
                        <div class="modal-body">
                            <p><strong>Lab Number:</strong> ' . htmlspecialchars($labNumber) . '</p>
                            <p>The preliminary report is not ready yet.</p>
                        </div>
                        <div class="modal-footer">
                            <button onclick="window.history.back();" class="btn btn-secondary">Go Back</button>
                        </div>
                        </div>
                    </div>
                    </div>

                    <script>
                    $(document).ready(function(){
                        $("#notReadyModal").modal("show");
                    });
                    </script>

                    </body>
                    </html>';
            exit();
        }
    
        // Check if preliminary report already recorded with fk_status_id = 71
        $checkExistingQuery = "SELECT * FROM llx_commande_trackws WHERE labno = $1 AND fk_status_id = 71";
        $existingResult = pg_query_params($pg_con, $checkExistingQuery, array($labNumber));
    
        if (!$existingResult) {
            die("Error in check existing query: " . pg_last_error($pg_con));
        }
    
        if (pg_num_rows($existingResult) > 0) {
            echo '
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Already Given</title>
                    <link rel="stylesheet" href="../bootstrap/bootstrap.min.css">
                    <script src="../bootstrap/jquery-3.5.1.slim.min.js"></script>
                    <script src="../bootstrap/bootstrap.bundle.min.js"></script>
                </head>
                <body>

                <!-- Modal -->
                <div class="modal fade" id="alreadyGivenModal" tabindex="-1" role="dialog" aria-labelledby="alreadyGivenModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title" id="alreadyGivenModalLabel">Report Already Given</h5>
                    </div>
                    <div class="modal-body">
                        <p><strong>Lab Number:</strong> ' . htmlspecialchars($labNumber) . '</p>
                        <p>You have already given the preliminary report.</p>
                        <p>Please proceed to give the final report now.</p>
                    </div>
                    <div class="modal-footer">
                        <button onclick="window.history.back();" class="btn btn-secondary">Back</button>
                    </div>
                    </div>
                </div>
                </div>

                <script>
                $(document).ready(function(){
                    $("#alreadyGivenModal").modal("show");
                });
                </script>

                </body>
                </html>
            ';
            exit();
        }
    
        // Insert new record for fk_status_id = 71 (preliminary report acknowledged)
        $insertQuery = "INSERT INTO llx_commande_trackws (labno, fk_status_id, user_id) VALUES ($1, $2, $3)";
        $insertResult = pg_query_params($pg_con, $insertQuery, array($labNumber, 71, $user_id));
    
        if (!$insertResult) {
            die("Error in insert query: " . pg_last_error($pg_con));
        }


        echo '
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Preliminary Report Recorded</title>

                    <link rel="stylesheet" href="../bootstrap/bootstrap.min.css">
                    <script src="../bootstrap/jquery-3.5.1.slim.min.js"></script>
                    <script src="../bootstrap/bootstrap.bundle.min.js"></script>
                </head>
                <body>

                <!-- Success Modal -->
                <div class="modal fade" id="successModal" tabindex="-1" role="dialog" aria-labelledby="successModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title" id="successModalLabel">Preliminary Report Recorded</h5>
                    </div>
                    <div class="modal-body">
                        <p><strong>Lab Number:</strong> ' . htmlspecialchars($labNumber) . '</p>
                        <p>The preliminary report was successfully recorded.</p>
                        <p>You may now proceed with the final report later.</p>
                        <p><em>Redirecting back in a few seconds...</em></p>
                    </div>
                    <div class="modal-footer">
                        <button onclick="window.location.href=\'' . $_SERVER['HTTP_REFERER'] . '\';" class="btn btn-secondary">Go Back Now</button>
                    </div>
                    </div>
                </div>
                </div>

                <script>
                $(document).ready(function(){
                    $("#successModal").modal("show");

                    setTimeout(function() {
                        window.location.href = "' . $_SERVER['HTTP_REFERER'] . '";
                    }, 3000); // 3-second delay
                });
                </script>

                </body>
                </html>';

        exit();
    
    }
    elseif ($reportType === 'final report') {
        // Check if final report status (11) exists
        $checkFinalReadyQuery = "SELECT * FROM llx_commande_trackws WHERE labno = $1 AND fk_status_id = 11";
        $finalReadyResult = pg_query_params($pg_con, $checkFinalReadyQuery, array($labNumber));
    
        if (!$finalReadyResult) {
            die("Error in SQL query: " . pg_last_error($pg_con));
        }
    
        if (pg_num_rows($finalReadyResult) === 0) {
            // Final report not ready
            echo '
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Final Report Not Ready</title>
                    <link rel="stylesheet" href="../bootstrap/bootstrap.min.css">
                    <script src="../bootstrap/jquery-3.5.1.slim.min.js"></script>
                    <script src="../bootstrap/bootstrap.bundle.min.js"></script>
                </head>
                <body>
                <div class="modal fade" id="notReadyModal" tabindex="-1" role="dialog">
                  <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                      <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Final Report Not Ready</h5>
                      </div>
                      <div class="modal-body">
                        <p><strong>Lab Number:</strong> ' . htmlspecialchars($labNumber) . '</p>
                        <p>The final report has not been marked ready for delivery yet.</p>
                      </div>
                      <div class="modal-footer">
                        <button onclick="window.history.back();" class="btn btn-secondary">Go Back</button>
                      </div>
                    </div>
                  </div>
                </div>
                <script>
                    $(document).ready(function(){
                        $("#notReadyModal").modal("show");
                    });
                </script>
                </body>
                </html>';
            exit();
        }
    
        // Check if already acknowledged (fk_status  = 3)
        $checkAlreadyGiven = "SELECT 1 FROM llx_commande WHERE ref = $1 AND fk_statut = 3 LIMIT 1";
        $alreadyGivenResult = pg_query_params($pg_con, $checkAlreadyGiven, array($labNumber));

    
        if (!$alreadyGivenResult) {
            die("Error checking final report given: " . pg_last_error($pg_con));
        }
    
        if (pg_num_rows($alreadyGivenResult) > 0) {
            // Already given final report
            echo '
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Final Report Already Delivered</title>
                    <link rel="stylesheet" href="../bootstrap/bootstrap.min.css">
                    <script src="../bootstrap/jquery-3.5.1.slim.min.js"></script>
                    <script src="../bootstrap/bootstrap.bundle.min.js"></script>
                </head>
                <body>
                <div class="modal fade" id="alreadyGivenModal" tabindex="-1" role="dialog">
                  <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                      <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title">Report Already Delivered</h5>
                      </div>
                      <div class="modal-body">
                        <p><strong>Lab Number:</strong> ' . htmlspecialchars($labNumber) . '</p>
                        <p>already delivered the final report.</p>
                      </div>
                      <div class="modal-footer">
                        <button onclick="window.history.back();" class="btn btn-secondary">Back</button>
                      </div>
                    </div>
                  </div>
                </div>
                <script>
                    $(document).ready(function(){
                        $("#alreadyGivenModal").modal("show");
                    });
                </script>
                </body>
                </html>';
            exit();
        }
    
        // 2. Update llx_commande to set status = 3 (Delivered)
        $updateCommandeStatusQuery = "UPDATE llx_commande SET fk_statut = 3 WHERE ref = $1";
        $updateCommandeStatusResult = pg_query_params($pg_con, $updateCommandeStatusQuery, array($labNumber));

        if (!$updateCommandeStatusResult) {
            die("Error updating commande status: " . pg_last_error($pg_con));
        }

        // 2. Get rowid and fk_soc from llx_commande using ref
        $getCommandeQuery = "SELECT rowid, fk_soc FROM llx_commande WHERE ref = $1";
        $getCommandeResult = pg_query_params($pg_con, $getCommandeQuery, array($labNumber));
        if (!$getCommandeResult) {
            die("Error fetching order info: " . pg_last_error($pg_con));
        }
        $commande = pg_fetch_assoc($getCommandeResult);
        $rowid = $commande['rowid'];
        $fk_soc = $commande['fk_soc'];

        // 1. Get the latest ID from llx_actioncomm
        $getLastIdQuery = "SELECT id FROM llx_actioncomm ORDER BY id DESC LIMIT 1";
        $getLastIdResult = pg_query($pg_con, $getLastIdQuery);
        $lastIdRow = pg_fetch_assoc($getLastIdResult);
        $ref = isset($lastIdRow['id']) ? $lastIdRow['id'] + 1 : 1; // fallback to 1 if empty

       
        $insertActionQuery = "
            INSERT INTO llx_actioncomm (
                datec, datep, fk_user_author, fk_soc,
                label, note, fk_element, elementtype,
                percent, fulldayevent, priority, transparency,
                fk_user_action, visibility, event_paid, status,
                ref, code
            ) VALUES (
                (NOW() AT TIME ZONE 'UTC') AT TIME ZONE 'Asia/Dhaka',
                (NOW() AT TIME ZONE 'UTC') AT TIME ZONE 'Asia/Dhaka', 
                $1, $2,
                $3, $4, $5, 'order',
                -1, 0, 0, 0,
                $1, 'default', 0, 0,
                $6, $7
            )
        ";

        $label = "Order $labNumber classified delivered";
        $note = "Author: $username\nOrder $labNumber classified delivered";

        $insertActionResult = pg_query_params($pg_con, $insertActionQuery, array(
            $user_id,
            $fk_soc,
            $label,
            $note,
            $rowid,
            strval($ref), // convert to string if ref column is varchar
            'AC_ORDER_CLOSE'
        ));

        if (!$insertActionResult) {
            die("Error inserting actioncomm: " . pg_last_error($pg_con));
        }

    
        // Success message
        echo '
            <!DOCTYPE html>
            <html>
            <head>
                <title>Final Report Delivered</title>
                <link rel="stylesheet" href="../bootstrap/bootstrap.min.css">
                <script src="../bootstrap/jquery-3.5.1.slim.min.js"></script>
                <script src="../bootstrap/bootstrap.bundle.min.js"></script>
            </head>
            <body>
            <div class="modal fade" id="successModal" tabindex="-1" role="dialog">
              <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                  <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Final Report Delivered</h5>
                  </div>
                  <div class="modal-body">
                    <p><strong>Lab Number:</strong> ' . htmlspecialchars($labNumber) . '</p>
                    <p>The final report has been successfully recorded as delivered.</p>
                    <p><em>Redirecting back shortly...</em></p>
                  </div>
                  <div class="modal-footer">
                    <button onclick="window.location.href=\'' . $_SERVER['HTTP_REFERER'] . '\';" class="btn btn-secondary">Go Back Now</button>
                  </div>
                </div>
              </div>
            </div>
            <script>
                $(document).ready(function(){
                    $("#successModal").modal("show");
                    setTimeout(function() {
                        window.location.href = "' . $_SERVER['HTTP_REFERER'] . '";
                    }, 3000);
                });
            </script>
            </body>
            </html>';
        exit();
    }
     
    else {
        // Handle unknown report types
        echo "<div class='alert alert-warning'>";
        echo "<h3>Unknown Report Type</h3>";
        echo "<p>The specified report type '$reportType' is not recognized.</p>";
        echo "</div>";
    }
}
?>