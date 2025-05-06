<?php
include('connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comment = trim($_POST['comment'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $rowid = trim($_POST['rowid'] ?? '');
    $username = trim($_POST['username'] ?? '');

    if ($comment && $title && $rowid && $username) {
        // Fetch existing data
        $result = pg_query_params($pg_con, "SELECT referal_reason FROM llx_doctor_referral_system_records WHERE rowid = $1", [$rowid]);
        $row = pg_fetch_assoc($result);
        $existing = json_decode($row['referal_reason'], true) ?? [];

        // Ensure it's a plain array
        if (!is_array($existing) || array_keys($existing) !== range(0, count($existing) - 1)) {
            $existing = []; // Reset to empty if it was stored in key-value style
        }

        $formattedDate = date('j F, Y g:i A');

        // Append new comment
        $existing[] = [
            $username  => $comment,
            'date' => $formattedDate
        ];

        // Save
        $updated_json = json_encode($existing);
        $update = pg_query_params($pg_con, "UPDATE llx_doctor_referral_system_records SET referal_reason = $1 WHERE rowid = $2", [$updated_json, $rowid]);

        echo $update ? "Comment saved." : "Error saving comment.";
    } else {
        echo "Missing data.";
    }
}
?>

