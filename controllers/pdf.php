<?php

namespace gamboamartin\facturacion\controllers;

use config\generales;
use gamboamartin\comercial\models\com_tmp_prod_cs;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\fc_cuenta_predial;
use gamboamartin\validacion\validacion;
use Mpdf\Mpdf;
use NumberFormatter;
use PDO;
use stdClass;
use Throwable;

final class pdf
{

    public Mpdf $pdf;
    private errores $error;
    private validacion $valida;

    public function __construct()
    {
        $this->error = new errores();
        $this->valida = new validacion();
        try {
            $temporales = (new generales())->path_base . "archivos/tmp/";
            $this->pdf = new Mpdf(['tempDir' => $temporales, 'mode' => 'utf-8', 'format' => [229, 279],
                'margin_left' => 8, 'margin_right' => 8]);
        } catch (Throwable $e) {
            $error = (new errores())->error('Error al generar objeto de pdf', $e);
            print_r($error);
            die('Error');
        }

        $this->pdf->simpleTables = false;

        $css = file_get_contents((new generales())->path_base . "css/pdf.css");
        try {
            $this->pdf->WriteHTML($css, 1);
        }
        catch (Throwable $e){
            $error = (new errores())->error('Error al generar objeto css', $e);
            print_r($error);
            die('Error');
        }

    }

    /**
     * Valida si aplica o no la integracion de un impuesto en una partida
     * @param array $concepto Concepto a validar
     * @param string $tipo_impuesto Tipo de impuesto a integrar
     * @return bool
     */
    private function aplica_impuesto(array $concepto, string $tipo_impuesto): bool
    {
        $aplica = false;
        if(isset($concepto[$tipo_impuesto]) && count($concepto[$tipo_impuesto])>0) {
            $aplica = true;
        }
        return $aplica;
    }

    private function aplica_cualquier_impuesto(array $concepto){
        $aplica_cualquier_impuesto = false;
        $aplica_traslado = $this->aplica_impuesto(concepto: $concepto, tipo_impuesto: 'traslados');
        if(errores::$error){
            return $this->error->error(mensaje: 'Error validar aplica impuesto',data:  $aplica_traslado);
        }
        $aplica_retencion = $this->aplica_impuesto(concepto: $concepto, tipo_impuesto: 'retenidos');
        if(errores::$error){
            return $this->error->error(mensaje: 'Error validar aplica impuesto',data:  $aplica_retencion);
        }

        if($aplica_retencion || $aplica_traslado) {
            $aplica_cualquier_impuesto = true;
        }
        return $aplica_cualquier_impuesto;
    }

    private function base(float $fc_partida_cantidad, float $fc_partida_descuento, float $fc_partida_valor_unitario): float
    {
        $base = $fc_partida_valor_unitario * $fc_partida_cantidad;
        $base = round($base,2);

        $base = $base - $fc_partida_descuento;
        return round($base,2);
    }

