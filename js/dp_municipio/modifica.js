let sl_dp_pais = $("#dp_pais_id");
let sl_dp_estado = $("#dp_estado_id");

let asigna_estados = (dp_pais_id = '') => {
    let url = get_url("dp_estado","get_estado", {dp_pais_id: dp_pais_id});

    get_data(url, function (data) {
        sl_dp_estado.empty();

        integra_new_option(sl_dp_estado,'Seleccione un estado','-1');

        $.each(data.registros, function( index, dp_estado ) {
            integra_new_option(sl_dp_estado,dp_estado.dp_estado_descripcion_select,dp_estado.dp_estado_id);
        });
        sl_dp_estado.selectpicker('refresh');
    });
}

sl_dp_pais.change(function () {
    let selected = $(this).find('option:selected');
    asigna_estados(selected.val());
});