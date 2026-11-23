<?php
declare(strict_types=1);

use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\_timbrado_guardia;
use PHPUnit\Framework\TestCase;
use Pruebas\TimbradoGuardia\Conexion;

final class TimbradoGuardiaTest extends TestCase
{
    private Conexion $pdo;
    private _timbrado_guardia $guardia;
    private const UUID = '00000000-0000-0000-0000-000000000001';

    protected function setUp(): void
    {
        require_once __DIR__.'/soporte_guardia.php';
        require_once RAIZ_REPOSITORIO_PRUEBAS.'/orm/bloqueo_uso_csd.php';
        require_once RAIZ_REPOSITORIO_PRUEBAS.'/orm/_timbrado_guardia.php';
        errores::$error = false;
        $this->pdo = new Conexion();
        $this->guardia = new _timbrado_guardia($this->pdo);
    }

    private function xml(): string
    {
        return '<cfdi:Comprobante xmlns:cfdi="http://www.sat.gob.mx/cfd/4"><cfdi:Complemento>'
            .'<tfd:TimbreFiscalDigital xmlns:tfd="http://www.sat.gob.mx/TimbreFiscalDigital" UUID="'.self::UUID.'"/>'
            .'</cfdi:Complemento></cfdi:Comprobante>';
    }

    private function aceptado(): void
    {
        $this->guardia->antes_pac(1);
        $this->guardia->respuesta_pac(1, (object)['xml_sellado' => $this->xml(), 'uuid' => self::UUID], false);
    }

    private function persiste(): array
    {
        $this->pdo->facturas[1]['folio_fiscal'] = self::UUID;
        $this->pdo->sellos[1] = [self::UUID];
        return ['uuid' => self::UUID];
    }

    private function rechaza(callable $operacion, string $fragmento = ''): void
    {
        try { $operacion(); self::fail('Debió impedir la operación.'); }
        catch (RuntimeException $error) {
            self::assertNotSame('', $error->getMessage());
            if ($fragmento !== '') { self::assertStringContainsString($fragmento, $error->getMessage()); }
        }
    }

    private function intento(string $estado): array
    {
        return ['intento' => str_repeat('a', 32), 'estado' => $estado, 'uuid' => '', 'codigo' => '', 'mensaje' => '', 'xml_respuesta' => null];
    }

    public function testExitoArchivaXmlAntesDePersistirYCompleta(): void
    {
        $resultado = $this->guardia->ejecutar(1, function (): array {
            self::assertSame('preparando', $this->guardia->estado(1)['estado']);
            $this->guardia->antes_pac(1);
            self::assertSame('enviado', $this->guardia->estado(1)['estado']);
            $this->guardia->respuesta_pac(1, (object)['xml_sellado' => $this->xml()], false);
            self::assertSame('aceptado', $this->guardia->estado(1)['estado']);
            self::assertSame($this->xml(), $this->pdo->intentos[1]['xml_respuesta']);
            return $this->persiste();
        });
        self::assertSame(['uuid' => self::UUID], $resultado);
        self::assertSame('completo', $this->guardia->estado(1)['estado']);
        self::assertSame(['estado', 'uuid', 'codigo', 'mensaje'], array_keys($this->guardia->estado(1)));
        self::assertCount(2, $this->pdo->liberados);
        self::assertFalse(errores::$error);
    }

    public function testSinMigracionNoInvocaOperacion(): void
    {
        $this->pdo->esquema = false;
        $this->rechaza(fn () => $this->guardia->ejecutar(1, fn () => self::fail('No debe timbrar')), 'migración');
        self::assertSame([], $this->pdo->intentos);
    }

    public function testTransaccionExternaSeRechazaSinRevertirla(): void
    {
        $this->pdo->transaccion = true;
        $this->rechaza(fn () => $this->guardia->ejecutar(1, fn () => self::fail('No debe timbrar')), 'autocommit');
        self::assertTrue($this->pdo->transaccion);
    }

    public function testAutocommitDesactivadoSeRechaza(): void
    {
        $this->pdo->auto = 0;
        $this->rechaza(fn () => $this->guardia->ejecutar(1, fn () => self::fail('No debe timbrar')), 'autocommit');
    }

    public function testFacturaOcupadaNoInvocaPac(): void
    {
        $this->pdo->factura_ocupada = true;
        $this->rechaza(fn () => $this->guardia->ejecutar(1, fn () => self::fail('No debe timbrar')), 'procesando');
        self::assertSame([], $this->pdo->intentos);
    }

    public function testCsdOcupadoLiberaFacturaSinPrepararEnvio(): void
    {
        $this->pdo->csd_ocupado = true;
        $this->rechaza(fn () => $this->guardia->ejecutar(1, fn () => self::fail('No debe timbrar')), 'CSD');
        self::assertCount(1, $this->pdo->liberados);
        self::assertSame([], $this->pdo->intentos);
    }

