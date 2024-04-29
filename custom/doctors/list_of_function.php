<?php 
include('connection.php');

function get_done_gross_list_for_doctor() {
    global $pg_con;

    $sql = "SELECT 
                g.gross_id,
                g.lab_number,
                SUBSTRING(g.lab_number, 4) AS lab_number_without_prefix,
                g.patient_code,
                g.gross_assistant_name, 
                g.gross_doctor_name,
                c.date_commande AS date,
                c.date_livraison AS delivery_date
            FROM 
                llx_gross g
            LEFT JOIN 
                llx_commande c ON c.ref = SUBSTRING(g.lab_number, 4)
            LEFT JOIN 
                llx_commande_extrafields e ON e.fk_object = c.rowid
            WHERE 
                g.gross_status = 'Done' 
                AND g.gross_is_completed = 'true'
                AND NOT EXISTS (
                    SELECT 1
                    FROM llx_micro m
                    WHERE g.gross_id = CAST(m.fk_gross_id AS INTEGER)
                )
                AND NOT EXISTS (
                    SELECT 1
                    FROM llx_diagnosis d
                    WHERE g.gross_id = CAST(d.fk_gross_id AS INTEGER)
                )
                AND NOT EXISTS (
                    SELECT 1
                    FROM llx_micro m
                    WHERE g.lab_number = m.lab_number
                )
                AND NOT EXISTS (
                    SELECT 1
                    FROM llx_diagnosis d
                    WHERE g.lab_number = d.lab_number
                );
    ";

    $result = pg_query($pg_con, $sql);

    $done_list = [];

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $done_list[] = [
                'gross_id' => $row['gross_id'],
                'lab_number' => $row['lab_number'],
                'patient_code' => $row['patient_code'],
                'gross_assistant_name' => $row['gross_assistant_name'],
                'gross_doctor_name' => $row['gross_doctor_name'],
                'date' => $row['date'],
                'delivery_date' => $row['delivery_date']
            ];
        }

        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $done_list;
}


?>