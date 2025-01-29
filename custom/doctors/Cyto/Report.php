<?php

// Include the main TCPDF library
require_once('TCPDF/tcpdf.php');

// Define database connection parameters
$host = "postgres";
$user = "root";
$password = "root";
$db_name = "dolibarr";

// Establish a database connection
$pg_conn_string = "host=$host dbname=$db_name user=$user password=$password";
$pg_con = pg_connect($pg_conn_string);

// Check if the database connection is successful
if (!$pg_con) {
    die("Failed to connect with PostgreSQL: " . pg_last_error());
}

// Retrieve the LabNumber from the GET request
$LabNumber = isset($_GET['LabNumber']) ? $_GET['LabNumber'] : '';
$LabNumberWithoutPrefix = str_replace(["FNA", "-FNA"], "", $LabNumber);
$lab_number_table = str_replace("-", "", $LabNumberWithoutPrefix);
$lab_number = $LabNumberWithoutPrefix;
$username = isset($_GET['username']) ? $_GET['username'] : '';
// Prepare the SQL query for dynamic data
$invoice_number = "SELECT f.ref AS invoice
                        FROM llx_facture AS f
                    INNER JOIN llx_element_element AS ee ON ee.fk_target = f.rowid AND ee.sourcetype = 'commande' AND ee.targettype = 'facture'
                    INNER JOIN llx_commande AS c ON ee.fk_source = c.rowid
                    WHERE c.ref = '$LabNumberWithoutPrefix'";

// Execute the query
$invoice_result = pg_query($pg_con, $invoice_number);

// Check if the query was successful
if ($invoice_result) {
    // Initialize a variable to hold the invoice number
    $invoice_value = null;

    // Fetch the results (if any)
    while ($row = pg_fetch_assoc($invoice_result)) {
        // Process each row as needed
        $invoice = $row['invoice'];
        // Extract the invoice number and remove "SI" prefix 
        $invoice_value = str_replace(['SI'], '', $invoice);
        $invoice_value_table = str_replace(['SI', '-'], '', $invoice);
        
        // Store the invoice number in a session variable for later use
        $_SESSION['invoice_value'] = $invoice_value;
    }

} else {
    // Handle query error
    die("Query failed: " . pg_last_error($pg_con));
}

// sql opertaion for dynamic data 
$patient_information = "SELECT s.rowid AS rowid,
    s.nom AS nom,
    s.code_client AS code_client,
    s.address AS address,
    s.phone AS phone,
    s.fax AS fax,
    e.date_of_birth,
    e.sex,
    e.ageyrs,
    e.att_name,
    e.att_relation
    FROM llx_commande AS c
    JOIN llx_societe AS s ON c.fk_soc = s.rowid
    JOIN llx_societe_extrafields AS e ON s.rowid = e.fk_object
    WHERE ref = '$LabNumberWithoutPrefix'";

$patient_information_result = pg_query($pg_con, $patient_information);

$patient_data = [];

// Check if the query was successful
if ($patient_information_result) {
    // Fetch the results (if any)
    while ($row = pg_fetch_assoc($patient_information_result)) {
        // Process each row as needed
        $nom= $row['nom'];
        $code_client = $row['code_client'];
        $address = $row['address'];
        $phone = $row['phone'];
        $fax = $row['fax'];
        $date_of_birth = $row['date_of_birth'];
        $sex = $row['sex'];
        $ageyrs = $row['ageyrs'];
        $att_name = $row['att_name'];
        $att_relation = $row['att_relation'];
        // Store the patient information in a session variable for later use
        $_SESSION['nom'] = $nom;
        $_SESSION['code_client'] = $code_client;
        $_SESSION['address'] = $address;
        $_SESSION['phone'] = $phone;
        $_SESSION['fax'] = $fax;
        $_SESSION['date_of_birth'] = $date_of_birth;
        $_SESSION['sex'] = $sex ;
        $_SESSION['ageyrs'] = $ageyrs;
        $_SESSION['att_name'] = $att_name;
        $_SESSION['att_relation'] = $att_relation;
    }
} else {
    // Handle query error
    die("Query failed for Patient Information: " . pg_last_error());
}

// Create a custom PDF class that extends TCPDF
class CustomPDF extends TCPDF {

    protected $isLastPage = false; // Flag to check if it's the last page

    // Define properties for lab number, invoice value
    protected $lab_number;
    protected $invoice_value;
    protected $code_client;
    protected $username;
    
    // Constructor to accept and set the lab number, invoice value, and patient data
    public function __construct($lab_number, $invoice_value, $code_client, $username, $pg_con) {
        // Call the parent constructor
        parent::__construct();

        // Set the lab number and invoice value
        $this->lab_number = $lab_number;
        $this->invoice_value = $invoice_value;
        $this->code_client = $code_client;
        $this->username = $username; 
    }

    // Override the Header method to remove the underline and shadow effect
        public function Header() {
            // Increase the top margin to avoid overlapping or removing the header when printed
            $this->SetY(48); // Move the starting point of the header to 40 units from the top of the page
        
            // Set the font for the header
            $this->SetFont('Times', 'B', 12); // Bold Times, size 12
        
            // Set margins (optional, if you want to adjust the left and right margins)
            $this->SetMargins(50, 55, 50); // Set left, top, and right margins (increased top margin to 40 units)
        
            // Define barcode style
            $barcodeStyle = [
                'border' => 0, // No borders to avoid shadow effect
                'padding' => 0, 
                'fgcolor' => [0, 0, 0],
                'bgcolor' => false, // No background color to avoid fill shadows
            ];
        
            // Ensure lab number is formatted correctly for barcode
            $labBarcodeData = $this->lab_number;
            $invoiceBarcodeData = $this->invoice_value;
        
            // Create a DataMatrix barcode for the lab number
            $this->write2DBarcode($labBarcodeData, 'DATAMATRIX', '', '', 5, 5, $barcodeStyle, 'N'); // Adjust the size to 20x20mm
            $this->Ln(-24); // Adjust line spacing (positive value to move down)
        
            // Adjust Y position for 'CYTOLOGY REPORT' relative to the barcode size
            $barcodeHeight = 20; // Height of the barcode (same as above)
            $this->SetY($this->GetY() + $barcodeHeight - 5); // Set the Y position below the barcode
        
            // Set X position for 'CYTOLOGY REPORT' and center-align the text
            $this->SetX($this->getPageWidth() - 120); 
            $this->Cell(40, 8, '', 0, 1, 'C', false);
            $this->Ln(-3.5); // Adjust line spacing as needed
        
            // Move the cursor to the right side of the page for the invoice number barcode
            $this->SetX($this->getPageWidth() - 20); // Adjust the value to position the barcode correctly
        
            // Create a DataMatrix barcode for the invoice number
            $this->write2DBarcode($invoiceBarcodeData, 'DATAMATRIX', '', '', 20, 20, $barcodeStyle, 'N');
        }
    

