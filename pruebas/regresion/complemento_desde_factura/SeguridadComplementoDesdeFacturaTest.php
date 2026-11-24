<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
final class SeguridadComplementoDesdeFacturaTest extends TestCase {
    protected function setUp():void {require_once __DIR__.'/soporte_seguridad.php';$_SESSION=[];}
    public function testSinUsuarioNoConsultaPermisos():void {$p=new PDOSeguridadCP();self::assertFalse((new ControladorSeguridadCP($p))->permiso());self::assertSame([],$p->sql);}
    public function testExigeCuatroPermisosActivosAunqueSesionTengaCache():void {
        $_SESSION=['usuario_id'=>7,'permite'=>['todo'=>true]];$p=new PDOSeguridadCP();$p->permisos=3;
        self::assertFalse((new ControladorSeguridadCP($p))->permiso());self::assertSame([[7]],$p->args);
        foreach(["u.status='activo'","g.status='activo'","ag.status='activo'","a.status='activo'","s.status='activo'","'lista','complemento_pago'","'alta_bd','modifica'"] as $parte)self::assertStringContainsString($parte,$p->sql[0]);
    }
    public function testRevocacionSeReconsultaEnLaMismaSesion():void {
        $_SESSION=['usuario_id'=>7];$p=new PDOSeguridadCP();$p->permisos=4;$c=new ControladorSeguridadCP($p);self::assertTrue($c->permiso());
        $p->permisos=0;$p->transaccion=true;self::assertFalse($c->permiso());self::assertCount(2,$p->sql);self::assertStringContainsString('FOR UPDATE',$p->sql[1]);
    }
    private function rechazada(string $modo,int $consultas):void {
        $pipes=[];$p=proc_open([PHP_BINARY,__DIR__.'/solicitud_denegada.php',$modo],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,null,
            ['FUENTE_FACTURACION'=>RAIZ_REPOSITORIO_PRUEBAS]);self::assertIsResource($p);fclose($pipes[0]);$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);
        self::assertSame(0,proc_close($p));$r=json_decode($out,true,flags:JSON_THROW_ON_ERROR);$e=json_decode($err,true,flags:JSON_THROW_ON_ERROR);
        self::assertFalse($r['ok']);self::assertSame(403,$e['http']);self::assertSame($consultas,$e['consultas']);self::assertArrayNotHasKey('datos',$r);
    }
    private function salidaLegacy(string $modo,string $estado):void {
        $pipes=[];$p=proc_open([PHP_BINARY,__DIR__.'/salida_legacy_cp.php',$modo,$estado],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,null,
            ['FUENTE_FACTURACION'=>RAIZ_REPOSITORIO_PRUEBAS]);self::assertIsResource($p);fclose($pipes[0]);$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);
        self::assertSame(0,proc_close($p));self::assertStringNotContainsString('XML-CREDENCIAL-FICTICIA',$out);$r=json_decode($err,true,flags:JSON_THROW_ON_ERROR);
        self::assertSame(1,$r['legacy_invocado']);self::assertSame($estado!=='ausente',$r['sesion_presente']);self::assertSame(in_array($estado,['array','true'],true)?['previo']:null,$r['sesion']);self::assertSame(['previo'],$r['out']);self::assertSame($estado==='true',$r['error']);
        if($modo!=='exit')self::assertFalse(json_decode($out,true,flags:JSON_THROW_ON_ERROR)['ok']);
    }
    public function testFalloLegacyNoFiltraNiEnsuciaSesionPosterior():void {foreach(['array','null','ausente','true'] as $estado)$this->salidaLegacy('error',$estado);}
    public function testExitLegacyNoFiltraNiEnsuciaSesionPosterior():void {foreach(['array','null','ausente','true'] as $estado)$this->salidaLegacy('exit',$estado);}
    public function testPostSinCsrfNoConsultaNiCrea():void {$this->rechazada('ausente',0);}
    public function testPostCsrfAjenoNoConsultaNiCrea():void {$this->rechazada('ajeno',0);}
    public function testPostSinPermisoConCsrfValidoNoCrea():void {$this->rechazada('sin_permiso',1);}
}
