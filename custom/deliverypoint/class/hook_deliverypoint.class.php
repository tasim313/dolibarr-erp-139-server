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


class HookDeliverypoint
{
    /**
     * Hook to add content at the end of the formObjectOptions and before object validation.
     *
     * @param array $parameters Hook parameters
     * @param CommonObject $object The object
     * @param string $action Current action
     * @param HookManager $hookmanager Hook manager
     * @return string HTML code to display
     */
    public function formObjectOptions($parameters, &$object, &$action, $hookmanager)
    {
        global $langs, $conf;

        $html = '';

        // if ($parameters['currentcontext'] == 'paymentcard' && $object->multicurrency_code == 'BDT') {
        //     // if (!empty($_GET['action']) && $_GET['action'] === 'validate' && !empty($_GET['confirm']) && $_GET['confirm'] === 'yes') {
        //     //     $html .= '<script type="text/javascript">
        //     //         window.addEventListener("load", function () {
        //     //             window.location.href = document.referrer;
        //     //         });
        //     //     </script>';
        //     // }

        //     $html .= '<style>
        //         a.butAction[href*="action=valid"] {
        //             display: none !important;
        //         }
        //     </style>';
        // }

        // Only apply on the payment validation page
        if (strpos($_SERVER['PHP_SELF'], 'compta/paiement.php') !== false && $object->multicurrency_code == 'BDT') {
            $html .= '<script>
                document.addEventListener("DOMContentLoaded", function () {
                    const validateBtn = document.querySelector("form[name=\'formconfirm\'] input[type=\'submit\']");
                    if (validateBtn) {
                        validateBtn.style.display = "none";
                    }
                });
                console.log("Hook loaded, trying to hide Validate button.");
            </script>';
        }
        

        return $html;
    }

    /**
     * Hook to add JS for confirmation before payment validation.
     *
     * @param array $parameters Hook parameters
     * @param CommonObject $object The object
     * @param string $action Current action
     * @param HookManager $hookmanager Hook manager
     * @return string HTML code to display
     */
    public function formBeforeValidate($parameters, &$object, &$action, $hookmanager)
    {
        global $langs, $conf;

        $html = '';

        if (get_class($object) == 'Paiement' && in_array($action, array('confirm_valid'))) {
            if ($object->multicurrency_code == 'BDT' && !empty($conf->global->DELIVERYPOINT_BDT_PAYMENT_CONFIRMATION)) {
                $langs->load('deliverypoint@deliverypoint');

                $html .= '<script type="text/javascript">
                $(document).ready(function() {
                    $("form[name=\'formconfirm\']").submit(function(event) {
                        if (!confirm("' . $langs->trans('AreYouSureValidateBDTPayment') . '")) {
                            event.preventDefault();
                        } else {
                            // Add redirection after confirmation
                            window.addEventListener("load", function () {
                                window.location.href = document.referrer;
                            });
                        }
                    });
                });
                </script>';
            }
        }

        return $html;
    }

    public function formConfirm($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $langs, $user;

        // Debug to verify hook is called
        dol_syslog("HookDeliverypoint::formConfirm triggered");

        if (!empty($object) && $object->element == 'payment' && $object->multicurrency_code == 'BDT') {
            $js = '<script>
                document.addEventListener("DOMContentLoaded", function () {
                    const validateBtn = document.querySelector("form[name=\'formconfirm\'] input[type=\'submit\']");
                    if (validateBtn) {
                        validateBtn.style.display = "none";
                        console.log("Validate button hidden.");
                    } else {
                        console.log("Validate button not found.");
                    }
                });
            </script>';
            $hookmanager->resPrint .= $js;
        }

        return 0;
    }
}
?>