<?php 
include('connection.php');

function get_invoice_list_delivery_point($invoice) {
    global $pg_con;

    if (!$pg_con) {
        error_log('Database connection error.');
        return false;
    }

    $sql = "SELECT ref FROM llx_facture WHERE ref = $1";

    $stmt_name = "get_invoice_list_delivery_point";
    static $is_prepared = false;

    if (!$is_prepared) {
        $prepare_result = pg_prepare($pg_con, $stmt_name, $sql);
        if (!$prepare_result) {
            error_log('Query preparation error: ' . pg_last_error($pg_con));
            return false;
        }
        $is_prepared = true;
    }

    $result = pg_execute($pg_con, $stmt_name, [$invoice]);

    if ($result) {
        $rows = pg_fetch_all($result);
        pg_free_result($result);
        return $rows ?: false;
    } else {
        error_log('Query execution error: ' . pg_last_error($pg_con));
        return false;
    }
}


function get_payment_list_using_invoice_number_delivery_point($invoice) {
    global $pg_con;

    if (!$pg_con) {
        error_log('Database connection error.');
        return false;
    }

    $sql = "SELECT 
                -- Payment Details
                p.rowid AS payment_rowid, 
                p.ref AS payment_ref, 
                p.amount AS payment_amount, 
                p.datec AS payment_date, 

                -- Payment-Facture Join Table
                pf.rowid AS pf_rowid, 
                pf.fk_paiement AS fk_payment, 
                pf.fk_facture AS fk_invoice, 
                pf.amount AS allocated_amount,

                -- Invoice Details
                f.rowid AS invoice_rowid,
                f.ref AS invoice_ref, 
                f.datec AS invoice_date_created, 
                f.datef AS invoice_due_date, 
                f.date_valid AS invoice_validation_date, 
                f.date_closing AS invoice_closing_date, 
                f.paye AS amount_paid, 
                f.remise_percent AS discount_percent, 
                f.remise_absolue AS absolute_discount, 
                f.remise AS total_discount, 
                f.close_code AS closing_code, 
                f.close_note AS closing_note, 
                f.total_ht AS total_without_tax, 
                f.fk_statut AS status_numeric,  

                -- Computed/Readable Status
                CASE 
                    WHEN f.fk_statut = 0 THEN 'Draft'
                    WHEN f.fk_statut = 1 THEN 'Unpaid'
                    WHEN f.fk_statut = 2 THEN 'Paid'
                    WHEN f.fk_statut = 3 THEN 'Abandoned'
                    ELSE 'Unknown'
                END AS status_text,  

                -- User Info
                author.login AS author_user_login, 
                closer.login AS closer_user_login, 

                -- Notes
                f.note_private AS private_note, 
                f.note_public AS public_note

                FROM llx_facture AS f
                LEFT JOIN llx_paiement_facture AS pf ON pf.fk_facture = f.rowid
                LEFT JOIN llx_paiement AS p ON p.rowid = pf.fk_paiement
                LEFT JOIN llx_user AS author ON f.fk_user_author = author.rowid 
                LEFT JOIN llx_user AS closer ON f.fk_user_closing = closer.rowid

                -- Filter by invoice reference
                WHERE f.ref = $1";

    $stmt_name = "get_payment_list_using_invoice_number_delivery_point";
    static $is_prepared = false;

    if (!$is_prepared) {
        $prepare_result = pg_prepare($pg_con, $stmt_name, $sql);
        if (!$prepare_result) {
            error_log('Query preparation error: ' . pg_last_error($pg_con));
            return false;
        }
        $is_prepared = true;
    }

    $result = pg_execute($pg_con, $stmt_name, [$invoice]);

    if ($result) {
        $rows = pg_fetch_all($result);
        pg_free_result($result);
        return $rows ?: false;
    } else {
        error_log('Query execution error: ' . pg_last_error($pg_con));
        return false;
    }
}



?>