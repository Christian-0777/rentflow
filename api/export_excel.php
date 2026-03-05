<?php
// api/export_excel.php
// Generates Excel file from posted JSON payload

$data = json_decode($_POST['payload'] ?? '[]', true);
$headers = json_decode($_POST['headers'] ?? '[]', true);
$filename = $_POST['filename'] ?? 'rentflow_export.xlsx';

if (empty($headers)) {
  $headers = array_keys($data[0] ?? []);
}

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// Create Excel content (simple tab-separated format that Excel can open)
echo implode("\t", $headers) . "\n";

foreach ($data as $row) {
  $values = array_values($row);
  // Escape tabs and newlines in data
  $escaped = array_map(function($value) {
    return str_replace(["\t", "\n", "\r"], [' ', ' ', ''], $value);
  }, $values);
  echo implode("\t", $escaped) . "\n";
}
?>