<?php

class DeliverypointButtons
{
    private $db;
    
    public function __construct($db)
    {
        $this->db = $db;
    }
    
    /**
     * Add custom Deliver button when Re-Open button exists
     */
    public function addDeliveryButton($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $langs, $user;
        
        // Only execute for invoice card page
        if ($parameters['currentcontext'] != 'invoicecard') {
            return 0;
        }
        
        // Check if we're on an invoice page and Re-Open button exists
        if (!empty($parameters['head']) && preg_match('/reopen/', $parameters['head'])) {
            $url = DOL_URL_ROOT.'/custom/deliverypoint/deliver.php?id='.$object->id;
            
            // Add our custom button
            $parameters['head'] .= '<a class="butAction" href="'.$url.'">';
            $parameters['head'] .= img_picto('', 'truck', 'class="pictofixedwidth"');
            $parameters['head'] .= ' '.$langs->trans("CustomDeliver");
            $parameters['head'] .= '</a>';
        }
        
        return 0;
    }
}