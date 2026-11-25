(function () {
    'use strict';
    function initSidebar() {
        var root = document.querySelector('[data-sidebar]');
        if (!root || root.dataset.enhanced === 'true') { return; }
        var navigation = root.querySelector('.clientes-sidebar-nav');
        var links = Array.from(navigation.querySelectorAll('a.nav-link'));
        var current = root.dataset.currentSection;
        var paths = {
            people: 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M16 3a4 4 0 0 1 0 8M22 21v-2a4 4 0 0 0-3-3.87M13 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z',
            tag: 'M20 13 13 20a2 2 0 0 1-2.8 0L3 12.8V3h9.8l7.2 7.2a2 2 0 0 1 0 2.8ZM7 7h.01',
            receipt: 'M6 3 3 5v16l3-2 3 2 3-2 3 2 3-2 3 2V5l-3-2-3 2-3-2-3 2-3-2ZM7 9h10M7 13h10',
            pin: 'M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0ZM15 10a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z',
            route: 'M5 5h10a4 4 0 0 1 0 8H9a4 4 0 0 0 0 8h10M7 5a2 2 0 1 1-4 0 2 2 0 0 1 4 0ZM21 21a2 2 0 1 1-4 0 2 2 0 0 1 4 0Z',
            box: 'm12 3 9 5v9l-9 5-9-5V8l9-5ZM3 8l9 5 9-5M12 13v9M7.5 5.5l9 5',
            settings: 'M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8ZM12 2v3M12 19v3M2 12h3M19 12h3M5 5l2 2M17 17l2 2M5 19l2-2M17 7l2-2',
            grid: 'M3 3h7v7H3V3ZM14 3h7v7h-7V3ZM3 14h7v7H3v-7ZM14 14h7v7h-7v-7Z',
            arrow: 'm9 5 7 7-7 7'
        };
        function icon(name, className) {
            var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            svg.setAttribute('viewBox', '0 0 24 24'); svg.setAttribute('fill', 'none');
            svg.setAttribute('stroke', 'currentColor'); svg.setAttribute('stroke-width', '1.7');
            svg.setAttribute('stroke-linecap', 'round'); svg.setAttribute('stroke-linejoin', 'round');
            svg.setAttribute('class', className); svg.setAttribute('aria-hidden', 'true');
            var path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path.setAttribute('d', paths[name]); svg.appendChild(path); return svg;
        }
        function sectionIcon(section) {
            if (section.indexOf('tipo') !== -1 || section.indexOf('cat_') === 0) { return 'tag'; }
            if (section.indexOf('cliente') !== -1 || section.indexOf('contacto') !== -1 || section.indexOf('agente') !== -1 || section.indexOf('usuario') !== -1) { return 'people'; }
            if (section.indexOf('ruta') !== -1) { return 'route'; }
            if (section.indexOf('sucursal') !== -1 || section.indexOf('dp_') === 0) { return 'pin'; }
            if (section.indexOf('producto') !== -1) { return 'box'; }
            if (section.indexOf('fc_') === 0) { return 'receipt'; }
            if (section.indexOf('conf') !== -1 || section.indexOf('adm_') === 0) { return 'settings'; }
            return 'grid';
        }
        links.forEach(function (link) {
            var section;
            try { section = new URL(link.getAttribute('href'), window.location.href).searchParams.get('seccion') || ''; }
            catch (error) { return; }
            var label = document.createElement('span'); label.className = 'iv-sidebar__link-label';
            label.textContent = link.textContent.trim();
            link.replaceChildren(icon(sectionIcon(section), 'iv-sidebar__link-icon'), label, icon('arrow', 'iv-sidebar__link-arrow'));
            link.removeAttribute('role');
            var active = section === current;
            link.classList.toggle('active', active); link.closest('li').classList.toggle('active', active);
            if (active) { link.setAttribute('aria-current', 'page'); } else { link.removeAttribute('aria-current'); }
        });
        var input = root.querySelector('#iv-sidebar-search');
        var clear = root.querySelector('.iv-sidebar__clear');
        var empty = root.querySelector('.iv-sidebar__empty');
        var count = root.querySelector('[data-sidebar-count]');
        function normalized(value) { return value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim(); }
        function filter() {
            var query = input ? normalized(input.value) : '';
            var visible = 0;
            links.forEach(function (link) {
                var match = normalized(link.textContent).indexOf(query) !== -1;
                link.closest('li').hidden = !match; if (match) { visible++; }
            });
            count.textContent = String(visible); empty.hidden = visible !== 0;
            if (clear) { clear.hidden = query === ''; }
        }
        if (input) {
            input.addEventListener('input', filter);
            clear.addEventListener('click', function () { input.value = ''; filter(); input.focus(); });
        }
        filter();
        var mobile = window.matchMedia('(max-width: 900px)');
        var toggle = root.querySelector('.iv-sidebar__mobile-toggle');
        var toggleLabel = toggle.querySelector('[data-sidebar-toggle-label]');
        var panel = root.querySelector('.iv-sidebar__panel');
        function setOpen(open, restoreFocus) {
            root.dataset.open = String(open); toggle.setAttribute('aria-expanded', String(open));
            toggleLabel.textContent = open ? 'Ocultar' : 'Opciones';
            if (restoreFocus) { toggle.focus(); }
        }
        function sync() { setOpen(!mobile.matches, false); }
        toggle.addEventListener('click', function () { setOpen(root.dataset.open !== 'true', false); });
        panel.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && mobile.matches) { setOpen(false, true); }
        });
        function updateHeaderOffset() {
            var header = document.querySelector('.header .top-box');
            if (header && root.parentElement) {
                root.parentElement.style.setProperty('--iv-sidebar-top', Math.ceil(header.getBoundingClientRect().height) + 'px');
            }
        }
        root.dataset.enhanced = 'true'; sync(); updateHeaderOffset();
        if (mobile.addEventListener) { mobile.addEventListener('change', sync); } else { mobile.addListener(sync); }
        window.addEventListener('resize', updateHeaderOffset);
        var header = document.querySelector('.header .top-box');
        if (header && window.ResizeObserver) { new ResizeObserver(updateHeaderOffset).observe(header); }
    }
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', initSidebar); }
    else { initSidebar(); }
}());
