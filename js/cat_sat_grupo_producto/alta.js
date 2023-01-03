let sl_cat_sat_tipo_producto = $("#cat_sat_tipo_producto_id");
let sl_cat_sat_division_producto = $("#cat_sat_division_producto_id");
let text_codigo = $("#codigo");


let asigna_divisiones = (cat_sat_tipo_producto_id = '') => {
    let url = get_url("cat_sat_division_producto","get_divisiones", {cat_sat_tipo_producto_id: cat_sat_tipo_producto_id});

    get_data(url, function (data) {
        console.log(url);
        sl_cat_sat_division_producto.empty();

        integra_new_option(sl_cat_sat_division_producto,'Seleccione una division','-1');

        $.each(data.registros, function( index, division ) {
            integra_new_option(sl_cat_sat_division_producto,division.cat_sat_division_producto_descripcion_select,
                division.cat_sat_division_producto_id,"data-cat_sat_division_codigo",division.cat_sat_division_producto_codigo);
        });
        sl_cat_sat_division_producto.selectpicker('refresh');
    });
}

sl_cat_sat_tipo_producto.change(function () {
    let selected = $(this).find('option:selected');
    asigna_divisiones(selected.val());
});

sl_cat_sat_division_producto.change(function () {
    let selected = $(this).find('option:selected');
    let codigo = selected.data(`cat_sat_division_codigo`);

    text_codigo.val(`${codigo}NN`);
});