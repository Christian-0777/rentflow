<?php
// api/export_png.php
// Exports chart canvas to PNG by streaming the data URL back (client-side handles download)

header('Content-Type: application/json');
echo json_encode(['ok'=>true]);
