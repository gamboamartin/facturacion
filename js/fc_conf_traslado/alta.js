let sl_com_tipo_producto = $("#com_tipo_producto_id");
let sl_com_producto = $("#com_producto_id");

let asigna_productos = (com_tipo_producto_id = '') => {
    let url = get_url("com_producto","get_productos", {com_tipo_producto_id: com_tipo_producto_id});

    get_data(url, function (data) {
        sl_com_producto.empty();

        integra_new_option(sl_com_producto,'Seleccione un producto','-1');

        $.each(data.registros, function( index, producto ) {
            integra_new_option(sl_com_producto,producto.com_producto_descripcion_select, producto.com_producto_id);
        });
        sl_com_producto.selectpicker('refresh');
    });
}

sl_com_tipo_producto.change(function () {
    let selected = $(this).find('option:selected');
    asigna_productos(selected.val());
});