        // Override the Footer method to remove the underline
    
        public function Footer() {
            // Position at 15 mm from the bottom
            $this->SetY(-20);

            // Draw a horizontal line
            $this->SetLineWidth(0.5); // Set the line width
            $this->Line($this->lMargin, $this->GetY(), $this->getPageWidth() - $this->rMargin, $this->GetY()); // Draw line
        
            // Set font for footer content
            $this->SetFont('Times', '', 8);
        
            // Check if it's the last page
            if ($this->isLastPage) {
                $currentDateTime = date('Y-m-d H:i:s');
                
                // Get the page number information and add 'End Of Report' for the last page
                $currentPage = $this->getAliasNumPage();
                $totalPages = $this->getAliasNbPages();
                $pageNumberContent = 'Page ' . $currentPage . ' of ' . $totalPages . ' - End Of Report';
                
                // Footer content
                $leftFooterContent = 'Date: ' . $currentDateTime . ' | ' . htmlspecialchars($this->username);
                $leftWidth = $this->GetStringWidth($leftFooterContent);
                $rightWidth = $this->GetStringWidth($pageNumberContent);
                $totalWidth = $this->w - $this->lMargin - $this->rMargin;
                $qrCodeSize = 12;
                $spacing = ($totalWidth - $leftWidth - $rightWidth - $qrCodeSize) / 2;
        
                // Add the left footer content (date)
                $this->Cell($leftWidth, 5, $leftFooterContent, 0, 0, 'L');
                $this->Cell($spacing, 5, '', 0, 0);
        
                // QR code in the footer
                $style = array(
                    'border' => 0,
                    'padding' => 0,
                    'fgcolor' => array(0, 0, 0),
                    'bgcolor' => false
                );
                $formattedLabNumber = substr($this->lab_number, 0, 4) . '-' . substr($this->lab_number, 4);
                $formattedInvoiceNumber = substr($this->invoice_value, 0, 4) . '-' . substr($this->invoice_value, 4);
                $qrCodeData = "Patient Code: {$this->code_client} | Lab Number: {$formattedLabNumber} | Invoice Number: {$formattedInvoiceNumber}";
                // Ensure that you are not exceeding the character limit or including problematic characters
                $qrCodeData = htmlspecialchars($qrCodeData); // Encode special characters if necessary 
                $centerX = $this->GetX();
                $this->write2DBarcode($qrCodeData, 'QRCODE,Q', $centerX, $this->GetY(), $qrCodeSize, $qrCodeSize, $style, 'N');
                $this->SetX($centerX + $qrCodeSize + $spacing);
        
                // Add the right footer content (page number with "End Of Report")
                $this->SetY($this->GetY() - 11.5);
                $this->SetX($this->getPageWidth() - 50);
                $this->Cell($rightWidth, 5, $pageNumberContent, 0, 1, 'R');
            } else {
                // Standard footer for other pages (add "Continued on next page")
                $currentDateTime = date('Y-m-d H:i:s');
                $currentPage = $this->getAliasNumPage();
                $totalPages = $this->getAliasNbPages();
                $pageNumberContent = 'Page ' . $currentPage . ' of ' . $totalPages . ' - Continued on next page';
                
                $leftFooterContent = 'Date: ' . $currentDateTime . ' | ' . htmlspecialchars($this->username);
                $leftWidth = $this->GetStringWidth($leftFooterContent);
                $rightWidth = $this->GetStringWidth($pageNumberContent);
                $totalWidth = $this->w - $this->lMargin - $this->rMargin;
                $qrCodeSize = 12;
                $spacing = ($totalWidth - $leftWidth - $rightWidth - $qrCodeSize) / 2;

                // Add the left footer content (date)
                $this->Cell($leftWidth, 5, $leftFooterContent, 0, 0, 'L');
                $this->Cell($spacing, 5, '', 0, 0);

                // QR code in the footer
                $style = array(
                    'border' => 0,
                    'padding' => 0,
                    'fgcolor' => array(0, 0, 0),
                    'bgcolor' => false
                );
                $formattedLabNumber = substr($this->lab_number, 0, 4) . '-' . substr($this->lab_number, 4);
                $formattedInvoiceNumber = substr($this->invoice_value, 0, 4) . '-' . substr($this->invoice_value, 4);
                $qrCodeData = "Patient Code: {$this->code_client} | Lab Number: {$formattedLabNumber} | Invoice Number: {$formattedInvoiceNumber}";
                // Ensure that you are not exceeding the character limit or including problematic characters
                $qrCodeData = htmlspecialchars($qrCodeData); // Encode special characters if necessary
                $centerX = $this->GetX();
                $this->write2DBarcode($qrCodeData, 'QRCODE,Q', $centerX, $this->GetY(), $qrCodeSize, $qrCodeSize, $style, 'N');
                $this->SetX($centerX + $qrCodeSize + $spacing);

                // Add the right footer content (page number with "Continued on next page")
                $this->SetY($this->GetY() - 11.5);
                $this->SetX($this->getPageWidth() - 50);
                $this->Cell($rightWidth, 5, $pageNumberContent, 0, 1, 'R');
            }
        }
    
        // Set the flag to indicate it's the last page
        public function setIsLastPage($isLastPage) {
            $this->isLastPage = $isLastPage;
        }
}

// Create new PDF document using the custom class
$pdf = new CustomPDF($lab_number, $invoice_value, $code_client,$username, true, 'UTF-8', false);

// $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// Set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// Set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, 45, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetAuthor($username); // Replace with the author's name
$pdf->SetTitle($LabNumber);
$pdf->SetSubject('CYTOLOGY REPORT');
$pdf->SetCreator($username);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// Set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// Set font
$pdf->SetFont('Times', '', 11);

