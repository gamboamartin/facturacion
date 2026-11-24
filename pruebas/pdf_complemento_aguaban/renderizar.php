<?php
declare(strict_types=1);
set_error_handler(static function(int $severity,string $message,string $file,int $line):bool {
    if (!(error_reporting() & $severity)) { return false; }
    throw new ErrorException($message,0,$severity,$file,$line);
});
$source=(string)getenv('PDF_SOURCE');
$loader=require getenv('PDF_VENDOR').'/autoload.php';
$loader->addPsr4('gamboamartin\\facturacion\\models\\',$source.'/orm',true);
$data=json_decode(file_get_contents((string)getenv('PDF_DATA')),true,512,JSON_THROW_ON_ERROR);
if (!empty($data['_fixture_xml'])) {
    require $source.'/pruebas/regresion/pdf_complemento/fixture.php';
    $data=\gamboamartin\facturacion\models\_datos_pdf_complemento::desde_xml(xmlComplementoPrueba(), '', '');
}
$renderer=new \gamboamartin\facturacion\models\_PDF\aguaban\_complemento_pago(
    (string)getenv('PDF_TEMPLATE'),(string)getenv('PDF_CACHE'));
$pdf=$renderer->render($data);
if (!str_starts_with($pdf,'%PDF-') || strlen($pdf)<1000) { throw new RuntimeException('PDF incompleto'); }
if (file_put_contents((string)getenv('PDF_OUTPUT'),$pdf)!==strlen($pdf)) { throw new RuntimeException('No se guardó PDF completo'); }
echo json_encode(['estado'=>'PDF_SINTETICO_GENERADO','bytes'=>strlen($pdf)],JSON_THROW_ON_ERROR),PHP_EOL;
