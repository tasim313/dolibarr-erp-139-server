<?php
// Include TCPDF library
require_once('TCPDF/tcpdf.php');

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
        
        // Lab number
        $labNumber = 'HPL2402-03393';
        
        // Construct the footer content string for left side
        $leftFooterContent = 'Printed By: Tasim | Printed On: ' . $currentDateTime;
        
        // Get the page number information
        $pageNumberContent = 'Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages();
        
        // Calculate the width of the middle section
        $middleWidth = $this->GetStringWidth($labNumber);
        
        // Calculate the width of the left section
        $leftWidth = $this->GetStringWidth($leftFooterContent);
        
        // Calculate the width of the right section (to align with the right margin)
        $rightWidth = $this->GetStringWidth($pageNumberContent);
        
        // Add the left footer content
        $this->Cell($leftWidth, 5, $leftFooterContent, 0, 0, 'L');
        
        // Add spacing between sections
        $this->Cell(($this->w - $rightWidth - $leftWidth - $middleWidth - 10) / 2);
        
        // Add the lab number in the middle
        $this->Cell($middleWidth, 5, $labNumber, 0, 0, 'C');
        
        // Add spacing between sections
        $this->Cell(($this->w - $rightWidth - $leftWidth - $middleWidth - 10) / 2);
        
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
$pdf->setMargins(10, 20, 10);
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
            <td><strong>Patient Name:</strong><span>Lorem Ipsum is simply dummy text of the </span></td>
            <td><strong>Patient Code:</strong><span>PT2402-00331</span></td>
        </tr>
        <tr>
        <td><strong>Age:</strong><span>15 Yrs</span></td>
        <td><strong>Gender:</strong><span>Female</span></td>
        </tr>
        <tr>
            <td><strong>Refd. by:</strong><span>Asst. Prof.Dr. Md. Nazmul Haque, MBBS, FCPS(Surgery), MS(Urology)</span></td>
            <td><strong>Refd. From:</strong><span>PAN PACIFIC HOSPITAL</span></td>
        </tr>
        <tr>
            <td><strong>Received on:</strong><span>05/02/2024 11:58 AM</span></td>
            <td><strong>Reported on:</strong><span>05/02/2024 11:58 AM</span></td>
        </tr>
        <tr>
        
        </tr>
    </table>
    
';


$verticalOffset = 22;

// Add spacing before the barcode
$pdf->Ln(35); 

// Generate and output the barcode HTML
$leftBarcodeHTML = $pdf->write1DBarcode("12345678901", "EAN13", '', '', '', 12, 0.4, $style, "N");
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
$secondBarcodeHTML = $pdf->write1DBarcode("12345678901", "EAN13", '', '', '', 12, 0.4, $style, "N");
$pdf->writeHTMLCell(0, 0, '', '', $secondBarcodeHTML, 0, 1, false, true, 'C', true);

// Write HTML content to PDF
$pdf->writeHTML($htmlContent, true, false, true, false, '');
$tbl = <<<EOD
<table border="0" cellpadding="2" cellspacing="2" nobr="true">
 
 <tr>
  <td style="text-align: center; font-weight: bold;">Specimen History:</td>
  <td>Right breast with axillary lymph node.
  </td>
 </tr>
 <tr>
  <td style="text-align: center; font-weight: bold;">Clinical Details:</td>
  <td>Carcinoma right breast.
  </td>
  
 </tr>
 
</table>
EOD;

$pdf->writeHTML($tbl, true, false, false, false, '');

$tbl = <<<EOD
<table border="0" cellpadding="2" cellspacing="2" nobr="true">
 <tr>
  <td style="text-align: center; font-weight: bold;">Gross Description:</td>
  <td>Lorem Ipsum is simply dummy text of the printing. 
  <h4>Section Code</h4>
  <p><li>A1-A2: Sections from the</li><li>A1-A2: Sections from the</li></p>
  <h4>Summary Of Sections</h4>
  <p>Two pieces embedded in two blocks.</p>
  </td>
 </tr>
</table>
EOD;

$pdf->writeHTML($tbl, true, false, false, false, '');

$tbl = <<<EOD
<table border="0" cellpadding="2" cellspacing="2" nobr="true">
 <tr>
  <td style="text-align: center; font-weight: bold;">Micro Description:</td>
  <td>Lorem Ipsum is simply dummy text of the printing. New Lorem Ipsum is simply dummy text of the printing and typesetting industry. 
  </td>
 </tr>
 <tr>
  <td style="text-align: center; font-weight: bold;">Diagnosis:</td>
  <td>Lorem Ipsum is simply dummy text of the printing. New Lorem Ipsum is simply dummy text of the printing and typesetting industry. 
  </td>
 </tr>
</table>
EOD;

$pdf->writeHTML($tbl, true, false, false, false, '');

$tbl = <<<EOD
<table border="0" cellpadding="1" cellspacing="1" align="center">

 <tr nobr="true">
  <td>Assisted by:<br /><br /><br />Dr.Md.Mahabub Alam</td>
  
  <td>Finalized by:<br /><br /><br />Prof. Dr. Md. Aminul Islam Khan</td>
 </tr>
 
 <tr nobr="true">
  <td><br />MBBS, MD(Pathology, BSMMU)<br/>Junior Consultant, A I Khan Lab Ltd</td>
 
  <td><br />MBBS (DMC), Board Certified in Pathology<br/>Chief Consultant, A I Khan Lab Ltd.</td>
 </tr>
</table>
EOD;



$pdf->writeHTML($tbl, true, false, false, false, '');

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