// Add a page
$pdf->AddPage();

// Set margins (left, top, right)
$pdf->SetMargins(PDF_MARGIN_LEFT, 45, PDF_MARGIN_RIGHT); // Set top margin to 20px


$date_information = "SELECT 
    c.date_commande AS date, 
    c.date_livraison AS delivery_date, 
    e.referredfrom AS refd_hospital, 
    CASE 
        WHEN COALESCE(e.referred_by_dr_text, '') = '' THEN CONCAT(e.referredby_dr, ' ', e.referred_by_dr_text) 
        ELSE e.referred_by_dr_text 
    END AS refd_doctor 
    FROM 
    llx_commande AS c
    JOIN 
    llx_commande_extrafields AS e ON e.fk_object = c.rowid
    WHERE 
    ref = '$LabNumberWithoutPrefix';";

$date_information_result = pg_query($pg_con, $date_information);
// Check if the query was successful
if ($date_information_result) {
    // Fetch the results (if any)
    while ($row = pg_fetch_assoc($date_information_result)) {
        // Process each row as needed
        $date= $row['date'];
        $delivery_date = $row['delivery_date'];
        $delivery_date_12h = date("Y-m-d h:i A", strtotime($delivery_date));
        $refd_hospital = $row['refd_hospital'];
        $refd_doctor = $row['refd_doctor'];
        
        // Store the patient information in a session variable for later use
        $_SESSION['date'] = $date;
        $_SESSION['delivery_date_12h'] = $delivery_date_12h ;
        $_SESSION['refd_hospital'] = $refd_hospital;
        $_SESSION['refd_doctor'] = $refd_doctor;
    }
} else {
    // Handle query error
    die("Query failed for Date Information: " . pg_last_error());
}
// End of Sql opertaion


// Initialize a variable to hold the gender string
$gender = '';

// Determine the gender based on the value of the 'sex' column
if ($sex == 1) {
    $gender = 'Male';
} elseif ($sex == 2) {
    $gender = 'Female';
} else {
    $gender = 'Other';
}


$referred_from_information = "SELECT lastname
FROM llx_socpeople
WHERE rowid IN (
    SELECT fk_socpeople
    FROM llx_categorie_contact
    WHERE fk_categorie = 4
)
AND rowid = '$refd_hospital';";

$referred_from_information_result = pg_query($pg_con, $referred_from_information);
// Check if the query was successful
if ($referred_from_information_result) {
    // Fetch the results (if any)
    while ($row = pg_fetch_assoc($referred_from_information_result)) {
        $lastname = $row['lastname'];
        // Store the patient information in a session variable for later use
        $_SESSION['lastname'] = $lastname;
    }
} else {
    // Handle query error
    die("Query failed for Date Information: " . pg_last_error());
}
// End of Sql opertaion
// current data time
$finialized_time = "SELECT TO_CHAR(create_time, 'YYYY-MM-DD') AS formatted_time
                   FROM llx_commande_trackws 
                   WHERE fk_status_id = '10' 
                   AND labno = '$LabNumberWithoutPrefix'";
$finialized_time_result = pg_query($pg_con, $finialized_time);

if ($finialized_time_result) {
    $row = pg_fetch_assoc($finialized_time_result);
    $formatted_time = $row['formatted_time']; // Get the formatted time value
} else {
    $formatted_time = ''; // Handle cases where there's no result
}

$currentDateTime = date('Y-m-d g:i:s A');
// Define the content of the PDF document

// Patient Table
$htmlContent = '
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid black;
            border: none;
            padding: 2px;
            text-align: left;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
    </style>
    <hr>
    <table>
        <tr>
            <td><strong>Lab : </strong><span>11'.$lab_number_table.' </span></td>
            <td><strong>Invoice: </strong><span>011'.$invoice_value_table.'</span></td>
        </tr>
        <tr>
            <td><strong>Patient Name: </strong><span>'.$nom.' </span></td>
            <td><strong>Date Of Birth: </strong><span> '.$date_of_birth.'</span></td>
        </tr>
        <tr>
            <td><strong>Age: </strong><span> '.$ageyrs.'</span></td>
            <td><strong>Gender: </strong><span>' . $gender . '</span></td>
        </tr>
        <tr>
            <td><strong>Refd. by: </strong><span>'.$refd_doctor.'</span></td>
            <td><strong>Refd. From: </strong><span>'.$lastname.'</span></td>
        </tr>
        <tr>
            <td><strong>Received on: </strong><span>'.$date.'</span></td>
            <td><strong>Reported on: </strong><span>'.htmlspecialchars($formatted_time).'</span></td>
        </tr>
        <tr>
        
        </tr>
    </table> 
    <hr>
';

// Write HTML content to PDF
$pdf->writeHTML($htmlContent, true, false, true, false, '');


// SQL operation for dynamic data 
$fk_cyto_id = "SELECT rowid FROM llx_cyto WHERE lab_number = '$LabNumber'";
$fk_cyto_id_result = pg_query($pg_con, $fk_cyto_id);

// Check if the query was successful
if ($fk_cyto_id_result) {
    // Fetch the results (if any)
    while ($row = pg_fetch_assoc($fk_cyto_id_result)) {
        // Process each row as needed
        $fk_cyto_id = $row['rowid'];
        // Store the patient information in a session variable for later use
        $_SESSION['fk_cyto_id'] = $fk_cyto_id;
    }
} else {
    // Handle query error
    die("Query failed for gross ID Information: " . pg_last_error());
}

// Prepare clinical details
$clinical_details_info = "SELECT chief_complain AS clinical_details FROM llx_cyto_clinical_information WHERE cyto_id = '$fk_cyto_id'";
$clinical_details_result = pg_query($pg_con, $clinical_details_info);

if (!$clinical_details_result) {
    die("Query failed for clinical_details: " . pg_last_error());
}

// SQL operation for clinical history
$clinical_history_query = "select relevant_clinical_history from llx_cyto_clinical_information where cyto_id = '$fk_cyto_id' AND relevant_clinical_history != '<p><br></p>' 
AND relevant_clinical_history != '<br>' 
AND relevant_clinical_history != '<p>&nbsp;</p>' 
AND NOT relevant_clinical_history ~ '^<p\s*[^>]*>\s*</p>$'";
$clinical_history_result = pg_query($pg_con, $clinical_history_query);

