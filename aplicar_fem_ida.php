<?php
// Carga el resultado de la ida de la semifinal del Femenino 1era vs Banco Nacion (2 a 1). Se usa una vez y se borra solo.
header('Content-Type: text/plain; charset=utf-8');
require __DIR__.'/_datos.php';
$f=$DATA_DIR.'/fixture.json';
$fx=is_file($f)?json_decode(file_get_contents($f),true):null;
if(!is_array($fx)){echo "ERROR: no pude leer el fixture";exit;}
@mkdir($DATA_DIR.'/backup',0755,true);
@copy($f,$DATA_DIR.'/backup/fixture-antes-femida-'.date('Ymd-His').'.json');
$u=0;
foreach($fx as $k=>$m){
 $t=$m['titulo']??'';$n=$m['nota']??'';
 if(stripos($t,'Femenino')!==false && stripos($t,'Banco')!==false && stripos($n,'(ida)')!==false){
  if(empty($m['items'])){$m['items']=[['cat'=>'Femenino 1era']];}
  $m['items'][0]['gf']=2;$m['items'][0]['gc']=1;
  $fx[$k]=$m;$u++;
 }
}
if(!$u){echo "No encontre el partido de ida en el fixture. No cambie nada.\n";exit;}
if(!file_put_contents($f,json_encode(array_values($fx),JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT))){echo "ERROR: permisos";exit;}
echo "Listo: resultado cargado, Femenino 1era 2 - 1 Banco Nacion ($u partido).\n";@unlink(__FILE__);
