<?php
namespace config;

use stdClass;

class views
{
    public int $reg_x_pagina = 15; //Registros a mostrar en listas
    public string $titulo_sistema = 'Facturacion'; //Titulo de sistema
    public string $ruta_template_base = "/var/www/html/facturacion/vendor/gamboa.martin/template_1/"; // aqui va la ruta base de los templates, se recomienda no cambiarlo a menos que sepas lo que estas haciendo
    public string $ruta_templates = "/var/www/html/facturacion/vendor/gamboa.martin/template_1/template/"; // aqui va la ruta de los templates, se recomienda no cambiarlo a menos que sepas lo que estas haciendo

    public string $ruta_templates_local = "/var/www/html/facturacion/templates/"; // aqui va la ruta de los templates locales, se recomienda que sea una carpeta dentro del sistema para facilitar la personalización sin tocar el template original osea el vendor
    // aca termina
    public string $url = '';
    public string $url_assets = '';
    public string $url_js = '';
    public stdClass $heads;
    public array $subtitulos_menu = array();
    public static string $nav_bg_color = '#232F5C';

    public function __construct(){
        $this->heads = new stdClass();
        $this->heads->adm_seccion = new stdClass();
        $this->heads->adm_seccion->login = 'nav/_head_login.php'; // aqui va la ruta del head de la seccion adm_seccion, se recomienda que sea una ruta relativa a la carpeta de templates para facilitar la personalización sin tocar el template original osea el vendor


        $url = 'http://localhost/facturacion/'; // aqui va la url base del sistema, se recomienda que sea la url local del sistema para facilitar el desarrollo y las pruebas, se recomienda no usar la url de produccion para evitar problemas de seguridad
        $this->url = $url;
        $this->url_assets = $url.'vendor/gamboa.martin/template_1/assets/'; // aqui va la url de los assets, en este caso la que se toma en cuenta es la del template original osea el vendor, se recomienda no cambiarlo a menos que sepas lo que estas haciendo

        $this->url_js = $url.'vendor/gamboa.martin/js_base/src/'; // aqui va la url de los js, se recomienda que sea la del template original osea el vendor para facilitar la personalización sin tocar el template original osea el vendor, se recomienda no cambiarlo a menos que sepas lo que estas haciendo
    }

    public function template_path(string $rel): string
    {
        $rel = ltrim($rel, '/');

        $local = rtrim($this->ruta_templates_local, '/').'/'.$rel;
        if (is_file($local)) {
            return $local;
        }

        return rtrim($this->ruta_templates, '/').'/'.$rel;
    }

} // esta funcion se encarga de generar la ruta del template, primero busca en la carpeta de templates locales y si no lo encuentra busca en la carpeta de templates original osea el vendor, se recomienda usar esta funcion para generar la ruta de los templates para facilitar la personalización sin tocar el template original osea el vendor y asi se evita rompe el sistema en caso de que el template original osea el vendor se actualice y cambie la estructura de los templates, se recomienda no usar rutas absolutas para los templates para evitar problemas de seguridad y facilitar la personalización sin tocar el template original osea el vendor
