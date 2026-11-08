<?php
require __DIR__ . '/_datos.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
$f = $DATA_DIR . '/fixture.json';
if (!is_file($f)) { $f = __DIR__ . '/datos/fixture.json'; }
if (is_file($f)) { readfile($f); } else { echo '[]'; }
