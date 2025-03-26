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

    pg_query($pg_con, "BEGIN");

    try {
        for ($i = 0; $i < count($specimens); $i++) {
            $specimen = pg_escape_string(trim($specimens[$i]));
            $title = pg_escape_string(trim($titles[$i]));
            $description = !empty(trim($descriptions[$i])) ? pg_escape_string(trim($descriptions[$i])) : 'N/A';
            $comment = !empty(trim($comments[$i])) ? pg_escape_string(trim($comments[$i])) : 'N/A';

            $sql_micro = "INSERT INTO llx_preliminary_report_diagnosis 
                         (fk_gross_id, specimen, title, lab_number, description, comment, created_user, status)
                         VALUES ($1, $2, $3, $4, $5, $6, $7, $8)";
            
            $params = array($fk_gross_id, $specimen, $title, $lab_number, $description, $comment, $created_user, $status);
            $result_micro = pg_query_params($pg_con, $sql_micro, $params);
            
            if (!$result_micro) {
                throw new Exception("Microscopic insert failed: " . pg_last_error($pg_con));
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