let url = get_url("fc_factura","filtro_and", {});

$('#com_sucursal_id').on('changed.bs.select', function (e, clickedIndex, isSelected, previousValue) {
    const com_sucursal_id = $(this).val();
    console.log('url:', url);
    console.log('com_sucursal_id:', com_sucursal_id);

    $.ajax({
        url : url,
        data : { com_sucursal_id : com_sucursal_id},
        type : 'POST',
        success : function(json) {
            console.log(json);
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

    // Limpiar select
    sl_fc_factura.empty();

    // Opción por defecto
    sl_fc_factura.append('<option value="">Selecciona una opcion</option>');

    // Recorrer el array
    facturas.forEach((factura, index) => {

        const texto = `${factura.fc_factura_folio}| ${factura.fc_factura_total} | ${factura.fc_factura_fecha} | ${factura.com_cliente_razon_social}`;

        // OJO: si no tienes fc_factura_id, usa el index o folio
        sl_fc_factura.append(
            `<option value="${factura.fc_factura_id}">
                ${texto}
            </option>`
        );
    });

    // Refrescar bootstrap-select
    sl_fc_factura.selectpicker('refresh');
}
