<?php
declare(strict_types=1);
require __DIR__.'/../deps/vendor/autoload.php';
foreach(['ExactDrCalculator','ProportionalTaxAllocator','BrickOracle'] as $name)require __DIR__.'/../decimal/'.$name.'.php';
require __DIR__.'/../xml/Serializer.php';
require __DIR__.'/ClosedFirstAllocationPipeline.php';
use LabV2\FirstAllocation\ClosedFirstAllocationPipeline as P;
use Brick\Math\BigDecimal as D;
function sourceFixture(string $kind='single'):array {
 $tax=fn($base,$rate,$amount,$class='T')=>['class'=>$class,'tax'=>$class==='T'?'002':'001','factor'=>'Tasa','base'=>$base,'rate'=>$rate,'amount'=>$amount];
 $concept=fn($id,$base,$taxes)=>['id'=>$id,'gross'=>$base,'discount'=>'0.000000000000000000','taxes'=>$taxes];
 $base='100.000000000000000000';
 [$total,$paid,$concepts]=match($kind){
  'single'=>['116.000000000000000000','58',[$concept('1',$base,[$tax($base,'0.16','16.000000000000000000')])]],
  'mixed'=>['224','112',[$concept('1','100',[$tax('100','0.16','16')]),$concept('2','100',[$tax('100','0.08','8')])]],
  'retention'=>['106','53',[$concept('1','100',[$tax('100','0.16','16'),$tax('100','0.10','10','R')])]],
  'exento'=>['100','50',[$concept('1','100',[['class'=>'T','tax'=>'002','factor'=>'Exento','base'=>'100']])]],
 };
 return [['invoice_id'=>'700001','currency'=>'MXN','fingerprint'=>str_repeat('b',64),'total'=>$total,'adjustments'=>[],'prior_allocations'=>[],'concepts'=>$concepts],$paid];
}
function context():array{return ['trust'=>P::TRUST,'operation_id'=>'synthetic-op','payment_id'=>'701','payment_revision'=>'1','invoice_revision'=>'1'];}
if(isset($argv[1])&&str_starts_with($argv[1],'--xml=')){[$s,$a]=sourceFixture(substr($argv[1],6));echo P::fromCallerVerifiedSyntheticSource($s,$a,context())['xml'];exit;}
$n=0;function ok(bool $v):void{global $n;if(!$v)throw new RuntimeException('Assertion #'.($n+1));$n++;}
foreach(['single','mixed','retention','exento'] as $kind){
 [$s,$a]=sourceFixture($kind);$before=$s;$r=P::fromCallerVerifiedSyntheticSource($s,$a,context());
 ok($r===P::fromCallerVerifiedSyntheticSource($s,$a,context()));ok($s===$before);
 ok($r['source_fingerprint']===$s['fingerprint']);ok($r['dr']['source_fiscal_fingerprint']===$s['fingerprint']);
 ok($r['provenance_verified_here']===false&&$r['synthetic_xml_identity']===true);
 ok($r['status']==='SYNTHETIC_DRAFT_NOT_PERSISTED_NOT_FOR_TIMBRADO');
 ok(D::of($r['dr']['document_paid'])->isEqualTo($a));
 ok(D::of($s['total'])->minus($a)->isEqualTo($r['dr']['outstanding_balance']));
 $sum=D::zero();foreach($r['allocation']['concepts'] as $c)$sum=$sum->plus($c['total_paid']);ok($sum->isEqualTo($a));
 $doc=new DOMDocument();ok($doc->loadXML($r['xml'],LIBXML_NONET));$xp=new DOMXPath($doc);$xp->registerNamespace('p','http://www.sat.gob.mx/Pagos20');
 ok($xp->query('//p:DoctoRelacionado')->item(0)->getAttribute('ImpPagado')===$r['dr']['document_paid']);
 ok($xp->query('//p:Totales')->item(0)->getAttribute('MontoTotalPagos')===D::of($a)->toScale(2)->toString());
 if($kind==='single'){ok($r['allocation']['dr_tax_groups'][0]['base_dr']==='50.00');ok($r['allocation']['dr_tax_groups'][0]['amount_dr']==='8.00');}
 if($kind==='mixed'){ok(count($r['allocation']['dr_tax_groups'])===2);ok($r['oracle']['totals']['total_traslados_impuesto_iva_08']==='4.00');}
 if($kind==='retention')ok($r['oracle']['totals']['total_retenciones_isr']==='5.00');
 if($kind==='exento'){ $node=$xp->query('//p:TrasladoDR')->item(0);ok(!$node->hasAttribute('ImporteDR')&&!$node->hasAttribute('TasaOCuotaDR'));}
}
foreach(['trust','currency','history','nc','round','over','amount_syntax','amount_float_string','extra','source_mismatch'] as $bad){
 [$s,$a]=sourceFixture();$c=context();
 match($bad){
  'trust'=>$c['trust']='HTTP_CALLER', 'currency'=>$s['currency']='USD',
  'history'=>$s['prior_allocations']=[['paid'=>'1']], 'nc'=>$s['adjustments']=[['nc'=>'1']],
  'round'=>$a='0.01', 'over'=>$a='117', 'amount_syntax'=>$a='1e2',
  'amount_float_string'=>$a='0.001', 'extra'=>$c['is_trusted']=true,
  'source_mismatch'=>$s['total']='115',
 };
 try{P::fromCallerVerifiedSyntheticSource($s,$a,$c);throw new RuntimeException('Expected rejection');}
 catch(DomainException|InvalidArgumentException|Brick\Math\Exception\RoundingNecessaryException $e){ok(true);}
}
echo json_encode(['checks'=>$n,'result'=>'PASS','scope'=>'pure synthetic adapter shape; actual reader/boundary integration not run here'],JSON_PRETTY_PRINT).PHP_EOL;
