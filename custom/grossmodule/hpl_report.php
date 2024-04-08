<?php
// Include TCPDF library
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


$LabNumber = $_GET['lab_number'];
$LabNumberWithoutPrefix = str_replace(["HPL", "-HPL"], "", $LabNumber);
$lab_number = str_replace("-", "", $LabNumberWithoutPrefix);

class MYPDF extends TCPDF {

    public function MultiRow($left, $right) {
        // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0)

        $page_start = $this->getPage();
        $y_start = $this->GetY();

        // write the left cell
        $this->MultiCell(40, 0, $left, 1, 'R', 1, 2, '', '', true, 0);

        $page_end_1 = $this->getPage();
        $y_end_1 = $this->GetY();

        $this->setPage($page_start);

        // write the right cell
        $this->MultiCell(0, 0, $right, 1, 'J', 0, 1, $this->GetX(), $y_start, true, 0);

        $page_end_2 = $this->getPage();
        $y_end_2 = $this->GetY();

        // set the new row position by case
        if (max($page_end_1,$page_end_2) == $page_start) {
            $ynew = max($y_end_1, $y_end_2);
        } elseif ($page_end_1 == $page_end_2) {
            $ynew = max($y_end_1, $y_end_2);
        } elseif ($page_end_1 > $page_end_2) {
            $ynew = $y_end_1;
        } else {
            $ynew = $y_end_2;
        }

        $this->setPage(max($page_end_1,$page_end_2));
        $this->SetXY($this->GetX(),$ynew);
    }

      
    public function Footer() {
        // Position at 15 mm from bottom
        $this->SetY(-30);
    
        // Set font for the footer content
        $this->SetFont('helvetica', '', 8);
    
        // Add spacing between lines
        $this->Ln(5);
    
        // Get the current date/time
        $currentDateTime = date('Y-m-d H:i:s');
    
        // Construct the footer content string for left side
        $leftFooterContent = 'Tasim | ' . $currentDateTime;
    
        // Get the page number information
        $pageNumberContent = 'Page ' . $this->getAliasNumPage() . ' Of ' . $this->getAliasNbPages();
    
        // Calculate the width of the left section
        $leftWidth = $this->GetStringWidth($leftFooterContent);
    
        // Calculate the width of the right section (to align with the right margin)
        $rightWidth = $this->GetStringWidth($pageNumberContent);
    
        // Add the left footer content
        $this->Cell($leftWidth, 5, $leftFooterContent, 0, 0, 'L');
    
        // Add spacing between sections
        $this->Cell(($this->w - $rightWidth - $leftWidth - 10));
    
        // Add the page number on the right side
        $this->Cell($rightWidth, 5, $pageNumberContent, 0, 1, 'R');
    }
      
}


// Create a new TCPDF instance
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('A I KHAN LAB LTD');
$pdf->SetAuthor('A I KHAN LAB LTD');
$pdf->SetTitle('HISTOPATHOLOGY REPORT');
$pdf->SetSubject('DISPLAY');
$pdf->SetKeywords('HISTOPATHOLOGY REPORT, DISPLAY, A I KHAN LAB LTD');

$pdf->setPrintHeader(false);
$pdf->setFooterData(array(0,64,0), array(0,64,128));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

$pdf->SetFooterMargin(0);

// Add a page
$pdf->AddPage();
$pdf->setMargins(10, 40, 10);
// Set font
$pdf->SetFont('helvetica', '', 10);
$pdf->setPrintFooter(true);

// Define barcode style
$style = array(
    'position' => '',
    'align' => 'C',
    'stretch' => false,
    'fitwidth' => true,
    'cellfitalign' => '',
    'border' => false,
    'hpadding' => 'auto',
    'vpadding' => 'auto',
    'fgcolor' => array(0, 0, 0),
    'bgcolor' => false, //array(255,255,255),
    'text' => true,
    'font' => 'helvetica',
    'fontsize' => 8,
    'stretchtext' => 4
);

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

$date_information = "SELECT 
c.date_commande AS date, 
c.date_livraison AS delivery_date, 
e.referredfrom AS Refd_hospital, 
e.referredby_dr AS refd_doctor 
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
        $Refd_hospital = $row['Refd_hospital'];
        $refd_doctor = $row['refd_doctor'];
        
        // Store the patient information in a session variable for later use
        $_SESSION['date'] = $date;
        $_SESSION['delivery_date_12h'] = $delivery_date_12h ;
        $_SESSION['Refd_hospital'] = $Refd_hospital;
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

// Define the content of the PDF document

$htmlContent = '
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid black;
            padding: 2px;
            text-align: left;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        
    </style>
    
    <table>
        <tr>
            <td><strong>Patient Name: </strong><span>'.$nom.' </span></td>
            <td><strong>Date Of Birth: </strong><span> '.$date_of_birth.'</span></td>
        </tr>
        <tr>
        <td><strong>Age: </strong><span> '.$ageyrs.'</span></td>
        <td><strong>Gender: </strong><span>' . $gender . '</span></td>
        </tr>
        <tr>
            <td><strong>Refd. by: </strong><span> Prof. Dr. Md. Mohiuddin Matubber</span></td>
            <td><strong>Refd. From: </strong><span> Module General Hospital, Dhaka</span></td>
        </tr>
        <tr>
            <td><strong>Received on: </strong><span>'.$date.'</span></td>
            <td><strong>Reported on: </strong><span>'.$delivery_date_12h.'</span></td>
        </tr>
        <tr>
        
        </tr>
    </table>
    
