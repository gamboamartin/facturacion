<?php
namespace gamboamartin\facturacion\instalacion;

use gamboamartin\administrador\models\_instalacion;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\_etapa;
use gamboamartin\facturacion\models\_transacciones_fc;
use gamboamartin\facturacion\models\fc_complemento_pago;
use gamboamartin\facturacion\models\fc_complemento_pago_etapa;
use gamboamartin\facturacion\models\fc_factura;
use gamboamartin\facturacion\models\fc_factura_etapa;
use gamboamartin\facturacion\models\fc_nota_credito;
use gamboamartin\facturacion\models\fc_nota_credito_etapa;
use PDO;
use stdClass;

class instalacion
{

    private function _add_fc_factura_etapa(PDO $link): array|stdClass
    {
        $out = new stdClass();
        $create = (new _instalacion(link: $link))->create_table_new(table: 'fc_factura_etapa');
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al create table', data:  $create);
        }
        $out->create = $create;
        $foraneas = array();
        $foraneas['pr_etapa_proceso_id'] = new stdClass();
        $foraneas['fc_factura_id'] = new stdClass();

        $foraneas_r = (new _instalacion(link:$link))->foraneas(foraneas: $foraneas,table:  'fc_factura_etapa');

        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar foranea', data:  $foraneas_r);
        }
        $out->foraneas_r = $foraneas_r;
        $campos = new stdClass();
        $campos->fecha = new stdClass();
        $campos->fecha->tipo_dato = 'DATE';
        $campos->fecha->default = '1900-01-01';

        $result = (new _instalacion(link: $link))->add_columns(campos: $campos,table:  'fc_factura_etapa');

        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar campos', data:  $result);
        }
        $out->columnas = $result;
        return $out;

    }
    private function add_foraneas_facturacion(PDO $link,string $table)
    {
        $init = (new _instalacion(link: $link));
        $foraneas = $this->foraneas_factura();
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al obtener foraneas', data:  $foraneas);
        }


        $foraneas_r = $init->foraneas(foraneas: $foraneas,table:  $table);

        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar foranea', data:  $foraneas_r);
        }

        return $foraneas_r;

    }

    /**
     * POR DOCUMENTAR EN WIKI
     * Esta función devuelve un array de campos que son tratados como doubles en la facturación.
     *
     * @return array Retorna un array que consiste en nombres de campos que son tratados como doubles. Los campos incluidos son:
     *               'cantidad',
     *               'valor_unitario',
     *               'descuento',
     *               'total_traslados',
     *               'total_retenciones',
     *               'total',
     *               'monto_pago_nc',
     *               'monto_pago_cp',
     *               'saldo',
     *               'monto_saldo_aplicado',
     *               'total_descuento',
     *               'sub_total_base',
     *               'sub_total'
     * @version 22.2.0
     */
    private function campos_doubles_facturacion(): array
    {
        $campos_double = array();
        $campos_double[] = 'cantidad';
        $campos_double[] = 'valor_unitario';
        $campos_double[] = 'descuento';
        $campos_double[] = 'total_traslados';
        $campos_double[] = 'total_retenciones';
        $campos_double[] = 'total';
        $campos_double[] = 'monto_pago_nc';
        $campos_double[] = 'monto_pago_cp';
        $campos_double[] = 'saldo';
        $campos_double[] = 'monto_saldo_aplicado';
        $campos_double[] = 'total_descuento';
        $campos_double[] = 'sub_total_base';
        $campos_double[] = 'sub_total';
        return $campos_double;

    }

    /**
     * POR DOCUMENTAR EN WIKI
     * Esta es la función `campos_double_facturacion_integra` de la clase `Instalacion`.
     *
     * Esta función realiza la integración de los campos que son tratados como números de doble precisión (double) en
     * el contexto de la facturación.
     *
     * @param stdClass $campos Objeto que contiene los metadatos de los campos
     * @param PDO $link Representa una conexión a una base de datos.
     * @return array|stdClass Retorna un arreglo de campos dobles configurados correctamente, o en caso de error,
     * un objeto de la clase `errores`.
     *
     * @throws errores En caso de que ocurra algún error durante el proceso, se lanza una excepción de la clase errores.
     *
     * Primero, se crea una nueva instancia de `_instalacion` con el enlace PDO proporcionado.
     * Se obtiene la lista de campos double llamando a la función `campos_doubles_facturacion()`, y en caso de error,
     * se retorna un nuevo objeto de la clase `errores`.
     * A continuación, en la instancia `_instalacion` creada se realiza la adecuación predeterminada de los campos
     * double utilizando el método `campos_double_default()`,
     * pasando como parámetros el objeto $campos y los campos dobles obtenidos en el paso anterior.
     * Si ocurre algún error en este punto, se retorna un nuevo objeto de la clase `errores`.
     * Si todo el proceso se realiza sin errores, se retornan los campos ajustados.
     * @version 24.0.0
     */
    private function campos_double_facturacion_integra(stdClass $campos, PDO $link): array|stdClass
    {
        $init = (new _instalacion(link: $link));

        $campos_double = $this->campos_doubles_facturacion();
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al obtener campos_double', data:  $campos_double);
        }

        $campos = $init->campos_double_default(campos: $campos,name_campos:  $campos_double);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar campos double', data:  $campos);
        }
        return $campos;

    }

    private function campos_status_factura(stdClass $campos, PDO $link)
    {
        $init = (new _instalacion(link: $link));
        $name_campos = array();
        $name_campos[] = 'aplica_saldo';
        $name_campos[] = 'es_plantilla';

        $campos = $init->campos_status_inactivo(campos: $campos,name_campos:  $name_campos);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar campos status', data:  $campos);
        }

        return $campos;

    }

    private function exe_campos_factura(PDO $link, _transacciones_fc $modelo, _etapa $modelo_etapa)
    {
        $init = (new _instalacion(link: $link));

        $campos = $this->init_campos_factura(link: $link);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar campos status', data:  $campos);
        }


        $campos_r = $init->add_columns(campos: $campos,table:  $modelo->tabla);

        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar foranea', data:  $campos_r);
        }

        print_r($_SESSION['entidades_bd']);
        print_r($_SESSION['campos_tabla']);
        print_r($_SESSION['columnas_completas']);

        $registros = $modelo->registros(columnas_en_bruto: true);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al obtener registros', data:  $registros);
        }

        foreach ($registros as $registro){

            $ultima_etapa = $modelo->ultima_etapa(modelo_etapa: $modelo_etapa, registro_id: $registro['id']);
            if(errores::$error){
                return (new errores())->error(mensaje: 'Error al obtener ultima_etapa', data:  $ultima_etapa);
            }
            $etapa_descripcion = 'ALTA';
            if(!isset($ultima_etapa->pr_etapa_descripcion)){
                $etapa_descripcion = $ultima_etapa->pr_etapa_descripcion;
            }


            if(!isset($registro['etapa'])){
                return (new errores())->error(mensaje: 'Error no se asigno el campo etapa', data:  $registro);
            }

            if($etapa_descripcion !== $registro['etapa']){
                if(is_null($etapa_descripcion)){
                    $etapa_descripcion = 'ALTA';
                }
                $upd = $modelo->modifica_etapa(etapa_descripcion: $etapa_descripcion, registro_id: $registro['id']);
                if(errores::$error){
                    return (new errores())->error(mensaje: 'Error al actualizar etapa', data:  $upd);
                }
            }
        }


        return $campos_r;

    }

    private function fc_complemento_pago(PDO $link): array|stdClass
    {

        $modelo = new fc_complemento_pago(link: $link, valida_atributos_criticos: false);
        $modelo_etapa = new fc_complemento_pago_etapa(link: $link);

        $foraneas_r = $this->add_foraneas_facturacion(link: $link,table: __FUNCTION__);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar foranea', data:  $foraneas_r);
        }

        $campos_r = $this->exe_campos_factura(link: $link, modelo: $modelo,modelo_etapa: $modelo_etapa);

        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar foranea', data:  $campos_r);
        }

        $result = new stdClass();
        $result->foranenas = $foraneas_r;
        $result->campos = $campos_r;

        return $result;

    }

    private function fc_ejecucion_aut_plantilla(PDO $link): array|stdClass
    {
        $init = (new _instalacion(link: $link));

        $existe_entidad = $init->existe_entidad(table: __FUNCTION__);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al verificar table', data:  $existe_entidad);
        }

        if(!$existe_entidad) {

            $campos = new stdClass();
            $create_table = $init->create_table(campos: $campos, table: __FUNCTION__);
            if (errores::$error) {
                return (new errores())->error(mensaje: 'Error al crear table', data: $create_table);
            }
        }


        $foraneas = array();
        $foraneas['com_tipo_cliente_id'] = new stdClass();

        $foraneas_r = $init->foraneas(foraneas: $foraneas,table:  __FUNCTION__);

        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar foranea', data:  $foraneas_r);
        }



        return $foraneas_r;

    }

    private function fc_factura(PDO $link): array|stdClass
    {
        $create = $this->_add_fc_factura_etapa(link: $link);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar create', data:  $create);
        }

        $modelo = new fc_factura(link: $link, valida_atributos_criticos: false);
        $modelo_etapa = new fc_factura_etapa(link: $link);

        $foraneas_r = $this->add_foraneas_facturacion(link: $link,table: __FUNCTION__);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar foranea', data:  $foraneas_r);
        }


        $campos_r = $this->exe_campos_factura(link: $link, modelo: $modelo, modelo_etapa: $modelo_etapa);

        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar foranea', data:  $campos_r);
        }

        $result = new stdClass();
        $result->foraneas = $foraneas_r;
        $result->campos_r = $campos_r;

        return $result;


    }

    private function fc_factura_aut_plantilla(PDO $link): array|stdClass
    {
        $out = new stdClass();
        $init = (new _instalacion(link: $link));



        $create_table = $init->create_table_new( table: __FUNCTION__);
        if (errores::$error) {
            return (new errores())->error(mensaje: 'Error al crear table '.__FUNCTION__, data: $create_table);
        }
        $out->create_table = $create_table;



        $foraneas = array();
        $foraneas['fc_ejecucion_aut_plantilla_id'] = new stdClass();
        $foraneas['fc_factura_id'] = new stdClass();

        $foraneas_r = $init->foraneas(foraneas: $foraneas,table:  __FUNCTION__);

        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar foranea', data:  $foraneas_r);
        }
        $out->foraneas_r = $foraneas_r;

        $name_index = 'unique_fc_factura_id_exe';
        $existe_indice = $init->existe_indice_by_name(name_index: $name_index, table: __FUNCTION__);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al VERIFICAR SI EXISTE INDICE', data:  $existe_indice);
        }
        if(!$existe_indice){
            $columnas = array();
            $columnas[] = 'fc_factura_id';
            $columnas[] = 'fc_ejecucion_aut_plantilla_id';
            $uniques = $init->index_unique(columnas: $columnas, table: __FUNCTION__,index_name: $name_index);
            if(errores::$error){
                return (new errores())->error(mensaje: 'Error al ajustar uniques', data:  $uniques);
            }
            $out->uniques = $uniques;
        }

        return $out;

    }

    private function fc_factura_etapa(PDO $link): array|stdClass
    {
        $create = $this->_add_fc_factura_etapa(link: $link);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar create', data:  $create);
        }

        return $create;

    }

    private function fc_nota_credito(PDO $link): array|stdClass
    {
        $modelo = new fc_nota_credito(link: $link, valida_atributos_criticos: false);
        $modelo_etapa = new fc_nota_credito_etapa(link: $link);
        $foraneas_r = $this->add_foraneas_facturacion(link: $link,table: __FUNCTION__);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar foranea', data:  $foraneas_r);
        }



        $campos_r = $this->exe_campos_factura(link: $link, modelo: $modelo, modelo_etapa: $modelo_etapa);

        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar foranea', data:  $campos_r);
        }

        $result = new stdClass();
        $result->foraneas = $foraneas_r;
        $result->campos_r = $campos_r;

        return $result;


    }

    private function fc_partida(PDO $link): array|stdClass
    {
        $init = (new _instalacion(link: $link));
        $foraneas = array();
        $foraneas['com_producto_id'] = new stdClass();
        $foraneas['fc_factura_id'] = new stdClass();


        $foraneas_r = $init->foraneas(foraneas: $foraneas,table:  'fc_partida');

        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar foranea', data:  $foraneas_r);
        }


        $campos = new stdClass();

        $campos->cantidad = new stdClass();
        $campos->cantidad->tipo_dato = 'double';
        $campos->cantidad->default = '0';
        $campos->cantidad->longitud = '100,2';

        $campos->valor_unitario = new stdClass();
        $campos->valor_unitario->tipo_dato = 'double';
        $campos->valor_unitario->default = '0';
        $campos->valor_unitario->longitud = '100,2';

        $campos->descuento = new stdClass();
        $campos->descuento->tipo_dato = 'double';
        $campos->descuento->default = '0';
        $campos->descuento->longitud = '100,2';

        $campos->sub_total = new stdClass();
        $campos->sub_total->tipo_dato = 'double';
        $campos->sub_total->default = '0';
        $campos->sub_total->longitud = '100,2';

        $campos->total_traslados = new stdClass();
        $campos->total_traslados->tipo_dato = 'double';
        $campos->total_traslados->default = '0';
        $campos->total_traslados->longitud = '100,2';

        $campos->total_retenciones = new stdClass();
        $campos->total_retenciones->tipo_dato = 'double';
        $campos->total_retenciones->default = '0';
        $campos->total_retenciones->longitud = '100,2';

        $campos->sub_total_base = new stdClass();
        $campos->sub_total_base->tipo_dato = 'double';
        $campos->sub_total_base->default = '0';
        $campos->sub_total_base->longitud = '100,2';

        $campos->total = new stdClass();
        $campos->total->tipo_dato = 'double';
        $campos->total->default = '0';
        $campos->total->longitud = '100,2';


        $campos_r = $init->add_columns(campos: $campos,table:  'fc_partida');

        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar foranea', data:  $campos_r);
        }


        $result = new stdClass();
        $result->foranenas = $foraneas_r;
        $result->campos = $campos_r;

        return $result;

    }

    private function fc_partida_cp(PDO $link): array|stdClass
    {
        $init = (new _instalacion(link: $link));
        $foraneas = array();
        $foraneas['com_producto_id'] = new stdClass();
        $foraneas['fc_complemento_pago_id'] = new stdClass();


        $foraneas_r = $init->foraneas(foraneas: $foraneas,table:  'fc_partida_cp');

        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar foranea', data:  $foraneas_r);
        }


        $campos = new stdClass();

        $campos->sub_total = new stdClass();
        $campos->sub_total->tipo_dato = 'double';
        $campos->sub_total->default = '0';
        $campos->sub_total->longitud = '100,2';

        $campos->total_traslados = new stdClass();
        $campos->total_traslados->tipo_dato = 'double';
        $campos->total_traslados->default = '0';
        $campos->total_traslados->longitud = '100,2';

        $campos->total_retenciones = new stdClass();
        $campos->total_retenciones->tipo_dato = 'double';
        $campos->total_retenciones->default = '0';
        $campos->total_retenciones->longitud = '100,2';

        $campos->sub_total_base = new stdClass();
        $campos->sub_total_base->tipo_dato = 'double';
        $campos->sub_total_base->default = '0';
        $campos->sub_total_base->longitud = '100,2';

        $campos->total = new stdClass();
        $campos->total->tipo_dato = 'double';
        $campos->total->default = '0';
        $campos->total->longitud = '100,2';

        $campos_r = $init->add_columns(campos: $campos,table:  'fc_partida_cp');

        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar foranea', data:  $campos_r);
        }

        $result = new stdClass();
        $result->foranenas = $foraneas_r;
        $result->campos = $campos_r;

        return $result;

    }

    private function fc_partida_nc(PDO $link): array|stdClass
    {
        $init = (new _instalacion(link: $link));
        $foraneas = array();
        $foraneas['com_producto_id'] = new stdClass();
        $foraneas['fc_nota_credito_id'] = new stdClass();

        $foraneas_r = $init->foraneas(foraneas: $foraneas,table:  'fc_partida_nc');

        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar foranea', data:  $foraneas_r);
        }


        $campos = new stdClass();

        $campos->cantidad = new stdClass();
        $campos->cantidad->tipo_dato = 'double';
        $campos->cantidad->default = '0';
        $campos->cantidad->longitud = '100,2';

        $campos->valor_unitario = new stdClass();
        $campos->valor_unitario->tipo_dato = 'double';
        $campos->valor_unitario->default = '0';
        $campos->valor_unitario->longitud = '100,2';

        $campos->descuento = new stdClass();
        $campos->descuento->tipo_dato = 'double';
        $campos->descuento->default = '0';
        $campos->descuento->longitud = '100,2';

        $campos->sub_total = new stdClass();
        $campos->sub_total->tipo_dato = 'double';
        $campos->sub_total->default = '0';
        $campos->sub_total->longitud = '100,2';

        $campos->total_traslados = new stdClass();
        $campos->total_traslados->tipo_dato = 'double';
        $campos->total_traslados->default = '0';
        $campos->total_traslados->longitud = '100,2';

        $campos->total_retenciones = new stdClass();
        $campos->total_retenciones->tipo_dato = 'double';
        $campos->total_retenciones->default = '0';
        $campos->total_retenciones->longitud = '100,2';

        $campos->sub_total_base = new stdClass();
        $campos->sub_total_base->tipo_dato = 'double';
        $campos->sub_total_base->default = '0';
        $campos->sub_total_base->longitud = '100,2';

        $campos->total = new stdClass();
        $campos->total->tipo_dato = 'double';
        $campos->total->default = '0';
        $campos->total->longitud = '100,2';


        $campos_r = $init->add_columns(campos: $campos,table:  'fc_partida_nc');

        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar foranea', data:  $campos_r);
        }

        $result = new stdClass();
        $result->foranenas = $foraneas_r;
        $result->campos = $campos_r;

        return $result;

    }

    private function fc_retenido(PDO $link): array|stdClass
    {
        $init = (new _instalacion(link: $link));

        $foraneas = array();
        $foraneas['fc_partida_id'] = new stdClass();
        $foraneas['cat_sat_tipo_factor_id'] = new stdClass();
        $foraneas['cat_sat_factor_id'] = new stdClass();
        $foraneas['cat_sat_tipo_impuesto_id'] = new stdClass();


        $foraneas_r = $init->foraneas(foraneas: $foraneas,table:  'fc_retenido');

        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar foranea', data:  $foraneas_r);
        }


        $campos = new stdClass();

        $campos->total = new stdClass();
        $campos->total->tipo_dato = 'double';
        $campos->total->default = '0';
        $campos->total->longitud = '100,2';

        $campos_r = $init->add_columns(campos: $campos,table:  'fc_retenido');

        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar foranea', data:  $campos_r);
        }

        $result = new stdClass();
        $result->foranenas = $foraneas_r;
        $result->campos = $campos_r;

        return $result;

    }

    private function fc_retenido_nc(PDO $link): array|stdClass
    {
        $init = (new _instalacion(link: $link));

        $foraneas = array();
        $foraneas['fc_partida_nc_id'] = new stdClass();
        $foraneas['cat_sat_tipo_factor_id'] = new stdClass();
        $foraneas['cat_sat_factor_id'] = new stdClass();
        $foraneas['cat_sat_tipo_impuesto_id'] = new stdClass();


        $foraneas_r = $init->foraneas(foraneas: $foraneas,table:  'fc_retenido_nc');

        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar foranea', data:  $foraneas_r);
        }


        $campos = new stdClass();

        $campos->total = new stdClass();
        $campos->total->tipo_dato = 'double';
        $campos->total->default = '0';
        $campos->total->longitud = '100,2';

        $campos_r = $init->add_columns(campos: $campos,table:  'fc_retenido_nc');

        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar foranea', data:  $campos_r);
        }

        $result = new stdClass();
        $result->foranenas = $foraneas_r;
        $result->campos = $campos_r;

        return $result;

    }

    private function fc_traslado(PDO $link): array|stdClass
    {
        $init = (new _instalacion(link: $link));
        $foraneas = array();
        $foraneas['fc_partida_id'] = new stdClass();
        $foraneas['cat_sat_tipo_factor_id'] = new stdClass();
        $foraneas['cat_sat_factor_id'] = new stdClass();
        $foraneas['cat_sat_tipo_impuesto_id'] = new stdClass();


        $foraneas_r = $init->foraneas(foraneas: $foraneas,table:  'fc_traslado');

        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar foranea', data:  $foraneas_r);
        }


        $campos = new stdClass();

        $campos->total = new stdClass();
        $campos->total->tipo_dato = 'double';
        $campos->total->default = '0';
        $campos->total->longitud = '100,2';


        $campos_r = $init->add_columns(campos: $campos,table:  'fc_traslado');

        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar foranea', data:  $campos_r);
        }

        $result = new stdClass();
        $result->foranenas = $foraneas_r;
        $result->campos = $campos_r;

        return $result;

    }

    /**
     * POR DOCUMENTAR WIKI
     * Esta función devuelve un array con las claves foráneas utilizadas en la factura.
     *
     * @return array Las claves foráneas utilizadas en la factura.
     * @version 20.3.0
     */
    private function foraneas_factura(): array
    {
        $foraneas = array();
        $foraneas['fc_csd_id'] = new stdClass();
        $foraneas['cat_sat_forma_pago_id'] = new stdClass();
        $foraneas['cat_sat_metodo_pago_id'] = new stdClass();
        $foraneas['cat_sat_moneda_id'] = new stdClass();
        $foraneas['com_tipo_cambio_id'] = new stdClass();
        $foraneas['cat_sat_uso_cfdi_id'] = new stdClass();
        $foraneas['cat_sat_tipo_de_comprobante_id'] = new stdClass();
        $foraneas['dp_calle_pertenece_id'] = new stdClass();
        $foraneas['cat_sat_regimen_fiscal_id'] = new stdClass();
        $foraneas['com_sucursal_id'] = new stdClass();
        return $foraneas;

    }

    private function init_campos_factura(PDO $link)
    {
        $campos = new stdClass();
        $campos = $this->campos_double_facturacion_integra(campos: $campos,link:  $link);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar campos double', data:  $campos);
        }


        $campos = $this->campos_status_factura(campos: $campos,link:  $link);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar campos status', data:  $campos);
        }

        $campos->folio_fiscal = new stdClass();
        $campos->folio_fiscal->default = 'SIN ASIGNAR';

        $campos->etapa = new stdClass();
        $campos->etapa->default = 'ALTA';

        return $campos;

    }
    final public function instala(PDO $link): array|stdClass
    {

        $result = new stdClass();


        $fc_factura = $this->fc_factura(link: $link);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar fc_factura', data:  $fc_factura);
        }

        $result->fc_factura = $fc_factura;

        $fc_factura_etapa = $this->fc_factura_etapa(link: $link);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar fc_factura_etapa', data:  $fc_factura_etapa);
        }

        $result->fc_factura_etapa = $fc_factura_etapa;

        $fc_complemento_pago = $this->fc_complemento_pago(link: $link);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar fc_complemento_pago', data:  $fc_complemento_pago);
        }
        $result->fc_complemento_pago = $fc_complemento_pago;

        $fc_partida = $this->fc_partida(link: $link);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar fc_partida', data:  $fc_partida);
        }
        $result->fc_partida = $fc_partida;

        $fc_traslado = $this->fc_traslado(link: $link);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar fc_traslado', data:  $fc_traslado);
        }
        $result->fc_traslado = $fc_traslado;


        $fc_partida_nc = $this->fc_partida_nc(link: $link);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar fc_partida_nc', data:  $fc_partida_nc);
        }
        $result->fc_partida_nc = $fc_partida_nc;

        $fc_partida_cp = $this->fc_partida_cp(link: $link);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar fc_partida_nc', data:  $fc_partida_cp);
        }
        $result->fc_partida_cp = $fc_partida_cp;

        $fc_retenido = $this->fc_retenido(link: $link);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar fc_retenido', data:  $fc_retenido);
        }
        $result->fc_retenido = $fc_retenido;

        $fc_nota_credito = $this->fc_nota_credito(link: $link);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar fc_retenido', data:  $fc_nota_credito);
        }
        $result->fc_nota_credito = $fc_nota_credito;

        $fc_retenido_nc = $this->fc_retenido_nc(link: $link);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar fc_retenido_nc', data:  $fc_retenido_nc);
        }
        $result->fc_retenido_nc = $fc_retenido_nc;

        $fc_ejecucion_aut_plantilla = $this->fc_ejecucion_aut_plantilla(link: $link);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar fc_ejecucion_aut_plantilla',
                data:  $fc_ejecucion_aut_plantilla);
        }
        $result->fc_ejecucion_aut_plantilla = $fc_ejecucion_aut_plantilla;

        $fc_factura_aut_plantilla = $this->fc_factura_aut_plantilla(link: $link);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar fc_ejecucion_aut_plantilla',
                data:  $fc_factura_aut_plantilla);
        }
        $result->fc_factura_aut_plantilla = $fc_factura_aut_plantilla;


        return $result;

    }

}
