<?php

class HookCustomProduct
{
    public function formObjectOptions($parameters, &$object, &$action, $hookmanager)
    {
        global $langs;

        if (get_class($object) == 'Product' && $action === 'create') {
            print '<div class="info" style="color: green; font-weight: bold;">[Hook] Custom logic for Product creation loaded.</div>';
        }

        return 0;
    }
}

?>
