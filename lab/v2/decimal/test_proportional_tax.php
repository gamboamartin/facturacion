<?php
declare(strict_types=1);
require __DIR__.'/../deps/vendor/autoload.php';require __DIR__.'/ProportionalTaxAllocator.php';
use FacturacionLabV2\ProportionalTaxAllocator as A;
function fixture():array{return [['invoice_id'=>'1','currency'=>'MXN','fingerprint'=>str_repeat('a',64),'total'=>'6264','adjustments'=>[],'prior_allocations'=>[],'concepts'=>[
 ['id'=>'1','gross'=>'2700','discount'=>'0','taxes'=>[['class'=>'T','tax'=>'002','factor'=>'Tasa','base'=>'2700','rate'=>'0.16','amount'=>'432']]],
 ['id'=>'2','gross'=>'2900','discount'=>'0','taxes'=>[['class'=>'T','tax'=>'002','factor'=>'Tasa','base'=>'2900','rate'=>'0.08','amount'=>'232']]]]],['id'=>A::POLICY,'rules_version'=>'RMF2026_2.7.1.32','catalog_version'=>'SYNTHETIC_V1','scale'=>'2']];}
$n=0;function ok(bool $b):void{global $n;if(!$b)throw new RuntimeException('Assertion failed #'.($n+1));$n++;}
function bad(callable $edit,string $paid='3132'):void{[$s,$p]=fixture();$edit($s,$p);try{A::propose($paid,$s,$p);}catch(DomainException $e){ok(true);return;}throw new RuntimeException('Expected rejection');}
[$s,$p]=fixture();$r=A::propose('3132',$s,$p);
ok($r['concepts'][0]['taxes'][0]['base_dr']==='1350.00');ok($r['concepts'][0]['taxes'][0]['amount_dr']==='216.00');ok($r['concepts'][1]['taxes'][0]['base_dr']==='1450.00');ok($r['concepts'][1]['taxes'][0]['amount_dr']==='116.00');ok(count($r['dr_tax_groups'])===2);
ok($r===A::propose('3132',$s,$p));$s['concepts']=array_reverse($s['concepts']);ok($r===A::propose('3132',$s,$p));
[$s,$p]=fixture();$s['concepts']=[$s['concepts'][0]];$s['concepts'][0]['gross']='3000';$s['concepts'][0]['discount']='300';$s['total']='3132';$r=A::propose('1566',$s,$p);ok($r['concepts'][0]['discount_paid']==='150.00'&&$r['concepts'][0]['net_paid']==='1350.00');
// Distinct tax identities with equal numeric rates are not deduplicated.
$s['concepts'][0]['taxes'][]=['class'=>'T','tax'=>'003','factor'=>'Tasa','base'=>'2700','rate'=>'0.16','amount'=>'432'];$s['total']='3564';$r=A::propose('1782',$s,$p);ok(count($r['dr_tax_groups'])===2);ok($r['concepts'][0]['total_paid']==='1782.00');
$s['concepts'][0]['taxes'][]=['class'=>'R','tax'=>'001','factor'=>'Tasa','base'=>'2700','rate'=>'0.10','amount'=>'270'];$s['total']='3294';$r=A::propose('1647',$s,$p);ok(count($r['dr_tax_groups'])===3);ok($r['concepts'][0]['taxes'][0]['class']==='R'&&$r['concepts'][0]['taxes'][0]['amount_dr']==='135.00');
$s['concepts'][0]['taxes']=array_reverse($s['concepts'][0]['taxes']);ok($r===A::propose('1647',$s,$p));
[$s,$p]=fixture();$s['concepts'][1]['taxes']=[['class'=>'T','tax'=>'002','factor'=>'Exento','base'=>'2900']];$s['total']='6032';$r=A::propose('3016',$s,$p);ok($r['concepts'][1]['taxes'][0]['base_dr']==='1450.00');ok(!isset($r['concepts'][1]['taxes'][0]['rate'])&&!isset($r['concepts'][1]['taxes'][0]['amount_dr']));
$s['concepts'][1]['taxes']=[['class'=>'T','tax'=>'002','factor'=>'Tasa','base'=>'2900','rate'=>'0','amount'=>'0']];$z=A::propose('3016',$s,$p);ok($z!==$r&&$z['concepts'][1]['taxes'][0]['amount_dr']==='0.00');
[$s,$p]=fixture();$s['concepts'][1]=$s['concepts'][0];$s['concepts'][1]['id']='2';$s['total']='6264';$g=A::propose('3132',$s,$p);ok(count($g['dr_tax_groups'])===1&&$g['dr_tax_groups'][0]['base_dr']==='2700.00');
[$s,$p]=fixture();$full=A::propose('6264',$s,$p);ok($full['concepts'][0]['taxes'][0]['base_dr']==='2700.00');
foreach(['0','6264.01','-1','1e2','01','1.001'] as $x)bad(fn(&$s,&$p)=>null,$x);
bad(function(&$s,&$p){$s['adjustments']=[['nc'=>'1']];});bad(function(&$s,&$p){$s['prior_allocations']=[['paid'=>'1']];});
bad(function(&$s,&$p){$s['total']='6265';});bad(function(&$s,&$p){$s['concepts'][0]['taxes'][0]['amount']='431.99';});
bad(function(&$s,&$p){$s['concepts'][0]['taxes'][0]['base']='2600';});bad(function(&$s,&$p){$s['concepts'][0]['taxes'][0]['factor']='Cuota';});
bad(function(&$s,&$p){$s['concepts'][0]['taxes'][]=$s['concepts'][0]['taxes'][0];});bad(function(&$s,&$p){$s['concepts'][1]['id']='1';});
bad(function(&$s,&$p){$s['concepts'][0]['taxes']=[];});bad(function(&$s,&$p){$s['concepts'][0]['discount']='9999';});bad(function(&$s,&$p){$p['rules_version']='OTHER';});bad(function(&$s,&$p){$p['scale']=2;});bad(function(&$s,&$p){$s['total']=6264.0;});
// A valid 0.01 paid amount still yields periodic concept allocation, fail closed.
bad(fn(&$s,&$p)=>null,'0.01');
[$s,$p]=fixture();$before=$s;A::propose('3132',$s,$p);ok($s===$before);
// Independent conservation checks, decimal arithmetic only.
function properties(array $r):void {
 $sum=\Brick\Math\BigDecimal::zero();$expected=[];
 foreach($r['concepts'] as $c){
  $sum=$sum->plus($c['total_paid']);
  foreach($c['taxes'] as $t){
   $key=json_encode([$t['class'],$t['tax'],$t['factor'],$t['rate']??null],JSON_THROW_ON_ERROR);
   if(!isset($expected[$key]))$expected[$key]=['base'=>\Brick\Math\BigDecimal::zero(),'amount'=>\Brick\Math\BigDecimal::zero()];
   $expected[$key]['base']=$expected[$key]['base']->plus($t['base_dr']);
   if(isset($t['amount_dr']))$expected[$key]['amount']=$expected[$key]['amount']->plus($t['amount_dr']);
  }
 }
 ok($sum->isEqualTo($r['paid_document']));ok(count($expected)===count($r['dr_tax_groups']));
 foreach($r['dr_tax_groups'] as $t){
  $key=json_encode([$t['class'],$t['tax'],$t['factor'],$t['rate']??null],JSON_THROW_ON_ERROR);
  ok(isset($expected[$key])&&$expected[$key]['base']->isEqualTo($t['base_dr']));
  if($t['factor']!=='Exento')ok($expected[$key]['amount']->isEqualTo($t['amount_dr']));
  else ok(!isset($t['rate'])&&!isset($t['amount_dr']));
 }
}
// Deterministic generated families cover discount, multirate, shared-base
// retention, exemption, and zero rate. Integer construction avoids test floats.
for($i=1;$i<=30;$i++){
 [$s,$p]=fixture();$s['concepts']=[];$total=0;
 for($j=1;$j<=5;$j++){
  $net=100*($i+$j);$discount=2*$j;$taxes=[];$ct=$net;
  if($j===5)$taxes[]=['class'=>'T','tax'=>'002','factor'=>'Exento','base'=>(string)$net];
  else {
   $percent=[1=>16,2=>8,3=>0,4=>16][$j];$amt=intdiv($net*$percent,100);$ct+=$amt;
   $taxes[]=['class'=>'T','tax'=>'002','factor'=>'Tasa','base'=>(string)$net,'rate'=>[16=>'0.16',8=>'0.08',0=>'0'][$percent],'amount'=>(string)$amt];
   if($j===4){$ret=intdiv($net,10);$ct-=$ret;$taxes[]=['class'=>'R','tax'=>'001','factor'=>'Tasa','base'=>(string)$net,'rate'=>'0.10','amount'=>(string)$ret];}
  }
  $total+=$ct;$s['concepts'][]=['id'=>(string)$j,'gross'=>(string)($net+$discount),'discount'=>(string)$discount,'taxes'=>$taxes];
 }
 $s['total']=(string)$total;$paid=\Brick\Math\BigDecimal::of((string)$total)->dividedBy('2',2)->toString();
 $r=A::propose($paid,$s,$p);properties($r);
 $s['concepts']=array_reverse($s['concepts']);foreach($s['concepts'] as &$c){$c['taxes']=array_reverse($c['taxes']);$c=array_reverse($c,true);}unset($c);
 ok($r===A::propose($paid,array_reverse($s,true),array_reverse($p,true)));
}
foreach(['0','6'] as $scale){[$s,$p]=fixture();$p['scale']=$scale;$r=A::propose('3132',$s,$p);properties($r);ok($r['concepts'][0]['net_paid']===($scale==='0'?'1350':'1350.000000'));}
// Exactly 1000 concepts/2000 tax rows accepted.
[$s,$p]=fixture();$s['concepts']=[];
for($i=1;$i<=1000;$i++)$s['concepts'][]=['id'=>(string)$i,'gross'=>'100','discount'=>'0','taxes'=>[
 ['class'=>'T','tax'=>'002','factor'=>'Tasa','base'=>'100','rate'=>'0.16','amount'=>'16'],
 ['class'=>'R','tax'=>'001','factor'=>'Tasa','base'=>'100','rate'=>'0.10','amount'=>'10']]];
