let url = getAbsolutePath();

document.write(`<script src="${url}/js/direcciones.js"></script>`)

let session_id = getParameterByName('session_id');


let sl_org_empresa_id = $("#org_empresa_id");

let txt_fecha_inicio_operaciones = $('#fecha_inicio_operaciones');


sl_org_empresa_id.change(function(){

    let selected = $(this).find('option:selected');

    fecha_inicio_operaciones = selected.data('org_empresa_fecha_inicio_operaciones');


    let dp_pais_id_pa = selected.data('dp_pais_id');
    if(dp_pais_id_pa !== ''){
        dp_pais_id = dp_pais_id_pa;
    }
    $("#dp_pais_id").val(dp_pais_id_pa).selectpicker('refresh');


    let dp_estado_id_pa = selected.data('dp_estado_id');
    if(dp_estado_id_pa !== ''){
        dp_estado_id = dp_estado_id_pa;
    }

    let dp_municipio_id_pa = selected.data('dp_municipio_id');
    if(dp_municipio_id_pa !== ''){
        dp_municipio_id = dp_municipio_id_pa;
    }


    dp_cp_id = selected.data('dp_cp_id');
    dp_colonia_postal_id = selected.data('dp_colonia_postal_id');
    dp_calle_pertenece_id = selected.data('dp_calle_pertenece_id');

    if(fecha_inicio_operaciones !== '0000-00-00'){
        txt_fecha_inicio_operaciones.val(fecha_inicio_operaciones);
    }

    dp_asigna_estados(dp_pais_id,dp_estado_id);
    dp_asigna_municipios(dp_estado_id,dp_municipio_id);
    dp_asigna_cps(dp_municipio_id,dp_cp_id);
    dp_asigna_colonias_postales(dp_cp_id,dp_colonia_postal_id);
    dp_asigna_calles_pertenece(dp_colonia_postal_id,dp_calle_pertenece_id)


});

