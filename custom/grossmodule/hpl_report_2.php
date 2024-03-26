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

}

// Create a new TCPDF instance
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

$pdf->setPrintHeader(false);
$pdf->setFooterData(array(0,64,0), array(0,64,128));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);



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
        <br><br>
    <table>
        <tr>
            <td><strong>Lab No:</strong><span>HPL2402-03393</span></td>
            
            <td><strong>SI No:</strong><span>2212-45226</span></td>
        </tr>
        <tr>
            <td><strong>Patient Name:</strong><span>Ms. Mim</span></td>
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


$verticalOffset = 15;

// Add spacing before the barcode
$pdf->Ln(35); 

// Generate and output the barcode HTML
$leftBarcodeHTML = $pdf->write1DBarcode("2402-03393", "EAN13", '', '', '', 18, 0.4, $style, "N");
$pdf->writeHTMLCell(0, 0, '', '', $leftBarcodeHTML, 0, 1, false, true, 'C', true);

// Calculate the Y position for the h1 tag
$h1Y = $pdf->GetY() - $verticalOffset;

// Add the h1 tag
$pdf->writeHTMLCell(0, 0, '', $h1Y, '<h1 style="text-align: center; font-style: italic; font-size: 12px; font-family: "Times New Roman", Times, serif;">HISTOPATHOLOGY REPORT</h1>', 0, 1, false, true, 'C', true);

// Update the current Y position
$currentY = $pdf->GetY();

// Calculate the width for columns
$columnWidth = ($pdf->GetPageWidth() - $pdf->getMargins()['left'] - $pdf->getMargins()['right']) / 2;
$columnSpacing = 10; // Adjust as needed for the desired spacing between columns

// Calculate the width for columns
$columnWidth = ($pdf->GetPageWidth() - $pdf->getMargins()['left'] - $pdf->getMargins()['right']) / 2;
$columnSpacing = 10; // Adjust as needed for the desired spacing between columns


$tableContent = array(
    array("Specimen", " Right breast with axillary lymph node."),
    array("Clinical Details", "Carcinoma right breast. "),
    array("Gross", "Lorem Ipsum is simply dummy text of the printing",
    
    ),
    array("Section Code", "<li>A1-A2: Sections from the</li><li>A1-A2: Sections from the</li>",
    
    ),
    array("Summary Of Sections", "Two pieces embedded in two blocks.",
    
    ),
    array("Micro", " Lorem Ipsum is simply dummy text of the printing and typesetting industry."),
   
    array("Diagnosis", "Specimens A infected myomatous polyp, biopsy: Progestin effect on endometrium. Please see microscopic description"),
);



// Write HTML content to PDF
$pdf->writeHTML($htmlContent, true, false, true, false, '');


// // Find the maximum width for the left column
$maxLeftWidth = 0;
foreach ($tableContent as $row) {
    $maxLeftWidth = max($maxLeftWidth, strlen($row[0]));
}

// Find the maximum width for the right column
$maxRightWidth = 0;
foreach ($tableContent as $row) {
    $maxRightWidth = max($maxRightWidth, strlen($row[1]));
}

// Calculate the width of the left column based on the maximum content length
$leftWidth = 40 + $maxLeftWidth * 0.2; 

foreach ($tableContent as $row) {
    // Set the cell height ratio to make the content centered vertically
    $pdf->setCellHeightRatio(1.5);

    // Left cell without border
    $pdf->MultiCell($leftWidth, 0, $row[0], 0, 'R', 0, 0, '', '', true, 0, false, true, 0);

    // Check if the right cell content contains list items
    if (strpos($row[1], '<li>') !== false) {
        // Calculate the width of the right column based on the length of the content
        $rightWidth = 180 + strlen($row[1]) * 0.2; // Adjust the multiplier as needed

        // Right cell with border and HTML content
        $pdf->writeHTMLCell($rightWidth, 0, '', '', ':<br>' . $row[1], 0, 1, false, true, 'J', true);
    } else {
        // Right cell without border
        if ($row[0] === "Summary Of Sections") {
            // If it's "Summary Of Sections", set a width that allows the content to be printed on a single line
            $pdf->MultiCell(0, 0, ': ' . $row[1], 0, 'J', 0, 1, '', '', true, 0, false, true, 0);
        } else {
            // For other rows, use the default width
            $pdf->MultiCell(0, 0, ': ' . $row[1], 0, 'J', 0, 1, '', '', true, 0, false, true, 0);
        }
    }

    // Reset the cell height ratio
    $pdf->setCellHeightRatio(1);
}




// <div class="">
//         <div style="width:'.$columnWidth.'px; float:left;">
//             <p><strong>Assisted by:</strong> Dr.Md.Mahabub Alam</p>
//             <p><strong>Qualification:</strong> MBBS, MD(Pathology, BSMMU)</p>
//             <p><strong>Position:</strong> Junior Consultant, A I Khan Lab Ltd</p>
//         </div>
//         <div style="width:'.$columnWidth.'px; float:left; margin-left:'.$columnSpacing.'px;">
//             <p><strong>Finalized by:</strong> Prof. Dr. Md. Aminul Islam Khan</p>
//             <p><strong>Qualification:</strong> MBBS (DMC), Board Certified in Pathology</p>
//             <p><strong>Position:</strong> Chief Consultant, A I Khan Lab Ltd.</p>
//         </div>
//     </div>

// foreach ($tableContent as $row) {
//     // Set the cell height ratio to make the content centered vertically
//     $pdf->setCellHeightRatio(1.5);

//     // Left cell without border
//     $pdf->MultiCell(40, 0, $row[0], 0, 'R', 0, 0, '', '', true, 0, false, true, 0);

//     // Check if the right cell content contains list items
//     if (strpos($row[1], '<li>') !== false) {
//         // Right cell with border and HTML content
//         $pdf->writeHTMLCell(0, 0, '', '', ':<br>' . $row[1], 0, 1, false, true, 'J', true);
//     } else {
//         // Right cell without border
//         $pdf->MultiCell(0, 0, ': ' . $row[1], 0, 'J', 0, 1, '', '', true, 0, false, true, 0);
//     }

//     // Reset the cell height ratio
//     $pdf->setCellHeightRatio(1);
// }



    


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