if (!$clinical_history_result) {
    die("Error in SQL query for clinical historye : " . pg_last_error());
}

// SQL operation for on examination
$on_examination_query = "select on_examination from llx_cyto_clinical_information where cyto_id = '$fk_cyto_id' AND on_examination != '<p><br></p>' 
AND on_examination != '<br>' 
AND on_examination != '<p>&nbsp;</p>' 
AND NOT on_examination ~ '^<p\s*[^>]*>\s*</p>$'";
$on_examination_result = pg_query($pg_con, $on_examination_query);

if (!$on_examination_result) {
    die("Error in SQL query for on examination : " . pg_last_error());
}

// SQL operation for on examination
$on_examination_query = "select on_examination from llx_cyto_clinical_information where cyto_id = '$fk_cyto_id' AND on_examination != '<p><br></p>' 
AND on_examination != '<br>' 
AND on_examination != '<p>&nbsp;</p>' 
AND NOT on_examination ~ '^<p\s*[^>]*>\s*</p>$'";
$on_examination_result = pg_query($pg_con, $on_examination_query);

if (!$on_examination_result) {
    die("Error in SQL query for on examination : " . pg_last_error());
}


// SQL operation for clinical impression
$clinical_impression_query = "select clinical_impression from llx_cyto_clinical_information where cyto_id = '$fk_cyto_id' AND clinical_impression != '<p><br></p>' 
AND clinical_impression != '<br>' 
AND clinical_impression != '<p>&nbsp;</p>' 
AND NOT clinical_impression ~ '^<p\s*[^>]*>\s*</p>$'";
$clinical_impression_result = pg_query($pg_con, $clinical_impression_query);

if (!$clinical_impression_result) {
    die("Error in SQL query for clinical impression : " . pg_last_error());
}


$aspiration_materials_query = "select aspiration_materials from llx_cyto_fixation_details where cyto_id = '$fk_cyto_id' AND aspiration_materials != '<p><br></p>' 
AND aspiration_materials != '<br>' 
AND aspiration_materials != '<p>&nbsp;</p>' 
AND NOT aspiration_materials ~ '^<p\s*[^>]*>\s*</p>$' LIMIT 1";

$aspiration_materials_result = pg_query($pg_con, $aspiration_materials_query);

if (!$aspiration_materials_query){
    die("Error in SQL query for aspiration materails : " . preg_last_error());
}

$location_query = "select location from llx_cyto_fixation_details where cyto_id = '$fk_cyto_id' AND location != '<p><br></p>' 
AND location != '<br>' 
AND location != '<p>&nbsp;</p>' 
AND NOT location ~ '^<p\s*[^>]*>\s*</p>$'";

$location_result = pg_query($pg_con, $location_query);

if (!$location_query){
    die("Error in SQL query for location : " . preg_last_error());
}


$slide_number_query = "select slide_number, dry from llx_cyto_fixation_details where cyto_id = '$fk_cyto_id' AND slide_number != '<p><br></p>' 
AND slide_number != '<br>' 
AND slide_number != '<p>&nbsp;</p>' 
AND NOT slide_number ~ '^<p\s*[^>]*>\s*</p>$'";

$slide_number_result = pg_query($pg_con, $slide_number_query);

if (!$slide_number_query){
    die("Error in SQL query for slide number : " . preg_last_error());
}


$special_instructions_query = "select special_instructions from llx_cyto_fixation_details where cyto_id = '$fk_cyto_id' AND special_instructions != '<p><br></p>' 
AND special_instructions != '<br>' 
AND special_instructions != '<p>&nbsp;</p>' 
AND NOT special_instructions ~ '^<p\s*[^>]*>\s*</p>$'";

$special_instructions_result = pg_query($pg_con, $special_instructions_query);

if (!$special_instructions_query){
    die("Error in SQL query for special instructions : " . preg_last_error());
}

// Query to get recall_fk_cyto_id
$recall_fk_cyto_id_query = "SELECT rowid FROM llx_cyto_recall WHERE lab_number = '$LabNumberWithoutPrefix'";
$recall_fk_cyto_id_result = pg_query($pg_con, $recall_fk_cyto_id_query);

// Check if the query was successful
if ($recall_fk_cyto_id_result) {
    // Fetch the results (if any)
    while ($row = pg_fetch_assoc($recall_fk_cyto_id_result)) {
        // Process each row as needed
        $recall_fk_cyto_id = $row['rowid'];
        // Store the patient information in a session variable for later use
        $_SESSION['recall_fk_cyto_id'] = $recall_fk_cyto_id;
    }
} else {
    // Handle query error
    die("Query failed for recall ID Information: " . pg_last_error());
}

if (!$recall_fk_cyto_id_result) {
    die("Error in SQL query for recall_fk_cyto_id: " . pg_last_error($pg_con));
}


// recall history
$additional_clinical_history_query = "select additional_relevant_clinical_history from llx_cyto_recall_clinical_information where cyto_id = '$recall_fk_cyto_id' AND additional_relevant_clinical_history != '<p><br></p>' 
AND additional_relevant_clinical_history != '<br>' 
AND additional_relevant_clinical_history != '<p>&nbsp;</p>' 
AND NOT additional_relevant_clinical_history ~ '^<p\s*[^>]*>\s*</p>$'";
$additional_clinical_history_result = pg_query($pg_con, $additional_clinical_history_query);

if (!$additional_clinical_history_result) {
    die("Error in SQL query for additional clinical historye : " . pg_last_error());
}

// SQL operation for recall on examination
$additional_on_examination_query = "select additional_findings_on_examination from llx_cyto_recall_clinical_information where cyto_id = '$recall_fk_cyto_id' AND additional_findings_on_examination != '<p><br></p>' 
AND additional_findings_on_examination != '<br>' 
AND additional_findings_on_examination != '<p>&nbsp;</p>' 
AND NOT additional_findings_on_examination ~ '^<p\s*[^>]*>\s*</p>$'";
$additional_on_examination_result = pg_query($pg_con, $additional_on_examination_query);

if (!$additional_on_examination_result) {
    die("Error in SQL query for on examination : " . pg_last_error());
}

