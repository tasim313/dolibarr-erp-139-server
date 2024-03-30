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
        $leftFooterContent = 'Tasim |' . $currentDateTime;
        
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
      
    // public function Footer() {
    //     // Position at 15 mm from bottom
    //     $this->SetY(-30);
        
    //     // Set font for the footer content
    //     $this->SetFont('helvetica', '', 8);
        
    //     // Add spacing between lines
    //     $this->Ln(5);
        
    //     // Get the current date/time
    //     $currentDateTime = date('Y-m-d H:i:s');
        
    //     // Lab number
    //     $labNumber = 'HPL2402-03393';
        
    //     // Construct the footer content string for left side
    //     $leftFooterContent = 'Tasim |' . $currentDateTime;
        
    //     // Get the page number information
    //     $pageNumberContent = 'Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages();
        
    //     // Calculate the width of the middle section
    //     $middleWidth = $this->GetStringWidth($labNumber);
        
    //     // Calculate the width of the left section
    //     $leftWidth = $this->GetStringWidth($leftFooterContent);
        
    //     // Calculate the width of the right section (to align with the right margin)
    //     $rightWidth = $this->GetStringWidth($pageNumberContent);
        
    //     // Add the left footer content
    //     $this->Cell($leftWidth, 5, $leftFooterContent, 0, 0, 'L');
        
    //     // Add the lab number in the middle
    //     $this->Cell($middleWidth, 5, $labNumber, 0, 0, 'C');
        
    //     // Add the QR code including the lab number
    //     $qrCodeContent = $labNumber; // Include lab number in QR code content
    //     $this->write2DBarcode($qrCodeContent, 'QRCODE,L', '', '', 50, 50, $style, 'N');
        
    //     // Add the page number on the right side
    //     $this->Cell($rightWidth, 5, $pageNumberContent, 0, 1, 'R');
    // }
    
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
            <td><strong>Patient Name:</strong><span> Ms. Ambia Begum </span></td>
            <td><strong>Date Of Birth:</strong><span> 05/02/2024</span></td>
        </tr>
        <tr>
        <td><strong>Age:</strong><span> 15 Yrs</span></td>
        <td><strong>Gender:</strong><span> Female</span></td>
        </tr>
        <tr>
            <td><strong>Refd. by:</strong><span> Prof. Dr. Md. Mohiuddin Matubber</span></td>
            <td><strong>Refd. From:</strong><span> Module General Hospital, Dhaka</span></td>
        </tr>
        <tr>
            <td><strong>Received on:</strong><span> 05/02/2024 11:58 AM</span></td>
            <td><strong>Reported on:</strong><span> 05/02/2024 11:58 AM</span></td>
        </tr>
        <tr>
        
        </tr>
    </table>
    
';

$verticalOffset = 22;

// Add spacing before the barcode
$pdf->Ln(40); 

// Generate and output the barcode HTML
$leftBarcodeHTML = $pdf->write1DBarcode("11240203393", "EAN13", '', '', '', 12, 0.4, $style, "N");
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
$secondBarcodeHTML = $pdf->write1DBarcode("01124100002", "EAN13", '', '', '', 12, 0.4, $style, "N");
$pdf->writeHTMLCell(0, 0, '', '', $secondBarcodeHTML, 0, 1, false, true, 'C', true);

// Write HTML content to PDF
$pdf->writeHTML($htmlContent, true, false, true, false, '');
$tbl = <<<EOD
<table border="0" cellpadding="2" cellspacing="2" nobr="true">
 
 <tr>
  <td style="text-align: left; font-weight: bold; width: 25%;">Site Of Specimen:</td>
  <td style="text-align: left; width: 70%;">Left breast with axillary tail
  </td>
 </tr>
 <tr>
  <td style="text-align: left; font-weight: bold; width: 25%;">Clinical Details:</td>
  <td style="text-align: left; width: 70%;">Carcinoma breast, left 
  </td>
  
 </tr>
 
</table>
EOD;

$pdf->writeHTML($tbl, true, false, false, false, '');

$tbl = <<<EOD
<table border="0" cellpadding="1" cellspacing="1" nobr="true">
 <tr>
  <td style="text-align: left; font-weight: bold; width: 25%;">Gross Description:</td>
  <td style="text-align: left; width: 70%;">Specimen received in formalin in a container with name, age and sample name: 
    Specimen consists of left breast with overlying skin with nipple and areola with axillary tail. Specimen measuring: 22x10x6 cm. Overlying skin measuring: 16x9 cm. Cut surface shows a grayish-white and grayish-yellowish nodular area measuring: 2.5x2 cm at 12.0 to 1.0 clock position at upper outer quadrant. It is 4 cm from nipple, 3 cm from deep resection margin and 2 cm from superior soft tissue resection margin. 
  <h4>Section Code</h4>
  <p><li>A1-A3: Sections from the grayish-white area </li><li>A4: Sections from the deep resection margin</li>
  <li>A5: Sections from the skin with superior soft tissue resection margin</li>
  <li>A6: Sections from the areola</li>
  <li>A7: Sections from the nipple</li>
  <li>A11-A12: Sections from the lymph nodes</li>
  </p>
  <h4>Summary Of Sections</h4>
  <p>Multiple pieces embedded in twelve blocks</p>
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
  <td style=" width: 28%;">MBBS, MD(Pathology, BSMMU)<br/>Junior Consultant, A I Khan Lab Ltd</td>
 
  <td style=" width: 111%;">MBBS (DMC), Board Certified in Pathology<br/>Chief Consultant, A I Khan Lab Ltd.</td>
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
