(function () {
    'use strict';
    var form = document.querySelector('.com-producto-form');
    if (!form) { return; }
    var typeForm = form.classList.contains('com-tipo-producto-form');
    function field(name, text, placeholder) {
        var input = form.querySelector('[name="' + name + '"]');
        if (!input) { return null; }
        var label = input.id ? form.querySelector('label[for="' + input.id + '"]') : null;
        if (label) { label.textContent = text; }
        if (placeholder && !input.disabled) { input.placeholder = placeholder; }
        return input;
    }
    field('descripcion', typeForm ? 'Nombre del tipo de producto' : 'Descripción del producto', typeForm ? 'Ej. Servicios' : 'Nombre con el que identificarás el producto');
    var code = field('codigo', typeForm ? 'Código del tipo de producto' : 'Código del producto', typeForm ? '' : 'Ej. PROD-001');
    field('precio', 'Precio unitario', 'Ej. 100.00');
    if (typeForm && code && code.disabled) {
        var label = form.querySelector('label[for="' + code.id + '"]');
        if (label) { label.textContent = 'Código automático'; }
        var help = document.createElement('p');
        help.className = 'com-cliente-help'; help.id = 'tipo-producto-codigo-ayuda';
        help.textContent = 'El sistema genera este código a partir del nombre.';
        code.insertAdjacentElement('afterend', help);
        code.setAttribute('aria-describedby', help.id);
    }
    var current = new URL(window.location.href).searchParams.get('accion');
    var section = typeForm ? 'com_tipo_producto' : 'com_producto';
    document.querySelectorAll('.com-producto-actions a[href]').forEach(function (link) {
        var url = new URL(link.href, window.location.href);
        if (url.origin !== window.location.origin || url.searchParams.get('seccion') !== section) { return; }
        var action = url.searchParams.get('accion');
        if (action === 'alta') { link.textContent = typeForm ? '+ Nuevo tipo' : '+ Nuevo producto'; }
        if (action === 'lista') { link.textContent = 'Volver al catálogo'; link.classList.add('com-cliente-action-lista'); }
        if (action === current) { link.setAttribute('aria-current', 'page'); }
    });
}());
