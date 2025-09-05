<?php
namespace gamboamartin\facturacion\tests\controllers\_xml_dispersion;

use PHPUnit\Framework\TestCase;
use gamboamartin\facturacion\controllers\_xls_dispersion;
use ReflectionClass;

final class es_fila_validaTest extends TestCase
{
    /**
     * Helper para invocar el método privado es_fila_valida()
     */
    private function callEsFilaValida(_xls_dispersion $obj, int $fila): bool
    {
        $ref = new ReflectionClass($obj);
        $method = $ref->getMethod('es_fila_valida');

        return $method->invoke($obj, $fila);
    }

    /**
     * @dataProvider providerFilas
     */
    public function testEsFilaValida(int $entrada, bool $esperado): void
    {
        $cls = new _xls_dispersion();
        $resultado = $this->callEsFilaValida($cls, $entrada);

        $this->assertSame(
            $esperado,
            $resultado,
            "Fallo al validar fila: entrada=" . var_export($entrada, true)
        );
    }

    public static function providerFilas(): array
    {
        return [
            // ✅ Válidos
            [1, true],
            [2, true],
            [100, true],

            // ❌ Inválidos
            [0, false],   // límite inferior no válido
            [-1, false],  // negativo
            [-100, false] // negativo grande
        ];
    }
}