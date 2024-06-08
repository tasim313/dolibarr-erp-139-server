<?php
include('connection.php');
include('common_function.php');
include('../grossmodule/gross_common_function.php');
$LabNumber = $_GET['lab_number'] ?? 'unknown';;
$fk_gross_id = getGrossIdByLabNumber($LabNumber);
$LabNumberWithoutPrefix = substr($LabNumber, 3);
if ($LabNumber !== null) {
    $last_value = substr($LabNumber, 8);
} else {
    echo 'Error: Lab number not found';
}

print('<script src="https://cdn.jsdelivr.net/jsbarcode/3.5.8/JsBarcode.all.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.1.1.min.js" integrity="sha256-hVVnYaiADRTO2PzUGmuLJr8BLUSjGIZsDYGmIJLv2b8=" 
    crossorigin="anonymous"></script>');

$invoice_number = "SELECT f.ref AS invoice  FROM llx_facture AS f  JOIN llx_societe s ON f.fk_soc = s.rowid  JOIN llx_commande AS c ON c.fk_soc = s.rowid 
WHERE c.ref = '$LabNumberWithoutPrefix'";

$invoice_result = pg_query($pg_con, $invoice_number);


// Generate the EAN13 barcode using an online service
$barcodeUrl = 'https://barcode.tec-it.com/barcode.ashx?data=' . urlencode($LabNumber) . '&code=EAN13&multiplebarcodes=false&translate-esc=false&unit=Fit&dpi=96&imagetype=png&rotation=0&color=%23000000&bgcolor=%23ffffff&quiet=0&qunit=mm&quietunit=0';
$barcodeImage = file_get_contents($barcodeUrl);
$barcodeFilePath = sys_get_temp_dir() . '/barcode.png';
file_put_contents($barcodeFilePath, $barcodeImage);

$specimenIformation   = get_gross_specimens_list($LabNumberWithoutPrefix);

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
$content = "this is for test data"; // Your custom content

// ODT content
$odtContent = <<<EOD
<?xml version="1.0" encoding="UTF-8"?>
<office:document-content
    xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"
    xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0"
    xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"
    xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0"
    xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0"
    xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0"
    xmlns:xlink="http://www.w3.org/1999/xlink"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    xmlns:meta="urn:oasis:names:tc:opendocument:xmlns:meta:1.0"
    xmlns:number="urn:oasis:names:tc:opendocument:xmlns:datastyle:1.0"
    xmlns:svg="urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0"
    xmlns:chart="urn:oasis:names:tc:opendocument:xmlns:chart:1.0"
    xmlns:dr3d="urn:oasis:names:tc:opendocument:xmlns:dr3d:1.0"
    xmlns:math="http://www.w3.org/1998/Math/MathML"
    xmlns:form="urn:oasis:names:tc:opendocument:xmlns:form:1.0"
    xmlns:script="urn:oasis:names:tc:opendocument:xmlns:script:1.0"
    office:version="1.0">
    <office:body>
        <office:text>
            <!-- Barcode image -->
            <draw:frame draw:style-name="fr1" draw:name="barcode.png" text:anchor-type="paragraph" svg:width="5cm" svg:height="2cm" draw:z-index="0">
                <draw:image xlink:href="Pictures/barcode.png" xlink:type="simple" xlink:show="embed" xlink:actuate="onLoad"/>
            </draw:frame>
            <text:p>HISTOPATHOLOGY REPORT</text:p>
            <table:table table:name="Test Table" table:style-name="Table1">
                    <table:table-column table:style-name="Table1.A"/>
                    <table:table-column table:style-name="Table1.B"/>
                    <table:table-row>
                        <table:table-cell office:value-type="string">
                            <text:p>Header 1</text:p>
                        </table:table-cell>
                        <table:table-cell office:value-type="string">
                            <text:p>Header 2</text:p>
                        </table:table-cell>
                    </table:table-row>
                    <table:table-row>
                        <table:table-cell office:value-type="string">
                            <text:p>Row 1, Cell 1</text:p>
                        </table:table-cell>
                        <table:table-cell office:value-type="string">
                            <text:p>Row 1, Cell 2</text:p>
                        </table:table-cell>
                    </table:table-row>
                    <table:table-row>
                        <table:table-cell office:value-type="string">
                            <text:p>Row 2, Cell 1</text:p>
                        </table:table-cell>
                        <table:table-cell office:value-type="string">
                            <text:p>Row 2, Cell 2</text:p>
                        </table:table-cell>
                    </table:table-row>
            </table:table>
            
            <text:p>$content</text:p>
        </office:text>
    </office:body>
</office:document-content>
EOD;

$zip = new ZipArchive();
$tempFile = tempnam(sys_get_temp_dir(), 'odt');
$zip->open($tempFile, ZipArchive::CREATE);

// Add mimetype
$zip->addFromString('mimetype', 'application/vnd.oasis.opendocument.text');

// Add content
$zip->addFromString('content.xml', $odtContent);

// Add meta.xml
$metaContent = <<<EOD
<?xml version="1.0" encoding="UTF-8"?>
<office:document-meta
    xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"
    xmlns:xlink="http://www.w3.org/1999/xlink"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    xmlns:meta="urn:oasis:names:tc:opendocument:xmlns:meta:1.0"
    office:version="1.0">
    <office:meta>
        <meta:generator>PHP ODT Generator</meta:generator>
        <meta:initial-creator>System</meta:initial-creator>
        <dc:creator>System</dc:creator>
        <dc:date>2024-06-05T00:00:00</dc:date>
    </office:meta>
</office:document-meta>
EOD;
$zip->addFromString('meta.xml', $metaContent);

// Close the zip archive
$zip->close();

header('Content-Description: File Transfer');
header('Content-Type: application/vnd.oasis.opendocument.text');
header('Content-Disposition: attachment; filename="' . $LabNumber . '_generated_document.odt"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($tempFile));
readfile($tempFile);
unlink($tempFile);
exit;
?>
