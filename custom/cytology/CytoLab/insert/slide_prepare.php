<?php 
include("../connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Fetch data from the form submission
    $lab_number = $_POST['lab_number'];
    $created_user = isset($_POST['created_user']) ? $_POST['created_user'] : '';

    // Prepare the SQL query for insertion
    $query = "
        INSERT INTO llx_cyto_slide_prepared (lab_number, created_user) 
        VALUES ($1, $2)
    ";

    // Execute the query with parameters
    $result = pg_query_params(
        $pg_con, 
        $query, 
        [$lab_number, $created_user]
    );

    // Check the result
    if ($result) {
        // Display message for 4 seconds, then redirect
        echo "<script>
                window.onload = function() {
                    var messageDiv = document.createElement('div');
                    messageDiv.innerHTML = 'Lab Number " . htmlspecialchars($lab_number) . " inserted successfully.';
                    messageDiv.style.position = 'fixed';
                    messageDiv.style.top = '50%';
                    messageDiv.style.left = '50%';
                    messageDiv.style.transform = 'translate(-50%, -50%)';
                    messageDiv.style.padding = '15px';
                    messageDiv.style.background = 'green';
                    messageDiv.style.color = 'white';
                    messageDiv.style.fontSize = '18px';
                    messageDiv.style.borderRadius = '5px';
                    messageDiv.style.zIndex = '9999';
                    document.body.appendChild(messageDiv);

                    setTimeout(function() {
                        messageDiv.remove(); // Remove message before redirecting
                        window.location.href = '" . $_SERVER['HTTP_REFERER'] . "';
                    }, 2000);
                };
              </script>";
        exit;
    } else {
        // Display error message in case of failure
        echo "Error: " . pg_last_error($pg_con);
    }
}

?>