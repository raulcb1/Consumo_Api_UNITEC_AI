// public/assets/js/lotes.js

document.addEventListener('DOMContentLoaded', () => {
    cargarLotes();

    // Filtros
    document.getElementById('btn-aplicar-filtros')?.addEventListener('click', cargarLotes);
    document.getElementById('btn-limpiar-filtros')?.addEventListener('click', limpiarFiltros);
    document.getElementById('f-buscar')?.addEventListener('keydown', e => { if (e.key === 'Enter') cargarLotes(); });

    // Cambio de línea en el modal (aunque ya no creamos lotes, por si acaso)
    document.getElementById('lote-linea')?.addEventListener('change', e => cargarProgramas(e.target.value));

    // Guardar lote (actualización de datos locales)
    document.getElementById('btn-guardar-lote')?.addEventListener('click', guardarLote);
});

async function cargarLotes() {
    const tbody = document.getElementById('tabla-lotes');
    tbody.innerHTML = '<tr><td colspan="10" class="vacio">Cargando...</td></tr>';

    const filtros = {};
    const lineaId = document.getElementById('f-linea').value;
    if (lineaId) filtros.linea_id = lineaId;

    const buscar = document.getElementById('f-buscar').value.trim();
    if (buscar) filtros.buscar = buscar;

    const desde = document.getElementById('f-desde').value;
    if (desde) filtros.fecha_inicio = desde + ' 00:00:00';

    const hasta = document.getElementById('f-hasta').value;
    if (hasta) filtros.fecha_fin = hasta + ' 23:59:59';

    const r = await Api.lotes.listar(filtros);
    if (!r.ok || !r.data?.success) {
        tbody.innerHTML = `<tr><td colspan="10" class="vacio">${r.data?.message || 'Error al cargar'}</td></tr>`;
        return;
    }

    const lotes = r.data.data || [];
    document.getElementById('contador-lotes').textContent = `${lotes.length} lote(s)`;

    if (!lotes.length) {
        tbody.innerHTML = '<tr><td colspan="10" class="vacio">No hay lotes que coincidan con los filtros.</td></tr>';
        return;
    }

    tbody.innerHTML = lotes.map(l => {
        const estado = l.fecha_fin
            ? '<span style="color: var(--ink-soft);">Finalizado</span>'
            : '<span style="color: var(--verde); font-weight: 600;">● En Proceso</span>';

        let acciones = `<a href="${BASE_URL}/lotes/detalle.php?id=${l.id}" class="boton boton-borde boton-sm" title="Ver detalle">${svgVer()}</a>`;

        if (PUEDE_EDITAR) {
            acciones += ` <button class="boton boton-borde boton-sm" onclick="abrirModalEditar(${l.id})" title="Editar datos locales">${svgEditar()}</button>`;
        }
        if (PUEDE_ELIMINAR) {
            acciones += ` <button class="boton boton-peligro boton-sm" onclick="eliminarLote(${l.id})" title="Eliminar copia local">${svgEliminar()}</button>`;
        }

        return `
            <tr>
                <td><code>${l.lot_id_api || '—'}</code></td>
                <td><strong>${escaparHtml(l.nombre_lote)}</strong></td>
                <td>${escaparHtml(l.linea_codigo)}</td>
                <td>${escaparHtml(l.descripcion_variedad) || '—'}</td>
                <td>${escaparHtml(l.descripcion_productor) || '—'}</td>
                <td>${escaparHtml(l.paquetes) || '0'}</td>
                <td>${formatearFecha(l.fecha_inicio, true)}</td>
                <td>${formatearFecha(l.fecha_fin, true)}</td>
                <td>${estado}</td>
                <td><div class="acciones">${acciones}</div></td>
            </tr>
        `;
    }).join('');
}

function limpiarFiltros() {
    document.getElementById('f-linea').value = '';
    document.getElementById('f-buscar').value = '';
    document.getElementById('f-desde').value = '';
    document.getElementById('f-hasta').value = '';
    cargarLotes();
}

