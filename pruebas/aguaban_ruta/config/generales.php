<?php
namespace config;
/** Configuración ficticia del laboratorio, nunca la configuración privada. */
final class generales {
    public string $path_base = '/var/www/html/facturacion/';
    public string $sistema = 'facturacion';
    public bool $aplica_seguridad = true;
    public string $session_id = 'QA-FICTICIA';
    public string $url_base = 'http://example.invalid/';
}
