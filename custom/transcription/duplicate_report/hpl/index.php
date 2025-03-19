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
$LabNumber = isset($_GET['lab_number']) ? $_GET['lab_number'] : '';
$LabNumberWithoutPrefix = str_replace(["HPL", "-HPL"], "", $LabNumber);
$lab_number_table = str_replace("-", "", $LabNumberWithoutPrefix);
$lab_number = $LabNumberWithoutPrefix;
// Prepare the SQL query for dynamic data
$invoice_number = "SELECT f.ref AS invoice  
                   FROM llx_facture AS f  
                   JOIN llx_societe s ON f.fk_soc = s.rowid  
                   JOIN llx_commande AS c ON c.fk_soc = s.rowid 
                   WHERE c.ref = '$LabNumberWithoutPrefix' 
                   ORDER BY f.rowid DESC LIMIT 1;";

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
$patient_information = "select rowid, nom, code_client, address, phone, fax, date_of_birth, sex, ageyrs, att_name, att_relation
from llx_other_report_patient_information where lab_number = '$LabNumber'";

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
    
    // Constructor to accept and set the lab number, invoice value, and patient data
    public function __construct($lab_number, $invoice_value, $code_client, $pg_con) {
        // Call the parent constructor
        parent::__construct();

        // Set the lab number and invoice value
        $this->lab_number = $lab_number;
        $this->invoice_value = $invoice_value;
        $this->code_client = $code_client;
    }

    // Override the Header method to remove the underline and shadow effect
        public function Header() {
            // Increase the top margin to avoid overlapping or removing the header when printed
            $this->SetY(40); // Move the starting point of the header to 40 units from the top of the page
        
            // Set the font for the header
            $this->SetFont('Times', 'B', 12); // Bold Times, size 12
        
            // Set margins (optional, if you want to adjust the left and right margins)
            $this->SetMargins(50, 50, 50); // Set left, top, and right margins (increased top margin to 40 units)
        
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
        
            // Adjust Y position for 'HISTOPATHOLOGY REPORT' relative to the barcode size
            $barcodeHeight = 20; // Height of the barcode (same as above)
            $this->SetY($this->GetY() + $barcodeHeight - 5); // Set the Y position below the barcode
        
            // Set X position for 'HISTOPATHOLOGY REPORT' and center-align the text
            $this->SetX($this->getPageWidth() - 120); 
            $this->Cell(40, 8, 'HISTOPATHOLOGY REPORT', 0, 1, 'C', false);
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
                $leftFooterContent = '' . $currentDateTime;
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
                
                $leftFooterContent = $currentDateTime;
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
$pdf = new CustomPDF($lab_number, $invoice_value, $code_client, true, 'UTF-8', false);

// $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// Set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// Set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, 45, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);

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

// SQL operation for Site Of Specimen
$Site_Of_Specimen = "SELECT rowid, lab_number, site_of_specimen AS specimen 
                     FROM llx_other_report_site_of_specimen 
                     WHERE lab_number = '$LabNumber'";

$Site_Of_Specimen_result = pg_query($pg_con, $Site_Of_Specimen);

// Check if the query executed successfully
if (!$Site_Of_Specimen_result) {
    die("Error in SQL query: " . pg_last_error());
}

// Prepare clinical details
$clinical_details_info = "SELECT clinical_details FROM llx_other_report_clinical_details WHERE lab_number = '$LabNumber'";
$clinical_details_result = pg_query($pg_con, $clinical_details_info);

// Check if the query was successful
if (!$clinical_details_result) {
    die("Query failed for clinical_details: " . pg_last_error());
}

// Prepare addressing details
$addressing_details_info = "SELECT addressing FROM llx_other_report_clinical_details WHERE lab_number = '$LabNumber'";
$addressing_details_result = pg_query($pg_con, $addressing_details_info);

if (!$addressing_details_result) {
    die("Query failed for addressing_details: " . pg_last_error());
}

// SQL operation for dynamic data 
$fk_gross_id = "SELECT gross_id FROM llx_gross WHERE lab_number = '$LabNumber'";
$fk_gross_id_result = pg_query($pg_con, $fk_gross_id);

// Check if the query was successful
if ($fk_gross_id_result) {
    // Fetch the results (if any)
    while ($row = pg_fetch_assoc($fk_gross_id_result)) {
        // Process each row as needed
        $fk_gross_id = $row['gross_id'];
        // Store the patient information in a session variable for later use
        $_SESSION['fk_gross_id'] = $fk_gross_id;
    }
} else {
    // Handle query error
    die("Query failed for gross ID Information: " . pg_last_error());
}

// Prepare gross description
$gross_description = "SELECT specimen_id, specimen, gross_description FROM llx_other_report_gross_specimen  WHERE lab_number = '$LabNumber' ORDER BY specimen_id ASC";
$gross_description_result = pg_query($pg_con, $gross_description);

// Check if the query was successful
if (!$gross_description_result) {
    die("Query failed for gross_description: " . pg_last_error());
}


// SQL operation for dynamic data
$micro_details_info = "SELECT description, specimen FROM llx_other_report_micro WHERE lab_number = '$LabNumber' ORDER BY rowid ASC";
$micro_details_result = pg_query($pg_con, $micro_details_info);

// Check if the query was successful
if (!$micro_details_result) {
    die("Query failed for micro_description: " . pg_last_error());
}

// SQL operation for dynamic data
$diagnosis_details_info  = "SELECT  description, specimen, title, comment FROM llx_other_report_diagnosis WHERE lab_number = '$LabNumber' ORDER BY rowid ASC";
$diagnosis_details_result = pg_query($pg_con, $diagnosis_details_info);

// Check if the query was successful
if (!$diagnosis_details_result) {
    die("Query failed for diagnosis_description: " . pg_last_error());
}

// Fetch section information from the database
$sections_info = "SELECT gross_specimen_section_id, fk_gross_id, section_code, specimen_section_description, cassettes_numbers FROM llx_other_report_gross_specimen_section WHERE fk_gross_id = $1  ORDER BY 
LEFT(section_code, 1) ASC, 
CAST(SUBSTRING(section_code, 2) AS INTEGER) ASC, 
gross_specimen_section_id ASC";

$stmt = pg_prepare($pg_con, "sections_info_query", $sections_info);

$sections_info_result = pg_execute($pg_con, "sections_info_query", array($fk_gross_id));

// Check if the query was successful
if ($sections_info_result) {
    // Fetch the results (if any)
    while ($row = pg_fetch_assoc($sections_info_result)) {
        // Store section code and description pairs
        $section_code = $row['section_code'];
        $specimen_section_description = $row['specimen_section_description'];
        $section_pairs[$section_code] = $specimen_section_description;
    }

    // Store the section information in session variables for later use
    $_SESSION['section_pairs'] = $section_pairs;
} else {
    // Handle query error
    die("Query failed for sections_info: " . pg_last_error());
}

// Initialize an array to hold sections grouped by specimen letters
$grouped_sections = array();

// Group sections by specimen letters
foreach ($section_pairs as $section_code => $specimen_section_description) {
    $specimen_letter = substr($section_code, 0, 1);
    $grouped_sections[$specimen_letter][$section_code] = $specimen_section_description;
}

// Sort sections within each group based on section numbers & use sort
foreach ($grouped_sections as &$group) {
    uksort($group, function($a, $b) {
        // Extract section numbers
        $num_a = intval(substr($a, 1));
        $num_b = intval(substr($b, 1));
        // Compare section numbers
        return $num_a - $num_b;
    });
}

// Construct the section code and description list
$section_code_list = '';

// Iterate over grouped sections to generate the list
foreach ($grouped_sections as $specimen_letter => $sections) {
    $section_code_list .= "";
    foreach ($sections as $section_code => $specimen_section_description) {
        $section_code_list .= "<li>$section_code: $specimen_section_description</li>";
    }
    $section_code_list .= "";
}

// Remove the trailing comma and space
$section_code_list = rtrim($section_code_list, ', ');
$currentDateTime = date('Y-m-d');

// Fetch report_type from the database based on LabNumber
$report_type_query = "SELECT report_type FROM llx_other_report WHERE previous_lab_number = '$LabNumber' ORDER BY rowid DESC LIMIT 1";
$report_type_result = pg_query($pg_con, $report_type_query);

// Check if the query was successful
if (!$report_type_result) {
    die("Query failed for report_type: " . pg_last_error());
}

// Fetch the report_type value from the query result
$report_type_row = pg_fetch_assoc($report_type_result);
$report_type = $report_type_row['report_type'];  // Get the dynamic report type

// Get the current date and time
$currentDateTime = date("d F, Y");  // You can adjust this format as needed

// Only add the HTML line if report_type is NOT "Correction of Report"
if (strcasecmp($report_type, "Correction of Report") !== 0) {
    if (strcasecmp($report_type, "Internal Histopathology Review") === 0) {
        $html = '<h4 align="center">(Review Report: Dated ' . $currentDateTime . ')</h4><br>';
    } else {
        $html = '<h4 align="center">(' . htmlspecialchars($report_type) . ': Dated ' . $currentDateTime . ')</h4><br>';
    }
    $pdf->writeHTML($html, true, false, true, false, '');
}


// Initialize content for the HTML table
$html = '
<table border="0" cellspacing="0" cellpadding="4" style="width:100%; table-layout: fixed;">';

// Add Addressing
$addressing_details_rows = [];
while ($row = pg_fetch_assoc($addressing_details_result)) {
    if (!empty($row['addressing'])) {
        $addressing = $row['addressing'];

        // Decode HTML entities (if stored as encoded)
        $addressing = html_entity_decode($addressing);

        // Replace <p> tags with <br> for proper formatting
        $addressing = preg_replace('/<p[^>]*>(.*?)<\/p>/i', '$1<br>', $addressing);

        // Normalize <br> tags: collapse multiple <br> into a single <br>
        $addressing = preg_replace('/(<br\s*\/?>\s*)+/', '<br>', $addressing);

        // Remove trailing <br> tags if they don't precede text
        $addressing = preg_replace('/<br>\s*$/', '', $addressing);

        // Trim leading/trailing spaces
        $addressing = trim($addressing);

        $addressing_details_rows[] = $addressing;
    }
}

// Show the "Addressing" row only if there are valid values
if (!empty($addressing_details_rows)) {
    $html .= '<tr>
                <th style="width: 26%;"><b>Addressing:</b></th>
                <td style="width: 74%;">' . implode('<br>', $addressing_details_rows) . '</td>
              </tr>';
}

// Add Site Of Specimen
$html .= '<tr>
            <th style="width: 26%;"><b>Site Of Specimen:</b></th>
            <td style="width: 74%;">';
$specimen_rows = [];
while ($row = pg_fetch_assoc($Site_Of_Specimen_result)) {
    $specimen_rows[] = htmlspecialchars($row['specimen']);
}
$html .= implode('<br/>', $specimen_rows);
$html .= '</td></tr>';

// Add Clinical Details
$html .= '<tr>
            <th style="width: 26%;"><b>Clinical Details:</b></th>
            <td style="width: 74%;">';

$clinical_details_rows = [];
while ($row = pg_fetch_assoc($clinical_details_result)) {
    if (!empty($row['clinical_details'])) {
        $clinical_details = $row['clinical_details'];

        // Decode HTML entities if stored as encoded
        $clinical_details = html_entity_decode($clinical_details);

        // Replace <p> tags with <br> for proper formatting
        $clinical_details = preg_replace('/<p[^>]*>(.*?)<\/p>/i', '$1<br>', $clinical_details);

        // Normalize multiple <br> tags into a single <br>
        $clinical_details = preg_replace('/(<br\s*\/?>\s*)+/', '<br>', $clinical_details);

        // Remove trailing <br> tags
        $clinical_details = preg_replace('/<br>\s*$/', '', $clinical_details);

        // Trim spaces
        $clinical_details = trim($clinical_details);

        $clinical_details_rows[] = $clinical_details;
    }
}

$html .= implode('<br>', $clinical_details_rows);
$html .= '</td></tr>';


// Add Gross Description
$html .= '<tr>
            <th style="width: 26%;"><b>Gross Description:</b></th>
            <td style="width: 74%;">';
$gross_description_rows = [];
while ($row = pg_fetch_assoc($gross_description_result)) {
    // Use the raw content from the database for HTML rendering
    $specimen = $row['specimen'];
    $description = $row['gross_description'];

    // Normalize <br> tags: collapse multiple <br> to a single <br>
    $description = preg_replace('/(<br\s*\/?>\s*)+/', '<br>', $description);

    // Handle <p> tags: replace <p> with <br> only if the content isn't just whitespace
    $description = preg_replace('/<p[^>]*>(.*?)<\/p>/', '$1<br>', $description);

    // Remove trailing <br> tags if they don't precede text
    $description = preg_replace('/<br>\s*$/', '', $description);

    // Trim to remove leading and trailing whitespace
    $description = trim($description);


    // Allow specific HTML rendering
    $gross_description_rows[] = "<strong>$specimen:</strong> $description"; // This will render <strong> tag correctly
}
$html .= implode('<br/>', $gross_description_rows);
$html .= '</td></tr>';

// Add Section Codes and Descriptions
$html .= '<tr>
            <th style="width: 26%;"></th>
            <td style="width: 74%;">';
// Here, we add the $section_code_list to the HTML content
$html .=  '<b>Section Code</b> :'.''.  $section_code_list;
$html .= '</td></tr>';

// Add Micro Description
$html .= '<tr>
            <th style="width: 26%;"><b>Microscopic Description:</b></th>
            <td style="width: 74%;">';

$micro_description_rows = [];
while ($row = pg_fetch_assoc($micro_details_result)) {
    $specimen = htmlspecialchars($row['specimen']);
    $description = $row['description'];
    // Normalize <br> tags: collapse multiple <br> to a single <br>
    $description = preg_replace('/(<br\s*\/?>\s*)+/', '<br>', $description);

    // Handle <p> tags: replace <p> with <br> only if the content isn't just whitespace
    $description = preg_replace('/<p[^>]*>(.*?)<\/p>/', '$1<br>', $description);

    // Remove trailing <br> tags if they don't precede text
    $description = preg_replace('/<br>\s*$/', '', $description);

    // Trim to remove leading and trailing whitespace
    $description = trim($description);

    $micro_description_rows[] = "<strong>$specimen:</strong> $description";
}
$html .= implode('<br/>', $micro_description_rows);
$html .= '</td></tr>';

// Add Diagnosis Description
$html .= '<tr>
            <th style="width: 26%;"><b>Diagnosis/Conclusion:</b></th>
            <td style="width: 74%;">';

$diagnosis_description_rows = [];
while ($row = pg_fetch_assoc($diagnosis_details_result)) {
    $specimen = htmlspecialchars($row['specimen']);
    $title = $row['title'];
    $description = $row['description'];
    $comment = $row['comment'];
    // Normalize <br> tags: collapse multiple <br> to a single <br>
    $description = preg_replace('/(<br\s*\/?>\s*)+/', '<br>', $description);

    // Handle <p> tags: replace <p> with <br> only if the content isn't just whitespace
    $description = preg_replace('/<p[^>]*>(.*?)<\/p>/', '$1<br>', $description);

    // Remove trailing <br> tags if they don't precede text
    $description = preg_replace('/<br>\s*$/', '', $description);

    // Trim to remove leading and trailing whitespace
    $description = trim($description);

    // Check if the comment is empty
    if (empty($comment)) {
        // Comment is empty, so don't include it
        $diagnosis_description_rows[] = "<strong>$specimen,</strong>&nbsp;&nbsp;<strong>$title:</strong><br>" . $description;
    } else {
        // Comment is not empty, include it with formatting
        $diagnosis_description_rows[] = "<strong>$specimen,</strong>&nbsp;&nbsp;<strong>$title:</strong><br>" . $description . "<strong>Comment : </strong>" . $comment;
    }
}

// Join the rows with a line break
$html .= implode('<br/>', $diagnosis_description_rows);
$html .= '</td></tr>';

$html .= '</table>';


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
INNER JOIN llx_duplicate_report_doctor_assisted AS ds ON dd.username = ds.doctor_username
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
                            INNER JOIN llx_duplicate_report_doctor_finalized_by_signature AS ds ON dd.username = ds.doctor_username
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
// $pdf->writeHTML($signaturesTableHTML, true, false, false, false, '');

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