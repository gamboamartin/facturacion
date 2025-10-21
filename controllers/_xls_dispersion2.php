<?php
namespace gamboamartin\facturacion\controllers;

use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\fc_empleado;
use PDO;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use stdClass;
use Throwable;

class _xls_dispersion2{

    public PDO $link;
    private stdClass $fc_layout_nom;
    public static array $letras = array();
    private const string ENCABEZADO_COL          = 'A';
    private const int ENCABEZADO_MAX_FILAS    = 200;
    private const array ENCABEZADOS_PERMITIDOS  = ['CLAVE EMPLEADO', 'CLAVEEMPLEADO','# EMPLEADO'];

    public function __construct(PDO $link)
    {
        $this->link = $link;
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
        $hoja->getColumnDimension('E')->setAutoSize(true);
        $hoja->getColumnDimension('F')->setAutoSize(true);
        $hoja->getColumnDimension('G')->setAutoSize(true);
        $hoja->getColumnDimension('H')->setAutoSize(true);
        $hoja->getColumnDimension('I')->setAutoSize(true);
        $hoja->getColumnDimension('J')->setAutoSize(true);
        $hoja->getColumnDimension('K')->setAutoSize(true);
        $hoja->getColumnDimension('L')->setAutoSize(true);
        $hoja->getColumnDimension('M')->setAutoSize(true);
        $hoja->getColumnDimension('N')->setAutoSize(true);
        $hoja->getColumnDimension('O')->setAutoSize(true);
        $hoja->getColumnDimension('P')->setAutoSize(true);
        $hoja->getColumnDimension('Q')->setAutoSize(true);
        $hoja->getColumnDimension('R')->setAutoSize(true);
        $hoja->getColumnDimension('S')->setAutoSize(true);
        $hoja->getColumnDimension('T')->setAutoSize(true);

        return $hoja;

    }

