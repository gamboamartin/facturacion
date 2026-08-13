<?php
namespace gamboamartin\facturacion\models;
use PDO;
use gamboamartin\errores\errores;
use config\generales;
use stdClass;

class com_cliente extends \gamboamartin\comercial\models\com_cliente {

     public function __construct(PDO $link) {
        parent::__construct(link: $link);

       if (property_exists(generales::class, 'datos_adicionales_com_cliente') && generales::$datos_adicionales_com_cliente) {
            $this->columnas_extra['com_cliente_nombre_emergencia'] =
                "IFNULL((SELECT nombre_emergencia FROM datos_adicionales_com_cliente_artistik 
                WHERE datos_adicionales_com_cliente_artistik.com_cliente_id = com_cliente.id), '')";

            $this->columnas_extra['com_cliente_curp'] =
                "IFNULL((SELECT curp FROM datos_adicionales_com_cliente_artistik 
                WHERE datos_adicionales_com_cliente_artistik.com_cliente_id = com_cliente.id), '')";

            $this->columnas_extra['com_cliente_foto'] =
                "IFNULL((SELECT CASE WHEN foto IS NOT NULL AND foto != '' 
                THEN CONCAT('<img src=\"', foto, '\" style=\"width:50px;height:50px;object-fit:cover;border-radius:4px;\" />') 
                ELSE '' END 
                FROM datos_adicionales_com_cliente_artistik 
                WHERE datos_adicionales_com_cliente_artistik.com_cliente_id = com_cliente.id), '')";
        }
    }

    public function elimina_bd(int $id): array|stdClass
    {
        $filtro[$this->key_filtro_id] = $id;
        $del = (new fc_receptor_email(link: $this->link))->elimina_con_filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al eliminar receptores',data:  $del);
        }

        $del = parent::elimina_bd(id: $id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al eliminar ',data:  $del);
        }
        return $del;
    }
}