<?php
namespace config;

use stdClass;

class pac{
    public stdClass $pac;
    public static int $fc_csd_nomina_id = 0; // aqui va el id del certificado de sello digital que se usara para timbrar las nominas
    public string $ruta_pac = ''; // aqui va la ruta del wsdl del pac, se recomienda cambiarlo por seguridad
    public string $usuario_integrador = ''; // aqui va el usuario o contraseña que se usara para autenticar con el pac, se recomienda cambiarlo por seguridad
    public string $timbra_rs = ''; // aqui va el nombre del metodo que se usara para timbrar
    public string $cancela_rs = '';// aqui va el nombre del metodo que se usara para cancelar
    public string $tipo = ''; // aqui va el tipo de entrada que se usara para timbrar, puede ser json o xml, se recomienda json para facilitar la integracion
    public string $pac_prov = ''; // aqui va el nombre del pac que se usara, se recomienda usar el nombre del pac para facilitar la integracion
    public bool $base_64_qr = true;

    public function __construct(){
        $this->pac = new stdClass();
        $this->pac->facturalo = new stdClass();
        $this->pac->facturalo->ruta = ''; // aqui va la ruta del wsdl del pac
        $this->pac->facturalo->pass = ''; // aqui va el usuario o contraseña que se usara para autenticar con el pac
        $this->pac->facturalo->timbra_rs = ''; // aqui va el nombre del metodo que se usara para timbrar
        $this->pac->facturalo->aplica_params = false;
        $this->pac->facturalo->tipo_entrada = ''; // aqui va el tipo de entrada que se usara para timbrar, puede ser json o xml, se recomienda json para facilitar la integracion

        $this->pac->profact = new stdClass();
        #$this->pac->profact->ruta = '' // aqui va la ruta del wsdl del pac;
        #$this->pac->profact->pass = '' // aqui va el usuario o contraseña que se usara para autenticar con el pac;
        #$this->pac->profact->timbra_rs = ''; // aqui va el nombre del metodo que se usara para timbrar
        #$this->pac->profact->aplica_params = ; // aqui va un booleano que indica si se aplican los parametros de timbrado, se recomienda true para facilitar la integracion
        #$this->pac->profact->tipo_entrada = ''; // aqui va el tipo de entrada que se usara para timbrar, puede ser json o xml, se recomienda json para facilitar la integracion
    }
}
