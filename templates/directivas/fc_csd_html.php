<?php
namespace gamboamartin\facturacion\html;


use gamboamartin\errores\errores;
use gamboamartin\facturacion\controllers\controlador_fc_csd;
use gamboamartin\facturacion\models\fc_csd;
use gamboamartin\organigrama\html\org_sucursal_html;
use gamboamartin\system\html_controler;
use PDO;
use stdClass;


class fc_csd_html extends html_controler {

    private function asigna_inputs(controlador_fc_csd $controler, stdClass|array $inputs): array|stdClass
    {
        $controler->inputs->select = new stdClass();
        $controler->inputs->select->org_sucursal_id = $inputs->selects->org_sucursal_id;
        $controler->inputs->serie = $inputs->texts->serie;

        return $controler->inputs;
    }

    private function asigna_inputs_alta(controlador_fc_csd $controler, stdClass|array $inputs): array|stdClass
    {
        $controler->inputs->select = new stdClass();
        $controler->inputs->select->org_sucursal_id = $inputs['selects']->org_sucursal_id;
        $controler->inputs->serie = $inputs['inputs']->serie;

        return $controler->inputs;
    }

    public function genera_inputs_alta(controlador_fc_csd $controler, array $keys_selects, PDO $link): array|stdClass
    {
        $inputs = $this->init_alta2(row_upd: $controler->row_upd, modelo: $controler->modelo,
            keys_selects:  $keys_selects);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar inputs',data:  $inputs);
        }
        $inputs_asignados = $this->asigna_inputs_alta(controler:$controler, inputs: $inputs);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al asignar inputs',data:  $inputs_asignados);
        }

        return $inputs_asignados;
    }

    private function genera_inputs_modifica(controlador_fc_csd $controler, PDO $link): array|stdClass
    {
        $inputs = $this->init_modifica(link: $link, row_upd: $controler->row_upd);
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

    private function init_modifica(PDO $link, stdClass $row_upd): array|stdClass
    {

        $selects = $this->selects_modifica(link: $link, row_upd: $row_upd);
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

    public function inputs_fc_csd(controlador_fc_csd $controlador): array|stdClass
    {
        $inputs = $this->genera_inputs_modifica(controler: $controlador, link: $controlador->link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar inputs',data:  $inputs);
        }
        return $inputs;
    }

    /**
     * Genera un input de tipo serie
     * @param int $cols numero de columnas css1
     * @param stdClass $row_upd registro en proceso
     * @param bool $value_vacio Si vacio deja el input sin value
     * @return array|string
     * @version 0.126.26
     */
    public function input_serie(int $cols, stdClass $row_upd, bool $value_vacio): array|string
    {
        $valida = $this->directivas->valida_cols(cols: $cols);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar columnas', data: $valida);
        }

        $html =$this->directivas->input_text_required(disabled: false,name: 'serie',place_holder: 'Serie',
            row_upd: $row_upd, value_vacio: $value_vacio);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar input', data: $html);
        }

        $div = $this->directivas->html->div_group(cols: $cols,html:  $html);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar div', data: $div);
        }

        return $div;
    }


    private function selects_modifica(PDO $link, stdClass $row_upd): array|stdClass
    {
        $selects = new stdClass();

        $select = (new org_sucursal_html(html:$this->html_base))->select_org_sucursal_id(
            cols: 6, con_registros:true, id_selected:$row_upd->org_sucursal_id,link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select',data:  $select);
        }
        $selects->org_sucursal_id = $select;

        return $selects;
    }

    public function select_fc_csd_id(int $cols, bool $con_registros, int $id_selected, PDO $link,
                                     string $label = 'CSD'): array|string
    {
        $modelo = new fc_csd($link);

        $select = $this->select_catalogo(cols:$cols,con_registros:$con_registros,id_selected:$id_selected,
            modelo: $modelo, label: $label);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select', data: $select);
        }
        return $select;
    }

    protected function texts_alta(stdClass $row_upd, bool $value_vacio, stdClass $params = new stdClass()): array|stdClass
    {
        $texts = new stdClass();

        $in_serie = $this->input_serie(cols: 6,row_upd:  $row_upd,value_vacio:  $value_vacio);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar input',data:  $in_serie);
        }
        $texts->serie = $in_serie;

        return $texts;
    }

}





