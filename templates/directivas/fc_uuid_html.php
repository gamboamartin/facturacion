<?php
namespace gamboamartin\facturacion\html;

use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\fc_uuid;
use gamboamartin\system\html_controler;
use PDO;
use stdClass;

class fc_uuid_html extends html_controler {

    public function input_folio(int $cols, stdClass $row_upd, bool $value_vacio, bool $disabled = false): array|string
    {
        $valida = $this->directivas->valida_cols(cols: $cols);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar columnas', data: $valida);
        }

        $html =$this->directivas->input_text_required(disabled: $disabled,name: 'folio',place_holder: 'Folio',
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

    public function input_total(int $cols, stdClass $row_upd, bool $value_vacio, bool $disabled = false): array|string
    {
        $valida = $this->directivas->valida_cols(cols: $cols);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar columnas', data: $valida);
        }

        $html =$this->directivas->input_text_required(disabled: $disabled,name: 'total',place_holder: 'Total',
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
    public function input_uuid(int $cols, stdClass $row_upd, bool $value_vacio, bool $disabled = false): array|string
    {
        $valida = $this->directivas->valida_cols(cols: $cols);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar columnas', data: $valida);
        }

        $html =$this->directivas->input_text_required(disabled: $disabled,name: 'uuid',place_holder: 'UUID',
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

    public function select_fc_uuid_id(int $cols, bool $con_registros, int $id_selected, PDO $link,
                                         array $columns_ds = array('fc_uuid_uuid','com_cliente_rfc','fc_uuid_folio', 'fc_uuid_total'),
                                         bool $disabled = false, array $filtro = array(),
                                         array $registros = array()): array|string
    {
        $modelo = new fc_uuid(link: $link);

        $select = $this->select_catalogo(cols: $cols, con_registros: $con_registros, id_selected: $id_selected,
            modelo: $modelo, columns_ds: $columns_ds, disabled: $disabled, filtro: $filtro, label: 'Factura Externa',
            registros: $registros, required: true);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select', data: $select);
        }
        return $select;
    }
}
