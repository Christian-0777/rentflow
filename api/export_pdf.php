<?php
// api/export_pdf.php
// Exports chart canvas to PDF by accepting a data URL and returning a simple PDF-like HTML for printing

// Note: For production, use a real PDF library (TCPDF, Dompdf). Here we return HTML printable as PDF.
$dataUrl = $_POST['dataUrl'] ?? '';
$name = $_POST['name'] ?? 'chart';

header('Content-Type: text/html; charset=utf-8');
echo "<html><head><title>$name</title></head><body>";
echo "<img src='".htmlspecialchars($dataUrl)."' style='width:100%'>";
echo "</body></html>";
