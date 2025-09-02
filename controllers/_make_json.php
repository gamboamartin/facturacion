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
                return (new errores())->error('Error el banco debe existir', $fc_row_layout);
            }
            $this->r_banco = $banco_array[$this->r_banco];
        }
        $data = [
            "Comprobante" => [
                "Version" => "4.0",
                "Serie" => "2025",
                "Folio" => "{$this->folio}",
                "Fecha" => "{$this->fecha_emision}T23:59:00",
                "NoCertificado" => "00001000000707719966",
                "SubTotal" => "{$this->neto}",
                "Moneda" => "MXN",
                "TipoCambio" => "1",
                "Total" => "{$this->neto}",
                "TipoDeComprobante" => "N",
                "Exportacion" => "01",
                "MetodoPago" => "PUE",
                "LugarExpedicion" => "14210",

                "Emisor" => [
                    "Rfc" => "RRH240411K89",
                    "Nombre" => "RECURSOS Y RESULTADOS HARIMENI",
                    "RegimenFiscal" => "601"
                ],

                "Receptor" => [
                    "Rfc" => "{$this->r_rfc}",
                    "Nombre" => "{$this->r_nombre}",
                    "DomicilioFiscalReceptor" => "{$this->r_cp}",
                    "RegimenFiscalReceptor" => "605",
                    "UsoCFDI" => "CN01"
                ],

                "Conceptos" => [
                    [
                        "ClaveProdServ" => "84111505",
                        "Cantidad" => "1",
                        "ClaveUnidad" => "ACT",
                        "Descripcion" => "Pago de nómina",
                        "ValorUnitario" => "{$this->neto}",
                        "Importe" => "{$this->neto}",
                        "ObjetoImp" => "01"
                    ]
                ],

                "Complemento" => [
                    [
                        "Nomina" => [
                            "Version" => "1.2",
                            "TipoNomina" => "E",
                            "FechaPago" => "{$this->fecha_pago}",
                            "FechaInicialPago" => "{$this->fecha_pago}",
                            "FechaFinalPago" => "{$this->fecha_pago}",
                            "NumDiasPagados" => "1",
                            "TotalPercepciones" => "{$this->neto}",

                            "Receptor" => [
                                "Curp" => "{$this->r_curp}",
                                "NumSeguridadSocial" => "{$this->r_nss}",
                                "FechaInicioRelLaboral" => "2025-01-01",//dato por defecto para todos
                                "Antigüedad" => "P10W",
                                "TipoContrato" => "99",
                                "Sindicalizado" => "No",
                                "TipoJornada" => "01",
                                "TipoRegimen" => "99",
                                "NumEmpleado" => "{$this->clave_empleado}",
                                "Departamento" => "0",
                                "Puesto" => "0",
                                "RiesgoPuesto" => "1",
                                "PeriodicidadPago" => "99",
                                "Banco" => "{$this->r_banco}",
                                "CuentaBancaria" => "{$cuenta}",
                                "SalarioBaseCotApor" => "278.80",//dato por defecto para todos
                                "SalarioDiarioIntegrado" => "292.54",//dato por defecto para todos
                                "ClaveEntFed" => "CMX"
                            ],

                            "Percepciones" => [
                                "TotalJubilacionPensionRetiro" => "{$this->neto}",
                                "TotalGravado" => "0.0",
                                "TotalExento" => "{$this->neto}",
                                "Percepcion" => [
                                    [
                                        "TipoPercepcion" => "039",
                                        "Clave" => "999",
                                        "Concepto" => "PENSIÓN POR RENTA VITALICIA",
                                        "ImporteGravado" => "0.00",
                                        "ImporteExento" => "{$this->neto}"
                                    ]
                                ],
                                "JubilacionPensionRetiro" => [
                                    "TotalUnaExhibicion" => "0.0",
                                    "IngresoAcumulable" => "0.0",
                                    "IngresoNoAcumulable" => "{$this->neto}"
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        if($this->r_clave_interbancaria !== ''){
            unset($data['Comprobante']['Complemento'][0]['Nomina']['Receptor']['Banco']);
        }
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return ['json' => $json];
    }
}