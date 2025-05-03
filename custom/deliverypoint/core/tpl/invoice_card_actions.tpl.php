<?php
// Original content plus your custom button
if (!empty($reopenbutton)) {
    echo $reopenbutton;
    echo '<a class="butAction" href="'.DOL_URL_ROOT.'/custom/deliverypoint/deliver.php?id='.$object->id.'">'.$langs->trans("CustomDeliver").'</a>';
}
// Rest of original content
?>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const reopenBtn = document.querySelector('a[href*="reopen"]');
    if (reopenBtn) {
        const deliverBtn = document.createElement('a');
        deliverBtn.className = 'butAction';
        deliverBtn.href = '<?php echo DOL_URL_ROOT ?>/custom/deliverypoint/deliver.php?id=<?php echo GETPOST("id") ?>';
        deliverBtn.textContent = '<?php echo dol_escape_js($langs->trans("CustomDeliver")) ?>';
        reopenBtn.parentNode.insertBefore(deliverBtn, reopenBtn.nextSibling);
    }
});
</script>