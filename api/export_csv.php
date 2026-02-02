<?php
// api/export_csv.php
// Generates CSV from posted JSON payload

$data = json_decode($_POST['payload'] ?? '[]', true);
$headers = json_decode($_POST['headers'] ?? '[]', true);
$filename = $_POST['filename'] ?? 'rentflow_export.csv';

if (empty($headers)) {
  $headers = array_keys($data[0] ?? []);
}

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
fputcsv($out, $headers);
foreach ($data as $row) {
  fputcsv($out, array_values($row));
}
fclose($out);
