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
            $this->Cell(40, 8, 'CYTOLOGY REPORT', 0, 1, 'C', false);
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

$examination_query = "SELECT on_examination FROM llx_cyto_clinical_information WHERE cyto_id = '$fk_cyto_id'";
$examination_result = pg_query($pg_con, $examination_query);

if (!$examination_result) {
    die("Error in SQL query for examination : " . pg_last_error());
}

// SQL operation for aspiration_note
$aspiration_note_query = "SELECT aspiration_note FROM llx_cyto_clinical_information WHERE cyto_id = '$fk_cyto_id'";
$aspiration_note_result = pg_query($pg_con, $aspiration_note_query);

if (!$aspiration_note_result) {
    die("Error in SQL query for aspiration note: " . pg_last_error());
}



// Prepare microscopic description
$microscopic_description = "select microscopic_description from llx_cyto_microscopic_description where lab_number ='$LabNumber'";
$microscopic_description_result = pg_query($pg_con, $microscopic_description);

// Check if the query was successful
if (!$microscopic_description_result) {
    die("Query failed for microscopic_description: " . pg_last_error());
}


// SQL operation for dynamic data
$conclusion_details_info = "select conclusion from llx_cyto_microscopic_description where lab_number ='$LabNumber'";
$conclusion_details_result = pg_query($pg_con, $conclusion_details_info );

// Check if the query was successful
if (!$conclusion_details_result) {
    die("Query failed for conclusion_description: " . pg_last_error());
}

// SQL operation for dynamic data
$comment_details_info  = "select comment from llx_cyto_microscopic_description where lab_number ='$LabNumber' AND comment != '<p><br></p>' 
AND comment != '<br>' 
AND comment != '<p>&nbsp;</p>' 
AND NOT comment ~ '^<p\s*[^>]*>\s*</p>$'";
$comment_details_result = pg_query($pg_con, $comment_details_info);

// Check if the query was successful
if (!$comment_details_result) {
    die("Query failed for comment_description: " . pg_last_error());
}


// Initialize content for the HTML table
$html = '
<table border="0" cellspacing="0" cellpadding="4" style="width:100%; table-layout: fixed;">';

// Add Clinical Details
$html .= '<tr>
            <th style="width: 26%;"><b>Clinical Details:</b></th>
            <td style="width: 74%;">';
$clinical_details_rows = [];
while ($row = pg_fetch_assoc($clinical_details_result)) {
    $clinical_details_rows[] = htmlspecialchars($row['clinical_details']);
}
$html .= implode('<br/>', $clinical_details_rows);
$html .= '</td></tr>';

// Add on_examination
$html .= '<tr>
            <th style="width: 26%;"><b>OnExamination:</b></th>
            <td style="width: 74%;">';

$examination_rows = [];
while ($row = pg_fetch_assoc($examination_result)) {
    // Normalize <br> tags: collapse multiple <br> to a single <br>
    $examination = $row['on_examination'];
    $examination = preg_replace('/(<br\s*\/?>\s*)+/', '<br>', $examination);
    
    // Handle <p> tags: replace <p> with <br> if the content isn't just whitespace
    $examination = preg_replace('/<p[^>]*>(.*?)<\/p>/', '$1<br>', $examination);
    
    // Remove trailing <br> tags if they don't precede text
    $examination = preg_replace('/<br>\s*$/', '', $examination);
    
    // Trim to remove leading and trailing whitespace
    $examination = trim($examination);

    // Add the formatted examination to the rows
    $examination_rows[] = $examination;
}

// Combine the rows with <br/> for output
$html .= implode('<br/>', $examination_rows);
$html .= '</td></tr>';


// Add Aspiration Note
$html .= '<tr>
            <th style="width: 26%;"><b>Aspiration Note:</b></th>
            <td style="width: 74%;">';

$aspiration_note_rows = [];
while ($row = pg_fetch_assoc($aspiration_note_result)) {
    // Normalize <br> tags: collapse multiple <br> to a single <br>
    $aspiration_note = $row['aspiration_note'];
    $aspiration_note = preg_replace('/(<br\s*\/?>\s*)+/', '<br>', $aspiration_note);
    
    // Handle <p> tags: replace <p> with <br> if the content isn't just whitespace
    $aspiration_note = preg_replace('/<p[^>]*>(.*?)<\/p>/', '$1<br>', $aspiration_note);
    
    // Remove trailing <br> tags if they don't precede text
    $aspiration_note = preg_replace('/<br>\s*$/', '', $aspiration_note);
    
    // Trim to remove leading and trailing whitespace
    $aspiration_note = trim($aspiration_note);

    // Add the formatted Aspiration Note to the rows
    $aspiration_note_rows[] = $aspiration_note;
}

