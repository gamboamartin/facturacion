<?php
namespace links\secciones;
use config\generales;
use gamboamartin\errores\errores;
use gamboamartin\system\links_menu;
use stdClass;

class link_fc_factura extends links_menu
{
    public stdClass $links;


    private function link_fc_factura_alta(): array|string
    {
        $fc_factura_alta = $this->fc_factura_alta();
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener link de fc_factura alta', data: $fc_factura_alta);
        }

        $fc_factura_alta .= "&session_id=$this->session_id";
        return $fc_factura_alta;
    }

    public function link_fc_cfd_partida_alta_bd(int $fc_factura_id): string
    {

        $link = $this->link_con_id(accion: 'alta_partida_bd', registro_id: $fc_factura_id, seccion: 'fc_factura');
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al generar link', data: $link);
        }

        return $link;
    }

    protected function links(int $registro_id): stdClass|array
    {

        $links = parent::links(registro_id: $registro_id); // TODO: Change the autogenerated stub
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al generar links', data: $links);
        }

        $fc_factura_alta = $this->link_fc_factura_alta();
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al generar link', data: $fc_factura_alta);
        }
        $this->links->fc_factura->nueva_empresa = $fc_factura_alta;

        return $links;
    }

    private function fc_factura_alta(): string
    {
        return "./index.php?seccion=fc_factura&accion=alta";
    }

}
