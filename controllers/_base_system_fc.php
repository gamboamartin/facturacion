<?php

namespace gamboamartin\facturacion\controllers;

use gamboamartin\cat_sat\models\cat_sat_forma_pago;
use gamboamartin\cat_sat\models\cat_sat_metodo_pago;
use gamboamartin\cat_sat\models\cat_sat_moneda;
use gamboamartin\cat_sat\models\cat_sat_regimen_fiscal;
use gamboamartin\cat_sat\models\cat_sat_tipo_de_comprobante;
use gamboamartin\cat_sat\models\cat_sat_uso_cfdi;
use gamboamartin\comercial\models\com_sucursal;
use gamboamartin\comercial\models\com_tipo_cambio;
use gamboamartin\direccion_postal\models\dp_calle_pertenece;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\html\_base_fc_html;
use gamboamartin\facturacion\models\com_producto;
use gamboamartin\facturacion\models\fc_csd;
use stdClass;

class _base_system_fc extends _base_system{

    protected string $cat_sat_tipo_de_comprobante;
    protected _base_fc_html $html_fc;
    protected array $data_selected_alta = array();

    public function alta(bool $header, bool $ws = false): array|string
    {
        /**
         * REFACTORIZAR
         */
        $parents = $this->parents();
        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al obtener parents',data:  $parents);
            print_r($error);
            exit;
        }

