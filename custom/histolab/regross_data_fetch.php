<?php
include('connection.php');
include('histo_common_function.php');
include('../grossmodule/gross_common_function.php');

$data = json_decode(file_get_contents("php://input"), true);

$start_date = $data['start_date'] ?? '';
$end_date = $data['end_date'] ?? '';

if (!$start_date || !$end_date) {
    echo json_encode([]);
    exit;
}

$regross_list = regross_cassettes_list($start_date, $end_date);
echo json_encode($regross_list);