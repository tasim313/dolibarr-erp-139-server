<?php
// Include TCPDF library
require_once('TCPDF/tcpdf.php');

// Create a new TCPDF instance
$pdf = new TCPDF('P', 'mm', 'A4');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
// Add a page
$pdf->AddPage();
$pdf->setMargins(10, 20, 10);
// Set font
$pdf->SetFont('helvetica', '', 12);

 // Generate barcodes
 $tempDir = 'temp/'; // Create a temporary directory for barcode images
 if (!file_exists($tempDir)) {
   mkdir($tempDir, 0777, true);
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
        h1 {
            text-align: center;
        }
        .row {
            width: 100%;
    border-collapse: collapse;
    border-color: transparent; /* Set border color to transparent */
    padding: 2px;
    text-align: left;
        }
        
        
    </style>
    <br><br><br><br><br><br>
 
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
        
        <h4>Specimen:</h4>
        <p>Right breast with axillary lymph node 
            Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industrys standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.</p>
        <h4>Clinical Details:</h4>
        <p>Carcinoma right breast Lorem Ipsum is simply dummy text of the printing and typesetting industry. 
            Lorem Ipsum has been the industrys standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum. </p>

        <h4>Gross :</h4>
        <p >Lorem Ipsum is simply dummy text of the printing and typesetting industry. 
            Lorem Ipsum has been the industrys standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages,
             and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum. </p>
        <h4>Section Code :</h4>
        <p>
            <li>A1-A2: Sections from the</li>
            <li>A3-A4: Sections from the<li>
            <li>A5-A6: Sections from the</li>
            <li>A7-A8: Sections from the </li>
            <li>A9-A10: Sections from the </li>   
            <li>A11-A12: Sections from the</li>
        </p>
        <h4>Summary of sections :</h4>
        <p>Two pieces embedded in two blocks. </p>
        <h4>Micro Appearance :</h4> 
        <p >Lorem Ipsum is simply dummy text of the printing and typesetting industry. 
            Lorem Ipsum has been the industrys standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages,
             and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum. </p>
        <h4>Diagnosis :</h4>
        <p >Lorem Ipsum is simply dummy text of the printing and typesetting industry. 
            Lorem Ipsum has been the industrys standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages,
             and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum. </p>
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
