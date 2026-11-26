<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use gamboamartin\facturacion\controllers\controlador_com_agente;
use gamboamartin\facturacion\models\com_agente;
final class ConfiguracionAgentesTest extends TestCase {
    private function controlador(string $fixture): controlador_com_agente {
        require __DIR__.'/config_'.$fixture.'.php';
        require __DIR__.'/soporte.php';
        require RAIZ_REPOSITORIO_PRUEBAS.'/orm/com_agente.php';
        require RAIZ_REPOSITORIO_PRUEBAS.'/controllers/controlador_com_agente.php';
        $link = new class extends PDO { public function __construct() {} };
        \gamboamartin\comercial\models\com_agente::$registros = [
            ['com_agente_status'=>'activo','com_agente_com_tipo_agente_id'=>'7'],
            ['com_agente_status'=>'inactivo','com_agente_com_tipo_agente_id'=>7],
            ['com_agente_status'=>'activo','com_agente_com_tipo_agente_id'=>2],
            ['com_agente_status'=>'activo','com_agente_com_tipo_agente_id'=>-1],
        ];
        return new controlador_com_agente($link);
    }
    private function verificar(controlador_com_agente $c, int $tipo, int $asesores, bool $relacion): void {
        self::assertSame($tipo, com_agente::tipo_agente_asesor_configurado());
        self::assertSame($tipo, $c->tipo_agente_asesor_id);
        self::assertSame($relacion, $c->aplica_relacion_agentes);
        self::assertSame(['delegado'=>true], $c->lista(false));
        self::assertSame(4, $c->info_total_agentes);
        self::assertSame(3, $c->info_agentes_activos);
        self::assertSame($asesores, $c->info_asesores);
    }
    public function testSinPropiedadNoFallaNiCuentaAsesorFicticio(): void {
        $this->verificar($this->controlador('ausente'), -1, 0, false);
    }
    public function testTipoConfiguradoMantieneIdYRelacion(): void {
        $this->verificar($this->controlador('definida'), 7, 2, true);
    }
    public function testConfiguracionNulaSeTrataComoAusente(): void {
        $this->verificar($this->controlador('nula'), -1, 0, false);
    }
    public function testPropiedadSinInicializarNoFalla(): void {
        $this->verificar($this->controlador('sin_inicializar'), -1, 0, false);
    }
    public function testIdNoPositivoNoSeInterpretaComoAsesor(): void {
        $c=$this->controlador('definida');
        \config\generales::$tipo_agente_asesor=0;
        self::assertSame(-1,com_agente::tipo_agente_asesor_configurado());
        $c->lista(false); self::assertSame(0,$c->info_asesores);
        \config\generales::$tipo_agente_asesor=-1;
        self::assertSame(-1,com_agente::tipo_agente_asesor_configurado());
        $c->lista(false); self::assertSame(0,$c->info_asesores);
    }
    public function testConfiguracionLegacyInvalidaNoActivaAsesor(): void {
        $c=$this->controlador('legacy');
        foreach ([true, 1.5, 'sin-id', '', '0'] as $valor) {
            \config\generales::$tipo_agente_asesor=$valor;
            self::assertSame(-1,com_agente::tipo_agente_asesor_configurado());
            $c->lista(false); self::assertSame(0,$c->info_asesores);
        }
    }
    public function testIdNumericoLegacyConservado(): void {
        $this->verificar($this->controlador('legacy'), 7, 2, false);
    }
}
