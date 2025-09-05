<?php
namespace gamboamartin\facturacion\tests\controllers\_xml_dispersion;

use PHPUnit\Framework\TestCase;
use gamboamartin\facturacion\controllers\_xls_dispersion;
use ReflectionClass;

final class es_columna_validaTest extends TestCase
{
    /**
     * Helper para invocar el método privado es_columna_valida()
     */
    private function callEsColumnaValida(_xls_dispersion $obj, string $col): bool
    {
        $ref = new ReflectionClass($obj);
        $method = $ref->getMethod('es_columna_valida');
        return $method->invoke($obj, $col);
    }

    /**
     * @dataProvider providerColumnas
     */
    public function testEsColumnaValida(string $entrada, bool $esperado): void
    {
        $cls = new _xls_dispersion();
        $resultado = $this->callEsColumnaValida($cls, $entrada);

        $this->assertSame(
            $esperado,
            $resultado,
            "Fallo al validar columna: entrada=" . var_export($entrada, true)
        );
    }

    public static function providerColumnas(): array
    {
        return [
            // ✅ Válidas
            ['A', true],
            ['Z', true],
            ['AA', true],
            ['AZ', true],
            ['ZZ', true],
            ['ABC', true],

            // ❌ Inválidas
            ['a', false],   // minúscula
            ['A1', false],  // mezcla letras y números
            ['1A', false],  // empieza con número
            ['', false],    // vacío
            [' ', false],   // espacio
            ['Á', false],   // acento
            ['A-B', false], // caracteres especiales
        ];
    }
}