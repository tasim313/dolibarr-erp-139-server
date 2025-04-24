<?php

class ActionsDeliveryPoint
{
    /**
     * Hook to inject JS popup if session message exists
     */
    public function addHtmlFooter($parameters, &$object, &$action, $hookmanager)
        {
            error_log("✅ addHtmlFooter hook from mymodule executed");

            if (session_status() === PHP_SESSION_NONE) session_start();

            if (!empty($_SESSION['invoice_paid_popup'])) {
                $popup_message = addslashes($_SESSION['invoice_paid_popup']);
                print "<script>
                    alert('$popup_message');
                    console.log('$popup_message');
                </script>";
                unset($_SESSION['invoice_paid_popup']);
            }

            return 0;
        }

}
