<?php
namespace gamboamartin\facturacion\models;
use base\orm\modelo;
use gamboamartin\errores\errores;
use gamboamartin\organigrama\models\org_sucursal;
use PDO;
use stdClass;


class fc_csd extends modelo{
    public function __construct(PDO $link){
        $tabla = 'fc_csd';
        $columnas = array($tabla=>false,'org_sucursal'=>$tabla,'org_empresa'=>'org_sucursal',
            'dp_calle_pertenece'=>'org_sucursal','cat_sat_regimen_fiscal'=>'org_empresa');
        $campos_obligatorios = array('codigo','serie','org_sucursal_id','descripcion_select','alias','codigo_bis');

        $no_duplicados = array('codigo','descripcion_select','alias','codigo_bis','serie');

        $campos_view['org_sucursal_id'] = array('type' => 'selects', 'model' => new org_sucursal($link));
        $campos_view['codigo'] = array('type' => 'inputs');
        $campos_view['codigo_bis'] = array('type' => 'inputs');
        $campos_view['serie'] = array('type' => 'inputs');

        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas, campos_view: $campos_view, no_duplicados: $no_duplicados, tipo_campos: array());

        $this->NAMESPACE = __NAMESPACE__;
    }

    public function alta_bd(): array|stdClass
    {
        $this->registro = $this->inicializa_campos_base(data: $this->registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al inicializar campos base',data: $this->registro);
        }

        $r_alta_bd = parent::alta_bd();
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al dar de alta csd',data: $r_alta_bd);
        }

        return $r_alta_bd;
    }

    public function get_csd(int $fc_csd_id): array|stdClass|int
    {
        $registro = $this->registro(registro_id: $fc_csd_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener CSD',data:  $registro);
        }

        return $registro;
    }

    public function modifica_bd(array $registro, int $id, bool $reactiva = false): array|stdClass
    {
        $this->registro = $this->inicializa_campos_base(data: $registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al inicializar campos base',data: $this->registro);
        }

        $r_modifica_bd = parent::modifica_bd($registro, $id, $reactiva);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al modificar csd',data: $r_modifica_bd);
        }

        return $r_modifica_bd;
    }

    private function inicializa_campos_base(array $data): array
    {
        $sucursal = $this->registro_por_id(entidad: new org_sucursal($this->link),id: $data['org_sucursal_id']);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener la sucursal',data: $sucursal);
        }

        if(!isset($data['codigo_bis'])){
            $data['codigo_bis'] = $data['codigo'];
        }

        if(!isset($data['descripcion'])){
            $data['descripcion'] = $data['codigo'];
        }

        if(!isset($data['descripcion_select'])){
            $data['descripcion_select'] = $data['codigo'];
            $data['descripcion_select'] .= " - ";
            $data['descripcion_select'] .= $sucursal->org_empresa_descripcion;
        }

        if(!isset($data['alias'])){
            $data['alias'] = $data['codigo'];
        }

        return $data;
    }

}