        $r_alta =  parent::alta(header: false);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar template',data:  $r_alta, header: $header,ws:$ws);
        }

        $tipo_comprobante = $this->get_tipo_comprobante();
        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al obtener tipo de comprobante',data:  $tipo_comprobante);
            print_r($error);
            die('Error');
        }

        $this->asignar_propiedad(identificador: 'cat_sat_tipo_de_comprobante_id',
            propiedades: ["id_selected" => $tipo_comprobante,
                "filtro" => array('cat_sat_tipo_de_comprobante.id' => $tipo_comprobante)]);

        $this->row_upd->fecha = date('Y-m-d');
        $this->row_upd->subtotal = 0;
        $this->row_upd->descuento = 0;
        $this->row_upd->impuestos_trasladados = 0;
        $this->row_upd->impuestos_retenidos = 0;
        $this->row_upd->total = 0;
        $this->row_upd->exportacion = '01';


        $inputs = $this->genera_inputs(keys_selects:  $this->keys_selects);
        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al generar inputs',data:  $inputs);
            print_r($error);
            die('Error');
        }

        $observaciones = $this->html_fc->input_observaciones(cols: 12,row_upd: new stdClass(),value_vacio: false);
        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al generar observaciones',data:  $observaciones);
            print_r($error);
            die('Error');
        }
        $this->inputs->observaciones = $observaciones;



        return $r_alta;
    }

    private function get_tipo_comprobante(): array|int
    {
        $tipo_comprobante = $this->tipo_de_comprobante_predeterminado();
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener tipo de comprobante predeterminado',
                data:  $tipo_comprobante);
        }

        return $tipo_comprobante->registros[0]['cat_sat_tipo_de_comprobante_id'];
    }

    final public function init_datatable(): stdClass
    {

        $columns[$this->modelo->tabla."_folio"]["titulo"] = "Fol";
        $columns["org_empresa_rfc"]["titulo"] = "RFC";
        $columns["com_cliente_rfc"]["titulo"] = "Cte";
        $columns[$this->modelo->tabla."_fecha"]["titulo"] = "Fecha";
        $columns[$this->modelo->tabla."_total"]["titulo"] = "Total";
        $columns[$this->modelo->tabla."_uuid"]["titulo"] = "UUID";
        $columns[$this->modelo->tabla."_etapa"]["titulo"] = "Estatus";

        $filtro = array($this->modelo->tabla.".folio","org_empresa.rfc",
            "com_cliente.rfc",$this->modelo->tabla.'.fecha',$this->modelo->tabla.'_etapa');

        $datatables = new stdClass();
        $datatables->columns = $columns;
        $datatables->filtro = $filtro;

        return $datatables;
    }

    public function init_inputs(): array
    {
        $identificador = "fc_csd_id";
        $propiedades = array("label" => "Empresa", "cols" => 12,"extra_params_keys"=>array("fc_csd_serie"));
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "com_sucursal_id";
        $propiedades = array("label" => "Cliente", "cols" => 12,"extra_params_keys" => array("com_cliente_cat_sat_forma_pago_id",
            "com_cliente_cat_sat_metodo_pago_id","com_cliente_cat_sat_moneda_id","com_cliente_cat_sat_uso_cfdi_id"));
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "cat_sat_forma_pago_id";
        $propiedades = array("label" => "Forma Pago", 'id_selected'=>$this->data_selected_alta['cat_sat_forma_pago_id']['id'],
            'filtro'=>$this->data_selected_alta['cat_sat_forma_pago_id']['filtro']);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "cat_sat_metodo_pago_id";
        $propiedades = array("label" => "Metodo Pago");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "cat_sat_moneda_id";
        $propiedades = array("label" => "Moneda");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "com_tipo_cambio_id";
        $propiedades = array("label" => "Tipo Cambio");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "cat_sat_uso_cfdi_id";
        $propiedades = array("label" => "Uso CFDI");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "cat_sat_tipo_de_comprobante_id";
        $propiedades = array("label" => "Tipo Comprobante");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "dp_calle_pertenece_id";
        $propiedades = array("label" => "Calle");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "cat_sat_regimen_fiscal_id";
        $propiedades = array("label" => "Regimen Fiscal");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "folio";
        $propiedades = array("place_holder" => "Folio", 'required'=>false, 'disabled'=>true);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "exportacion";
        $propiedades = array("place_holder" => "Exportación");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "serie";
        $propiedades = array("place_holder" => "Serie", 'required'=>false,'disabled'=>true);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "subtotal";
        $propiedades = array("place_holder" => "Subtotal", "cols" => 4,"disabled" => true);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "descuento";
        $propiedades = array("place_holder" => "Descuento", "cols" => 4,"disabled" => true);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "impuestos_trasladados";
        $propiedades = array("place_holder" => "Imp. Trasladados", "disabled" => true);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "impuestos_retenidos";
        $propiedades = array("place_holder" => "Imp. Retenidos", "disabled" => true);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "total";
        $propiedades = array("place_holder" => "Total", "cols" => 4, "disabled" => true);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "fecha";
        $propiedades = array("place_holder" => "Fecha");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        return $this->keys_selects;
    }

    /**
     * Integra los parents de manera ordenada para su peticion
     * @return array
     */
    private function parents(): array
    {
        $this->parents_verifica[] = (new com_sucursal(link: $this->link));
        $this->parents_verifica[] = (new cat_sat_regimen_fiscal(link: $this->link));
        $this->parents_verifica[] = (new dp_calle_pertenece(link: $this->link));
        $this->parents_verifica[] = (new cat_sat_tipo_de_comprobante(link: $this->link));
        $this->parents_verifica[] = (new cat_sat_uso_cfdi(link: $this->link));
        $this->parents_verifica[] = (new com_tipo_cambio(link: $this->link));
        $this->parents_verifica[] = (new cat_sat_moneda(link: $this->link));
        $this->parents_verifica[] = (new cat_sat_metodo_pago(link: $this->link));
        $this->parents_verifica[] = (new cat_sat_forma_pago(link: $this->link));
        $this->parents_verifica[] = (new fc_csd(link: $this->link));
        $this->parents_verifica[] = (new com_producto(link: $this->link));
        return $this->parents_verifica;
    }

    private function tipo_de_comprobante_predeterminado(): array|stdClass
    {
        $filtro['cat_sat_tipo_de_comprobante.descripcion'] = $this->cat_sat_tipo_de_comprobante;
        $tipo_comprobante = (new cat_sat_tipo_de_comprobante($this->link))->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener tipo de comprobante',data:  $this->conf_generales);
        }

        if ($tipo_comprobante->n_registros === 0){
            $tipo_comprobante = (new cat_sat_tipo_de_comprobante($this->link))->get_tipo_comprobante_predeterminado();
            if(errores::$error){
                return $this->errores->error(mensaje: 'Error al obtener tipo de comprobante predeterminado',
                    data:  $tipo_comprobante);
            }
        }
        return $tipo_comprobante;
    }



}
