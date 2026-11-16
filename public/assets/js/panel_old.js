/* =====================================================================
   panel.js - Lógica del dashboard principal
   =====================================================================
   Carga el resumen desde /api/panel.php y rellena las tarjetas y tablas.
   ===================================================================== */

document.addEventListener('DOMContentLoaded', () => {

    // Carga inicial
    cargarResumen();

    // Botón de refrescar manual
    const btnRefrescar = document.getElementById('btn-refrescar');
    if (btnRefrescar) {
        btnRefrescar.addEventListener('click', async () => {
            btnRefrescar.disabled = true;
            await cargarResumen();
            btnRefrescar.disabled = false;
            notificar('Datos actualizados correctamente', 'exito');
        });
    }

    // Auto-refresco cada 60 segundos (solo si la pestaña está visible)
    setInterval(() => {
        if (document.visibilityState === 'visible') {
            cargarResumen(true); // silencioso
        }
    }, 60000);
});

/**
 * Carga el resumen del sistema y actualiza la UI.
 * @param {boolean} silencioso Si es true, no muestra notificaciones de error.
 */
async function cargarResumen(silencioso = false) {
    const respuesta = await Api.panel.resumen();

    if (!respuesta.ok || !respuesta.data || !respuesta.data.success) {
        const mensaje = respuesta.data?.message || 'No se pudo cargar el resumen';
        if (!silencioso) notificar(mensaje, 'error');
        return;
    }

    const datos = respuesta.data.data;

    // Actualizar tarjetas
    document.getElementById('stat-lineas').textContent         = datos.total_lineas    ?? 0;
    document.getElementById('stat-programas').textContent      = datos.total_programas ?? 0;
    document.getElementById('stat-lotes').textContent          = datos.total_lotes     ?? 0;
    document.getElementById('stat-lotes-activos').textContent  = datos.lotes_activos   ?? 0;

    // Rellenar tablas
    renderUltimosLotes(datos.ultimos_lotes || []);
    renderLineas(datos.lineas || []);
}

/**
 * Renderiza la tabla de últimos lotes.
 */
function renderUltimosLotes(lotes) {
    const tbody = document.getElementById('tabla-ultimos-lotes');
    if (!tbody) return;

    if (!lotes.length) {
        tbody.innerHTML = '<tr><td colspan="5" class="vacio">No hay lotes registrados todavía.</td></tr>';
        return;
    }

    tbody.innerHTML = lotes.map(lote => `
        <tr>
            <td><strong>${escaparHtml(lote.nombre_lote)}</strong></td>
            <td>${escaparHtml(lote.codigo_externo) || '—'}</td>
            <td>${escaparHtml(lote.descripcion_productor) || '—'}</td>
            <td>${escaparHtml(lote.descripcion_variedad) || '—'}</td>
            <td>${formatearFecha(lote.fecha_inicio, true)}</td>
        </tr>
    `).join('');
}

/**
 * Renderiza la tabla de líneas.
 */
function renderLineas(lineas) {
    const tbody = document.getElementById('tabla-lineas');
    if (!tbody) return;

    if (!lineas.length) {
        tbody.innerHTML = '<tr><td colspan="3" class="vacio">No hay líneas registradas.</td></tr>';
        return;
    }

    tbody.innerHTML = lineas.map(linea => `
        <tr>
            <td><strong>${escaparHtml(linea.linea)}</strong></td>
            <td>${escaparHtml(linea.descripcion) || '—'}</td>
            <td>${escaparHtml(linea.planta) || '—'}</td>
        </tr>
    `).join('');
}