<?php 
include("connection.php");

$dbconn = $pg_con;

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    try {
        // 1. Fetch the data before deleting
        $selectQuery = "SELECT gross_specimen_section_id, fk_gross_id, section_code, specimen_section_description, cassettes_numbers, tissue 
                        FROM llx_gross_specimen_section 
                        WHERE gross_specimen_section_id = $1";

        $selectResult = pg_query_params($dbconn, $selectQuery, array($id));

        if ($selectResult && pg_num_rows($selectResult) > 0) {
            $row = pg_fetch_assoc($selectResult);

            // 2. Insert into history table
            $insertQuery = "INSERT INTO llx_gross_specimen_section_history 
                            (gross_specimen_section_id, fk_gross_id, section_code, specimen_section_description, cassettes_numbers, tissue) 
                            VALUES ($1, $2, $3, $4, $5, $6)";

            $insertResult = pg_query_params($dbconn, $insertQuery, array(
                $row['gross_specimen_section_id'],
                $row['fk_gross_id'],
                $row['section_code'],
                $row['specimen_section_description'],
                $row['cassettes_numbers'],
                $row['tissue']
            ));

            if (!$insertResult) {
                echo "Failed to insert into history table: " . pg_last_error($dbconn);
                exit();
            }

            // 3. Get lab_number from llx_gross
            $labNumberQuery = "SELECT lab_number FROM llx_gross WHERE gross_id = $1";
            $labResult = pg_query_params($dbconn, $labNumberQuery, array($row['fk_gross_id']));

            if ($labResult && pg_num_rows($labResult) > 0) {
                $labRow = pg_fetch_assoc($labResult);
                $lab_number = $labRow['lab_number'];

                // 4. Get current cassette count and cassette rowid (using latest row instead of CURRENT_DATE)
                $batchQuery = "SELECT c.total_cassettes_count, c.rowid AS cassette_rowid
                               FROM llx_batch_details AS d
                               JOIN llx_batch_cassette_counts AS c ON d.batch_number = c.batch_details_cassettes
                               WHERE d.lab_number = $1";

                $batchResult = pg_query_params($dbconn, $batchQuery, array($lab_number));

                if ($batchResult && pg_num_rows($batchResult) > 0) {
                    $batchRow = pg_fetch_assoc($batchResult);
                    $currentCount = (int)$batchRow['total_cassettes_count'];
                    $cassetteRowId = $batchRow['cassette_rowid'];
                    $cassettesToSubtract = (int)$row['cassettes_numbers'];

                    $newCount = max(0, $currentCount - 1); // prevent negative

                    // 5. Update cassette count
                    $updateQuery = "UPDATE llx_batch_cassette_counts SET total_cassettes_count = $1 WHERE rowid = $2";
                    $updateResult = pg_query_params($dbconn, $updateQuery, array($newCount, $cassetteRowId));

                    if (!$updateResult) {
                        echo "Failed to update total_cassettes_count: " . pg_last_error($dbconn);
                        exit();
                    }

                    // 6. Now delete from original table
                    $deleteQuery = "DELETE FROM llx_gross_specimen_section WHERE gross_specimen_section_Id = $1";
                    $deleteResult = pg_query_params($dbconn, $deleteQuery, array($id));

                    if ($deleteResult) {
                        header("Location: " . $_SERVER['HTTP_REFERER']);
                        exit();
                    } else {
                        echo "Error in deletion: " . pg_last_error($dbconn);
                    }

                } else {
                    echo "No cassette count data found for the lab number.";
                    exit();
                }

            } else {
                echo "Lab number not found for gross ID.";
                exit();
            }

        } else {
            echo "No data found for the given ID.";
        }

    } catch (Exception $e) {
        echo "Exception occurred: " . $e->getMessage();
    }
} else {
    echo "No ID provided.";
}
?>