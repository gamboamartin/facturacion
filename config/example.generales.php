<?php
namespace config;
class generales{
    public bool $muestra_index = true;
    public string $path_base;
    public string $session_id = '';
    public string $sistema = 'facturacion'; // aqui va el nombre del sistema, se usa para mostrarlo en los templates y en el sistema en general

    public string $url_base = 'http://localhost/facturacion/';// aqui va la url base del sistema, se usa para generar urls absolutas en los templates y en el sistema en general

    public string $adm_usuario_user_init = 'root'; // aqui va el usuario inicial para la tabla adm_usuario, se usa para generar un usuario inicial en caso de que la tabla este vacia, se recomienda cambiarlo por seguridad

    public string $adm_usuario_password_init = 'moro58'; // aqui va la clave del usuario que esta en la tabla adm_usuario
    public array $secciones = array();
    public bool $encripta_md5 = false;
    public bool $aplica_seguridad = true;
    public array $defaults ;
    public int $tipo_sucursal_matriz_id = 1 ; // aqui va el id del tipo de sucursal matriz, se usa para generar la sucursal matriz en caso de que la tabla este vacia, se recomienda cambiarlo por seguridad

    public int $tipo_dispersion = 1; // especifico de konsulta

    public string $ruta_factura_pdf = "/var/www/html/facturacion/plantillas/factura_base.pdf"; // aqui va la ruta del pdf que se usara como plantilla para generar las facturas, se recomienda que sea un pdf con campos editables para facilitar la generacion de las facturas
    public bool $aplica_relacion_layout_factura = false; // especifico de konsulta

    public bool $aplica_relacion_agentes = true; //se requiere siempre que se aplique la relacion de agentes
    public static int $grupo_id_operadores = 4; //se requiere siempre que se aplique la relacion de agentes
    public static int $grupo_id_asesor = 5; //se requiere siempre que se aplique la relacion de agentes
    public static int $tipo_agente_operador = 1; // especifico de konsulta
    //se requiere siempre que se aplique la relacion de agentes
    public static int $tipo_agente_asesor = 2; // especifico de konsulta
    //se requiere siempre que se aplique la relacion de agentes
    public static string $empresa_pagadora_reportes = 'RECURSOS Y RESULTADOS HARIMENI'; // especifico de konsulta
    public static string $key_n8n = 'VHn9JjiujaWPok5yNMb5sYj9';
    public static string $url_base_n8n = 'https://n8n-test.ivitec.mx/webhook-test';
    public static array $codigos_error_datos_constancias = ['CFDI40143','CFDI40147','CFDI40145','CFDI40999'];
    public string $cache_secret_key = 'f9a8c7d6e5b4a360_super_secret_key_ivitec';
       public static int $accion_id_descarga_factura = 10063;
    public static int $accion_id_alta_cliente = 10064;
    public static int $accion_id_alta_factura = 10060;
    public static int $accion_id_alta_partida = 10061;
    public static int $accion_id_editar_cliente = 10062;
    public static int $accion_id_editar_factura = 10065;
    public static int $accion_id_timbrar_factura = 10066;
    public bool $cambios_titulo_pdf = true;
    public bool $leyenda_factura_pdf =true;
    public static bool $datos_adicionales_com_cliente = true; 
    // public static bool $login_ivitec_v2 = false;
    // public static bool $com_agente_v2 = false;
    // public static string $tipo_menu = 'horizontal'; // 'horizontal' | 'vertical'

    public function __construct(){
        $this->path_base = '/var/www/html/facturacion/'; // aqui va la ruta base del sistema, se usa para generar rutas absolutas en los templates y en el sistema en general

        if(isset($_GET['session_id'])){
            $this->session_id = $_GET['session_id'];
        }
        $this->defaults['dp_pais']['id'] = 0; // aqui va el id del pais por default
        $this->defaults['dp_estado']['id'] = 0; // aqui va el id del estado por default
        $this->defaults['dp_municipio']['id'] = 0; // aqui va el id del municipio por default
    }
}
