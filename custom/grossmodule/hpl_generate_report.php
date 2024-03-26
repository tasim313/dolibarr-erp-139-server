<?php
// Include TCPDF library
require_once('TCPDF/tcpdf.php');

// Extend TCPDF class to customize functionality
class MYPDF extends TCPDF {

    public function MultiRow($left, $right) {
        // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0)
        // Custom function to display two cells in a row
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

// Set document information
$pdf->SetCreator('SETUPORG');
$pdf->SetAuthor('SETUPORG');
$pdf->SetTitle('PRODUCT LIST');
$pdf->SetSubject('DISPLAY');
$pdf->SetKeywords('PRODUCT, DISPLAY, SETUPORG');

// Set default header and footer data
$pdf->setPrintHeader(false);
$pdf->setFooterData(array(0, 64, 0), array(0, 64, 128));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// Add a page
$pdf->AddPage();
$pdf->setMargins(10, 20, 10);

// Set font
$pdf->SetFont('helvetica', '', 12);

function generateBarcode($code, $type = 'C39') {
    // Include TCPDF library
    require_once('TCPDF/tcpdf.php');

    // Create a new TCPDF instance
    $pdf = new TCPDF();

    // Start buffering
    ob_start();

    // Generate barcode
    $pdf->write1DBarcode($code, $type, '', '', '', 18, 0.4, array(0, 0, 0), 'N');

    // Get buffer contents
    $barcodeImageData = ob_get_clean();

    return $barcodeImageData;
}

// Usage example:
$barcode1 = generateBarcode("2402-03393", "C39"); // Generate barcode image data for the first barcode
$barcode2 = generateBarcode("1234-56789", "C39");

// HTML content with the table and header
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
        h1 {
            text-align: left;
            font-style: italic;
            font-size: 12px;
            font-family: "Times New Roman", Times, serif;
            margin: 0;
            padding: 0;
            display: inline;
        }

        .barcode-container {
            display: inline-block;
            margin-left: 10px;
        }
    </style>

    <div>
        <h1>HISTOPATHOLOGY REPORT</h1>
        <div class="barcode-container">
            ' . $pdf->write1DBarcode('HPL2402-03393', 'C39', '', '', '', 18, 0.4, array(0, 0, 0), 'N') . '
        </div>
        <div class="barcode-container">
            ' . $pdf->write1DBarcode('2212-45226', 'C39', '', '', '', 18, 0.4, array(0, 0, 0), 'N') . '
        </div>
    </div>
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
    </table>';

// Write HTML content to PDF
$pdf->writeHTML($htmlContent, true, false, true, false, '');
// Calculate the width for the left and right content in the MultiCell
$leftWidth = 40; // Adjust as needed
$rightWidth = 40; // Adjust as needed

// Calculate the remaining width for the barcodes
$barcodeWidth = 100 - $leftWidth - $rightWidth; // Adjust as needed

// Generate barcode HTML for the left barcode
$leftBarcodeHTML = $pdf->write1DBarcode("2402-03393", "EAN13", '', '', '', 18, 0.4, $style, "N");

// Generate barcode HTML for the right barcode
$rightBarcodeHTML = $pdf->write1DBarcode("1234-56789", "EAN13", '', '', '', 18, 0.4, $style, "N");

// Calculate the height of the MultiCell based on the tallest barcode
$barcodeHeight = max($leftBarcodeHTML['h'], $rightBarcodeHTML['h']);

// Set the height of the MultiCell to accommodate the barcodes
$pdf->SetCellHeightRatio($barcodeHeight / 4); // Adjust the denominator as needed

// Start the MultiCell
$pdf->MultiCell($leftWidth + $barcodeWidth + $rightWidth, $barcodeHeight, '', 1, 'C', 0, 0, '', '', true, 0, false, true, $barcodeHeight, 'M');

// Set the position for the left barcode
$pdf->SetXY($pdf->GetX() + $leftWidth, $pdf->GetY() - $barcodeHeight);

// Output the left barcode
$pdf->write1DBarcode("2402-03393", "EAN13", '', '', '', 18, 0.4, $style, "N");

// Set the position for the right barcode
$pdf->SetXY($pdf->GetX() + $barcodeWidth, $pdf->GetY());

// Output the right barcode
$pdf->write1DBarcode("1234-56789", "EAN13", '', '', '', 18, 0.4, $style, "N");

// Reset the height of the MultiCell to its default value
$pdf->setCellHeightRatio(1);

// Set the position for the content
$pdf->SetXY($pdf->GetX() - $leftWidth - $barcodeWidth, $pdf->GetY() + $barcodeHeight);

// Write the content for the left part of the MultiCell
$pdf->MultiCell($leftWidth, 0, 'Left Content', 0, 'C');

// Set the position for the content
$pdf->SetXY($pdf->GetX() + $leftWidth + $barcodeWidth, $pdf->GetY());

// Write the content for the right part of the MultiCell
$pdf->MultiCell($rightWidth, 0, 'Right Content', 0, 'C');
// Output the PDF
$pdf->Output('histopathology_report.pdf', 'I');
