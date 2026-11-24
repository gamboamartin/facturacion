<?php
declare(strict_types=1);

use gamboamartin\facturacion\models\_complemento_desde_factura;
use PHPUnit\Framework\TestCase;

/** Frontera de autorización del servicio real, sin autoload ni configuración privada. */
final class ComplementoDesdeFacturaTest extends TestCase
{
    private PDO $pdo;
    private int $llamadas = 0;

    protected function setUp():void
    {
        require_once RAIZ_REPOSITORIO_PRUEBAS.'/orm/_complemento_desde_factura.php';
        $this->pdo = new class extends PDO {
            public int $consultas = 0;
            public function __construct() {}
            public function prepare(string $query,array $options=[]):PDOStatement|false {$this->consultas++;throw new LogicException('SQL prohibido en esta prueba');}
            public function query(string $query,?int $fetchMode=null,mixed ...$fetchModeArgs):PDOStatement|false {$this->consultas++;throw new LogicException('SQL prohibido en esta prueba');}
            public function exec(string $statement):int|false {$this->consultas++;throw new LogicException('Escrituras prohibidas');}
            public function inTransaction():bool {return false;}
            public function beginTransaction():bool {$this->consultas++;throw new LogicException('Transacciones prohibidas');}
        };
    }

    private function servicio(int $usuario,callable $permiso):_complemento_desde_factura
    {
        return new _complemento_desde_factura($this->pdo,$usuario,$permiso,function(array $cabecera,array $pago,array $docto):int {$this->llamadas++;throw new LogicException('No debe crear estructura');});
    }

    private function datos():array
    {
        return ['clave_solicitud'=>str_repeat('a',32),'monto'=>'116.00','fecha_pago'=>'2026-11-24T12:00',
            'cat_sat_forma_pago_id'=>'1','com_tipo_cambio_id'=>'1','fc_csd_id'=>'1'];
    }

    private function sinAcceso(callable $operacion):void
    {
        $error=null;
        try {$operacion();}catch(Throwable $e){$error=$e;}
        self::assertInstanceOf(InvalidArgumentException::class,$error);
        self::assertSame(0,$this->pdo->consultas);
        self::assertSame(0,$this->llamadas);
    }

    public function testPrepararSinPermisoNoConsultaFactura():void
    {
        $this->sinAcceso(fn()=>$this->servicio(7,fn()=>false)->preparar(1));
    }

    public function testCrearSinPermisoNoIniciaTransaccionNiCallback():void
    {
        $this->sinAcceso(fn()=>$this->servicio(7,fn()=>false)->crear(1,$this->datos()));
    }

    public function testUsuarioCeroNoPuedePrepararAunqueCallbackAutorice():void
    {
        $this->sinAcceso(fn()=>$this->servicio(0,fn()=>true)->preparar(1));
    }

    public function testUsuarioCeroNoPuedeCrearAunqueCallbackAutorice():void
    {
        $this->sinAcceso(fn()=>$this->servicio(0,fn()=>true)->crear(1,$this->datos()));
    }
    public function testIdFacturaInvalidoNoLeeContexto():void
    {
        $this->sinAcceso(fn()=>$this->servicio(7,fn()=>true)->preparar(0));
    }

    public function testImportesNoPositivosOAmbiguosNoAccedenSql():void
    {
        foreach(['0','-1','NaN','INF','1e2','1,00','1.234','1;DROP',[]] as $valor){
            $this->sinAcceso(fn()=>$this->servicio(7,fn()=>true)->crear(1,array_replace($this->datos(),['monto'=>$valor])));
        }
    }

    public function testClaveSolicitudInvalidaNoAccedeSql():void
    {
        foreach(['',str_repeat('a',31),str_repeat('g',32),[]] as $valor){
            $this->sinAcceso(fn()=>$this->servicio(7,fn()=>true)->crear(1,array_replace($this->datos(),['clave_solicitud'=>$valor])));
        }
    }

    public function testFechaInexistenteOIncompletaNoAccedeSql():void
    {
        foreach(['','2026-02-30T12:00','2026-11-24T25:00','2026-11-24',[]] as $valor){
            $this->sinAcceso(fn()=>$this->servicio(7,fn()=>true)->crear(1,array_replace($this->datos(),['fecha_pago'=>$valor])));
        }
    }

    public function testCatalogosCompuestosONegativosNoAccedenSql():void
    {
        foreach(['fc_csd_id','cat_sat_forma_pago_id','com_tipo_cambio_id'] as $campo){
            foreach([[],0,-1,'1 OR 1=1'] as $valor)$this->sinAcceso(fn()=>$this->servicio(7,fn()=>true)->crear(1,array_replace($this->datos(),[$campo=>$valor])));
        }
    }
}
