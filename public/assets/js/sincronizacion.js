/* =====================================================================
   sincronizacion.js - Lógica del panel de sincronización
   ===================================================================== */

document.addEventListener('DOMContentLoaded', () => {

    // Cargar historial y selector al entrar
    cargarLogs();
    cargarSelectorLineas();

    // Referencias a botones
    const btnLineas = document.getElementById('btn-sinc-lineas');
    const btnTodo = document.getElementById('btn-sinc-todo');
    const btnProgramas = document.getElementById('btn-sinc-programas');
    const btnLotes = document.getElementById('btn-sinc-lotes');
    const btnEstadisticas = document.getElementById('btn-sinc-estadisticas');
    const btnRefrescar = document.getElementById('btn-refrescar-logs');
    const selector = document.getElementById('selector-linea');

    // Reaccionar al cambio de línea
    if (selector) {
        selector.addEventListener('change', () => actualizarEstadoBotones());
        actualizarEstadoBotones();
    }

    // Sincronizar líneas → luego refrescar el selector
    if (btnLineas) {
        btnLineas.addEventListener('click', async () => {
            await ejecutar(btnLineas, () => Api.sincronizacion.lineas(), 'Sincronizando líneas');
            await cargarSelectorLineas();
        });
    }

    // Sincronizar todo (una línea o todas)
    if (btnTodo) {
        btnTodo.addEventListener('click', async () => {
            const valor = selector?.value || '';
            const lineaId = esLineaEspecifica(valor) ? parseInt(valor) : null;

            const etiqueta = lineaId
                ? 'Sincronizando todo (línea seleccionada)'
                : 'Sincronizando todo el sistema';

            await ejecutar(btnTodo, () => Api.sincronizacion.todo(lineaId), etiqueta);
            await cargarSelectorLineas();
        });
    }

    // Sincronizar programas de una línea específica
    if (btnProgramas) {
        btnProgramas.addEventListener('click', async () => {
            const valor = selector?.value || '';
            if (!esLineaEspecifica(valor)) return;
            await ejecutar(
                btnProgramas,
                () => Api.sincronizacion.programas(parseInt(valor)),
                'Sincronizando programas'
            );
        });
    }

    // Sincronizar lotes de una línea específica
    if (btnLotes) {
        btnLotes.addEventListener('click', async () => {
            const valor = selector?.value || '';
            if (!esLineaEspecifica(valor)) return;
            await ejecutar(
                btnLotes,
                () => Api.sincronizacion.lotes(parseInt(valor)),
                'Sincronizando lotes'
            );
        });
    }

    // Sincronizar estadísticas (una línea o todas)
    if (btnEstadisticas) {
        btnEstadisticas.addEventListener('click', async () => {
            const valor = selector?.value || '';
            const lineaId = esLineaEspecifica(valor) ? parseInt(valor) : null;

            // Forzar recaptura si el usuario tiene Ctrl (o Alt) presionado
            // → útil para re-capturar lotes finalizados que ya tienen snapshot
            const forzar = false; // o pon un checkbox en la UI si lo prefieres

            const etiqueta = lineaId
                ? 'Sincronizando estadísticas de la línea seleccionada'
                : 'Sincronizando estadísticas de todas las líneas';

            await ejecutar(
                btnEstadisticas,
                () => Api.sincronizacion.estadisticas(lineaId, forzar),
                etiqueta
            );
        });
    }

    // Refrescar historial
    if (btnRefrescar) {
        btnRefrescar.addEventListener('click', cargarLogs);
    }
});

// =====================================================================
// Utilidades
// =====================================================================

/**
 * Determina si el valor del selector corresponde a una línea específica.
 * Excluye los valores especiales "" (nada), "todas" y "todas_estadisticas".
 */
function esLineaEspecifica(valor) {
    return valor !== '' && valor !== 'todas' && valor !== 'todas_estadisticas' && !isNaN(parseInt(valor));
}

