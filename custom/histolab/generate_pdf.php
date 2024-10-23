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

    // Custom header method
    public function Header() {
        // Set header content with userName and today's date
        $header = "Gross Ledger&nbsp;Printed by: " . $this->userName . " on " . $this->today;
        // Additional text for Bone D/C and Re-gross information
        $additionalInfo = "There is no information of Bone D/C and Re-gross Section Code here. If you need any information of Bone D/C and Re-gross, then visit the Bone D/C and Re-gross Tab.";
        // Combine the header and additional info
        $fullHeader = $header . "<br>" . $additionalInfo;
        // Write the full HTML header content to the PDF
        $this->writeHTMLCell(0, 0, '', '', $fullHeader, 0, 1, 0, true, 'C', true);
        // Add bottom margin by moving down 10mm (or any other value)
        $this->SetY($this->GetY() + 20); // Adjust 10mm down for bottom margin
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