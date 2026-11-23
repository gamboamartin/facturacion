<?php
declare(strict_types=1);

use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\_timbrado_guardia;
use gamboamartin\facturacion\models\_transacciones_fc;
use gamboamartin\facturacion\models\fc_factura;
use PHPUnit\Framework\TestCase;
use Pruebas\TimbradoGuardia\Conexion;

/** Ejecuta el override real de factura con padre fiscal simulado, sin PAC ni configuración. */
final class TimbradoModeloTest extends TestCase
{
    private Conexion $pdo;
    private fc_factura $modelo;
    private array $argumentos;

    protected function setUp(): void
    {
        require_once __DIR__.'/soporte_guardia.php';
        require_once __DIR__.'/soporte_modelo.php';
        require_once RAIZ_REPOSITORIO_PRUEBAS.'/orm/bloqueo_uso_csd.php';
        require_once RAIZ_REPOSITORIO_PRUEBAS.'/orm/_timbrado_guardia.php';
        require_once RAIZ_REPOSITORIO_PRUEBAS.'/orm/fc_factura.php';
        errores::$error = false;
        _transacciones_fc::$llamadas = [];
        _transacciones_fc::$fases = [];
        _transacciones_fc::$modo = 'exito';
        $this->pdo = new Conexion();
        $this->modelo = new fc_factura($this->pdo);
        $clases = ['modelo_documento' => '_doc', 'modelo_etapa' => '_etapa', 'modelo_partida' => '_partida',
            'modelo_predial' => '_cuenta_predial', 'modelo_relacion' => '_relacion', 'modelo_relacionada' => '_relacionada',
            'modelo_retencion' => '_data_impuestos', 'modelo_sello' => '_sellado', 'modelo_traslado' => '_data_impuestos',
            'modelo_uuid_ext' => '_uuid_ext'];
        foreach ($clases as $parametro => $clase) {
            $nombre = 'gamboamartin\\facturacion\\models\\'.$clase;
            $this->argumentos[$parametro] = new $nombre();
        }
        $this->argumentos['registro_id'] = 1;
    }

    public function testModeloConectaGuardiaYHooksAntesDePersistir(): void
    {
        $resultado = $this->modelo->timbra_xml(...$this->argumentos);
        self::assertSame('00000000-0000-0000-0000-000000000001', $resultado['uuid']);
        self::assertSame(['preparando', 'enviado', 'aceptado'], _transacciones_fc::$fases);
        self::assertSame([array_values($this->argumentos)], _transacciones_fc::$llamadas);
        self::assertSame('completo', (new _timbrado_guardia($this->pdo))->estado(1)['estado']);
        self::assertFalse(errores::$error);
    }

    public function testModeloConservaErrorLegacyYRespuestaParaRevision(): void
    {
        _transacciones_fc::$modo = 'error_local';
        self::assertSame(['error' => true, 'mensaje' => 'Fallo local ficticio'], $this->modelo->timbra_xml(...$this->argumentos));
        self::assertTrue(errores::$error);
        self::assertSame('revision_requerida', (new _timbrado_guardia($this->pdo))->estado(1)['estado']);
        self::assertNotEmpty($this->pdo->intentos[1]['xml_respuesta']);
    }

    public function testModeloSinMigracionNoEntraAlTimbradoLegacy(): void
    {
        $this->pdo->esquema = false;
        $resultado = $this->modelo->timbra_xml(...$this->argumentos);
        self::assertTrue($resultado['error']);
        self::assertTrue(errores::$error);
        self::assertStringContainsString('migración', $resultado['mensaje']);
        self::assertSame([], _transacciones_fc::$llamadas);
    }

    public function testModeloNoReenviaCuandoExisteUuidLocal(): void
    {
        $uuid = '00000000-0000-0000-0000-000000000001';
        $this->pdo->facturas[1]['folio_fiscal'] = $uuid;
        $this->pdo->sellos[1] = [$uuid];
        $resultado = $this->modelo->timbra_xml(...$this->argumentos);
        self::assertTrue($resultado['error']);
        self::assertTrue(errores::$error);
        self::assertSame('Error: la factura ya ha sido timbrada', $resultado['mensaje']);
        self::assertSame($uuid, $resultado['data']['uuid']);
        self::assertTrue($resultado['data']['ya_timbrada']);
        self::assertSame([], _transacciones_fc::$llamadas);
    }

    public function testModeloSanitizaExcepcionPosteriorAlEnvio(): void
    {
        _transacciones_fc::$modo = 'timeout';
        $resultado = $this->modelo->timbra_xml(...$this->argumentos);
        self::assertTrue($resultado['error']);
        self::assertStringNotContainsString('SECRETO', json_encode($resultado));
        self::assertSame('revision_requerida', (new _timbrado_guardia($this->pdo))->estado(1)['estado']);
    }
}
