<?php

namespace gamboamartin\facturacion\controllers;

use config\generales;
use gamboamartin\errores\errores;
use Mpdf\Mpdf;
use Throwable;

final class pdf {

    public Mpdf $pdf;


    public function __construct(){
        try {
            $temporales = (new generales())->path_base . "archivos/tmp/";
            $this->pdf = new Mpdf(['tempDir' => $temporales]);
        }
        catch (Throwable $e){
            $error = (new errores())->error('Error al generar objeto de pdf', $e);
            print_r($error);
            die('Error');
        }

        $this->pdf->AddPage();
        $this->pdf->SetFont('Arial');
        $this->pdf->setSourceFile((new generales())->path_base.'archivos/plantillas/nomina.pdf'); // Sin extensión
        $template = $this->pdf->importPage(1);
        $this->pdf->useTemplate($template);

        $this->pdf->Output('ejemmplo'.'.pdf','D');
    }



}
