<?php
namespace gamboamartin\facturacion\html;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\controllers\controlador_fc_retencion_p;
use gamboamartin\facturacion\models\fc_retencion_p;
use gamboamartin\system\html_controler;
use PDO;
use stdClass;

class fc_retencion_p_html extends html_controler {

    private function asigna_inputs(controlador_fc_retencion_p $controler, stdClass $inputs): array|stdClass
    {
        $controler->inputs->select = new stdClass();
        /**$controler->inputs->select->fc_complemento_pago_id = $inputs->selects->fc_complemento_pago_id;
        $controler->inputs->version = $inputs->texts->version;
         */
        return $controler->inputs;
    }

    public function genera_inputs_alta(controlador_fc_retencion_p $controler, PDO $link): array|stdClass
    {
        $inputs = $this->init_alta(keys_selects: array(), link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar inputs',data:  $inputs);

        }
        $inputs_asignados = $this->asigna_inputs(controler:$controler, inputs: $inputs);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al asignar inputs',data:  $inputs_asignados);
        }

        return $inputs_asignados;
    }

    private function genera_inputs_modifica(controlador_fc_retencion_p $controler,PDO $link,
                                            stdClass $params = new stdClass()): array|stdClass
    {
        $inputs = $this->init_modifica(link: $link, row_upd: $controler->row_upd, params: $params);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar inputs',data:  $inputs);

        }
        $inputs_asignados = $this->asigna_inputs(controler:$controler, inputs: $inputs);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al asignar inputs',data:  $inputs_asignados);
        }

        return $inputs_asignados;
    }

    protected function init_alta(array $keys_selects, PDO $link): array|stdClass
    {
        $selects = $this->selects_alta(keys_selects: $keys_selects, link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar selects',data:  $selects);
        }

        $texts = $this->texts_alta(row_upd: new stdClass(), value_vacio: true);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar texts',data:  $texts);
        }

        $alta_inputs = new stdClass();
        $alta_inputs->selects = $selects;
        $alta_inputs->texts = $texts;

        return $alta_inputs;
    }

    private function init_modifica(PDO $link, stdClass $row_upd, stdClass $params = new stdClass()): array|stdClass
    {
        $selects = $this->selects_modifica(link: $link, row_upd: $row_upd);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar selects',data:  $selects);
        }

        $texts = $this->texts_alta(row_upd: $row_upd, value_vacio: false, params: $params);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar texts',data:  $texts);
        }

        $alta_inputs = new stdClass();
        $alta_inputs->texts = $texts;
        $alta_inputs->selects = $selects;
        return $alta_inputs;
    }

    protected function selects_alta(array $keys_selects,PDO $link): array|stdClass
    {
        $selects = new stdClass();
        return $selects;
    }

    private function selects_modifica(PDO $link, stdClass $row_upd): array|stdClass
    {
        $selects = new stdClass();
        return $selects;
    }

    public function select_fc_retencion_p_id(int $cols, bool $con_registros, int $id_selected, PDO $link,
                                                bool $disabled = false, array $filtro = array()): array|string
    {
        $modelo = new fc_retencion_p(link: $link);

        $select = $this->select_catalogo(cols: $cols, con_registros: $con_registros, id_selected: $id_selected,
            modelo: $modelo, disabled: $disabled, filtro: $filtro, label: 'Pago', required: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al generar select', data: $select);
        }
        return $select;
    }

    protected function texts_alta(stdClass $row_upd, bool $value_vacio, stdClass $params = new stdClass()): array|stdClass
    {
        $texts = new stdClass();
        return $texts;
    }

}
