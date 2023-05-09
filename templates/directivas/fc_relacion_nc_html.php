<?php
namespace gamboamartin\facturacion\html;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\fc_relacion_nc;
use PDO;
class fc_relacion_nc_html extends _relacion_html {
    public function select_fc_relacion_nc_id(int $cols, bool $con_registros, int $id_selected, PDO $link,
                                              bool $required = false): array|string
    {
        $modelo = new fc_relacion_nc($link);

        $select = $this->select_catalogo(cols:$cols,con_registros:$con_registros,id_selected:$id_selected,
            modelo: $modelo, label: "Nota Credito",required: $required);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select', data: $select);
        }
        return $select;
    }
}




