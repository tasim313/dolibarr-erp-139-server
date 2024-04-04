<?php 
include("connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Escape and sanitize input data
    $specimens = isset($_POST['specimen']) ? $_POST['specimen'] : [];
    $lab_numbers = isset($_POST['lab_number']) ? $_POST['lab_number'] : [];
    $fk_gross_ids = isset($_POST['fk_gross_id']) ? $_POST['fk_gross_id'] : [];
    $descriptions = isset($_POST['description']) ? $_POST['description'] : [];
    $created_users = isset($_POST['created_user']) ? $_POST['created_user'] : [];
    $statuses = isset($_POST['status']) ? $_POST['status'] : [];

    // Prepare and execute the INSERT statement
    $stmt = pg_prepare($pg_con, "insert_statement", "INSERT INTO llx_micro (fk_gross_id, specimen, description, created_user, status, lab_number) VALUES ($1, $2, $3, $4, $5, $6)");
    
    if (!$stmt) {
        echo "Error preparing statement: " . pg_last_error($pg_con);
        exit();
    }

    for ($i = 0; $i < count($specimens); $i++) {
        $result = pg_execute($pg_con, "insert_statement", array(
            $fk_gross_ids[$i],
            pg_escape_string($specimens[$i]),
            pg_escape_string($descriptions[$i]),
            pg_escape_string($created_users[$i]),
            pg_escape_string($statuses[$i]),
            pg_escape_string($lab_numbers[$i])
        ));

        if (!$result) {
            echo "Error inserting data: " . pg_last_error($pg_con);
            exit();
        }else{
            echo '<script>alert("Data  insert successfully!");</script>';
        }
    }
    
    // Redirect after successful insertion
    header("Location: diagnosis.php?fk_gross_id=" . $fk_gross_ids[0]);
    exit();
} else {
    // Redirect if not a POST request
    header("Location: transcriptionindex.php");
    exit();
}

?>