// SQL operation for recall on examination
$additional_clinical_impression_query = "select additional_clinical_impression from llx_cyto_recall_clinical_information where cyto_id = '$recall_fk_cyto_id' AND additional_clinical_impression != '<p><br></p>' 
AND additional_clinical_impression != '<br>' 
AND additional_clinical_impression != '<p>&nbsp;</p>' 
AND NOT additional_clinical_impression ~ '^<p\s*[^>]*>\s*</p>$'";
$additional_clinical_impression_result = pg_query($pg_con, $additional_clinical_impression_query);

if (!$additional_clinical_impression_result) {
    die("Error in SQL query for on additional clinical impression : " . pg_last_error());
}

$additional_aspiration_materials_query = "select aspiration_materials from llx_cyto_recall_fixation_details where cyto_id = '$recall_fk_cyto_id' AND aspiration_materials != '<p><br></p>' 
AND aspiration_materials != '<br>' 
AND aspiration_materials != '<p>&nbsp;</p>' 
AND NOT aspiration_materials ~ '^<p\s*[^>]*>\s*</p>$'";
$additional_aspiration_materials_result = pg_query($pg_con, $additional_aspiration_materials_query);

if (!$additional_aspiration_materials_result) {
    die("Error in SQL query for additional aspiration materials : " . pg_last_error());
}


$additional_location_query = "select location from llx_cyto_recall_fixation_details where cyto_id = '$recall_fk_cyto_id' AND location != '<p><br></p>' 
AND location != '<br>' 
AND location != '<p>&nbsp;</p>' 
AND NOT location ~ '^<p\s*[^>]*>\s*</p>$'";
$additional_location_result = pg_query($pg_con, $additional_location_query);

if (!$additional_location_result) {
    die("Error in SQL query for additional location : " . pg_last_error());
}

$additional_slide_number_query = "select slide_number, dry from llx_cyto_recall_fixation_details where cyto_id = '$recall_fk_cyto_id' AND slide_number != '<p><br></p>' 
AND slide_number != '<br>' 
AND slide_number != '<p>&nbsp;</p>' 
AND NOT slide_number ~ '^<p\s*[^>]*>\s*</p>$'";

$additional_slide_number_result = pg_query($pg_con, $additional_slide_number_query);

if (!$additional_slide_number_query){
    die("Error in SQL query for slide number : " . preg_last_error());
}

// Initialize content for the HTML table
$html = '
<table border="0" cellspacing="0" cellpadding="4" style="width:100%; table-layout: fixed;">';

// Add Clinical Details
$html .= '<tr>
            <th style="width: 12%;"><b>C/C:</b></th>
            <td style="width: 88%;">';
$clinical_details_rows = [];
while ($row = pg_fetch_assoc($clinical_details_result)) {
    $clinical_details_rows[] = htmlspecialchars($row['clinical_details']);
}
$html .= implode('<br/>', $clinical_details_rows);
$html .= '</td></tr>';


// Add Clinical History
// Check if there are rows in the result
if (pg_num_rows($clinical_history_result) > 0) {
    $html .= '<tr>
            <th style="width: 12%;"><b>H/O:</b></th>
            <td style="width: 88%;">';

$clinical_history_rows = [];
while ($row = pg_fetch_assoc($clinical_history_result)) {
    // Normalize <br> tags: collapse multiple <br> to a single <br>
    $clinical_history = $row['relevant_clinical_history'];
    $clinical_history = preg_replace('/(<br\s*\/?>\s*)+/', '<br>', $clinical_history);
    
    // Handle <p> tags: replace <p> with <br> if the content isn't just whitespace
    $clinical_history = preg_replace('/<p[^>]*>(.*?)<\/p>/', '$1<br>', $clinical_history);
    
    // Remove trailing <br> tags if they don't precede text
    $clinical_history = preg_replace('/<br>\s*$/', '', $clinical_history);
    
    // Trim to remove leading and trailing whitespace
    $clinical_history = trim($clinical_history);

    // Add the formatted Aspiration Note to the rows
    $clinical_history_rows[] = $clinical_history;
}

// Combine the rows with <br/> for output
$html .= implode('<br/>', $clinical_history_rows);
$html .= '</td></tr>';
}

// Add on_examination
// Check if there are rows in the result
if (pg_num_rows($on_examination_result) > 0) {
    $html .= '<tr>
                <th style="width: 12%;"><b>O/E:</b></th>
                <td style="width: 88%;">';

    $on_examination_rows = [];
    while ($row = pg_fetch_assoc($on_examination_result)) {
        // Normalize <br> tags: collapse multiple <br> to a single <br>
        $on_examination = $row['on_examination'];
        $on_examination = preg_replace('/(<br\s*\/?>\s*)+/', '<br>', $on_examination);

        // Handle <p> tags: replace <p> with <br> if the content isn't just whitespace
        $on_examination = preg_replace('/<p[^>]*>(.*?)<\/p>/', '$1<br>', $on_examination);

        // Remove trailing <br> tags if they don't precede text
        $on_examination = preg_replace('/<br>\s*$/', '', $on_examination);

        // Trim to remove leading and trailing whitespace
        $on_examination = trim($on_examination);

        // Add the formatted on_examination to the rows
        $on_examination_rows[] = $on_examination;
    }

    // Combine the rows with <br/> for output
    $html .= implode('<br/>', $on_examination_rows);
    $html .= '</td></tr>';
}

// Add clinical_impression
// Check if there are rows in the result
if (pg_num_rows($clinical_impression_result) > 0) {
    $html .= '<tr>
            <th style="width: 12%;"><b>C/I:</b></th>
            <td style="width: 88%;">';

$clinical_impression_rows = [];
while ($row = pg_fetch_assoc($clinical_impression_result)) {
    // Normalize <br> tags: collapse multiple <br> to a single <br>
    $clinical_impression = $row['clinical_impression'];
    $clinical_impression = preg_replace('/(<br\s*\/?>\s*)+/', '<br>', $clinical_impression);
    
    // Handle <p> tags: replace <p> with <br> if the content isn't just whitespace
    $clinical_impression = preg_replace('/<p[^>]*>(.*?)<\/p>/', '$1<br>', $clinical_impression);
    
    // Remove trailing <br> tags if they don't precede text
    $clinical_impression = preg_replace('/<br>\s*$/', '', $clinical_impression);
    
    // Trim to remove leading and trailing whitespace
    $clinical_impression = trim($clinical_impression);

    // Add the formatted Aspiration Note to the rows
    $clinical_impression_rows[] = $clinical_impression;
}

// Combine the rows with <br/> for output
$html .= implode('<br/>', $clinical_impression_rows);
$html .= '</td></tr>';
}


