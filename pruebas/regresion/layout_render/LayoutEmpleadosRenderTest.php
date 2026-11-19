<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/** Vista y JavaScript reales; controlador y fragmentos ficticios, sin ORM, BD ni red. */
final class LayoutEmpleadosRenderTest extends TestCase
{
    private string $temporal = '';

    private const ENCABEZADOS = [
        'Id', 'Clave Empleado', 'Codigo Postal', 'NSS', 'RFC', 'CURP',
        'NOMBRE COMPLETO', 'NETO A DEPOSITAR', 'BANCO', 'CUENTA',
        'CLABE INTERBANCARIA', 'TARJETA', 'EMAIL', 'UUID', 'ERROR',
        'DESCARGA XML', 'DESCARGA PDF', 'DESCARGA ZIP', 'REGENERA PDF',
        'CANCELAR RECIBO', 'MODIFICAR', 'TIMBRA',
    ];

    protected function setUp(): void
    {
        require_once __DIR__ . '/dobles.php';
        $this->temporal = sys_get_temp_dir() . '/fc-layout-render-' . bin2hex(random_bytes(8));
        mkdir($this->temporal, 0700);
        mkdir($this->temporal . '/head', 0700);
        foreach (['head/title.php', 'head/subtitulo.php', 'mensajes.php'] as $fragmento) {
            file_put_contents(
                $this->temporal . '/' . $fragmento,
                '<span data-fragmento-prueba="' . $fragmento . '">Fragmento ficticio</span>'
            );
        }
        \config\views::$rutaTemporal = $this->temporal;
    }

    protected function tearDown(): void
    {
        \config\views::$rutaTemporal = '';
        if ($this->temporal !== '') {
            foreach (['head/title.php', 'head/subtitulo.php', 'mensajes.php'] as $fragmento) {
                if (is_file($this->temporal . '/' . $fragmento)) {
                    unlink($this->temporal . '/' . $fragmento);
                }
            }
            if (is_dir($this->temporal . '/head')) {
                rmdir($this->temporal . '/head');
            }
            if (is_dir($this->temporal)) {
                rmdir($this->temporal);
            }
        }
    }

    private function render(array $filas): DOMXPath
    {
        $controlador = (object)['fc_rows_layout' => $filas];
        // Cualquier warning/notice del render hace fallar el caso, aunque el HTML parezca válido.
        set_error_handler(static function (int $nivel, string $mensaje, string $archivo, int $linea): never {
            throw new ErrorException($mensaje, 0, $nivel, $archivo, $linea);
        });
        ob_start();
        try {
            include RAIZ_REPOSITORIO_PRUEBAS . '/views/fc_layout_nom/ver_empleados.php';
            $html = ob_get_contents();
        } finally {
            ob_end_clean();
            restore_error_handler();
        }
        $dom = new DOMDocument();
        $antes = libxml_use_internal_errors(true);
        try {
            self::assertTrue($dom->loadHTML('<?xml encoding="UTF-8">' . $html));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($antes);
        }
        $xpath = new DOMXPath($dom);
        self::assertCount(1, $xpath->query('//table[@id="tabla_empleados"]'));
        foreach (['head/title.php', 'head/subtitulo.php', 'mensajes.php'] as $fragmento) {
            self::assertCount(1, $xpath->query('//*[@data-fragmento-prueba="' . $fragmento . '"]'));
        }
        $encabezados = $xpath->query('//table[@id="tabla_empleados"]/thead/tr/th');
        self::assertCount(22, $encabezados);
        self::assertSame(self::ENCABEZADOS, array_map(
            static fn(DOMNode $n): string => trim($n->textContent),
            iterator_to_array($encabezados)
        ));
        return $xpath;
    }

