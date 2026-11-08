<?php
// guardar.php - API privada para cargar partidos y resultados del fixture del Atlético Sagrado Corazón.
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');
require __DIR__ . '/_datos.php';
$dir = $DATA_DIR;
$fxFile = $dir . '/fixture.json';
$authFile = $dir . '/auth.php';
$bakDir = $dir . '/backup';
$failFile = $dir . '/intentos.json';

function out($a, $code = 200) { http_response_code($code); echo json_encode($a, JSON_UNESCAPED_UNICODE); exit; }
function clean($s, $max) { $s = trim(strip_tags((string)$s)); $s = preg_replace('/\s+/u', ' ', $s); return mb_substr($s, 0, $max); }
function validHora($h) { return is_string($h) && preg_match('/^([01]?\d|2[0-3]):[0-5]\d$/', $h); }
function readFx($f) { if (!is_file($f)) return []; $d = json_decode(file_get_contents($f), true); return is_array($d) ? $d : []; }
function sortFx(&$fx) {
    usort($fx, function ($a, $b) {
        $fa = $a['fecha'] ?? '9999-99-99'; $fb = $b['fecha'] ?? '9999-99-99';
        if ($fa !== $fb) return strcmp($fa, $fb);
        $ha = $a['hora'] ?? '99:99'; $hb = $b['hora'] ?? '99:99';
        return strcmp(str_pad($ha, 5, '0', STR_PAD_LEFT), str_pad($hb, 5, '0', STR_PAD_LEFT));
    });
}
function writeFx($fxFile, $bakDir, $fx) {
    if (!is_dir($bakDir)) @mkdir($bakDir, 0755, true);
    if (is_file($fxFile)) { @copy($fxFile, $bakDir . '/fixture-' . date('Ymd-His') . '.json'); }
    $files = glob($bakDir . '/fixture-*.json'); sort($files);
    while (count($files) > 40) { @unlink(array_shift($files)); }
    $tmp = $fxFile . '.tmp';
    file_put_contents($tmp, json_encode(array_values($fx), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    rename($tmp, $fxFile);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(['ok' => false, 'error' => 'Método no permitido'], 405);
$in = json_decode(file_get_contents('php://input'), true);
if (!is_array($in)) out(['ok' => false, 'error' => 'Datos inválidos'], 400);
$action = $in['action'] ?? '';
$cfg = [];
if (is_file($authFile)) { $raw = file_get_contents($authFile); $nl = strpos($raw, "\n"); $cfg = $nl === false ? [] : json_decode(substr($raw, $nl + 1), true); if (!is_array($cfg)) $cfg = []; }
$hash = $cfg['hash'] ?? '';
$setup = $cfg['setup'] ?? '';

if ($action === 'estado') out(['ok' => true, 'configurado' => $hash !== '']);

// Límite de intentos fallidos (8 en 10 minutos)
$fails = is_file($failFile) ? (json_decode(file_get_contents($failFile), true) ?: []) : [];
$now = time();
$fails = array_values(array_filter($fails, function ($t) use ($now) { return $t > $now - 600; }));
if (count($fails) >= 8) out(['ok' => false, 'error' => 'Demasiados intentos. Esperá 10 minutos.'], 429);
function fail($msg, $fails, $failFile, $code = 401) { $fails[] = time(); @file_put_contents($failFile, json_encode($fails)); usleep(1200000); out(['ok' => false, 'error' => $msg], $code); }

if ($action === 'setup') {
    if ($hash !== '' || $setup === '') out(['ok' => false, 'error' => 'La clave ya fue configurada.'], 409);
    if (!hash_equals($setup, trim((string)($in['codigo'] ?? '')))) fail('Código de instalación incorrecto.', $fails, $failFile);
    $clave = (string)($in['clave'] ?? '');
    if (mb_strlen($clave) < 8) out(['ok' => false, 'error' => 'La clave tiene que tener al menos 8 caracteres.'], 400);
    $new = "<?php exit; ?>\n" . json_encode(['setup' => '', 'hash' => password_hash($clave, PASSWORD_DEFAULT)]) . "\n";
    file_put_contents($authFile, $new);
    out(['ok' => true]);
}

if ($hash === '') out(['ok' => false, 'error' => 'Primero hay que elegir la clave.'], 409);
if (!password_verify((string)($in['clave'] ?? ''), $hash)) fail('Clave incorrecta.', $fails, $failFile);
if ($fails) @file_put_contents($failFile, '[]');

$lock = fopen($dir . '/.lock', 'c');
flock($lock, LOCK_EX);
$fx = readFx($fxFile);

if ($action === 'login' || $action === 'list') { sortFx($fx); out(['ok' => true, 'fixture' => $fx]); }

if ($action === 'delete') {
    $id = clean($in['id'] ?? '', 30);
    $fx = array_values(array_filter($fx, function ($m) use ($id) { return ($m['id'] ?? '') !== $id; }));
    writeFx($fxFile, $bakDir, $fx); sortFx($fx);
    out(['ok' => true, 'fixture' => $fx]);
}

if ($action === 'save') {
    $m = $in['match'] ?? null;
    if (!is_array($m)) out(['ok' => false, 'error' => 'Falta el partido.'], 400);
    $titulo = clean($m['titulo'] ?? '', 80);
    if ($titulo === '') out(['ok' => false, 'error' => 'Falta el título (por ejemplo "Sagrado vs Rowing").'], 400);
    $fecha = $m['fecha'] ?? null;
    if ($fecha !== null && $fecha !== '') {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $fecha, $p) || !checkdate((int)$p[2], (int)$p[3], (int)$p[1])) out(['ok' => false, 'error' => 'Fecha inválida.'], 400);
    } else { $fecha = null; }
    $hora = $m['hora'] ?? null;
    if ($hora === '' || $hora === null) { $hora = null; } elseif (!validHora($hora)) { out(['ok' => false, 'error' => 'Hora inválida.'], 400); }
    $items = [];
    foreach ((array)($m['items'] ?? []) as $it) {
        if (!is_array($it)) continue;
        $cat = clean($it['cat'] ?? '', 40);
        if ($cat === '') continue;
        if (isset($it['gf']) && isset($it['gc']) && $it['gf'] !== '' && $it['gc'] !== '') {
            $gf = filter_var($it['gf'], FILTER_VALIDATE_INT); $gc = filter_var($it['gc'], FILTER_VALIDATE_INT);
            if ($gf === false || $gc === false || $gf < 0 || $gc < 0 || $gf > 99 || $gc > 99) out(['ok' => false, 'error' => 'Goles inválidos en ' . $cat . '.'], 400);
            $row2 = ['cat' => $cat, 'gf' => $gf, 'gc' => $gc]; $gol = clean($it['gol'] ?? '', 90); if ($gol !== '') $row2['gol'] = $gol; $items[] = $row2;
        } elseif (!empty($it['hora'])) {
            if (!validHora($it['hora'])) out(['ok' => false, 'error' => 'Horario inválido en ' . $cat . '.'], 400);
            $items[] = ['cat' => $cat, 'hora' => $it['hora']];
        } elseif (!empty($it['txt'])) {
            $items[] = ['cat' => $cat, 'txt' => clean($it['txt'], 40)];
        }
        if (count($items) >= 20) break;
    }
    if (!$items) out(['ok' => false, 'error' => 'Cargá al menos una categoría con resultado u horario.'], 400);
    $id = clean($m['id'] ?? '', 30);
    if ($id === '' || !preg_match('/^[A-Za-z0-9_-]+$/', $id)) $id = 'm' . date('ymdHis') . substr(bin2hex(random_bytes(2)), 0, 3);
    $row = ['id' => $id, 'fecha' => $fecha, 'titulo' => $titulo, 'nota' => clean($m['nota'] ?? '', 160), 'items' => $items];
    if ($hora) $row['hora'] = $hora;
    $found = false;
    foreach ($fx as $k => $old) { if (($old['id'] ?? '') === $id) { $fx[$k] = $row; $found = true; break; } }
    if (!$found) $fx[] = $row;
    sortFx($fx);
    if (count($fx) > 300) $fx = array_slice($fx, -300);
    writeFx($fxFile, $bakDir, $fx);
    out(['ok' => true, 'fixture' => $fx, 'id' => $id]);
}

out(['ok' => false, 'error' => 'Acción desconocida'], 400);