    final public function data_relacionados(string $name_entidad, array $relacionadas){

        if(count($relacionadas)>0) {
            $body_td_title = $this->html(etiqueta: "td", data: "Facturas Relacionadas", class: "negrita");
            $body_tr_title = $this->html(etiqueta: "tr", data: $body_td_title);
            $body_title = $this->html(etiqueta: "tbody", data: $body_tr_title);
            $table_title = $this->html(etiqueta: "table", data: $body_title);

            try {
                $this->pdf->WriteHTML($table_title);
            } catch (Throwable $e) {
                return $this->error->error(mensaje: 'Error al generar pdf', data: $e);
            }
        }

        foreach ($relacionadas as $relacion) {
            $body_td_tr = $this->html(etiqueta: "td", data: "Tipo Relacion: $relacion[cat_sat_tipo_relacion_codigo] $relacion[cat_sat_tipo_relacion_descripcion]", class: "negrita");
            $body_tr_tr = $this->html(etiqueta: "tr", data: $body_td_tr);
            $body_tr = $this->html(etiqueta: "tbody", data: $body_tr_tr );
            $table_tr = $this->html(etiqueta: "table", data: $body_tr);
            try {
                $this->pdf->WriteHTML($table_tr);
            }
            catch (Throwable $e){
                return $this->error->error(mensaje: 'Error al generar pdf',data:  $e);
            }


            $tables_uuid = $this->integra_relacionadas(name_entidad: $name_entidad, relacion: $relacion);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al integrar tables_uuid', data: $tables_uuid);
            }

        }


    }


    public function header(string $cfdi, string $cod_postal, string $cod_postal_receptor, string $csd, string $efecto,
                           string $exportacion, string $fecha, string $folio, string $folio_fiscal,
                           string $nombre_emisor, string $nombre_receptor,  string $observaciones,
                           string $regimen_fiscal, string $regimen_fiscal_receptor, string $rfc_emisor,
                           string $rfc_receptor, string $ruta_logo): string|array
    {


        $logo = '';
        if($ruta_logo!=='') {
            if(file_exists($ruta_logo)){
                $logo = $this->html(etiqueta: "img", data: "", propiedades: 'src = "' . $ruta_logo . '" width = "110px"');
                if (errores::$error) {
                    return $this->error->error(mensaje: 'Error al generar data', data: $ruta_logo);
                }
                $logo = "<div width = '100%' align='center'>$logo</div>";
            }
        }

        $body_td_rfc_emisor = $this->html(etiqueta: "td", data: "RFC emisor:", class: "negrita");
        $body_td_rfc_emisor_data = $this->html(etiqueta: "td", data: $rfc_emisor);
        $body_td_folio_fiscal = $this->html(etiqueta: "td", data: "Folio fiscal:", class: "negrita");
        $body_td_folio_fiscal_data = $this->html(etiqueta: "td", data: $folio_fiscal);


        $body_td_5 = $this->html(etiqueta: "td", data: "Nombre emisor:", class: "negrita");
        $body_td_6 = $this->html(etiqueta: "td", data: $nombre_emisor);
        $body_td_7 = $this->html(etiqueta: "td", data: "No. de serie del CSD:", class: "negrita");
        $body_td_8 = $this->html(etiqueta: "td", data: $csd);
        $body_td_9 = $this->html(etiqueta: "td", data: "RFC receptor:", class: "negrita");
        $body_td_10 = $this->html(etiqueta: "td", data: $rfc_receptor);
        $body_td_11 = $this->html(etiqueta: "td", data: "Código postal, fecha y hora de emisión:", class: "negrita");
        $body_td_12 = $this->html(etiqueta: "td", data: "$cod_postal $fecha");
        $body_td_13 = $this->html(etiqueta: "td", data: "Nombre receptor:", class: "negrita");
        $body_td_14 = $this->html(etiqueta: "td", data: $nombre_receptor);
        $body_td_15 = $this->html(etiqueta: "td", data: "Efecto de comprobante:", class: "negrita");
        $body_td_16 = $this->html(etiqueta: "td", data: $efecto);
        $body_td_17 = $this->html(etiqueta: "td", data: "Código postal del receptor:", class: "negrita");
        $body_td_18 = $this->html(etiqueta: "td", data: $cod_postal_receptor);
        $body_td_19 = $this->html(etiqueta: "td", data: "Régimen fiscal:", class: "negrita");
        $body_td_20 = $this->html(etiqueta: "td", data: $regimen_fiscal);
        $body_td_21 = $this->html(etiqueta: "td", data: "Régimen fiscal receptor:", class: "negrita");
        $body_td_22 = $this->html(etiqueta: "td", data: $regimen_fiscal_receptor);
        $body_td_23 = $this->html(etiqueta: "td", data: "Exportación:", class: "negrita");
        $body_td_24 = $this->html(etiqueta: "td", data: $exportacion);

        $body_td_uso_cfdi = $this->html(etiqueta: "td", data: "Uso CFDI:", class: "negrita");
        $body_td_uso_cfdi_data = $this->html(etiqueta: "td", data: $cfdi);

        $body_td_folio = $this->html(etiqueta: "td", data: "Folio:", class: "negrita");
        $body_td_folio_data = $this->html(etiqueta: "td", data: $folio);

        $body_td_tag_observaciones = $this->html(etiqueta: "td",data: "Observaciones:", class: "negrita" );
        $body_td_observaciones = $this->html(etiqueta: "td",data: $observaciones );


        $body_tr_1 = $this->html(etiqueta: "tr", data: $body_td_rfc_emisor . $body_td_rfc_emisor_data .
            $body_td_folio_fiscal . $body_td_folio_fiscal_data);
        $body_tr_2 = $this->html(etiqueta: "tr", data: $body_td_5 . $body_td_6 . $body_td_7 . $body_td_8);
        $body_tr_3 = $this->html(etiqueta: "tr", data: $body_td_9 . $body_td_10 . $body_td_11 . $body_td_12);
        $body_tr_4 = $this->html(etiqueta: "tr", data: $body_td_13 . $body_td_14 . $body_td_15 . $body_td_16);
        $body_tr_5 = $this->html(etiqueta: "tr", data: $body_td_17 . $body_td_18 . $body_td_19 . $body_td_20);
        $body_tr_6 = $this->html(etiqueta: "tr", data: $body_td_21 . $body_td_22 . $body_td_23 . $body_td_24);
        $body_tr_7 = $this->html(etiqueta: "tr", data: $body_td_uso_cfdi . $body_td_uso_cfdi_data. $body_td_folio. $body_td_folio_data);
        $body_tr_8 = $this->html(etiqueta: "tr", data: $body_td_tag_observaciones. $body_td_observaciones);

        $body = $this->html(etiqueta: "tbody", data:  $body_tr_1 . $body_tr_2 . $body_tr_3 . $body_tr_4 .
            $body_tr_5 . $body_tr_6 . $body_tr_7. $body_tr_8);

        $table = $this->html(etiqueta: "table", data: $body);

        try {
            $this->pdf->WriteHTML($logo. $table);
            //$this->pdf->SetHeader($ruta_logo.$table);
        }
        catch (Throwable $e){
            return $this->error->error(mensaje: 'Error al generar pdf',data:  $e);
        }
        return $table;
    }

    private function integra_relacionada(string $key_relacionadas, string $key_uuid, array $relacion){
        $table_uuid = '';

        if(isset($relacion[$key_relacionadas])) {

            foreach ($relacion[$key_relacionadas] as $relacionada) {

                $table_uuid = $this->table_uuid_rel(key_uuid: $key_uuid, relacionada: $relacionada);
                if (errores::$error) {
                    return $this->error->error(mensaje: 'Error al integrar table_uuid', data: $table_uuid);
                }
            }
        }

        return $table_uuid;
    }

    private function integra_relacionadas(string $name_entidad, array $relacion){
        $datas = new stdClass();
        $table_uuid = $this->integra_relacionada(key_relacionadas: 'fc_facturas_relacionadas',
            key_uuid: $name_entidad . '_uuid', relacion: $relacion);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al integrar table_uuid', data: $table_uuid);
        }
        $datas->fc_facturas_relacionadas = $table_uuid;

        $table_uuid = $this->integra_relacionada(key_relacionadas: 'fc_facturas_externas_relacionadas',
            key_uuid: 'fc_uuid_uuid', relacion: $relacion);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al integrar table_uuid', data: $table_uuid);
        }
        $datas->fc_facturas_externas_relacionadas = $table_uuid;

        $table_uuid = $this->integra_relacionada(key_relacionadas: 'fc_facturas_relacionadas_nc',
            key_uuid: 'fc_factura_uuid', relacion: $relacion);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al integrar table_uuid', data: $table_uuid);
        }
        $datas->fc_facturas_relacionadas_nc = $table_uuid;

        return $datas;
    }

    private function concepto_datos(array $concepto, string $name_entidad_partida, PDO $link): string|array
    {

        $concepto = $this->keys_no_ob(concepto: $concepto);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar concepto',data:  $concepto);
        }

        $keys = array($name_entidad_partida.'_descuento',$name_entidad_partida.'_valor_unitario','cat_sat_producto_codigo',
            'cat_sat_producto_id',$name_entidad_partida.'_cantidad','cat_sat_unidad_codigo','cat_sat_unidad_descripcion',
            $name_entidad_partida.'_cantidad','cat_sat_obj_imp_descripcion');
        $valida = $this->valida->valida_existencia_keys(keys: $keys,registro:  $concepto);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar concepto',data:  $valida);
        }

        $class = "txt-center border";

        $fc_partida_valor_unitario = $this->fc_partida_double(concepto: $concepto,
            key: $name_entidad_partida.'_valor_unitario');
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener fc_partida_valor_unitario',data:  $fc_partida_valor_unitario);
        }

        $fc_partida_cantidad = $this->fc_partida_double(concepto: $concepto, key: $name_entidad_partida.'_cantidad');
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener fc_partida_cantidad',data:  $fc_partida_cantidad);
        }

        $fc_partida_descuento = $this->fc_partida_double(concepto: $concepto,key: $name_entidad_partida.'_descuento');
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al limpiar monto',data:  $fc_partida_descuento);
        }

        $fc_partida_importe = $this->fc_partida_double(concepto: $concepto,key: $name_entidad_partida.'_sub_total_base');
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al limpiar monto',data:  $fc_partida_importe);
        }

        $fc_partida_sub_total = $this->fc_partida_double(concepto: $concepto,key: $name_entidad_partida.'_sub_total');
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al limpiar monto',data:  $fc_partida_sub_total);
        }


        $fc_partida_descuento = $this->monto_moneda(monto: $fc_partida_descuento);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al limpiar monto',data:  $fc_partida_descuento);
        }

        $fc_partida_valor_unitario = $this->monto_moneda(monto: $fc_partida_valor_unitario);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al limpiar monto',data:  $fc_partida_valor_unitario);
        }

        $fc_partida_importe = $this->monto_moneda(monto: $fc_partida_importe);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al limpiar monto',data:  $fc_partida_importe);
        }

        $fc_partida_sub_total = $this->monto_moneda(monto: $fc_partida_sub_total);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al limpiar monto',data:  $fc_partida_sub_total);
        }


        $body_td_1 = $this->html(etiqueta: "td", data: $concepto['com_producto_codigo_sat'], class: $class, propiedades: "colspan='2'");
        $body_td_2 = $this->html(etiqueta: "td", data: $concepto['com_producto_codigo'], class: $class);
        $body_td_3 = $this->html(etiqueta: "td", data: $fc_partida_cantidad, class: $class);
        $body_td_4 = $this->html(etiqueta: "td", data: $concepto['cat_sat_unidad_codigo'], class: $class);
        $body_td_5 = $this->html(etiqueta: "td", data: $concepto['cat_sat_unidad_descripcion'], class: $class);
        $body_td_6 = $this->html(etiqueta: "td", data: $fc_partida_valor_unitario, class: $class);
        $body_td_7 = $this->html(etiqueta: "td", data: $fc_partida_importe, class: $class);
        $body_td_8 = $this->html(etiqueta: "td", data: $fc_partida_descuento, class: $class);
        $body_td_9 = $this->html(etiqueta: "td", data: $fc_partida_sub_total, class: $class);
        $body_td_10 = $this->html(etiqueta: "td", data: $concepto['cat_sat_obj_imp_descripcion'], class: $class);

        return $this->html(etiqueta: "tr", data: $body_td_1 . $body_td_2 . $body_td_3 . $body_td_4 . $body_td_5 . $body_td_6 .
            $body_td_7 . $body_td_8 . $body_td_9. $body_td_10);
    }

    private function columnas_impuestos(): string
    {
        $body_td_3 = $this->html(etiqueta: "td", data: "Impuesto", class: "txt-center negrita", propiedades: "colspan='2'");
        $body_td_4 = $this->html(etiqueta: "td", data: "Tipo", class: "txt-center negrita", propiedades: "colspan='2'");
        $body_td_5 = $this->html(etiqueta: "td", data: "Base", class: "txt-center negrita");
        $body_td_6 = $this->html(etiqueta: "td", data: "Tipo Factor", class: "txt-center negrita", propiedades: "colspan='2'");
        $body_td_7 = $this->html(etiqueta: "td", data: "Tasa o Cuota", class: "txt-center negrita", propiedades: "colspan='2'");
        $body_td_8 = $this->html(etiqueta: "td", data: "Importe", class: "txt-center negrita");

        return $this->html(etiqueta: "tr", data:  $body_td_3 . $body_td_4 . $body_td_5 .
            $body_td_6 . $body_td_7 . $body_td_8);
    }

    private function columnas_impuestos_base(array $concepto){
        $html = '';
        $aplica_cualquier_impuesto = $this->aplica_cualquier_impuesto(concepto: $concepto);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error validar aplica impuesto',data:  $aplica_cualquier_impuesto);
        }

        if($aplica_cualquier_impuesto) {
            $html = $this->columnas_impuestos();
        }
        return $html;
    }

    private function concepto_calculos(array $concepto, string $name_entidad_partida): string
    {

        $body_tr = $columns_imp = $this->columnas_impuestos_base(concepto: $concepto);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar columns_imp',data:  $columns_imp);
        }
        $tr = $this->integra_impuesto(concepto: $concepto, name_entidad_partida: $name_entidad_partida, tipo_impuesto: 'traslados');
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar tr',data:  $tr);
        }
        $body_tr.= $tr;
        $tr = $this->integra_impuesto(concepto: $concepto, name_entidad_partida: $name_entidad_partida, tipo_impuesto: 'retenidos');
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar tr',data:  $tr);
        }
        $body_tr.= $tr;


        return $body_tr;
    }

    private function concepto_producto(array $concepto, string $name_entidad_partida): string|array
    {

        $name_entidad_partida = trim($name_entidad_partida);
        if($name_entidad_partida === ''){
            return $this->error->error(mensaje: 'Error name_entidad_partida esta vacio', data: $name_entidad_partida);
        }

        $keys = array($name_entidad_partida.'_descripcion');
        $valida = $this->valida->valida_existencia_keys(keys: $keys,registro:  $concepto);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar concepto', data: $valida);
        }

        $body_td_1 = $this->html(etiqueta: "td", data: "Descripción", class: "border color negrita", propiedades: "colspan='11'");
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al genera dato', data: $body_td_1);
        }
        $body_td_2 = $this->html(etiqueta: "td", data: $concepto[$name_entidad_partida.'_descripcion'], class: "border",
            propiedades: "colspan='11'");
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al genera dato', data: $body_td_1);
        }

        $body_tr_1 = $this->html(etiqueta: "tr", data: $body_td_1);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al genera dato', data: $body_td_1);
        }
        $body_tr_2 = $this->html(etiqueta: "tr", data: $body_td_2);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al genera dato', data: $body_td_1);
        }

        return $body_tr_1.$body_tr_2;
    }

    private function concepto_numeros(array $concepto, PDO $link): string|array
    {
        $body_tr = '';
        $aplica_numero_pedimento = false;
        $aplica_cuenta_predial = false;

        if($concepto['com_producto_aplica_predial'] === 'activo'){
            $aplica_cuenta_predial = true;
        }

        $class = "color border negrita txt-center";

        if($aplica_numero_pedimento) {
            $body_td_1 = $this->html(etiqueta: "td", data: "Número de pedimento", class: $class, propiedades: "colspan='4'");
        }
        if($aplica_cuenta_predial) {
            $body_td_2 = $this->html(etiqueta: "td", data: "Número de cuenta predial", class: $class, propiedades: "colspan='6'");
        }
        if($aplica_numero_pedimento) {
            $body_td_3 = $this->html(etiqueta: "td", data: " ", class: "txt-center border", propiedades: "colspan='4'");
        }
        if($aplica_cuenta_predial) {

            $cuenta_predial = (new fc_cuenta_predial(link: $link))->cuenta_predial(fc_registro_partida_id: $concepto['fc_partida_id']);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al obtener predial', data: $cuenta_predial);
            }

            $body_td_4 = $this->html(etiqueta: "td", data: "$cuenta_predial[fc_cuenta_predial_descripcion]", class: "txt-center border", propiedades: "colspan='6'");
        }
        if($aplica_numero_pedimento) {
            $body_tr = $this->html(etiqueta: "tr", data: $body_td_1 . $body_td_2);
        }
        if($aplica_cuenta_predial) {
            $body_tr .= $this->html(etiqueta: "tr", data: $body_td_2 . $body_td_4);
        }

        return $body_tr;
    }

    public function conceptos(array $conceptos, PDO $link, string $name_entidad_partida)
    {
        $name_entidad_partida = trim($name_entidad_partida);
        if($name_entidad_partida === ''){
            return $this->error->error(mensaje: 'Error name_entidad_partida esta vacio',data:  $name_entidad_partida);
        }

        $titulo = $this->html(etiqueta: "h1", data: "Conceptos", class: "negrita titulo");
        try {
            $this->pdf->WriteHTML($titulo);
        }
        catch (Throwable $e){
            return $this->error->error(mensaje: 'Error al generar pdf',data:  $e);
        }
        $head_td_clave_prod_serv = $this->html(etiqueta: "th",
            data: "Clave Prod/Serv", class: "negrita border color", propiedades: "colspan='2'");;
        $head_td_no_identificacion = $this->html(etiqueta: "th", data: "No. Ident", class: "negrita border color");
        $head_td_cantidad = $this->html(etiqueta: "th", data: "Cant", class: "negrita border color");
        $head_td_cve_unidad = $this->html(etiqueta: "th", data: "Cve Unidad", class: "negrita border color");
        $head_td_unidad = $this->html(etiqueta: "th", data: "Unidad", class: "negrita border color");
        $head_td_valor_unitario = $this->html(etiqueta: "th", data: "Valor Unitario", class: "negrita border color");
        $head_td_importe = $this->html(etiqueta: "th", data: "Importe", class: "negrita border color");
        $head_td_descuento = $this->html(etiqueta: "th", data: "Descuento", class: "negrita border color");
        $head_td_sub_total = $this->html(etiqueta: "th", data: "Sub Total", class: "negrita border color");
        $head_td_obj_imp = $this->html(etiqueta: "th", data: "Objeto Impuesto", class: "negrita border color");

        $head_tr_1 = $this->html(etiqueta: "tr", data: $head_td_clave_prod_serv . $head_td_no_identificacion
            . $head_td_cantidad . $head_td_cve_unidad . $head_td_unidad . $head_td_valor_unitario .
            $head_td_importe . $head_td_descuento. $head_td_sub_total . $head_td_obj_imp);

        $body_tr = "";

        foreach ($conceptos as $concepto) {

            $concepto_pdf = $this->concepto_datos(concepto: $concepto,
                name_entidad_partida: $name_entidad_partida, link: $link);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al generar concepto',data:  $concepto_pdf);
            }

            $body_tr .= $concepto_pdf;


            $concepto_producto = $this->concepto_producto(concepto: $concepto, name_entidad_partida: $name_entidad_partida);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al generar concepto de producto',data:  $concepto_producto);
            }


            $body_tr .= $concepto_producto;
            if (errores::$error) {
                $error = (new errores())->error('Error al maquetar concepto', $body_tr);
                print_r($error);
                die('Error');
            }

            $body_tr .= $this->concepto_calculos(concepto: $concepto, name_entidad_partida: $name_entidad_partida);
            if (errores::$error) {
                $error = (new errores())->error('Error al maquetar concepto', $body_tr);
                print_r($error);
                die('Error');
            }

            $body_tr .= $this->concepto_numeros(concepto: $concepto, link: $link);
            if (errores::$error) {
                $error = (new errores())->error('Error al maquetar concepto', $body_tr);
                print_r($error);
                die('Error');
            }
        }

        $head = $this->html(etiqueta: "thead", data: $head_tr_1);
        $body = $this->html(etiqueta: "tbody", data: $body_tr);

        $table = $this->html(etiqueta: "table", data: $head . $body, class: "border");

        $this->pdf->WriteHTML($table);
    }


    final function complemento_pago(array $fc_doctos_relacionados, array $fc_pago_pagos, array $fc_pago_totales){

        $titulo = $this->html(etiqueta: "h2", data: "Totales", class: "negrita titulo");
        try {
            $this->pdf->WriteHTML($titulo);
        }
        catch (Throwable $e){
            return $this->error->error(mensaje: 'Error al generar pdf',data:  $e);
        }

        $head_td_base_16 = $this->html(etiqueta: "th",
            data: "Base IVA 16", class: "negrita border color");;
        if (errores::$error) {
            $error = (new errores())->error('Error al td', $head_td_base_16);
            print_r($error);
            die('Error');
        }

        $head_td_tr_16 = $this->html(etiqueta: "th",
            data: "Traslados IVA 16", class: "negrita border color");;
        if (errores::$error) {
            $error = (new errores())->error('Error al td', $head_td_tr_16);
            print_r($error);
            die('Error');
        }

        $head_td_monto_total_pagos = $this->html(etiqueta: "th",
            data: "Total Pagos", class: "negrita border color");;
        if (errores::$error) {
            $error = (new errores())->error('Error al td', $head_td_tr_16);
            print_r($error);
            die('Error');
        }

        $head_data = $head_td_base_16.$head_td_tr_16.$head_td_monto_total_pagos;
        $head_tr_1 = $this->html(etiqueta: "tr", data: $head_data);
        if (errores::$error) {
            $error = (new errores())->error('Error al tr', $head_tr_1);
            print_r($error);
            die('Error');
        }



        $head = $this->html(etiqueta: "thead", data: $head_tr_1);

        $body = '';
        foreach ($fc_pago_totales as $fc_pago_total) {

            $tds = '';

            $fc_pago_total_traslados_base_iva_16 = $fc_pago_total['fc_pago_total_total_traslados_base_iva_16'];
            $td = $this->html(etiqueta: "td", data: $fc_pago_total_traslados_base_iva_16, class: "border");
            if (errores::$error) {
                $error = (new errores())->error('Error al td', $td);
                print_r($error);
                die('Error');
            }
            $tds.=$td;

            $fc_pago_total_traslados_impuestos_iva_16 = $fc_pago_total['fc_pago_total_total_traslados_impuesto_iva_16'];
            $td = $this->html(etiqueta: "td", data: $fc_pago_total_traslados_impuestos_iva_16, class: "border");
            if (errores::$error) {
                $error = (new errores())->error('Error al td', $td);
                print_r($error);
                die('Error');
            }
            $tds.=$td;

            $fc_pago_total_monto_total_pagos = $fc_pago_total['fc_pago_total_monto_total_pagos'];
            $td = $this->html(etiqueta: "td", data: $fc_pago_total_monto_total_pagos, class: "border");
            if (errores::$error) {
                $error = (new errores())->error('Error al td', $td);
                print_r($error);
                die('Error');
            }
            $tds.=$td;

            $tr = $this->html(etiqueta: "tr", data: $tds);
            if (errores::$error) {
                $error = (new errores())->error('Error al tr', $head_tr_1);
                print_r($error);
                die('Error');
            }
            $body.=$tr;
        }



        $table = $this->html(etiqueta: "table", data: $head . $body, class: "border");

        $this->pdf->WriteHTML($table);


        /**
         *
         */





        $titulo = $this->html(etiqueta: "h1", data: "Pagos", class: "negrita titulo");
        try {
            $this->pdf->WriteHTML($titulo);
        }
        catch (Throwable $e){
            return $this->error->error(mensaje: 'Error al generar pdf',data:  $e);
        }

        $head_td_fecha_pago = $this->html(etiqueta: "th",
            data: "Fecha de Pago", class: "negrita border color");;
        if (errores::$error) {
            $error = (new errores())->error('Error al td', $head_td_fecha_pago);
            print_r($error);
            die('Error');
        }


        $head_td_forma_pago = $this->html(etiqueta: "th",
            data: "Forma de Pago", class: "negrita border color");;
        if (errores::$error) {
            $error = (new errores())->error('Error al td', $head_td_forma_pago);
            print_r($error);
            die('Error');
        }

        $head_td_moneda = $this->html(etiqueta: "th",
            data: "Moneda", class: "negrita border color");;
        if (errores::$error) {
            $error = (new errores())->error('Error al td', $head_td_moneda);
            print_r($error);
            die('Error');
        }

        $head_td_tipo_cambio = $this->html(etiqueta: "th",
            data: "Tipo de Cambio", class: "negrita border color");;
        if (errores::$error) {
            $error = (new errores())->error('Error al td', $head_td_tipo_cambio);
            print_r($error);
            die('Error');
        }

        $head_td_monto = $this->html(etiqueta: "th",
            data: "Monto de Pago", class: "negrita border color");;
        if (errores::$error) {
            $error = (new errores())->error('Error al td', $head_td_monto);
            print_r($error);
            die('Error');
        }

        $head_data = $head_td_fecha_pago.$head_td_forma_pago.$head_td_moneda.$head_td_tipo_cambio.$head_td_monto;


        $head_tr_1 = $this->html(etiqueta: "tr", data: $head_data);
        if (errores::$error) {
            $error = (new errores())->error('Error al tr', $head_tr_1);
            print_r($error);
            die('Error');
        }
        $head = $this->html(etiqueta: "thead", data: $head_tr_1);

        $body = '';
        foreach ($fc_pago_pagos as $fc_pago_pago) {
            $tds = '';

            $fc_pago_pago_fecha_pago = $fc_pago_pago['fc_pago_pago_fecha_pago'];
            $td = $this->html(etiqueta: "td", data: $fc_pago_pago_fecha_pago, class: "border");
            if (errores::$error) {
                $error = (new errores())->error('Error al td', $td);
                print_r($error);
                die('Error');
            }
            $tds.=$td;


            $cat_sat_forma_pago = $fc_pago_pago['cat_sat_forma_pago_codigo'].' '.$fc_pago_pago['cat_sat_forma_pago_descripcion'];
            $td = $this->html(etiqueta: "td", data: $cat_sat_forma_pago, class: "border");
            if (errores::$error) {
                $error = (new errores())->error('Error al td', $td);
                print_r($error);
                die('Error');
            }
            $tds.=$td;

            $moneda = $fc_pago_pago['cat_sat_moneda_codigo'].' '.$fc_pago_pago['cat_sat_moneda_descripcion'];
            $td = $this->html(etiqueta: "td", data: $moneda, class: "border");
            if (errores::$error) {
                $error = (new errores())->error('Error al td', $td);
                print_r($error);
                die('Error');
            }
            $tds.=$td;

            $tipo_cambio = $fc_pago_pago['com_tipo_cambio_monto'];
            $td = $this->html(etiqueta: "td", data: $tipo_cambio, class: "border");
            if (errores::$error) {
                $error = (new errores())->error('Error al td', $td);
                print_r($error);
                die('Error');
            }
            $tds.=$td;

            $monto = $fc_pago_pago['fc_pago_pago_monto'];
            $td = $this->html(etiqueta: "td", data: $monto, class: "border");
            if (errores::$error) {
                $error = (new errores())->error('Error al td', $td);
                print_r($error);
                die('Error');
            }
            $tds.=$td;

            $tr = $this->html(etiqueta: "tr", data: $tds);
            if (errores::$error) {
                $error = (new errores())->error('Error al tr', $head_tr_1);
                print_r($error);
                die('Error');
            }
            $body.=$tr;
        }

        $table = $this->html(etiqueta: "table", data: $head . $body, class: "border");

        $this->pdf->WriteHTML($table);



        $titulo = $this->html(etiqueta: "h1", data: "Documentos Relacionados", class: "negrita titulo");
        try {
            $this->pdf->WriteHTML($titulo);
        }
        catch (Throwable $e){
            return $this->error->error(mensaje: 'Error al generar pdf',data:  $e);
        }

        $head_td_fc_factura_folio = $this->html(etiqueta: "th",
            data: "Folio", class: "negrita border color");;
        if (errores::$error) {
            $error = (new errores())->error('Error al td', $head_td_fc_factura_folio);
            print_r($error);
            die('Error');
        }


        $head_td_fc_factura_uuid = $this->html(etiqueta: "th",
            data: "UUID", class: "negrita border color");;
        if (errores::$error) {
            $error = (new errores())->error('Error al td', $head_td_fc_factura_uuid);
            print_r($error);
            die('Error');
        }

        $head_td_num_parcialidad = $this->html(etiqueta: "th",
            data: "Num Parcialidad", class: "negrita border color");;
        if (errores::$error) {
            $error = (new errores())->error('Error al td', $head_td_num_parcialidad);
            print_r($error);
            die('Error');
        }

        $head_td_saldo_anterior = $this->html(etiqueta: "th",
            data: "Saldo Anterior", class: "negrita border color");;
        if (errores::$error) {
            $error = (new errores())->error('Error al td', $head_td_saldo_anterior);
            print_r($error);
            die('Error');
        }

        $head_td_importe_pagado = $this->html(etiqueta: "th",
            data: "Importe Pagado", class: "negrita border color");;
        if (errores::$error) {
            $error = (new errores())->error('Error al td', $head_td_importe_pagado);
            print_r($error);
            die('Error');
        }

        $head_td_saldo_insoluto = $this->html(etiqueta: "th",
            data: "Saldo", class: "negrita border color");;
        if (errores::$error) {
            $error = (new errores())->error('Error al td', $head_td_saldo_insoluto);
            print_r($error);
            die('Error');
        }


        $head_data = $head_td_fc_factura_folio.$head_td_fc_factura_uuid.$head_td_num_parcialidad.$head_td_saldo_anterior;
        $head_data .= $head_td_importe_pagado.$head_td_saldo_insoluto;


        $head_tr_1 = $this->html(etiqueta: "tr", data: $head_data);
        if (errores::$error) {
            $error = (new errores())->error('Error al tr', $head_tr_1);
            print_r($error);
            die('Error');
        }
        $head = $this->html(etiqueta: "thead", data: $head_tr_1);

        $body = '';
        foreach ($fc_doctos_relacionados as $fc_docto_relacionado) {
            $tds = '';

            $td = $this->html(etiqueta: "td", data: $fc_docto_relacionado['fc_factura_folio'], class: "border");
            if (errores::$error) {
                $error = (new errores())->error('Error al td', $td);
                print_r($error);
                die('Error');
            }
            $tds.=$td;

            $td = $this->html(etiqueta: "td", data: $fc_docto_relacionado['fc_factura_uuid'], class: "border");
            if (errores::$error) {
                $error = (new errores())->error('Error al td', $td);
                print_r($error);
                die('Error');
            }
            $tds.=$td;

            $td = $this->html(etiqueta: "td", data: $fc_docto_relacionado['fc_docto_relacionado_num_parcialidad'], class: "border");
            if (errores::$error) {
                $error = (new errores())->error('Error al td', $td);
                print_r($error);
                die('Error');
            }
            $tds.=$td;

            $td = $this->html(etiqueta: "td", data: $fc_docto_relacionado['fc_docto_relacionado_imp_saldo_ant'], class: "border");
            if (errores::$error) {
                $error = (new errores())->error('Error al td', $td);
                print_r($error);
                die('Error');
            }
            $tds.=$td;

            $td = $this->html(etiqueta: "td", data: $fc_docto_relacionado['fc_docto_relacionado_imp_pagado'], class: "border");
            if (errores::$error) {
                $error = (new errores())->error('Error al td', $td);
                print_r($error);
                die('Error');
            }
            $tds.=$td;

            $td = $this->html(etiqueta: "td", data: $fc_docto_relacionado['fc_docto_relacionado_imp_saldo_insoluto'], class: "border");
            if (errores::$error) {
                $error = (new errores())->error('Error al td', $td);
                print_r($error);
                die('Error');
            }
            $tds.=$td;

            $tr = $this->html(etiqueta: "tr", data: $tds);
            if (errores::$error) {
                $error = (new errores())->error('Error al tr', $head_tr_1);
                print_r($error);
                die('Error');
            }
            $body.=$tr;

        }


        $table = $this->html(etiqueta: "table", data: $head . $body, class: "border");

        $this->pdf->WriteHTML($table);



    }

    private function data_impuestos(array $data_impuesto, string $name_entidad_partida, string $tipo_impuesto){


        $base_imp = $this->init_impuesto(data_impuesto: $data_impuesto, name_entidad_partida: $name_entidad_partida,
            tipo_impuesto: $tipo_impuesto);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener impuesto base',data:  $base_imp);
        }

        $base = $this->base(fc_partida_cantidad: $base_imp->fc_partida_cantidad, fc_partida_descuento: $base_imp->fc_partida_descuento,
            fc_partida_valor_unitario:  $base_imp->fc_partida_valor_unitario);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al ajustar base',data:  $base);
        }

        $importe = $this->importe_moneda(base: $base,tasa_cuota:  $base_imp->tasa_cuota);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al ajustar base',data:  $importe);
        }


        $base = $this->monto_moneda(monto: $base);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al ajustar base',data:  $base);
        }

        $base_imp->base = $base;
        $base_imp->importe = $importe;

        return $base_imp;
    }

    /**
     * @param array $concepto
     * @param string $key
     * @return array|float
     */
    private function fc_partida_double(array $concepto, string $key): float|array
    {

        $key = trim($key);
        if($key === ''){
            return $this->error->error(mensaje: 'Error key esta vacio',data:  $key);
        }

        $keys = array($key);
        $valida = $this->valida->valida_existencia_keys(keys: $keys,registro:  $concepto);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar concepto',data:  $valida);
        }

        $fc_monto = $this->limpia_monto(monto: $concepto[$key]);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al limpiar monto',data:  $fc_monto);
        }
        $fc_monto = (float)$fc_monto;
        return round($fc_monto,2);
    }

    private function fc_partida_cantidad(array $concepto){
        $fc_partida_cantidad = $this->fc_partida_double(concepto: $concepto,key:  'fc_partida_cantidad');
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al limpiar monto',data:  $fc_partida_cantidad);
        }
    }

    private function fc_partida_valor_unitario(array $concepto){
        $fc_partida_valor_unitario = $this->fc_partida_double(concepto: $concepto,key:  'fc_partida_valor_unitario');
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al limpiar monto',data:  $fc_partida_valor_unitario);
        }
        return $fc_partida_valor_unitario;
    }

    /**
     * Integra un html para para pdf
     * @param string $etiqueta Etiqueta de html
     * @param string $data Datos del contenido de html
     * @param string $class Clase de css para pdf html
     * @param string $propiedades Propiedades estaticas de css
     * @return string|array
     * @version 10.38.2
     */
    private function html(string $etiqueta, string $data = "", string $class = "",
                          string $propiedades = ""): string|array
    {
        $etiqueta = trim($etiqueta);
        if($etiqueta === ''){
            return $this->error->error(mensaje: 'Error etiqueta esta vacia',data:  $etiqueta);
        }

        $class = trim($class);
        $class_html = '';
        if($class !== ''){
            $class_html = "class='$class'";
        }
        $propiedades = trim($propiedades);
        $data = trim($data);

        $html = "<$etiqueta $class_html $propiedades>$data</$etiqueta>";


        return trim($html);
    }

    private function init_impuesto(array $data_impuesto, string $name_entidad_partida, string $tipo_impuesto){
        $impuesto = $this->impuesto(data_impuesto: $data_impuesto);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener impuesto base',data:  $impuesto);
        }
        $tipo = $tipo_impuesto;
        if($tipo_impuesto === 'traslados') {
            $tipo = 'Traslado';
        }
        if($tipo_impuesto === 'retenidos') {
            $tipo = 'Retenciones';
        }
        $fc_partida_valor_unitario = round($data_impuesto[$name_entidad_partida.'_valor_unitario'],2);
        $fc_partida_cantidad = round($data_impuesto[$name_entidad_partida.'_cantidad'],2);
        $fc_partida_descuento = round($data_impuesto[$name_entidad_partida.'_descuento'],2);
        $tasa_cuota = round($data_impuesto['cat_sat_factor_factor'],6);
        $tipo_factor = $data_impuesto['cat_sat_tipo_factor_codigo'];

        $data = new stdClass();
        $data->impuesto = $impuesto;
        $data->tipo = $tipo;
        $data->fc_partida_valor_unitario = $fc_partida_valor_unitario;
        $data->fc_partida_cantidad = $fc_partida_cantidad;
        $data->fc_partida_descuento = $fc_partida_descuento;
        $data->tasa_cuota = $tasa_cuota;
        $data->tipo_factor = $tipo_factor;
        return $data;
    }

    private function importe_moneda(float $base, float $tasa_cuota){
        $importe = $base * $tasa_cuota;
        $importe = round($importe,2);
        $importe = $this->monto_moneda(monto: $importe);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al ajustar base',data:  $importe);
        }
        return $importe;
    }

    /**
     * Obtiene el tipo de impuesto
     * @param array $data_impuesto Datos del impuesto a integrar
     * @return string
     */
    private function impuesto(array $data_impuesto): string
    {
        return $data_impuesto['cat_sat_tipo_impuesto_descripcion'];
    }

    private function impuestos_concepto(string $base, string $importe, string $impuesto, string $tasa_cuota, string $tipo, string $tipo_factor){

        $td_impuesto = $this->td_centrado_cl_2(data: $impuesto);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar td', data: $td_impuesto);
        }

        $td_tipo = $this->td_centrado_cl_2(data: $tipo);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar td', data: $td_tipo);
        }


        $td_base = $this->td_centrado(data: $base);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar td', data: $td_base);
        }


        $td_tipo_factor = $this->td_centrado_cl_2(data: $tipo_factor);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar td', data: $td_tipo_factor);
        }

        $td_tasa_cuota = $this->td_centrado_cl_2(data: $tasa_cuota);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar td', data: $td_tasa_cuota);
        }

        $td_importe = $this->td_centrado(data: $importe);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar td', data: $td_importe);
        }

        $data = new stdClass();
        $data->td_impuesto = $td_impuesto;
        $data->td_tipo = $td_tipo;
        $data->td_base = $td_base;
        $data->td_tipo_factor = $td_tipo_factor;
        $data->td_tasa_cuota = $td_tasa_cuota;
        $data->td_importe = $td_importe;

        return $data;

    }

    private function integra_impuesto(array $concepto, string $name_entidad_partida, string $tipo_impuesto){
        $tr = '';
        $aplica = $this->aplica_impuesto(concepto: $concepto, tipo_impuesto: $tipo_impuesto);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error validar aplica impuesto',data:  $aplica);
        }
        if($aplica){
            $tr = $this->tr_impuestos_concepto(concepto: $concepto, name_entidad_partida: $name_entidad_partida,
                tipo_impuesto: $tipo_impuesto);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al generar tr',data:  $tr);
            }
        }
        return $tr;
    }

    /**
     * POR DOCUMENTAR EN WIKI
     * La función `keys_no_ob` de la clase PDF garantiza que ciertas llaves existan en el array de concepto que se
     * pasa como parámetro.
     *
     * @param array $concepto es el array que representa un concepto de facturación.
     * @return array Retorna el array de concepto con las llaves aseguradas.
     *
     * Esta función toma un array '$concepto' y asegura que ciertas llaves estén presentes en el mismo.
     * En este caso, la llave que se busca es 'fc_partida_descuento'.
     *
     * El proceso inicia creando un array '$keys_no_ob' con las llaves que se desean asegurar. Luego, para cada llave en '$keys_no_ob',
     * el método verifica si dicha llave existe en la matriz de concepto proporcionada. Si la llave no está presente, se incluye en el array
     * '$concepto' con un valor de 0.
     *
     * Finalmente, la función retorna el array modificado, asegurando así que todas las llaves del array '$keys_no_ob' están presentes.
     * Esta función es útil para prevenir errores en el manejo del array al intentar acceder a llaves que podrían no existir.
     * @version 24.1.0
     */
    private function keys_no_ob(array $concepto): array
    {

        $keys_no_ob = array('fc_partida_descuento');
        foreach ($keys_no_ob as $key_no_ob){
            if(!isset($concepto[$key_no_ob])){
                $concepto[$key_no_ob] = 0;
            }
        }
        return $concepto;
    }

    /**
     * Limpia un monto para dejarlo como double
     * @param int|float|string $monto Monto a ajustar
     * @return array|string
     * @version 2.12.0
     */
    private function limpia_monto(int|float|string $monto): array|string
    {
        $monto = trim($monto);
        if($monto === ''){
            return $this->error->error(mensaje: 'Error monto esta vacio', data: $monto);
        }
        $monto = str_replace(' ', '', $monto);
        $monto = str_replace('$', '', $monto);
        $monto = str_replace(',', '', $monto);

        if(!is_numeric($monto)){
            return $this->error->error(mensaje: 'Error monto debe ser un numero', data: $monto);
        }

        return str_replace(',', '', $monto);
    }

    /**
     * Ajusta un monto a dor formato moneda mx
     * @param int|float|string $monto Monto a formatear
     * @return array|false|string
     * @version 2.18.0
     */
    private function monto_moneda(int|float|string $monto): bool|array|string
    {
        $monto = trim($monto);
        if($monto === ''){
            return $this->error->error(mensaje: 'Error monto esta vacio', data: $monto);
        }
        $monto = $this->limpia_monto(monto: $monto);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al limpiar monto',data:  $monto);
        }
        $formatter = new NumberFormatter('es_MX',  NumberFormatter::CURRENCY);
        return $formatter->formatCurrency($monto, 'MXN');
    }

    private function table_uuid_rel(string $key_uuid, array $relacionada){
        $body_td_uuid = $this->html(etiqueta: "td", data: "UUID: $relacionada[$key_uuid]");
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar body_td_uuid', data: $body_td_uuid);
        }
        $body_tr_uuid = $this->html(etiqueta: "tr", data: $body_td_uuid);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar body_tr_uuid', data: $body_tr_uuid);
        }

        $body_uuid = $this->html(etiqueta: "tbody", data: $body_tr_uuid );
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar body_uuid', data: $body_uuid);
        }
        $table_uuid = $this->html(etiqueta: "table", data: $body_uuid);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar table_uuid', data: $table_uuid);
        }

        try {
            $this->pdf->WriteHTML($table_uuid);
        }
        catch (Throwable $e){
            return $this->error->error(mensaje: 'Error al generar pdf',data:  $e);
        }
        return $table_uuid;
    }

    private function td_centrado(string $data){
        $td = $this->html(etiqueta: "td", data: $data, class: "txt-center");
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al maqueta td', data: $td);
        }
        return $td;
    }

    private function td_centrado_cl_2(string $data){
        $td = $this->html(etiqueta: "td", data: $data, class: "txt-center", propiedades: "colspan='2'");
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al maqueta td', data: $td);
        }
        return $td;
    }

    public function totales(string $descuento, string $moneda, string $subtotal, string $forma_pago, string $imp_trasladados,
                            string $imp_retenidos, string $metodo_pago, string $total)
    {

        $subtotal = $this->monto_moneda(monto: $subtotal);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al limpiar monto',data:  $subtotal);
        }

        $descuento = $this->monto_moneda(monto: $descuento);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al limpiar monto',data:  $descuento);
        }

        $imp_trasladados = $this->monto_moneda(monto: $imp_trasladados);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al limpiar monto',data:  $imp_trasladados);
        }

        $imp_retenidos = $this->monto_moneda(monto: $imp_retenidos);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al limpiar monto',data:  $imp_retenidos);
        }

        $total = $this->monto_moneda(monto: $total);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al limpiar monto',data:  $total);
        }


        $body_td_1 = $this->html(etiqueta: "td", data: "Moneda:", class: "negrita");
        $body_td_2 = $this->html(etiqueta: "td", data: $moneda);
        $body_td_3 = $this->html(etiqueta: "td", data: "Subtotal:", class: "negrita");
        $body_td_4 = $this->html(etiqueta: "td", data: $subtotal);

        $body_td_5 = $this->html(etiqueta: "td", data: "Forma de pago:", class: "negrita", propiedades: "rowspan='3'");
        $body_td_6 = $this->html(etiqueta: "td", data: $forma_pago, propiedades: "rowspan='3'");
        $body_td_7 = $this->html(etiqueta: "td", data: "Descuento:", class: "negrita");
        $body_td_8 = $this->html(etiqueta: "td", data: $descuento);

        $body_td_9 = $this->html(etiqueta: "td", data: "Impuestos trasladados:", class: "negrita");
        $body_td_10 = $this->html(etiqueta: "td", data: $imp_trasladados);
        $body_td_11 = $this->html(etiqueta: "td", data: "Impuestos retenidos", class: "negrita");
        $body_td_12 = $this->html(etiqueta: "td", data: $imp_retenidos);

        $body_td_13 = $this->html(etiqueta: "td", data: "Método de pago:", class: "negrita");
        $body_td_14 = $this->html(etiqueta: "td", data: $metodo_pago);
        $body_td_15 = $this->html(etiqueta: "td", data: "Total", class: "negrita");
        $body_td_16 = $this->html(etiqueta: "td", data: $total);

        $body_tr_1 = $this->html(etiqueta: "tr", data: $body_td_1 . $body_td_2 . $body_td_3 . $body_td_4);
        $body_tr_2 = $this->html(etiqueta: "tr", data: $body_td_5 . $body_td_6 . $body_td_7 . $body_td_8);
        $body_tr_3 = $this->html(etiqueta: "tr", data: $body_td_9 . $body_td_10);
        $body_tr_4 = $this->html(etiqueta: "tr", data: $body_td_11 . $body_td_12);
        $body_tr_5 = $this->html(etiqueta: "tr", data: $body_td_13 . $body_td_14 . $body_td_15 . $body_td_16);

        $body = $this->html(etiqueta: "tbody", data: $body_tr_1 . $body_tr_2 . $body_tr_3 . $body_tr_4 . $body_tr_5);

        $table = $this->html(etiqueta: "table", data: $body, class: "mt-2");

        $this->pdf->WriteHTML($table);
    }

    private function tr_impuestos(string $base, string $importe, string $impuesto, string $tasa_cuota, string $tipo, string $tipo_factor){
        $tds = $this->impuestos_concepto(base: $base,importe:  $importe,impuesto:  $impuesto,tasa_cuota:  $tasa_cuota,tipo:  $tipo, tipo_factor: $tipo_factor);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar tds',data:  $tds);
        }


        $tr = $this->html(etiqueta: "tr", data: $tds->td_impuesto . $tds->td_tipo . $tds->td_base . $tds->td_tipo_factor . $tds->td_tasa_cuota .
            $tds->td_importe);

        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar tr',data:  $tr);
        }
        return $tr;
    }

    private function tr_impuestos_concepto(array $concepto, string $name_entidad_partida, string $tipo_impuesto){


        $impuestos = array();
        if(isset($concepto[$tipo_impuesto])){
            $impuestos = $concepto[$tipo_impuesto];
        }

        $tr = '';
        foreach ($impuestos as $data_impuesto) {
            $data_impuestos = $this->data_impuestos(data_impuesto: $data_impuesto,
                name_entidad_partida: $name_entidad_partida, tipo_impuesto: $tipo_impuesto);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al ajustar datos de impuestos', data: $data_impuestos);
            }

            $tr .= $this->tr_impuestos(base: $data_impuestos->base, importe: $data_impuestos->importe, impuesto: $data_impuestos->impuesto,
                tasa_cuota: $data_impuestos->tasa_cuota, tipo: $data_impuestos->tipo, tipo_factor: $data_impuestos->tipo_factor);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al generar tr', data: $tr);
            }
        }
        return $tr;

    }

    public function sellos(string $sello_cfdi, string $sello_sat)
    {

       // $this->pdf->WriteHTML("<br><br><br><br>");
        $sello = $this->html(etiqueta: "h2", data: "Sello digital del CFDI:", class: "negrita mt-5");
        $this->pdf->WriteHTML($sello);

        $firma = $this->html(etiqueta: "p", data: $sello_cfdi);
        $this->pdf->WriteHTML($firma);

        $sello = $this->html(etiqueta: "h2", data: "Sello digital del SAT:", class: "negrita");
        $this->pdf->WriteHTML($sello);

        $firma = $this->html(etiqueta: "p", data: $sello_sat);
        $this->pdf->WriteHTML($firma);
    }

    public function complementos(string $ruta_documento,string $complento, string $rfc_proveedor, string $fecha, string $no_certificado)
    {
        //$ruta_documento = '';

        $qr = $this->html(etiqueta: "img", data: "", propiedades: 'src = "' . $ruta_documento . '" width = "120"');
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar data', data: $qr);
        }

        $escribe_qr = true;

        if ($ruta_documento !== ""){
            if(!file_exists($ruta_documento)) {
                $escribe_qr = false;
            }
        }
        else{
            $escribe_qr = false;
        }
        if($escribe_qr){
            $this->pdf->WriteHTML($qr);
        }
        else{
            $this->pdf->WriteHTML('<div class="" width = "120" height="120"></div>');
        }

        $cadena_sat = $this->html(etiqueta: "h2", data: "Cadena Original del complemento de certificación digital del SAT:", class: "negrita");
        $this->pdf->WriteFixedPosHTML($cadena_sat, 50, $this->pdf->y - 35,200,10);
        $complento = $this->html(etiqueta: "p", data: $complento);
        $this->pdf->WriteFixedPosHTML($complento, 50, $this->pdf->y - 30,170,20);

        $body_td_1 = $this->html(etiqueta: "td", data: "RFC del proveedor de certificación:", class: "negrita");
        $body_td_2 = $this->html(etiqueta: "td", data: $rfc_proveedor);
        $body_td_3 = $this->html(etiqueta: "td", data: "Fecha y hora de certificación:", class: "negrita");
        $body_td_4 = $this->html(etiqueta: "td", data: $fecha, class: "text-center");
        $body_td_5 = $this->html(etiqueta: "td", data: "No. de serie del certificado SAT", class: "negrita");
        $body_td_6 = $this->html(etiqueta: "td", data: $no_certificado);

        $body_tr_1 = $this->html(etiqueta: "tr", data: $body_td_1 . $body_td_2 . $body_td_3 . $body_td_4);
        $body_tr_2 = $this->html(etiqueta: "tr", data: $body_td_5 . $body_td_6);

        $body = $this->html(etiqueta: "tbody", data: $body_tr_1 . $body_tr_2 );
        $table = $this->html(etiqueta: "table", data: $body, class: "mt-2");

        $this->pdf->WriteFixedPosHTML($table, 50, $this->pdf->y - 15,170,20);
    }

    public function guardar(string $nombre_documento, bool $descarga, bool $guarda)
    {

        if($descarga) {
            $this->pdf->Output($nombre_documento . '.pdf', 'D');
            return $nombre_documento;
        }
        if($guarda){
            $path_base = (new generales())->path_base;
            $path_base_archivos = $path_base.'archivos';
            if(!file_exists($path_base_archivos)){
                mkdir($path_base_archivos);
            }
            $nombre_documento = $path_base_archivos.'/'.$nombre_documento.'.pdf';


            $this->pdf->Output($nombre_documento, 'F');
            return $nombre_documento;
        }

        $this->pdf->Output( );
    }

    public function footer(string $descripcion)
    {
        $footer = $this->html(etiqueta: "h2", data: $descripcion, class: "footer negrita");

        $this->pdf->SetFooter($footer);
    }
}
