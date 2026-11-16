/* =====================================================================
   reportes.js - Catálogo de reportes
   ===================================================================== */

document.addEventListener('DOMContentLoaded', cargarCatalogo);

async function cargarCatalogo() {
    const grid = document.getElementById('grid-reportes');
    const r = await Api.peticion('/api/reportes.php?accion=catalogo');

    if (!r.ok || !r.data?.success) {
        grid.innerHTML = `<p style="grid-column:1/-1; color: var(--rojo);">${r.data?.message || 'Error al cargar'}</p>`;
        return;
    }

    const reportes = r.data.data || [];
    if (!reportes.length) {
        grid.innerHTML = '<p style="grid-column:1/-1; color: var(--ink-soft);">No hay reportes disponibles.</p>';
        return;
    }

    grid.innerHTML = reportes.map(rep => `
        <a href="${BASE_URL}/reportes/ver.php?codigo=${encodeURIComponent(rep.codigo)}" class="tarjeta-reporte">
            <div class="titulo">${escaparHtml(rep.nombre)}</div>
            <div class="descripcion">${escaparHtml(rep.descripcion || '')}</div>
            <div class="categoria">${escaparHtml(rep.categoria || '')}</div>
        </a>
    `).join('');
}