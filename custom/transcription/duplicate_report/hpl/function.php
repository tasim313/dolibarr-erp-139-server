<?php 
include ("../connection.php");


function other_report_patient_information_by_lab($lab_number) {
    global $pg_con;

    $sql = "SELECT rowid, 
                   nom, 
                   code_client, 
                   address, 
                   phone, 
                   fax, 
                   date_of_birth, 
                   sex, 
                   ageyrs, 
                   att_name, 
                   att_relation, 
                   lab_number 
            FROM llx_other_report_patient_information 
            WHERE lab_number = $1";

    // Use pg_query_params to safely pass parameters
    $result = pg_query_params($pg_con, $sql, [$lab_number]);

    $patients = [];

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $patients[] = [
                'rowid' => $row['rowid'],
                'name' => $row['nom'], 
                'patient_code' => $row['code_client'], 
                'address' => $row['address'],
                'phone' => $row['phone'],
                'fax' => $row['fax'],
                'date_of_birth' => $row['date_of_birth'],
                'Gender' => $row['sex'],
                'Age' => $row['ageyrs'],
                'att_name' => $row['att_name'],
                'att_relation' => $row['att_relation']
            ];
        }

        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $patients;
}


function clinicalInformation($lab_number) {
    global $pg_con;

    // SQL query to fetch the required data
    $sql = "
        SELECT clinical_details FROM llx_clinical_details WHERE lab_number = $1";

    // Prepare the SQL query
    $stmt_name = "get_clinical_information";
    $prepare_result = pg_prepare($pg_con, $stmt_name, $sql);

    // Check if the preparation was successful
    if (!$prepare_result) {
        return 'Error in query preparation: ' . pg_last_error($pg_con);
    }

    // Execute the prepared query
    $result = pg_execute($pg_con, $stmt_name, array($lab_number));

    // Check if the query execution was successful
    if ($result) {
        // Fetch the first row of the result
        $row = pg_fetch_assoc($result);

        // Free the result resource
        pg_free_result($result);

        // Return the fetched row
        return $row;
    } else {
        return 'Error in query execution: ' . pg_last_error($pg_con);
    }
}

function other_report_clinicalInformation($lab_number) {
    global $pg_con;

    // SQL query to fetch the required data
    $sql = "
        SELECT clinical_details, addressing FROM llx_other_report_clinical_details WHERE lab_number = $1";

    // Prepare the SQL query
    $stmt_name = "get_other_report_clinical_information";
    $prepare_result = pg_prepare($pg_con, $stmt_name, $sql);

    // Check if the preparation was successful
    if (!$prepare_result) {
        return 'Error in query preparation: ' . pg_last_error($pg_con);
    }

    // Execute the prepared query
    $result = pg_execute($pg_con, $stmt_name, array($lab_number));

    // Check if the query execution was successful
    if ($result) {
        // Fetch the first row of the result
        $row = pg_fetch_assoc($result);

        // Free the result resource
        pg_free_result($result);

        // Return the fetched row
        return $row;
    } else {
        return 'Error in query execution: ' . pg_last_error($pg_con);
    }
}



function other_report_site_of_specimen($lab_number) {
    global $pg_con;

    $sql = "
        SELECT rowid AS specimen_rowid, site_of_specimen AS specimen 
        FROM llx_other_report_site_of_specimen 
        WHERE lab_number = $1 ORDER BY rowid ASC";

    // Use pg_query_params to prevent SQL injection and fix the parameter error
    $result = pg_query_params($pg_con, $sql, [$lab_number]);

    $specimens = [];

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $specimens[] = [
                'specimen' => $row['specimen'],
                'specimen_rowid' => $row['specimen_rowid']
            ];
        }

        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $specimens;
}


function other_report_gross_specimen_description($lab_number) {
    global $pg_con;

    // Query to fetch specimen details based on lab number
    $sql = "SELECT specimen_id, specimen, gross_description 
            FROM llx_other_report_gross_specimen 
            WHERE lab_number = $1 
            ORDER BY specimen_id ASC";

    // Execute query safely using parameters
    $result = pg_query_params($pg_con, $sql, array($lab_number));

    $specimens = [];

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $specimens[] = $row;
        }
        pg_free_result($result);
    } else {
        error_log('Error: ' . pg_last_error($pg_con)); // Logs error instead of printing
    }

    return $specimens; // Returns empty array if no data or query fails
}

function other_report_gross_specimen_section($lab_number) {
    global $pg_con;
    $sql = "select rowid, gross_specimen_section_id, fk_gross_id, 
            section_code, specimen_section_description, cassettes_numbers,tissue,
            bone, re_gross, requires_slide_for_block, decalcified_bone, lab_number
            from llx_other_report_gross_specimen_section WHERE TRIM(lab_number) = $1 ORDER BY 
            LEFT(section_code, 1) ASC, 
            CAST(SUBSTRING(section_code, 2) AS INTEGER) ASC, 
            gross_specimen_section_id ASC";
    $result = pg_query_params($pg_con, $sql, array($lab_number));

    $specimens = array();

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $specimens[] = $row;
        }
        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $specimens;
}


function other_report_gross_summary_of_section($lab_number) {
    global $pg_con;
    $sql = "SELECT gross_summary_id, fk_gross_id, summary, ink_code 
            FROM llx_other_report_gross_summary_of_section 
            WHERE lab_number = $1 
            ORDER BY gross_summary_id ASC;";
    
    $result = pg_query_params($pg_con, $sql, array($lab_number));

    $specimens = array();

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $specimens[] = $row;
        }
        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $specimens;
}


function other_report_ExistingMicroDescriptions($labNumber) {
    global $pg_con;

    $existingMicroDescriptions = array();

    $sql = "SELECT rowid, lab_number, fk_gross_id, description, 
    specimen FROM llx_other_report_micro WHERE lab_number = '$labNumber' ORDER BY rowid ASC";
    $result = pg_query($pg_con, $sql);

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $existingMicroDescriptions[] = array(
                'row_id' => $row['rowid'],
                'lab_number' => $row['lab_number'],
                'fk_gross_id' => $row['fk_gross_id'],
                'description' => $row['description'],
                'specimen' => $row['specimen']
            );
        }
        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $existingMicroDescriptions;
}


function other_report_ExistingDiagnosisDescriptions($labNumber) {
    global $pg_con;

    $existingDiagnosisDescriptions = array();

    $sql = "SELECT rowid,lab_number,fk_gross_id, description, specimen, title, comment FROM llx_other_report_diagnosis WHERE lab_number = '$labNumber' ORDER BY rowid ASC";
    $result = pg_query($pg_con, $sql);

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $existingDiagnosisDescriptions[] = array(
                'row_id' => $row['rowid'],
                'lab_number' => $row['lab_number'],
                'fk_gross_id' => $row['fk_gross_id'],
                'description' => $row['description'],
                'title' => $row['title'],
                'comment' => $row['comment'],
                'specimen' => $row['specimen']
            );
        }
        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $existingDiagnosisDescriptions;
}

?>