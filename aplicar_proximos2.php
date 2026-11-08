<?php
// Actualiza los proximos partidos (10, 12, 14 y 16 de noviembre). Se usa una vez y se borra solo.
header('Content-Type: text/plain; charset=utf-8');
require __DIR__.'/_datos.php';
$f=$DATA_DIR.'/fixture.json';
$fx=is_file($f)?json_decode(file_get_contents($f),true):null;
if(!is_array($fx)){echo "ERROR: no pude leer el fixture";exit;}
@mkdir($DATA_DIR.'/backup',0755,true);
@copy($f,$DATA_DIR.'/backup/fixture-antes-proximos2-'.date('Ymd-His').'.json');
$nuevos=[
 ['id'=>'m17','fecha'=>'2026-11-10','titulo'=>'Femenino 1era vs Banco Nación','nota'=>'Semifinal (vuelta) · Estadio cubierto de Newell’s','items'=>[['cat'=>'Femenino 1era','hora'=>'22:00']],'hora'=>'22:00'],
 ['id'=>'m18','fecha'=>'2026-11-12','titulo'=>'Remeros vs Sagrado','nota'=>'Primera · Cuartos de final (ida) · De visitante','items'=>[['cat'=>'Primera','hora'=>'20:30']],'hora'=>'20:30'],
 ['id'=>'m15','fecha'=>'2026-11-14','titulo'=>'Sub-20 Femenino vs Central','nota'=>'En Aurora','items'=>[['cat'=>'Sub-20','hora'=>'17:00']],'hora'=>'17:00'],
 ['id'=>'m19','fecha'=>'2026-11-16','titulo'=>'Sagrado vs Remeros','nota'=>'Primera · Cuartos de final (vuelta) · La Fortaleza','items'=>[['cat'=>'Primera','hora'=>'21:00']],'hora'=>'21:00'],
];
$ids=array_column($nuevos,'id');
$fx=array_values(array_filter($fx,function($m)use($ids){return !in_array($m['id']??'',$ids,true) && ($m['id']??'')!=='m12';}));
foreach($nuevos as $n)$fx[]=$n;
usort($fx,function($a,$b){$fa=$a['fecha']??'9999-99-99';$fb=$b['fecha']??'9999-99-99';if($fa!==$fb)return strcmp($fa,$fb);return strcmp(str_pad($a['hora']??'99:99',5,'0',STR_PAD_LEFT),str_pad($b['hora']??'99:99',5,'0',STR_PAD_LEFT));});
if(!file_put_contents($f,json_encode($fx,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT))){echo "ERROR: permisos";exit;}
echo "Listo: ".count($nuevos)." partidos cargados; total ".count($fx).".\n";@unlink(__FILE__);
