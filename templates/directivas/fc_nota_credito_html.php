<?php
namespace gamboamartin\facturacion\html;
use gamboamartin\errores\errores;
use gamboamartin\system\html_controler;
use gamboamartin\facturacion\models\fc_nota_credito;
use PDO;
class fc_nota_credito_html extends html_controler {
    public function select_fc_nota_credito_id(int $cols, bool $con_registros, int $id_selected, PDO $link,
                                              bool $required = false): array|string
    {
        $modelo = new fc_nota_credito($link);

        $select = $this->select_catalogo(cols:$cols,con_registros:$con_registros,id_selected:$id_selected,
            modelo: $modelo, label: "Nota Credito",required: $required);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select', data: $select);
        }
        return $select;
    }
}