    private function filaFicticia(int $numero): stdClass
    {
        $fila = (object)[
            'fc_row_layout_id' => (string)(9000 + $numero),
            'fc_row_layout_cve_empleado' => 'EMPLEADO-FICTICIO-' . $numero,
            'fc_row_layout_cp' => 'CP-FICTICIO-' . $numero,
            'fc_row_layout_nss' => 'NSS-FICTICIO-' . $numero,
            'fc_row_layout_rfc' => 'RFC-FICTICIO-' . $numero,
            'fc_row_layout_curp' => 'CURP-FICTICIA-' . $numero,
            'fc_row_layout_nombre_completo' => 'Persona ficticia ' . $numero,
            'fc_row_layout_neto_depositar' => $numero . '00.00',
            'fc_row_layout_banco' => 'Banco ficticio ' . $numero,
            'fc_row_layout_cuenta' => 'CUENTA-FICTICIA-' . $numero,
            'fc_row_layout_clabe' => 'CLABE-FICTICIA-' . $numero,
            'fc_row_layout_tarjeta' => 'TARJETA-FICTICIA-' . $numero,
            'fc_row_layout_email' => 'persona' . $numero . '@example.invalid',
            'fc_row_layout_uuid' => 'UUID-FICTICIO-' . $numero,
            'fc_row_layout_error' => '',
        ];
        foreach (['descarga_xml', 'descarga_pdf', 'descarga_zip', 'regenera_pdf',
            'cancelar_recibo', 'modifica', 'timbra', 'retimbra'] as $accion) {
            $fila->{'btn_' . $accion} = '<button type="button" data-accion-prueba="' . $accion
                . '">' . $accion . ' ficticio ' . $numero . '</button>';
        }
        return $fila;
    }

    public function testVistaVaciaConservaVeintidosEncabezadosSinFilas(): void
    {
        $xpath = $this->render([]);
        self::assertCount(1, $xpath->query('//table[@id="tabla_empleados"]/tbody'));
        self::assertCount(0, $xpath->query('//table[@id="tabla_empleados"]/tbody/tr'));
        self::assertCount(0, $xpath->query('//table[@id="tabla_empleados"]/tbody//td'));
    }

    public function testVistaConDosFilasMantieneVeintidosDatosPorFila(): void
    {
        $filas = [$this->filaFicticia(1), $this->filaFicticia(2)];
        $xpath = $this->render($filas);
        $renglones = $xpath->query('//table[@id="tabla_empleados"]/tbody/tr');
        self::assertCount(2, $renglones);
        foreach ($renglones as $indice => $renglon) {
            $celdas = $xpath->query('./td', $renglon);
            self::assertCount(22, $celdas, 'Cada fila debe corresponder a los 22 encabezados.');
            $esperados = array_slice(array_values((array)$filas[$indice]), 0, 15);
            foreach ($esperados as $posicion => $valor) {
                self::assertSame($valor, trim($celdas->item($posicion)->textContent));
            }
            foreach (['descarga_xml', 'descarga_pdf', 'descarga_zip', 'regenera_pdf',
                'cancelar_recibo', 'modifica'] as $posicion => $accion) {
                self::assertCount(1, $xpath->query(
                    './button[@data-accion-prueba="' . $accion . '"]', $celdas->item(15 + $posicion)
                ));
            }
            self::assertCount(2, $xpath->query('./button', $celdas->item(21)));
            self::assertCount(1, $xpath->query('./button[@data-accion-prueba="timbra"]', $celdas->item(21)));
            self::assertCount(1, $xpath->query('./button[@data-accion-prueba="retimbra"]', $celdas->item(21)));
        }
    }

    public function testJavaScriptInicializaTablaYTargetsDentroDelRango(): void
    {
        $xpath = $this->render([]);
        $js = file_get_contents(RAIZ_REPOSITORIO_PRUEBAS . '/js/fc_layout_nom/ver_empleados.js');
        self::assertIsString($js);
        $patron = <<<'REGEX'
~\$\(\s*(['"])#([A-Za-z][\w-]*)\1\s*\)\s*\.\s*DataTable\s*\(\s*\{~
REGEX;
        self::assertSame(1, preg_match_all($patron, $js, $inicios));
        self::assertSame('tabla_empleados', $inicios[2][0]);
        self::assertCount(1, $xpath->query('//table[@id="' . $inicios[2][0] . '"]'));
        self::assertSame(1, preg_match(
            '~\bcolumnDefs\s*:\s*\[(?<definitions>(?:\s*\{[^{}]*\}\s*,?)+)\s*\]~s', $js, $defs
        ));
        self::assertGreaterThan(0, preg_match_all('~\{[^{}]*\}~s', $defs['definitions'], $objetos));
        $columnas = $xpath->query('//table[@id="tabla_empleados"]/thead/tr/th')->length;
        foreach ($objetos[0] as $objeto) {
            self::assertSame(1, preg_match('~\btargets\s*:\s*\[([^\]]+)\]~', $objeto, $targets));
            foreach (explode(',', $targets[1]) as $token) {
                $token = trim($token);
                self::assertMatchesRegularExpression('/^-?\d+$/D', $token);
                // DataTables también permite índices desde el final: -1 es la última columna.
                self::assertGreaterThanOrEqual(-$columnas, (int)$token);
                self::assertLessThan($columnas, (int)$token);
            }
        }
    }
}
