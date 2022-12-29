let sl_com_producto = $("#com_producto_id");
let txt_cantidad = $("#cantidad");
let txt_descripcion = $("#descripcion");
let txt_valor_unitario = $("#valor_unitario");
let txt_descuento = $("#descuento");
let txt_subtotal = $("#subtotal");
let txt_total = $("#total");

sl_com_producto.change(function () {
    let selected = $(this).find('option:selected');
    let descripcion = selected.data(`com_producto_descripcion`);

    txt_descripcion.val(descripcion);
});

txt_cantidad.on('input', function () {
    let valor = $(this).val();
    let valor_unitario = txt_valor_unitario.val();
    let subtotal = valor * valor_unitario;
    let descuento = txt_descuento.val();
    let total = subtotal - descuento;

    txt_subtotal.val(subtotal);
    txt_total.val(total);
});

txt_valor_unitario.on('input', function () {
    let valor = $(this).val();
    let cantidad = txt_cantidad.val();
    let subtotal = valor * cantidad;
    let descuento = txt_descuento.val();
    let total = subtotal - descuento;

    txt_subtotal.val(subtotal);
    txt_total.val(total);
});

txt_descuento.on('input', function () {
    let valor = $(this).val();
    let cantidad = txt_cantidad.val();
    let valor_unitario = txt_valor_unitario.val();
    let subtotal = cantidad * valor_unitario;

    if (valor > subtotal){
        alert("El descuento no puede superar al subtotal obtenido")
        valor = 0;
        $(this).val(0);
    }

    let total = subtotal - valor;

    txt_subtotal.val(subtotal);
    txt_total.val(total);
});



