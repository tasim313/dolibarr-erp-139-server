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

  // Prepare update statement (excluding lab_number update)
  $stmt = pg_prepare($pg_con, "update_statement", "UPDATE llx_micro SET fk_gross_id = $1, specimen = $2, description = $3, created_user = $4, status = $5 WHERE lab_number = $6");

  if (!$stmt) {
    echo "Error preparing statement: " . pg_last_error($pg_con);
    exit();
  }

  for ($i = 0; $i < count($specimens); $i++) {
    $result = pg_execute($pg_con, "update_statement", array(
      $fk_gross_ids[$i],
      pg_escape_string($specimens[$i]),
      pg_escape_string($descriptions[$i]),
      pg_escape_string($created_users[$i]),
      pg_escape_string($statuses[$i]),
      $lab_numbers[$i] // Original lab number (for where clause)
    ));

    if (!$result) {
      echo "Error updating data: " . pg_last_error($pg_con);
      exit();
    } else {
      echo '<script>alert("Data updated successfully!");</script>';
    }
  }

  // Redirect after successful update
  header("Location:hpl_transcription_list.php?lab_number=" . $lab_numbers[0]);
  exit();
} else {
  // Redirect if not a POST request
  header("Location: transcriptionindex.php");
  exit();
}

?>