// Add aspiration materials
// Check if there are rows in the result
if (pg_num_rows($aspiration_materials_result) > 0) {
    $html .= '<tr>
            <th style="width: 12%;"><b>A/M:</b></th>
            <td style="width: 88%;">';

$aspiration_materials_rows = [];
while ($row = pg_fetch_assoc($aspiration_materials_result)) {
    // Normalize <br> tags: collapse multiple <br> to a single <br>
    $aspiration_materials = $row['aspiration_materials'];
    $aspiration_materials = preg_replace('/(<br\s*\/?>\s*)+/', '<br>', $aspiration_materials);
    
    // Handle <p> tags: replace <p> with <br> if the content isn't just whitespace
    $aspiration_materials = preg_replace('/<p[^>]*>(.*?)<\/p>/', '$1<br>', $aspiration_materials);
    
    // Remove trailing <br> tags if they don't precede text
    $aspiration_materials = preg_replace('/<br>\s*$/', '', $aspiration_materials);
    
    // Trim to remove leading and trailing whitespace
    $aspiration_materials = trim($aspiration_materials);

    // Add the formatted Aspiration Note to the rows
    $aspiration_materials_rows[] = $aspiration_materials;
}

// Combine the rows with <br/> for output
$html .= implode('<br/>', $aspiration_materials_rows);
$html .= '</td></tr>';
}

/// Add location
$location_rows = [];

// Check if there are rows in the result
if (pg_num_rows($location_result) > 0) {
    while ($row = pg_fetch_assoc($location_result)) {
        // Normalize <br> tags: collapse multiple <br> to a single <br>
        $location = $row['location'];
        $location = preg_replace('/(<br\s*\/?>\s*)+/', '<br>', $location);

        // Handle <p> tags: replace <p> with <br> if the content isn't just whitespace
        $location = preg_replace('/<p[^>]*>(.*?)<\/p>/', '$1<br>', $location);

        // Remove trailing <br> tags if they don't precede text
        $location = preg_replace('/<br>\s*$/', '', $location);

        // Trim to remove leading and trailing whitespace
        $location = trim($location);

        // Add to the list if not empty
        if (!empty($location)) {
            $location_rows[] = $location;
        }
    }
}

// Remove duplicate locations
$unique_locations = array_unique($location_rows);

// **Only display the row if there are non-empty locations**
if (!empty($unique_locations)) {
    $html .= '<tr>
            <th style="width: 12%;"><b>Aspiration:</b></th>
            <td style="width: 88%;">' . implode('<br/>', $unique_locations) . '</td>
        </tr>';
}


// Add slide
// Initialize counters
$no_dry_count = 0;
$yes_dry_count = 0;

// Check if there are rows in the result
if (pg_num_rows($slide_number_result) > 0) {
    while ($row = pg_fetch_assoc($slide_number_result)) {
        // Normalize <br> tags: collapse multiple <br> to a single <br>
        $slide = $row['slide_number'];
        $slide = preg_replace('/(<br\s*\/?>\s*)+/', '<br>', $slide);

        // Handle <p> tags: replace <p> with <br> if the content isn't just whitespace
        $slide = preg_replace('/<p[^>]*>(.*?)<\/p>/', '$1<br>', $slide);

        // Remove trailing <br> tags if they don't precede text
        $slide = preg_replace('/<br>\s*$/', '', $slide);

        // Trim to remove leading and trailing whitespace
        $slide = trim($slide);

        // Count based on dry value
        if (strtolower($row['dry']) === 'no') {
            $no_dry_count++;
        } elseif (strtolower($row['dry']) === 'yes') {
            $yes_dry_count++;
        }
    }

    // Add slide data to HTML
    $html .= '<tr>
                <th style="width: 12%;"><b>Slide:</b></th>
                <td style="width: 88%;">' . $no_dry_count . '+' . $yes_dry_count . '</td>
              </tr>';
}


// Add special_instructions
$special_instructions_rows = [];

// Check if there are rows in the result
if (pg_num_rows($special_instructions_result) > 0) {
    while ($row = pg_fetch_assoc($special_instructions_result)) {
        // Normalize <br> tags: collapse multiple <br> to a single <br>
        $special_instructions = $row['special_instructions'];
        $special_instructions = preg_replace('/(<br\s*\/?>\s*)+/', '<br>', $special_instructions);

        // Handle <p> tags: replace <p> with <br> if the content isn't just whitespace
        $special_instructions = preg_replace('/<p[^>]*>(.*?)<\/p>/', '$1<br>', $special_instructions);

        // Remove trailing <br> tags if they don't precede text
        $special_instructions = preg_replace('/<br>\s*$/', '', $special_instructions);

        // Trim to remove leading and trailing whitespace
        $special_instructions = trim($special_instructions);

        // Add to the list if not empty
        if (!empty($special_instructions)) {
            $special_instructions_rows[] = $special_instructions;
        }
    }
}

// Remove duplicate special_instructions
$unique_special_instructions = array_unique($special_instructions_rows);

// **Only display the row if there are non-empty special_instructions**
if (!empty($unique_special_instructions)) {
    $html .= '<tr>
            <th style="width: 12%;"><b>S/I:</b></th>
            <td style="width: 88%;">' . implode('<br/>', $unique_special_instructions) . '</td>
        </tr>';
}


// Add Additional relevant clinical history
// Check if there are rows in the result
if (pg_num_rows($additional_clinical_history_result) > 0) {
    $html .= '<tr>
            <th style="width: 12%;"><b>A/H/O:</b></th>
            <td style="width: 88%;">';

$additional_clinical_history_rows = [];
while ($row = pg_fetch_assoc($additional_clinical_history_result)) {
    // Normalize <br> tags: collapse multiple <br> to a single <br>
    $additional_clinical_history = $row['additional_relevant_clinical_history'];
    $additional_clinical_history = preg_replace('/(<br\s*\/?>\s*)+/', '<br>', $clinical_history);
    
    // Handle <p> tags: replace <p> with <br> if the content isn't just whitespace
    $additional_clinical_history = preg_replace('/<p[^>]*>(.*?)<\/p>/', '$1<br>', $additional_clinical_history);
    
    // Remove trailing <br> tags if they don't precede text
    $additional_clinical_history= preg_replace('/<br>\s*$/', '', $additional_clinical_history);
    
    // Trim to remove leading and trailing whitespace
    $additional_clinical_history = trim($additional_clinical_history);

    // Add the formatted Aspiration Note to the rows
    $additional_clinical_history_rows[] = $additional_clinical_history;
}

// Combine the rows with <br/> for output
$html .= implode('<br/>', $additional_clinical_history_rows);
$html .= '</td></tr>';
}