';


// sql opertaion for dynamic data 

$invoice_number = "SELECT f.ref AS invoice  FROM llx_facture AS f  JOIN llx_societe s ON f.fk_soc = s.rowid  JOIN llx_commande AS c ON c.fk_soc = s.rowid 
WHERE c.ref = '$LabNumberWithoutPrefix'";

$invoice_result = pg_query($pg_con, $invoice_number);

// Check if the query was successful
if ($invoice_result) {
    // Fetch the results (if any)
    while ($row = pg_fetch_assoc($invoice_result)) {
        // Process each row as needed
        $invoice = $row['invoice'];
        // Extract the invoice number and remove "SI" prefix and "-"
        $invoice_value = str_replace(['SI', '-'], '', $invoice);
        
        // Store the invoice number in a session variable for later use
        $_SESSION['invoice_value'] = $invoice_value;
    }
} else {
    // Handle query error
    die("Query failed: " . pg_last_error());
}


// End of Sql opertaion

$verticalOffset = 22;

// Add spacing before the barcode
$pdf->Ln(40); 

// Generate and output the barcode HTML
$leftBarcodeHTML = $pdf->write1DBarcode("11$lab_number", "EAN13", '', '', '', 12, 0.4, $style, "N");
$pdf->writeHTMLCell(0, 0, '', '', $leftBarcodeHTML, 0, 1, false, true, 'C', true);

// Calculate the Y position for the h1 tag
$h1Y = $pdf->GetY() - $verticalOffset;

// Add the h1 tag
$pdf->writeHTMLCell(0, 0, '', $h1Y, '<h1 style="text-align: center; font-style: bold; font-size: 14px; font-family: "URW Chancery L", cursive;">HISTOPATHOLOGY REPORT</h1>', 0, 1, false, true, 'C', true);

// Update the current Y position
$currentY = $pdf->GetY();

$secondBarcodeX = 150; // Adjust the X position as needed

// Add spacing before the second barcode
$pdf->SetXY($secondBarcodeX, $currentY);

// Generate and output the second barcode HTML
$secondBarcodeHTML = $pdf->write1DBarcode("011$invoice_value", "EAN13", '', '', '', 12, 0.4, $style, "N");
$pdf->writeHTMLCell(0, 0, '', '', $secondBarcodeHTML, 0, 1, false, true, 'C', true);

// sql opertaion for dynamic data 
$Site_Of_Specimen = "SELECT de.fk_commande, de.fk_product, de.description as specimen,  c.ref, e.num_containers,
(
    SELECT COUNT(*) 
    FROM llx_commandedet AS inner_de 
    WHERE inner_de.fk_commande = c.rowid
) AS number_of_specimens
FROM 
llx_commande AS c 
JOIN 
llx_commandedet AS de ON de.fk_commande = c.rowid
JOIN 
llx_commande_extrafields AS e ON e.fk_object = c.rowid
WHERE 
c.ref = '$LabNumberWithoutPrefix'";

$Site_Of_Specimen_result = pg_query($pg_con, $Site_Of_Specimen);
// Check if the query was successful
if ($Site_Of_Specimen_result) {
    // Fetch the results (if any)
    while ($row = pg_fetch_assoc($Site_Of_Specimen_result)) {
        // Process each row as needed
        $specimen= $row['specimen'];
        $specimen_list .= $specimen . "<br>";
        
        // Store the patient information in a session variable for later use
        $_SESSION['specimen_list'] = $specimen_list;
    }
} else {
    // Handle query error
    die("Query failed for Site of Specimen Information: " . pg_last_error());
}

$clinical_details_info  = "select clinical_details from llx_clinical_details where lab_number = '$LabNumber'";

$clinical_details_result = pg_query($pg_con, $clinical_details_info);
// Check if the query was successful
if ($clinical_details_result) {
    // Fetch the results (if any)
    while ($row = pg_fetch_assoc($clinical_details_result)) {
        // Process each row as needed
        $clinical_details = $row['clinical_details'];
        
        // Store the patient information in a session variable for later use
        $_SESSION['clinical_details'] = $clinical_details;
    }
} else {
    // Handle query error
    die("Query failed for clinical_details: " . pg_last_error());
}

// end of sql operations

// Write HTML content to PDF
$pdf->writeHTML($htmlContent, true, false, true, false, '');
$tbl = <<<EOD
<table border="0" cellpadding="2" cellspacing="2" nobr="true">
 
 <tr>
  <td style="text-align: left; font-weight: bold; width: 25%;">Site Of Specimen:</td>
  <td style="text-align: left; width: 70%;">$specimen_list
  </td>
 </tr>
 <tr>
  <td style="text-align: left; font-weight: bold; width: 25%;">Clinical Details:</td>
  <td style="text-align: left; width: 70%;">$clinical_details 
  </td>
  
 </tr>
 
