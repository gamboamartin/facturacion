<?php
namespace gamboamartin\facturacion\models;
use base\orm\modelo;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\controllers\_xls_empleados;
use PDO;


class fc_row_layout extends modelo{
    public function __construct(PDO $link){
        $tabla = 'fc_row_layout';
        $columnas = array($tabla=>false,'fc_layout_nom'=>$tabla);
        $campos_obligatorios = array();
        $campos_view = array();
        $no_duplicados = array();

        parent::__construct(link: $link,tabla:  $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas, campos_view: $campos_view, no_duplicados: $no_duplicados,tipo_campos: array());

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'Rows';
    }

    /**
     * Sanitiza campos eliminando espacios, guiones y caracteres especiales
     */
    private function sanitizar_campos(array &$registro): void
    {
        $campos_a_sanitizar = ['rfc', 'nss', 'clabe', 'cuenta', 'nombre_completo'];
        
        foreach ($campos_a_sanitizar as $campo) {
            if (isset($registro[$campo]) && is_string($registro[$campo])) {
                if ($campo === 'nombre_completo') {
                    // Para nombre_completo: solo eliminar espacios al inicio y final
                    $registro[$campo] = trim($registro[$campo]);
                } elseif ($campo === 'clabe' || $campo === 'cuenta') {
                    // Para clabe y cuenta: solo números (eliminar TODOS los caracteres no numéricos)
                    $registro[$campo] = preg_replace('/[^0-9]/', '', $registro[$campo]);
                } else {
                    // Para RFC y NSS: eliminar espacios y guiones
                    $registro[$campo] = str_replace([' ', '-'], '', $registro[$campo]);
                    // Convertir a mayúsculas para RFC
                    if ($campo === 'rfc') {
                        $registro[$campo] = strtoupper($registro[$campo]);
                    }
                }
            }
        }
    }

    public function alta_registro(array $registro): array|\stdClass
    {
        $fc_empleado_modelo = new fc_empleado($this->link);
        $fc_empleado = $fc_empleado_modelo->registro($registro['fc_empleado_id']);
        if(errores::$error){
            return (new errores())->error('Error al obtener empleado', $fc_empleado);
        }

        if($fc_empleado['fc_empleado_validado_sat'] === 'activo') {
            $registro['cp'] = $fc_empleado['fc_empleado_cp'];
            $registro['nss'] = $fc_empleado['fc_empleado_nss'];
            $registro['rfc'] = $fc_empleado['fc_empleado_rfc'];
            $registro['curp'] = $fc_empleado['fc_empleado_curp'];
            $registro['nombre_completo'] = $fc_empleado['fc_empleado_nombre_completo'];
        }

        // Sanitizar campos antes de procesar
        $this->sanitizar_campos($registro);

        if(!isset($registro['codigo'])){
            $registro['codigo'] = $registro['fc_empleado_id'];
            $registro['codigo'] .= $registro['fc_layout_nom_id'];
            $registro['codigo'] .= $registro['nss'];
            $registro['codigo'] .= $registro['rfc'];
            $registro['codigo'] .= $registro['curp'];
        }
        if(!isset($registro['codigo_bis'])){
            $registro['codigo_bis'] = $registro['codigo'];
        }
        if(!isset($registro['descripcion'])){
            $registro['descripcion'] = $registro['fc_empleado_id'];
            $registro['descripcion'] .= ' '.$registro['fc_layout_nom_id'];
            $registro['descripcion'] .= ' '.$registro['nss'];
            $registro['descripcion'] .= ' '.$registro['rfc'];
            $registro['descripcion'] .= ' '.$registro['curp'];
        }

        $r_alta = parent::alta_registro($registro);
        if(errores::$error){
            return (new errores())->error('Error al insertar row', $r_alta);
        }
        return $r_alta;
    }

    /**
     * Método privado para eliminar sin manejo de transacción
     * Se usa cuando se llama desde fc_layout_nom que ya maneja la transacción
     */
    private function elimina_con_hijos(int $id): array|\stdClass
    {
        // 1. Validar que el registro no esté timbrado
        $registro = $this->registro(registro_id: $id, retorno_obj: true);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener registro fc_row_layout',data: $registro);
        }

        if($registro->fc_row_layout_esta_timbrado === 'activo'){
            return $this->error->error(
                mensaje: 'No se puede eliminar el registro porque está timbrado. Los documentos timbrados no pueden eliminarse',
                data: array('fc_row_layout_id' => $id, 'esta_timbrado' => 'activo')
            );
        }

        // 2. Eliminar fc_cfdi_sellado_nomina relacionados
        $fc_cfdi_sellado_modelo = new fc_cfdi_sellado_nomina(link: $this->link);
        $filtro_cfdi = array();
        $filtro_cfdi['fc_cfdi_sellado_nomina.fc_row_layout_id'] = $id;

        $result_cfdi = $fc_cfdi_sellado_modelo->filtro_and(filtro: $filtro_cfdi);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener fc_cfdi_sellado_nomina',data: $result_cfdi);
        }

        foreach ($result_cfdi->registros as $registro_cfdi) {
            $rs_del = $fc_cfdi_sellado_modelo->elimina_bd($registro_cfdi['fc_cfdi_sellado_nomina_id']);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al eliminar fc_cfdi_sellado_nomina',data: $rs_del);
            }
        }

        // 3. Eliminar fc_row_nomina relacionados
        $fc_row_nomina_modelo = new fc_row_nomina(link: $this->link);
        $filtro_nomina = array();
        $filtro_nomina['fc_row_nomina.fc_row_layout_id'] = $id;

        $result_nomina = $fc_row_nomina_modelo->filtro_and(filtro: $filtro_nomina);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener fc_row_nomina',data: $result_nomina);
        }

        foreach ($result_nomina->registros as $registro_nomina) {
            $rs_del = $fc_row_nomina_modelo->elimina_bd($registro_nomina['fc_row_nomina_id']);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al eliminar fc_row_nomina',data: $rs_del);
            }
        }

        // 4. Eliminar el fc_row_layout
        $r_elimina_bd = parent::elimina_bd($id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al eliminar fc_row_layout',data: $r_elimina_bd);
        }

        return $r_elimina_bd;
    }

    /**
     * Método público para eliminar con manejo de transacción
     * Se usa cuando se llama directamente desde el controlador
     */
    public function elimina_bd(int $id): array|\stdClass
    {
        // Verificar si ya hay una transacción activa
        $transaccion_iniciada = false;
        if(!$this->link->inTransaction()){
            $this->link->beginTransaction();
            $transaccion_iniciada = true;
        }

        $resultado = $this->elimina_con_hijos($id);
        
        if(errores::$error){
            if($transaccion_iniciada){
                $this->link->rollBack();
            }
            return $resultado;
        }

        if($transaccion_iniciada){
            $this->link->commit();
        }
        return $resultado;
    }

    public function modifica_bd(array $registro, int $id, bool $reactiva = false): array|\stdClass
    {
        // Sanitizar campos antes de actualizar
        $this->sanitizar_campos($registro);
        
        $r_modifica = parent::modifica_bd($registro, $id, $reactiva);
        if(errores::$error){
            return (new errores())->error('Error al modificar row', $r_modifica);
        }
        return $r_modifica;
    }

}