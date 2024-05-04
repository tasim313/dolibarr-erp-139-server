<?php 
include('connection.php');

function get_histo_gross_specimen_list() {
    global $pg_con;

    $sql = "SELECT g.gross_id, g.lab_number, g.gross_create_date, g.gross_status,
            s.gross_specimen_section_id, s.section_code, s.cassettes_numbers, s.tissue
            FROM llx_gross g
            INNER JOIN llx_gross_specimen_section s ON g.gross_id = CAST(s.fk_gross_id AS INTEGER)
            WHERE g.gross_status = 'Done'
            AND s.fk_gross_id !~ '[^\d]'";
    $result = pg_query($pg_con, $sql);

    $gross_specimens = [];

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $gross_specimens[] = ['Lab Number' => $row['lab_number'], 'Gross Create Date' => $row['gross_create_date'],
            'Gross Status'=>$row['gross_status'], 'gross_specimen_section_id' => $row['gross_specimen_section_id'], 
            'section_code' => $row['section_code'], 'cassettes_numbers' => $row['cassettes_numbers'], 'tissue' => $row['tissue']];
        }

        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $gross_specimens;
}


?>