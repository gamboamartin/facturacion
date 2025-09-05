<?php
namespace gamboamartin\facturacion\controllers;

use gamboamartin\errores\errores;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use stdClass;
use Throwable;

class _xls_dispersion{

    public static array $letras = array();

    public function __construct()
    {
        $letras = array('A','B','D','E','F','G','H','I','J','K','L','M','N','O','P','Q',
            'R','S','T','U','V','W','X','X','Z');

        $letras_b = $letras;

        $letras_bin = $letras;
        foreach ($letras as $letra) {
            foreach ($letras_b as $letra_b) {
                $letras_bin[] = $letra.$letra_b;
            }
        }

        self::$letras = $letras_bin;

    }

    private function asigna_dato_columna(array $columnas, string $key, mixed $value): array
    {
        $value = $this->normaliza_value(value: $value);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al inicializar value', data: $value);
        }
        if($value !== ''){
            $columnas[self::$letras[$key]] = $value;
        }
        return $columnas;

    }

    private function autosize(Worksheet $hoja): Worksheet
    {
        $hoja->getColumnDimension('A')->setAutoSize(true);
        $hoja->getColumnDimension('B')->setAutoSize(true);
        $hoja->getColumnDimension('C')->setAutoSize(true);
        $hoja->getColumnDimension('D')->setAutoSize(true);

        return $hoja;

    }

    private function buscar_primera_coincidencia(Worksheet $hoja, string $col, int $maxFilas, array $candidatos): int {
        $highest = min($hoja->getHighestRow(), $maxFilas);
        for ($row = 1; $row <= $highest; $row++) {
            $val = $this->valor_celda_normalizado($hoja, $col, $row);
            if ($val !== '' && in_array($val, $candidatos, true)) {
                return $row;
            }
        }
        return 0;
    }

    private function carga_row_layout(Worksheet $hoja, array $layout_dispersion, int $recorrido): array
    {
        $valores_fila = $this->init_valores_fila(hoja: $hoja,recorrido:  $recorrido);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al obtener $valores_fila', data: $valores_fila);
        }

        $row = $this->genera_row_fila($valores_fila);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al obtener $row', data: $row);
        }
        $layout_dispersion[] = $row;

        return $layout_dispersion;

    }

    /**
     * REG
     * Verifica si una celda de Excel contiene un valor significativo.
     *
     * Flujo de validación:
     *  1. Se valida que los parámetros ($hoja, $col, $row) sean correctos:
     *     - `$hoja` debe ser instancia de {@see Worksheet}.
     *     - `$col` debe cumplir el formato de columna válido (ej. "A", "B", "AA").
     *     - `$row` debe ser un entero positivo (>=1).
     *     En caso contrario, retorna un arreglo de error generado por {@see errores}.
     *
     *  2. Se intenta leer el valor de la celda con {@see leer_valor_celda()}.
     *     - Si ocurre una excepción al leer, se captura y retorna `false`
     *       después de registrar el error.
     *
     *  3. Si la lectura es exitosa:
     *     - Se evalúa con {@see tiene_contenido_no_vacio()} para determinar
     *       si el contenido es significativo (no nulo, no vacío, no solo espacios).
     *
     * Reglas de retorno:
     *  - `true`  → la celda contiene un valor significativo (ej. "ABC", 0, 123).
     *  - `false` → la celda está vacía, contiene solo espacios o se atrapó un error de lectura.
     *  - `array` → error de validación de parámetros (hoja, columna o fila inválida).
     *
     * @param Worksheet $hoja  Hoja de cálculo en la que se encuentra la celda.
     * @param string    $col   Columna en notación Excel (ejemplo: "A", "B", "AA").
     * @param int       $row   Número de fila (>=1).
     *
     * @return bool|array
     *   - `true` si la celda tiene contenido no vacío.
     *   - `false` si la celda está vacía o no tiene contenido significativo.
     *   - `array` con detalles del error si los parámetros son inválidos.
     *
     * @example Celda con texto
     * ```php
     * $hoja->setCellValue('A1', 'Hola');
     * $this->celda_tiene_valor($hoja, 'A', 1); // true
     * ```
     *
     * @example Celda con número 0
     * ```php
     * $hoja->setCellValue('B2', 0);
     * $this->celda_tiene_valor($hoja, 'B', 2); // true
     * ```
     *
     * @example Celda vacía
     * ```php
     * $this->celda_tiene_valor($hoja, 'C', 3); // false
     * ```
     *
     * @example Columna inválida
     * ```php
     * $this->celda_tiene_valor($hoja, '1A', 1);
     * // retorna array con error
     * ```
     */
    private function celda_tiene_valor(Worksheet $hoja, string $col, int $row): bool|array
    {
        if (!$this->es_hoja_valida($hoja)) {
            return (new errores())->error(mensaje: 'Error: hoja inválida, use notación Excel en mayúsculas',
                data: $col);
        }
        if (!$this->es_columna_valida($col)) {
            return (new errores())->error(mensaje: 'Error: columna inválida, use notación Excel en mayúsculas',
                data: $col);
        }
        if (!$this->es_fila_valida($row)) {
            return (new errores())->error(mensaje: 'Error: número de fila inválido, debe ser entero positivo',
                data: $row);
        }

        $valor = $this->leer_valor_celda($hoja, $col, $row);
        if ($valor instanceof Throwable) {
            return (new errores())->error(
                mensaje: 'Error al leer valor de celda',
                data: ['col' => $col, 'row' => $row, 'exception' => $valor->getMessage()]
            );
        }

        return $this->tiene_contenido_no_vacio($valor);
    }

    private function columnas(Worksheet $hoja): array
    {
        $valores_fila = $this->get_encabezados_xls(hoja: $hoja);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al inicializar valores_fila', data: $valores_fila);
        }
        $columnas = $this->genera_columnas($valores_fila);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al inicializar value', data: $columnas);
        }

        return $columnas;


    }

    private function columnas_a_escanear(): array
    {
        return ['A', 'B', 'C', 'D', 'E'];
    }

    private function datos_iniciales(Worksheet $hoja): array|stdClass
    {
        $es_valido = $this->es_valido(hoja: $hoja);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al obtener $es_valido', data: $es_valido);
        }
        if(!$es_valido){
            return (new errores())->error(mensaje: 'Error revise layout', data: $es_valido);
        }

        $fila_inicial = $this->fila_inicial(hoja: $hoja);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al obtener fila_inicial', data: $fila_inicial);
        }

        $ultima_fila = $this->ultima_fila(fila_inicial: $fila_inicial, hoja:  $hoja);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al obtener $ultima_fila', data: $ultima_fila);
        }

        $columnas = $this->columnas($hoja);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al obtener $columnas', data: $columnas);
        }

        $data = new stdClass();
        $data->fila_inicial = $fila_inicial;
        $data->ultima_fila = $ultima_fila;
        $data->columnas = $columnas;

        return $data;

    }

    private function datos_layout(Worksheet $hoja): stdClass
    {
        $cliente = $hoja->rangeToArray("D2:D6", null, true, false);

        if(is_null($cliente[0][0])){
            $cliente[0][0] = '';
        }
        if(is_null($cliente[1][0])){
            $cliente[1][0] = '';
        }
        $name_cliente = strtoupper(trim($cliente[0][0]));
        $periodo = strtoupper(trim($cliente[1][0]));

        $datos = new stdClass();
        $datos->name_cliente = $name_cliente;
        $datos->periodo = $periodo;

        return $datos;

    }

    private function elimina_doble_esp(string $value): string
    {
        $value = str_replace('  ',' ',$value);
        $value = str_replace('  ',' ',$value);
        $value = str_replace('  ',' ',$value);
        return str_replace('  ',' ',$value);

    }

    /**
     * REG
     * Verifica si una referencia de columna de Excel es válida.
     *
     * Una columna se considera válida cuando:
     *  - Contiene únicamente letras del alfabeto inglés (A–Z).
     *  - Puede tener una o más letras en mayúsculas (ejemplo: "A", "Z", "AA", "BC", "ZZ").
     *  - No contiene números, acentos, espacios ni caracteres especiales.
     *  - No acepta letras en minúsculas.
     *
     * Internamente utiliza una expresión regular `/^[A-Z]+$/` para validar el formato.
     *
     * @param string $col
     *   Cadena que representa la columna a validar.
     *
     * @return bool
     *   - `true` si la columna es válida.
     *   - `false` si contiene caracteres no permitidos o está vacía.
     *
     * @example Uso válido
     * ```php
     * $this->es_columna_valida("A");   // true
     * $this->es_columna_valida("AA");  // true
     * $this->es_columna_valida("ZZ");  // true
     * ```
     *
     * @example Uso inválido
     * ```php
     * $this->es_columna_valida("a");   // false (minúscula)
     * $this->es_columna_valida("A1");  // false (contiene número)
     * $this->es_columna_valida("Á");   // false (acento)
     * $this->es_columna_valida("");    // false (vacío)
     * ```
     */
    private function es_columna_valida(string $col): bool
    {
        return preg_match('/^[A-Z]+$/', $col) === 1;
    }

    /**
     * REG
     * Verifica si un número de fila de Excel es válido.
     *
     * Una fila es considerada válida cuando:
     *  - Es un número entero mayor que cero.
     *  - Representa un índice de fila existente en una hoja de Excel
     *    (las filas empiezan en 1, no en 0).
     *
     * @param int $row
     *   Número de fila a validar.
     *
     * @return bool
     *   - `true` si $row es mayor que 0.
     *   - `false` si $row es 0 o un número negativo.
     *
     * @example Uso válido
     * ```php
     * $this->es_fila_valida(1);   // true
     * $this->es_fila_valida(10);  // true
     * ```
     *
     * @example Uso inválido
     * ```php
     * $this->es_fila_valida(0);    // false
     * $this->es_fila_valida(-5);   // false
     * ```
     */
    private function es_fila_valida(int $row): bool
    {
        return $row > 0;
    }

    /**
     * REG
     * Verifica si el parámetro recibido es una hoja de cálculo válida.
     *
     * Este método evalúa si el valor proporcionado es una instancia de
     * {@see Worksheet} de PhpSpreadsheet. Es útil como validación defensiva
     * antes de realizar operaciones sobre el objeto hoja.
     *
     * @param mixed $hoja Valor a evaluar; puede ser cualquier tipo de dato.
     *
     * @return bool
     *   - `true`  si $hoja es instancia de {@see Worksheet}.
     *   - `false` en cualquier otro caso (null, string, int, array, etc.).
     *
     * @example Uso con una hoja válida
     * ```php
     * $spreadsheet = new Spreadsheet();
     * $hoja = $spreadsheet->getActiveSheet();
     *
     * $valido = $this->es_hoja_valida($hoja);
     * // $valido === true
     * ```
     *
     * @example Uso con un valor inválido
     * ```php
     * $valido = $this->es_hoja_valida("texto");
     * // $valido === false
     * ```
     */
    private function es_hoja_valida(mixed $hoja): bool
    {
        return $hoja instanceof Worksheet;
    }

    private function es_fila_inicial_valida(int $fila_inicial): bool
    {
        return $fila_inicial > 0;
    }


    private function es_valido(Worksheet $hoja): bool|array
    {
        if (!$this->es_hoja_valida($hoja)) {
            return (new errores())->error(
                mensaje: 'Parámetro $hoja inválido: se esperaba Worksheet',
                data: ['hoja' => get_debug_type($hoja)]
            );
        }

        $fila_encabezado = $this->file_encabezado($hoja);
        if (errores::$error) {
            // Propaga el error enriqueciendo el contexto
            return (new errores())->error(
                mensaje: 'Encabezado no encontrado en la hoja (validación de layout)',
                data: $fila_encabezado
            );
        }

        if (!is_int($fila_encabezado) || $fila_encabezado < 1) {
            return (new errores())->error(
                mensaje: 'Fila de encabezado inválida',
                data: ['fila_encabezado' => $fila_encabezado]
            );
        }

        return true;
    }

    private function estilos_wr(Worksheet $hoja): Worksheet
    {
        $hoja->getStyle('B:B')
            ->getNumberFormat()
            ->setFormatCode('@');

        $hoja->getStyle('C:C')
            ->getNumberFormat()
            ->setFormatCode('0.00');

        return $hoja;

    }

    /**
     * REG
     * Determina la fila inicial de los datos en el layout de dispersión.
     *
     * Este método localiza la fila donde se encuentra el encabezado esperado
     * (utilizando {@see file_encabezado()}) y retorna la siguiente fila,
     * que corresponde al inicio de los datos del layout.
     *
     * Flujo:
     *  - Llama a `file_encabezado` para obtener la fila del encabezado.
     *  - Valida que la fila obtenida sea un entero positivo.
     *  - Si es válida, retorna el número de fila + 1.
     *  - Si ocurre algún error en el proceso, retorna un arreglo de error
     *    generado por la clase {@see \gamboamartin\errores\errores}.
     *
     * @param Worksheet $hoja Hoja de cálculo de Excel donde se buscará el encabezado.
     *
     * @return int|array
     *   - `int`: Número de fila donde comienzan los datos (encabezado + 1).
     *   - `array`: Estructura de error con mensaje y datos adicionales.
     *
     * @example Uso exitoso
     * ```php
     * $spreadsheet = new Spreadsheet();
     * $hoja = $spreadsheet->getActiveSheet();
     * $hoja->setCellValue('A5', 'CLAVE EMPLEADO'); // Encabezado en fila 5
     *
     * $fila_inicial = $this->fila_inicial($hoja);
     * // $fila_inicial === 6
     * ```
     *
     * @example Encabezado no encontrado
     * ```php
     * $spreadsheet = new Spreadsheet();
     * $hoja = $spreadsheet->getActiveSheet();
     * $hoja->setCellValue('A1', 'SIN ENCABEZADO');
     *
     * $fila_inicial = $this->fila_inicial($hoja);
     * // $fila_inicial es un array con detalles del error
     * ```
     */
    private function fila_inicial(Worksheet $hoja): int|array
    {

        // Localiza la fila del encabezado
        $fila_encabezado = $this->file_encabezado($hoja);
        if (errores::$error) {
            return (new errores())->error(
                mensaje: 'Error al obtener fila de encabezado',
                data: $fila_encabezado
            );
        }

        // Validación de resultado
        if (!is_int($fila_encabezado) || $fila_encabezado <= 0) {
            return (new errores())->error(
                mensaje: 'Error: fila de encabezado inválida',
                data: $fila_encabezado
            );
        }

        // La fila inicial de datos es la siguiente a la del encabezado
        return $fila_encabezado + 1;
    }


    private function file_encabezado(Worksheet $hoja): int|array
    {
        // Validaciones defensivas
        if (!$this->es_hoja_valida($hoja)) {
            return (new errores())->error(
                mensaje: 'Parámetro $hoja inválido: se esperaba Worksheet',
                data: ['hoja' => get_debug_type($hoja)]
            );
        }

        // Config centralizada (evita números mágicos)
        $columna           = 'A';
        $maxFilas          = 200;
        $headersPermitidos = ['CLAVE EMPLEADO', 'CLAVEEMPLEADO'];

        if (!$this->es_columna_valida($columna)) {
            return (new errores())->error(
                mensaje: 'Columna inválida: use notación Excel en mayúsculas (A..Z, AA..)',
                data: ['columna' => $columna]
            );
        }

        $fila = $this->buscar_primera_coincidencia($hoja, $columna, $maxFilas, $headersPermitidos);
        if ($fila > 0) {
            return $fila;
        }

        // Error consistente y contextualizado
        return (new errores())->error(
            mensaje: 'Encabezado no encontrado en la columna indicada',
            data: [
                'encabezados_esperados' => $headersPermitidos,
                'columna_escaneada'     => $columna,
                'filas_escaneadas'      => min($hoja->getHighestRow(), $maxFilas)
            ]
        );
    }

    private function fila_tiene_datos(Worksheet $hoja, int $row, array $columns): bool
    {
        foreach ($columns as $col) {
            if ($this->celda_tiene_valor($hoja, $col, $row)) {
                return true;
            }
        }
        return false;
    }

    private function genera_columnas(array $valores_fila): array
    {
        $row_xls = $valores_fila[0];
        $columnas = array();
        foreach ($row_xls as $key => $value) {
            $columnas = $this->asigna_dato_columna(columnas: $columnas,key:  $key,value:  $value);
            if(errores::$error){
                return (new errores())->error(mensaje: 'Error al inicializar value', data: $value);
            }
        }
        return $columnas;

    }

    private function genera_row_fila(array $valores_fila): stdClass
    {
        $nombre = strtoupper(trim($valores_fila[0]['5']));
        $clabe = strtoupper(trim($valores_fila[0]['9']));
        if($clabe === ''){
            $clabe = strtoupper(trim($valores_fila[0]['10']));
        }
        $monto = strtoupper(trim($valores_fila[0]['6']));
        if(is_numeric($monto)){
            $monto = round($monto,2);
        }

        $row = new stdClass();
        $row->nombre = $nombre;
        $row->clabe = $clabe;
        $row->monto = $monto;
        $row->concepto = 'PENSION POR RENTA VITALICIA';

        return $row;

    }

    private function get_encabezados_xls(Worksheet $hoja): array
    {
        $fila_encabezado = $this->file_encabezado($hoja);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al obtener $fila_encabezado', data: $fila_encabezado);
        }
        $valores_fila = $hoja->rangeToArray("A$fila_encabezado:Z$fila_encabezado", null,
            true, false);
        if(!isset($valores_fila[0])){
            return (new errores())->error(mensaje: 'Error al obtener datos de encabezados', data: $valores_fila);
        }

        return $valores_fila;

    }

    private function init_valores_fila(Worksheet $hoja, int $recorrido): array
    {
        $valores_fila = $hoja->rangeToArray("A$recorrido:L$recorrido", null, true, false);

        if(is_null($valores_fila[0]['5'])){
            $valores_fila[0]['5'] = '';
        }
        if(is_null($valores_fila[0]['6'])){
            $valores_fila[0]['6'] = '';
        }
        if(is_null($valores_fila[0]['9'])){
            $valores_fila[0]['9'] = '';
        }
        if(is_null($valores_fila[0]['10'])){
            $valores_fila[0]['10'] = '';
        }

        return $valores_fila;

    }

    private function init_value(mixed $value): string
    {
        if(is_null($value)){
            $value = '';
        }
        $value = trim($value);
        return strtoupper($value);

    }

    private function layout_dispersion(Worksheet $hoja, stdClass $ini): array
    {
        $layout_dispersion = array();
        $recorrido = $ini->fila_inicial;

        while ($recorrido <= $ini->ultima_fila) {
            $layout_dispersion = $this->carga_row_layout(hoja: $hoja,layout_dispersion:  $layout_dispersion,recorrido: $recorrido);
            if(errores::$error){
                return (new errores())->error(mensaje: 'Error al obtener $layout_dispersion', data: $layout_dispersion);
            }
            $recorrido++;
        }

        return $layout_dispersion;

    }

    final public function lee_layout_base(stdClass$fc_layout_nom): array|stdClass
    {
        $archivo = $fc_layout_nom->doc_documento_ruta_absoluta;
        $spreadsheet = IOFactory::load($archivo);
        $hoja = $spreadsheet->getActiveSheet();

        $es_valido = $this->es_valido(hoja: $hoja);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al obtener $es_valido', data: $es_valido);
        }
        if(!$es_valido){
            return (new errores())->error(mensaje: 'Error revise layout', data: $es_valido);
        }

        $ini = $this->datos_iniciales(hoja: $hoja);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al obtener $ini', data: $ini);
        }

        $layout_dispersion = $this->layout_dispersion(hoja: $hoja,ini: $ini);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al obtener $layout_dispersion', data: $layout_dispersion);
        }

        $data = new stdClass();
        $data->layout_dispersion = $layout_dispersion;
        $data->hoja = $hoja;
        $data->columnas = $ini->columnas;
        $data->fila_inicial = $ini->fila_inicial;
        $data->ultima_fila = $ini->ultima_fila;
        $data->primer_columna = array_key_first($ini->columnas);
        $data->ultima_columna = array_key_last($ini->columnas);

        return $data;

    }

    /**
     * REG
     * Lee de forma segura el valor de una celda en una hoja de Excel.
     *
     * Este método construye la referencia de celda concatenando la columna
     * (por ejemplo `"A"`) con el número de fila (por ejemplo `1` → `"A1"`)
     * y obtiene su valor mediante PhpSpreadsheet.
     *
     * Comportamiento:
     *  - Si la celda existe, retorna su valor tal cual lo interpreta PhpSpreadsheet.
     *    Puede ser `string`, `int`, `float`, `null`, etc.
     *  - Si ocurre una excepción (por ejemplo, índice de fila/columna fuera de rango),
     *    se captura y se retorna el objeto `Throwable` correspondiente en lugar
     *    de interrumpir la ejecución.
     *
     * @param Worksheet $hoja
     *   Instancia de hoja de cálculo donde se leerá el valor.
     * @param string $col
     *   Columna en notación Excel (ejemplo: `"A"`, `"B"`, `"AA"`).
     * @param int $row
     *   Número de fila (≥ 1).
     *
     * @return mixed
     *   - Valor de la celda (string, int, float, null, etc.).
     *   - Objeto `Throwable` si ocurre una excepción durante la lectura.
     *
     * @example Uso exitoso
     * ```php
     * $spreadsheet = new Spreadsheet();
     * $hoja = $spreadsheet->getActiveSheet();
     * $hoja->setCellValue('A1', 'Hola');
     *
     * $valor = $this->leer_valor_celda($hoja, 'A', 1);
     * // $valor === "Hola"
     * ```
     *
     * @example Celda vacía
     * ```php
     * $valor = $this->leer_valor_celda($hoja, 'B', 2);
     * // $valor === null
     * ```
     *
     * @example Error controlado
     * ```php
     * $valor = $this->leer_valor_celda($hoja, 'ZZZ', 9999);
     * // $valor es instancia de Throwable (error atrapado)
     * ```
     */
    private function leer_valor_celda(Worksheet $hoja, string $col, int $row): mixed
    {
        try {
            return $hoja->getCell($col . $row)->getValue();
        } catch (Throwable $e) {
            return $e;
        }
    }

    /**
     * REG
     * Normaliza un valor arbitrario a texto en mayúsculas.
     *
     * Reglas de normalización:
     *  - Si el valor es `null`, se convierte en cadena vacía `""`.
     *  - Se fuerza conversión a `string` usando `(string)`.
     *  - Se eliminan espacios en blanco al inicio y al final con `trim()`.
     *  - El resultado se convierte a mayúsculas con `strtoupper()`.
     *
     * Casos especiales:
     *  - `true`  → `(string)true = "1"` → retorna `"1"`.
     *  - `false` → `(string)false = ""` → retorna `""`.
     *  - `0` (int) → `(string)0 = "0"` → retorna `"0"`.
     *  - Números decimales → se convierten a string y se ponen en mayúsculas (sin cambios relevantes).
     *
     * @param mixed $val
     *   Valor a normalizar. Puede ser de cualquier tipo (string, int, float, bool, null, etc.).
     *
     * @return string
     *   Representación del valor en texto, siempre en mayúsculas y sin espacios sobrantes.
     *
     * @example
     * ```php
     * $this->normaliza_texto(" hola "); // "HOLA"
     * $this->normaliza_texto(null);     // ""
     * $this->normaliza_texto(true);     // "1"
     * $this->normaliza_texto(123.45);   // "123.45"
     * ```
     */
    private function normaliza_texto(mixed $val): string {
        return strtoupper(trim((string)($val ?? '')));
    }

    private function normaliza_value(mixed $value): array|string
    {
        $value = trim(str_replace("'", '', $value));
        $value = $this->init_value(value: $value);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al inicializar value', data: $value);
        }
        $value = $this->replaces_val(value: $value);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al inicializar value', data: $value);
        }
        return $value;

    }

    private function replace_may_val(string $value): string
    {
        $value = str_replace('Á','A',$value);
        $value = str_replace('É','E',$value);
        $value = str_replace('Í','I',$value);
        $value = str_replace('Ó','O',$value);
        return str_replace('Ú','U',$value);

    }

    private function replace_min_val(string $value):string
    {
        $value = str_replace('á','A',$value);
        $value = str_replace('é','E',$value);
        $value = str_replace('í','I',$value);
        $value = str_replace('ó','O',$value);
        return str_replace('ú','U',$value);

    }

    private function replaces_val(string $value): array|string
    {
        $value = $this->replace_min_val(value: $value);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al inicializar value', data: $value);
        }

        $value = $this->replace_may_val(value: $value);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al inicializar value', data: $value);
        }

        $value = $this->elimina_doble_esp(value: $value);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al inicializar value', data: $value);
        }

        return $value;

    }

    /**
     * REG
     * Verifica si un valor contiene contenido no vacío después de su normalización.
     *
     * Reglas de evaluación:
     *  - Si el valor es `null`, se considera vacío → retorna `false`.
     *  - El valor recibido se convierte a cadena con `(string)` y se aplica `trim()`.
     *  - Si el resultado de `trim()` es una cadena vacía, retorna `false`.
     *  - En cualquier otro caso, retorna `true`.
     *
     * Casos particulares:
     *  - `""` o `"   "` → `false`
     *  - `0` (int) → `"0"` tras casting, retorna `true`
     *  - `false` → `""` tras casting, retorna `false`
     *  - `true` → `"1"` tras casting, retorna `true`
     *
     * @param mixed $raw Valor a evaluar. Puede ser de cualquier tipo (string, int, bool, null, etc.).
     *
     * @return bool
     *   - `true`  si el valor contiene contenido significativo no vacío.
     *   - `false` si es `null` o cadena vacía después de normalizarlo.
     *
     * @example
     * ```php
     * $this->tiene_contenido_no_vacio(null);     // false
     * $this->tiene_contenido_no_vacio('');       // false
     * $this->tiene_contenido_no_vacio('   ');    // false
     * $this->tiene_contenido_no_vacio('ABC');    // true
     * $this->tiene_contenido_no_vacio(123);      // true
     * $this->tiene_contenido_no_vacio(false);    // false
     * $this->tiene_contenido_no_vacio(true);     // true
     * ```
     */
    private function tiene_contenido_no_vacio(mixed $raw): bool
    {
        if ($raw === null) {
            return false;
        }
        $val = trim((string)$raw);
        return $val !== '';
    }

    private function title_doc(Worksheet $hoja): array|string
    {
        $datos_ly = $this->datos_layout(hoja: $hoja);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al obtener $datos_ly', data: $datos_ly);
        }


        $fecha = date('YmdHis');
        return "$datos_ly->name_cliente.$datos_ly->periodo.$fecha.xlsx";

    }

    private function ultima_fila(int $fila_inicial, Worksheet $hoja): int|array
    {
        // Validaciones defensivas
        if (!$this->es_fila_inicial_valida($fila_inicial)) {
            return (new errores())->error(
                mensaje: 'Error: $fila_inicial inválida; se espera entero positivo',
                data: $fila_inicial
            );
        }
        if (!$hoja instanceof Worksheet) {
            return (new errores())->error(
                mensaje: 'Error: parámetro $hoja inválido, se esperaba Worksheet',
                data: $hoja
            );
        }

        $columns   = $this->columnas_a_escanear(); // ['A','B','C','D','E'] (fácil de ampliar)
        $highest   = (int) $hoja->getHighestRow();
        $startRow  = max(1, $fila_inicial);
        $lastRow   = -1;

        // Una sola pasada desde fila_inicial hasta el final de la hoja
        for ($row = $startRow; $row <= $highest; $row++) {
            if ($this->fila_tiene_datos($hoja, $row, $columns)) {
                $lastRow = $row;
            }
        }

        if ($lastRow < $startRow) {
            return (new errores())->error(
                mensaje: 'Error al obtener última fila: no se encontraron datos a partir de la fila inicial',
                data: ['fila_inicial' => $fila_inicial, 'highestRow' => $highest, 'columns' => $columns]
            );
        }

        return $lastRow;
    }

    private function valor_celda_normalizado(Worksheet $hoja, string $col, int $fila): string {
        if (!$this->es_hoja_valida($hoja) || !$this->es_columna_valida($col) || !$this->es_fila_valida($fila)) {
            return '';
        }
        try {
            $raw = $hoja->getCell($col.$fila)->getValue();
            return $this->normaliza_texto($raw);
        } catch (\Throwable) {
            return '';
        }
    }

    private function write_data(Worksheet $hoja, array $layout_dispersion): Worksheet|array
    {
        $hoja = $this->write_keys($hoja);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al obtener $hoja_wr', data: $hoja);
        }

        $hoja = $this->write_rows($hoja,$layout_dispersion);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al obtener $hoja_wr', data: $hoja);
        }
        return $hoja;

    }

    final public function write_dispersion(Worksheet $hoja_base, array $layout_dispersion)
    {
        $xls = new Spreadsheet();
        $hoja_wr = $xls->getActiveSheet();

        $hoja_wr = $this->estilos_wr($hoja_wr);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al obtener $hoja_wr', data: $hoja_wr);
        }

        $hoja_wr = $this->write_data(hoja: $hoja_wr,layout_dispersion: $layout_dispersion);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al obtener $hoja_wr', data: $hoja_wr);
        }

        $hoja_wr = $this->autosize($hoja_wr);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al obtener $hoja_wr', data: $hoja_wr);
        }

        $title = $this->title_doc($hoja_base);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al obtener $title', data: $title);
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'.$title.'"');
        header('Cache-Control: max-age=0');

        try {
            $writer = new Xlsx($xls);
            $writer->save('php://output');
            exit;
        }
        catch (Throwable $e) {
            return (new errores())->error(mensaje: 'Error al guardar file', data: $e);
        }


    }

    private function write_keys(Worksheet $hoja): Worksheet|array
    {
        $keys = array('Nombre','Clabe','Monto','Concepto');
        $hoja->fromArray($keys);


        try {
            $hoja->getStyle('A1:Z1')->applyFromArray([
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN]],
            ]);
        }
        catch (Throwable $e){
            return (new errores())->error(mensaje: 'Error al cargar estilos', data: $e);
        }

        return $hoja;

    }

    private function write_row(Worksheet $hoja_wr, stdClass $row, int $row_ini): Worksheet
    {
        $hoja_wr->setCellValueExplicit("A$row_ini", $row->nombre, DataType::TYPE_STRING);
        $hoja_wr->setCellValueExplicit("B$row_ini", $row->clabe, DataType::TYPE_STRING);
        $hoja_wr->setCellValueExplicit("C$row_ini", $row->monto, DataType::TYPE_NUMERIC);
        $hoja_wr->setCellValueExplicit("D$row_ini", $row->concepto, DataType::TYPE_STRING);

        return $hoja_wr;

    }

    private function write_rows(Worksheet $hoja_wr, array $layout_dispersion): Worksheet|array
    {
        $row_ini = 2;
        foreach ($layout_dispersion as $row){
            $hoja_wr = $this->write_row($hoja_wr, $row, $row_ini);
            if(errores::$error){
                return (new errores())->error(mensaje: 'Error al obtener $hoja_wr', data: $hoja_wr);
            }
            $row_ini++;
        }

        return $hoja_wr;

    }

}
