// public/assets/js/lote_detalle.js

document.addEventListener('DOMContentLoaded', () => {
    cargarDetalle();

    // Evento para el botón de exportar
    const btnExportar = document.getElementById('btn-exportar-excel');
    if (btnExportar) {
        btnExportar.addEventListener('click', () => {
            // Redirigimos al endpoint de PHP que genera el Excel
            window.location.href = BASE_URL + '/api/exportar_excel.php?id=' + LOTE_ID;
        });
    }

    const btnExportarPdf = document.getElementById('btn-exportar-pdf');
    if (btnExportarPdf) {
        btnExportarPdf.addEventListener('click', generarPdf);
    }
});

function generarPdf() {
    const elemento = document.getElementById('contenedor-detalle');
    const headerPdf = document.getElementById('pdf-header');

    if (!elemento || elemento.style.display === 'none') {
        notificar('Espera a que cargue la información del lote.', 'aviso');
        return;
    }

    const btn = document.getElementById('btn-exportar-pdf');
    const textoOriginal = btn.innerHTML;
    btn.innerHTML = '<span>Generando PDF...</span>';
    btn.disabled = true;

    // 1. Mostrar la cabecera temporalmente
    headerPdf.style.display = 'block';

    // Opcional: Si quieres que la cabecera esté DENTRO del PDF, 
    // creamos un contenedor temporal que envuelva todo.
    const wrapper = document.createElement('div');
    wrapper.appendChild(headerPdf.cloneNode(true));
    wrapper.appendChild(elemento.cloneNode(true));

    // Ocultamos el original para que el clon no se vea afectado por el display:none
    elemento.style.display = 'none';

    const opciones = {
        margin: [10, 10, 10, 10],
        filename: 'Reporte_Calidad_' + document.getElementById('titulo-lote').textContent + '.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
        pagebreak: { mode: ['avoid-all', 'css', 'legacy'] }
    };

    // 2. Generar el PDF desde el wrapper
    html2pdf().set(opciones).from(wrapper).save().then(() => {
        // 3. Restaurar el estado original
        headerPdf.style.display = 'none';
        elemento.style.display = 'block';
        btn.innerHTML = textoOriginal;
        btn.disabled = false;
        notificar('PDF generado correctamente', 'exito');
    }).catch(err => {
        console.error(err);
        headerPdf.style.display = 'none';
        elemento.style.display = 'block';
        btn.innerHTML = textoOriginal;
        btn.disabled = false;
        notificar('Error al generar el PDF', 'error');
    });
}

