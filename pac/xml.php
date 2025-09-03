<?php
namespace gamboamartin\facturacion\pac;

header('Content-Type: application/json');
$objConexion = new _cnx_pac();


$apikey = 'apikey';
$opc = 0;

if (isset($_GET['opc']))
    $opc = $_GET['opc'];

switch($opc)
{
    case 1:
        $cfdi = file_get_contents('rsc/ejemplo_cfdi_01.xml');
        echo $objConexion->operacion_timbrar($apikey, $cfdi);
        break;
    case 2:
        $cfdi = file_get_contents('rsc/ejemplo_cfdi_01.xml');
        echo $objConexion->operacion_timbrarTFD($apikey, $cfdi);
        break;
    case 3:
        $cfdi = file_get_contents('rsc/ejemplo_cfdi_01.xml');
        echo $objConexion->operacion_timbrar3($apikey, $cfdi);
        break;
    case 4:
        $cfdi = file_get_contents('rsc/ejemplo_cfdi_02.xml');
        $keyPEM = file_get_contents('rsc/CSD_EKU9003173C9_key.pem');
        echo $objConexion->operacion_timbrarConSello($apikey, $cfdi, $keyPEM);
        break;
    case 5:
        $txtB64 = base64_encode( file_get_contents('rsc/ejemplo_cfdi.txt') );
        $keyPEM = file_get_contents('rsc/CSD_EKU9003173C9_key.pem');
        $cerPEM = file_get_contents('rsc/CSD_EKU9003173C9_cer.pem');
        echo $objConexion->operacion_timbrarTXT($apikey, $txtB64, $keyPEM, $cerPEM);
        break;
    case 6:
        $txtB64 = base64_encode( file_get_contents('rsc/ejemplo_cfdi.txt') );
        $keyPEM = file_get_contents('rsc/CSD_EKU9003173C9_key.pem');
        $cerPEM = file_get_contents('rsc/CSD_EKU9003173C9_cer.pem');
        $plantilla = '1';
        $logoB64 = base64_encode( file_get_contents('rsc/logo.png') );
        echo $objConexion->operacion_timbrarTXT2($apikey, $txtB64, $keyPEM, $cerPEM, $plantilla, $logoB64);
        break;
    case 7:
        $txtB64 = base64_encode( file_get_contents('rsc/ejemplo_cfdi.txt') );
        $keyPEM = file_get_contents('rsc/CSD_EKU9003173C9_key.pem');
        $cerPEM = file_get_contents('rsc/CSD_EKU9003173C9_cer.pem');
        echo $objConexion->operacion_timbrarTXT3($apikey, $txtB64, $keyPEM, $cerPEM);
        break;
    case 8:
        $jsonB64 = base64_encode( file_get_contents('rsc/ejemploJSON_cfdi.json') );
        $keyPEM = file_get_contents('rsc/CSD_EKU9003173C9_key.pem');
        $cerPEM = file_get_contents('rsc/CSD_EKU9003173C9_cer.pem');
        echo $objConexion->operacion_timbrarJSON($apikey, $jsonB64, $keyPEM, $cerPEM);
        break;
    case 9:
        $jsonB64 = base64_encode( file_get_contents('rsc/ejemploJSON_cfdi.json') );
        $keyPEM = file_get_contents('rsc/CSD_EKU9003173C9_key.pem');
        $cerPEM = file_get_contents('rsc/CSD_EKU9003173C9_cer.pem');
        $plantilla = '1';
        echo $objConexion->operacion_timbrarJSON2($apikey, $jsonB64, $keyPEM, $cerPEM, $plantilla);
        break;
    case 10:
        $jsonB64 = base64_encode( file_get_contents('rsc/ejemploJSON_cfdi.json') );
        $keyPEM = file_get_contents('rsc/CSD_EKU9003173C9_key.pem');
        $cerPEM = file_get_contents('rsc/CSD_EKU9003173C9_cer.pem');
        echo $objConexion->operacion_timbrarJSON3($apikey, $jsonB64, $keyPEM, $cerPEM);
        break;
    case 11:
        $jsonB64 = base64_encode( file_get_contents('rsc/JSON_cfdiRetenciones20.json') );
        $keyPEM = file_get_contents('rsc/CSD_EKU9003173C9_key.pem');
        $cerPEM = file_get_contents('rsc/CSD_EKU9003173C9_cer.pem');
        $plantilla = 'retenciones';
        echo $objConexion->operacion_timbrarRetencionJSON($apikey, $jsonB64, $keyPEM, $cerPEM, $plantilla);
        break;
    case 12:
        $cfdi = file_get_contents('rsc/XML_cfdi40Retenciones20.xml');
        echo $objConexion->operacion_timbrarRetencion($apikey, $cfdi);
        break;
    case 13:
        echo $objConexion->operacion_consultar_creditos($apikey);
        break;
    case 14:
        $uuid = '4a5dc24d-e0a9-4172-9fdd-38b2dfbd4435';
        $rfcEmisor = 'EKU9003173C9';
        $rfcReceptor = 'XAXX010101000';
        $total = 1.16;
        echo $objConexion->operacion_consultarEstadoSAT($apikey, $uuid, $rfcEmisor, $rfcReceptor, $total);
        break;
    case 15:
        $uuid = '4a5dc24d-e0a9-4172-9fdd-38b2dfbd4435';
        $rfcEmisor = 'EKU9003173C9';
        $rfcReceptor = 'XAXX010101000';
        $total = 1.16;
        $keyCSD = base64_encode( file_get_contents('rsc/CSD_EKU9003173C9.key') );
        $cerCSD = base64_encode( file_get_contents('rsc/CSD_EKU9003173C9.cer') );
        $passCSD = '12345678a';
        echo $objConexion->operacion_cancelar($apikey, $keyCSD, $cerCSD, $passCSD, $uuid, $rfcEmisor, $rfcReceptor, $total);
        break;
    case 16:
        $uuid = '4a5dc24d-e0a9-4172-9fdd-38b2dfbd4435';
        $rfcEmisor = 'EKU9003173C9';
        $rfcReceptor = 'XAXX010101000';
        $total = 1.16;
        $pfxB64 = base64_encode( file_get_contents('rsc/CSD_EKU9003173C9.pfx') );
        $passPFX = '12345678a';
        echo $objConexion->operacion_cancelarPFX($apikey, $pfxB64, $passPFX, $uuid, $rfcEmisor, $rfcReceptor, $total);
        break;
    case 17:
        $keyPEM = file_get_contents('rsc/CSD01_AAA010101AAA_key.pem');
        $cerPEM = file_get_contents('rsc/CSD01_AAA010101AAA_cer.pem');
        echo $objConexion->operation_consultarAutorizacionesPendientes($apikey, $keyPEM, $cerPEM);
        break;
    case 18:
        $keyPEM = file_get_contents('rsc/CSD01_AAA010101AAA_key.pem');
        $cerPEM = file_get_contents('rsc/CSD01_AAA010101AAA_cer.pem');
        $uuid = 'A2C2335B-552A-4CD4-A400-238443DBFC4B';
        $respuesta = 'Aceptar'; // 'Rechazar'
        echo $objConexion->operation_autorizarCancelacion($apikey, $keyPEM, $cerPEM, $uuid, $respuesta);
        break;
    case 19:
        $uuid = '4a5dc24d-e0a9-4172-9fdd-38b2dfbd4435';
        $rfcEmisor = 'EKU9003173C9';
        $rfcReceptor = 'XAXX010101000';
        $total = 1.16;
        $keyCSD = base64_encode( file_get_contents('rsc/CSD_EKU9003173C9.key') );
        $cerCSD = base64_encode( file_get_contents('rsc/CSD_EKU9003173C9.cer') );
        $passCSD = '12345678a';
        $motivo = '02';
        $folioSustitucion = '';
        echo $objConexion->operacion_cancelar2($apikey, $keyCSD, $cerCSD, $passCSD, $uuid, $rfcEmisor, $rfcReceptor, $total, $motivo, $folioSustitucion);
        break;
    case 20:
        $uuid = '4a5dc24d-e0a9-4172-9fdd-38b2dfbd4435';
        $rfcEmisor = 'EKU9003173C9';
        $rfcReceptor = 'XAXX010101000';
        $total = 1.16;
        $pfxB64 = base64_encode( file_get_contents('rsc/CSD_EKU9003173C9.pfx') );
        $passPFX = '12345678a';
        $motivo = '02';
        $folioSustitucion = '';
        echo $objConexion->operacion_cancelarPFX2($apikey, $pfxB64, $passPFX, $uuid, $rfcEmisor, $rfcReceptor, $total, $motivo, $folioSustitucion);
        break;
    default: header('Content-Type: text/html');
        echo 'OPERACION DESCONCIDA, DEFINA UNA OPERACIÓN VIA GET<br>';
        echo '1) timbrar<br>';
        echo '2) timbrar TFD<br>';
        echo '3) timbrar 3<br>';
        echo '4) timbrar TimbrarConSello<br>';
        echo '5) timbrar TXT (sólo XML)<br>';
        echo '6) timbrar TXT2 (XML y PDF)<br>';
        echo '7) timbrar TXT3 (XML y datos para PDF)<br>';
        echo '8) timbrar JSON (sólo XML)<br>';
        echo '9) timbrar JSON2 (XML y PDF)<br>';
        echo '10) timbrar JSON3 (XML y datos para PDF)<br>';
        echo '11) timbrar Retenciones JSON (XML y PDF)<br>';
        echo '12) timbrar Retenciones<br>';
        echo '13) consultar creditos<br>';
        echo '14) consultar estado SAT<br>';
        echo '15) cancelar<br>';
        echo '16) cancelarPFX<br>';
        echo '17) consultar autorizaciones pendientes<br>';
        echo '18) autorizar cancelación<br>';
        echo '19) cancelar2<br>';
        echo '20) cancelarPFX2<br>';
        echo '<br>p.e. http://localhost/../PHP/test.php?opc=5';
}
?>
