<?php
require __DIR__.'/PdoTransport.php';
$observed='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
foreach([$observed,'STRICT_TRANS_TABLES','STRICT_ALL_TABLES','STRICT_TRANS_TABLES,STRICT_ALL_TABLES'] as $v){
 $out=PdoTransport::sessionMode($v);demand(in_array('STRICT_ALL_TABLES',explode(',',$out),true),'strict_required');
 demand(array_diff(explode(',',$v),explode(',',$out))===[],'no_mode_removed');
 demand(PdoTransport::sessionMode($out)===$out,'idempotent');
}
foreach(['','NO_ENGINE_SUBSTITUTION','strict_trans_tables'] as $v){$rejected=false;try{PdoTransport::sessionMode($v);}catch(MigrationFailure $e){$rejected=$e->getMessage()==='strict_session_required';}demand($rejected,'nonstrict_rejected');}
echo "7 synthetic mode cases PASS; no DB connection\n";
