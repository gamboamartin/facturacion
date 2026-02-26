<?php
namespace config;
class generales{
    public bool $muestra_index = true;
    public string $path_base;
    public string $session_id = '';
    public string $sistema = ''; // aqui va el nombre del sistema, se usa para mostrarlo en los templates y en el sistema en general

    public string $url_base = '';// aqui va la url base del sistema, se usa para generar urls absolutas en los templates y en el sistema en general

    public string $adm_usuario_user_init = ''; // aqui va el usuario inicial para la tabla adm_usuario, se usa para generar un usuario inicial en caso de que la tabla este vacia, se recomienda cambiarlo por seguridad

    public string $adm_usuario_password_init = ''; // aqui va la clave del usuario que esta en la tabla adm_usuario
    public array $secciones = array();
    public bool $encripta_md5 = false;
    public bool $aplica_seguridad = true;
    public array $defaults ;
    public int $tipo_sucursal_matriz_id = 0 ; // aqui va el id del tipo de sucursal matriz, se usa para generar la sucursal matriz en caso de que la tabla este vacia, se recomienda cambiarlo por seguridad

    public int $tipo_dispersion = 0; // aqui va el id del tipo de dispersion que se usara para generar las facturas

    public string $ruta_factura_pdf = " "; // aqui va la ruta del pdf que se usara como plantilla para generar las facturas, se recomienda que sea un pdf con campos editables para facilitar la generacion de las facturas
    public bool $aplica_relacion_layout_factura = true;

    public function __construct(){
        $this->path_base = ''; // aqui va la ruta base del sistema, se usa para generar rutas absolutas en los templates y en el sistema en general

        if(isset($_GET['session_id'])){
            $this->session_id = $_GET['session_id'];
        }
        $this->defaults['dp_pais']['id'] = 0; // aqui va el id del pais por default
        $this->defaults['dp_estado']['id'] = 0; // aqui va el id del estado por default
        $this->defaults['dp_municipio']['id'] = 0; // aqui va el id del municipio por default
    }
}
