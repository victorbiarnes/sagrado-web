<?php
// Saca de la web el partido de ida de la semifinal Femenino 1era vs Banco Nacion (ya jugado). No toca ningun otro partido. Se usa una vez y se borra solo.
header('Content-Type: text/plain; charset=utf-8');
require __DIR__.'/_datos.php';
$f=$DATA_DIR.'/fixture.json';
$fx=is_file($f)?json_decode(file_get_contents($f),true):null;
if(!is_array($fx)){echo "ERROR: no pude leer el fixture";exit;}
@mkdir($DATA_DIR.'/backup',0755,true);
@copy($f,$DATA_DIR.'/backup/fixture-antes-sacaida-'.date('Ymd-His').'.json');
$out=[];$u=0;
foreach($fx as $m){
 $t=$m['titulo']??'';$n=$m['nota']??'';
 if(stripos($t,'Femenino')!==false && stripos($t,'Banco')!==false && stripos($n,'(ida)')!==false && stripos($n,'vuelta')===false){$u++;continue;}
 $out[]=$m;
}
if(!$u){echo "No encontre el partido de ida. No cambie nada.\n";exit;}
if(!file_put_contents($f,json_encode($out,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT))){echo "ERROR: permisos";exit;}
echo "Listo: saque $u partido (Femenino ida). Quedan ".count($out)." partidos cargados.\n";@unlink(__FILE__);
