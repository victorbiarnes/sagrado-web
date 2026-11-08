<?php
// Carpeta de datos FUERA de public_html, para que los despliegues desde GitHub no la borren.
// Si no se puede crear/escribir allí, usa una carpeta dentro del sitio.
$DATA_DIR = dirname(__DIR__) . '/datos_sagrado';
if (!is_dir($DATA_DIR)) { @mkdir($DATA_DIR, 0755, true); }
if (!is_dir($DATA_DIR) || !is_writable($DATA_DIR)) {
    $DATA_DIR = __DIR__ . '/datos_priv';
    if (!is_dir($DATA_DIR)) { @mkdir($DATA_DIR, 0755, true); }
}
// Primera vez: sembrar el fixture con los partidos cargados hasta hoy.
if (!is_file($DATA_DIR . '/fixture.json') && is_file(__DIR__ . '/datos/fixture.json')) {
    @copy(__DIR__ . '/datos/fixture.json', $DATA_DIR . '/fixture.json');
}
if (!is_file($DATA_DIR . '/.htaccess')) { @file_put_contents($DATA_DIR . '/.htaccess', "Require all denied\n"); }
