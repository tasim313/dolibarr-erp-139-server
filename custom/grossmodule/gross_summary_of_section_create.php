<?php 

include("connection.php");


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fk_gross_id = isset($_POST['fk_gross_id']) ? pg_escape_string($pg_con, $_POST['fk_gross_id']) : '';
    $summary = isset($_POST['summary']) ? pg_escape_string($pg_con, $_POST['summary']) : '';
    $ink_code = isset($_POST['ink_code']) ? pg_escape_string($pg_con, $_POST['ink_code']) : '';
    $sql = "INSERT INTO llx_gross_summary_of_section
            (
            fk_gross_id,
            summary, 
            ink_code
            )
            VALUES (
                '$fk_gross_id',
                '$summary',
                '$ink_code'
                )";

    $result = pg_query($pg_con, $sql);

    if ($result) {
        echo '<script>';
        echo 'alert("Data inserted successfully");';
        echo 'window.location.href = "grossmoduleindex.php";'; 
        echo '</script>';
        exit(); 
    } else {
        echo "Error: " . $sql . "<br>" . pg_last_error($pg_con);
    }
    pg_close($pg_con);
}else{
   header("Location: gross_create.php");
   exit();
}

?>