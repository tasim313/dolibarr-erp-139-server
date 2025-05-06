<?php
    include('connection.php');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        date_default_timezone_set('Asia/Dhaka');

        $final_ref_comment = trim($_POST['final_ref_comment'] ?? '');
        $final_ref_comment_title = trim($_POST['final_ref_comment_title'] ?? '');
        $final_referring_doctor_name = trim($_POST['final_referring_doctor_name'] ?? '');
        $final_lab_number = trim($_POST['final_LabNumber'] ?? '');

        // Generate title from first 3 words if not provided
        if (empty($final_ref_comment_title)) {
            $words = preg_split('/\s+/', $final_ref_comment);
            $final_title = implode(' ', array_slice($words, 0, 3));
            if (empty($final_title)) {
                $final_title = "Referral for {$final_lab_number}";
            }
        } else {
            $final_title = $final_ref_comment_title;
        }

        // Format: "30 January, 2024 4:30 PM"
        $final_formattedDate = date('j F, Y g:i A');

        // Build comment array with doctor name as key
        $final_commentEntry = [
            $final_referring_doctor_name => $final_ref_comment,
            'date' => $final_formattedDate
        ];
        $final_commentsJson = json_encode([$final_commentEntry]);

        // Insert query
        $final_insertSql = "INSERT INTO llx_doctor_referral_system_records 
                    (title, lab_number, refering_doctor_name, referal_reason, refered_date) 
                    VALUES ($1, $2, $3, $4, $5)";

        $final_insertResult = pg_query_params($pg_con, $final_insertSql, [
            $final_title,
            $final_lab_number,
            $final_referring_doctor_name,
            $final_commentsJson,
            date('Y-m-d H:i:s')
        ]);

        if ($final_insertResult) {
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        } else {
            echo "Error inserting referral: " . pg_last_error($pg_con);
        }
    }
?>