    public function testErrorPrevioPreservaBanderaYPermiteNuevaPreparacion(): void
    {
        $error = ['error' => true, 'mensaje' => 'Validación ficticia'];
        self::assertSame($error, $this->guardia->ejecutar(1, function () use ($error): array {
            errores::$error = true; return $error;
        }));
        self::assertTrue(errores::$error);
        self::assertSame('preparando', $this->guardia->estado(1)['estado']);
        $previo = $this->pdo->intentos[1]['intento'];
        errores::$error = false;
        $this->guardia->ejecutar(1, function (): array { $this->aceptado(); return $this->persiste(); });
        self::assertNotSame($previo, $this->pdo->intentos[1]['intento']);
        self::assertSame('completo', $this->guardia->estado(1)['estado']);
    }

    public function testFalloAlGuardarEnvioImpideLaLlamadaPac(): void
    {
        $this->pdo->fallar_envio = true;
        $llamadas = 0;
        $this->rechaza(fn () => $this->guardia->ejecutar(1, function () use (&$llamadas): void {
            $this->guardia->antes_pac(1); $llamadas++;
        }));
        self::assertSame(0, $llamadas);
        self::assertSame('preparando', $this->guardia->estado(1)['estado']);
        self::assertStringNotContainsString('privado', json_encode($this->guardia->estado(1)));
    }

    public function testTimeoutDespuesDelEnvioExigeRevisionSinReintento(): void
    {
        $this->rechaza(fn () => $this->guardia->ejecutar(1, function (): never {
            $this->guardia->antes_pac(1); throw new RuntimeException('Secreto ficticio del proveedor');
        }));
        self::assertSame('revision_requerida', $this->guardia->estado(1)['estado']);
        $this->rechaza(fn () => $this->guardia->ejecutar(1, fn () => self::fail('No debe reenviar')), 'revisión');
        self::assertStringNotContainsString('Secreto', json_encode($this->guardia->estado(1)));
    }

    public function testEstadosInciertosPersistentesImpidenNuevoEnvio(): void
    {
        foreach (['enviado', 'aceptado', 'revision_requerida', 'completo'] as $estado) {
            $this->pdo->intentos[1] = $this->intento($estado);
            $this->rechaza(fn () => $this->guardia->ejecutar(1, fn () => self::fail('No debe reenviar')), 'revisión');
            self::assertSame($estado, $this->guardia->estado(1)['estado']);
        }
    }

    public function testFalloLocalConRespuestaAceptadaConservaXmlYUuid(): void
    {
        $resultado = $this->guardia->ejecutar(1, function (): array {
            $this->aceptado(); errores::$error = true; return ['error' => true];
        });
        self::assertTrue($resultado['error']);
        self::assertTrue(errores::$error);
        self::assertSame('revision_requerida', $this->guardia->estado(1)['estado']);
        self::assertSame(self::UUID, $this->guardia->estado(1)['uuid']);
        self::assertSame($this->xml(), $this->pdo->intentos[1]['xml_respuesta']);
        self::assertArrayNotHasKey('xml_respuesta', $this->guardia->estado(1));
    }

    public function testUuidLocalEvitaOtroEnvioYConservaAvisoDeRevision(): void
    {
        $this->persiste();
        $this->pdo->intentos[1] = $this->intento('revision_requerida');
        $resultado = $this->guardia->ejecutar(1, fn () => self::fail('No debe reenviar'));
        self::assertTrue($resultado['ya_timbrada']);
        self::assertTrue($resultado['requiere_revision']);
        self::assertSame(self::UUID, $resultado['uuid']);
        self::assertSame('revision_requerida', $this->guardia->estado(1)['estado']);
    }

    public function testRechazoCfdiTipificadoPermiteCorregirSinExponerDetalle(): void
    {
        $error = ['error' => true, 'data' => [0 => 'Error', 6 => 'CFDI40130', 7 => 'Dato fiscal que no debe salir']];
        $this->guardia->ejecutar(1, function () use ($error): array {
            $this->guardia->antes_pac(1); errores::$error = true;
            $this->guardia->respuesta_pac(1, $error, true); return $error;
        });
        self::assertSame('rechazado', $this->guardia->estado(1)['estado']);
        self::assertSame('CFDI40130', $this->guardia->estado(1)['codigo']);
        self::assertStringNotContainsString('Dato fiscal', $this->guardia->estado(1)['mensaje']);
        errores::$error = false;
        $this->guardia->ejecutar(1, function (): array { $this->aceptado(); return $this->persiste(); });
        self::assertSame('completo', $this->guardia->estado(1)['estado']);
    }

    public function testTextoQuePareceRechazoNoAutorizaReenvio(): void
    {
        $this->rechaza(fn () => $this->guardia->ejecutar(1, function (): void {
            $this->guardia->antes_pac(1); errores::$error = true;
            $this->guardia->respuesta_pac(1, ['error' => true, 'mensaje' => 'CFDI40130', 'data' => 'texto'], true);
        }));
        self::assertSame('revision_requerida', $this->guardia->estado(1)['estado']);
    }

