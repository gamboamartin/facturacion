let sl_com_producto = $("#com_producto_id");
let txt_descripcion = $("#descripcion");
let txt_unidad = $("#unidad");
let txt_impuesto = $("#impuesto");
let txt_tipo_factor = $("#tipo_factor");
let txt_factor = $("#factor");
let txt_cantidad = $("#cantidad");
let txt_valor_unitario = $("#valor_unitario");
let txt_descuento = $("#descuento");
let txt_subtotal = $("#subtotal");
let txt_total = $("#total");

/*
let seccion = getParameterByName('seccion');
let accion = getParameterByName('accion');

url_data_table = url_data_table.replace(seccion,"fc_partida");
url_data_table = url_data_table.replace(accion,"get_data");

function format(d) {return (`<table class="table table-striped" > 
<thead >
    <tr>
        <th rowspan="2" >Producto</th>
        <th colspan="3" style="text-align:center;">Traslados</th>
        <th colspan="3" style="text-align:center;">Retenciones</th>
    </tr>
    <tr>
        <th>Tipo Impuesto</th>
        <th>Factor</th>
        <th>Importe</th>
        <th>Tipo Impuesto</th>
        <th>Factor</th>
        <th>Importe</th>
    </tr>
</thead>
<tbody>
    <tr>
        <th>${d.com_producto_descripcion}</th>
        <th>${d.fc_traslado_tipo_impuesto}</th>
        <th>${d.fc_traslado_factor}</th>
        <th>${d.fc_partida_codigo}</th>
        <th>${d.fc_retenido_tipo_impuesto}</th>
        <th>${d.fc_retenido_factor}</th>
        <th>${d.fc_partida_codigo}</th>
    </tr>
</tbody>
</table>`);
}


var dt = $("#fc_partida").DataTable({
        processing: true,
        serverSide: true,
        ajax: url_data_table,
        columns: [
            {
                class: 'details-control',
                orderable: false,
                data: null,
                defaultContent: '',
            },
            { data: 'cat_sat_producto_codigo' },
            { data: 'com_producto_codigo' },
            { data: 'fc_partida_cantidad' },
            { data: 'cat_sat_unidad_descripcion' },
            { data: 'fc_partida_valor_unitario' },
            { data: 'fc_partida_importe' },
            { data: 'fc_partida_descuento' },
            { data: 'cat_sat_obj_imp_descripcion' },
        ],
        order: [[1, 'asc']],
    });

var detailRows = [];

$('#fc_partida tbody').on('click', 'tr td.details-control', function () {
    var tr = $(this).closest('tr');
    var row = dt.row(tr);
    var idx = detailRows.indexOf(tr.attr('id'));

    if (row.child.isShown()) {
        tr.removeClass('details');
        row.child.hide();

        // Remove from the 'open' array
        detailRows.splice(idx, 1);
    } else {
        tr.addClass('details');
        row.child(format(row.data())).show();

        // Add to the 'open' array
        if (idx === -1) {
            detailRows.push(tr.attr('id'));
        }
    }
});

dt.on('draw', function () {
    detailRows.forEach(function(id, i) {
        $('#' + id + ' td.details-control').trigger('click');
    });
});


 */

sl_com_producto.change(function () {
    let selected = $(this).find('option:selected');
    let descripcion = selected.data(`com_producto_descripcion`);
    let unidad = selected.data(`cat_sat_unidad_descripcion`);
    let impuesto = selected.data(`cat_sat_obj_imp_descripcion`);
    let tipo_factor = selected.data(`cat_sat_tipo_factor_descripcion`);
    let factor = selected.data(`cat_sat_factor_factor`);

    txt_descripcion.val(descripcion);
    txt_unidad.val(unidad);
    txt_impuesto.val(impuesto);
    txt_tipo_factor.val(tipo_factor);
    txt_factor.val(factor);
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