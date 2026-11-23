<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;

/** Constructor real con padre ficticio: sesión anterior a la migración, sin consultas SQL. */
final class InformacionGlobalSesionTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__.'/../fc_factura/dobles_factura.php';
        require_once RAIZ_REPOSITORIO_PRUEBAS.'/orm/fc_factura.php';
        $_SESSION=['campos_tabla'=>['fc_factura'=>['esquema_anterior'],'fc_nota_credito'=>['conservar']],
            'columnas_completas'=>['fc_factura'=>['esquema_anterior'],'fc_nota_credito'=>['conservar']]];
    }
    private function construir(): void
    {
        $pdo=new class extends PDO {
            public function __construct() {}
            public function query(string $query, ?int $fetchMode=null, mixed ...$fetchModeArgs): PDOStatement|false {
                throw new RuntimeException('SQL prohibido en contrato de sesión');
            }
        };
        new \gamboamartin\facturacion\models\fc_factura($pdo);
    }
    public function testSesionAnteriorRenuevaSoloColumnasDeFactura(): void
    {
        $this->construir();
        self::assertArrayNotHasKey('fc_factura',$_SESSION['campos_tabla']);
        self::assertArrayNotHasKey('fc_factura',$_SESSION['columnas_completas']);
        self::assertSame(['conservar'],$_SESSION['campos_tabla']['fc_nota_credito']);
        self::assertSame(['conservar'],$_SESSION['columnas_completas']['fc_nota_credito']);
    }
    public function testSegundaInstanciaConservaEsquemaYaRenovado(): void
    {
        $this->construir();
        $_SESSION['campos_tabla']['fc_factura']=['global_periodicidad','global_meses','global_anio'];
        $_SESSION['columnas_completas']['fc_factura']=['esquema_renovado'];
        $this->construir();
        self::assertSame(['global_periodicidad','global_meses','global_anio'],$_SESSION['campos_tabla']['fc_factura']);
        self::assertSame(['esquema_renovado'],$_SESSION['columnas_completas']['fc_factura']);
    }
}
