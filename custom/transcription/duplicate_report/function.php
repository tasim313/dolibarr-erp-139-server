<?php 
include('../connection.php');

function diagonsis_micro_complete_by_lab($lab_number) {
    global $pg_con;

    // SQL query to check if both micro and diagnosis statuses are 'done'
    $sql = "
        SELECT 
            COALESCE(CASE 
                        WHEN LOWER(TRIM(m.status)) = 'done' AND LOWER(TRIM(d.status)) = 'done' THEN 'OK'
                        ELSE 'Not OK'
                    END, 'Not OK') AS status_check
        FROM 
            llx_micro m
        LEFT JOIN 
            llx_diagnosis d ON m.lab_number = d.lab_number
        WHERE 
            m.lab_number = $1";

    // Prepare and execute the SQL query
    $result = pg_prepare($pg_con, "get_status_check", $sql);
    $result = pg_execute($pg_con, "get_status_check", array($lab_number));

    if ($result) {
        $row = pg_fetch_assoc($result);
        pg_free_result($result);

        return $row['status_check']; // Return 'OK' or 'Not OK'
    } else {
        return 'Error';
    }
}


?>