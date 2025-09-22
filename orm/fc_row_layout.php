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