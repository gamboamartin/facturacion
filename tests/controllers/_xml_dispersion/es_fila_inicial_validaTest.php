<?php
namespace gamboamartin\facturacion\tests\controllers\_xml_dispersion;
use PHPUnit\Framework\TestCase;
use gamboamartin\facturacion\controllers\_xls_dispersion;

final class es_fila_inicial_validaTest extends TestCase
{
    private _xls_dispersion $xls;

    protected function setUp(): void
    {
        $this->xls = new _xls_dispersion();
    }

    /**
     * @dataProvider providerFilas
     */
    public function testEsFilaInicialValida(int $fila, bool $esperado): void
    {
        $resultado = $this->invokeEsFilaInicialValida($fila);
        $this->assertSame($esperado, $resultado);
    }

    public function providerFilas(): array
    {
        return [
            'fila válida mínima'   => [1, true],
            'fila válida grande'   => [100, true],
            'fila cero'            => [0, false],
            'fila negativa'        => [-5, false],
        ];
    }

    /**
     * Helper para invocar el método privado con Reflection.
     */
    private function invokeEsFilaInicialValida(int $fila): bool
    {
        $ref = new \ReflectionClass(_xls_dispersion::class);
        $method = $ref->getMethod('es_fila_inicial_valida');
        return $method->invokeArgs($this->xls, [$fila]);
    }
}