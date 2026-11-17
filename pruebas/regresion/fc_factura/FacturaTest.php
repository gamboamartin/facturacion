<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/** Contratos unitarios reales con padres y colaboradores dobles. No es integración SQL. */
final class FacturaTest extends TestCase
{
    private PDO $pdo;
    private bool $errorEsperado=false;

    protected function setUp(): void
    {
        require_once __DIR__.'/dobles_factura.php';
        $raiz = defined('RAIZ_REPOSITORIO_PRUEBAS') ? RAIZ_REPOSITORIO_PRUEBAS : getenv('RAIZ_REPO');
        if (!$raiz) { throw new RuntimeException('Debe definir RAIZ_REPO o RAIZ_REPOSITORIO_PRUEBAS'); }
        require_once $raiz.'/orm/fc_factura.php';
        require_once $raiz.'/controllers/controlador_fc_factura.php';
        \gamboamartin\errores\errores::$error = false;
        \gamboamartin\comercial\models\com_sucursal::$fallar=false;
        \gamboamartin\comercial\models\com_sucursal::$solicitudes=[];
        $this->pdo = new class extends PDO {
            public function __construct() {}
            public function prepare(string $query, array $options=[]): PDOStatement|false { throw new RuntimeException('SQL prohibido'); }
            public function query(string $query, ?int $fetchMode=null, mixed ...$fetchModeArgs): PDOStatement|false { throw new RuntimeException('SQL prohibido'); }
            public function exec(string $statement): int|false { throw new RuntimeException('SQL prohibido'); }
        };
    }

    protected function tearDown(): void
    {
        self::assertSame($this->errorEsperado, \gamboamartin\errores\errores::$error, 'Bandera de error esperada');
    }

    public function testConstructorConValidacionCritica(): void { $this->comprobarConstructor(true); }
    public function testConstructorSinValidacionCritica(): void { $this->comprobarConstructor(false); }
    public function testReporteFacturacionVacio(): void { $this->comprobarReporte('obtener_registros_reporte_facturacion', []); }
    public function testReporteFacturacionConRegistro(): void { $this->comprobarReporte('obtener_registros_reporte_facturacion', $this->registroFicticio()); }
    public function testReporteVentasVacio(): void { $this->comprobarReporte('obtener_registros_reporte_ventas', []); }
    public function testReporteVentasConRegistro(): void { $this->comprobarReporte('obtener_registros_reporte_ventas', $this->registroFicticio()); }
    public function testControladorAltaSinWs(): void { $this->comprobarControlador('alta', false); }
    public function testControladorAltaConWs(): void { $this->comprobarControlador('alta', true); }
    public function testControladorModificaSinWs(): void { $this->comprobarControlador('modifica', false); }
    public function testControladorModificaConWs(): void { $this->comprobarControlador('modifica', true); }

    private function registroFicticio(): array { return [['fc_factura_id'=>900001, 'fc_factura_total'=>116.0]]; }

    private function comprobarConstructor(bool $criticos): void
    {
        $modelo = new \gamboamartin\facturacion\models\fc_factura($this->pdo, $criticos);
        self::assertSame('fc_factura', $modelo->tabla);
        self::assertSame('fc_factura_id', $modelo->key_fc_id);
        self::assertSame('Factura', $modelo->etiqueta);
        self::assertSame($criticos, $modelo->valida_atributos_criticos);
        self::assertSame(['fc_factura_uuid'=>"IFNULL(fc_factura.folio_fiscal,'SIN UUID')"], $modelo->columnas_extra);
    }

    private function comprobarReporte(string $metodo, array $filas): void
    {
        $modelo = new \gamboamartin\facturacion\models\fc_factura($this->pdo);
        $modelo->registros = $filas;
        self::assertSame($filas, $modelo->$metodo(['fecha_inicio'=>'2026-01-01','fecha_fin'=>'2026-01-31']));
        self::assertSame([[
            'filtro'=>['fc_factura.etapa'=>'TIMBRADO'],
            'filtro_rango'=>['fc_factura.fecha'=>['valor1'=>'2026-01-01 00:00:00','valor2'=>'2026-01-31 23:59:59']]
        ]], $modelo->llamadas);
    }

