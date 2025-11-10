<?php
namespace gamboamartin\facturacion\models;
use base\orm\modelo;
use gamboamartin\errores\errores;
use gamboamartin\validacion\validacion;
use stdClass;
use PDO;


class fc_empleado_contacto extends modelo{
    public function __construct(PDO $link){
        $tabla = 'fc_empleado_contacto';
        $columnas = array( $tabla => false, 'com_tipo_contacto' => $tabla, 'fc_empleado' => $tabla );
        $campos_view = array();
        $campos_obligatorios = array();
        $no_duplicados = array();


        parent::__construct(link: $link,tabla:  $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas, campos_view: $campos_view, no_duplicados: $no_duplicados);

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'Empleado Contacto';
    }

    public function alta_bd(): array|stdClass
    {
        $validacion = $this->validacion(datos: $this->registro, registro_id: -1);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error de validacion', data: $validacion);
        }

        $this->registro = $this->inicializa_campos($this->registro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al inicializar campo base', data: $this->registro);
        }

        $r_alta_bd = parent::alta_bd();
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al dar de alta fc_empleado_contacto', data: $r_alta_bd);
        }

        return $r_alta_bd;
    }


    protected function inicializa_campos(array $registros): array
    {
        $registros['codigo'] = $this->get_codigo_aleatorio();
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error generar codigo', data: $registros);
        }

        $registros['descripcion'] = $registros['nombre'] . ' ' . $registros['ap'];

        if (array_key_exists('am', $registros)) {
            $registros['descripcion'] .= ' ' . $registros['am'];
        }

        return $registros;
    }

    public function validacion(array $datos, int $registro_id): array
    {
        if (array_key_exists('status', $datos)) {
            return $datos;
        }

        $validacion = (new validacion())->valida_correo(correo: $datos['correo']);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error de validacion', data: $validacion);
        }

        $validacion =(new validacion())->valida_numero_tel_mx(tel: $datos['telefono']);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error de validacion', data: $validacion);
        }

        $validacion =(new validacion())->valida_solo_texto(texto: $datos['nombre']);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error de validacion', data: $validacion);
        }

        $validacion =(new validacion())->valida_solo_texto(texto: $datos['ap']);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error de validacion', data: $validacion);
        }

        if (strlen($datos['nombre']) < 3) {
            $mensaje_error = sprintf("Error el campo nombre '%s' debe tener como minimo 3 caracteres",
                $datos['nombre']);
            return $this->error->error(mensaje: $mensaje_error, data: $datos);
        }

        if (strlen($datos['ap']) < 3) {
            $mensaje_error = sprintf("Error el campo apellido paterno '%s' debe tener como minimo 3 caracteres",
                $datos['ap']);
            return $this->error->error(mensaje: $mensaje_error, data: $datos);
        }

        return $datos;
    }

}