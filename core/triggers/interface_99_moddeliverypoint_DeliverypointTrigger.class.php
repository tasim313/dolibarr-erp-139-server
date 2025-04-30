<?php
require_once DOL_DOCUMENT_ROOT . '/core/triggers/dolibarrtriggers.class.php';

class InterfaceDeliveryPointTrigger extends DolibarrTriggers
{
    public function __construct($db)
    {
        parent::__construct($db);
        $this->name = preg_replace('/^Interface/i', '', get_class($this));
        $this->family = 'deliverypoint';
        $this->description = 'Trigger for payment processing and delivery point redirection';
        $this->version = '1.2.0';
        $this->picto = 'technic';
    }

    public function runTrigger($action, $object, $user, $langs, $conf)
    {
        dol_syslog("Trigger " . $this->name . " called for action " . $action, LOG_DEBUG);
        // Only process payment creation events
        if ($action !== 'PAYMENT_CUSTOMER_CREATE') {
            return 0;
        }

        // Validate payment object
        if (!is_object($object) || empty($object->ref)) {
            dol_syslog("Payment object is not valid", LOG_ERR);
            return 0;
        }

        $payment_ref = trim($object->ref);
        dol_syslog("Processing payment with ref: " . $payment_ref, LOG_DEBUG);

        try {
            // 1. Get payment and invoice data with payment status
            $sql = "SELECT p.amount, f.total_ttc, f.ref as invoice_ref,
                    f.rowid as invoice_id, f.datef as invoice_date,
                    f.fk_soc as thirdparty_id, f.fk_statut as invoice_status,
                    f.paye as invoice_paid
                    FROM " . MAIN_DB_PREFIX . "paiement p
                    JOIN " . MAIN_DB_PREFIX . "paiement_facture pf ON p.rowid = pf.fk_paiement
                    JOIN " . MAIN_DB_PREFIX . "facture f ON pf.fk_facture = f.rowid
                    WHERE p.ref = '" . $this->db->escape($payment_ref) . "'";

            $resql = $this->db->query($sql);

            if (!$resql) {
                dol_syslog("Payment data query failed: " . $this->db->lasterror(), LOG_ERR);
                return 0;
            }

            if ($this->db->num_rows($resql) == 0) {
                dol_syslog("No payment data found for ref: " . $payment_ref, LOG_WARNING);
                return 0;
            }

            $payment_data = $this->db->fetch_array($resql);

            $comment = json_encode($payment_data);

            // 2. Insert into custom table
            $sql_insert = "INSERT INTO " . MAIN_DB_PREFIX . "custom_trigger
                            (trigger_value, comment)
                            VALUES (99, '" . $this->db->escape($comment) . "')";

            $resql_insert = $this->db->query($sql_insert);
            if ($resql_insert) {

                // Directly check the value of 'invoice_paid' from the fetched array
                if (isset($payment_data['invoice_paid']) && $payment_data['invoice_paid'] == 1) {
                    echo ('insert value :' .$resql_insert);
                    echo("Payment data : " .$payment_data);
                    echo ("Comment : " . $comment);
                    $redirect_url = DOL_URL_ROOT . '/custom/deliverypoint/view/index.php?search=' . $payment_data['invoice_ref'];
                    // // Perform immediate redirect
                    header("Location: " . $redirect_url);
                    exit(); // Ensure script stops after redirection
                } else {
                    dol_syslog("Payment processed but invoice not fully paid (invoice_paid=" . (isset($payment_data['invoice_paid']) ? $payment_data['invoice_paid'] : 'not found') . ")", LOG_DEBUG);
                    return 0;
                }

            } else {
                dol_syslog("Insert failed: " . $this->db->lasterror(), LOG_ERR);
                return 0;
            }

        } catch (Exception $e) {
            dol_syslog("Trigger exception: " . $e->getMessage(), LOG_ERR);
            return 0;
        }
    }
}