</table>
EOD;

//  sql opertaion for dynamic data 
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


$gross_description = "SELECT specimen_id, specimen, gross_description FROM llx_gross_specimen WHERE fk_gross_id = '$fk_gross_id'";

$gross_description_result = pg_query($pg_con, $gross_description);
// Check if the query was successful
if ($gross_description_result) {
    // Fetch the results (if any)
    while ($row = pg_fetch_assoc($gross_description_result)) {
        // Process each row as needed
        $gross_description = $row['gross_description'];
        $gross_description_list .= $gross_description . "<br>";
        
        // Store the patient information in a session variable for later use
        $_SESSION['gross_description_list'] = $gross_description_list;
    }
} else {
    // Handle query error
    die("Query failed for gross description Information: " . pg_last_error());
}

$clinical_details_info  = "select clinical_details from llx_clinical_details where lab_number = '$LabNumber'";

$clinical_details_result = pg_query($pg_con, $clinical_details_info);
// Check if the query was successful
if ($clinical_details_result) {
    // Fetch the results (if any)
    while ($row = pg_fetch_assoc($clinical_details_result)) {
        // Process each row as needed
        $clinical_details = $row['clinical_details'];
        
        // Store the patient information in a session variable for later use
        $_SESSION['clinical_details'] = $clinical_details;
    }
} else {
    // Handle query error
    die("Query failed for clinical_details: " . pg_last_error());
}

// end of sql operations

$pdf->writeHTML($tbl, true, false, false, false, '');

$tbl = <<<EOD
<table border="0" cellpadding="1" cellspacing="1" nobr="true">
 <tr>
  <td style="text-align: left; font-weight: bold; width: 25%;">Gross Description:</td>
  <td style="text-align: left; width: 70%;">$gross_description_list 
  <h4>Section Code: </h4>
  <p><li>A1-A3: Sections from the grayish-white area </li><li>A4: Sections from the deep resection margin</li>
  <li>A5: Sections from the skin with superior soft tissue resection margin</li>
  <li>A6: Sections from the areola</li>
  <li>A7: Sections from the nipple</li>
  <li>A11-A12: Sections from the lymph nodes</li>
  </p>
  </td>
 </tr>
</table>
EOD;

$pdf->writeHTML($tbl, true, false, false, false, '');

$tbl = <<<EOD
<table border="0" cellpadding="2" cellspacing="2" nobr="true">
 <tr>
  <td style="text-align: left; font-weight: bold; width: 25%;">Micro Description:</td>
  <td style="text-align: left; width: 70%;">Sections of the breast tissue show marked hyalinization, fibrosis, infiltration of chronic inflammatory cells,   
  </td>
 </tr>
 <tr>
  <td style="text-align: left; font-weight: bold; width: 25%;">Diagnosis:</td>
  <td style="text-align: left; width: 70%;">Left breast with axillary tail, mastectomy: 
  - No residual tumor
  - Fibrosis and foreign-body giant cell reaction.
  - Complete therapeutic response to breast and lymph node.
  </td>
 </tr>
</table>
EOD;

$pdf->writeHTML($tbl, true, false, false, false, '');

$tbl = <<<EOD
<table border="0" cellpadding="1" cellspacing="1" align="center">

 <tr nobr="true">
  <td style="text-align: left; font-weight: bold; width: 35%;"><br /><br /><br/>Dr.Md.Mahabub Alam</td>
  
  <td style="text-align: right; font-weight: bold; width: 60%;"><br /><br /><br/>Prof. Dr. Md. Aminul Islam Khan</td>
 </tr>
 
 <tr nobr="true">
  <td style="text-align: left; width: 28%;">MBBS, MD(Pathology, BSMMU)<br/>Junior Consultant, A I Khan Lab Ltd</td>
 
  <td style="text-align: right; width: 74%;">MBBS (DMC), Board Certified in Pathology<br/>Chief Consultant, A I Khan Lab Ltd.</td>
 </tr>
</table>
EOD;



$pdf->writeHTML($tbl, true, false, false, false, '');

$style = array(
    'width' => 10, // Initial width value
    'height' => 10, // Initial height value
);

// Calculate bottom margin and center position
$bottomMargin = 40;
$bottomY = $pdf->getPageHeight() - $bottomMargin;

// New height and width for the QR code
$newWidth = $style['width'] * 1.5; // Increase width by 50%
$newHeight = $style['height'] * 1.5; // Increase height by 50%

$centerX = ($pdf->getPageWidth() - $newWidth) / 2; // Adjust center based on new width

// Add the QR code at the bottom center with new dimensions
$pdf->write2DBarcode('PT2402-00335', 'QRCODE,Q', $centerX, $bottomY, $newWidth, $newHeight, $style, 'N');



// Get the PDF content as a string  
$pdfContent = $pdf->Output('', 'S');

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
