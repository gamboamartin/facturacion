<?php
namespace models;
use base\orm\modelo;
use gamboamartin\errores\errores;
use gamboamartin\organigrama\controllers\controlador_org_empresa;
use models\base\limpieza;
use PDO;
use stdClass;

class fc_cfd_partida extends modelo{
    public function __construct(PDO $link){
        $tabla = __CLASS__;
        $columnas = array($tabla=>false,'fc_factura'=>$tabla);
        $campos_obligatorios = array('codigo','com_producto_id');

        $no_duplicados = array('codigo','descripcion_select','alias','codigo_bis');

        parent::__construct(link: $link,tabla:  $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas,no_duplicados: $no_duplicados,tipo_campos: array());
    }

    public function calculo_sub_total_partida(int $fc_cfd_partida_id): float| array
    {
        $data = $this->registro(registro_id: $fc_cfd_partida_id, retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener los registros', data: $data);
        }
        return $data->fc_cfd_partida_cantidad * $data->fc_cfd_partida_valor_unitario;
    }

    public function partidas(int $fc_factura_id): array|stdClass
    {
        if($fc_factura_id <=0){
            return $this->error->error(mensaje: 'Error $fc_factura_id debe ser mayor a 0', data: $fc_factura_id);
        }
        $filtro['fc_factura.id'] = $fc_factura_id;
        $r_fc_cfd_partida = $this->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener partidas', data: $r_fc_cfd_partida);
        }
        return $r_fc_cfd_partida;
    }

}