// Add additional findings on examination
// Check if there are rows in the result
if (pg_num_rows($additional_on_examination_result) > 0) {
    $html .= '<tr>
            <th style="width: 12%;"><b>A/O/E:</b></th>
            <td style="width: 88%;">';

$additional_on_examination_rows = [];
while ($row = pg_fetch_assoc($additional_on_examination_result)) {
    // Normalize <br> tags: collapse multiple <br> to a single <br>
    $additional_on_examination = $row['additional_findings_on_examination'];
    $additional_on_examination = preg_replace('/(<br\s*\/?>\s*)+/', '<br>', $additional_on_examination);
    
    // Handle <p> tags: replace <p> with <br> if the content isn't just whitespace
    $additional_on_examination = preg_replace('/<p[^>]*>(.*?)<\/p>/', '$1<br>', $additional_on_examination);
    
    // Remove trailing <br> tags if they don't precede text
    $additional_on_examination= preg_replace('/<br>\s*$/', '', $additional_on_examination);
    
    // Trim to remove leading and trailing whitespace
    $additional_on_examination = trim($additional_on_examination);

    // Add the formatted Aspiration Note to the rows
    $additional_on_examination_rows[] = $additional_on_examination;
}

// Combine the rows with <br/> for output
$html .= implode('<br/>', $additional_on_examination_rows);
$html .= '</td></tr>';
}

// Add additional findings additional aspiration materials
// Check if there are rows in the result
if (pg_num_rows($additional_clinical_impression_result) > 0) {
    $html .= '<tr>
            <th style="width: 12%;"><b>A/O/E:</b></th>
            <td style="width: 88%;">';

$additional_clinical_impression_rows = [];
while ($row = pg_fetch_assoc($additional_clinical_impression_result)) {
    // Normalize <br> tags: collapse multiple <br> to a single <br>
    $additional_clinical_impression = $row['additional_clinical_impression'];
    $additional_clinical_impression = preg_replace('/(<br\s*\/?>\s*)+/', '<br>', $additional_clinical_impression);
    
    // Handle <p> tags: replace <p> with <br> if the content isn't just whitespace
    $additional_clinical_impression = preg_replace('/<p[^>]*>(.*?)<\/p>/', '$1<br>', $additional_clinical_impression);
    
    // Remove trailing <br> tags if they don't precede text
    $additional_clinical_impression = preg_replace('/<br>\s*$/', '', $additional_clinical_impression);
    
    // Trim to remove leading and trailing whitespace
    $additional_clinical_impression = trim($additional_clinical_impression);

    // Add the formatted Aspiration Note to the rows
    $additional_clinical_impression_rows[] = $additional_clinical_impression;
}

// Combine the rows with <br/> for output
$html .= implode('<br/>', $additional_clinical_impression_rows);
$html .= '</td></tr>';
}


// Add additional aspiration materials
// Check if there are rows in the result
if (pg_num_rows($additional_aspiration_materials_result) > 0) {
    $html .= '<tr>
            <th style="width: 12%;"><b>A/A/M:</b></th>
            <td style="width: 88%;">';

    $additional_aspiration_materials_rows = [];
    while ($row = pg_fetch_assoc($additional_aspiration_materials_result)) {
        // Normalize <br> tags: collapse multiple <br> to a single <br>
        $additional_aspiration_materials = $row['aspiration_materials'];
        $additional_aspiration_materials = preg_replace('/(<br\s*\/?>\s*)+/', '<br>', $additional_aspiration_materials);

        // Handle <p> tags: replace <p> with <br> if the content isn't just whitespace
        $additional_aspiration_materials = preg_replace('/<p[^>]*>(.*?)<\/p>/', '$1<br>', $additional_aspiration_materials);

        // Remove trailing <br> tags if they don't precede text
        $additional_aspiration_materials = preg_replace('/<br>\s*$/', '', $additional_aspiration_materials);

        // Trim to remove leading and trailing whitespace
        $additional_aspiration_materials= trim($additional_aspiration_materials);

        // Add the formatted Aspiration Note to the rows
        $additional_aspiration_materials_rows[] = $additional_aspiration_materials;
    }

    // Remove duplicate special_instructions
    $unique_additional_aspiration_materials = array_unique($additional_aspiration_materials_rows);

    // Combine the unique rows with <br/> for output
    $html .= implode('<br/>', $unique_additional_aspiration_materials);
    $html .= '</td></tr>';
}


// Add location
// Check if there are rows in the result
if (pg_num_rows($additional_location_result) > 0) {
    $html .= '<tr>
            <th style="width: 12%;"><b>Aspiration:</b></th>
            <td style="width: 88%;">';

    $additional_location_rows = [];
    while ($row = pg_fetch_assoc($additional_location_result)) {
        // Normalize <br> tags: collapse multiple <br> to a single <br>
        $additional_location = $row['location'];
        $additional_location = preg_replace('/(<br\s*\/?>\s*)+/', '<br>', $additional_location);

        // Handle <p> tags: replace <p> with <br> if the content isn't just whitespace
        $additional_location = preg_replace('/<p[^>]*>(.*?)<\/p>/', '$1<br>', $additional_location);

        // Remove trailing <br> tags if they don't precede text
        $additional_location = preg_replace('/<br>\s*$/', '', $additional_location);

        // Trim to remove leading and trailing whitespace
        $additional_location = trim($additional_location);

        // Add the formatted Aspiration Note to the rows
        $additional_location_rows[] = $additional_location;
    }

    // Remove duplicate additional location
    $unique_additional_location = array_unique($additional_location_rows);

    // Combine the unique rows with <br/> for output
    $html .= implode('<br/>', $unique_additional_location);
    $html .= '</td></tr>';
}


// Add additional slide
// Initialize counters
$additional_no_dry_count = 0;
$additional_yes_dry_count = 0;

