<?php
namespace gamboamartin\facturacion\tests\orm\_timbra_nomina\_datos;

use base\conexion;
use gamboamartin\facturacion\tests\base_test;
use gamboamartin\js_base\base;
use gamboamartin\test\test;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\_timbra_nomina\_datos;
use PDO;
use stdClass;

final class datos_reciboTest extends test
{
    private PDO $pdo;

    protected function setUp(): void
    {
        errores::$error = false;
        $this->pdo = $this->link;

    }

    protected function tearDown(): void
    {
        errores::$error = false;
    }



    /** Helper: inserta un layout de nómina y retorna su ID */
    private function seedLayoutNom(PDO $pdo, ?string $descripcion = 'LAYOUT TEST'): int
    {
        $insert_fc_layout_nom = (new base_test())->insert_fc_layout_nom($this->pdo);
        return (int)$pdo->lastInsertId();
    }

    /** Helper: inserta una fila de layout y retorna su ID */
    private function seedRowLayout(PDO $pdo): int
    {
        $insert_fc_row_layout = (new base_test())->insert_fc_row_layout($this->pdo);
        return (int)$pdo->lastInsertId();
    }

    /** Caso feliz: existe fc_row_layout y su fc_layout_nom asociado */
    public function testDatosRecibo_Exito(): void
    {
        $delete_fc_row_nomina = (new base_test())->delete_fc_row_nomina($this->pdo);
        if(errores::$error){
            $error = (new errores())->error('Error',$delete_fc_row_nomina);
            print_r($error);
            exit;
        }

        $delete_fc_row_layout = (new base_test())->delete_fc_row_layout($this->pdo);
        if(errores::$error){
            $error = (new errores())->error('Error',$delete_fc_row_layout);
            print_r($error);
            exit;
        }

        $delete_fc_layout_nom = (new base_test())->delete_fc_layout_nom($this->pdo);
        if(errores::$error){
            $error = (new errores())->error('Error',$delete_fc_layout_nom);
            print_r($error);
            exit;
        }

        $delete_fc_cer_pem = (new base_test())->delete_fc_cer_pem($this->pdo);
        if(errores::$error){
            $error = (new errores())->error('Error',$delete_fc_cer_pem);
            print_r($error);
            exit;
        }

        $delete_fc_cer_csd = (new base_test())->delete_fc_cer_csd($this->pdo);
        if(errores::$error){
            $error = (new errores())->error('Error',$delete_fc_cer_csd);
            print_r($error);
            exit;
        }

        $delete_fc_key_pem = (new base_test())->delete_fc_key_pem($this->pdo);
        if(errores::$error){
            $error = (new errores())->error('Error',$delete_fc_key_pem);
            print_r($error);
            exit;
        }

        $delete_fc_key_csd = (new base_test())->delete_fc_key_csd($this->pdo);
        if(errores::$error){
            $error = (new errores())->error('Error',$delete_fc_key_csd);
            print_r($error);
            exit;
        }

        $delete_fc_factura_documento = (new base_test())->delete_fc_factura_documento($this->pdo);
        if(errores::$error){
            $error = (new errores())->error('Error',$delete_fc_factura_documento);
            print_r($error);
            exit;
        }

        $delete_doc_version = (new base_test())->delete_doc_version($this->pdo);
        if(errores::$error){
            $error = (new errores())->error('Error',$delete_doc_version);
            print_r($error);
            exit;
        }

        $delete_com_cliente_documento = (new base_test())->delete_com_cliente_documento($this->pdo);
        if(errores::$error){
            $error = (new errores())->error('Error',$delete_com_cliente_documento);
            print_r($error);
            exit;
        }

        $delete_not_adjunto = (new base_test())->delete_not_adjunto($this->pdo);
        if(errores::$error){
            $error = (new errores())->error('Error',$delete_not_adjunto);
            print_r($error);
            exit;
        }

        $delete_doc_documento = (new base_test())->delete_doc_documento($this->pdo);
        if(errores::$error){
            $error = (new errores())->error('Error',$delete_doc_documento);
            print_r($error);
            exit;
        }

        $insert_doc_documento = (new base_test())->insert_doc_documento($this->pdo);
        if(errores::$error){
            $error = (new errores())->error('Error',$insert_doc_documento);
            print_r($error);
            exit;
        }

        $layoutNomId = $this->seedLayoutNom($this->pdo, 'NOMINA SEPTIEMBRE');
        $rowLayoutId = $this->seedRowLayout($this->pdo);

        $svc = new _datos();
        $out = $svc->datos_recibo($this->pdo, $rowLayoutId);

        $this->assertFalse(errores::$error, 'No debe activar bandera de error en éxito');
        $this->assertInstanceOf(stdClass::class, $out);
        $this->assertTrue(isset($out->fc_layout_nom), 'Debe incluir fc_layout_nom');
        $this->assertTrue(isset($out->fc_row_layout), 'Debe incluir fc_row_layout');

        $this->assertEquals($layoutNomId, (int)$out->fc_layout_nom->id);
        $this->assertEquals($rowLayoutId, (int)$out->fc_row_layout->id);
        $this->assertEquals($layoutNomId, (int)$out->fc_row_layout->fc_layout_nom_id);
    }

    /** Parámetro inválido: id <= 0 */
    public function testDatosRecibo_IdInvalido(): void
    {
        $svc = new _datos();
        $out = $svc->datos_recibo($this->pdo, 0);

        $this->assertIsArray($out);
        $this->assertTrue(errores::$error);
        $this->assertStringContainsString('Parámetro $fc_row_layout_id inválido', (string)$out['mensaje']);
    }

    /** No existe la fila solicitada */
    public function testDatosRecibo_RowNoExiste(): void
    {
        $svc = new _datos();
        $out = $svc->datos_recibo($this->pdo, 999999);

        $this->assertIsArray($out, 'Debe regresar arreglo de error si no existe la fila');
        $this->assertTrue(errores::$error);
        $this->assertStringContainsString('fc_row_layout', (string)$out['mensaje']);
    }

    /** Existe la fila pero SIN fc_layout_nom_id válido (NULL o <=0) */
    public function testDatosRecibo_RowSinLayoutNomId(): void
    {
        // Insertar una fila con fc_layout_nom_id inválido: para SQLite podemos setear 0
        $stmt = $this->pdo->prepare('INSERT INTO fc_row_layout (fc_layout_nom_id) VALUES (0)');
        $stmt->execute();
        $rowLayoutId = (int)$this->pdo->lastInsertId();

        $svc = new _datos();
        errores::$error = false;
        $out = $svc->datos_recibo($this->pdo, $rowLayoutId);

        $this->assertIsArray($out);
        $this->assertTrue(errores::$error);
        $this->assertStringContainsString('fc_layout_nom_id ausente o inválido', (string)$out['mensaje']);
    }

    /** Existe fc_row_layout con fc_layout_nom_id pero NO existe el fc_layout_nom asociado */
    public function testDatosRecibo_LayoutNomNoExiste(): void
    {
        // Crear fila con referencia a un layout inexistente
        $rowId = $this->seedRowLayout($this->pdo, 123456); // no existe

        $svc = new _datos();
        errores::$error = false;
        $out = $svc->datos_recibo($this->pdo, $rowId);

        $this->assertIsArray($out);
        $this->assertTrue(errores::$error);
        $this->assertStringContainsString('fc_layout_nom', (string)$out['mensaje']);
    }
}