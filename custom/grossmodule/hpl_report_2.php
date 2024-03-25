<?php
// Include TCPDF library
require_once('TCPDF/tcpdf.php');

// Create a new TCPDF instance
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

$pdf->setPrintHeader(false);
$pdf->setFooterData(array(0,64,0), array(0,64,128));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
$pdf->setBarcode(date('Y-m-d H:i:s'));
// Add a page
$pdf->AddPage();
$pdf->setMargins(10, 20, 10);
// Set font
$pdf->SetFont('helvetica', '', 12);



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
        h1 {
            text-align: center;
        }

        .key {
            display: inline-block;
            text-align: left;
            font-weight: bold;
            width: 100px; 
        }
        
        .value {
            display: inline-block;
            width: auto; 
            text-align: left;
            
        }
        
        
    </style>
    <br><br>
 
        <h1>HISTOPATHOLOGY REPORT</h1>
   
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
    </table>
    <div>
    <br><br>
    <table style="border: none; width: auto; ">
    <tr>
        <td class="key">Specimen</td>
        <td><span class="value">: Right breast with axillary lymph node. Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in 
        the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and 
        more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.</span></td>
        
    </tr>
    <tr>
        <td class="key">Clinical Details</td>
        <td>
        <span class="value">: Carcinoma right breast. Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of 
        Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.
        </span>
        </td>
    </tr>
    <tr>
        <td class="key">Gross</td>
        <td>
        <span class="value">: Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry standard dummy text ever since the 1500s, 
        when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, 
        but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets 
        containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.
        </span>
        <br>
            <table style="border: none;">
                <tr>
                    <td class="key">Section Code</td>
                    <td>
                    <span class="value">: A1-A2: Sections from the</span>
                    </td>
                </tr>
                <tr>
                    <td style="width: 130px; font-weight: bold;">Summary Of Sections</td>
                    <td>
                    <span class="value">:Two pieces embedded in two blocks.</span>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <br>
    <tr>
        <td class="key">Micro</td>
        <td>
        <span class="value">: Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry standard dummy text ever since the 1500s,
        when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with 
        the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.
        </span>
        </td>
    </tr>
    <tr>
        <td class="key">Diagnosis</td>
        <td>
        <span class="value">: Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry standard dummy text ever since the 1500s, 
        when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s 
        with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.
        </span>
        </td>
    </tr>
</table>

    </div>
    <div class="">
    <div class="">
        <p><strong>Assisted by:</strong> Dr.Md.Mahabub Alam</p>
        <p><strong>Qualification:</strong> MBBS, MD(Pathology, BSMMU)</p>
        <p><strong>Position:</strong> Junior Consultant, A I Khan Lab Ltd</p>
    </div>
    <div class="">
        <p><strong>Finalized by:</strong> Prof. Dr. Md. Aminul Islam Khan</p>
        <p><strong>Qualification:</strong> MBBS (DMC), Board Certified in Pathology</p>
        <p><strong>Position:</strong> Chief Consultant, A I Khan Lab Ltd.</p>
    </div>
    
</div>


';

// EAN 13
$pdf->Cell(0, 0, '', 0, 1);
$pdf->write1DBarcode('1234567890128', 'EAN13', '', '', '', 18, 0.4, $style, 'N');

$pdf->Ln();


// CODE 11
$pdf->Cell(0, 0, 'CODE 11', 0, 1);
$pdf->write1DBarcode('123-456-789', 'CODE11', '', '', '', 18, 0.4, $style, 'N');

$pdf->Ln();

// PHARMACODE
$pdf->Cell(0, 0, 'PHARMACODE', 0, 1);
$pdf->write1DBarcode('789', 'PHARMA', '', '', '', 18, 0.4, $style, 'N');

$pdf->Ln();

// PHARMACODE TWO-TRACKS
$pdf->Cell(0, 0, 'PHARMACODE TWO-TRACKS', 0, 1);
$pdf->write1DBarcode('105', 'PHARMA2T', '', '', '', 18, 2, $style, 'N');

// Write HTML content to PDF
$pdf->writeHTML($htmlContent, true, false, true, false, '');

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
