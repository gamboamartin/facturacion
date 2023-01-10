let sl_dp_pais = $("#dp_pais_id");
let sl_dp_estado = $("#dp_estado_id");
let sl_dp_municipio = $("#dp_municipio_id");
let sl_dp_cp = $("#dp_cp_id");
let sl_colonia_postal = $("#dp_colonia_postal_id");
let sl_dp_calle_pertenece = $("#dp_calle_pertenece_id");
let sl_dp_calle_pertenece_entre1 = $("#dp_calle_pertenece_entre1_id");
let sl_dp_calle_pertenece_entre2 = $("#dp_calle_pertenece_entre2_id");

let fecha_inicio_operaciones = $("#fecha_inicio_operaciones");
let fecha_ultimo_cambio_sat = $("#fecha_ultimo_cambio_sat");

let asigna_estados = (dp_pais_id = '') => {
    let url = get_url("dp_estado","get_estado", {dp_pais_id: dp_pais_id});

    get_data(url, function (data) {
        sl_dp_estado.empty();
        sl_dp_municipio.empty();
        sl_dp_cp.empty();
        sl_colonia_postal.empty();
        sl_dp_calle_pertenece.empty();
        sl_dp_calle_pertenece_entre1.empty();
        sl_dp_calle_pertenece_entre2.empty();

        integra_new_option(sl_dp_estado,'Seleccione un estado','-1');
        integra_new_option(sl_dp_municipio,'Seleccione un municipio','-1');
        integra_new_option(sl_dp_cp,'Seleccione un codigo postal','-1');
        integra_new_option(sl_colonia_postal,'Seleccione una colonia postal','-1');
        integra_new_option(sl_dp_calle_pertenece,'Seleccione una calle','-1');
        integra_new_option(sl_dp_calle_pertenece_entre1,'Seleccione una calle','-1');
        integra_new_option(sl_dp_calle_pertenece_entre2,'Seleccione una calle','-1');

        $.each(data.registros, function( index, dp_estado ) {
            integra_new_option(sl_dp_estado,dp_estado.dp_estado_descripcion_select,dp_estado.dp_estado_id);
        });
        sl_dp_estado.selectpicker('refresh');
        sl_dp_municipio.selectpicker('refresh');
        sl_dp_cp.selectpicker('refresh');
        sl_colonia_postal.selectpicker('refresh');
        sl_dp_calle_pertenece.selectpicker('refresh');
        sl_dp_calle_pertenece_entre1.selectpicker('refresh');
        sl_dp_calle_pertenece_entre2.selectpicker('refresh');
    });
}

let asigna_municipios = (dp_estado_id = '') => {
    let url = get_url("dp_municipio","get_municipio", {dp_estado_id: dp_estado_id});

    get_data(url, function (data) {
        sl_dp_municipio.empty();
        sl_dp_cp.empty();
        sl_colonia_postal.empty();
        sl_dp_calle_pertenece.empty();
        sl_dp_calle_pertenece_entre1.empty();
        sl_dp_calle_pertenece_entre2.empty();

        integra_new_option(sl_dp_municipio,'Seleccione un municipio','-1');
        integra_new_option(sl_dp_cp,'Seleccione un codigo postal','-1');
        integra_new_option(sl_colonia_postal,'Seleccione una colonia postal','-1');
        integra_new_option(sl_dp_calle_pertenece,'Seleccione una calle','-1');
        integra_new_option(sl_dp_calle_pertenece_entre1,'Seleccione una calle','-1');
        integra_new_option(sl_dp_calle_pertenece_entre2,'Seleccione una calle','-1');

        $.each(data.registros, function( index, dp_municipio ) {
            integra_new_option(sl_dp_municipio,dp_municipio.dp_municipio_descripcion_select,dp_municipio.dp_municipio_id);
        });
        sl_dp_municipio.selectpicker('refresh');
        sl_dp_cp.selectpicker('refresh');
        sl_colonia_postal.selectpicker('refresh');
        sl_dp_calle_pertenece.selectpicker('refresh');
        sl_dp_calle_pertenece_entre1.selectpicker('refresh');
        sl_dp_calle_pertenece_entre2.selectpicker('refresh');
    });
}

let asigna_codigos_postales = (dp_municipio_id = '') => {
    let url = get_url("dp_cp","get_cp", {dp_municipio_id: dp_municipio_id});

    get_data(url, function (data) {
        sl_dp_cp.empty();
        sl_colonia_postal.empty();
        sl_dp_calle_pertenece.empty();
        sl_dp_calle_pertenece_entre1.empty();
        sl_dp_calle_pertenece_entre2.empty();

        integra_new_option(sl_dp_cp,'Seleccione un codigo postal','-1');
        integra_new_option(sl_colonia_postal,'Seleccione una colonia postal','-1');
        integra_new_option(sl_dp_calle_pertenece,'Seleccione una calle','-1');
        integra_new_option(sl_dp_calle_pertenece_entre1,'Seleccione una calle','-1');
        integra_new_option(sl_dp_calle_pertenece_entre2,'Seleccione una calle','-1');

        $.each(data.registros, function( index, dp_cp ) {
            integra_new_option(sl_dp_cp,dp_cp.dp_cp_descripcion_select,dp_cp.dp_cp_id);
        });
        sl_dp_cp.selectpicker('refresh');
        sl_colonia_postal.selectpicker('refresh');
        sl_dp_calle_pertenece.selectpicker('refresh');
        sl_dp_calle_pertenece_entre1.selectpicker('refresh');
        sl_dp_calle_pertenece_entre2.selectpicker('refresh');
    });
}

