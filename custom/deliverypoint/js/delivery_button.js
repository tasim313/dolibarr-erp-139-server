document.addEventListener("DOMContentLoaded", function() {
    // Check every second for Re-Open button (async page loading)
    const checkInterval = setInterval(function() {
        const reopenBtn = document.querySelector('a[href*="reopen"]');
        if (reopenBtn) {
            clearInterval(checkInterval);
            
            // Create custom button
            const deliverBtn = document.createElement('a');
            deliverBtn.className = 'butAction';
            deliverBtn.style.marginLeft = '5px';
            deliverBtn.href = DOL_URL_ROOT + '/custom/deliverypoint/deliver.php?id=' + <?php echo GETPOST('id', 'int'); ?>;
            
            // Add icon and text
            deliverBtn.innerHTML = '<i class="fa fa-truck"></i> ' + 
                <?php echo json_encode($langs->trans("CustomDeliver")); ?>;
            
            // Insert after Re-Open button
            reopenBtn.parentNode.insertBefore(deliverBtn, reopenBtn.nextSibling);
        }
    }, 1000);
});