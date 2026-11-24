<?php
declare(strict_types=1);

use gamboamartin\facturacion\models\_formato_pdf_complemento;
use PHPUnit\Framework\TestCase;

final class FormatoPdfComplementoTest extends TestCase
{
    protected function setUp(): void { require_once RAIZ_REPOSITORIO_PRUEBAS.'/orm/_formato_pdf_complemento.php'; }

    public function testSeleccionExplicitaSoloParaComplemento(): void
    {
        self::assertSame('aguaban', _formato_pdf_complemento::seleccionar((object)['formato_pdf_complemento_pago'=>'aguaban','proyecto_custom_pdf'=>'otro','plantilla_tipo'=>'ivitec']));
        self::assertSame('estandar', _formato_pdf_complemento::seleccionar((object)['formato_pdf_complemento_pago'=>'estandar','proyecto_custom_pdf'=>'aguaban']));
    }
    public function testConfiguracionAnteriorConservaCompatibilidad(): void
    {
        self::assertSame('aguaban', _formato_pdf_complemento::seleccionar((object)['proyecto_custom_pdf'=>'aguaban']));
        self::assertSame('aguaban', _formato_pdf_complemento::seleccionar((object)['formato_pdf_complemento_pago'=>'','proyecto_custom_pdf'=>'aguaban']));
        self::assertSame('estandar', _formato_pdf_complemento::seleccionar((object)[]));
    }
    public function testOpcionesDesconocidasNoSonRutasNiClasesDinamicas(): void
    {
        foreach (['../aguaban','otro','AGUABAN',['aguaban'],true] as $v) {
            $error = null;
            try { _formato_pdf_complemento::seleccionar((object)['formato_pdf_complemento_pago'=>$v]); }
            catch (InvalidArgumentException $e) { $error = $e; }
            self::assertInstanceOf(InvalidArgumentException::class, $error);
        }
    }
}
