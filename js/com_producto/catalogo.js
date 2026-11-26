(function ($) {
    'use strict';
    var root = document.querySelector('[data-producto-catalogo]');
    if (!root || !$.fn.dataTable) { return; }
    var prefix = root.dataset.productoCatalogo;
    var section = root.dataset.productoSeccion;
    var isType = section === 'com_tipo_producto';
    var tableElement = root.querySelector('table.datatable');
    var selector = '#' + tableElement.id;
    var empty = isType ? 'Los tipos que registres aparecerán en este catálogo.' : 'Los productos que registres aparecerán en este catálogo.';
    var noResults = 'No encontramos coincidencias. Prueba con otro nombre o código.';
    $.extend(true, $.fn.dataTable.defaults, { language: {
        processing: 'Consultando catálogo…', loadingRecords: 'Cargando catálogo…',
        lengthMenu: 'Mostrar _MENU_ registros', search: 'Buscar:', zeroRecords: noResults, emptyTable: empty,
        info: 'Mostrando _START_ a _END_ de _TOTAL_ registros', infoEmpty: 'Sin registros para mostrar',
        infoFiltered: '(de _MAX_ registros)', thousands: ',',
        paginate: { first: 'Primera', last: 'Última', next: 'Siguiente', previous: 'Anterior' },
        aria: { sortAscending: ': ordenar de menor a mayor', sortDescending: ': ordenar de mayor a menor' }
    } });
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
    var search = document.getElementById(prefix + '-busqueda');
    var clear = document.getElementById(prefix + '-limpiar');
    var total = document.getElementById(prefix + '-total');
    var timer;
    function table() { return $.fn.dataTable.isDataTable(selector) ? $(selector).DataTable() : null; }
    $(selector).on('preDraw.dt', function (event, settings) {
        settings.oLanguage.sEmptyTable = new $.fn.dataTable.Api(settings).search().trim() ? noResults : empty;
    }).on('draw.dt init.dt', function () {
        var api = table(); if (!api) { return; }
        var count = api.page.info().recordsDisplay;
        total.textContent = count + (count === 1 ? ' registro encontrado' : ' registros encontrados');
    });
    search.addEventListener('input', function () {
        clear.disabled = search.value === ''; window.clearTimeout(timer);
        timer = window.setTimeout(function () { var api = table(); if (api) { api.search(search.value).draw(); } }, 350);
    });
    clear.addEventListener('click', function () {
        window.clearTimeout(timer); search.value = ''; clear.disabled = true;
        var api = table(); if (api) { api.search('').draw(); } search.focus();
    });
    root.addEventListener('click', function (event) {
        var link = event.target.closest('a[href]'); if (!link || !root.contains(link)) { return; }
        var url = new URL(link.href, window.location.href);
        if (url.origin !== window.location.origin || url.searchParams.get('seccion') !== section || url.searchParams.get('accion') !== 'elimina_bd') { return; }
        if (/confirm\s*\(/.test(link.getAttribute('onclick') || '')) { return; }
        if (!window.confirm(isType ? '¿Quieres eliminar este tipo de producto?' : '¿Quieres eliminar este producto?')) { event.preventDefault(); event.stopImmediatePropagation(); }
    }, true);
    root.querySelectorAll('.com-producto-actions a[href]').forEach(function (link) {
        var url = new URL(link.href, window.location.href);
        if (url.origin !== window.location.origin || url.searchParams.get('seccion') !== section) { return; }
        var action = url.searchParams.get('accion');
        if (action === 'alta') { link.textContent = isType ? '+ Nuevo tipo' : '+ Nuevo producto'; }
        if (action === 'lista') { link.setAttribute('aria-current', 'page'); }
    });
}(jQuery));
