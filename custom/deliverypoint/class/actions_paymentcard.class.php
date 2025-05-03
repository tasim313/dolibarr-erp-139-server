<?php

class ActionsPaymentcard
{
    function doActions($parameters, &$object, &$action, $hookmanager)
    {
        global $db, $conf, $langs;

        // Check if we are inside the paymentcard context
        if (in_array('paymentcard', explode(':', $parameters['context'])))
        {
            // You can debug object if needed
            // dol_syslog(print_r($object,true), LOG_DEBUG);

            // Check: if payment is validated and related invoice is fully paid
            if (!empty($object) && method_exists($object, 'fetch') && $object->id > 0)
            {
                // Load the related invoice(s)
                if (!empty($object->invoiceid)) {
                    require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
                    $invoice = new Facture($db);
                    $invoice->fetch($object->invoiceid);

                    if ($invoice->statut == 2) // Status 2 = Paid
                    {
                        // Success: Now redirect
                        if (!headers_sent()) {
                            // Choose your fixed URL or fallback to referrer
                            $redirect_url = DOL_URL_ROOT.'/custom/deliverypoint/view/index.php';

                            // Uncomment this if you want to fallback to referrer if needed
                            // if (empty($redirect_url)) $redirect_url = $_SERVER['HTTP_REFERER'];

                            header('Location: ' . $redirect_url);
                            exit;
                        }
                    }
                }
            }
        }

        return 0; // No error
    }

    public function beforeBodyClose( $parameters, &$object, &$action, $hookmanager ) {
		
        $hookmanager->resPrint = '
    <div style="width: 250px; background: white; text-align: center; padding: 10px; margin: 0 auto; border: 8px solid red;">
        BODY ENding
    </div>';
                
        return 1;
    
    }
}