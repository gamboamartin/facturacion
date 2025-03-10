$(document).ready(function () {
    var table_fc_factura = $('.datatable').DataTable();
    var filtro_aplicado = false;

    $('#limpiar').prop('disabled', true);

    function verificar_filtros() {
        var tiene_valor = false;

        $('.filtros-avanzados input').each(function () {
            if ($(this).val().trim() !== '') {
                tiene_valor = true;
                return false;
            }
        });

        $('#limpiar').prop('disabled', !tiene_valor);
    }

    $('.filtros-avanzados input').on('input', function () {
        verificar_filtros();
    });

    $('#filtrar').on('click', function () {
        $('#filtrar').prop('disabled', true);
        table_fc_factura.ajax.reload(function () {
            $('#filtrar').prop('disabled', false);
            $('#limpiar').prop('disabled', false);
            filtro_aplicado = true;
        });
    });

    $('#limpiar').on('click', function () {
        $('.filtros-avanzados input').val('');
        $('#limpiar').prop('disabled', true);

        if (filtro_aplicado) {
            table_fc_factura.ajax.reload();
            filtro_aplicado = false;
        }
    });
});
