<?php
declare(strict_types=1);
/** Ejercita el constructor heredado y las acciones reales, sin reemplazar clases. */
function controlador_ruta_qa(PDO $pdo,string $accion='lista',?int $id=null): \gamboamartin\facturacion\controllers\controlador_aguaban_ruta {
    $_GET=['seccion'=>'aguaban_ruta','accion'=>$accion,'adm_menu_id'=>'3'];
    if($id!==null)$_GET['registro_id']=(string)$id;
    $c=(new \base\controller\init())->controller($pdo,'aguaban_ruta');
    exigir_ruta($c instanceof \gamboamartin\facturacion\controllers\controlador_aguaban_ruta,'factory_controlador_real');
    return $c;
}
function render_ruta_qa(object $controlador,string $view): string {
    ob_start();try{include '/var/www/html/facturacion/views/aguaban_ruta/'.$view.'.php';return ob_get_contents();}finally{ob_end_clean();}
}
function dom_ruta_qa(string $html): DOMDocument {
    $dom=new DOMDocument();$prev=libxml_use_internal_errors(true);$dom->loadHTML('<?xml encoding="UTF-8">'.$html,LIBXML_NONET);libxml_clear_errors();libxml_use_internal_errors($prev);return $dom;
}
caso_ruta('constructor_factory_y_salida_framework_reales',function()use($pdo){
    $_SESSION=['usuario_id'=>23,'grupo_id'=>7];$_SERVER['REQUEST_METHOD']='GET';$_POST=[];
    $c=controlador_ruta_qa($pdo);$seguridad=new \base\seguridad(false);$init=new \base\controller\init();
    $view=(new ReflectionMethod($init,'include_action'))->invoke($init,true,$c,$seguridad);
    exigir_ruta(is_string($view)&&realpath($view)==='/var/www/html/facturacion/views/aguaban_ruta/lista.php','resolucion_view_real');
    $out=(new \base\controller\salida_data())->salida_ws($c,$view,$seguridad);
    exigir_ruta($out===true&&!\gamboamartin\errores\errores::$error,'despacho_real_sin_error');
    $html=render_ruta_qa($c,'lista');exigir_ruta(str_contains($html,'Rutas de medición')&&str_contains($html,'PAG-01'),'vista_lista_datos_reales');
});
caso_ruta('lecturas_controlador_alta_modifica_consulta_y_confirmacion_sin_writes',function()use($pdo,$admin){
    $r=$admin->crear_ruta(entrada_ruta('RUTA-UI'));$id=(int)$r['id'];$a=tablas_ruta($pdo);
    foreach(['alta','modifica','consulta','elimina']as$accion){$c=controlador_ruta_qa($pdo,$accion,$id);$out=$c->$accion(false);exigir_ruta($out['ok']===true,'lectura_'.$accion);$html=render_ruta_qa($c,$accion);exigir_ruta($html!==''&&str_contains($html,'<section'),'render_'.$accion);}
    exigir_ruta(tablas_ruta($pdo)===$a,'confirmacion_y_formularios_no_escriben');
});
caso_ruta('escrituras_controlador_rechazan_get_sin_modificar',function()use($pdo){
    $_SESSION=['usuario_id'=>23,'grupo_id'=>7];$_SERVER['REQUEST_METHOD']='GET';$_POST=[];$a=tablas_ruta($pdo);
    foreach(['alta_bd','modifica_bd','elimina_bd','status']as$accion){$c=controlador_ruta_qa($pdo,$accion,1);http_response_code(200);$out=$c->$accion(false);exigir_ruta($out['ok']===false&&http_response_code()===405,'get_rechazado_'.$accion);}
    exigir_ruta(tablas_ruta($pdo)===$a,'get_sin_writes');
});
caso_ruta('escrituras_controlador_rechazan_csrf_ausente_y_falso',function()use($pdo){
    $_SERVER['REQUEST_METHOD']='POST';$a=tablas_ruta($pdo);
    foreach(['alta_bd','modifica_bd','elimina_bd','status']as$accion){$c=controlador_ruta_qa($pdo,$accion,1);foreach([[],['csrf'=>'invalido'],['csrf'=>[]]]as$post){$_POST=$post;http_response_code(200);$out=$c->$accion(false);exigir_ruta($out['ok']===false&&http_response_code()===403,'csrf_rechazado_'.$accion);}}
    exigir_ruta(tablas_ruta($pdo)===$a,'csrf_invalido_sin_writes');
});
caso_ruta('alta_y_modificacion_controlador_post_reales_con_csrf',function()use($pdo){
    $c=controlador_ruta_qa($pdo,'alta_bd');$_SERVER['REQUEST_METHOD']='POST';$_POST=entrada_ruta('RUTA-POST')+['csrf'=>$_SESSION['aguaban_ruta_csrf']];$r=$c->alta_bd(false);exigir_ruta($r['ok']===true,'post_alta');$id=(int)$r['registro']['id'];
    $c=controlador_ruta_qa($pdo,'modifica_bd',$id);$_POST=entrada_ruta('RUTA-POST',['descripcion'=>'Ruta actualizada'])+['csrf'=>$_SESSION['aguaban_ruta_csrf']];$r=$c->modifica_bd(false);exigir_ruta($r['ok']===true&&$r['registro']['descripcion']==='Ruta actualizada','post_modifica');
});
caso_ruta('status_y_eliminacion_controlador_post_reales_con_csrf',function()use($pdo){
    $id=(int)$pdo->query("SELECT id FROM aguaban_ruta WHERE codigo='RUTA-POST'")->fetchColumn();$c=controlador_ruta_qa($pdo,'status',$id);$_SERVER['REQUEST_METHOD']='POST';$_POST=['csrf'=>$_SESSION['aguaban_ruta_csrf'],'status'=>'inactivo'];$r=$c->status(false);exigir_ruta($r['ok']===true&&$r['registro']['status']==='inactivo','post_status');
    $c=controlador_ruta_qa($pdo,'elimina_bd',$id);$_POST=['csrf'=>$_SESSION['aguaban_ruta_csrf']];$r=$c->elimina_bd(false);exigir_ruta($r['ok']===true&&$r['registro']['id']===$id,'post_eliminar');exigir_ruta((int)$pdo->query('SELECT COUNT(*) FROM aguaban_ruta WHERE id='.$id)->fetchColumn()===0,'eliminado_en_bd');
});
caso_ruta('controlador_revalida_usuario_y_acl_en_llamada_directa',function()use($pdo){
    $c=controlador_ruta_qa($pdo,'alta_bd');$_SESSION['usuario_id']=45;$_SESSION['grupo_id']=7;$_SERVER['REQUEST_METHOD']='POST';$_POST=entrada_ruta('NO-HTTP-ADMIN')+['csrf'=>$_SESSION['aguaban_ruta_csrf']];$a=tablas_ruta($pdo);http_response_code(200);$r=$c->alta_bd(false);exigir_ruta($r['ok']===false&&http_response_code()===403,'controlador_no_confia_en_sesion_grupo');exigir_ruta(tablas_ruta($pdo)===$a,'sin_escritura_directa');$_SESSION['usuario_id']=23;
});
caso_ruta('error_post_conserva_datos_formulario_sin_campos_internos',function()use($pdo){
    $c=controlador_ruta_qa($pdo,'alta_bd');$_SERVER['REQUEST_METHOD']='POST';$_POST=entrada_ruta('PAG-01',['descripcion'=>'Mi captura pendiente'])+['csrf'=>$_SESSION['aguaban_ruta_csrf']];$r=$c->alta_bd(false);exigir_ruta($r['ok']===false&&$c->ruta_config['registro']['descripcion']==='Mi captura pendiente','captura_conservada');$html=render_ruta_qa($c,'alta_bd');exigir_ruta(str_contains($html,'Mi captura pendiente')&&str_contains($html,'Ya existe'),'error_visible');exigir_ruta(!str_contains($html,'name="hash_version"')&&!str_contains($html,'name="usuario_alta_id"'),'internos_no_editables');
});
caso_ruta('vista_escapa_html_en_datos_errores_token_y_operadores',function()use($pdo){
    $c=controlador_ruta_qa($pdo,'alta');$x='\"><script>alert(1)</script>&';$c->ruta_config['registro']=entrada_ruta($x,['descripcion'=>$x,'sector'=>$x]);$c->ruta_config['csrf']=$x;$c->ruta_config['error']=$x;$c->ruta_config['operadores']=[['id'=>41,'descripcion'=>$x]];
    $html=render_ruta_qa($c,'alta');$dom=dom_ruta_qa($html);exigir_ruta($dom->getElementsByTagName('script')->length===0,'sin_script_inyectado');$xp=new DOMXPath($dom);exigir_ruta($xp->query('//input[@name="codigo"]')->item(0)->getAttribute('value')===$x,'codigo_escape_roundtrip');exigir_ruta($xp->query('//input[@name="csrf"]')->item(0)->getAttribute('value')===$x,'token_escape_roundtrip');exigir_ruta(str_contains($html,'&lt;script&gt;'),'html_codificado');
});
caso_ruta('vista_oculta_botones_sin_permiso_y_post_csrf',function()use($pdo){
    $c=controlador_ruta_qa($pdo,'alta');$c->ruta_config['permisos']=array_fill_keys(\gamboamartin\facturacion\models\_aguaban_ruta_acceso::ACCIONES,false);$html=render_ruta_qa($c,'alta');$xp=new DOMXPath(dom_ruta_qa($html));exigir_ruta($xp->query('//button[@type="submit"]')->length===0,'sin_submit_no_autorizado');exigir_ruta($xp->query('//form[@method="post"]/input[@name="csrf"]')->length===1,'post_con_csrf');
});
caso_ruta('url_y_paginacion_conservan_filtros_sin_inyectar_destino',function()use($pdo){
    $c=controlador_ruta_qa($pdo);$c->session_id='QA-FICTICIA';$url=$c->url_ruta('lista',null,['pagina'=>2,'sector'=>'Río & norte','seccion'=>'externo','accion'=>'elimina_bd']);parse_str(parse_url($url,PHP_URL_QUERY),$q);exigir_ruta($q['seccion']==='aguaban_ruta'&&$q['accion']==='lista'&&$q['sector']==='Río & norte'&&$q['pagina']==='2'&&$q['session_id']==='QA-FICTICIA','url_conserva_solo_filtros');
    $c->ruta_config['filtros']=['codigo'=>'PAG-','sector'=>'Río & norte'];$c->ruta_config['resultado']=['registros'=>[],'total'=>25,'pagina'=>1,'paginas'=>2,'limite'=>20];$html=render_ruta_qa($c,'lista');$xp=new DOMXPath(dom_ruta_qa($html));$next=$xp->query('//a[@rel="next"]')->item(0);exigir_ruta($next!==null,'siguiente_presente');parse_str(parse_url($next->getAttribute('href'),PHP_URL_QUERY),$p);exigir_ruta($p['codigo']==='PAG-'&&$p['sector']==='Río & norte'&&$p['pagina']==='2','paginacion_con_filtros');
});

