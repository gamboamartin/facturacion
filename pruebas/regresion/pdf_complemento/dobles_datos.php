<?php
declare(strict_types=1);

/** Solo para fronteras SQL del lector; no sustituye el ensayo PDO real del despliegue. */
final class PdoDatosPdfComplemento extends PDO
{
    public array $sentencias = [];
    public array $documentos = [];
    public array $sellados = [];
    public array $cabecera = ['id'=>1,'serie'=>'CP','folio'=>'000042','total'=>'0','fecha'=>'2026-11-24T12:00:01',
        'emisor_nombre'=>'EMISOR FICTICIO ÁRBOL','emisor_rfc'=>'AAA010101AAA','emisor_telefono'=>'',
        'emisor_regimen'=>'601','cp_expedicion'=>'45010','receptor_nombre'=>'RECEPTOR FICTICIO MUÑOZ',
        'receptor_rfc'=>'BBB010101BBB','receptor_regimen'=>'601','receptor_cp'=>'64000','uso_cfdi'=>'CP01',
        'calle'=>'CALLE FICTICIA','numero_exterior'=>'1','numero_interior'=>'','colonia'=>'','municipio'=>'','estado'=>'',
        'folio_fiscal'=>'SIN UUID','etapa'=>'ALTA'];
    public function __construct() {}
    public function prepare(string $query, array $options=[]): PDOStatement|false
    {
        if (!preg_match('/^SELECT\b/', trim($query))) { throw new LogicException('Solo SELECT permitido.'); }
        $this->sentencias[] = $query;
        return new class($this, $query) extends PDOStatement {
            public function __construct(private PdoDatosPdfComplemento $conexion, private string $sql) {}
            public function execute(?array $params=null): bool { return true; }
            public function fetchAll(int $mode=PDO::FETCH_DEFAULT, mixed ...$args): array { return $this->conexion->respuesta($this->sql); }
        };
    }
    public function respuesta(string $sql): array
    {
        if (str_contains($sql, 'FROM fc_complemento_pago c INNER JOIN fc_csd')) { return [$this->cabecera]; }
        if (str_contains($sql, 'FROM fc_cfdi_sellado_cp')) { return $this->sellados; }
        if (str_contains($sql, 'FROM fc_complemento_pago_documento')) { return $this->documentos; }
        if (str_contains($sql, 'FROM fc_pago_pago pp')) {
            return [['id'=>10,'fecha_pago'=>'2026-11-20T08:30:00','monto'=>'580.00','forma'=>'03','moneda'=>'MXN','tipo_cambio'=>'1']];
        }
        if (str_contains($sql, 'FROM fc_docto_relacionado dr')) {
            return [['id'=>20,'serie'=>'A','folio'=>'100','folio_fiscal'=>'22222222-2222-4222-8222-222222222222',
                'moneda'=>'MXN','objeto'=>'02','equivalencia_dr'=>'1','num_parcialidad'=>'2','imp_saldo_ant'=>'1160.00',
                'imp_pagado'=>'580.00','imp_saldo_insoluto'=>'580.00']];
        }
        if (str_contains($sql, 'FROM fc_traslado_')) {
            return [['base'=>'500.00','impuesto'=>'002','factor'=>'Tasa','tasa'=>'0.160000','importe'=>'80.00']];
        }
        return [];
    }
}
