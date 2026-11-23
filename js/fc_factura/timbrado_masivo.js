(function () {
    'use strict';

    function iniciar() {
        const root = document.getElementById('vista_fc_factura_timbrado_masivo');
        if (!root || root.dataset.iniciado === '1') return;
        root.dataset.iniciado = '1';
        const el = function (id) { return root.querySelector('#tm-' + id); };
        const aviso = function (mensaje, tipo) {
            el('aviso').textContent = mensaje;
            el('aviso').dataset.tipo = tipo || 'info';
            el('aviso').hidden = !mensaje;
        };
        let config, endpoint;
        try {
            config = JSON.parse(el('config').textContent);
            if (!config.endpoint || !config.csrf) throw new Error('Falta la configuración de la sesión.');
            endpoint = new URL(config.endpoint, window.location.href);
            if (endpoint.origin !== window.location.origin || !/^https?:$/.test(endpoint.protocol)) {
                throw new Error('La dirección de timbrado no corresponde a esta aplicación.');
            }
        } catch (error) {
            aviso(error.message || 'No se pudo preparar el timbrado.', 'error');
            root.querySelectorAll('button, input, select').forEach(function (control) { control.disabled = true; });
            return;
        }

        const nombresFiltros = {
            fecha_inicio: 'Fecha inicial', fecha_fin: 'Fecha final', serie: 'Serie', folio: 'Folio',
            rfc: 'RFC del cliente', cliente: 'Cliente', emisor_id: 'Emisor',
            total_min: 'Total desde', total_max: 'Total hasta'
        };
        const etiquetasEstado = {
            pendiente: 'Pendiente', procesando: 'Procesando', timbrada: 'Timbrada',
            omitida: 'Omitida', rechazada: 'Rechazada', revision_requerida: 'Revisión requerida'
        };
        const maximo = Math.max(1, Math.min(100, Number(config.maximo) || 100));
        const s = {
            ocupado: false, ejecutando: false, pausar: false, sucios: false, consulta: 0,
            filas: [], idsFiltrados: [], total: 0, pagina: 1, paginas: 1, seleccion: new Set(), todas: false,
            filtros: {}, confirmacion: null, clave: '', firma: '', loteId: '', lote: null
        };
        const numero = function (valor) { return Math.max(0, Math.trunc(Number(valor) || 0)); };
        const texto = function (valor) { return valor === null || valor === undefined ? '' : String(valor); };
        const serieFolio = function (fila) {
            const serie = texto(fila.serie).trim();
            const folio = texto(fila.folio).trim();
            return serie && folio && folio !== serie &&
                !folio.startsWith(serie + '-') && !folio.startsWith(serie + ' ')
                ? serie + '-' + folio : folio;
        };
        const idValido = function (valor) { return /^[1-9][0-9]*$/.test(texto(valor)); };
        const seleccionadas = function () { return s.todas ? s.total : s.seleccion.size; };
        const seleccionCompleta = function () {
            return s.total > 0 && s.total <= maximo && s.idsFiltrados.length === s.total;
        };
        const filtrosActuales = function () {
            const filtros = {};
            Object.keys(nombresFiltros).forEach(function (nombre) {
                filtros[nombre] = el('filtros').elements.namedItem(nombre).value.trim();
            });
            return filtros;
        };

        async function solicitar(operacion, datos) {
            const body = new URLSearchParams();
            body.set('operacion', operacion);
            body.set('csrf', config.csrf);
            Object.entries(datos || {}).forEach(function (entry) { body.set(entry[0], entry[1]); });
            let response;
            try {
                response = await fetch(endpoint.href, {
                    method: 'POST', credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
                    body: body.toString()
                });
            } catch (error) {
                throw new Error('Se perdió la conexión. Consulta el estado antes de continuar; no se reenvió la operación.');
            }
            let json;
            try {
                json = await response.json();
            } catch (error) {
                throw new Error('El servidor no devolvió un estado válido. Consulta el estado antes de continuar.');
            }
            if (!response.ok || !json || json.ok !== true) {
                const error = new Error(texto(json && json.mensaje) || 'No se pudo completar la operación.');
                error.estadoHttp = response.status;
                throw error;
            }
            if (!json.datos || typeof json.datos !== 'object') throw new Error('La respuesta no contiene datos del lote.');
            return json.datos;
        }

        function actualizarControles() {
            const bloqueado = s.ocupado || s.ejecutando;
            el('filtros').querySelectorAll('input, select, button').forEach(function (control) { control.disabled = bloqueado; });
            const bloqueaSeleccion = bloqueado || s.sucios || Boolean(s.loteId);
            el('todas').disabled = bloqueaSeleccion || !seleccionCompleta();
            el('todas').textContent = 'Todas las que cumplen el filtro (' + s.total + ')';
            el('quitar-seleccion').disabled = bloqueaSeleccion || seleccionadas() === 0;
            el('revisar').disabled = bloqueaSeleccion || seleccionadas() === 0 || seleccionadas() > maximo;
            el('revisar').textContent = seleccionadas() ? 'Revisar ' + seleccionadas() + ' facturas' : 'Revisar selección';
            el('anterior').disabled = bloqueado || s.sucios || s.pagina <= 1;
            el('siguiente').disabled = bloqueado || s.sucios || s.pagina >= s.paginas;
            el('pagina-todas').disabled = bloqueaSeleccion || s.todas || s.filas.length === 0;
            const seleccionPagina = s.filas.filter(function (fila) { return s.seleccion.has(texto(fila.id)); }).length;
            el('pagina-todas').checked = s.filas.length > 0 && (s.todas || seleccionPagina === s.filas.length);
            el('pagina-todas').indeterminate = !s.todas && seleccionPagina > 0 && seleccionPagina < s.filas.length;
            el('filas').querySelectorAll('input[type="checkbox"]').forEach(function (control) {
                control.checked = s.todas || s.seleccion.has(control.value);
                control.disabled = bloqueaSeleccion || s.todas;
            });
            el('confirmar').disabled = bloqueado || !s.confirmacion;
            el('cancelar').disabled = bloqueado;
            el('consultar').disabled = bloqueado || !s.loteId;
            el('reanudar').disabled = bloqueado || !s.loteId ||
                (s.lote && numero(s.lote.resumen && s.lote.resumen.pendientes) === 0);
            el('pausar').hidden = !s.ejecutando;
            el('pausar').disabled = s.pausar;
            let detalle = s.todas ? 'Seleccionadas todas las ' + s.total + ' facturas que cumplen el filtro.' :
                (s.seleccion.size ? s.seleccion.size + ' facturas seleccionadas entre las páginas.' : 'Ninguna factura seleccionada.');
            if (s.total > maximo) detalle += ' Para seleccionar todas, afina los filtros hasta un máximo de ' + maximo + '.';
            else if (s.total > 0 && !seleccionCompleta()) detalle += ' Vuelve a aplicar los filtros para verificar la selección completa.';
            if (s.sucios) detalle = 'Los filtros cambiaron. Aplica los filtros para seleccionar facturas.';
            el('seleccion-detalle').textContent = detalle;
        }

        function cerrarConfirmacion() {
            s.confirmacion = null;
            el('confirmacion').hidden = true;
        }

        function limpiarSeleccion() {
            s.seleccion.clear();
            s.todas = false;
            cerrarConfirmacion();
            actualizarControles();
        }

        function celda(fila, valor, clase) {
            const td = document.createElement('td');
            td.textContent = texto(valor);
            if (clase) td.className = clase;
            fila.appendChild(td);
            return td;
        }

        function mensajeTabla(cuerpo, columnas, mensaje) {
            cuerpo.replaceChildren();
            const tr = document.createElement('tr');
            const td = celda(tr, mensaje);
            td.colSpan = columnas;
            cuerpo.appendChild(tr);
        }

        function renderFilas() {
            const cuerpo = el('filas');
            cuerpo.replaceChildren();
            if (s.filas.length === 0) {
                mensajeTabla(cuerpo, 8, 'No hay facturas pendientes que cumplan los filtros.');
            }
            s.filas.forEach(function (fila) {
                const tr = document.createElement('tr');
                const td = document.createElement('td');
                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.value = texto(fila.id);
                checkbox.setAttribute('aria-label', 'Seleccionar factura ' + serieFolio(fila));
                checkbox.addEventListener('change', function () {
                    cerrarConfirmacion();
                    if (checkbox.checked && s.seleccion.size >= maximo) {
                        checkbox.checked = false;
                        aviso('Puedes seleccionar como máximo ' + maximo + ' facturas por lote.', 'error');
                    } else if (checkbox.checked) {
                        s.seleccion.add(checkbox.value);
                    } else {
                        s.seleccion.delete(checkbox.value);
                    }
                    actualizarControles();
                });
                td.appendChild(checkbox);
                tr.appendChild(td);
                celda(tr, serieFolio(fila));
                celda(tr, fila.cliente);
                celda(tr, fila.rfc);
                celda(tr, fila.emisor);
                celda(tr, fila.fecha);
                celda(tr, [fila.total, fila.moneda].filter(function (v) { return v !== null && v !== undefined && v !== ''; }).join(' '));
                celda(tr, fila.etapa);
                cuerpo.appendChild(tr);
            });
            el('conteo').textContent = s.total + ' facturas pendientes encontradas.';
            el('pagina-estado').textContent = 'Página ' + s.pagina + ' de ' + s.paginas;
            actualizarControles();
        }

        async function listar(pagina) {
            const consulta = ++s.consulta;
            s.ocupado = true;
            aviso('');
            actualizarControles();
            try {
                const datos = await solicitar('listar', { filtros: JSON.stringify(s.filtros), pagina: pagina });
                if (consulta !== s.consulta) return;
                if (!Array.isArray(datos.filas) || datos.filas.some(function (fila) { return !idValido(fila.id); })) {
                    throw new Error('La lista de facturas recibida no es válida.');
                }
                s.filas = datos.filas;
                s.total = numero(datos.total);
                const ids = Array.isArray(datos.seleccion_ids) ? datos.seleccion_ids.map(texto) : [];
                const idsValidos = ids.length <= maximo && ids.every(idValido) && new Set(ids).size === ids.length;
                const idsNuevos = idsValidos && ids.length === s.total ? ids : [];
                const cambiaSeleccion = idsNuevos.length !== s.idsFiltrados.length ||
                    idsNuevos.some(function (id) { return !s.idsFiltrados.includes(id); });
                s.idsFiltrados = idsNuevos;
                if (s.todas && cambiaSeleccion) {
                    limpiarSeleccion();
                    aviso('Las facturas disponibles cambiaron. Revisa y selecciona de nuevo antes de timbrar.', 'info');
                }
                s.pagina = Math.max(1, numero(datos.pagina));
                s.paginas = Math.max(1, numero(datos.paginas));
                s.sucios = false;
                renderFilas();
            } catch (error) {
                s.filas = [];
                s.idsFiltrados = [];
                s.total = 0;
                s.seleccion.clear();
                s.todas = false;
                s.sucios = true;
                mensajeTabla(el('filas'), 8, 'No se pudo consultar la lista. Aplica los filtros para intentar de nuevo.');
                el('conteo').textContent = 'Consulta pendiente.';
                aviso(error.message, 'error');
            } finally {
                s.ocupado = false;
                actualizarControles();
            }
        }

        function mostrarResultados(loteId) {
            s.loteId = loteId;
            cerrarConfirmacion();
            el('pendientes').hidden = true;
            el('resultados').hidden = false;
            el('titulo').textContent = 'Resultados de timbrado';
            const url = new URL(window.location.href);
            url.searchParams.set('lote_id', loteId);
            window.history.replaceState(null, '', url.href);
        }

        function renderLote(datos) {
            if (!/^[a-f0-9]{32}$/i.test(texto(datos.lote_id)) || !datos.resumen || !Array.isArray(datos.resultados)) {
                throw new Error('El estado del lote recibido no es válido.');
            }
            if (s.loteId && datos.lote_id !== s.loteId) throw new Error('El servidor devolvió un lote diferente.');
            s.lote = datos;
            mostrarResultados(datos.lote_id);
            const resumen = datos.resumen;
            const etiquetas = {
                total: 'Total', pendientes: 'Pendientes', procesando: 'Procesando', timbradas: 'Timbradas',
                omitidas: 'Omitidas', rechazadas: 'Rechazadas', revision_requerida: 'Revisión requerida'
            };
            el('resumen').replaceChildren();
            Object.keys(etiquetas).forEach(function (campo) {
                const span = document.createElement('span');
                span.textContent = etiquetas[campo] + ': ' + numero(resumen[campo]);
                el('resumen').appendChild(span);
            });
            const total = numero(resumen.total);
            const pendientes = numero(resumen.pendientes);
            const procesando = numero(resumen.procesando);
            const completas = Math.max(0, total - pendientes - procesando);
            el('progreso').max = Math.max(1, total);
            el('progreso').value = completas;
            el('progreso-texto').textContent = completas + ' de ' + total + ' facturas con resultado. ' +
                pendientes + ' pendientes y ' + procesando + ' en proceso.';
            const cuerpo = el('resultados-filas');
            cuerpo.replaceChildren();
            datos.resultados.forEach(function (resultado) {
                const tr = document.createElement('tr');
                celda(tr, resultado.factura_id);
                celda(tr, resultado.folio);
                const estado = celda(tr, etiquetasEstado[resultado.estado] || resultado.estado, 'tm-estado');
                estado.dataset.estado = texto(resultado.estado);
                celda(tr, resultado.mensaje);
                celda(tr, resultado.uuid);
                cuerpo.appendChild(tr);
            });
            if (datos.resultados.length === 0) mensajeTabla(cuerpo, 5, 'El lote todavía no tiene resultados.');
            actualizarControles();
        }

        function mensajeFin() {
            const resumen = s.lote.resumen;
            if (numero(resumen.procesando) > 0) {
                aviso('Hay una factura en proceso. Consulta el estado antes de reanudar.', 'info');
            } else if (numero(resumen.pendientes) > 0) {
                aviso('Lote pausado. Puedes consultar y reanudar las facturas pendientes.', 'info');
            } else if (numero(resumen.rechazadas) > 0 || numero(resumen.revision_requerida) > 0) {
                aviso('El lote terminó con incidencias. Revisa el resultado de cada factura; las incidencias no se reintentan automáticamente.', 'error');
            } else {
                aviso('Lote terminado. Los resultados de cada factura quedan disponibles en esta página.', 'exito');
            }
        }

        async function procesarPendientes() {
            s.ejecutando = true;
            s.pausar = false;
            actualizarControles();
            try {
                while (!s.pausar && numero(s.lote.resumen.pendientes) > 0 && numero(s.lote.resumen.procesando) === 0) {
                    const antes = numero(s.lote.resumen.pendientes);
                    const pendiente = s.lote.resultados.find(function (resultado) { return resultado.estado === 'pendiente'; });
                    if (!pendiente || !idValido(pendiente.factura_id)) {
                        throw new Error('No se pudo identificar la siguiente factura pendiente.');
                    }
                    const datos = await solicitar('procesar', { lote_id: s.loteId, factura_id: pendiente.factura_id });
                    renderLote(datos);
                    if (numero(datos.resumen.pendientes) >= antes && numero(datos.resumen.procesando) === 0) {
                        aviso('El lote no avanzó. Consulta su estado antes de continuar.', 'error');
                        return;
                    }
                }
                mensajeFin();
            } catch (error) {
                aviso(error.message + ' Usa «Consultar estado» o «Consultar y reanudar pendientes».', 'error');
            } finally {
                s.ejecutando = false;
                actualizarControles();
            }
        }

        async function consultar(reanudar) {
            if (s.ocupado || s.ejecutando || !s.loteId) return;
            s.ocupado = true;
            aviso('');
            actualizarControles();
            try {
                renderLote(await solicitar('estado', { lote_id: s.loteId }));
                if (reanudar && numero(s.lote.resumen.procesando) === 0 && numero(s.lote.resumen.pendientes) > 0) {
                    await procesarPendientes();
                } else {
                    mensajeFin();
                }
            } catch (error) {
                aviso(error.message, 'error');
            } finally {
                s.ocupado = false;
                actualizarControles();
            }
        }

        function crearClave() {
            const bytes = new Uint8Array(16);
            window.crypto.getRandomValues(bytes);
            return Array.from(bytes, function (byte) { return byte.toString(16).padStart(2, '0'); }).join('');
        }

        el('filtros').addEventListener('input', function () {
            s.sucios = true;
            limpiarSeleccion();
        });
        el('filtros').addEventListener('submit', function (event) {
            event.preventDefault();
            if (s.ocupado || s.ejecutando || !el('filtros').reportValidity()) return;
            const filtros = filtrosActuales();
            if (filtros.fecha_inicio && filtros.fecha_fin && filtros.fecha_inicio > filtros.fecha_fin) {
                aviso('La fecha inicial no puede ser posterior a la fecha final.', 'error');
                return;
            }
            if (filtros.total_min !== '' && filtros.total_max !== '' && Number(filtros.total_min) > Number(filtros.total_max)) {
                aviso('El total desde no puede ser mayor al total hasta.', 'error');
                return;
            }
            limpiarSeleccion();
            s.filtros = filtros;
            listar(1);
        });
        el('limpiar').addEventListener('click', function () {
            el('filtros').reset();
            limpiarSeleccion();
            s.filtros = filtrosActuales();
            listar(1);
        });
        el('anterior').addEventListener('click', function () { if (!s.ocupado && s.pagina > 1) listar(s.pagina - 1); });
        el('siguiente').addEventListener('click', function () { if (!s.ocupado && s.pagina < s.paginas) listar(s.pagina + 1); });
        el('pagina-todas').addEventListener('change', function () {
            cerrarConfirmacion();
            const nueva = new Set(s.seleccion);
            s.filas.forEach(function (fila) {
                if (el('pagina-todas').checked) nueva.add(texto(fila.id));
                else nueva.delete(texto(fila.id));
            });
            if (nueva.size > maximo) aviso('La selección superaría el máximo de ' + maximo + ' facturas.', 'error');
            else s.seleccion = nueva;
            actualizarControles();
        });
        el('todas').addEventListener('click', function () {
            if (s.sucios || !seleccionCompleta()) return;
            cerrarConfirmacion();
            s.todas = true;
            s.seleccion.clear();
            actualizarControles();
        });
        el('quitar-seleccion').addEventListener('click', limpiarSeleccion);
        el('revisar').addEventListener('click', function () {
            const cantidad = seleccionadas();
            if (s.sucios || cantidad < 1 || cantidad > maximo) return;
            s.confirmacion = {
                cantidad: cantidad, modo: s.todas ? 'todas' : 'ids',
                ids: s.todas ? s.idsFiltrados.slice() : Array.from(s.seleccion), filtros: Object.assign({}, s.filtros)
            };
            el('confirmacion-resumen').textContent = 'Vas a timbrar ' + cantidad + ' facturas. ' +
                (s.todas ? 'Se incluirán todas las que cumplen los filtros aplicados.' : 'Solo se incluirán las facturas que seleccionaste.');
            el('confirmacion-filtros').replaceChildren();
            const aplicados = Object.keys(nombresFiltros).filter(function (campo) { return s.filtros[campo]; });
            aplicados.forEach(function (campo) {
                const li = document.createElement('li');
                const valor = campo === 'emisor_id'
                    ? el('emisor').selectedOptions[0].textContent : s.filtros[campo];
                li.textContent = nombresFiltros[campo] + ': ' + valor;
                el('confirmacion-filtros').appendChild(li);
            });
            if (!aplicados.length) {
                const li = document.createElement('li');
                li.textContent = 'Sin filtros adicionales.';
                el('confirmacion-filtros').appendChild(li);
            }
            el('confirmacion').hidden = false;
            actualizarControles();
            el('confirmacion-titulo').focus();
        });
        el('cancelar').addEventListener('click', function () {
            cerrarConfirmacion();
            actualizarControles();
            el('revisar').focus();
        });
        el('confirmar').addEventListener('click', async function () {
            if (s.ocupado || s.ejecutando || !s.confirmacion) return;
            const confirmacion = s.confirmacion;
            s.ocupado = true;
            aviso('');
            actualizarControles();
            try {
                const firma = JSON.stringify(confirmacion);
                if (s.firma !== firma || !s.clave) {
                    s.clave = crearClave();
                    s.firma = firma;
                }
                const datos = await solicitar('crear', {
                    clave_solicitud: s.clave, modo: confirmacion.modo,
                    factura_ids: JSON.stringify(confirmacion.ids),
                    filtros: JSON.stringify(confirmacion.filtros)
                });
                renderLote(datos);
                el('resultados-titulo').focus();
                await procesarPendientes();
            } catch (error) {
                if (error.estadoHttp === 422 && confirmacion.modo === 'todas') {
                    s.sucios = true;
                    limpiarSeleccion();
                }
                aviso(error.message, 'error');
            } finally {
                s.ocupado = false;
                actualizarControles();
            }
        });
        el('consultar').addEventListener('click', function () { consultar(false); });
        el('reanudar').addEventListener('click', function () { consultar(true); });
        el('pausar').addEventListener('click', function () {
            s.pausar = true;
            aviso('Se pausará cuando termine la factura actual. Esa operación no se cancela.', 'info');
            actualizarControles();
        });
        window.addEventListener('beforeunload', function (event) {
            if (s.ejecutando) {
                event.preventDefault();
                event.returnValue = '';
            }
        });
        if (config.lote_id) {
            if (!/^[a-f0-9]{32}$/i.test(texto(config.lote_id))) {
                aviso('El identificador del lote no es válido.', 'error');
                el('pendientes').hidden = true;
                return;
            }
            mostrarResultados(config.lote_id);
            consultar(false);
        } else {
            s.filtros = filtrosActuales();
            listar(1);
        }
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', iniciar, { once: true });
    else iniciar();
})();
