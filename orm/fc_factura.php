<?php
namespace models;
use base\orm\modelo;
use gamboamartin\errores\errores;
use gamboamartin\organigrama\controllers\controlador_org_empresa;
use models\base\limpieza;
use PDO;
use stdClass;

class fc_factura extends modelo{
    public function __construct(PDO $link){
        $tabla = __CLASS__;
        $columnas = array($tabla=>false,'fc_cfd'=>$tabla, 'cat_sat_forma_pago'=>$tabla,'cat_sat_metodo_pago'=>$tabla,
            'cat_sat_moneda'=>$tabla, 'com_tipo_cambio'=>$tabla, 'cat_sat_uso_cfdi'=>$tabla,
            'cat_sat_tipo_de_comprobante'=>$tabla, 'dp_calle_pertenece'=>$tabla, 'cat_sat_regimen_fiscal'=>$tabla,
            'com_sucursal'=>$tabla);
        $campos_obligatorios = array('folio', 'fc_cfd_id','cat_sat_forma_pago_id','cat_sat_metodo_pago_id',
            'cat_sat_moneda_id', 'com_tipo_cambio_id', 'cat_sat_uso_cfdi_id', 'cat_sat_tipo_de_comprobante_id',
            'dp_calle_pertenece_id', 'cat_sat_regimen_fiscal_id', 'com_sucursal_id');

        $no_duplicados = array('codigo','descripcion_select','alias','codigo_bis','serie');

        parent::__construct(link: $link,tabla:  $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas,no_duplicados: $no_duplicados,tipo_campos: array());
    }

    public function alta_bd(): array|stdClass
    {
        $registro_cfd = (new fc_cfd($this->link))->registro($this->registro['fc_cfd_id']);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener fc cfd',data: $registro_cfd);
        }

        $registro_org_sucursal = (new org_sucursal($this->link))->registro(1);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener sucursal',data: $registro_org_sucursal);
        }

        $registro_com_sucursal = (new com_sucursal($this->link))->registro($this->registro['com_sucursal_id']);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener sucursal',data: $registro_com_sucursal);
        }

        $this->registro['codigo'] = $this->registro['serie'].' ' .$this->registro['folio'];
        $this->registro['codigo_bis'] = $this->registro['serie'].' ' .$this->registro['folio'];
        $this->registro['fecha'] = date('Y-m-d h:i:s');
        $this->registro['descripcion_select'] = $registro_org_sucursal['org_empresa_razon_social'].' '.
            $registro_com_sucursal['com_cliente_razon_social'];
        $this->registro['alias'] = $this->registro['descripcion_select'];

        $r_alta_bd =  parent::alta_bd(); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al dar de alta accion',data: $r_alta_bd);
        }

        return $r_alta_bd;
    }
}