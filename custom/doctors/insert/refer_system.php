<?php
    include('connection.php');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        date_default_timezone_set('Asia/Dhaka');

        $ref_comment = trim($_POST['ref_comment'] ?? '');
        $ref_comment_title = trim($_POST['ref_comment_title'] ?? '');
        $referring_doctor_name = trim($_POST['referring_doctor_name'] ?? '');
        $lab_number = trim($_POST['LabNumber'] ?? '');

        // Generate title from first 3 words if not provided
        if (empty($ref_comment_title)) {
            $words = preg_split('/\s+/', $ref_comment);
            $title = implode(' ', array_slice($words, 0, 3));
            if (empty($title)) {
                $title = "Referral for {$lab_number}";
            }
        } else {
            $title = $ref_comment_title;
        }

        // Format: "30 January, 2024 4:30 PM"
        $formattedDate = date('j F, Y g:i A');

        // Build comment array with doctor name as key
        $commentEntry = [
            $referring_doctor_name => $ref_comment,
            'date' => $formattedDate
        ];
        $commentsJson = json_encode([$commentEntry]);

        // Insert query
        $insertSql = "INSERT INTO llx_doctor_referral_system_records 
                    (title, lab_number, refering_doctor_name, referal_reason, refered_date) 
                    VALUES ($1, $2, $3, $4, $5)";

        $insertResult = pg_query_params($pg_con, $insertSql, [
            $title,
            $lab_number,
            $referring_doctor_name,
            $commentsJson,
            date('Y-m-d H:i:s')
        ]);

        if ($insertResult) {
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        } else {
            echo "Error inserting referral: " . pg_last_error($pg_con);
        }
    }
    
?>