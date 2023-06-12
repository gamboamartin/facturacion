<?php
namespace gamboamartin\facturacion\controllers;

use gamboamartin\comercial\models\com_tmp_prod_cs;
use gamboamartin\errores\errores;
use PDO;

class _tmps{

    private errores $error;

    public function __construct(){
        $this->error = new errores();
    }

    /**
     * Verifica si existe un producto de tipo temporal
     * @param PDO $link Conexion a la base de datos
     * @param array $partida Partida en proceso
     * @return array
     */
    final public function com_tmp_prod_cs(PDO $link, array $partida): array
    {
        $filtro['com_producto.id'] = $partida['com_producto_id'];
        $existe_tmp = (new com_tmp_prod_cs(link: $link))->existe(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar si existe existe_tmp', data: $existe_tmp);
        }
        if($existe_tmp){
            $r_com_tmp_prod_cs = (new com_tmp_prod_cs(link: $link))->filtro_and(filtro: $filtro);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al obtener producto', data: $r_com_tmp_prod_cs);
            }
            $partida['cat_sat_producto_codigo'] = $r_com_tmp_prod_cs->registros[0]['com_tmp_prod_cs_cat_sat_producto'];
        }
        return $partida;
    }

}
