let url = getAbsolutePath();
let registro_id = getParameterByName('registro_id');
let session_id = getParameterByName('session_id');

let sl_dp_pais_id = $("#dp_pais_id");
let sl_cat_sat_moneda_id = $("#cat_sat_moneda_id");
let dp_pais_id = sl_dp_pais_id.val();
let cat_sat_moneda_id = sl_cat_sat_moneda_id.val();

sl_dp_pais_id.change(function(){
    dp_pais_id = $(this).val();
    adm_asigna_monedas(dp_pais_id);
});




function adm_asigna_monedas(dp_pais_id = ''){
    let sl_cat_sat_moneda_id = $("#cat_sat_moneda_id");

    let url = "index.php?seccion=cat_sat_moneda&ws=1&accion=get_cat_sat_moneda&dp_pais_id="+dp_pais_id+"&session_id="+session_id;

    $.ajax({
        type: 'GET',
        url: url,
    }).done(function( data ) {  // Función que se ejecuta si todo ha ido bien
        console.log(data);
        sl_cat_sat_moneda_id.empty();
        if(!isNaN(data.error)){
            if(data.error === 1){
                let msj = data.mensaje_limpio+' '+url;
                alert(msj);
                console.log(data);
                return false;
            }
        }
        integra_new_option("#cat_sat_moneda_id",'Seleccione una Moneda','-1');
        $.each(data.registros, function( index, cat_sat_moneda ) {

            integra_new_option("#cat_sat_moneda_id",cat_sat_moneda.cat_sat_moneda_codigo+' '+cat_sat_moneda.cat_sat_moneda_descripcion,cat_sat_moneda.cat_sat_moneda_id);
        });
        sl_cat_sat_moneda_id.val(cat_sat_moneda_id);
        sl_cat_sat_moneda_id.selectpicker('refresh');
    }).fail(function (jqXHR, textStatus, errorThrown){ // Función que se ejecuta si algo ha ido mal
        console.log(jqXHR);
        console.log(textStatus);
        console.log(errorThrown);
        console.log(url);
        alert('Error al ejecutar');
        console.log("The following error occured: "+ textStatus +" "+ errorThrown);
    });

}





