<?php 
include('../connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fk_gross_id = $_POST['fk_gross_id'];
    
    if (isset($_POST['specimen_code']) && isset($_POST['description'])) {
        $specimenCodes = $_POST['specimen_code'];
        $descriptions = $_POST['description'];
        
        // Prepare the SQL query
        $query = "INSERT INTO llx_gross_specimen_used (fk_gross_id, section_code, description) VALUES ($1, $2, $3)";
        
        $result = pg_prepare($pg_con, "insert_single", $query);
        if ($result) {
            // Loop through each specimen and description entry
            foreach ($specimenCodes as $index => $specimenCode) {
                $description = $descriptions[$index];
                
                // Execute the prepared statement for each row
                $result = pg_execute($pg_con, "insert_single", array($fk_gross_id, $specimenCode, $description));
                
                if (!$result) {
                    // Debug query execution error for individual row
                    echo 'Error inserting row. ' . pg_last_error($pg_con);
                    exit;
                }
            }
            
            // Redirect to the previous page after successful insertion
            echo '<script>';
            echo 'window.location.href = "' . $_SERVER['HTTP_REFERER'] . '";'; 
            echo '</script>';
        } else {
            // Debug query preparation error
            echo 'Error preparing statement. ' . pg_last_error($pg_con);
        }
    }  
} else {
    // Redirect if not a POST request
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
}
?>