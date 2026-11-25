(function ($) {
    'use strict';
    var root = document.querySelector('.com-tipo-lista');
    if (!root || !$.fn.dataTable) { return; }
    var empty = '<span class="com-cliente-empty-icon bi bi-tags" aria-hidden="true"></span><strong>Organiza a tus clientes</strong>Los tipos que registres aparecerán en este catálogo.';
    var noResults = 'No encontramos tipos con esa búsqueda. Prueba con otro nombre o identificador.';
    $.extend(true, $.fn.dataTable.defaults, { language: {
        processing: 'Consultando tipos…', loadingRecords: 'Cargando tipos…',
        lengthMenu: 'Mostrar _MENU_ tipos', search: 'Buscar:', zeroRecords: noResults, emptyTable: empty,
        info: 'Mostrando _START_ a _END_ de _TOTAL_ tipos', infoEmpty: 'Sin tipos para mostrar',
        infoFiltered: '(de _MAX_ tipos registrados)', thousands: ',',
        paginate: { first: 'Primera', last: 'Última', next: 'Siguiente', previous: 'Anterior' },
        aria: { sortAscending: ': ordenar de menor a mayor', sortDescending: ': ordenar de mayor a menor' }
    } });
    // Render catalog text literally; leave the framework's authorized buttons intact.
    var sharedColumnDefs = window.asigna_columnDefs;
    window.asigna_columnDefs = function (definitions) {
        var prepared = sharedColumnDefs(definitions);
        prepared.forEach(function (definition) {
            if (definition.type !== 'text') { return; }
            definition.render = function (data, type, row) {
                var value = definition.rendered.map(function (key) { return row[key] === null || row[key] === undefined ? '' : String(row[key]); }).join(' ');
                return type === 'display' ? $('<span>').text(value).html() : value;
            };
        });
        return prepared;
    };
    var search = document.getElementById('tipos-cliente-busqueda');
    var clear = document.getElementById('tipos-cliente-limpiar');
    var timer;
    function table() { return $.fn.dataTable.isDataTable('#tipos-cliente-tabla') ? $('#tipos-cliente-tabla').DataTable() : null; }
    $('#tipos-cliente-tabla').on('preDraw.dt', function (event, settings) {
        settings.oLanguage.sEmptyTable = new $.fn.dataTable.Api(settings).search().trim() ? noResults : empty;
    }).on('draw.dt init.dt', function () {
        var api = table();
        if (!api) { return; }
        var count = api.page.info().recordsDisplay;
        document.getElementById('tipos-cliente-total').textContent = count + (count === 1 ? ' tipo encontrado' : ' tipos encontrados');
    });
    search.addEventListener('input', function () {
        clear.disabled = search.value === '';
        window.clearTimeout(timer);
        timer = window.setTimeout(function () { var api = table(); if (api) { api.search(search.value).draw(); } }, 350);
    });
    clear.addEventListener('click', function () {
        window.clearTimeout(timer); search.value = ''; clear.disabled = true;
        var api = table(); if (api) { api.search('').draw(); }
        search.focus();
    });
    root.addEventListener('click', function (event) {
        var link = event.target.closest('a[href]');
        if (!link || !root.contains(link)) { return; }
        var url = new URL(link.href, window.location.href);
        if (url.origin !== window.location.origin || url.searchParams.get('seccion') !== 'com_tipo_cliente' || url.searchParams.get('accion') !== 'elimina_bd') { return; }
        if (/confirm\s*\(/.test(link.getAttribute('onclick') || '')) { return; }
        if (!window.confirm('¿Quieres eliminar este tipo de cliente?')) { event.preventDefault(); event.stopImmediatePropagation(); }
    }, true);
}(jQuery));
