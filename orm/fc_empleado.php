<?php
namespace gamboamartin\facturacion\models;
use base\orm\modelo;
use gamboamartin\errores\errores;
use PDO;


class fc_empleado extends modelo{
    public function __construct(PDO $link){
        $tabla = 'fc_empleado';
        $columnas = array($tabla=>false);

        $campos_view = array();
        $campos_obligatorios = array();
        $no_duplicados = array();


        parent::__construct(link: $link,tabla:  $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas, campos_view: $campos_view, no_duplicados: $no_duplicados);

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'Empleados';
    }

    /**
     * Sanitiza campos eliminando espacios y guiones
     */
    private function sanitizar_campos(array &$registro): void
    {
        $campos_a_sanitizar = ['rfc', 'nss', 'clabe', 'cuenta', 'nombre_completo'];
        
        foreach ($campos_a_sanitizar as $campo) {
            if (isset($registro[$campo]) && is_string($registro[$campo])) {
                if ($campo === 'nombre_completo') {
                    // Para nombre_completo: solo eliminar espacios al inicio y final
                    $registro[$campo] = trim($registro[$campo]);
                } else {
                    // Para otros campos: eliminar espacios y guiones
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
        // Sanitizar campos antes de procesar
        $this->sanitizar_campos($registro);
        if(!isset($registro['codigo'])){
            $registro['codigo'] = trim($registro['rfc'].$registro['curp']);
        }
        if(!isset($registro['descripcion'])){
            $registro['descripcion'] = trim($registro['codigo'].$registro['nombre_completo']);
        }
        if(!isset($registro['codigo_bis'])){
            $registro['codigo_bis'] = trim($registro['descripcion']);
        }
        $r_alta = parent::alta_registro(registro: $registro);
        if(errores::$error){
            return (new errores())->error('Error al insertar', $r_alta);
        }
        return $r_alta;
    }

    public function modifica_bd(array $registro, int $id, bool $reactiva = false): array|\stdClass
    {
        // Sanitizar campos antes de actualizar
        $this->sanitizar_campos($registro);
        
        $r_modifica = parent::modifica_bd($registro, $id, $reactiva);
        if(errores::$error){
            return (new errores())->error('Error al modificar empleado', $r_modifica);
        }
        return $r_modifica;
    }

}