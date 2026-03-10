<?php
// api/export_full_pdf.php
// Export reports page as PDF using Puppeteer

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/auth.php';

// Use plain string for role check
require_role('admin');

// Start output buffering to capture the HTML
ob_start();

// Include the reports page logic (without the HTML wrapper)
require_once __DIR__.'/../admin/reports.php';

// Get the captured HTML
$html = ob_get_clean();

// Clean the HTML: remove buttons, nav
$dom = new DOMDocument();
libxml_use_internal_errors(true); // Suppress warnings for malformed HTML
$dom->loadHTML($html);
libxml_clear_errors();

// Remove navigation
$nav = $dom->getElementsByTagName('nav');
if ($nav->length > 0) {
    $nav->item(0)->parentNode->removeChild($nav->item(0));
}

// Remove all buttons and export sections
$xpath = new DOMXPath($dom);
$buttons = $xpath->query("//button | //a[contains(@class, 'btn')] | //div[contains(@class, 'export-full-page')] | //div[contains(@class, 'export-buttons')]");
foreach ($buttons as $button) {
    $button->parentNode->removeChild($button);
}

// Remove modal
$modal = $dom->getElementById('pdfModal');
if ($modal) {
    $modal->parentNode->removeChild($modal);
}

// Get the cleaned HTML
$cleanHtml = $dom->saveHTML();

// Create temp files
$tempDir = sys_get_temp_dir();
$tempHtml = tempnam($tempDir, 'report_') . '.html';
$tempPdf = tempnam($tempDir, 'report_') . '.pdf';

// Write cleaned HTML to temp file
file_put_contents($tempHtml, $cleanHtml);

// Run Puppeteer script
$nodeScript = __DIR__ . '/../export_pdf.js';
$command = "node \"$nodeScript\" \"$tempHtml\" \"$tempPdf\"";
exec($command, $output, $returnCode);

if ($returnCode !== 0) {
    // Error
    unlink($tempHtml);
    http_response_code(500);
    echo 'Error generating PDF';
    exit;
}

// Serve the PDF
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="rentflow_report_' . date('Y-m-d') . '.pdf"');
header('Content-Length: ' . filesize($tempPdf));

readfile($tempPdf);

// Clean up
unlink($tempHtml);
unlink($tempPdf);
?>