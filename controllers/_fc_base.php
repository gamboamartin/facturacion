<?php
namespace gamboamartin\facturacion\controllers;
use base\orm\modelo;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\_etapa;
use gamboamartin\proceso\models\pr_proceso;
use stdClass;

class _fc_base{

    private errores $error;
    public function __construct(){
        $this->error = new errores();
    }

    private function aplica_etapa(string $key_factura_id_filter, modelo $modelo_etapa, int $registro_id, stdClass $verifica): bool|array
    {
        $aplica_etapa = false;
        if($verifica->mensaje === 'Cancelado'){

            $filtro['pr_etapa.descripcion'] = 'cancelado_sat';
            $filtro[$key_factura_id_filter] = $registro_id;
            $existe = $modelo_etapa->existe(filtro: $filtro);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al validar etapa',data:  $existe);
            }
            if(!$existe){
                $aplica_etapa = true;
            }
        }
        return $aplica_etapa;
    }

    /**
     * REG
     * Inicializa la base del controlador de facturación, configurando enlaces e inputs necesarios.
     *
     * Este método se encarga de inicializar los enlaces (`links`) y los inputs (`inputs`) del controlador
     * de facturación, asegurando que la configuración de la base se realice correctamente.
     *
     * ### Proceso:
     * 1. Verifica que el parámetro `$name_modelo_email` no esté vacío.
     * 2. Llama a `init_links` del controlador para inicializar enlaces relacionados con el modelo de email.
     * 3. Llama a `init_inputs` del controlador para configurar los inputs necesarios.
     * 4. Retorna un objeto con las propiedades `links` e `inputs`.
     *
     * @param _base_system_fc $controler Instancia del controlador que maneja la facturación.
     * @param string $name_modelo_email Nombre del modelo relacionado con emails. No debe estar vacío.
     *
     * @return stdClass|array Retorna un objeto con las propiedades:
     *   - `links`: Enlaces generados por `init_links`.
     *   - `inputs`: Inputs generados por `init_inputs`.
     *   Si ocurre un error, retorna un array con detalles del error.
     *
     * @example
     * ```php
     * $controler = new _base_system_fc();
     * $resultado = $this->init_base_fc($controler, 'modelo_email');
     *
     * // Posible salida:
     * stdClass Object
     * (
     *     [links] => Array
     *         (
     *             [crear] => "https://miapp.com/factura/crear"
     *             [listar] => "https://miapp.com/factura/listar"
     *         )
     *
     *     [inputs] => Array
     *         (
     *             [fc_csd_id] => Array
     *                 (
     *                     [label] => "Empresa"
     *                     [cols] => 12
     *                     [extra_params_keys] => Array
     *                         (
     *                             [0] => "fc_csd_serie"
     *                         )
     *                 )
     *         )
     * )
     * ```
     *
     * @example Manejo de error cuando $name_modelo_email está vacío:
     * ```php
     * $controler = new _base_system_fc();
     * $resultado = $this->init_base_fc($controler, '');
     *
     * // Salida esperada:
     * Array
     * (
     *     [error] => true
     *     [mensaje] => "Error $name_modelo_email esta vacio"
     *     [data] => ""
     *     [es_final] => true
     * )
     * ```
     */
    final public function init_base_fc(_base_system_fc $controler, string $name_modelo_email): array|stdClass
    {
        $name_modelo_email = trim($name_modelo_email);
        if ($name_modelo_email === '') {
            return $this->error->error(
                mensaje: 'Error $name_modelo_email esta vacio',
                data: $name_modelo_email,
                es_final: true
            );
        }

        $links = $controler->init_links(name_modelo_email: $name_modelo_email);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al inicializar links', data: $links);
        }

        $inputs = $controler->init_inputs();
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al inicializar inputs', data: $inputs);
        }

        $data = new stdClass();
        $data->links = $links;
        $data->inputs = $inputs;
        return $data;
    }


    final public function integra_etapa(string $key_factura_id_filter, modelo $modelo, modelo $modelo_etapa,
                                   int $registro_id, stdClass $verifica, bool $valida_existencia_etapa = true): bool|array
    {
        $aplica_etapa = $this->aplica_etapa(key_factura_id_filter: $key_factura_id_filter,
            modelo_etapa: $modelo_etapa, registro_id: $registro_id, verifica: $verifica);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al si aplica etapa',data:  $aplica_etapa);
        }

        if($aplica_etapa) {
            $r_alta_factura_etapa = (new pr_proceso(link: $modelo_etapa->link))->inserta_etapa(
                adm_accion: 'cancelado_sat', fecha: '', modelo: $modelo, modelo_etapa: $modelo_etapa,
                registro_id: $registro_id, valida_existencia_etapa: $valida_existencia_etapa);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al insertar etapa', data: $r_alta_factura_etapa);
            }
        }
        return $aplica_etapa;
    }






}
