<?php 
    include ("../connection.php");
    include ("../histo_common_function.php");

    global $pg_con;

    // Ensure the request method is POST
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        header('Content-Type: application/json');
        
        $inputData = file_get_contents('php://input');
        $boneStatusData = json_decode($inputData, true);

        error_log('Received data: ' . print_r($boneStatusData, true));

        if ($boneStatusData === null && json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid JSON data.']);
            exit;
        }

        $fkStatusId = 52; // Status ID for 'Bones Ready'
        $insertedValues = []; // Array to hold successfully inserted values

        if (is_array($boneStatusData) && !empty($boneStatusData)) {
            foreach ($boneStatusData as $boneStatus) {
                $labnumber = isset($boneStatus['labnumber']) ? trim($boneStatus['labnumber']) : '';
                $user_id = isset($boneStatus['user_id']) ? pg_escape_string($pg_con, $boneStatus['user_id']) : '';
                $status = isset($boneStatus['status']) ? pg_escape_string($pg_con, $boneStatus['status']) : ''; // Sanitize if used
                $id = isset($boneStatus['id']) ? pg_escape_string($pg_con, $boneStatus['id']) : ''; // Retrieve id

                // Remove 'HPL' prefix if it exists
                $labnumberProcessed = preg_replace('/^HPL/', '', $labnumber);

                if (empty($status) || empty($labnumberProcessed) || empty($id)) {
                    error_log('Skipping empty lab number, status, or id: ' . $labnumberProcessed);
                    continue;
                }

                // Insert query
                $sqlInsert = "INSERT INTO llx_commande_trackws (labno, user_id, fk_status_id) VALUES ($1, $2, $3)";
                
                error_log('Inserting labno: ' . $labnumberProcessed . ' with status: ' . $status);

                $resultInsert = pg_query_params($pg_con, $sqlInsert, [$labnumberProcessed, $user_id, $fkStatusId]);

                if ($resultInsert) {
                    // Add the inserted values to the response array
                    $insertedValues[] = [
                        'labnumber' => $labnumberProcessed,
                        'user_id' => $user_id,
                        'fk_status_id' => $fkStatusId
                    ];
                    
                    // Update query for llx_gross_specimen_section table
                    $sqlUpdate = "UPDATE llx_gross_specimen_section SET boneslide = 'Bones Slide Ready' WHERE gross_specimen_section_id = $1";
                    $resultUpdate = pg_query_params($pg_con, $sqlUpdate, [$id]);

                    if (!$resultUpdate) {
                        error_log('Failed to update llx_gross_specimen_section: ' . pg_last_error($pg_con));
                        echo json_encode(['status' => 'error', 'message' => 'Database error while updating: ' . pg_last_error($pg_con)]);
                        exit;
                    }

                } else {
                    error_log('Failed to insert into llx_commande_trackws: ' . pg_last_error($pg_con));
                    echo json_encode(['status' => 'error', 'message' => 'Database error during insert: ' . pg_last_error($pg_con)]);
                    exit;
                }
            }

            // Return success response with the inserted data
            echo json_encode([
                'status' => 'success', 
                'message' => 'Statuses updated and llx_gross_specimen_section modified successfully!',
                'inserted_data' => $insertedValues // Include the inserted values in the response
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid data received.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    }
?>