// Check if there are rows in the result
if (pg_num_rows($additional_slide_number_result) > 0) {
    while ($row = pg_fetch_assoc($additional_slide_number_result)) {
        // Normalize <br> tags: collapse multiple <br> to a single <br>
        $additional_slide = $row['slide_number'];
        $additional_slide = preg_replace('/(<br\s*\/?>\s*)+/', '<br>', $additional_slide);

        // Handle <p> tags: replace <p> with <br> if the content isn't just whitespace
        $additional_slide = preg_replace('/<p[^>]*>(.*?)<\/p>/', '$1<br>', $additional_slide);

        // Remove trailing <br> tags if they don't precede text
        $additional_slide = preg_replace('/<br>\s*$/', '', $additional_slide);

        // Trim to remove leading and trailing whitespace
        $additional_slide = trim($additional_slide);

        // Count based on dry value
        if (strtolower($row['dry']) === 'no') {
            $additional_no_dry_count++;
        } elseif (strtolower($row['dry']) === 'yes') {
            $additional_yes_dry_count++;
        }
    }

    // Add slide data to HTML
    $html .= '<tr>
                <th style="width: 12%;"><b>Slide:</b></th>
                <td style="width: 88%;">' . $additional_no_dry_count . '+' . $additional_yes_dry_count . '</td>
              </tr>';
}


$additional_special_instructions_query = "select special_instructions from llx_cyto_recall_fixation_details where cyto_id = '$recall_fk_cyto_id' AND special_instructions != '<p><br></p>' 
AND special_instructions != '<br>' 
AND special_instructions != '<p>&nbsp;</p>' 
AND NOT special_instructions ~ '^<p\s*[^>]*>\s*</p>$'";

$additional_special_instructions_result = pg_query($pg_con, $additional_special_instructions_query);

if (!$additional_special_instructions_query){
    die("Error in SQL query for additional special instructions : " . preg_last_error());
}

// Add special_instructions
// Check if there are rows in the result
if (pg_num_rows($additional_special_instructions_result) > 0) {
    $html .= '<tr>
            <th style="width: 12%;"><b>A/S/I:</b></th>
            <td style="width: 88%;">';

    $additional_special_instructions_rows = [];
    while ($row = pg_fetch_assoc($additional_special_instructions_result)) {
        // Normalize <br> tags: collapse multiple <br> to a single <br>
        $additional_special_instructions = $row['special_instructions'];
        $additional_special_instructions = preg_replace('/(<br\s*\/?>\s*)+/', '<br>', $additional_special_instructions);

        // Handle <p> tags: replace <p> with <br> if the content isn't just whitespace
        $additional_special_instructions = preg_replace('/<p[^>]*>(.*?)<\/p>/', '$1<br>', $additional_special_instructions);

        // Remove trailing <br> tags if they don't precede text
        $additional_special_instructions = preg_replace('/<br>\s*$/', '', $additional_special_instructions);

        // Trim to remove leading and trailing whitespace
        $additional_special_instructions = trim($additional_special_instructions);

        // Add the formatted Aspiration Note to the rows
        $additional_special_instructions_rows[] = $additional_special_instructions;
    }

    // Remove duplicate special_instructions
    $unique_additional_special_instructions = array_unique($additional_special_instructions_rows);

    // Combine the unique rows with <br/> for output
    $html .= implode('<br/>', $unique_additional_special_instructions);
    $html .= '</td></tr>';
}

// Write the HTML content
$pdf->writeHTML($html, true, false, true, false, '');


// Calculate space for Diagnosis Description
$diagnosisDescriptionHeight = $pdf->getStringHeight($html, '', $pdf->getPageWidth());

// Get current Y position before writing the signatures
$currentY = $pdf->GetY();

// Calculate remaining space before Signatures table
$remainingSpace = $pdf->getPageHeight() - $currentY;
$remainingPercentage = ($remainingSpace / $pdf->getPageHeight()) * 100;

// Define the "End of Report" message with star icons
$end_pdf = '……………………… End of Report ………………………'; 

// Check if there's enough space for the signatures table
$spaceNeeded = $pdf->getStringHeight($signaturesTableHTML, '', $pdf->getPageWidth());
$spaceAvailable = $pdf->getPageHeight() - $pdf->GetY();
$bottomMargin = 48; // Adjust this value as needed

// Function to handle line breaks consistently
function addLineBreaks($pdf, $count = 1) {
    for ($i = 0; $i < $count; $i++) {
        $pdf->Ln(10); // You can adjust this value for more or less spacing
    }
}

// Check if there's enough space between Diagnosis Description and Signatures table
if ($remainingPercentage > 50) {
    // Add space before the message
    addLineBreaks($pdf, 3); // Add three line breaks

    $pdf->Cell(0, 10, $end_pdf, 0, 1, 'C'); // Centered message

    // Add space after the message for signatures table
    addLineBreaks($pdf, 2); // Add two line breaks for consistent spacing
}







// Check if there's enough space for the signatures table
$spaceNeeded = $pdf->getStringHeight($signaturesTableHTML, '', $pdf->getPageWidth());
$spaceAvailable = $pdf->getPageHeight() - $pdf->GetY();
$bottomMargin = 48; // Adjust this value as needed

// Check if there's enough space on the current page
if ($spaceNeeded > $spaceAvailable - $bottomMargin) {
    // Not enough space, add a new page
    $pdf->AddPage();
}

// Position the cursor at the bottom margin
$pdf->SetY($pdf->getPageHeight() - $bottomMargin);

// Write the signatures table HTML
$pdf->writeHTML($signaturesTableHTML, true, false, false, false, '');

// Reset pointer to the last page
$pdf->lastPage();

$pdf->setIsLastPage(true); // Set the last page flag

// Add the content for the last page
$pdf->writeHTML($lastPageContent);

$fileName = $LabNumber . '.pdf';

// Get the PDF content as a string  
$pdf->Output($fileName, 'I');

// Base64 encode the PDF content
$pdfData = base64_encode($pdfContent);

?>



<!DOCTYPE html>
<html>
<head>
    <title>Preview PDF</title>
</head>
<body>
    <!-- Embed PDF content using iframe -->
    <iframe src="data:application/pdf;base64,<?php echo $pdfData; ?>" style="width: 100%; height: 1200px;" frameborder="0"></iframe>
</body>
</html>