/**
 * Habilita/deshabilita botones según el valor del selector.
 *   ""                    → solo "Sincronizar líneas", "Sincronizar todo" y "Sincronizar estadísticas"
 *   número                → todos los botones disponibles
 *   "todas"               → "Sincronizar todo" itera todas; programas/lotes deshabilitados
 *   "todas_estadisticas"  → solo "Sincronizar estadísticas" (todas)
 */
function actualizarEstadoBotones() {
    const selector = document.getElementById('selector-linea');
    const btnProgramas = document.getElementById('btn-sinc-programas');
    const btnLotes = document.getElementById('btn-sinc-lotes');
    const btnEstadisticas = document.getElementById('btn-sinc-estadisticas');
    if (!selector) return;

    const valor = selector.value;
    const esEspecifica = esLineaEspecifica(valor);
    const esTodas = valor === 'todas' || valor === 'todas_estadisticas';

    // Estadísticas siempre disponible
    if (btnEstadisticas) btnEstadisticas.disabled = false;

    // Programas y lotes solo cuando hay una línea específica seleccionada
    if (btnProgramas) btnProgramas.disabled = !esEspecifica;
    if (btnLotes) btnLotes.disabled = !esEspecifica;

    // Tooltips explicativos
    if (btnProgramas) btnProgramas.title = esTodas
        ? 'Selecciona una línea específica para usar esta acción'
        : '';
    if (btnLotes) btnLotes.title = esTodas
        ? 'Selecciona una línea específica para usar esta acción'
        : '';
}

// =====================================================================
// Cargar el selector de líneas (sin destruir las opciones estáticas)
// =====================================================================
async function cargarSelectorLineas() {
    const selector = document.getElementById('selector-linea');
    if (!selector) return;

    // Preservar la selección actual
    const valorActual = selector.value;

    const respuesta = await Api.sincronizacion.listarLineas();
    if (!respuesta.ok || !respuesta.data || !respuesta.data.success) {
        return;
    }

    const lineas = respuesta.data.data || [];

    // -----------------------------------------------------------------
    // 1. Eliminar solo las opciones dinámicas (las que insertó el JS antes)
    // -----------------------------------------------------------------
    selector.querySelectorAll('option[data-dinamica="1"]').forEach(o => o.remove());

    // -----------------------------------------------------------------
    // 2. Insertar las nuevas opciones dinámicas antes de la opción "todas"
    // -----------------------------------------------------------------
    const opcionTodas = selector.querySelector('option[data-todas="1"]');

    for (const l of lineas) {
        const opt = document.createElement('option');
        opt.value = l.id;
        opt.dataset.dinamica = '1';
        opt.dataset.codigo = l.linea;
        opt.textContent = l.descripcion
            ? `${l.linea} — ${l.descripcion}`
            : l.linea;

        if (opcionTodas) {
            selector.insertBefore(opt, opcionTodas);
        } else {
            selector.appendChild(opt);
        }
    }

    // -----------------------------------------------------------------
    // 3. Gestionar las opciones "todas" y "todas_estadisticas"
    // -----------------------------------------------------------------
    const todasExiste = !!opcionTodas;
    const todasStatsExiste = !!selector.querySelector('option[value="todas_estadisticas"]');

    if (lineas.length === 0) {
        // Sin líneas: quitar ambas opciones especiales
        if (todasExiste) opcionTodas.remove();
        if (todasStatsExiste) selector.querySelector('option[value="todas_estadisticas"]').remove();
    } else {
        // Con líneas: crear las opciones especiales si no existen
        if (!todasExiste) {
            const optTodas = document.createElement('option');
            optTodas.value = 'todas';
            optTodas.dataset.estatica = '1';
            optTodas.dataset.todas = '1';
            optTodas.style.fontWeight = '600';
            optTodas.style.color = 'var(--verde)';
            optTodas.textContent = '— Sincronizar todas las líneas —';
            selector.appendChild(optTodas);
        }

        if (!todasStatsExiste) {
            const optTodasStats = document.createElement('option');
            optTodasStats.value = 'todas_estadisticas';
            optTodasStats.dataset.estatica = '1';
            optTodasStats.style.fontWeight = '600';
            optTodasStats.style.color = 'var(--azul)';
            optTodasStats.textContent = '— Sincronizar estadísticas de TODAS las líneas —';
            selector.appendChild(optTodasStats);
        }
    }

    // -----------------------------------------------------------------
    // 4. Restaurar la selección previa si aún es válida
    // -----------------------------------------------------------------
    const existeValor =
        valorActual === 'todas' ||
        valorActual === 'todas_estadisticas' ||
        (esLineaEspecifica(valorActual) && lineas.some(l => String(l.id) === String(valorActual)));

    selector.value = existeValor ? valorActual : '';

    actualizarEstadoBotones();
}

