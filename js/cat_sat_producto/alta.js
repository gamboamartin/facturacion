let sl_cat_sat_tipo_producto = $("#cat_sat_tipo_producto_id");
let sl_cat_sat_division_producto = $("#cat_sat_division_producto_id");
let sl_cat_sat_grupo_producto = $("#cat_sat_grupo_producto_id");
let sl_cat_sat_clase_producto = $("#cat_sat_clase_producto_id");
let text_codigo = $("#codigo");

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
            integra_new_option(sl_cat_sat_division_producto,division.cat_sat_division_producto_descripcion_select,
                division.cat_sat_division_producto_id,"data-cat_sat_division_codigo",division.cat_sat_division_producto_codigo);
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
            integra_new_option(sl_cat_sat_grupo_producto,grupo.cat_sat_grupo_producto_descripcion_select,
                grupo.cat_sat_grupo_producto_id,"data-cat_sat_grupo_codigo",grupo.cat_sat_grupo_producto_codigo);
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
            integra_new_option(sl_cat_sat_clase_producto,clase.cat_sat_clase_producto_descripcion_select,
                clase.cat_sat_clase_producto_id,"data-cat_sat_clase_codigo",clase.cat_sat_clase_producto_codigo);
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
    let codigo = selected.data(`cat_sat_division_codigo`);
    asigna_grupos(selected.val());
    text_codigo.val(`${codigo}NNNNNN`);
});

sl_cat_sat_grupo_producto.change(function () {
    let selected = $(this).find('option:selected');
    let codigo = selected.data(`cat_sat_grupo_codigo`);
    asigna_clases(selected.val());
    text_codigo.val(`${codigo}NNNN`);
});

sl_cat_sat_clase_producto.change(function () {
    let selected = $(this).find('option:selected');
    let codigo = selected.data(`cat_sat_clase_codigo`);

    text_codigo.val(`${codigo}NN`);
});