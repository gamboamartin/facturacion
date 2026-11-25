(function () {
    'use strict';
    var current = new URL(window.location.href).searchParams.get('accion');
    document.querySelectorAll('.com-tipo-actions a[href]').forEach(function (link) {
        var url = new URL(link.href, window.location.href);
        if (url.origin !== window.location.origin || url.searchParams.get('seccion') !== 'com_tipo_cliente') { return; }
        var action = url.searchParams.get('accion');
        if (action === 'alta') { link.textContent = '+ Nuevo tipo'; }
        if (action === 'lista') { link.textContent = current === 'lista' ? 'Ver catálogo' : 'Volver al catálogo'; link.classList.add('com-cliente-action-lista'); }
        if (action === current) { link.setAttribute('aria-current', 'page'); }
    });
}());