// =====================================================================
// Ejecutar una acción con feedback (botón deshabilitado + notificación)
// =====================================================================
async function ejecutar(boton, funcion, etiqueta) {
    const textoOriginal = boton.innerHTML;
    boton.disabled = true;
    boton.innerHTML = '<span>Procesando...</span>';

    mostrarEstado('info', etiqueta + '... Por favor espera.');

    try {
        const respuesta = await funcion();

        if (!respuesta.ok || !respuesta.data || !respuesta.data.success) {
            const mensaje = respuesta.data?.message || 'Error desconocido';
            notificar(mensaje, 'error');
            mostrarEstado('error', mensaje);
        } else {
            const mensaje = respuesta.data.message || 'Operación completada';
            notificar(mensaje, 'exito');
            mostrarEstado('exito', mensaje);
        }
    } catch (e) {
        notificar('Error inesperado: ' + e.message, 'error');
        mostrarEstado('error', e.message);
    } finally {
        boton.disabled = false;
        boton.innerHTML = textoOriginal;
        cargarLogs();
    }
}

// =====================================================================
// Mostrar bloque de estado en la UI
// =====================================================================
function mostrarEstado(tipo, mensaje) {
    const panel = document.getElementById('panel-estado');
    const contenido = document.getElementById('estado-contenido');
    if (!panel || !contenido) return;

    panel.style.display = 'block';

    const clases = {
        exito: 'alerta-exito',
        error: 'alerta-error',
        info: 'alerta-info',
        aviso: 'alerta-aviso'
    };

    contenido.innerHTML = `<div class="alerta ${clases[tipo] || 'alerta-info'}">${escaparHtml(mensaje)}</div>`;
}

// =====================================================================
// Cargar historial de sincronización
// =====================================================================
async function cargarLogs() {
    const tbody = document.getElementById('tabla-logs');
    if (!tbody) return;

    const respuesta = await Api.sincronizacion.logs();

    if (!respuesta.ok || !respuesta.data || !respuesta.data.success) {
        tbody.innerHTML = '<tr><td colspan="5" class="vacio">No se pudo cargar el historial.</td></tr>';
        return;
    }

    const logs = respuesta.data.data || [];

    if (!logs.length) {
        tbody.innerHTML = '<tr><td colspan="5" class="vacio">Aún no hay sincronizaciones registradas.</td></tr>';
        return;
    }

    tbody.innerHTML = logs.map(log => {
        const ok = log.codigo_respuesta >= 200 && log.codigo_respuesta < 300;
        const estado = ok
            ? `<span style="color: var(--verde);">✔ ${log.codigo_respuesta}</span>`
            : `<span style="color: var(--rojo);">✖ ${log.codigo_respuesta || 'Error'}</span>`;

        return `
            <tr>
                <td>${formatearFecha(log.created_at, true)}</td>
                <td><code>${escaparHtml(log.metodo)}</code></td>
                <td><code>${escaparHtml(log.endpoint)}</code></td>
                <td>${estado}</td>
                <td>${log.tiempo_ejecucion_ms ?? '—'} ms</td>
            </tr>
        `;
    }).join('');
}