<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

include("../../connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get data from POST request
    $data = json_decode(file_get_contents('php://input'), true);

    $sourceBatchName = $data['source_batch_name'];
    $targetBatchName = $data['target_batch_name'];
    $sourceRowId = (int)$data['source_rowid'];
    $targetRowId = (int)$data['target_rowid'];
    $swapCount = (int)$data['swap_count'];
    $sourceTotalCount = (int)$data['source_total_count'];
    $targetTotalCount = (int)$data['target_total_count'];

    // Validate input
    if (empty($sourceBatchName) || empty($targetBatchName) || $sourceRowId <= 0 || $targetRowId <= 0 || $swapCount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid input data']);
        exit;
    }

    // Prepare SQL updates
    $updateSourceQuery = "UPDATE llx_batch_cassette_counts SET total_cassettes_count = $1, description = $2 WHERE rowid = $3";
    $updateTargetQuery = "UPDATE llx_batch_cassette_counts SET total_cassettes_count = $1, description = $2 WHERE rowid = $3";

    // Prepare description messages
    $descriptionSource = sprintf(
        "Transferred %d cassettes to batch '%s'",
        $swapCount,
        htmlspecialchars($targetBatchName)
    );
    $descriptionTarget = sprintf(
        "Received %d cassettes from batch '%s'",
        $swapCount,
        htmlspecialchars($sourceBatchName)
    );

    // Execute updates
    $updateSourceResult = pg_query_params($pg_con, $updateSourceQuery, [$sourceTotalCount, $descriptionSource, $sourceRowId]);
    if (!$updateSourceResult) {
        echo json_encode(['success' => false, 'message' => 'Failed to update source batch: ' . pg_last_error($pg_con)]);
        exit;
    }

    $updateTargetResult = pg_query_params($pg_con, $updateTargetQuery, [$targetTotalCount, $descriptionTarget, $targetRowId]);
    if (!$updateTargetResult) {
        echo json_encode(['success' => false, 'message' => 'Failed to update target batch: ' . pg_last_error($pg_con)]);
        exit;
    }

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

?>