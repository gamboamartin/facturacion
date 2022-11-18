let sl_com_producto = $("#com_producto_id");
let txt_codigo = $("#codigo");
let txt_descripcion = $("#descripcion");

sl_com_producto.change(function () {
    let selected = $(this).find('option:selected');
    let codigo = selected.data(`com_producto_codigo`);
    let descripcion = selected.data(`com_producto_descripcion`);

    txt_codigo.val(codigo);
    txt_descripcion.val(descripcion);
});