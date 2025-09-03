<?php
namespace gamboamartin\facturacion\controllers;
use gamboamartin\errores\errores;
use PDO;

class _make_json
{
    public errores $error;
    private string $folio;
    private float $neto;
    private string $r_rfc;
    private string $r_nombre;
    private ?string $r_cp;
    private ?string $r_curp;
    private ?string $r_nss;
    private string $clave_empleado;
    private ?string $r_banco;
    private ?string $r_cuenta;
    private ?string $r_clave_interbancaria;
    private ?string $r_tarjeta;
    private string $fecha_emision;
    private string $fecha_pago;

    private string $no_certificado = '30001000000500003416';

    private string $regimen_fiscal_receptor = "605";
    public function __construct(PDO $link, object $fc_row_layout) {

        $this->error = new errores();

        $this->folio = "FF{$fc_row_layout->fc_row_layout_id}";
        $this->r_cp = $fc_row_layout->fc_row_layout_cp;
        $this->neto = $fc_row_layout->fc_row_layout_neto_depositar;
        $this->r_rfc = $fc_row_layout->fc_row_layout_rfc;
        $this->r_nombre = $fc_row_layout->fc_row_layout_nombre_completo;
        $this->clave_empleado = $fc_row_layout->fc_row_layout_fc_empleado_id;
        $this->r_curp = $fc_row_layout->fc_row_layout_curp;;
        $this->r_nss = $fc_row_layout->fc_row_layout_nss;;
        $this->r_cuenta = $fc_row_layout->fc_row_layout_cuenta;
        $this->r_clave_interbancaria = $fc_row_layout->fc_row_layout_clabe;
        $this->r_tarjeta = $fc_row_layout->fc_row_layout_tarjeta;
        //ToDo: obtener bien la fecha de emision $fc_row_layout->fc_row_layout_fecha_emision no se encuentra
        $this->fecha_emision = $fc_row_layout->fc_row_layout_fecha_pago;
        $this->fecha_pago = $fc_row_layout->fc_row_layout_fecha_pago;
        $this->r_banco = $fc_row_layout->fc_row_layout_banco;

    }

    private function concepto(): array
    {
        return [
            "ClaveProdServ" => "84111505",
            "Cantidad" => "1",
            "ClaveUnidad" => "ACT",
            "Descripcion" => "Pago de nómina",
            "ValorUnitario" => "{$this->neto}",
            "Importe" => "{$this->neto}",
            "ObjetoImp" => "01"
        ];

    }

    private function emisor(string $nombre, string $regimen_fiscal, string $rfc): array
    {
        return ["Rfc" => "$rfc", "Nombre" => "$nombre", "RegimenFiscal" => "$regimen_fiscal"];
    }

    public function getJson(): array
    {

        $cuenta = $this->r_clave_interbancaria;
        if(!isset($this->r_clave_interbancaria) || $this->r_clave_interbancaria === ''){
            $cuenta = $this->r_cuenta;
            if (!$this->r_cuenta || $this->r_cuenta === '') {
                $cuenta = $this->r_tarjeta;
            }
        }
        $banco_array = [
            'BANAMEX' => '002',
            'SANTANDER' => '012',
            'BBVA' => '014',
            'BANCOMER' => '014',
            'HSBC' => '021',
            'SCOTIABANK' => '032',
            'BANORTE' => '072',
        ];

        if(!isset($this->r_clave_interbancaria) || $this->r_clave_interbancaria === ''){
            if (!$this->r_banco) {
                return (new errores())->error('Error el banco debe existir', $this);
            }
            $this->r_banco = $banco_array[$this->r_banco];
        }

        $emisor = $this->emisor(nombre: 'ESCUELA KEMPER URGATE',regimen_fiscal:  '601',rfc:  'EKU9003173C9');
        if(errores::$error){
            return (new errores())->error('Error al generar emisor', $emisor);
        }

        $receptor = $this->receptor();
        if(errores::$error){
            return (new errores())->error('Error al generar receptor', $receptor);
        }

        $concepto = $this->concepto();
        if(errores::$error){
            return (new errores())->error('Error al generar concepto', $concepto);
        }

        $nomina = $this->nomina(cuenta: $cuenta);
        if(errores::$error){
            return (new errores())->error('Error al generar nomina', $nomina);
        }

        $data = [
            "Comprobante" => [
                "Version" => "4.0",
                "Serie" => "2025",
                "Folio" => "{$this->folio}",
                "Fecha" => "{$this->fecha_emision}T12:00:00",
                "NoCertificado" => "$this->no_certificado",
                "SubTotal" => "{$this->neto}",
                "Moneda" => "MXN",
                "TipoCambio" => "1",
                "Total" => "{$this->neto}",
                "TipoDeComprobante" => "N",
                "Exportacion" => "01",
                "MetodoPago" => "PUE",
                "LugarExpedicion" => "14210",
                "Emisor" => $emisor,
                "Receptor" => $receptor,
                "Conceptos" => [$concepto],
                "Complemento" => [["Nomina" => $nomina]]
            ]
        ];
        if($this->r_clave_interbancaria !== ''){
            unset($data['Comprobante']['Complemento'][0]['Nomina']['Receptor']['Banco']);
        }
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return ['json' => $json];
    }

