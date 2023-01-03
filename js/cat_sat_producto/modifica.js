let sl_cat_sat_tipo_producto = $("#cat_sat_tipo_producto_id");
let sl_cat_sat_division_producto = $("#cat_sat_division_producto_id");
let sl_cat_sat_grupo_producto = $("#cat_sat_grupo_producto_id");
let sl_cat_sat_clase_producto = $("#cat_sat_clase_producto_id");

let asigna_divisiones = (cat_sat_tipo_producto_id = '') => {
    let url = get_url("cat_sat_division_producto","get_divisiones", {cat_sat_tipo_producto_id: cat_sat_tipo_producto_id});

    get_data(url, function (data) {
        sl_cat_sat_division_producto.empty();
        sl_cat_sat_grupo_producto.empty();
        sl_cat_sat_clase_producto.empty();

        integra_new_option(sl_cat_sat_division_producto,'Seleccione una division','-1');
        integra_new_option(sl_cat_sat_grupo_producto,'Seleccione un grupo','-1');
        integra_new_option(sl_cat_sat_clase_producto,'Seleccione una clase','-1');

        $.each(data.registros, function( index, division ) {
            integra_new_option(sl_cat_sat_division_producto,division.cat_sat_division_producto_descripcion_select,division.cat_sat_division_producto_id);
        });
        sl_cat_sat_division_producto.selectpicker('refresh');
        sl_cat_sat_grupo_producto.selectpicker('refresh');
        sl_cat_sat_clase_producto.selectpicker('refresh');
    });
}

let asigna_grupos = (cat_sat_division_producto_id = '') => {
    let url = get_url("cat_sat_grupo_producto","get_grupos", {cat_sat_division_producto_id: cat_sat_division_producto_id});

    get_data(url, function (data) {
        sl_cat_sat_grupo_producto.empty();
        sl_cat_sat_clase_producto.empty();

        integra_new_option(sl_cat_sat_grupo_producto,'Seleccione un grupo','-1');
        integra_new_option(sl_cat_sat_clase_producto,'Seleccione una clase','-1');

        $.each(data.registros, function( index, grupo ) {
            integra_new_option(sl_cat_sat_grupo_producto,grupo.cat_sat_grupo_producto_descripcion_select,grupo.cat_sat_grupo_producto_id);
        });
        sl_cat_sat_grupo_producto.selectpicker('refresh');
        sl_cat_sat_clase_producto.selectpicker('refresh');
    });
}

let asigna_clases = (cat_sat_grupo_producto_id = '') => {
    let url = get_url("cat_sat_clase_producto","get_clases", {cat_sat_grupo_producto_id: cat_sat_grupo_producto_id});

    get_data(url, function (data) {
        sl_cat_sat_clase_producto.empty();

        integra_new_option(sl_cat_sat_clase_producto,'Seleccione una clase','-1');

        $.each(data.registros, function( index, clase ) {
            integra_new_option(sl_cat_sat_clase_producto,clase.cat_sat_clase_producto_descripcion_select,clase.cat_sat_clase_producto_id);
        });
        sl_cat_sat_clase_producto.selectpicker('refresh');
    });
}

sl_cat_sat_tipo_producto.change(function () {
    let selected = $(this).find('option:selected');
    asigna_divisiones(selected.val());
});

sl_cat_sat_division_producto.change(function () {
    let selected = $(this).find('option:selected');
    asigna_grupos(selected.val());
});

sl_cat_sat_grupo_producto.change(function () {
    let selected = $(this).find('option:selected');
    asigna_clases(selected.val());
});