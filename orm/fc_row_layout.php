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
     * Método público para eliminar con manejo de transacción
     * Se usa cuando se llama directamente desde el controlador
     */
    public function elimina_bd(int $id): array|\stdClass
    {

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

        $r_elimina_bd = parent::elimina_bd($id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al eliminar fc_row_layout',data: $r_elimina_bd);
        }

        return $r_elimina_bd;
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

    public function recibo_cancelado(int $id): array|bool
    {
        $registro = $this->registro(registro_id: $id, retorno_obj: true);
        if(errores::$error){
            return (new errores())->error('Error al obtener informacion del registro', $registro);
        }
        if ($registro->fc_row_layout_esta_cancelado  === 'activo') {
            return true;
        }

        return false;
    }

    public function envia_nomina(int $fc_row_layout_id)
    {
        $this->registro_id = $fc_row_layout_id;
        $rs = $this->obten_data();
        if(errores::$error){
            return (new errores())->error('Error al obten_data de fc_row_layout ', $rs);
        }

        $fc_empleado_id = $rs['fc_row_layout_fc_empleado_id'];

        $fc_empleado_contacto_modelo = new fc_empleado_contacto($this->link);

        $tiene_correo_valido = $fc_empleado_contacto_modelo->tiene_correo_validado(
            fc_empleado_id: $fc_empleado_id
        );
        if(errores::$error){
            return (new errores())->error('Error al validar correo', $tiene_correo_valido);
        }

        if (!$tiene_correo_valido) {
            return [];
        }

        $result = (new fc_row_nomina($this->link))->filtro_and(
            filtro: ['fc_row_nomina.fc_row_layout_id' => $fc_row_layout_id],
        );
        if(errores::$error){
            return (new errores())->error('Error al obtener registro fc_row_nomina', $result);
        }

        $n_registros = $result->n_registros;
        if ((int)$n_registros === 0){
            return [];
        }

        $adjuntos = [];

        foreach ($result->registros as $registro) {
            $extension = pathinfo($registro['doc_documento_nombre'], PATHINFO_EXTENSION);
            $not_adjunto_name_out = $registro['fc_row_layout_uuid'] . '.' . $extension;

            $adjuntos[] = [
                'doc_documento_ruta_absoluta' => $registro['doc_documento_ruta_absoluta'],
                'not_adjunto_name_out' => $not_adjunto_name_out,
            ];
        }

        $rs_envia_nomina = $fc_empleado_contacto_modelo->envia_nomina_fc_empleado_contacto(
            fc_empleado_id: $fc_empleado_id,
            adjuntos:  $adjuntos,
        );
        if(errores::$error){
            return (new errores())->error('Error al envia_nomina_fc_empleado_contacto', $rs_envia_nomina);
        }

        return $rs_envia_nomina;
    }

}