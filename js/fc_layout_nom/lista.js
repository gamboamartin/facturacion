/**
 * Lista de layouts de nómina (fc_layout_nom): badges de estado y panel de acciones por fila.
 * Depende de jQuery y DataTables (ya cargados por el template). No modifica permisos:
 * solo reordena los botones que el servidor ya filtró en cada fila.
 */
document.addEventListener('DOMContentLoaded', function () {

    /*
     * Cada acción llega en la fila como una clave propia (modifica, elimina_bd, ...) con su <a> ya armado
     * y filtrado por permisos. Aquí solo se clasifican por clave: lo que no esté en el mapa cae en "Otras acciones".
     */
    const GRUPOS = [
        {
            titulo: 'Datos',
            visibles: ['modifica', 'ver_empleados', 'modifica_sucursal', 'modifica_layout_periodo',
                'modifica_fecha_emision', 'actualiza_porcentaje_comision'],
            mas_titulo: '',
            mas: []
        },
        {
            titulo: 'Archivos',
            visibles: ['carga_files_layout'],
            mas_titulo: 'Más opciones',
            mas: ['carga_empleados', 'descarga_original', 'genera_dispersion', 'descarga_timbres',
                'regenera_rec_pdfs']
        },
        {
            titulo: 'Otras acciones',
            visibles: ['elimina_bd'],
            mas_titulo: 'Más acciones',
            mas: ['layout_pagado', 'timbra_recibos', 'retimbrar_recibos', 'cancela_recibos', 'envia_nominas',
                'envia_nomina_cliente', 'envia_nomina_empleados', 'asigna_factura', 'ver_relaciones', 'status']
        }
    ];

    const CLAVES_PELIGRO = ['elimina_bd'];

    const TONOS_TIMBRADO = {
        'SIN TIMBRAR': 'warning',
        'TIMBRADO PARCIAL': 'info',
        'TIMBRADO CON ERROR': 'danger',
        'TIMBRADO': 'success'
    };

    const TONOS_LAYOUT = {
        'Descarga Layout': 'neutral',
        'Descargado o Generado': 'warning',
        'Pagado': 'info',
        'Enviado al Cliente': 'success'
    };

    const CLAVES_MAPEADAS = GRUPOS.reduce(function (acumulado, grupo) {
        return acumulado.concat(grupo.visibles, grupo.mas);
    }, []);

    function esAccion(valor) {
        return typeof valor === 'string' && /^\s*<a\s[^>]*role=['"]button['"]/.test(valor);
    }

    function crearAccion(clave, html) {
        const origen = $(html);
        const enlace = document.createElement('a');
        enlace.className = 'iv-act';
        if (CLAVES_PELIGRO.indexOf(clave) !== -1) {
            enlace.classList.add('iv-act--danger');
        }
        enlace.setAttribute('href', origen.attr('href') || '#');
        if (origen.attr('target')) {
            enlace.setAttribute('target', origen.attr('target'));
        }

        const claseIcono = origen.find('span').first().attr('class');
        if (claseIcono) {
            const icono = document.createElement('span');
            icono.className = claseIcono;
            icono.setAttribute('aria-hidden', 'true');
            enlace.appendChild(icono);
        }

        const texto = document.createElement('span');
        texto.textContent = origen.attr('title') || origen.text();
        enlace.appendChild(texto);
        return enlace;
    }

    function crearGrupo(grupo, claves, fila) {
        const visibles = grupo.visibles.filter(function (clave) { return claves.indexOf(clave) !== -1; });
        const extras = grupo.mas.filter(function (clave) { return claves.indexOf(clave) !== -1; });

        if (grupo.titulo === 'Otras acciones') {
            claves.forEach(function (clave) {
                if (CLAVES_MAPEADAS.indexOf(clave) === -1) {
                    extras.push(clave);
                }
            });
        }

        if (visibles.length === 0 && extras.length === 0) {
            return null;
        }

        const contenedor = document.createElement('div');
        contenedor.className = 'iv-panel__group';

        const titulo = document.createElement('h4');
        titulo.className = 'iv-panel__group-title';
        titulo.textContent = grupo.titulo;
        contenedor.appendChild(titulo);

        const lista = document.createElement('div');
        lista.className = 'iv-panel__actions';
        visibles.forEach(function (clave) {
            lista.appendChild(crearAccion(clave, fila[clave]));
        });
        contenedor.appendChild(lista);

        if (extras.length === 0) {
            return contenedor;
        }

        const mas = document.createElement('details');
        mas.className = 'iv-mas';
        const resumen = document.createElement('summary');
        resumen.textContent = grupo.mas_titulo || 'Más opciones';
        mas.appendChild(resumen);

        const menu = document.createElement('div');
        menu.className = 'iv-mas__menu';
        extras.forEach(function (clave) {
            menu.appendChild(crearAccion(clave, fila[clave]));
        });
        mas.appendChild(menu);
        contenedor.appendChild(mas);
        return contenedor;
    }

    function crearPanel(fila) {
        const claves = Object.keys(fila).filter(function (clave) { return esAccion(fila[clave]); });
        if (claves.length === 0) {
            return null;
        }

        const panel = document.createElement('div');
        panel.className = 'iv-panel';

        const titulo = document.createElement('h3');
        titulo.className = 'iv-panel__title';
        titulo.textContent = 'Acciones del layout #' + fila.fc_layout_nom_id;
        panel.appendChild(titulo);

        const rejilla = document.createElement('div');
        rejilla.className = 'iv-panel__grid';
        GRUPOS.forEach(function (grupo) {
            const nodo = crearGrupo(grupo, claves, fila);
            if (nodo) {
                rejilla.appendChild(nodo);
            }
        });
        panel.appendChild(rejilla);
        return panel;
    }

    function cerrarPaneles() {
        $('tr.iv-panel-row').remove();
        $('.iv-acciones-toggle').attr('aria-expanded', 'false');
    }

    function formatoTimbrado(valor) {
        const texto = String(valor || '').toLowerCase();
        return texto.charAt(0).toUpperCase() + texto.slice(1);
    }

    function pintaBadge(celda, valor, tonos, formato) {
        if (!valor) {
            return;
        }
        const badge = document.createElement('span');
        const tono = tonos[valor] || 'neutral';
        badge.className = 'iv-badge iv-badge--' + tono;
        badge.textContent = formato ? formato(valor) : valor;
        celda.textContent = '';
        celda.appendChild(badge);
    }

    function procesaTabla(tabla) {
        const api = $(tabla).DataTable();
        const columnas = api.settings()[0].aoColumns;

        api.rows({page: 'current'}).every(function () {
            const fila = this.data();
            const tr = this.node();

            columnas.forEach(function (columna, indice) {
                const celda = api.cell(tr, indice).node();
                if (columna.data === 'fc_layout_nom_estado_timbrado') {
                    pintaBadge(celda, fila.fc_layout_nom_estado_timbrado, TONOS_TIMBRADO, formatoTimbrado);
                }
                if (columna.data === 'fc_layout_nom_estado_layout') {
                    pintaBadge(celda, fila.fc_layout_nom_estado_layout, TONOS_LAYOUT, null);
                }
            });

            const celda_acciones = $(tr).find("td a[role='button']").first().closest('td');
            if (celda_acciones.length === 0) {
                return;
            }

            const boton = document.createElement('button');
            boton.type = 'button';
            boton.className = 'iv-acciones-toggle';
            boton.setAttribute('aria-expanded', 'false');
            boton.innerHTML = 'Acciones <i class="bi bi-chevron-down" aria-hidden="true"></i>';
            celda_acciones.empty().append(boton);
        });

        if (api.responsive) {
            api.responsive.recalc();
        }
    }

    $(document).on('draw.dt', 'table.datatable', function () {
        cerrarPaneles();
        procesaTabla(this);
    });

    // Si el primer dibujado ocurrió antes de enlazar el evento, se procesa lo ya pintado (es idempotente).
    $('table.datatable').each(function () {
        if ($.fn.dataTable.isDataTable(this) && $(this).find('tbody tr').length > 0) {
            procesaTabla(this);
        }
    });

    $(document).on('click', '.iv-acciones-toggle', function () {
        const boton = $(this);
        const tr = boton.closest('tr');
        const abierto = tr.next('tr.iv-panel-row').length > 0;

        cerrarPaneles();
        if (abierto) {
            return;
        }

        const api = boton.closest('table').DataTable();
        const panel = crearPanel(api.row(tr).data());
        if (!panel) {
            return;
        }

        const columnas_visibles = api.columns(':visible')[0].length;
        const fila_panel = $('<tr class="iv-panel-row"><td class="iv-panel-cell"></td></tr>');
        fila_panel.find('td').attr('colspan', columnas_visibles).append(panel);
        tr.after(fila_panel);
        boton.attr('aria-expanded', 'true');
    });

    $(document).on('click', function (evento) {
        if ($(evento.target).closest('.iv-dropdown').length === 0) {
            $('.iv-dropdown').removeAttr('open');
        }
    });
});