<?php

require_once DOL_DOCUMENT_ROOT . '/core/triggers/dolibarrtriggers.class.php';

/**
 * Trigger to act when invoice is fully paid
 */
class Interfacedeliverypointtrigger extends DolibarrTriggers
{
    public function __construct($db)
    {
        $this->db = $db;

        $this->name = preg_replace('/^Interface/i', '', get_class($this));
        $this->family = 'deliverypoint';
        $this->description = 'Trigger to log and show message when invoice is fully paid';
        $this->version = '1.0.0';
        $this->picto = 'technic';
    }

    /**
     * Trigger execution
     *
     * @param string $action
     * @param object $object
     * @param User $user
     * @param Translate $langs
     * @param Conf $conf
     * @return int
     */
    public function runTrigger($action, $object, $user, $langs, $conf)
    {
        if ($action === 'PAYMENT_CUSTOMER_CREATE') {
            try {
                // Manual PostgreSQL connection
                $host = "postgres";
                $user = "root";
                $password = "root";
                $db_name = "dolibarr";

                $pg_conn_string = "host=$host dbname=$db_name user=$user password=$password";
                $pg_con = pg_connect($pg_conn_string);

                if (!$pg_con) {
                    error_log("DeliveryPoint Trigger: Failed to connect to PostgreSQL: " . pg_last_error());
                    return 0;
                }

                // Check total amount vs amount paid
                $invoice_id = $object->facid;
                $total_ttc = $object->total_ttc;
                $amount_paid = $object->am;

                if (floatval($amount_paid) >= floatval($total_ttc)) {
                    // Insert into llx_custom_trigger
                    $comment = "Invoice #$invoice_id fully paid.";
                    $insert_sql = "INSERT INTO llx_custom_trigger (trigger_value, comment) VALUES (1, $1)";
                    $result = pg_query_params($pg_con, $insert_sql, [$comment]);

                    if ($result) {
                        error_log("DeliveryPoint Trigger: Trigger inserted successfully for invoice $invoice_id.");
                    } else {
                        error_log("DeliveryPoint Trigger: Failed to insert trigger - " . pg_last_error($pg_con));
                    }

                    // Set session to show popup (optional)
                    if (session_status() === PHP_SESSION_NONE) {
                        session_start();
                    }
                    $_SESSION['invoice_paid_popup'] = "Invoice #$invoice_id has been fully paid.";
                    header("Location: ".$_SERVER['HTTP_REFERER']);
                }

                pg_close($pg_con);

            } catch (Exception $e) {
                error_log('DeliveryPoint Trigger Exception: ' . $e->getMessage());
            }
        }

        return 0;
    }
}