$s['total']='106000';$r=A::propose('53000',$s,$p);properties($r);ok(count($r['concepts'])===1000);
$limit=$s;$policy=$p;
bad(function(&$s,&$p)use($limit,$policy){$s=$limit;$p=$policy;$c=$s['concepts'][0];$c['id']='1001';$s['concepts'][]=$c;$s['total']='106106';},'53000');
bad(function(&$s,&$p)use($limit,$policy){$s=$limit;$p=$policy;$s['concepts'][0]['taxes'][]=['class'=>'T','tax'=>'003','factor'=>'Tasa','base'=>'100','rate'=>'0.08','amount'=>'8'];$s['total']='106008';},'106008');
// Integer range overflow on aggregated base despite a small reconciled total.
bad(function(&$s,&$p){
 $s['total']='1';$s['concepts']=[];
 for($i=1;$i<=2;$i++)$s['concepts'][]=['id'=>(string)$i,'gross'=>'999999999999999999','discount'=>'0','taxes'=>[['class'=>'R','tax'=>'001','factor'=>'Tasa','base'=>'999999999999999999','rate'=>'1','amount'=>'999999999999999999']]];
 $s['concepts'][]=['id'=>'3','gross'=>'1','discount'=>'0','taxes'=>[['class'=>'T','tax'=>'002','factor'=>'Exento','base'=>'1']]];
},'1');
// Source input exceeding integer budget rejects independently of aggregation.
bad(function(&$s,&$p){$s['concepts'][0]['gross']='1000000000000000000';});

echo json_encode(['checks'=>$n,'result'=>'PASS','scope'=>'intact first-allocation synthetic fixtures only'],JSON_PRETTY_PRINT).PHP_EOL;
