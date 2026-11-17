<?php

namespace Pruebas\OrdenOriginal;

use gamboamartin\errores\errores;
use gamboamartin\validacion\validacion;
use stdClass;


class init
{
    private errores $error;


    public function __construct()
    {
        $this->error = new errores();

    }

    /**
     * Obtiene draw para datatable
     * @return int|array
     * @version 0.239.37
     */
    final public function draw(): int|array
    {
        $draw = mt_rand(1, 999);
        if (isset ($_GET['draw'])) {
            $draw = $_GET['draw'];
        }
        if (!is_numeric($draw)) {
            return $this->error->error(mensaje: 'Error draw debe ser un numero', data: $draw);
        }

        return $draw;
    }

    final public function in()
    {
        $in = array();
        if (isset($_GET['in'])) {
            $in = $_GET['in'];
        }
        return $in;
    }

    /**
     * Inicializa datatables
     * @param array $filtro Filtro
     * @param string $identificador
     * @param array $data
     * @param array $in
     * @param bool $multi_selects
     * @param string $type
     * @return array
     * @version 0.151.33
     */
    final public function init_datatable(array $filtro, string $identificador = ".datatable", array $data = array(),
                                         array $in = array(), bool $multi_selects = false, string $type = "datatable"): array
    {
        $datatable["columns"] = array();
        $datatable["columnDefs"] = array();
        $datatable['filtro'] = $filtro;
        $datatable['identificador'] = $identificador;
        $datatable['data'] = $data;
        $datatable['in'] = $in;
        $datatable['multi_selects'] = $multi_selects;
        $datatable['type'] = $type;

        return $datatable;
    }

    /**
     * REG
     * Inicializa y obtiene el filtro para el DataTable.
     *
     * Este método construye un filtro basado en la sección proporcionada y la lista de filas.
     * Verifica si el filtro ya está establecido en el objeto `$datatables`. Si no lo está,
     * genera un nuevo filtro combinando la sección y cada elemento en el array `rows_lista`.
     * Si el filtro ya está presente en `$datatables`, se utiliza directamente.
     *
     * El filtro construido se devuelve como un array. Este filtro se usa para restringir
     * los datos que se mostrarán en el DataTable según criterios específicos.
     *
     * @param stdClass $datatables El objeto DataTable que puede contener un filtro preestablecido.
     *
     * @param array $rows_lista Lista de filas (campos) para las cuales se generará el filtro.
     *                           Cada elemento en este array es un nombre de campo que se combinará
     *                           con la sección para crear una condición de filtro.
     *
     * @param string $seccion El nombre de la sección utilizado para generar el filtro. Este string no debe estar vacío.
     *
     * @return array|array<string, string> Devuelve el filtro construido como un array.
     *         Si ocurre un error (como una sección vacía o una fila vacía), se devuelve un mensaje de error en su lugar.
     *
     * @example
     *  // Ejemplo 1: Creando un filtro para una sección y filas específicas
     *  $datatables = new stdClass();
     *  $rows_lista = ['campo1', 'campo2', 'campo3'];
     *  $seccion = 'mi_seccion';
     *  $filtro = $init->init_filtro_datatables($datatables, $rows_lista, $seccion);
     *  // Devuelve: ['mi_seccion.campo1', 'mi_seccion.campo2', 'mi_seccion.campo3']
     *
     *  // Ejemplo 2: Cuando el filtro ya está establecido en $datatables
     *  $datatables->filtro = ['condicion_filtro_existente'];
     *  $filtro = $init->init_filtro_datatables($datatables, $rows_lista, 'mi_seccion');
     *  // Devuelve: ['condicion_filtro_existente']
     *
     * @example
     *  // Ejemplo 3: Error cuando la sección está vacía
     *  $filtro = $init->init_filtro_datatables($datatables, $rows_lista, '');
     *  // Devuelve: ['error' => 'Error seccion esta vacia', 'data' => '']
     *
     * @example
     *  // Ejemplo 4: Error cuando una fila en $rows_lista está vacía
     *  $filtro = $init->init_filtro_datatables($datatables, ['', 'campo2'], 'mi_seccion');
     *  // Devuelve: ['error' => 'Error key_row_lista esta vacia', 'data' => '']
     */
    final public function init_filtro_datatables(stdClass $datatables, array $rows_lista, string $seccion): array
    {
        // Se elimina espacio en blanco al principio y al final de la sección
        $seccion = trim($seccion);

        // Verifica si la sección no está vacía
        if ($seccion === '') {
            return $this->error->error(mensaje: 'Error seccion esta vacia', data: $seccion);
        }

        $filtro = array();

        // Verifica si el filtro ya está establecido en el objeto datatables
        if (!isset($datatables->filtro)) {
            // Si no hay filtro, genera uno nuevo combinando la sección y las filas
            foreach ($rows_lista as $key_row_lista) {
                // Elimina espacio en blanco al principio y al final de cada campo
                $key_row_lista = trim($key_row_lista);

                // Verifica si la fila está vacía
                if ($key_row_lista === '') {
                    return $this->error->error(mensaje: 'Error key_row_lista esta vacia', data: $key_row_lista);
                }

                // Agrega el campo al filtro, precedido por el nombre de la sección
                $filtro[] = $seccion . '.' . $key_row_lista;
            }
        } else {
            // Si el filtro ya está establecido, lo usa directamente
            $filtro = $datatables->filtro;
        }

        // Devuelve el filtro construido
        return $filtro;
    }


    final public function order(array $datatable): array
    {
        if(!isset($_GET['order'][0]['dir'])){
            $_GET['order'][0]['dir'] = "ASC";
        }
        if(!isset($_GET['order'][0]['column'])){
            $_GET['order'][0]['column'] = 0;
        }


        if (count($_GET['order']) > 0 && $_GET['order'][0]['dir'] !== "desc") {
            $_GET['order'][0]['dir'] = "DESC";
        }

        $order = array();
        if (isset($_GET['order'])) {
            if (count($datatable['columns']) - 1 > $_GET['order'][0]['column']) {
                $campo = $datatable['columns'][$_GET['order'][0]['column']]->data;
                $order = array($campo => $_GET['order'][0]['dir']);
            }
        }
        return $order;
    }

    /**
     * Integra el numero de registros por pagina en el get data
     * @return int
     * @version 13.63.0
     */
    final public function n_rows_for_page(): int
    {
        $n_rows_for_page = 10;
        if (isset ($_GET['length'])) {
            $n_rows_for_page = (int)$_GET['length'];
        }
        return $n_rows_for_page;
    }

    /**
     * Obtiene la pagina de ejecucion
     * @param int $n_rows_for_page Registros por pagina
     * @return int
     */
    final public function pagina(int $n_rows_for_page): int
    {
        $pagina = 1;
        if (isset ($_GET['start'])) {
            $pagina = (int)($_GET['start'] / $n_rows_for_page) + 1;
        }
        if ($pagina <= 0) {
            $pagina = 1;
        }
        return $pagina;
    }


}
