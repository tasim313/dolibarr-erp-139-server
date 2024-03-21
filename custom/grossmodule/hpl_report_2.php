<?php
// Include TCPDF library
require_once('TCPDF/tcpdf.php');

// Create a new TCPDF instance
$pdf = new TCPDF('P', 'mm', 'A4');

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 12);

// Define the content of the PDF document
$htmlContent = '
<!DOCTYPE html>
<html>
<head>
    <title>HISTOPATHOLOGY REPORT</title>
    <!-- Add CSS styles here -->
</head>
<body>
    <h1>HISTOPATHOLOGY REPORT</h1>
    <table border="1" cellpadding="5">
        <tr>
            <th>Lab No:</th>
            <td>HPL2402-03393</td>
            <th>SI No:</th>
            <td>2212-45226</td>
        </tr>
        <tr>
            <th>Patient:</th>
            <td>Ms. Mim</td>
            <td >
                        <table>
                            <tr>
                                <th>Age:</th>
                                <td>15 Yrs</td>
                                <th>Sex:</th>
                                <td>Female</td>
                            </tr>
                        </table>
                    </td>
        </tr>
        <tr>
            <th>Refd. by:</th>
            <td>PAN PACIFIC HOSPITAL</td>
        </tr>
        <tr>
            <th>Specimen:</th>
            <td colspan="3">Tissue from ...</td>
        </tr>
        <tr>
            <th>Clinical Details:</th>
            <td colspan="3">[Your dynamic clinical details here]</td>
        </tr>
        <tr>
            <th>Specimen received on:</th>
            <td colspan="3">05/02/2024 11:58 AM</td>
        </tr>
        <tr>
            <th>Reported on:</th>
            <td colspan="3">[Your dynamic reported on date here]</td>
        </tr>
    </table>
    <!-- Add dynamic content here -->
</body>
</html>';

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
    <iframe src="data:application/pdf;base64,<?php echo $pdfData; ?>" style="width: 100%; height: 600px;" frameborder="0"></iframe>

    <!-- Download PDF link -->
    <a href="data:application/pdf;base64,<?php echo $pdfData; ?>" download="example.pdf">Download PDF</a>
</body>
</html>
