<?php
namespace gamboamartin\facturacion\html;
use gamboamartin\errores\errores;
use gamboamartin\system\html_controler;
use gamboamartin\facturacion\models\fc_partida_nc;
use PDO;
class fc_partida_nc_html extends html_controler {
    public function select_fc_partida_nc_id(int $cols, bool $con_registros, int $id_selected, PDO $link,
                                              bool $required = false): array|string
    {
        $modelo = new fc_partida_nc($link);

        $select = $this->select_catalogo(cols:$cols,con_registros:$con_registros,id_selected:$id_selected,
            modelo: $modelo, label: "Partida Nota Credito",required: $required);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select', data: $select);
        }
        return $select;
    }
}




