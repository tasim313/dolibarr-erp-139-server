<?php
require_once('../grossmodule/TCPDF/tcpdf.php');

// Extend the TCPDF class to create custom Header
class MYPDF extends TCPDF {
    protected $userName;
    protected $today;

    public function setUserNameAndDate($userName, $today) {
        $this->userName = $userName;
        $this->today = $today;
    }

    
    public function Header() {
        // Set the top margin (adjust the value as needed)
        $topMargin = 10; // Example: 20mm top margin
        $this->SetY($topMargin); // Move down to set the top margin
        // Set header content with userName and today's date
        $header = "Gross Ledger&nbsp;Printed by: " . $this->userName . " on " . $this->today;
    
        // Additional text for Bone D/C and Re-gross information
        $additionalInfo = "There is no information of Bone D/C and Re-gross Section Code here. If you need any information of Bone D/C and Re-gross, then visit the Bone D/C and Re-gross Tab.";
    
        // Combine the header and additional info
        $fullHeader = $header;
    
        // Write the full HTML header content to the PDF with default font size
        $this->writeHTMLCell(0, 0, '', '', $fullHeader, 0, 1, 0, true, 'C', true);
    
        // Set a smaller font size for the additional info
        $this->SetFont('helvetica', 'I', 8); // Example: change 'helvetica' to your desired font and size
    
        // Write additional information with smaller font size
        $this->writeHTMLCell(0, 0, '', '', $additionalInfo, 0, 1, 0, true, 'C', true);
    
        // Reset the font size back to original for subsequent content
        $this->SetFont('helvetica', '', 10); // Example: set back to original size
    
        // Set bottom margin after header
        $bottomMargin = 20; // Example: 10mm bottom margin
        $this->SetY($this->GetY() + $bottomMargin); // Move down to set the bottom margin
    }
    
}

if (isset($_POST['tableData']) && isset($_POST['userName']) && isset($_POST['today'])) {
    $tableData = $_POST['tableData'];
    $userName = $_POST['userName'];
    $today = $_POST['today'];

    // Remove empty or null table rows
    $tableData = preg_replace('/<tr>\s*(<td>\s*<\/td>\s*)+<\/tr>/', '', $tableData);

    // Create new PDF document using the custom MYPDF class
    $pdf = new MYPDF();

    // Set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor($userName);
    $pdf->SetTitle('Gross Ledger');
    $pdf->SetSubject('Gross Ledger PDF');

    // Set userName and today's date in the custom class to use in the header
    $pdf->setUserNameAndDate($userName, $today);

    // Set margins: left, top, bottom
    $pdf->SetMargins(20, 30, 15); // 15mm left, 20mm top, 15mm right
    $pdf->SetAutoPageBreak(TRUE, 15); // Enable automatic page breaks and set the bottom margin to 15mm

    // Add a page
    $pdf->AddPage();

    // Set content for PDF (table layout and spacing adjustments)
    $tableData = preg_replace('/(<br\s*\/?>\s*)+/', '<br>', $tableData);

    // Handle <p> tags: replace <p> with <br> only if the content isn't just whitespace
    $tableData = preg_replace('/<p[^>]*>(.*?)<\/p>/', '$1<br>', $tableData);

    // Remove trailing <br> tags if they don't precede text
    $tableData = preg_replace('/<br>\s*$/', '', $tableData);

    // Trim to remove leading and trailing whitespace
    $tableData = trim($tableData);
    $html = "<br><p>";
    $html .= "{$tableData}</p>";

    // Write HTML content to PDF
    $pdf->writeHTML($html, true, false, true, false, '');

    // Output the PDF (send it to the browser)
    $pdf->Output('ledger.pdf', 'D');  // 'D' forces download, use 'I' for inline view

    exit;
}

?>