    private function jubilacion(): array
    {
        return [
            "TotalUnaExhibicion" => "0.0",
            "IngresoAcumulable" => "0.0",
            "IngresoNoAcumulable" => "{$this->neto}"
        ];

    }

    private function nomina(string $cuenta): array
    {

        $receptor_nomina = $this->receptor_nomina(cuenta: $cuenta);
        if(errores::$error){
            return (new errores())->error('Error al generar receptor_nomina', $receptor_nomina);
        }

        $percepciones = $this->percepciones();
        if(errores::$error){
            return (new errores())->error('Error al generar percepciones', $percepciones);
        }

        return [
            "Version" => "1.2",
            "TipoNomina" => "E",
            "FechaPago" => "{$this->fecha_pago}",
            "FechaInicialPago" => "{$this->fecha_pago}",
            "FechaFinalPago" => "{$this->fecha_pago}",
            "NumDiasPagados" => "1",
            "TotalPercepciones" => "{$this->neto}",
            "Receptor" => $receptor_nomina,
            "Percepciones" => $percepciones
        ];

    }

    private function percepcion(): array
    {
        return [
            "TipoPercepcion" => "039",
            "Clave" => "999",
            "Concepto" => "PENSIÓN POR RENTA VITALICIA",
            "ImporteGravado" => "0.00",
            "ImporteExento" => "{$this->neto}"
        ];

    }

    private function percepciones(): array
    {

        $percepcion = $this->percepcion();
        if(errores::$error){
            return (new errores())->error('Error al generar percepcion', $percepcion);
        }

        $jubilacion = $this->jubilacion();
        if(errores::$error){
            return (new errores())->error('Error al generar jubilacion', $jubilacion);
        }

        return [
            "TotalJubilacionPensionRetiro" => "{$this->neto}",
            "TotalGravado" => "0.0",
            "TotalExento" => "{$this->neto}",
            "Percepcion" => [$percepcion],
            "JubilacionPensionRetiro" => $jubilacion
        ];

    }

    private function receptor(): array
    {
        $receptor['Rfc'] = "{$this->r_rfc}";
        $receptor['Nombre'] = "{$this->r_nombre}";
        $receptor['DomicilioFiscalReceptor'] = "{$this->r_cp}";
        $receptor['RegimenFiscalReceptor'] = "{$this->regimen_fiscal_receptor}";
        $receptor['UsoCFDI'] = "CN01";
        return $receptor;
    }

    private function receptor_nomina(string $cuenta): array
    {
        return ["Curp" => "{$this->r_curp}", "NumSeguridadSocial" => "{$this->r_nss}",
            "FechaInicioRelLaboral" => "2025-01-01",//dato por defecto para todos
            "Antigüedad" => "P10W", "TipoContrato" => "99", "Sindicalizado" => "No", "TipoJornada" => "01",
            "TipoRegimen" => "99", "NumEmpleado" => "{$this->clave_empleado}", "Departamento" => "0", "Puesto" => "0",
            "RiesgoPuesto" => "1", "PeriodicidadPago" => "99", "Banco" => "{$this->r_banco}",
            "CuentaBancaria" => "{$cuenta}", "SalarioBaseCotApor" => "278.80",//dato por defecto para todos
            "SalarioDiarioIntegrado" => "292.54",//dato por defecto para todos
            "ClaveEntFed" => "CMX"];

    }
}