let asigna_colonias_postales = (dp_cp_id = '') => {
    let url = get_url("dp_colonia_postal","get_colonia_postal", {dp_cp_id: dp_cp_id});

    get_data(url, function (data) {
        sl_colonia_postal.empty();
        sl_dp_calle_pertenece.empty();
        sl_dp_calle_pertenece_entre1.empty();
        sl_dp_calle_pertenece_entre2.empty();

        integra_new_option(sl_colonia_postal,'Seleccione una colonia postal','-1');
        integra_new_option(sl_dp_calle_pertenece,'Seleccione una calle','-1');
        integra_new_option(sl_dp_calle_pertenece_entre1,'Seleccione una calle','-1');
        integra_new_option(sl_dp_calle_pertenece_entre2,'Seleccione una calle','-1');

        $.each(data.registros, function( index, dp_colonia_postal ) {
            integra_new_option(sl_colonia_postal,dp_colonia_postal.dp_colonia_postal_descripcion_select,dp_colonia_postal.dp_colonia_postal_id);
        });
        sl_colonia_postal.selectpicker('refresh');
        sl_dp_calle_pertenece.selectpicker('refresh');
        sl_dp_calle_pertenece_entre1.selectpicker('refresh');
        sl_dp_calle_pertenece_entre2.selectpicker('refresh');
    });
}

/* Comentado por IA
let asigna_calles = (dp_calle_pertenece_id = '') => {
    let url = get_url("dp_calle_pertenece","get_calle_pertenece", {dp_calle_pertenece_id: dp_calle_pertenece_id});

    get_data(url, function (data) {
        sl_dp_calle_pertenece.empty();
        sl_dp_calle_pertenece_entre1.empty();
        sl_dp_calle_pertenece_entre2.empty();

        integra_new_option(sl_dp_calle_pertenece,'Seleccione una calle','-1');
        integra_new_option(sl_dp_calle_pertenece_entre1,'Seleccione una calle','-1');
        integra_new_option(sl_dp_calle_pertenece_entre2,'Seleccione una calle','-1');

        $.each(data.registros, function( index, calle ) {
            integra_new_option(sl_dp_calle_pertenece,calle.dp_calle_pertenece_descripcion_select,calle.dp_calle_pertenece_id);
            integra_new_option(sl_dp_calle_pertenece_entre1,calle.dp_calle_pertenece_descripcion_select,calle.dp_calle_pertenece_id);
            integra_new_option(sl_dp_calle_pertenece_entre2,calle.dp_calle_pertenece_descripcion_select,calle.dp_calle_pertenece_id);
        });

        sl_dp_calle_pertenece.selectpicker('refresh');
        sl_dp_calle_pertenece_entre1.selectpicker('refresh');
        sl_dp_calle_pertenece_entre2.selectpicker('refresh');
    });
}

*/

let asigna_calles = (dp_colonia_postal_id = '') => {
    let url = get_url("dp_calle_pertenece","get_calle_pertenece", {dp_colonia_postal_id: dp_colonia_postal_id});

    get_data(url, function (data) {
        sl_dp_calle_pertenece.empty();
        sl_dp_calle_pertenece_entre1.empty();
        sl_dp_calle_pertenece_entre2.empty();

        integra_new_option(sl_dp_calle_pertenece,'Seleccione una calle','-1');
        integra_new_option(sl_dp_calle_pertenece_entre1,'Seleccione una calle','-1');
        integra_new_option(sl_dp_calle_pertenece_entre2,'Seleccione una calle','-1');

        $.each(data.registros, function( index, calle ) {
            integra_new_option(sl_dp_calle_pertenece,calle.dp_calle_pertenece_descripcion_select,calle.dp_calle_pertenece_id);
            integra_new_option(sl_dp_calle_pertenece_entre1,calle.dp_calle_pertenece_descripcion_select,calle.dp_calle_pertenece_id);
            integra_new_option(sl_dp_calle_pertenece_entre2,calle.dp_calle_pertenece_descripcion_select,calle.dp_calle_pertenece_id);
        });

        sl_dp_calle_pertenece.selectpicker('refresh');
        sl_dp_calle_pertenece_entre1.selectpicker('refresh');
        sl_dp_calle_pertenece_entre2.selectpicker('refresh');
    });
}

sl_dp_pais.change(function () {
    let selected = $(this).find('option:selected');
    asigna_estados(selected.val());
});

sl_dp_estado.change(function () {
    let selected = $(this).find('option:selected');
    asigna_municipios(selected.val());
});

sl_dp_municipio.change(function () {
    let selected = $(this).find('option:selected');
    asigna_codigos_postales(selected.val());
});

sl_dp_cp.change(function () {
    let selected = $(this).find('option:selected');
    asigna_colonias_postales(selected.val());
});

sl_colonia_postal.change(function () {
    let selected = $(this).find('option:selected');
    asigna_calles(selected.val());
});

fecha_inicio_operaciones.change(function () {
    fecha_ultimo_cambio_sat.val(fecha_inicio_operaciones.val());
});