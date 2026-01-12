<?php
namespace gamboamartin\facturacion\models;
use base\orm\modelo;
use config\generales;
use DateTime;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\controllers\controlador_fc_empleado_contacto;
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

    public function valida_tiempo_tokens(): void
    {
        $filtro = [
          'fc_empleado_contacto.estatus_correo' => controlador_fc_empleado_contacto::STATUS_LINK_ENVIADO,
        ];

        $columnas = [
            'fc_empleado_contacto_id',
            'fc_empleado_contacto_fecha_token_validacion',
        ];
        $response = $this->filtro_and(columnas: $columnas, filtro: $filtro);
        if (errores::$error) {
            exit;
        }

        foreach ($response->registros as $registro) {
            $fecha_token = $registro['fc_empleado_contacto_fecha_token_validacion'];
            $registro_id = $registro['fc_empleado_contacto_id'];

            $fecha_actual = new DateTime();       // fecha y hora actual

            try {
                $fecha_token_dt = new DateTime($fecha_token);
                $diferencia = $fecha_actual->diff($fecha_token_dt);
                if ($diferencia->days > 2 ) {
                    $row_update = [
                        'estatus_correo' => (controlador_fc_empleado_contacto::STATUS_NO_VALIDADO),
                        'token_validacion' => '',
                    ];
                    $rs = $this->modifica_bd(
                        registro: $row_update,
                        id: $registro_id
                    );
                    if (errores::$error) {
                        exit;
                    }
                }
            } catch (\DateMalformedStringException $e) {

            }
        }
    }

    public function actualiza_estado_correo(int $registro_id, string $estado)
    {
        $rs = $this->modifica_bd(
            registro: ['estatus_correo' => $estado ],
            id: $registro_id
        );
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al actualizar el estatus_correo', data: $rs);
        }

        return [];
    }

    public function genera_link_validacion(string $correo, int $registro_id)
    {
        $token = $this->get_codigo_aleatorio(longitud: 16);
        $fecha_token_validacion = date('Y-m-d H:i:s');
        $url_validacion = (new generales())->url_base;
        $url_validacion .= "valida_correo.php?correo={$correo}&token={$token}";

        $rs = $this->modifica_bd(
            registro: ['token_validacion' => $token, 'fecha_token_validacion' => $fecha_token_validacion],
            id: $registro_id
        );
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al actualizar el token de validacion', data: $rs);
        }

        return $url_validacion;
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

    public function alta_registro(array $registro): array|stdClass
    {
        if ($registro['email'] === ''){
            return [];
        }

        $email = trim($registro['email']);
        $email = preg_replace('/[^\S\r\n]+/u', '', $email); // elimina todos los tipos de espacios
        $email = strtolower($email);
        $nombre_completo = strtolower($registro['nombre_completo']);
        $nombre_completo = str_replace('  ', ' ', $nombre_completo);
        $nombre_completo = str_replace('   ', ' ', $nombre_completo);
        $nombre_completo = str_replace('    ', ' ', $nombre_completo);

        $nombre_separado = $this->separarNombreCompleto($nombre_completo);

        $registro_alta['codigo'] = $this->get_codigo_aleatorio();
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error generar codigo', data: $registro_alta);
        }

        if (mb_strlen($nombre_separado['apellido_paterno'], 'UTF-8') <= 3) {
            $nombre_separado['apellido_paterno'] = "Sin Apellido";
        }

        if (mb_strlen($nombre_separado['apellido_materno'], 'UTF-8') <= 3) {
            $nombre_separado['apellido_materno'] = "Sin Apellido";
        }

        if (mb_strlen($nombre_separado['nombres'], 'UTF-8') <= 3) {
            $nombre_separado['nombres'] = "Sin nombres";
        }

        $registro_alta['nombre'] = $nombre_separado['nombres'];
        $registro_alta['ap'] = $nombre_separado['apellido_paterno'];
        $registro_alta['am'] = $nombre_separado['apellido_materno'];
        $registro_alta['correo'] = $email;
        $registro_alta['telefono'] = '1000000000';
        $registro_alta['descripcion'] = $nombre_completo;
        $registro_alta['com_tipo_contacto_id'] = -1;
        $registro_alta['fc_empleado_id'] = $registro['fc_empleado_id'];

        $rs = $this->filtro_and(filtro: ['fc_empleado_contacto.correo' => $email]);
        if (errores::$error) {
            return $this->error->error(mensaje: "Error al buscar correo:{$email} en fc_empleado_contacto", data: $rs);
        }

        if ((int)$rs->n_registros !== 0){
            return [];
        }

        $r_alta_registro = parent::alta_registro($registro_alta);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al dar de alta fc_empleado_contacto', data: $r_alta_registro);
        }

        return $r_alta_registro;
    }

    public function modifica_bd(array $registro, int $id, bool $reactiva = false): array|stdClass
    {
        $registro['descripcion'] = $registro['nombre'] . ' ' . $registro['ap'];

        if (array_key_exists('am', $registro)) {
            $registro['descripcion'] .= ' ' . $registro['am'];
        }

        $r_modifica_bd = parent::modifica_bd($registro, $id, $reactiva);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al dar de modifica_bd fc_empleado_contacto', data: $r_modifica_bd);
        }

        return $r_modifica_bd;
    }

    public function envia_nomina_fc_empleado_contacto(int $fc_empleado_id, array $adjuntos = [])
    {
        $filtro = [
            'fc_empleado_contacto.fc_empleado_id' => $fc_empleado_id,
        ];

        $rs = $this->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener registro del empleado contacto', data: $rs);
        }

        $n_registros = $rs->n_registros;

        if ((int)$n_registros !== 1) {
            return [];
        }

        $estatus_correo = $rs->registros[0]['fc_empleado_contacto_estatus_correo'];
        $correo = $rs->registros[0]['fc_empleado_contacto_correo'];

        if ($estatus_correo !== controlador_fc_empleado_contacto::STATUS_VALIDADO) {
            return [];
        }

        $rs_mail = (new _email_nomina())->enviar_nomina(
            correo: $correo,
            adjuntos: $adjuntos,
            link: $this->link
        );
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al enviar _email_nomina', data: $rs_mail);
        }

        return $rs_mail;

    }

    public function tiene_correo_validado(int $fc_empleado_id)
    {
        $filtro = [
            'fc_empleado_contacto.fc_empleado_id' => $fc_empleado_id,
        ];

        $rs = $this->filtro_and(columnas: ['fc_empleado_contacto_estatus_correo'], filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener registro del empleado contacto', data: $rs);
        }

        $n_registros = $rs->n_registros;

        if ((int)$n_registros !== 1) {
            return false;
        }

        $estatus_correo = $rs->registros[0]['fc_empleado_contacto_estatus_correo'];

        if ($estatus_correo !== controlador_fc_empleado_contacto::STATUS_VALIDADO) {
            return false;
        }

        return true;
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

    private function separarNombreCompleto($nombreCompleto): array
    {
        // Limpiar dobles espacios y convertir a mayúsculas o minúsculas si se desea
        $nombreCompleto = trim(preg_replace('/\s+/', ' ', $nombreCompleto));

        // 2. Convertir a minúsculas
        $nombreCompleto = mb_strtolower($nombreCompleto, 'UTF-8');

        // Convertir a array de palabras
        $partes = explode(' ', $nombreCompleto);
        $total = count($partes);

        // Validación rápida
        if ($total < 2) {
            return [
                'nombres' => $nombreCompleto,
                'apellido_paterno' => '',
                'apellido_materno' => ''
            ];
        }

        // Listas comunes para identificar nombres compuestos
        $prefijosNombres = ['de', 'del', 'de la', 'de los', 'la', 'las', 'los'];
        $prefijosApellidos = ['del', 'de', 'de la', 'de las', 'de los'];

        // AP MATERNO = última palabra(s)
        $apellido_materno = array_pop($partes);

        // AP PATERNO = palabra(s) anteriores
        $apellido_paterno = array_pop($partes);

        // Si el apellido paterno trae prefijos como "de", "del", etc.
        if (in_array(strtolower($apellido_paterno), $prefijosApellidos) && count($partes) > 0) {
            $apellido_paterno = $apellido_paterno . ' ' . array_pop($partes);
        }

        // Nombres = todo lo que quedó
        $nombres = implode(' ', $partes);

        return [
            'nombres' => $this->capitalizar($nombres),
            'apellido_paterno' => $this->capitalizar($apellido_paterno),
            'apellido_materno' => $this->capitalizar($apellido_materno)
        ];
    }

    private function capitalizar($texto): array|false|string|null
    {
        return mb_convert_case($texto, MB_CASE_TITLE, "UTF-8");
    }


}