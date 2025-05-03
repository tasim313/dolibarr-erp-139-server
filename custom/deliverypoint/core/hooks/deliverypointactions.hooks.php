<?php

if (!defined('DOL_VERSION')) {
    die('Can\'t access directly');
}

class deliverypointactionsHooks extends DolibarrHook
{
    /**
     * Hook constructor
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Returns the module's information.
     *
     * @return array Module information.
     */
    public function info()
    {
        return array(
            'name' => 'Delivery Point Actions Hook',
            'id' => 'deliverypointactions',
            'version' => '1.0',
            'description' => 'Adds a "Back in Delivery" button to the invoice card.',
            'author' => 'Tasim',
            'rights_class' => '',
            'url' => ''
        );
    }

    /**
     * Hook on the card actions.
     *
     * @param array   $parameters Hook parameters.
     * @param object  $object     The current object (Facture in this case).
     * @param string  $action     The current action.
     * @param object  $hookmanager The hook manager.
     *
     * @return int|void
     */
    public function card_actions($parameters, $object, $action, $hookmanager)
    {
        global $langs, $conf;

        if (in_array('compta/facture/card.php', $hookmanager->loaded_modules)) {
            if (is_object($object) && get_class($object) == 'Facture') {
                // Check if the user has permission to perform this action (optional)
                if ($user->rights->deliverypoint->backtodelivery) {
                    $langs->load('deliverypoint@deliverypoint'); // Load your module's language file

                    $form = new Form($this->db);

                    $url = $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=backtodelivery'; // Adjust URL as needed

                    $button = $form->form_button($url, $langs->trans('BackInDelivery'));

                    print $button;
                }
            }
        }
        return 0; // Important: Return 0 to continue the hook chain
    }
}