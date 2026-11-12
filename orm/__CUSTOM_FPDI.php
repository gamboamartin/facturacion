<?php

namespace gamboamartin\facturacion\models;

use config\generales;
use setasign\Fpdi\Fpdi;

class __CUSTOM_FPDI extends Fpdi
{

    public function __construct()
    {
        parent::__construct();
        $ruta_fonts = (new generales())->path_base.'plantillas/fonts/';
        $this->fontpath = $ruta_fonts;
    }

    /**
     * Calcula la cantidad de líneas que ocupará un texto dentro de una celda FPDF.
     *
     * Determina cuántas líneas necesita un texto para ajustarse al ancho especificado,
     * considerando la fuente y el tamaño actualmente configurados en el documento.
     *
     * Es útil para calcular previamente la altura necesaria de una celda con MultiCell()
     * y mantener alineadas verticalmente las columnas de una misma fila.
     *
     * @param float  $w   Ancho disponible para mostrar el texto (en milímetros).
     * @param string $txt Texto cuyo número de líneas se desea calcular.
     *
     * @return int Cantidad de líneas necesarias para mostrar el texto.
     */
    public function NbLines(float $w, string $txt): int
    {
        $cw = $this->CurrentFont['cw'];

        if ($w == 0) {
            $w = $this->w - $this->rMargin - $this->x;
        }

        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);

        if ($nb > 0 && $s[$nb - 1] == "\n") {
            $nb--;
        }

        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;

        while ($i < $nb) {
            $c = $s[$i];

            if ($c == "\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }

            if ($c == ' ') {
                $sep = $i;
            }

            $l += $cw[$c] ?? 0;

            if ($l > $wmax) {
                if ($sep == -1) {
                    if ($i == $j) {
                        $i++;
                    }
                } else {
                    $i = $sep + 1;
                }

                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
            } else {
                $i++;
            }
        }

        return $nl;
    }
}