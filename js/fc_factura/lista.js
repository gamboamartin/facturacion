$('.datatable').on('init.dt', function () {
    var table_fc_factura = $('.datatable').DataTable();

    var registros = table_fc_factura.rows().data().toArray();

    $('#filtrar').on('click', function () {
        $('#filtrar').prop('disabled', true);
        table_fc_factura.ajax.reload(function () {
            $('#filtrar').prop('disabled', false);
        });
    });
});
