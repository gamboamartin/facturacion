<?php
namespace gamboamartin\facturacion\tests\controllers\_xml_dispersion;
use PHPUnit\Framework\TestCase;
use gamboamartin\facturacion\controllers\_xls_dispersion;

final class columnas_a_escanearTest extends TestCase
{
    private _xls_dispersion $xls;

    protected function setUp(): void
    {
        $this->xls = new _xls_dispersion();
    }

    public function testColumnasAEscanearDevuelveArregloEsperado(): void
    {
        $resultado = $this->invokeColumnasAEscanear();

        $this->assertIsArray($resultado, 'El resultado debe ser un array');
        $this->assertSame(['A', 'B', 'C', 'D', 'E'], $resultado, 'El arreglo no coincide con el esperado');
        $this->assertCount(5, $resultado, 'El arreglo debe tener exactamente 5 columnas');
    }

    /**
     * Helper para invocar el método privado con Reflection.
     */
    private function invokeColumnasAEscanear(): array
    {
        $ref = new \ReflectionClass(_xls_dispersion::class);
        $method = $ref->getMethod('columnas_a_escanear');
        $method->setAccessible(true);
        return $method->invokeArgs($this->xls, []);
    }
}