    private function comprobarControlador(string $metodo, bool $ws): void
    {
        $controlador = (new ReflectionClass(\gamboamartin\facturacion\controllers\controlador_fc_factura::class))->newInstanceWithoutConstructor();
        $controlador->link = $this->pdo;
        $controlador->modelo = new \gamboamartin\facturacion\models\fc_factura($this->pdo);
        $resultado = $controlador->$metodo(false, $ws);
        self::assertSame($controlador->modelo, $controlador->modelo_entidad);
        self::assertSame([['header'=>false,'ws'=>$ws]], $controlador->llamadas);
        self::assertSame($metodo.'_ficticia', $metodo==='alta' ? $resultado['vista'] : $resultado->vista);
        if ($metodo==='modifica') {
            self::assertInstanceOf(\gamboamartin\facturacion\models\fc_partida::class, $controlador->modelo_partida);
            self::assertInstanceOf(\gamboamartin\facturacion\controllers\controlador_fc_partida::class, $controlador->ctl_partida);
        }
    }
    public function testReporteFacturacionPropagaError(): void { $this->comprobarErrorReporte('obtener_registros_reporte_facturacion'); }
    public function testReporteVentasPropagaError(): void { $this->comprobarErrorReporte('obtener_registros_reporte_ventas'); }
    private function comprobarErrorReporte(string $metodo): void
    {
        $this->errorEsperado=true;
        $modelo=new \gamboamartin\facturacion\models\fc_factura($this->pdo);
        $modelo->fallarConsulta=true;
        $resultado=$modelo->$metodo(['fecha_inicio'=>'2026-01-01','fecha_fin'=>'2026-01-31']);
        self::assertTrue($resultado['error']);
        self::assertSame('Error al obtener datos',$resultado['mensaje']);
        self::assertSame([], $resultado['data']->registros);
        self::assertCount(1,$modelo->llamadas);
    }
    public function testAltaPropagaErrorPadre(): void { $this->comprobarErrorControlador('alta','Error al generar template'); }
    public function testModificaPropagaErrorPadre(): void { $this->comprobarErrorControlador('modifica','Error al generar template'); }
    private function controladorAislado(): \gamboamartin\facturacion\controllers\controlador_fc_factura
    {
        $controlador=(new ReflectionClass(\gamboamartin\facturacion\controllers\controlador_fc_factura::class))->newInstanceWithoutConstructor();
        $controlador->link=$this->pdo;
        $controlador->modelo=new \gamboamartin\facturacion\models\fc_factura($this->pdo);
        return $controlador;
    }
    private function comprobarErrorControlador(string $metodo,string $mensaje): void
    {
        $this->errorEsperado=true;
        $controlador=$this->controladorAislado();$controlador->fallarPadre=true;
        $resultado=$controlador->$metodo(false,true);
        self::assertTrue($resultado['error']);self::assertSame($mensaje,$resultado['mensaje']);
        self::assertFalse($resultado['header']);self::assertTrue($resultado['ws']);
        self::assertSame($metodo.'_ficticia',is_array($resultado['data'])?$resultado['data']['vista']:$resultado['data']->vista);
        self::assertCount(1,$controlador->llamadas);
    }
    public function testFiltroControladorRechazaSucursalAusenteSinConsultar(): void
    {
        $anterior=$_POST;$_POST=[];
        try {
            $controlador=$this->controladorAislado();
            $resultado=$controlador->filtro_and(false,true);
            self::assertSame(['error'=>true,'mensaje'=>'Error $_POST[com_sucursal_id] debe existir','data'=>[],'header'=>false,'ws'=>true],$resultado);
            self::assertSame([],$controlador->modelo->llamadas);
        } finally { $_POST=$anterior; }
    }
    public function testFacturasSucursalCargaClienteYFiltra(): void
    {
        $modelo=new \gamboamartin\facturacion\models\fc_factura($this->pdo);$modelo->registros=$this->registroFicticio();
        self::assertSame($this->registroFicticio(),$modelo->obtener_facturas_con_sucursal(600001));
        self::assertSame([['registro_id'=>600001,'columnas'=>['com_cliente_id']]],\gamboamartin\comercial\models\com_sucursal::$solicitudes);
        self::assertSame([['filtro'=>['com_cliente.id'=>700001,'fc_factura.etapa'=>'TIMBRADO','fc_factura.asignado_fc_layout'=>'inactivo'],'filtro_rango'=>[],'columnas'=>['fc_factura_folio','fc_factura_total','com_cliente_razon_social','fc_factura_fecha','fc_factura_id']]],$modelo->llamadas);
    }
    public function testFacturasSucursalDetieneTrasFalloDeCliente(): void
    {
        $this->errorEsperado=true;\gamboamartin\comercial\models\com_sucursal::$fallar=true;
        $modelo=new \gamboamartin\facturacion\models\fc_factura($this->pdo);
        $resultado=$modelo->obtener_facturas_con_sucursal(600001);
        self::assertTrue($resultado['error']);self::assertSame('Error al obtener data de sucursal',$resultado['mensaje']);
        self::assertSame(['com_cliente_id'=>700001],$resultado['data']);self::assertSame([],$modelo->llamadas);
    }
    public function testFacturasSucursalPropagaFalloConsultaFacturas(): void
    {
        $this->errorEsperado=true;
        $modelo=new \gamboamartin\facturacion\models\fc_factura($this->pdo);$modelo->fallarConsulta=true;
        $resultado=$modelo->obtener_facturas_con_sucursal(600001);
        self::assertTrue($resultado['error']);self::assertSame('Error al obtener facturas',$resultado['mensaje']);
        self::assertCount(1,$modelo->llamadas);
    }

}
