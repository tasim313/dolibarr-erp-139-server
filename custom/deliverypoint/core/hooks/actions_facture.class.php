<?php

class ActionsFacture
{
    private $db;
    
    public function __construct($db)
    {
        $this->db = $db;
    }
    
    public function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $langs, $user;
        
        // Only execute for invoice card page
        if ($parameters['currentcontext'] != 'invoicecard') {
            return 0;
        }
        
        // Check if Re-Open button is present
        if (!empty($parameters['head']) && strpos($parameters['head'], 'reopen') !== false) {
            // Add your custom Deliver button
            $url = DOL_URL_ROOT.'/custom/deliverypoint/deliver.php?id='.$object->id;
            $parameters['head'] .= '<a class="butAction" href="'.$url.'">'.$langs->trans("CustomDeliver").'</a>';
        }
        
        return 0;
    }
}

class DeliveryButtonHook
{
    public function addJS($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $langs;
        
        if ($parameters['currentcontext'] == 'invoicecard') {
            echo '
            <script>
            document.addEventListener("DOMContentLoaded", function() {
                const reopenBtn = document.querySelector(\'a[href*="reopen"]\');
                if (reopenBtn) {
                    const deliverBtn = document.createElement(\'a\');
                    deliverBtn.className = \'butAction\';
                    deliverBtn.href = \''.DOL_URL_ROOT.'/custom/deliverypoint/deliver.php?id='.GETPOST('id').'\';
                    deliverBtn.textContent = \''.dol_escape_js($langs->trans("CustomDeliver")).'\';
                    reopenBtn.parentNode.insertBefore(deliverBtn, reopenBtn.nextSibling);
                }
            });
            </script>';
        }
        return 0;
    }
}

// Register hook
$hookmanager->initHooks(array('invoicecard'));
$hookmanager->addAction('addMoreActionsButtons', 'YourModuleButtons', $this->db, 0);