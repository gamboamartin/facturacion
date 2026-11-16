<?php
/** Source only: shared script version applies exclusively to NC, without changing PHP blocks. */
$root=dirname(__DIR__,2);
$nc=file_get_contents($root.'/views/fc_nota_credito/modifica.php');
$hash=substr(hash_file('sha256',$root.'/js/_facturacion.js'),0,12);
$original='<script src="<?php echo (new \\config\\generales())->url_base."js/_facturacion.js" ?>"></script>';
$versioned=str_replace('?>"','?>?v='.$hash.'"',$original);
if(substr_count($nc,$versioned)!==1||substr_count($nc,'js/_facturacion.js')!==1)throw new RuntimeException('NC shared version missing or duplicate');
if(hash('sha256',str_replace($versioned,$original,$nc))!=='1699a21240871c715729262b5bf9a7effaead819314fb174c6f2c3197a5cf1ea')throw new RuntimeException('Unrelated NC view changes');
if(hash_file('sha256',$root.'/views/fc_factura/modifica.php')!=='39d2e5e86fe9582426ec4d976093a5017ed0f44fb43e56f3be51a899a435c03b')throw new RuntimeException('Factura view changed');
echo "PASS: exact NC-only shared-script version delta, single include, matching JS hash; factura unchanged\n";
