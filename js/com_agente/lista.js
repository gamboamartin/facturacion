$(document).ready(function () {
    var table_com_agente = $('.datatable').DataTable();
    var filtro_aplicado = false;

    $('#limpiar').prop('disabled', true);

    function valores_select_propios() {
        var filtros = {};

        $('.filtros-avanzados select').each(function () {
            var $select = $(this);
            var valor = $select.val();
            var campo = $select.data('filtro_campo');

            if (campo && valor) {
                filtros[campo] = valor;
            }
        });

        return filtros;
    }

    function verificar_filtros() {
        var tiene_valor = false;

        $('.filtros-avanzados input, .filtros-avanzados select').each(function () {
            if ($(this).val().toString().trim() !== '') {
                tiene_valor = true;
                return false;
            }
        });

        $('#limpiar').prop('disabled', !tiene_valor);
    }

    $('.filtros-avanzados input, .filtros-avanzados select').on('input change', function () {
        verificar_filtros();
    });

    table_com_agente.on('preXhr.dt', function (e, settings, data) {
        data.filtros_select_propios = valores_select_propios();
    });

    $('#filtrar').on('click', function () {
        $('#filtrar').prop('disabled', true);
        table_com_agente.ajax.reload(function () {
            $('#filtrar').prop('disabled', false);
            $('#limpiar').prop('disabled', false);
            filtro_aplicado = true;
        });
    });

    $('#limpiar').on('click', function () {
        $('.filtros-avanzados input, .filtros-avanzados select').val('');
        $('#limpiar').prop('disabled', true);

        if (filtro_aplicado) {
            table_com_agente.ajax.reload();
            filtro_aplicado = false;
        }
    });
});