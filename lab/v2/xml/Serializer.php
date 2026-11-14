<?php
declare(strict_types=1);
namespace LabV2\Xml;
/** Pure serialization of a validated oracle DTO. No calculations, DB, files or network.
 * NOT a CFDI issuer. Caller must independently validate DTO correspondence and XSD.
 * payments: [['id'=>string,'attributes'=>Pago attrs,'documents'=>[['attributes'=>DR attrs,
 * 'retenciones'=>[DR tax attrs], 'traslados'=>[DR tax attrs]]]]].
 */
final class Serializer {
 const NS='http://www.sat.gob.mx/Pagos20';
 private static function node(\DOMDocument $d,\DOMElement $parent,string $name,array $attrs):\DOMElement {
  $n=$d->createElementNS(self::NS,'pago20:'.$name);
  foreach($attrs as $k=>$v){if(!is_string($v))throw new \InvalidArgumentException('XML attribute must be string');$n->setAttribute($k,$v);}
  $parent->appendChild($n);return $n;
 }
 private static function attrs(array $a,array $required,array $optional=[]):array {
  if(array_diff($required,array_keys($a)) || array_diff(array_keys($a),array_merge($required,$optional)))throw new \InvalidArgumentException('Missing/unsupported XML attributes');
  foreach($a as $v)if(!is_string($v)||$v==='')throw new \InvalidArgumentException('Nonempty string attributes required');
  return $a;
 }
 private static function taxDR(array $a,bool $ret):array {
  self::attrs($a,['BaseDR','ImpuestoDR','TipoFactorDR'],['TasaOCuotaDR','ImporteDR']);
  if($a['TipoFactorDR']==='Exento'){
   if($ret||isset($a['TasaOCuotaDR'])||isset($a['ImporteDR']))throw new \InvalidArgumentException('Invalid Exento attributes');
  }elseif(!isset($a['TasaOCuotaDR'],$a['ImporteDR']))throw new \InvalidArgumentException('Tax rate/amount missing');
  return $a;
 }
 public static function serialize(array $oracle,array $payments):string {
  if(!$payments || !isset($oracle['groups'],$oracle['totals']) || !is_array($oracle['groups']) || !is_array($oracle['totals']))throw new \InvalidArgumentException('Missing DTO');
  $ids=[];foreach($payments as $p){if(!isset($p['id'])||!is_string($p['id'])||isset($ids[$p['id']]))throw new \InvalidArgumentException('Duplicate/invalid payment identity');$ids[$p['id']]=true;}
  $buckets=['monto_total_pagos'=>'MontoTotalPagos'];$groups=[];$seen=[];
  foreach($oracle['groups'] as $g){
   if(!isset($ids[$g['pago_id']]) || !in_array($g['clase'],['T','R'],true))throw new \InvalidArgumentException('Unknown group payment/kind');
   $attrs=['ImpuestoP'=>$g['impuesto']];
   if($g['clase']==='R'){
    $name=['001'=>'ISR','002'=>'IVA','003'=>'IEPS'][$g['impuesto']]??throw new \InvalidArgumentException('Unsupported retention');
    foreach(['base_p','tasa','tipo_factor'] as $k)if(isset($g[$k]))throw new \InvalidArgumentException('Retention P only tax and amount');
    $attrs['ImporteP']=$g['importe_p'];$buckets['total_retenciones_'.strtolower($name)]='TotalRetenciones'.$name;
    $key=$g['pago_id'].'|R|'.$g['impuesto'];
   }else{
    if($g['impuesto']!=='002')throw new \InvalidArgumentException('LAB supports IVA transfers only');
    $attrs['BaseP']=$g['base_p'];$attrs['TipoFactorP']=$g['tipo_factor'];
    if($g['tipo_factor']==='Exento'){
     if(isset($g['tasa'])||isset($g['importe_p']))throw new \InvalidArgumentException('Exento must omit rate/amount');
     $suffix='exento';$buckets['total_traslados_base_iva_exento']='TotalTrasladosBaseIVAExento';
    }else{
     if($g['tipo_factor']!=='Tasa')throw new \InvalidArgumentException('Unsupported LAB factor');
     $suffix=['0.160000'=>'16','0.080000'=>'08','0.000000'=>'00'][$g['tasa']]??throw new \InvalidArgumentException('Expected canonical oracle rate');
     $attrs['TasaOCuotaP']=$g['tasa'];$attrs['ImporteP']=$g['importe_p'];
     $xmlSuffix=['16'=>'16','08'=>'8','00'=>'0'][$suffix];
     $buckets['total_traslados_base_iva_'.$suffix]='TotalTrasladosBaseIVA'.$xmlSuffix;
     $buckets['total_traslados_impuesto_iva_'.$suffix]='TotalTrasladosImpuestoIVA'.$xmlSuffix;
    }
    $key=$g['pago_id'].'|T|'.$g['impuesto'].'|'.$g['tipo_factor'].'|'.$suffix;
   }
   if(isset($seen[$key]))throw new \InvalidArgumentException('Duplicate semantic group');$seen[$key]=true;
   $groups[$g['pago_id']][$g['clase']][]=$attrs;
  }
  $tot=[];foreach($buckets as $key=>$xml){if(!array_key_exists($key,$oracle['totals']))throw new \InvalidArgumentException('Missing present-group total');$tot[$xml]=$oracle['totals'][$key];}
  $d=new \DOMDocument('1.0','UTF-8');$d->formatOutput=true;$root=$d->createElementNS(self::NS,'pago20:Pagos');$root->setAttribute('Version','2.0');$d->appendChild($root);
  self::node($d,$root,'Totales',$tot);
  foreach($payments as $p){
   $pn=self::node($d,$root,'Pago',self::attrs($p['attributes'],['FechaPago','FormaDePagoP','MonedaP','Monto'],['TipoCambioP','NumOperacion']));
   if(empty($p['documents']))throw new \InvalidArgumentException('Related documents required');
   foreach($p['documents'] as $doc){
    $dn=self::node($d,$pn,'DoctoRelacionado',self::attrs($doc['attributes'],['IdDocumento','MonedaDR','NumParcialidad','ImpSaldoAnt','ImpPagado','ImpSaldoInsoluto','ObjetoImpDR'],['EquivalenciaDR','Serie','Folio']));
    if(!empty($doc['retenciones'])||!empty($doc['traslados'])){
     if($doc['attributes']['ObjetoImpDR']!=='02')throw new \InvalidArgumentException('Taxes require ObjetoImpDR02 in LAB');
     $tax=self::node($d,$dn,'ImpuestosDR',[]);
     foreach(['retenciones'=>['RetencionesDR','RetencionDR',true],'traslados'=>['TrasladosDR','TrasladoDR',false]] as $k=>$spec){
      if(!empty($doc[$k])){$container=self::node($d,$tax,$spec[0],[]);foreach($doc[$k] as $a)self::node($d,$container,$spec[1],self::taxDR($a,$spec[2]));}
     }
    }
   }
   if(isset($groups[$p['id']])){
    $tax=self::node($d,$pn,'ImpuestosP',[]);
    foreach(['R'=>['RetencionesP','RetencionP'],'T'=>['TrasladosP','TrasladoP']] as $k=>$spec){
     if(isset($groups[$p['id']][$k])){$container=self::node($d,$tax,$spec[0],[]);foreach($groups[$p['id']][$k] as $attrs)self::node($d,$container,$spec[1],$attrs);}
    }
   }
  }
  return $d->saveXML();
 }
}
