<?php

class ComptaPaiementHook
{
    public function printTopBottom($parameters, &$object, &$action, $hookmanager)
    {
        global $langs;

        if (!empty($_GET['facid'])) {
            // Show console log
            print '<script>';
            print 'console.log("🎉 Congratulations Delivery Trigger Fire");';
            print '</script>';

            // Show button
            print '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    var btn = document.createElement("button");
                    btn.innerText = "New Hook Button";
                    btn.style.margin = "10px";
                    btn.onclick = function() {
                        alert("New Hook Button clicked!");
                    };
                    document.body.appendChild(btn);
                });
            </script>';
        }

        return 0;
    }
}
