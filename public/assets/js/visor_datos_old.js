// public/assets/js/visor_datos.js

let tablaClases = null;
let tablaDistribuciones = null;

document.addEventListener('DOMContentLoaded', async () => {
    const selector = document.getElementById('selector-lote-visor');
    const btnExport = document.getElementById('btn-export-all');
    
    // 1. Cargar lista de lotes
    try {
        const r = await Api.peticion('/api/visordatos.php?accion=listar_lotes');
        if (r.ok && r.data?.success) {
            const lotes = r.data.data;
            lotes.forEach(l => {
                const fecha = l.fecha_inicio ? l.fecha_inicio.split(' ')[0] : 'Sin fecha';
                selector.add(new Option(`${l.lot_id_api || l.id} - ${l.nombre_lote} - ${fecha}`, l.id));
            });
            selector.options[0].text = '--- Seleccionar un lote ---';
        }
    } catch (e) {
        console.error('Error al cargar lotes', e);
    }

    // 2. Evento al seleccionar lote
    selector.addEventListener('change', async (e) => {
        const id = e.target.value;
        if (!id) {
            document.getElementById('contenedor-visor').style.display = 'none';
            document.getElementById('mensaje-vacio').style.display = 'block';
            btnExport.disabled = true;
            return;
        }

        document.getElementById('mensaje-vacio').style.display = 'none';
        document.getElementById('contenedor-visor').style.display = 'block';
        btnExport.disabled = false;
        cargarDetalle(id);
    });

    // 3. Evento para Exportar TODO a Excel (Vía PHP)
    btnExport.addEventListener('click', () => {
        const idLote = selector.value;
        if (!idLote) {
            notificar('Selecciona un lote primero', 'aviso');
            return;
        }
        
        // Redirigimos al endpoint PHP que genera el Excel con la plantilla
        window.location.href = BASE_URL + '/api/exportar_excel.php?id=' + idLote;
    });
});

async function cargarDetalle(id) {
    const r = await Api.peticion(`/api/visordatos.php?accion=detalle&id=${id}`);
    if (!r.ok || !r.data?.success) {
        notificar('Error al cargar detalle', 'error');
        return;
    }

    const { lote, conteo, clases, distribuciones } = r.data.data;

    // --- Lote Info ---
    document.getElementById('info-lote').innerHTML = `
        <div class="dato-fila"><strong>ID API:</strong> <span>${lote.lot_id_api}</span></div>
        <div class="dato-fila"><strong>Nombre:</strong> <span>${lote.nombre_lote}</span></div>
        <div class="dato-fila"><strong>Variedad:</strong> <span>${lote.descripcion_variedad || '—'}</span></div>
        <div class="dato-fila"><strong>Fecha Inicio:</strong> <span>${lote.fecha_inicio || '—'}</span></div>
        <div class="dato-fila"><strong>Fecha Fin:</strong> <span>${lote.fecha_fin || '—'}</span></div>
        <div class="dato-fila"><strong>Cajas (boxNumber):</strong> <span>${lote.paquetes}</span></div>
    `;

    // --- Calidad Interna ---
    if (conteo) {
        document.getElementById('info-interna').innerHTML = `
            <div class="dato-fila"><strong>Brix Medio:</strong> <span>${conteo.brix_promedio ?? 'null'}</span></div>
            <div class="dato-fila"><strong>Brix Bajo Umbral:</strong> <span>${conteo.brix_bajo_umbral ?? 'null'} %</span></div>
            <div class="dato-fila"><strong>Acidez Media:</strong> <span>${conteo.acidez_promedio ?? 'null'}</span></div>
            <div class="dato-fila"><strong>Acidez Alta Umbral:</strong> <span>${conteo.acidez_alta_umbral ?? 'null'} %</span></div>
        `;
    } else {
        document.getElementById('info-interna').innerHTML = '<p class="vacio">Sin conteo registrado.</p>';
    }

    // --- Tabla Clases ---
    if (tablaClases) tablaClases.destroy();
    tablaClases = new Tabulator("#tabla-clases", {
        data: clases,
        layout: "fitColumns",
        columns: [
            { title: "Clase", field: "nombre_clase", width: 200 },
            { title: "Porcentaje (%)", field: "porcentaje_clase", sorter: "number" }
        ]
    });

    // --- Tabla Distribuciones ---
    if (tablaDistribuciones) tablaDistribuciones.destroy();
    tablaDistribuciones = new Tabulator("#tabla-distribuciones", {
        data: distribuciones,
        layout: "fitColumns",
        groupBy: "criterio",
        groupHeader: function(value, count, data, group){
            let titulo = value;
            if(value === 'AvgDiameter') titulo = 'Calibres (AvgDiameter)';
            if(value === 'Weight') titulo = 'Colores (Weight)';
            if(value === 'Defects') titulo = 'Defectos (Defects)';
            if(value === 'PercColor') titulo = 'Calidad (PercColor)';
            
            return `<strong style="color:var(--verde);">${titulo}</strong> <span style="color:#64748b;">(${count} registros)</span>`;
        },
        columns: [
            { title: "Rango / Regla", field: "rango", width: 250 },
            { title: "Porcentaje (%)", field: "porcentaje", sorter: "number" },
            { title: "Criterio Original", field: "criterio", width: 150 }
        ]
    });
}