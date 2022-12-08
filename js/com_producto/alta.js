let sl_cat_sat_tipo_producto = $("#cat_sat_tipo_producto_id");
let sl_cat_sat_division_producto = $("#cat_sat_division_producto_id");
let sl_cat_sat_grupo_producto = $("#cat_sat_grupo_producto_id");
let sl_cat_sat_clase_producto = $("#cat_sat_clase_producto_id");
let sl_cat_sat_producto = $("#cat_sat_producto_id");

let txt_descripcion = $("#descripcion");

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

let asigna_productos = (cat_sat_clase_producto_id = '') => {
    let url = get_url("cat_sat_producto","get_productos", {cat_sat_clase_producto_id: cat_sat_clase_producto_id});

    get_data(url, function (data) {
        sl_cat_sat_producto.empty();

        integra_new_option(sl_cat_sat_producto,'Seleccione un producto','-1');

        $.each(data.registros, function( index, producto ) {
            integra_new_option(sl_cat_sat_producto,producto.cat_sat_producto_descripcion_select,producto.cat_sat_producto_id,
                "data-cat_sat_producto_descripcion",producto.cat_sat_producto_descripcion);
        });
        sl_cat_sat_producto.selectpicker('refresh');
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

sl_cat_sat_clase_producto.change(function () {
    let selected = $(this).find('option:selected');
    asigna_productos(selected.val());
});

sl_cat_sat_producto.change(function () {
    let selected = $(this).find('option:selected');
    let descripcion = selected.data(`cat_sat_producto_descripcion`);

    txt_descripcion.val(descripcion)
});