    /**
     * REG
     * Busca en una columna de la hoja la primera fila que contenga un valor
     * coincidente con alguno de los candidatos esperados.
     *
     * Flujo de validación:
     *  1. Verifica que los parámetros sean válidos:
     *     - `$hoja` debe ser instancia de {@see Worksheet}.
     *     - `$col` debe cumplir el formato de columna de Excel en mayúsculas (ej. "A", "B", "AA").
     *     - `$maxFilas` debe ser un entero positivo (≥1).
     *     Si alguna validación falla, retorna inmediatamente `0`.
     *
     *  2. Determina el rango de filas a escanear:
     *     - Toma el mínimo entre `$maxFilas` y `getHighestRow()` de la hoja.
     *
     *  3. Itera fila por fila desde 1 hasta `$highest`:
     *     - Obtiene el valor con {@see valor_celda_normalizado()}.
     *     - Si el valor no es vacío y coincide estrictamente con alguno de los `$candidatos`
     *       (comparación estricta con `in_array(..., true)`), retorna el número de fila.
     *
     *  4. Si no encuentra ninguna coincidencia, retorna `0`.
     *
     * Reglas de retorno:
     *  - `int` con la fila donde se encontró la primera coincidencia.
     *  - `0` si no se encontró ninguna coincidencia o los parámetros eran inválidos.
     *
     * @param Worksheet $hoja       Hoja de cálculo donde se realizará la búsqueda.
     * @param string    $col        Columna en notación Excel (ejemplo: "A", "B", "AA").
     * @param int       $maxFilas   Número máximo de filas a escanear (≥1).
     * @param array     $candidatos Lista de valores normalizados a buscar.
     *
     * @return int Fila de la primera coincidencia; `0` si no hay coincidencias o parámetros inválidos.
     *
     * @example Uso exitoso
     * ```php
     * $hoja->setCellValue('A5', 'CLAVE EMPLEADO');
     * $fila = $this->buscar_primera_coincidencia($hoja, 'A', 200, ['CLAVE EMPLEADO', 'CLAVEEMPLEADO']);
     * // $fila === 5
     * ```
     *
     * @example Sin coincidencias
     * ```php
     * $hoja->setCellValue('A1', 'SIN MATCH');
     * $fila = $this->buscar_primera_coincidencia($hoja, 'A', 100, ['CLAVE EMPLEADO']);
     * // $fila === 0
     * ```
     *
     * @example Parámetros inválidos
     * ```php
     * $fila = $this->buscar_primera_coincidencia("no-hoja", "A", 10, ['X']); // 0
     * $fila = $this->buscar_primera_coincidencia($hoja, "1A", 10, ['X']);   // 0
     * $fila = $this->buscar_primera_coincidencia($hoja, "A", 0, ['X']);    // 0
     * ```
     */
    private function buscar_primera_coincidencia(
        Worksheet $hoja,
        string $col,
        int $maxFilas,
        array $candidatos
    ): int {
        // Guard clauses: validaciones defensivas
        if (!$this->es_hoja_valida($hoja))   return 0;
        if (!$this->es_columna_valida($col)) return 0;
        if ($maxFilas < 1)                   return 0;

        // Determinar el límite superior real
        $highest = min($hoja->getHighestRow(), $maxFilas);

        // Escaneo fila por fila
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

    /**
     * REG
     * Devuelve la lista de columnas que se deben escanear al determinar
     * la existencia de datos en una fila.
     *
     * Actualmente, se consideran las primeras cinco columnas de la hoja
     * de cálculo: **A, B, C, D y E**.
     * Esta configuración sirve como límite de exploración inicial para
     * detectar si una fila contiene información relevante.
     *
     * Notas de mantenimiento:
     * - El método centraliza la definición de columnas a revisar, evitando
     *   el uso de "números mágicos" en el código.
     * - Si en el futuro es necesario ampliar o reducir el rango de columnas
     *   a validar, este método puede ajustarse fácilmente.
     *
     * @return array<string>
     *   Arreglo indexado con las letras de las columnas a escanear.
     *   Ejemplo: ['A', 'B', 'C', 'D', 'E'].
     *
     * @example Uso típico
     * ```php
     * $cols = $this->columnas_a_escanear();
     * // $cols === ['A', 'B', 'C', 'D', 'E']
     * ```
     */
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
        return preg_replace('/\s{2,}/', ' ', $value) ?? $value;
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

    /**
     * REG
     * Verifica si un número de fila inicial es válido.
     *
     * Una fila inicial se considera válida cuando:
     *  - Es un número entero mayor que cero.
     *  - Representa el punto de partida correcto para procesar un layout
     *    (las filas en Excel comienzan en 1, no en 0).
     *
     * Reglas:
     *  - Retorna `true` si `$fila_inicial > 0`.
     *  - Retorna `false` en cualquier otro caso (0 o números negativos).
     *
     * @param int $fila_inicial
     *   Número de fila a validar.
     *
     * @return bool
     *   - `true` si la fila inicial es válida.
     *   - `false` si es cero o negativa.
     *
     * @example Uso válido
     * ```php
     * $this->es_fila_inicial_valida(1);   // true
     * $this->es_fila_inicial_valida(10);  // true
     * ```
     *
     * @example Uso inválido
     * ```php
     * $this->es_fila_inicial_valida(0);   // false
     * $this->es_fila_inicial_valida(-3);  // false
     * ```
     */
    private function es_fila_inicial_valida(int $fila_inicial): bool
    {
        return $fila_inicial > 0;
    }


    /**
     * REG
     * Valida que la hoja tenga un layout correcto (encabezado presente).
     *
     * Reglas:
     *  - Verifica que $hoja sea Worksheet válido.
     *  - Usa {@see file_encabezado()} para localizar el encabezado.
     *  - Retorna `true` si la fila del encabezado es un entero >= 1.
     *  - En cualquier otra situación, retorna un arreglo de error contextualizado.
     *
     * @param Worksheet $hoja
     * @return bool|array `true` si es válido; `array` con error si no.
     */
    private function es_valido(Worksheet $hoja): bool|array
    {
        // 1) Validación defensiva del parámetro
        if (!$this->es_hoja_valida($hoja)) {
            return (new errores())->error(
                mensaje: 'Parámetro $hoja inválido: se esperaba Worksheet',
                data: ['hoja' => get_debug_type($hoja)]
            );
        }

        // 2) Localizar encabezado (delegado)
        $fila_encabezado = $this->file_encabezado($hoja);

        // Si file_encabezado propagó error (array) o se marcó error global, enriquecer y retornar
        if (is_array($fila_encabezado) || errores::$error) {
            return (new errores())->error(
                mensaje: 'Encabezado no encontrado en la hoja (validación de layout)',
                data: $fila_encabezado
            );
        }

        // 3) Validar el resultado
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

//
        $hoja->getStyle('G:G')
            ->getNumberFormat()
            ->setFormatCode('0.00');

        $hoja->getStyle('M:M')
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


    /**
     * REG
     * Localiza la fila del encabezado dentro de una hoja de Excel.
     *
     * Este método escanea una columna específica (definida por la constante
     * {@see self::ENCABEZADO_COL}) hasta un número máximo de filas
     * ({@see self::ENCABEZADO_MAX_FILAS}) en busca de alguno de los valores
     * de encabezado permitidos ({@see self::ENCABEZADOS_PERMITIDOS}).
     *
     * Flujo:
     *  1. Validación defensiva:
     *     - Verifica que `$hoja` sea una instancia válida de {@see Worksheet}.
     *     - Verifica que la columna configurada sea válida.
     *     En caso contrario, retorna un arreglo de error generado por {@see errores}.
     *
     *  2. Escaneo:
     *     - Llama a {@see buscar_primera_coincidencia()} con la columna, el límite de filas
     *       y la lista de encabezados permitidos.
     *     - Si encuentra una coincidencia, retorna el número de fila (>0).
     *
     *  3. Error:
     *     - Si no encuentra coincidencia, retorna un arreglo de error con detalles
     *       del contexto (encabezados esperados, columna escaneada y número de filas revisadas).
     *
     * Reglas de retorno:
     *  - `int`   → Número de fila donde se encontró el encabezado.
     *  - `array` → Error con mensaje descriptivo y datos de contexto.
     *
     * @param Worksheet $hoja Hoja de cálculo de Excel donde se buscará el encabezado.
     *
     * @return int|array
     *   - int  → Fila del encabezado encontrada (>0).
     *   - array→ Estructura de error si no se encuentra el encabezado o la hoja/columna son inválidas.
     *
     * @example Uso exitoso
     * ```php
     * $spreadsheet = new Spreadsheet();
     * $hoja = $spreadsheet->getActiveSheet();
     * $hoja->setCellValue('A5', 'CLAVE EMPLEADO');
     *
     * $fila = $this->file_encabezado($hoja);
     * // $fila === 5
     * ```
     *
     * @example Hoja inválida
     * ```php
     * $fila = $this->file_encabezado("no-hoja");
     * // Retorna array con mensaje "Parámetro $hoja inválido"
     * ```
     *
     * @example Encabezado no encontrado
     * ```php
     * $spreadsheet = new Spreadsheet();
     * $hoja = $spreadsheet->getActiveSheet();
     * $hoja->setCellValue('A1', 'SIN MATCH');
     *
     * $fila = $this->file_encabezado($hoja);
     * // Retorna array con mensaje "Encabezado no encontrado en la columna indicada"
     * ```
     */
    private function file_encabezado(Worksheet $hoja): int|array
    {
        // 1) Validaciones defensivas
        if (!$this->es_hoja_valida($hoja)) {
            return (new errores())->error(
                mensaje: 'Parámetro $hoja inválido: se esperaba Worksheet',
                data: ['hoja' => get_debug_type($hoja)]
            );
        }

        // Lectura de configuración (sin números mágicos)
        $columna   = self::ENCABEZADO_COL;
        $maxFilas  = self::ENCABEZADO_MAX_FILAS;
        $candidatos = self::ENCABEZADOS_PERMITIDOS;

        if (!$this->es_columna_valida($columna)) {
            return (new errores())->error(
                mensaje: 'Columna inválida: use notación Excel en mayúsculas (A..Z, AA..)',
                data: ['columna' => $columna]
            );
        }

        // 2) Búsqueda de la primera coincidencia
        $fila = $this->buscar_primera_coincidencia($hoja, $columna, $maxFilas, $candidatos);
        if ($fila > 0) {
            return $fila;
        }

        // 3) Error consistente y contextualizado
        return (new errores())->error(
            mensaje: 'Encabezado no encontrado en la columna indicada',
            data: [
                'encabezados_esperados' => $candidatos,
                'columna_escaneada'     => $columna,
                'filas_escaneadas'      => min($hoja->getHighestRow(), $maxFilas)
            ]
        );
    }

    private function fila_tiene_datos(Worksheet $hoja, int $row, array $columns): bool|array
    {
        // 1) Validaciones defensivas
        $validacion = $this->validar_entrada_fila_tiene_datos($hoja, $row, $columns);
        if (errores::$error) {
            return (new errores())->error(
                mensaje: 'Error al validar entradas en fila_tiene_datos',
                data: ['detalle' => $validacion, 'row' => $row, 'columns' => $columns]
            );
        }

        // 2) Escaneo corto-circuito: retorna en cuanto encuentre una celda con valor
        foreach ($columns as $col) {
            $tieneValor = $this->celda_tiene_valor($hoja, $col, $row);
            if (errores::$error) {
                return (new errores())->error(
                    mensaje: 'Error al verificar si la celda tiene valor',
                    data: ['col' => $col, 'row' => $row, 'detalle' => $tieneValor]
                );
            }
            if ($tieneValor === true) {
                return true;
            }
        }

        // 3) Ninguna celda tuvo contenido no vacío
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

    private function genera_row_fila(array $valores_fila): stdClass|array
    {
        $nombre = strtoupper(trim($valores_fila[0]['5']));
        $correo = trim($valores_fila[0]['11']);
        $fecha_pago = $this->fc_layout_nom->fc_layout_nom_fecha_pago;
        $fecha_emision = substr($this->fc_layout_nom->fc_layout_nom_fecha_pago, 0, 10);
        $clabe = strtoupper(trim($valores_fila[0]['9']));

        $curp_rfc = strtoupper(trim($valores_fila[0]['4']));
        if($curp_rfc === ''){
            $curp_rfc = strtoupper(trim($valores_fila[0]['3']));
        }

        $rfc = strtoupper(trim($valores_fila[0]['3']));

        $empleado_id = $this->obtener_fc_empleado_id(rfc: $rfc);

        $tipo_cuenta = 'clabe';
        if($clabe === ''){
            $tipo_cuenta = 'tarjeta';
            $clabe = strtoupper(trim($valores_fila[0]['10']));
        }
        $monto = strtoupper(trim($valores_fila[0]['6']));
        if(is_numeric($monto)){
            $monto = round($monto,2);
        }

        $nombre_banco = strtoupper(trim($valores_fila[0]['7']));

        $clave_banco = $this->get_clave_banco(nombre_banco: $nombre_banco);

        $row = new stdClass();
        $row->clave_programa = 'D' . date('YmdHis');
        $row->nombre_programa = date('dmY') . '_I'.$this->fc_layout_nom->fc_layout_nom_id;
        $row->uso_futuro = '';
        $row->importe_total_programa = $monto;
        $row->clave_beneficiario = '';
        $row->clave_banco = $clave_banco;
        $row->tipo_cuenta = $tipo_cuenta;
        $row->referencia = '';
        $row->grupo = '';
        $row->id_referencia_cliente = '';
        $row->curp_rfc = $curp_rfc;
        $row->celular = '';
        $row->nombre = $nombre;
        $row->clabe = "'".$clabe;
        $row->monto = $monto;
        $row->correo = $correo;
        $row->fecha_pago = "'".$fecha_pago;
        $row->fecha_emision = "'".$fecha_emision;
        $row->concepto = 'PENSION POR RENTA VITALICIA';

        return $row;

    }

    private function obtener_fc_empleado_id(string $rfc): string
    {
        if ($rfc === '') {
            return '';
        }
        $fc_empleado_modelo = new fc_empleado($this->link);
        $filtro = ['fc_empleado.rfc' => $rfc];
        $rs = $fc_empleado_modelo->filtro_and(filtro: $filtro);
        if(errores::$error){
            return '';
        }
        if ((int)$rs->n_registros !== 1) {
            return '';
        }
        return (string)$rs->registros_obj[0]->fc_empleado_id;
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
        $valores_fila = $hoja->rangeToArray(range: "A$recorrido:L$recorrido",
            nullValue: null, calculateFormulas: true, formatData: false);

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

    /**
     * REG
     * Normaliza un valor para uso interno.
     *
     * Reglas de transformación:
     * - Si el valor es `null`, retorna una cadena vacía (`""`).
     * - Cualquier valor se convierte explícitamente a cadena (`(string)`).
     * - Se eliminan espacios en blanco al inicio y al final con `trim()`.
     * - Se transforma todo a mayúsculas con `strtoupper()`.
     *
     * Esto asegura que todos los datos procesados tengan un formato homogéneo,
     * evitando problemas por nulos, espacios o diferencias de mayúsculas/minúsculas.
     *
     * @param mixed $value Valor a normalizar. Puede ser `null`, string, número o booleano.
     *
     * @return string Valor normalizado en mayúsculas y sin espacios sobrantes.
     *
     * @example
     *   $this->init_value(null);        // ""
     *   $this->init_value(" hola ");    // "HOLA"
     *   $this->init_value("Test123");   // "TEST123"
     *   $this->init_value(true);        // "1"
     *   $this->init_value(false);       // ""
     *   $this->init_value(45.67);       // "45.67"
     */
    private function init_value(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        return strtoupper(trim((string) $value));
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

    final public function lee_layout_base(stdClass $fc_layout_nom): array|stdClass
    {

        $this->fc_layout_nom = $fc_layout_nom;

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

        if(is_null($value)){
            $value = '';
        }
        $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value);
        $value = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '', $value);
        $value = preg_replace('/\s+/', ' ', trim($value));
        $value = rtrim($value, ".");
        $value = preg_replace('/^[\. ]+/', '', $value);

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
        $map = [
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
        ];

        return strtr($value, $map);
    }

    private function replace_min_val(string $value): string
    {
        $map = [
            'á' => 'A',
            'é' => 'E',
            'í' => 'I',
            'ó' => 'O',
            'ú' => 'U',
        ];

        return strtr($value, $map);
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
        return "$datos_ly->name_cliente.$datos_ly->periodo.$fecha.csv";

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

        $columns   = $this->columnas_a_escanear(); // ['A','B','C','D','E'] (fácil de ampliar)
        if(errores::$error){
            return (new errores())->error('Error al obtener columnas', data: $columns);
        }
        $highest   = (int) $hoja->getHighestRow();
        $startRow  = max(1, $fila_inicial);
        $lastRow   = -1;

        // Una sola pasada desde fila_inicial hasta el final de la hoja
        for ($row = $startRow; $row <= $highest; $row++) {
            $tiene_datos = $this->fila_tiene_datos($hoja, $row, $columns);
            if(errores::$error){
                return (new errores())->error('Error al verificar si tiene datos',$tiene_datos);
            }
            if ($tiene_datos) {
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

    /**
     * REG
     * Valida que el arreglo de columnas contenga únicamente nombres de columna válidos en notación Excel.
     *
     * Reglas de validación:
     * - El parámetro `$columns` no puede ser un arreglo vacío.
     * - Cada elemento debe ser de tipo string.
     * - Cada string debe cumplir con el formato de columna Excel en mayúsculas (ej. "A", "B", ..., "Z", "AA", "AB"...).
     *
     * En caso de error:
     * - Devuelve un arreglo generado por `errores()->error()` con el mensaje y los datos problemáticos.
     * - Además, se activa la bandera estática `errores::$error = true`.
     *
     * En caso de éxito:
     * - Retorna `true`.
     *
     * @param array<int, string> $columns Lista de columnas a validar.
     *
     * @return true|array `true` si todas las columnas son válidas,
     *                    o `array` con la estructura de error en caso contrario.
     *
     * @example
     * ```php
     * // Ejemplo válido
     * $resultado = $this->valida_columnas(['A', 'B', 'C']);
     * // Resultado: true
     *
     * // Ejemplo inválido (arreglo vacío)
     * $resultado = $this->valida_columnas([]);
     * // Resultado: ['mensaje' => 'Parámetro $columns inválido: ...', 'data' => ['columns' => []]]
     *
     * // Ejemplo inválido (columna en minúscula)
     * $resultado = $this->valida_columnas(['a', 'B']);
     * // Resultado: ['mensaje' => 'Columna inválida detectada ...', 'data' => ['columna' => 'a', 'columns' => ['a','B']]]
     *
     * // Ejemplo inválido (columna no string)
     * $resultado = $this->valida_columnas(['A', 123]);
     * // Resultado: ['mensaje' => 'Columna inválida detectada ...', 'data' => ['columna' => 123, 'columns' => ['A',123]]]
     * ```
     */
    private function valida_columnas(array $columns): true|array
    {
        if ($columns === []) {
            return (new errores())->error(
                mensaje: 'Parámetro $columns inválido: se esperaba arreglo no vacío de columnas',
                data: ['columns' => $columns]
            );
        }

        foreach ($columns as $col) {
            if (!is_string($col) || !$this->es_columna_valida($col)) {
                return (new errores())->error(
                    mensaje: 'Columna inválida detectada en $columns: use notación Excel en mayúsculas',
                    data: ['columna' => $col, 'columns' => $columns]
                );
            }
        }

        return true;
    }

    /**
     * REG
     * Obtiene y normaliza el valor textual de una celda de Excel.
     *
     * Flujo de ejecución:
     *  1. Validaciones defensivas:
     *     - Verifica que `$hoja` sea instancia válida de {@see Worksheet}.
     *     - Verifica que `$col` sea una referencia de columna válida (ej. "A", "B", "AA").
     *     - Verifica que `$fila` sea un número entero positivo (>=1).
     *     En caso de no cumplir alguna validación, retorna cadena vacía `""`.
     *
     *  2. Lectura de valor:
     *     - Utiliza {@see leer_valor_celda()} para obtener el valor de la celda.
     *     - Si ocurre un error y `leer_valor_celda` devuelve un `Throwable`,
     *       se retorna cadena vacía `""` en lugar de propagar la excepción.
     *
     *  3. Normalización:
     *     - El valor leído se procesa con {@see normaliza_texto()}:
     *         - `null` → `""`
     *         - Se elimina espacios con `trim()`
     *         - Se convierte a mayúsculas con `strtoupper()`
     *     - Retorna el valor siempre como `string` en formato limpio y consistente.
     *
     * Reglas de retorno:
     *  - `""` si los parámetros son inválidos o hubo error al leer la celda.
     *  - `string` con el valor normalizado en mayúsculas en caso exitoso.
     *
     * @param Worksheet $hoja Hoja de cálculo donde se buscará la celda.
     * @param string    $col  Columna en notación Excel (ejemplo: "A", "B", "AA").
     * @param int       $fila Número de fila (>=1).
     *
     * @return string Valor normalizado de la celda o `""` en caso de error o celda vacía.
     *
     * @example Celda con texto
     * ```php
     * $hoja->setCellValue('A1', ' hola ');
     * $this->valor_celda_normalizado($hoja, 'A', 1); // "HOLA"
     * ```
     *
     * @example Celda con número
     * ```php
     * $hoja->setCellValue('B2', 123);
     * $this->valor_celda_normalizado($hoja, 'B', 2); // "123"
     * ```
     *
     * @example Celda vacía
     * ```php
     * $this->valor_celda_normalizado($hoja, 'C', 3); // ""
     * ```
     *
     * @example Parámetros inválidos
     * ```php
     * $this->valor_celda_normalizado("no-hoja", "A", 1); // ""
     * $this->valor_celda_normalizado($hoja, "1A", 1);    // ""
     * $this->valor_celda_normalizado($hoja, "A", 0);     // ""
     * ```
     */
    private function valor_celda_normalizado(Worksheet $hoja, string $col, int $fila): string
    {
        // Validaciones defensivas (guard clauses)
        if (!$this->es_hoja_valida($hoja))    return '';
        if (!$this->es_columna_valida($col))  return '';
        if (!$this->es_fila_valida($fila))    return '';

        // Lectura segura (no duplicar lógica: usa helper centralizado)
        $valor = $this->leer_valor_celda($hoja, $col, $fila);
        if ($valor instanceof Throwable) {
            return '';
        }

        // Normalización consistente en todo el proyecto
        return $this->normaliza_texto($valor);
    }

    private function validar_entrada_fila_tiene_datos(mixed $hoja, int $row, array $columns): true|array
    {
        if (!$this->es_hoja_valida($hoja)) {
            return (new errores())->error(
                mensaje: 'Parámetro $hoja inválido: se esperaba Worksheet',
                data: ['hoja' => get_debug_type($hoja)]
            );
        }

        if (!$this->es_fila_valida($row)) {
            return (new errores())->error(
                mensaje: 'Número de fila inválido: debe ser entero positivo (>=1)',
                data: ['row' => $row]
            );
        }

        $okCols = $this->valida_columnas($columns);
        if (errores::$error) {
            return (new errores())->error(
                mensaje: 'Error al validar columnas: use notación Excel en mayúsculas',
                data: ['columns' => $columns, 'detalle' => $okCols]
            );
        }

        return true;
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


        $title = preg_replace('/[\x00-\x1F\x7F]/u', '', $title);
        $title = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $title);
        $title = preg_replace('/\s+/', ' ', trim($title));
        $title = preg_replace('/^[\. ]+/', '', $title);
        $title = preg_replace('/\.+$/', '', $title);

        if (!preg_match('/\.csv$/i', $title)) {
            $title .= '.csv';
        }

        $encoded = rawurlencode($title);

        ob_clean();
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="'.$title.'"');
        header('Cache-Control: max-age=0');

        try {
            $writer = new Csv($xls);

            // Opciones recomendadas para CSV
            $writer->setDelimiter(',');

            // Evita problemas de codificación en Excel (agrega BOM UTF-8)
            echo "\xEF\xBB\xBF";

            $writer->save('php://output');
            exit;
        } catch (Throwable $e) {
            return (new errores())->error(mensaje: 'Error al guardar file', data: $e);
        }

    }

    private function write_keys(Worksheet $hoja): Worksheet|array
    {
        $keys = [
            'Clave Programa Dispersión','Nombre Programa','Fecha Elab','Fecha Pago',
            'Uso Futuro','Uso Futuro', 'Importe Total del Programa','Clave Beneficiario en Peibo',
            'Nombre del Beneficiario','Clave Banco','Clabe','tipo cuenta','importe',
            'Correo Beneficiario','Referencia','Concepto','Grupo','Id Referencia Cliente',
            'RFC/CURP','Celular'
        ];
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

        $hoja_wr->setCellValueExplicit("A$row_ini", $row->clave_programa, DataType::TYPE_STRING);
        $hoja_wr->setCellValueExplicit("B$row_ini", $row->nombre_programa, DataType::TYPE_STRING);
        $hoja_wr->setCellValueExplicit("C$row_ini", $row->fecha_emision, DataType::TYPE_STRING);
        $hoja_wr->setCellValueExplicit("D$row_ini", $row->fecha_pago, DataType::TYPE_STRING);
        $hoja_wr->setCellValueExplicit("E$row_ini", $row->uso_futuro, DataType::TYPE_STRING);
        $hoja_wr->setCellValueExplicit("F$row_ini", $row->uso_futuro, DataType::TYPE_STRING);
        $hoja_wr->setCellValueExplicit("G$row_ini", '', DataType::TYPE_STRING);
        $hoja_wr->setCellValueExplicit("H$row_ini", $row->clave_beneficiario, DataType::TYPE_STRING);
        $hoja_wr->setCellValueExplicit("I$row_ini", $row->nombre, DataType::TYPE_STRING);
        $hoja_wr->setCellValueExplicit("J$row_ini", $row->clave_banco, DataType::TYPE_STRING);
        $hoja_wr->setCellValueExplicit("K$row_ini", $row->clabe, DataType::TYPE_STRING);
        $hoja_wr->setCellValueExplicit("L$row_ini", $row->tipo_cuenta, DataType::TYPE_STRING);
        $hoja_wr->setCellValueExplicit("M$row_ini", $row->monto, DataType::TYPE_NUMERIC);
        $hoja_wr->setCellValueExplicit("N$row_ini", $row->correo, DataType::TYPE_STRING);
        $hoja_wr->setCellValueExplicit("O$row_ini", $row->referencia, DataType::TYPE_STRING);
        $hoja_wr->setCellValueExplicit("P$row_ini", $row->concepto, DataType::TYPE_STRING);
        $hoja_wr->setCellValueExplicit("Q$row_ini", $row->grupo, DataType::TYPE_STRING);
        $hoja_wr->setCellValueExplicit("R$row_ini", $row->id_referencia_cliente, DataType::TYPE_STRING);
        $hoja_wr->setCellValueExplicit("S$row_ini", $row->curp_rfc, DataType::TYPE_STRING);
        $hoja_wr->setCellValueExplicit("T$row_ini", $row->celular, DataType::TYPE_STRING);

        return $hoja_wr;

    }

    private function write_row_base(Worksheet $hoja_wr, stdClass $row, float $importe_total = 0): Worksheet
    {

        $hoja_wr->setCellValueExplicit("A2", $row->clave_programa, DataType::TYPE_STRING);
        $hoja_wr->setCellValueExplicit("B2", $row->nombre_programa, DataType::TYPE_STRING);
        $hoja_wr->setCellValueExplicit("C2", $row->fecha_emision, DataType::TYPE_STRING);
        $hoja_wr->setCellValueExplicit("D2", $row->fecha_pago, DataType::TYPE_STRING);
        $hoja_wr->setCellValueExplicit("E2", $row->uso_futuro, DataType::TYPE_STRING);
        $hoja_wr->setCellValueExplicit("F2", $row->uso_futuro, DataType::TYPE_STRING);
        $hoja_wr->setCellValueExplicit("G2", $importe_total, DataType::TYPE_NUMERIC);
        $hoja_wr->setCellValueExplicit("H2", '', DataType::TYPE_STRING);
        $hoja_wr->setCellValueExplicit("I2", '', DataType::TYPE_STRING);
        $hoja_wr->setCellValueExplicit("J2", '', DataType::TYPE_STRING);
        $hoja_wr->setCellValueExplicit("K2", '', DataType::TYPE_STRING);
        $hoja_wr->setCellValueExplicit("L2", '', DataType::TYPE_STRING);
        $hoja_wr->setCellValueExplicit("M2", '', DataType::TYPE_STRING);
        $hoja_wr->setCellValueExplicit("N2", '', DataType::TYPE_STRING);
        $hoja_wr->setCellValueExplicit("O2", '', DataType::TYPE_STRING);
        $hoja_wr->setCellValueExplicit("P2", '', DataType::TYPE_STRING);
        $hoja_wr->setCellValueExplicit("Q2", '', DataType::TYPE_STRING);
        $hoja_wr->setCellValueExplicit("R2", '', DataType::TYPE_STRING);
        $hoja_wr->setCellValueExplicit("S2", '', DataType::TYPE_STRING);
        $hoja_wr->setCellValueExplicit("T2", '', DataType::TYPE_STRING);

        return $hoja_wr;

    }

    private function write_rows(Worksheet $hoja_wr, array $layout_dispersion): Worksheet|array
    {
        $row_ini = 3;
        $total = 0;
        foreach ($layout_dispersion as $row){
            $total += $row->importe_total_programa;
            $hoja_wr = $this->write_row($hoja_wr, $row, $row_ini);
            if(errores::$error){
                return (new errores())->error(mensaje: 'Error al obtener $hoja_wr', data: $hoja_wr);
            }
            $row_ini++;
        }
        $this->write_row_base($hoja_wr, $layout_dispersion[0], $total);
        return $hoja_wr;

    }

    private function get_clave_banco(string $nombre_banco): string
    {
        // Normaliza acentos, mayúsculas y espacios
        $nombre_banco = strtoupper(trim($nombre_banco));
        $nombre_banco = str_replace(
            ['Á', 'É', 'Í', 'Ó', 'Ú'],
            ['A', 'E', 'I', 'O', 'U'],
            $nombre_banco
        );

        $banco_array = [
            'ACCENDO BANCO' => '83',
            'ACCIVAL' => '12',
            'ACTINVER' => '26',
            'AFIRME' => '15',
            'ALBO' => '139',
            'AMERICAN EXPRES' => '17',
            'ARCUS FI' => '125',
            'ASP INTEGRA OPC' => '31',
            'AXA' => '101',
            'AZTECA' => '21',
            'B&B' => '35',
            'BABIEN' => '34',
            'BANAMEX' => '1',
            'BANBAJIO' => '6',
            'BANCO COVALTO' => '113',
            'BANCO FAMSA' => '24',
            'BANCO S3' => '123',
            'BANCOMEXT' => '37',
            'BANCOPPEL' => '29',
            'BANCREA' => '98',
            'BANJERCITO' => '4',
            'BANK OF AMERICA' => '36',
            'BANK OF CHINA' => '132',
            'BANKAOOL' => '18',
            'BANOBRAS' => '38',
            'BANORTE' => '16',
            'BANREGIO' => '28',
            'BANSI' => '14',
            'BANXICO' => '39',
            'BARCLAYS' => '40',
            'BBASE' => '41',
            'BBVA MEXICO' => '2',
            'BBVA' => '2',
            'BMONEX' => '19',
            'CAJA POP MEXICA' => '102',
            'CAJA TELEFONIST' => '108',
            'CASHI CUENTA' => '133',
            'CB ACTINVER' => '43',
            'CB INTERCAM' => '44',
            'CB JPMORGAN' => '45',
            'CBDEUTSCHE' => '46',
            'CI BOLSA' => '54',
            'CITY MEXICO' => '58',
            'CLS' => '134',
            'COMPARTAMOS' => '23',
            'CONSUBANCO' => '56',
            'CREDICAPITAL' => '51',
            'CREDICLUB' => '135',
            'CREDIT SUISSE' => '57',
            'CRISTOBAL COLON' => '105',
            'CUENCA' => '129',
            'DEP Y PAG DIG' => '141',
            'DONDE' => '97',
            'ESTRUCTURADORES' => '59',
            'EVERCORE' => '60',
            'FINAMEX' => '61',
            'FINCO PAY' => '137',
            'FINCOMUN' => '48',
            'FND' => '104',
            'FONDEADORA' => '128',
            'FONDO (FIRA)' => '109',
            'FORJADORES' => '95',
            'GBM' => '62',
            'GNP' => '100',
            'HDI SEGUROS' => '63',
            'HEY BANCO' => '138',
            'HIPOTECARIA FED' => '64',
            'HSBC' => '5',
            'HUASTECAS' => '99',
            'ICBC' => '114',
            'INBURSA' => '8',
            'INDEVAL' => '65',
            'INFONAVIT' => '111',
            'INMOBILIARIO' => '96',
            'INTERACCIONES' => '9',
            'INTERCAM BANCO' => '66',
            'INVERCAP' => '110',
            'INVEX' => '13',
            'JP MORGAN' => '67',
            'KAPITAL' => '22',
            'KLAR' => '126',
            'KUSPIT' => '68',
            'LIBERTAD' => '69',
            'MAPFRE' => '70',
            'MASARI' => '71',
            'MERCADO PAGO W' => '127',
            'MERRILL LYNCH' => '72',
            'MEXPAGO' => '140',
            'MIFEL' => '10',
            'MIZUHO BANK' => '116',
            'MONEXCB' => '73',
            'MUFG' => '85',
            'MULTIVA BANCO' => '25',
            'NAFIN' => '74',
            'NU MEXICO' => '49',
            'NVIO' => '124',
            'OACTIN' => '75',
            'ORDER' => '76',
            'OSKNDIA' => '77',
            'PAGATODO' => '94',
            'PEIBO' => '131',
            'PERSEVERANCIA' => '107',
            'PRINCIPAL' => '106',
            'PROFUTURO' => '78',
            'REFORMA' => '47',
            'SABADELL' => '115',
            'SANTANDER' => '3',
            'SCOTIABANK' => '11',
            'SEGMTY' => '79',
            'SKANDIA' => '80',
            'SOFIEXPRESS' => '52',
            'SPIN BY OXXO' => '130',
            'STP' => '82',
            'SURA' => '103',
            'TELECOMM' => '50',
            'TESORED' => '136',
            'TIBER' => '84',
            'TRANSFER' => '118',
            'UALA' => '30',
            'UBS BANK' => '86',
            'UNAGRA' => '53',
            'UNICA' => '87',
            'VALMEX' => '88',
            'VALUE' => '89',
            'VE POR MAS' => '20',
            'VECTOR' => '90',
            'VOLKSWAGEN' => '32',
            'WAL-MART' => '27',
            'ZURICH' => '91',
            'ZURICHVI' => '92',
            'BANCO DEL BIENESTAR' => '166',
            'BIENESTAR' => '166',
            'CIBANCO' => '143'
        ];

        return $banco_array[$nombre_banco] ?? '';
    }

}