// --- MODAL DE EDICIÓN (Solo campos locales) ---
async function abrirModalEditar(id) {
    const r = await Api.lotes.obtener(id);
    if (!r.ok || !r.data?.success) {
        notificar('No se pudo cargar el lote', 'error');
        return;
    }
    const l = r.data.data;

    document.getElementById('modal-lote-titulo').textContent = 'Editar Lote (Datos Locales)';
    document.getElementById('lote-id').value = l.id;

    // Campos bloqueados (vienen de UNITEC)
    document.getElementById('lote-linea').value = l.linea_id;
    document.getElementById('lote-linea').disabled = true;
    document.getElementById('lote-nombre').value = l.nombre_lote;
    document.getElementById('lote-nombre').disabled = true;

    // Resto de campos editables (ERP, notas, etc.)
    document.getElementById('lote-codigo').value = l.codigo_externo || '';
    document.getElementById('lote-cod-productor').value = l.codigo_productor || '';
    document.getElementById('lote-desc-productor').value = l.descripcion_productor || '';
    document.getElementById('lote-cod-mercancia').value = l.codigo_mercancia || '';
    document.getElementById('lote-desc-mercancia').value = l.descripcion_mercancia || '';
    document.getElementById('lote-cod-grupo-variedad').value = l.codigo_grupo_variedad || '';
    document.getElementById('lote-desc-grupo-variedad').value = l.descripcion_grupo_variedad || '';
    document.getElementById('lote-cod-variedad').value = l.codigo_variedad || '';
    document.getElementById('lote-desc-variedad').value = l.descripcion_variedad || '';
    document.getElementById('lote-paquetes').value = l.paquetes || '';
    document.getElementById('lote-peso').value = l.peso || '';
    document.getElementById('lote-notas').value = l.notas || '';

    abrirModal('modal-lote');
}

async function guardarLote() {
    const id = document.getElementById('lote-id').value;
    if (!id) return;

    // Solo enviamos campos locales
    const datos = {
        codigo_externo: document.getElementById('lote-codigo').value.trim() || null,
        codigo_productor: document.getElementById('lote-cod-productor').value.trim() || null,
        descripcion_productor: document.getElementById('lote-desc-productor').value.trim() || null,
        codigo_mercancia: document.getElementById('lote-cod-mercancia').value.trim() || null,
        descripcion_mercancia: document.getElementById('lote-desc-mercancia').value.trim() || null,
        codigo_grupo_variedad: document.getElementById('lote-cod-grupo-variedad').value.trim() || null,
        descripcion_grupo_variedad: document.getElementById('lote-desc-grupo-variedad').value.trim() || null,
        codigo_variedad: document.getElementById('lote-cod-variedad').value.trim() || null,
        descripcion_variedad: document.getElementById('lote-desc-variedad').value.trim() || null,
        paquetes: document.getElementById('lote-paquetes').value.trim() || null,
        peso: document.getElementById('lote-peso').value.trim() || null,
        notas: document.getElementById('lote-notas').value.trim() || null
    };

    const btn = document.getElementById('btn-guardar-lote');
    btn.disabled = true;

    const r = await Api.lotes.actualizar(id, datos);
    btn.disabled = false;

    if (r.ok && r.data?.success) {
        notificar(r.data.message || 'Guardado', 'exito');
        cerrarModal('modal-lote');
        cargarLotes();
    } else {
        notificar(r.data?.message || 'Error al guardar', 'error');
    }
}

async function eliminarLote(id) {
    if (!confirmar('¿Eliminar este lote de la base de datos local? (El lote seguirá existiendo en el equipo UNITEC)')) return;
    const r = await Api.lotes.eliminar(id);
    if (r.ok && r.data?.success) {
        notificar('Copia local eliminada', 'exito');
        cargarLotes();
    } else {
        notificar(r.data?.message || 'Error al eliminar', 'error');
    }
}

// --- SVGs Auxiliares ---
function svgVer() { return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>'; }
function svgEditar() { return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>'; }
function svgEliminar() { return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>'; }