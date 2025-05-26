<?php
include('connection.php');
include('common_function.php');
include('../grossmodule/gross_common_function.php');
include('preliminary_report/preliminary_report_function.php');

if (isset($_POST['lab_number'])) {
    $input = trim($_POST['lab_number']);
    $lab_number = 'HPL' . $input;

    $transcript_list = get_done_transcript_list($lab_number);

    if (!empty($transcript_list)) {
        echo json_encode([
            'status' => 'transcript',
            'data' => $transcript_list
        ]);
        exit;
    }

    $gross_list = get_done_gross_list();

    foreach ($gross_list as $item) {
        if ($item['lab_number'] === $lab_number) {
            echo json_encode([
                'status' => 'gross',
                'data' => $item
            ]);
            exit;
        }
    }

    echo json_encode([
        'status' => 'not_found'
    ]);
}
