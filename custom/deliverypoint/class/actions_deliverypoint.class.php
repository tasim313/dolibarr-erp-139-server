<?php

include('../connection.php');
include_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
include_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
include_once(DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php');
include_once DOL_DOCUMENT_ROOT.'/core/db/DoliDBPgsql.class.php';

$db = new DoliDBPgsql($pg_con);

$hookmanager=new HookManager($db);
$hookmanager->initHooks(array(
    'productcard',
    'productlist',
    'thirdpartycard',
    'thirdpartylist',
    'invoicecard',
    'invoicelist',
    'supplierinvoicecard',
    'ordercard',
    'ordersuppliercard',
    'propalcard',
    'shipmentcard',
    'deliverycard',
    'paymentcard',
    'accountancy',
    'membercard',
    'projectcard',
    'taskcard',
    'agenda',
    'contactcard',
    'expensereportcard',
    'usercard',
    'admin',
    'main',
    'globalcard',
));

class ActionsDeliveryPoint
{
    
    // In actions_invoicecard.class.php
    public function formObjectOptions($parameters, &$object, &$action, $hookmanager) {
        global $langs;
    
        // Check if we have the paid invoice marker
        if (!empty($_SESSION['payment_complete_invoice']) && $object->id == $_SESSION['payment_complete_invoice']) {
            echo "<script>console.log('✅ Your Payment is complete');</script>";
            echo "<div class='ok'>✅ Your Payment is complete</div>";
            unset($_SESSION['payment_complete_invoice']); // clear after show
        }
    
        return 0;
    }
}