caso_ruta('modifica_id_invalido_muestra_error_sin_fatal_en_vista',function()use($pdo){$c=controlador_ruta_qa($pdo,'modifica');$_GET['registro_id']='0';$r=$c->modifica(false);exigir_ruta($r['ok']===false,'id_cero_rechazado');$html=render_ruta_qa($c,'modifica');exigir_ruta(str_contains($html,'No se encontró la ruta')||str_contains($html,'identificador'),'error_visible_sin_fatal');});

caso_ruta('error_estado_revocado_preserva_captura_y_muestra_estado_real',function()use($pdo,$admin){$r=$admin->crear_ruta(entrada_ruta('RUTA-UI-STATUS'));$id=(int)$r['id'];$c=controlador_ruta_qa($pdo,'modifica_bd',$id);$a=accion_ruta($pdo,'status');$pdo->exec("UPDATE adm_accion_grupo SET status='inactivo' WHERE adm_accion_id=$a AND adm_grupo_id=7");try{$_SERVER['REQUEST_METHOD']='POST';$_POST=entrada_ruta('RUTA-UI-STATUS',['descripcion'=>'Captura para corregir','status'=>'inactivo'])+['csrf'=>$_SESSION['aguaban_ruta_csrf']];$out=$c->modifica_bd(false);exigir_ruta($out['ok']===false&&http_response_code()===403,'cambio_estado_rechazado');exigir_ruta($c->ruta_config['registro']['descripcion']==='Captura para corregir'&&$c->ruta_config['registro']['status']==='activo','captura_preservada_estado_real');$html=render_ruta_qa($c,'modifica_bd');$xp=new DOMXPath(dom_ruta_qa($html));$hidden=$xp->query('//input[@type="hidden" and @name="status"]')->item(0);exigir_ruta($hidden!==null&&$hidden->getAttribute('value')==='activo','hidden_estado_real');}finally{$pdo->exec("UPDATE adm_accion_grupo SET status='activo' WHERE adm_accion_id=$a AND adm_grupo_id=7");}});