    public function testRespuestaXmlSinTfdNoContinuaPersistenciaLegacy(): void
    {
        $this->rechaza(fn () => $this->guardia->ejecutar(1, function (): void {
            $this->guardia->antes_pac(1);
            $this->guardia->respuesta_pac(1, (object)['xml_sellado' => '<no-es-cfdi/>'], false);
            self::fail('No debe continuar con XML no verificado');
        }));
        self::assertSame('revision_requerida', $this->guardia->estado(1)['estado']);
        self::assertSame('<no-es-cfdi/>', $this->pdo->intentos[1]['xml_respuesta']);
    }

    public function testCodigoCfdiFueraDeListaExigeRevisionSinReenvio(): void
    {
        foreach (['CFDI40101', 'CFDI40999'] as $codigo) {
            $this->pdo = new Conexion();
            $this->guardia = new _timbrado_guardia($this->pdo);
            errores::$error = false;
            $this->rechaza(fn () => $this->guardia->ejecutar(1, function () use ($codigo): void {
                $this->guardia->antes_pac(1);
                errores::$error = true;
                $this->guardia->respuesta_pac(1, ['error' => true, 'data' => [0 => 'Error', 6 => $codigo]], true);
            }));
            self::assertSame('revision_requerida', $this->guardia->estado(1)['estado']);
            $this->rechaza(fn () => $this->guardia->ejecutar(1, fn () => self::fail('No debe reenviar')), 'revisión');
        }
    }

    public function testCambioDeCsdAntesDelEnvioLoImpide(): void
    {
        $this->rechaza(fn () => $this->guardia->ejecutar(1, function (): void {
            $this->pdo->facturas[1]['fc_csd_id'] = 8;
            $this->guardia->antes_pac(1); self::fail('No debe enviar');
        }));
        self::assertSame('preparando', $this->guardia->estado(1)['estado']);
    }

    public function testEvidenciaLocalInvalidaNoDevuelveExitoNiEnvia(): void
    {
        $this->pdo->facturas[1]['folio_fiscal'] = 'dato inconsistente ficticio';
        $this->rechaza(fn () => $this->guardia->ejecutar(1, fn () => self::fail('No debe enviar')), 'inconsistente');
        self::assertSame('revision_requerida', $this->guardia->estado(1)['estado']);
    }

    public function testSelloAparecidoDurantePreparacionImpideEnvio(): void
    {
        $this->rechaza(fn () => $this->guardia->ejecutar(1, function (): void {
            $this->persiste();
            $this->guardia->antes_pac(1); self::fail('No debe enviar');
        }));
        self::assertSame('preparando', $this->guardia->estado(1)['estado']);
    }

    public function testSinPersistenciaLocalNoDeclaraCompletoAunquePacAcepto(): void
    {
        $this->rechaza(fn () => $this->guardia->ejecutar(1, function (): array {
            $this->aceptado(); return ['uuid' => self::UUID];
        }));
        self::assertSame('revision_requerida', $this->guardia->estado(1)['estado']);
        self::assertSame(self::UUID, $this->guardia->estado(1)['uuid']);
    }

    public function testUuidLocalDiferenteDeRespuestaRequiereRevision(): void
    {
        $this->rechaza(fn () => $this->guardia->ejecutar(1, function (): array {
            $this->aceptado();
            $otro = '00000000-0000-0000-0000-000000000002';
            $this->pdo->facturas[1]['folio_fiscal'] = $otro;
            $this->pdo->sellos[1] = [$otro];
            return ['uuid' => self::UUID];
        }));
        self::assertSame('revision_requerida', $this->guardia->estado(1)['estado']);
        self::assertSame(self::UUID, $this->guardia->estado(1)['uuid']);
    }

    public function testMarcadorSinUuidSinSelloPermitePrimerIntento(): void
    {
        $this->pdo->facturas[1]['folio_fiscal'] = ' sin uuid ';
        $resultado = $this->guardia->ejecutar(1, function (): array {
            self::assertSame(' sin uuid ', $this->pdo->facturas[1]['folio_fiscal']);
            self::assertSame('preparando', $this->guardia->estado(1)['estado']);
            $this->aceptado();
            return $this->persiste();
        });
        self::assertSame(['uuid' => self::UUID], $resultado);
        self::assertSame('completo', $this->guardia->estado(1)['estado']);
        self::assertSame(self::UUID, $this->pdo->facturas[1]['folio_fiscal']);
        self::assertSame([self::UUID], $this->pdo->sellos[1]);
        self::assertFalse(errores::$error);
    }

    public function testMarcadorSinUuidConSelloExigeRevisionSinReenvio(): void
    {
        $this->pdo->facturas[1]['folio_fiscal'] = 'SIN UUID';
        $this->pdo->sellos[1] = [self::UUID];
        $resultado = $this->guardia->ejecutar(1, fn () => self::fail('El sello existente impide otro envío'));
        self::assertTrue($resultado['ya_timbrada']);
        self::assertTrue($resultado['requiere_revision']);
        self::assertSame(self::UUID, $resultado['uuid']);
        self::assertSame('revision_requerida', $this->guardia->estado(1)['estado']);
        self::assertSame('SIN UUID', $this->pdo->facturas[1]['folio_fiscal']);
        self::assertSame([self::UUID], $this->pdo->sellos[1]);
    }
}
