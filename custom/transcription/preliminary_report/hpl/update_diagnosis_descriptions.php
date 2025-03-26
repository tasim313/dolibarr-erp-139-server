<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include("../connection.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!$pg_con) {
        die("Database connection failed: " . pg_last_error());
    }

    
    $fk_gross_id = pg_escape_string(trim($_POST['fk_gross_id'][0]));
    $lab_number = pg_escape_string(trim($_POST['lab_number'][0]));
    $created_user = pg_escape_string(trim($_POST['created_user'][0]));
    $status = pg_escape_string(trim($_POST['status'][0]));

    if (empty($fk_gross_id) || empty($lab_number) || empty($created_user)) {
        die("Error: Required fields are missing");
    }

    $specimens = $_POST['specimen'] ?? [];
    $titles = $_POST['title'] ?? [];
    $descriptions = $_POST['description'] ?? [];
    $comments = $_POST['comment'] ?? [];
    $row_ids = $_POST['row_id'] ?? [];

    pg_query($pg_con, "BEGIN");

    try {
        for ($i = 0; $i < count($specimens); $i++) {
            $specimen = pg_escape_string(trim($specimens[$i]));
            $title = pg_escape_string(trim($titles[$i]));
            $description = !empty(trim($descriptions[$i])) ? pg_escape_string(trim($descriptions[$i])) : '';
            $comment = !empty(trim($comments[$i])) ? pg_escape_string(trim($comments[$i])) : '';
            $row_id = pg_escape_string(trim($row_ids[$i]));

            // Debugging: Check if the row_id is being passed correctly
            echo "Row ID: $row_id\n"; 
            echo "Description: $description\n"; 
            echo "Comment: $comment\n"; 
            echo "Specimen: $specimen\n"; 
            echo "Title: $title\n";

            $sql_micro = "UPDATE llx_preliminary_report_diagnosis 
                         SET fk_gross_id = $1, specimen = $2, title = $3, lab_number = $4, 
                             description = $5, comment = $6, updated_user = $7, status = $8 
                         WHERE row_id = $9";

            $params = array($fk_gross_id, $specimen, $title, $lab_number, $description, $comment, $created_user, $status, $row_id);
            $result_micro = pg_query_params($pg_con, $sql_micro, $params);

            if (!$result_micro) {
                throw new Exception("Microscopic update failed: " . pg_last_error($pg_con));
            }
        }

        pg_query($pg_con, "COMMIT");
        header("Location: " . filter_var($_SERVER['HTTP_REFERER'], FILTER_SANITIZE_URL));
        exit();

    } catch (Exception $e) {
        pg_query($pg_con, "ROLLBACK");
        die("Database error: " . $e->getMessage());
    }
}

if ($pg_con) {
    pg_close($pg_con);
}
?>