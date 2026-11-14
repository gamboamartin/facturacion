<?php
declare(strict_types=1);
require __DIR__.'/../deps/vendor/autoload.php';
require __DIR__.'/ExactDrCalculator.php';
use FacturacionLabV2\ExactDrCalculator as C;
use FacturacionLabV2\ExactDrPolicyRequired;

function fixture(): array {
    return [
        ['operation_id'=>'synthetic-op-1','payment_child_id'=>'700001','invoice_id'=>'301','amount'=>['unit'=>'PAYMENT','value'=>'1160.00','currency'=>'MXN'],'quote_reference'=>'synthetic-quote','expected_payment_revision'=>'7','expected_invoice_ledger_revision'=>'12'],
        ['payment_child_id'=>'700001','invoice_id'=>'301','payment_revision'=>'7','invoice_ledger_revision'=>'12','payment_currency'=>'MXN','document_currency'=>'USD','equivalence'=>'0.0500000000','quote_reference'=>'synthetic-quote','payment_available'=>'2000.00','previous_balance'=>'100.00','next_partiality'=>'2','predecessor_reference'=>'synthetic-prior-1','target_state'=>'DRAFT','source_ledger_eligible'=>true,'source_fiscal_fingerprint'=>'synthetic-face-1'],
        ['id'=>C::POLICY,'catalog_version'=>'SYNTHETIC-CATALOG-V1','rules_version'=>'SYNTHETIC-RULES-V1','sequence_policy'=>'SYNTHETIC-LOCKED-SEQ-V1','payment_scale'=>'2','document_scale'=>'2']
    ];
}
$count=0;
function check(bool $v, string $label):void { global $count; if(!$v) throw new RuntimeException($label); $count++; }
function rejected(callable $edit, string $type=InvalidArgumentException::class):void {
    [$c,$s,$p]=fixture(); $edit($c,$s,$p);
    try { C::propose($c,$s,$p); } catch(Throwable $e) { check($e instanceof $type,'Unexpected error '.get_class($e).':'.$e->getMessage());return; }
    throw new RuntimeException('Expected rejection');
}
[$c,$s,$p]=fixture(); $r=C::propose($c,$s,$p);
check($r['document_paid']==='58.00' && $r['payment_allocation']==='1160.00','Payment conversion');
check($r['outstanding_balance']==='42.00' && $r['payment_remaining']==='840.00','Balances');
check($r===C::propose($c,$s,$p),'Deterministic DTO');
check($r===C::propose(array_reverse($c,true),array_reverse($s,true),array_reverse($p,true)),'Key order independent');
$c['amount']=['unit'=>'DOCUMENT','value'=>'58.00','currency'=>'USD'];$d=C::propose($c,$s,$p);
check($d['document_paid']===$r['document_paid'] && $d['payment_allocation']===$r['payment_allocation'],'Explicit units equivalent');
check($d['command_digest']!==$r['command_digest'],'Intent digest distinct');
$s['document_currency']='MXN'; $s['equivalence']='1';$s['previous_balance']='2000.00';$c['amount']=['unit'=>'PAYMENT','value'=>'1160.00','currency'=>'MXN'];
check(C::propose($c,$s,$p)['document_paid']==='1160.00','Same currency');
[$c,$s,$p]=fixture();$s['payment_currency']='EUR';$c['amount']['currency']='EUR';
check(C::propose($c,$s,$p)['document_paid']==='58.00','Two foreign currencies');
[$c,$s,$p]=fixture();$c['amount']['value']='100000000';$s['payment_available']='100000000';$s['equivalence']='0.0000000001';
check(C::propose($c,$s,$p)['document_paid']==='0.01','Ten decimal equivalence minimum');
[$c,$s,$p]=fixture();$c['amount']['value']='2000.00';
$r=C::propose($c,$s,$p);check($r['outstanding_balance']==='0.00' && $r['payment_remaining']==='0.00','Full settlement');
foreach(['0','3','6'] as $scale){
 [$c,$s,$p]=fixture();$p['payment_scale']=$scale;$p['document_scale']=$scale;$c['amount']['value']='20';$s['payment_available']='40';$s['previous_balance']='2';
 check(C::propose($c,$s,$p)['document_paid']===($scale==='0'?'1':'1.'.str_repeat('0',(int)$scale)),'Explicit synthetic precision');
}
rejected(function(&$c,&$s,&$p){$c['amount']['unit']='UNKNOWN';});
rejected(function(&$c,&$s,&$p){unset($c['amount']['unit']);});
rejected(function(&$c,&$s,&$p){$c['amount']['currency']='USD';});
rejected(function(&$c,&$s,&$p){$s['payment_available']='10.00';});
rejected(function(&$c,&$s,&$p){$s['previous_balance']='10.00';});
rejected(function(&$c,&$s,&$p){$c['amount']['value']='0';});
foreach([1.23,true,null,'1e2','-1','01','1.001',' 1','1.','9999999999999999999'] as $bad){rejected(function(&$c,&$s,&$p)use($bad){$c['amount']['value']=$bad;});}
rejected(function(&$c,&$s,&$p){$s['equivalence']='0.00000000001';});
rejected(function(&$c,&$s,&$p){$s['equivalence']='0';});
rejected(function(&$c,&$s,&$p){$s['document_currency']='MXN';});
rejected(function(&$c,&$s,&$p){$s['quote_reference']='another';});
rejected(function(&$c,&$s,&$p){$s['payment_revision']='8';});
rejected(function(&$c,&$s,&$p){$s['invoice_ledger_revision']='13';});
rejected(function(&$c,&$s,&$p){$s['invoice_id']='302';});
rejected(function(&$c,&$s,&$p){$s['target_state']='SIGNED';});
rejected(function(&$c,&$s,&$p){$s['source_ledger_eligible']=false;});
rejected(function(&$c,&$s,&$p){$s['source_ledger_eligible']='true';});
rejected(function(&$c,&$s,&$p){$s['next_partiality']='1000';});
rejected(function(&$c,&$s,&$p){$s['next_partiality']='0';});
rejected(function(&$c,&$s,&$p){$p['document_scale']=2;});
rejected(function(&$c,&$s,&$p){$p['document_scale']='7';});
rejected(function(&$c,&$s,&$p){$p['id']='OTHER';},ExactDrPolicyRequired::class);
rejected(function(&$c,&$s,&$p){$c['extra']='x';});
rejected(function(&$c,&$s,&$p){unset($p['sequence_policy']);});
rejected(function(&$c,&$s,&$p){$c['amount']['value']='0.01';},ExactDrPolicyRequired::class);
rejected(function(&$c,&$s,&$p){$s['equivalence']='3';$c['amount']=['unit'=>'DOCUMENT','value'=>'1.00','currency'=>'USD'];},ExactDrPolicyRequired::class);
rejected(function(&$c,&$s,&$p){$s['equivalence']='999999999999999999';$c['amount']['value']='2';});
[$c,$s,$p]=fixture();$s['next_partiality']='999';check(C::propose($c,$s,$p)['partiality']==='999','Partiality upper bound');
$before=[$c,$s,$p];C::propose($c,$s,$p);check([$c,$s,$p]===$before,'Inputs immutable');
echo json_encode(['checks'=>$count,'result'=>'PASS','scope'=>'pure synthetic exact-only proposals; no DB/IO'],JSON_PRETTY_PRINT).PHP_EOL;
