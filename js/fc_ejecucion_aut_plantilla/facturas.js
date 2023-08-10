let session_id = getParameterByName('session_id');
let adm_menu_id = getParameterByName('adm_menu_id');
let registro_id =getParameterByName('registro_id');
let frm = $(".frm-ejecuta");
let btn_timbra = $(".btn_timbra");
let btn_descarga = $(".btn_descarga");
let btn_envia_cfdi = $(".btn_envia_cfdi");

btn_descarga.click(function () {
    let url = "index.php?seccion=fc_ejecucion_aut_plantilla&accion=descarga";
    url = url+"&registro_id="+registro_id
    url = url+"&session_id="+session_id
    url = url+"&adm_menu_id="+adm_menu_id
    frm.attr("action",url);
});

btn_timbra.click(function () {
    let url = "index.php?seccion=fc_ejecucion_aut_plantilla&accion=timbra";
    url = url+"&registro_id="+registro_id
    url = url+"&session_id="+session_id
    url = url+"&adm_menu_id="+adm_menu_id
    frm.attr("action",url);
});
btn_envia_cfdi.click(function () {
    let url = "index.php?seccion=fc_ejecucion_aut_plantilla&accion=envia_cfdi";
    url = url+"&registro_id="+registro_id
    url = url+"&session_id="+session_id
    url = url+"&adm_menu_id="+adm_menu_id
    frm.attr("action",url);
});

