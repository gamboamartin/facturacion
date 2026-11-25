/* Configure only this customer table before the shared initializer runs. */
(function ($) {
    'use strict';
    var root = document.querySelector('.com-cliente-lista');
    if (!root || !$.fn.dataTable) { return; }
    $.extend(true, $.fn.dataTable.defaults, {
        language: {
            processing: 'Consultando clientes…', loadingRecords: 'Cargando clientes…',
            lengthMenu: 'Mostrar _MENU_ clientes', search: 'Buscar:',
            zeroRecords: 'No encontramos clientes con esa búsqueda. Prueba con otro nombre, RFC o código.',
            emptyTable: '<span class="com-cliente-empty-icon bi bi-people" aria-hidden="true"></span><strong>Tu directorio comienza aquí</strong>Los clientes que registres aparecerán en esta lista.',
            info: 'Mostrando _START_ a _END_ de _TOTAL_ clientes', infoEmpty: 'Sin clientes para mostrar',
            infoFiltered: '(de _MAX_ clientes registrados)', thousands: ',',
            paginate: { first: 'Primera', last: 'Última', next: 'Siguiente', previous: 'Anterior' },
            aria: { sortAscending: ': ordenar de menor a mayor', sortDescending: ': ordenar de mayor a menor' }
        }
    });
    // The shared table helper treats textual cells as HTML. Keep customer text
    // literal while preserving the server-generated HTML in action columns.
    function renderPhoto(value, type) {
        // The model supplies an image tag. Extract only its source; never reuse
        // supplied markup, styles or event attributes in the document.
        var match = /^\s*<img\s+src="([^"]+)"(?:\s+style="[^"<>]*")?\s*\/?>\s*$/i.exec(value);
        if (!match || !/^archivos\/fotos_clientes\/[A-Za-z0-9_-]+\.(?:jpg|jpeg|png|webp)$/i.test(match[1])) { return ''; }
        if (type !== 'display') { return match[1]; }
        var image = document.createElement('img');
        image.src = match[1]; image.alt = 'Foto del cliente';
        image.width = 50; image.height = 50; image.loading = 'lazy';
        image.className = 'com-cliente-photo';
        return image.outerHTML;
    }
    var sharedColumnDefs = window.asigna_columnDefs;
    window.asigna_columnDefs = function (definitions) {
        var prepared = sharedColumnDefs(definitions);
        prepared.forEach(function (definition) {
            if (definition.type !== 'text') { return; }
            definition.render = function (data, type, row) {
                var value = definition.rendered.map(function (key) { return row[key] === null || row[key] === undefined ? '' : String(row[key]); }).join(' ');
                if (definition.rendered.length === 1 && definition.rendered[0] === 'com_cliente_foto') { return renderPhoto(value, type); }
                return type === 'display' ? $('<span>').text(value).html() : value;
            };
        });
        return prepared;
    };
    var search = document.getElementById('clientes-busqueda');
    var clear = document.getElementById('clientes-limpiar');
    var timer;
    function table() { return $.fn.dataTable.isDataTable('#clientes-tabla') ? $('#clientes-tabla').DataTable() : null; }
    function updateCount() {
        var api = table();
        if (!api) { return; }
        var count = api.page.info().recordsDisplay;
        document.getElementById('clientes-total').textContent = count + (count === 1 ? ' cliente encontrado' : ' clientes encontrados');
    }
    $('#clientes-tabla').on('preDraw.dt', function (event, settings) {
        var searching = new $.fn.dataTable.Api(settings).search().trim() !== '';
        settings.oLanguage.sEmptyTable = searching ?
            'No encontramos clientes con esa búsqueda. Prueba con otro nombre, RFC o código.' :
            '<span class="com-cliente-empty-icon bi bi-people" aria-hidden="true"></span><strong>Tu directorio comienza aquí</strong>Los clientes que registres aparecerán en esta lista.';
    }).on('draw.dt init.dt', updateCount);
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
    $('.com-cliente-actions a').each(function () {
        var url = new URL(this.href, window.location.href);
        if (url.searchParams.get('seccion') !== 'com_cliente') { return; }
        if (url.searchParams.get('accion') === 'alta') { this.textContent = '+ Nuevo cliente'; }
        if (url.searchParams.get('accion') === 'lista') { this.textContent = 'Ver clientes'; this.classList.add('com-cliente-action-lista'); }
    });
}(jQuery));