// Combine the rows with <br/> for output
$html .= implode('<br/>', $aspiration_note_rows);
$html .= '</td></tr>';

// Add Microscopic Description
$html .= '<tr>
            <th style="width: 26%;"><b>Microscopic Description:</b></th>
            <td style="width: 74%;">';

$microscopic_description_rows = [];
while ($row = pg_fetch_assoc($microscopic_description_result)) {
    // Use the content from the database for rendering
    $description = $row['microscopic_description'];

    // Normalize <br> tags: collapse multiple <br> to a single <br>
    $description = preg_replace('/(<br\s*\/?>\s*)+/', '<br>', $description);

    // Handle <p> tags: replace <p> with <br> if the content isn't just whitespace
    $description = preg_replace('/<p[^>]*>(.*?)<\/p>/', '$1<br>', $description);

    // Remove trailing <br> tags if they don't precede text
    $description = preg_replace('/<br>\s*$/', '', $description);

    // Trim to remove leading and trailing whitespace
    $description = trim($description);

    // Add the formatted description to the rows
    $microscopic_description_rows[] = $description;
}

// Combine the rows with <br/> for output
$html .= implode('<br/>', $microscopic_description_rows);
$html .= '</td></tr>';

// Add Conclusion Description
$html .= '<tr>
            <th style="width: 26%;"><b>Conclusion:</b></th>
            <td style="width: 74%;">';

$conclusion_description_rows = [];
while ($row = pg_fetch_assoc($conclusion_details_result)) {
    $description = $row['conclusion'];

    // Normalize <br> tags: collapse multiple <br> to a single <br>
    $description = preg_replace('/(<br\s*\/?>\s*)+/', '<br>', $description);

    // Handle <p> tags: replace <p> with <br> only if the content isn't just whitespace
    $description = preg_replace('/<p[^>]*>(.*?)<\/p>/', '$1<br>', $description);

    // Remove trailing <br> tags if they don't precede text
    $description = preg_replace('/<br>\s*$/', '', $description);

    // Trim to remove leading and trailing whitespace
    $description = trim($description);

    // Append the processed description
    $conclusion_description_rows[] = $description;
}

// Combine the rows with <br/> for output
$html .= implode('<br/>', $conclusion_description_rows);
$html .= '</td></tr>';


