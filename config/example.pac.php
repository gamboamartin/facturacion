<?php
namespace config;

use stdClass;

class pac{
    public stdClass $pac;
    public static int $fc_csd_nomina_id = 0; // aqui va el id del certificado de sello digital que se usara para timbrar las nominas
    public string $ruta_pac = 'https://dev.facturaloplus.com/ws/servicio.do?wsdl'; // aqui va la ruta del wsdl del pac, se recomienda cambiarlo por seguridad
    public string $usuario_integrador = '5dd69b542bd847bba8f62f3c25920a25'; // aqui va el usuario o contraseña que se usara para autenticar con el pac, se recomienda cambiarlo por seguridad
    public string $timbra_rs = 'timbrar'; // aqui va el nombre del metodo que se usara para timbrar
    public string $cancela_rs = 'CancelaCFDI40';// aqui va el nombre del metodo que se usara para cancelar
    public string $tipo = 'json'; // aqui va el tipo de entrada que se usara para timbrar, puede ser json o xml, se recomienda json para facilitar la integracion
    public string $pac_prov = 'facturalo'; // aqui va el nombre del pac que se usara, se recomienda usar el nombre del pac para facilitar la integracion
    public bool $base_64_qr = true;

    public function __construct(){
        $this->pac = new stdClass();
        $this->pac->facturalo = new stdClass();
        $this->pac->facturalo->ruta = 'https://dev.facturaloplus.com/ws/servicio.do?wsdl'; // aqui va la ruta del wsdl del pac
        $this->pac->facturalo->pass = '5dd69b542bd847bba8f62f3c25920a25'; // aqui va el usuario o contraseña que se usara para autenticar con el pac
        $this->pac->facturalo->timbra_rs = 'timbrar'; // aqui va el nombre del metodo que se usara para timbrar
        $this->pac->facturalo->aplica_params = false;
        $this->pac->facturalo->tipo_entrada = 'json'; // aqui va el tipo de entrada que se usara para timbrar, puede ser json o xml, se recomienda json para facilitar la integracion

    }
}
