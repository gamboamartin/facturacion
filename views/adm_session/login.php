<?php
declare(strict_types=1);

use config\generales;

/** @var controllers\controlador_adm_session $controlador */

$usa_login_nuevo = false;
if(property_exists(generales::class, 'login_ivitec_v2')){
    $usa_login_nuevo = (bool)generales::$login_ivitec_v2;
}

if($usa_login_nuevo){
    include __DIR__ . '/login_ivitec.php';
    return;
}
?>
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="top-title">
                <ul class="breadcrumb">
                    <li class="item"><a href="index.php"> Inicio </a></li>
                    <li class="item"> Login</li>
                </ul>
                <h1 class="h-side-title page-title page-title-big text-color-primary">Iniciar Sesión</h1>
            </div> <!-- /. content-header -->
            <!-- /. widget-AVAILABLE PACKAGES -->
            <div class="row-cols-12">
                <div class="col-lg-3"></div>
                <div class="col-lg-6">
                    <div class="widget  widget-box box-container widget-form form-main" id="form">
                        <div class="widget-header">
                            <h2>Iniciar Sesión</h2>
                        </div>
                        <form method="post" action="./index.php?seccion=adm_session&accion=loguea" class="form-additional">
                            <div class="control-group">
                                <label class="control-label" for="inputUsername2">Nombre de usuario</label>
                                <div class="controls">
                                    <input type="text" name="user" value="" class="form-control" id="inputUsername2" placeholder="Nombre de usuario" />
                                </div>
                            </div>
                            <div class="control-group">
                                <label class="control-label" for="inputPassword1">Contraseña</label>
                                <div class="controls">
                                    <input type="password" name="password" value="" class="form-control" id="inputPassword1" placeholder="Contraseña" />
                                </div>
                            </div>

                            <div class="control-group">
                                <div class="controls">
                                    <button type="submit" class="btn btn-danger">Iniciar Sesión</button>
                                    <button type="reset" class="btn btn-default">Limpiar</button><br>
                                    <a href="#"><em>¿Olvidaste tu contraseña?</em></a>
                                </div>
                            </div>
                        </form>
                    </div><!-- /.widget-form-->
                </div>
            </div>
        </div><!-- /.center-content -->
    </div>
</div>