// Check if the query returns any rows
if (pg_num_rows($comment_details_result) == 0) {
    // If no rows are returned, show blank
    $html .= '</table>';
}else {
        // Add Comment Description
        $html .= '<tr>
        <th style="width: 26%;"><b>Comment:</b></th>
        <td style="width: 74%;">';

        $comment_description_rows = [];
        while ($row = pg_fetch_assoc($comment_details_result)) {
        $description = $row['comment'];

        // Normalize <br> tags: collapse multiple <br> to a single <br>
        $description = preg_replace('/(<br\s*\/?>\s*)+/', '<br>', $description);

        // Handle <p> tags: replace <p> with <br> only if the content isn't just whitespace
        $description = preg_replace('/<p[^>]*>(.*?)<\/p>/', '$1<br>', $description);

        // Remove trailing <br> tags if they don't precede text
        $description = preg_replace('/<br>\s*$/', '', $description);

        // Trim to remove leading and trailing whitespace
        $description = trim($description);

        // Append the processed description
        $$comment_description_rows[] = $description;
        }

        // Combine the rows with <br/> for output
        $html .= implode('<br/>', $$comment_description_rows);
        $html .= '</td></tr>';
        $html .= '</table>';
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



// sql opertaion for dynamic data 

$assisted_by  = "SELECT dd.username as username, dd.doctor_name as doctor_name, dd.education as education, 
dd.designation as designation
FROM llx_doctor_degination AS dd
INNER JOIN llx_doctor_assisted_by_signature AS ds ON dd.username = ds.doctor_username
WHERE ds.lab_number  = '$LabNumber'";

$assisted_by_result = pg_query($pg_con, $assisted_by);
// Check if the query was successful
if ($assisted_by_result) {
    // Fetch the results (if any)
    while ($row = pg_fetch_assoc($assisted_by_result)) {
        // Process each row as needed
        $assisted_doctor_name = $row['doctor_name'];
        $assisted_education = $row['education'];
        $assisted_designation = $row['designation'];
        // Store the assisted in a session variable for later use
        $_SESSION['doctor_name'] = $assisted_doctor_name;
        $_SESSION['education'] = $assisted_education;
        $_SESSION['designation '] = $assisted_designation;
    }
} else {
    // Handle query error
    die("Query failed for assisted_by: " . pg_last_error());
}

$finalized_by_info  = "SELECT dd.username as username, dd.doctor_name as doctor_name, dd.education as education, 
                            dd.designation as designation
                            FROM llx_doctor_degination AS dd
                            INNER JOIN llx_doctor_finalized_by_signature AS ds ON dd.username = ds.doctor_username
                            WHERE ds.lab_number = '$LabNumber'";

$finalized_by_info_result = pg_query($pg_con, $finalized_by_info);
// Check if the query was successful
if ($finalized_by_info_result) {
    // Fetch the results (if any)
    while ($row = pg_fetch_assoc($finalized_by_info_result)) {
        // Process each row as needed
        $finalized_by_doctor_name = $row['doctor_name'];
        $finalized_by_education = $row['education'];
        $finalized_by_designation = $row['designation'];
        // Store the assisted in a session variable for later use
        $_SESSION['doctor_name'] = $finalized_by_doctor_name;
        $_SESSION['education'] = $finalized_by_education;
        $_SESSION['designation '] = $finalized_by_designation;
    }
} else {
    // Handle query error
    die("Query failed for finalized_by: " . pg_last_error());
}

$finalized_by_doctor_name = trim($finalized_by_doctor_name);
$signaturesTableHTML = '';

switch ($finalized_by_doctor_name) {
    case 'Dr. Md. Shafikul Alam Tanim':
        switch (trim($assisted_doctor_name)) {
            case 'Dr. Syeeda Shiraj-Um-Mahmuda':
                $signaturesTableHTML = '<style>
                            .custom-table {
                            width: 100%;
                            border-collapse: collapse;
                            font-size: 11px;
                            }
                            .custom-table th, .custom-table td {
                            padding: 8px;
                            text-align: left;
                            }
                            .custom-table th {
                            font-weight: bold;
                            }
                            </style>
                            <table class="custom-table">
                            <tr>
                            <th colspan="2"><b>'.$assisted_doctor_name.'</b></th>
                            <th colspan="2" style="text-align:center">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>'.$finalized_by_doctor_name.'</b></th>
                            </tr>
                            <tr>
                            <th colspan="2">&nbsp;'.$assisted_education.'</th>
                           <th colspan="2" style="text-align:left-center">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$finalized_by_education.'</th>
                            </tr>
                            <tr>
                            <th colspan="2">&nbsp;'.$assisted_designation.'</th>
                            <th colspan="2" style="text-align:center">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$finalized_by_designation.'</th>
                            </tr>
                            </table>';
                break;
            
            case 'Dr. Farhana Yusuf':
                $signaturesTableHTML = '<style>
                                .custom-table {
                                width: 100%;
                                border-collapse: collapse;
                                font-size: 11px;
                                }
                                .custom-table th, .custom-table td {
                                padding: 8px;
                                text-align: left;
                                }
                                .custom-table th {
                                font-weight: bold;
                                }
                                </style>
                                <table class="custom-table">
                                <tr>
                                <th colspan="2"><b>'.$assisted_doctor_name.'</b></th>
                                <th colspan="2" style="text-align:center">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>'.$finalized_by_doctor_name.'</b></th>
                                </tr>
                                <tr>
                                <th colspan="2">&nbsp;'.$assisted_education.'</th>
                               <th colspan="2" style="text-align:left-center">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$finalized_by_education.'</th>
                                </tr>
                                <tr>
                                <th colspan="2">&nbsp;'.$assisted_designation.'</th>
                                <th colspan="2" style="text-align:center">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$finalized_by_designation.'</th>
                                </tr>
                                </table>';
                break;

            case 'Dr. Julekha Khatun':
                $signaturesTableHTML = '<style>
                        .custom-table {
                            width: 100%;
                            border-collapse: collapse;
                            font-size: 11px;
                        }
                        .custom-table th, .custom-table td {
                            padding: 8px;
                            text-align: left;
                        }
                        .custom-table th {
                            font-weight: bold;
                        }
                        </style>
                        <table class="custom-table">
                        <tr>
                            <th colspan="2"><b>'.$assisted_doctor_name.'</b></th>
                            <th colspan="2" style="text-align:center">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>'.$finalized_by_doctor_name.'</b></th>
                            </tr>
                            <tr>
                            <th colspan="2">&nbsp;'.$assisted_education.'</th>
                            <th colspan="2" style="text-align:left-center">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$finalized_by_education.'</th>
                        </tr>
                        <tr>
                            <th colspan="2">&nbsp;'.$assisted_designation.'</th>
                            <th colspan="2" style="text-align:center">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$finalized_by_designation.'</th>
                        </tr>
                        </table>';
                break;
            
            case 'Dr. Md. Shahrior Nahid':
                $signaturesTableHTML = '<style>
                    .custom-table {
                        width: 100%;
                        border-collapse: collapse;
                        font-size: 11px;
                    }
                    .custom-table th, .custom-table td {
                         padding: 8px;
                         text-align: left;
                     }
                    .custom-table th {
                         font-weight: bold;
                     }
                    </style>
                    <table class="custom-table">
                    <tr>
                        <th colspan="2"><b>'.$assisted_doctor_name.'</b></th>
                        <th colspan="2" style="text-align:center">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>'.$finalized_by_doctor_name.'</b></th>
                        </tr>
                        <tr>
                            <th colspan="2">&nbsp;'.$assisted_education.'</th>
                            <th colspan="2" style="text-align:left-center">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$finalized_by_education.'</th>
                        </tr>
                    <tr>
                        <th colspan="2">&nbsp;'.$assisted_designation.'</th>
                        <th colspan="2" style="text-align:center">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$finalized_by_designation.'</th>
                    </tr>
                    </table>';
                break;
            case 'Dr. Tasmia Islam':
                    $signaturesTableHTML = '<style>
                        .custom-table {
                            width: 100%;
                            border-collapse: collapse;
                            font-size: 11px;
                        }
                        .custom-table th, .custom-table td {
                             padding: 8px;
                             text-align: left;
                         }
                        .custom-table th {
                             font-weight: bold;
                         }
                        </style>
                        <table class="custom-table">
                        <tr>
                            <th colspan="2"><b>'.$assisted_doctor_name.'</b></th>
                            <th colspan="2" style="text-align:center">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>'.$finalized_by_doctor_name.'</b></th>
                            </tr>
                            <tr>
                                <th colspan="2">&nbsp;'.$assisted_education.'</th>
                                <th colspan="2" style="text-align:left-center">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$finalized_by_education.'</th>
                            </tr>
                        <tr>
                            <th colspan="2">&nbsp;'.$assisted_designation.'</th>
                            <th colspan="2" style="text-align:center">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$finalized_by_designation.'</th>
                        </tr>
                        </table>';
                    break;

            case 'Dr Jenifer Rahman':
                        $signaturesTableHTML = '<style>
                            .custom-table {
                                width: 100%;
                                border-collapse: collapse;
                                font-size: 11px;
                            }
                            .custom-table th, .custom-table td {
                                 padding: 8px;
                                 text-align: left;
                             }
                            .custom-table th {
                                 font-weight: bold;
                             }
                            </style>
                            <table class="custom-table">
                            <tr>
                                <th colspan="2"><b>'.$assisted_doctor_name.'</b></th>
                                <th colspan="2" style="text-align:center">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>'.$finalized_by_doctor_name.'</b></th>
                                </tr>
                                <tr>
                                    <th colspan="2">&nbsp;'.$assisted_education.'</th>
                                    <th colspan="2" style="text-align:left-center">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$finalized_by_education.'</th>
                                </tr>
                            <tr>
                                <th colspan="2">&nbsp;'.$assisted_designation.'</th>
                                <th colspan="2" style="text-align:center">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$finalized_by_designation.'</th>
                            </tr>
                            </table>';
                        break;

            default:
                // HTML for other assisted doctors with Dr. Md. Shafikul Alam Tanim
                $signaturesTableHTML = '<style>
                .custom-table {
                width: 100%;
                border-collapse: collapse;
                font-size: 11px;
                }
                .custom-table th, .custom-table td {
                padding: 8px;
                text-align: left;
                }
                .custom-table th {
                font-weight: bold;
                }
                </style>
                <table class="custom-table">
                <tr>
                <th colspan="2"><b>'.$assisted_doctor_name.'</b></th>
                <th colspan="2" style="text-align:center">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>'.$finalized_by_doctor_name.'</b></th>
                </tr>
                <tr>
                <th colspan="2">&nbsp;'.$assisted_education.'</th>
                <th colspan="2" style="text-align:left-center">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$finalized_by_education.'</th>
                </tr>
                <tr>
                <th colspan="2">&nbsp;'.$assisted_designation.'</th>
                <th colspan="2" style="text-align:center">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$finalized_by_designation.'</th>
                </tr>
                </table>';
                break;
        }
        break;

    case 'Prof. Dr. Md. Aminul Islam Khan':
        $signaturesTableHTML = '<style>
            .custom-table {
                width: 100%;
                border-collapse: collapse;
                font-size: 11px;
            }
            .custom-table th, .custom-table td {
                padding: 8px;
                text-align: left;
            }
            .custom-table th {
                font-weight: bold;
            }
            </style>
            <table class="custom-table">
                <tr>
                    <th colspan="2"><b>'.$assisted_doctor_name.'</b></th>
                    <th colspan="2" style="text-align:center">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>'.$finalized_by_doctor_name.'</b></th>
                </tr>
                <tr>
                    <th colspan="2">&nbsp;'.$assisted_education.'</th>
                    <th colspan="2" style="text-align:center">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$finalized_by_education.'</th>
                </tr>
                <tr>
                    <th colspan="2">&nbsp;'.$assisted_designation.'</th>
                    <th colspan="2" style="text-align:center">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$finalized_by_designation.'</th>
                </tr>
            </table>';
        break;

        case 'Dr. Syeeda Shiraj-Um-Mahmuda':
            $signaturesTableHTML = '<style>
                .custom-table {
                    width: 100%;
                    border-collapse: collapse;
                    font-size: 11px;
                }
                .custom-table th, .custom-table td {
                    padding: 8px;
                    text-align: left;
                }
                .custom-table th {
                    font-weight: bold;
                }
                </style>
                <table class="custom-table">
                    <tr>
                        <th colspan="2"><b>'.$assisted_doctor_name.'</b></th>
                        <th colspan="2" style="text-align:center">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>'.$finalized_by_doctor_name.'</b></th>
                    </tr>
                    <tr>
                        <th colspan="2">&nbsp;'.$assisted_education.'</th>
                        <th colspan="2" style="text-align:center">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$finalized_by_education.'</th>
                    </tr>
                    <tr>
                        <th colspan="2">&nbsp;'.$assisted_designation.'</th>
                        <th colspan="2" style="text-align:center">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$finalized_by_designation.'</th>
                    </tr>
                </table>';
            break;

        case 'Dr. Farhana Yusuf':
            $signaturesTableHTML = '<style>
                    .custom-table {
                        width: 100%;
                        border-collapse: collapse;
                        font-size: 11px;
                    }
                    .custom-table th, .custom-table td {
                        padding: 8px;
                        text-align: left;
                    }
                    .custom-table th {
                        font-weight: bold;
                    }
                    </style>
                    <table class="custom-table">
                        <tr>
                            <th colspan="2"><b>'.$assisted_doctor_name.'</b></th>
                            <th colspan="2" style="text-align:center">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>'.$finalized_by_doctor_name.'</b></th>
                        </tr>
                        <tr>
                            <th colspan="2">&nbsp;'.$assisted_education.'</th>
                            <th colspan="2" style="text-align:center">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$finalized_by_education.'</th>
                        </tr>
                        <tr>
                            <th colspan="2">&nbsp;'.$assisted_designation.'</th>
                            <th colspan="2" style="text-align:center">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$finalized_by_designation.'</th>
                        </tr>
                    </table>';
            break;

    default:
        // Handle default case if needed
        break;
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