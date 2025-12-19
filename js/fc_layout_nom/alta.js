let url = get_url("fc_factura","filtro_and", {});

$('#com_sucursal_id').on('changed.bs.select', function (e, clickedIndex, isSelected, previousValue) {
    const com_sucursal_id = $(this).val();
    if (!com_sucursal_id) {
        return;
    }

    $.ajax({
        url : url,
        data : { com_sucursal_id : com_sucursal_id},
        type : 'POST',
        success : function(json) {
            llenar_select_facturas(json);
        },
        error : function(xhr, status) {
            console.log(xhr);
            console.log(status);
        },
        // código a ejecutar sin importar si la petición falló o no
        complete : function(xhr, status) {
            //alert('Petición realizada');
        }
    });

});

function llenar_select_facturas(facturas) {

    const sl_fc_factura = $('#fc_factura_id');

    sl_fc_factura.empty();
    sl_fc_factura.append('<option value="-1">Selecciona una opcion</option>');

    facturas.forEach((factura, index) => {

        const texto = `${factura.fc_factura_folio}| ${factura.fc_factura_total} | ${factura.fc_factura_fecha} | ${factura.com_cliente_razon_social}`;

        sl_fc_factura.append(
            `<option value="${factura.fc_factura_id}">
                ${texto}
            </option>`
        );
    });

    sl_fc_factura.selectpicker('refresh');
}