async function cargarDetalle() {
    const r = await Api.peticion(`/api/lotes.php?id=${LOTE_ID}`);

    if (!r.ok || !r.data?.success) {
        document.getElementById('mensaje-vacio').innerHTML = '<p>Error al cargar el lote.</p>';
        return;
    }

    const lote = r.data.data;
    const conteo = lote.conteo;
    const clases = lote.clases || [];
    const distribuciones = lote.distribuciones || [];

    // Llenar datos de la cabecera para el PDF
    document.getElementById('pdf-lote-nombre').textContent = lote.nombre_lote;
    document.getElementById('pdf-lote-id').textContent = lote.lot_id_api || '—';
    document.getElementById('pdf-lote-variedad').textContent = lote.descripcion_variedad || '—';

    const ahora = new Date();
    document.getElementById('pdf-fecha-generacion').textContent = ahora.toLocaleDateString('es-PE') + ' ' + ahora.toLocaleTimeString('es-PE');

    // Encabezado
    document.getElementById('titulo-lote').textContent = lote.nombre_lote;
    document.getElementById('subtitulo-lote').textContent = `ID API: ${lote.lot_id_api} | Variedad: ${lote.descripcion_variedad || '—'}`;

    // Mostrar contenedor
    document.getElementById('mensaje-vacio').style.display = 'none';
    document.getElementById('contenedor-detalle').style.display = 'block';

    // --- Datos Generales ---
    document.getElementById('info-lote').innerHTML = `
        <div class="dato-fila"><strong>ID API:</strong> <span>${lote.lot_id_api || '—'}</span></div>
        <div class="dato-fila"><strong>Nombre:</strong> <span>${escaparHtml(lote.nombre_lote)}</span></div>
        <div class="dato-fila"><strong>Programa:</strong> <span>${escaparHtml(lote.programa) || '—'}</span></div>
        <div class="dato-fila"><strong>Código Externo:</strong> <span>${escaparHtml(lote.codigo_externo) || '—'}</span></div>
        <div class="dato-fila"><strong>Programa:</strong> <span>${escaparHtml(lote.descripcion_mercancia) || '—'}</span></div>
        <div class="dato-fila"><strong>Variedad:</strong> <span>${escaparHtml(lote.descripcion_variedad) || '—'}</span></div>
        <div class="dato-fila"><strong>Cajas:</strong> <span>${escaparHtml(lote.paquetes) || '0'}</span></div>
        <div class="dato-fila"><strong>Fecha Inicio:</strong> <span>${formatearFecha(lote.fecha_inicio, true)}</span></div>
        <div class="dato-fila"><strong>Fecha Fin:</strong> <span>${formatearFecha(lote.fecha_fin, true)}</span></div>
    `;

    // --- Calidad Interna ---
    if (conteo) {
        document.getElementById('info-interna').innerHTML = `
            <div class="dato-fila"><strong>Brix Promedio:</strong> <span>${conteo.brix_promedio ?? '—'}</span></div>
            <div class="dato-fila"><strong>Brix Bajo Umbral:</strong> <span>${conteo.brix_bajo_umbral ?? '—'} %</span></div>
            <div class="dato-fila"><strong>Acidez Promedio:</strong> <span>${conteo.acidez_promedio ?? '—'}</span></div>
            <div class="dato-fila"><strong>Acidez Alta Umbral:</strong> <span>${conteo.acidez_alta_umbral ?? '—'} %</span></div>
            <div class="dato-fila"><strong>Fecha Análisis:</strong> <span>${formatearFecha(conteo.consultado_en, true)}</span></div>
        `;
    } else {
        document.getElementById('info-interna').innerHTML = '<p class="vacio">Sin análisis de calidad interna registrado.</p>';
    }

    // --- Tabla Clases ---
    const tbodyClases = document.querySelector('#tabla-clases tbody');
    if (clases.length > 0) {
        tbodyClases.innerHTML = clases.map(c => `
            <tr>
                <td>${escaparHtml(c.nombre_clase)}</td>
                <td>${Number(c.porcentaje_clase).toFixed(2)}%</td>
            </tr>
        `).join('');
    } else {
        tbodyClases.innerHTML = '<tr><td colspan="2" class="vacio">Sin clases registradas.</td></tr>';
    }

    // --- Cuadros de Medidas y Defectos ---

    // Función auxiliar para llenar las mini-tablas
    const renderMiniTabla = (selector, datos, esNombreRango = true) => {
        const tbody = document.querySelector(`${selector} tbody`);
        if (!tbody) return;

        if (datos.length === 0) {
            tbody.innerHTML = '<tr><td colspan="2" class="vacio" style="text-align:center;">Sin datos</td></tr>';
            return;
        }

        tbody.innerHTML = datos.map(d => `
            <tr>
                <td>${escaparHtml(d.rango)}</td>
                <td style="text-align:right;">${Number(d.porcentaje).toFixed(2)}%</td>
            </tr>
        `).join('');
    };

    // 1. Filtrar los datos por criterio
    const datosCalibres = distribuciones.filter(d => d.criterio === 'AvgDiameter');
    const datosDefectos = distribuciones.filter(d => d.criterio === 'Defects');
    const datosCalidad = distribuciones.filter(d => d.criterio === 'PercColor');
    const datosColores = distribuciones.filter(d => d.criterio === 'Weight');

    // 2. Llenar cada tabla
    renderMiniTabla('#tabla-calibres', datosCalibres);
    renderMiniTabla('#tabla-defectos', datosDefectos);
    renderMiniTabla('#tabla-calidad', datosCalidad);
    renderMiniTabla('#tabla-colores', datosColores);

}