caso_ruta('navegacion_real_sidebar_y_menu_superior_sin_avisos', function () use ($pdo) {
    $_SESSION=['usuario_id'=>23,'grupo_id'=>7,'activa'=>1];
    $_SERVER['REQUEST_METHOD']='GET'; $_POST=[];
    set_error_handler(static function (int $severity, string $message, string $file, int $line): never {
        throw new ErrorException($message, 0, $severity, $file, $line);
    });
    try {
        $controlador=controlador_ruta_qa($pdo, 'lista');
        exigir_ruta($controlador->menu_permitido!==[] && $controlador->secciones_permitidas!==[], 'navegacion_poblada');
        $links_menu=(new \gamboamartin\system\links_menu($pdo, -1))->links;
        ob_start();
        try {
            include '/var/www/html/facturacion/templates/nav/_sidebar.php';
            $sidebar=ob_get_contents();
        } finally { ob_end_clean(); }
        ob_start();
        try {
            include '/var/www/html/facturacion/templates/nav/_menu_superior.php';
            $superior=ob_get_contents();
        } finally { ob_end_clean(); }
        exigir_ruta(!\gamboamartin\errores\errores::$error, 'framework_navegacion_sin_error');
        $xpath=new DOMXPath(dom_ruta_qa($sidebar));
        exigir_ruta($xpath->query('//aside[contains(@class,"clientes-sidebar")]')->length===1, 'sidebar_visible');
        exigir_ruta(str_contains($sidebar, 'Rutas') && str_contains($sidebar, 'seccion=aguaban_ruta'), 'rutas_navegables_en_sidebar');
        exigir_ruta(str_contains($superior, 'Aguaban') && str_contains($superior, 'aria-current="page"'), 'aguaban_activo_menu_superior');
        exigir_ruta((int)$controlador->adm_menu_id!==3, 'menu_resuelto_por_seccion_no_get_ajeno');
    } finally { restore_error_handler(); }
});
