<?php

class HookMyCustomButton
{
    public function formObjectOptions($parameters, &$object, &$action, $hookmanager)
    {
        if (get_class($object) === 'Facture') {
            echo '<div class="inline-block divButAction">';
            echo '<a class="butAction" href="?id=' . $object->id . '&action=mycustomaction">My Custom Button</a>';
            echo '</div>';
        }
        return
         0;
    }

    public function beforeBodyClose($parameters, &$object, &$action, $hookmanager)
    {
        $hookmanager->resPrint = '
        <div style="width: 250px; background: white; text-align: center; padding: 10px; margin: 0 auto; border: 8px solid red;">
            BODY ENding
        </div>';
        return 1;
    }
}
