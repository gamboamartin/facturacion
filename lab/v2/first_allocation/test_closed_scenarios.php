<?php
declare(strict_types=1);
require __DIR__.'/../deps/vendor/autoload.php';
foreach(['ExactDrCalculator','ProportionalTaxAllocator','BrickOracle'] as $name)require __DIR__.'/../decimal/'.$name.'.php';
require __DIR__.'/../xml/Serializer.php';require __DIR__.'/ClosedFirstAllocationPipeline.php';require __DIR__.'/ClosedFixtureScenarios.php';
use LabV2\FirstAllocation\ClosedFixtureScenarios as S;
use LabV2\FirstAllocation\ClosedFirstAllocationPipeline as P;
function syntheticSource(string $name):array{
 $d=S::definition($name);$lines=[];
 foreach($d['lines'] as $i=>$line)$lines[]=['id'=>(string)($i+1),'gross'=>$line['base'],'discount'=>'0','taxes'=>$line['taxes']];
 return ['invoice_id'=>'1','currency'=>'MXN','fingerprint'=>str_repeat('a',64),'total'=>$d['total'],'adjustments'=>[],'prior_allocations'=>[],'concepts'=>$lines];
}
function resultFor(string $name,array $source):array{return P::fromCallerVerifiedSyntheticSource($source,S::definition($name)['payment'],['trust'=>P::TRUST,'operation_id'=>'demo','payment_id'=>'1','payment_revision'=>'1','invoice_revision'=>'1']);}
if(isset($argv[1])&&str_starts_with($argv[1],'--xml=')){$name=substr($argv[1],6);if($name==='baseline')$name='single';$s=syntheticSource($name);S::assertSource($name,$s);$r=resultFor($name,$s);S::assertPipeline($name,$r);echo $r['xml'];exit;}
$n=0;function ok(bool $b):void{global $n;if(!$b)throw new RuntimeException('Assertion');$n++;}
function rejects(callable $f):void{try{$f();}catch(DomainException $e){ok(true);return;}throw new RuntimeException('Expected rejection');}
ok(S::names()===['single','mixed','retention','exento','mixed_exento','zero']);
foreach(S::names() as $name){
 $s=syntheticSource($name);S::assertSource($name,$s);ok(true);
 $r=resultFor($name,$s);S::assertPipeline($name,$r);ok(true);
 ok($r===resultFor($name,$s));
 $s['concepts']=array_reverse($s['concepts']);foreach($s['concepts'] as &$c)$c['taxes']=array_reverse($c['taxes']);unset($c);S::assertSource($name,$s);ok(true);
 $s['total'].='.000000000000000000';S::assertSource($name,$s);ok(true);
 $bad=$s;$bad['total']='999';rejects(fn()=>S::assertSource($name,$bad));
 $bad=$s;$bad['prior_allocations']=[['amount'=>'1']];rejects(fn()=>S::assertSource($name,$bad));
 $bad=$s;$bad['concepts'][0]['taxes'][0]['base']='99';rejects(fn()=>S::assertSource($name,$bad));
 $bad=$r;$bad['dr']['outstanding_balance']='0';rejects(fn()=>S::assertPipeline($name,$bad));
 $bad=$r;$bad['allocation']['dr_tax_groups'][0]['base_dr']='51';rejects(fn()=>S::assertPipeline($name,$bad));
 $bad=$r;$bad['oracle']['groups'][0]['impuesto']='999';rejects(fn()=>S::assertPipeline($name,$bad));
 $bad=$r;$bad['oracle']['totals']['monto_total_pagos']='0';rejects(fn()=>S::assertPipeline($name,$bad));
 $bad=$r;$bad['provenance_verified_here']=true;rejects(fn()=>S::assertPipeline($name,$bad));
}
rejects(fn()=>S::definition('caller_defined'));
$s=syntheticSource('exento');$s['concepts'][0]['taxes'][0]['rate']='0';rejects(fn()=>S::assertSource('exento',$s));
$s=syntheticSource('zero');rejects(fn()=>S::assertSource('exento',$s));
$r=resultFor('exento',syntheticSource('exento'));$r['allocation']['dr_tax_groups'][0]['amount_dr']='0';rejects(fn()=>S::assertPipeline('exento',$r));
echo json_encode(['checks'=>$n,'result'=>'PASS','scope'=>'finite synthetic arithmetic fixtures; no SQL/boundary authority'],JSON_PRETTY_PRINT).PHP_EOL;
