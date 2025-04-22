<?php

require_once DOL_DOCUMENT_ROOT . '/core/triggers/dolibarrtriggers.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';

class Interface_99_custom_PaymentTrigger extends DolibarrTriggers
{
    public $family = 'invoice';
    public $description = 'Trigger when invoice is fully paid';
    public $version = self::VERSION_DOLIBARR;
    public $picto = 'technic';

    public function runTrigger($action, $object, $user, $langs, $conf)
    {
        global $db;

        if ($action == 'PAYMENT_CUSTOMER_CREATE') {
            dol_syslog("🧪 PAYMENT_CUSTOMER_CREATE Trigger Fired", LOG_DEBUG);

            // Check if there are invoices linked to this payment
            if (!empty($object->linked_objects['facture']) && is_array($object->linked_objects['facture'])) {
                foreach ($object->linked_objects['facture'] as $invoice_id => $not_used) {
                    $invoice = new Facture($db);
                    $invoice->fetch($invoice_id);

                    $amount_paid = $invoice->getSommePaiement();
                    $total = $invoice->total_ttc;

                    dol_syslog("Invoice #$invoice->id: Paid = $amount_paid / Total = $total", LOG_DEBUG);

                    if ($amount_paid >= $total && $invoice->statut == 2) {
                        dol_syslog("✅ Invoice #$invoice->id is fully paid", LOG_NOTICE);

                        // Output debug info only if not via async or CLI
                        if (php_sapi_name() != 'cli' && !defined('RESTLER')) {
                            // Output for debug
                            var_dump("✅ Your Payment is complete");

                            // JavaScript for browser console
                            echo '<script>console.log("✅ Your Payment is complete");</script>';

                            // Optional: redirect back to referring page
                            if (!headers_sent() && !empty($_SERVER['HTTP_REFERER'])) {
                                header("Location: " . $_SERVER['HTTP_REFERER']);
                                exit;
                            }
                        }
                    }
                }
            }
        }

        return 0;
    }
}
