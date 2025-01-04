<?php
include("../connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Validate and sanitize input data
    $rowid = pg_escape_string($_POST['rowid'][0] ?? '');
    if (empty($rowid)) {
        echo "Error: Missing or invalid rowid";
        exit;
    }

    // Sanitize individual fields
    $prev_fnac = pg_escape_string($_POST['prev_fnac'][0] ?? '');
    $prev_fnac_date = !empty($_POST['prev_fnac_date'][0]) ? pg_escape_string($_POST['prev_fnac_date'][0]) : null;
    $prev_fnac_op = pg_escape_string($_POST['prev_fnac_op'][0] ?? '');
    $informed = pg_escape_string($_POST['informed'][0] ?? '');
    $given = pg_escape_string($_POST['given'][0] ?? '');
    $referred_by_dr_lastname = pg_escape_string($_POST['referred_by_dr_lastname'][0] ?? '');
    $referred_from_lastname = pg_escape_string($_POST['referred_from_lastname'][0] ?? '');
    $add_history = pg_escape_string($_POST['add_history'][0] ?? '');
    $other_labno = pg_escape_string($_POST['other_labno'][0] ?? '');
    $prev_biopsy = pg_escape_string($_POST['prev_biopsy'][0] ?? '');
    $prev_biopsy_date = !empty($_POST['prev_biopsy_date'][0]) ? pg_escape_string($_POST['prev_biopsy_date'][0]) : null;
    $prev_biopsy_op = pg_escape_string($_POST['prev_biopsy_op'][0] ?? '');
    $referred_by_dr_text = pg_escape_string($_POST['referred_by_dr_text'][0] ?? '');
    $referredfrom_text = pg_escape_string($_POST['referredfrom_text'][0] ?? '');

    // Update llx_commande_extrafields
    $sql_extrafields = "
        UPDATE llx_commande_extrafields SET 
        prev_fnac = '$prev_fnac',
        prev_fnac_date = " . ($prev_fnac_date ? "'$prev_fnac_date'" : "NULL") . ",
        prev_fnac_op = '$prev_fnac_op',
        informed = '$informed',
        given = '$given',
        add_history = '$add_history',
        other_labno = '$other_labno',
        prev_biopsy = '$prev_biopsy',
        prev_biopsy_date = " . ($prev_biopsy_date ? "'$prev_biopsy_date'" : "NULL") . ",
        prev_biopsy_op = '$prev_biopsy_op',
        referred_by_dr_text = '$referred_by_dr_text',
        referredfrom_text = '$referredfrom_text'
        WHERE rowid = '$rowid';
    ";

    $result_extrafields = pg_query($pg_con, $sql_extrafields);
    if ($result_extrafields) {
        echo "Updated llx_commande_extrafields rows: " . pg_affected_rows($result_extrafields) . "\n";
    } else {
        echo "Error in llx_commande_extrafields: " . pg_last_error($pg_con) . "\n";
    }

    // Update referred_by_dr_lastname in llx_socpeople
    $sql_referred_by_dr = "
        UPDATE llx_socpeople 
        SET lastname = '$referred_by_dr_lastname'
        WHERE rowid = (
            SELECT fk_socpeople 
            FROM llx_categorie_contact 
            WHERE fk_categorie = 3 
            AND fk_socpeople = (
                SELECT referred_by_dr::integer 
                FROM llx_commande_extrafields 
                WHERE rowid = '$rowid'
            )
        );
    ";

    $result_referred_by_dr = pg_query($pg_con, $sql_referred_by_dr);
    if ($result_referred_by_dr) {
        echo "Updated referred_by_dr rows: " . pg_affected_rows($result_referred_by_dr) . "\n";
    } else {
        echo "Error in referred_by_dr: " . pg_last_error($pg_con) . "\n";
    }

    // Update referred_from_lastname in llx_socpeople
    $sql_referred_from_dr = "
        UPDATE llx_socpeople 
        SET lastname = '$referred_from_lastname'
        WHERE rowid = (
            SELECT fk_socpeople 
            FROM llx_categorie_contact 
            WHERE fk_categorie = 4 
            AND fk_socpeople = (
                SELECT referred_from::integer 
                FROM llx_commande_extrafields 
                WHERE rowid = '$rowid'
            )
        );
    ";

    $result_referred_from_dr = pg_query($pg_con, $sql_referred_from_dr);
    if ($result_referred_from_dr) {
        echo "Updated referred_from rows: " . pg_affected_rows($result_referred_from_dr) . "\n";
    } else {
        echo "Error in referred_from: " . pg_last_error($pg_con) . "\n";
    }

    // Check for errors
    if ($result_extrafields && $result_referred_by_dr && $result_referred_from_dr) {
        $referer = $_SERVER['HTTP_REFERER'];
        header("Location: $referer");
        exit;
    } else {
        echo "Error updating data: " . pg_last_error($pg_con);
    }

    pg_close($pg_con);
}
?>