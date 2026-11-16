// public/assets/js/panel.js

// =====================================================================
// CONFIGURACIÓN DE DEBUG
// Cambia a 0 para ocultar los botones de debug, o a 1 para mostrarlos.
// =====================================================================
const enDebug = 1;

let datosGlobales = { datos_lote: [], interna: [], clases: [], medidas: [] };
let datosFiltrados = { datos_lote: [], interna: [], clases: [], medidas: [] };
let tablaCrudos = null;

let chartCalibres, chartDefectos, chartColores;

document.addEventListener('DOMContentLoaded', async () => {
    document.getElementById('pdf-fecha').textContent = new Date().toLocaleDateString('es-PE') + ' ' + new Date().toLocaleTimeString('es-PE');

    // Mostrar u ocultar botones de debug según la variable
    if (enDebug === 1) {
        document.querySelectorAll('.btn-debug').forEach(btn => btn.style.display = 'inline-block');

        // Asignar eventos a los botones de debug
        document.querySelectorAll('.btn-debug').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const chartName = e.target.getAttribute('data-chart');
                mostrarDebug(chartName);
            });
        });
    }

    await cargarDatos();

    document.getElementById('btn-aplicar').addEventListener('click', aplicarFiltros);

    // Botones de fecha rápida
    document.querySelectorAll('.btn-fecha').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const rango = e.target.getAttribute('data-rango');
            const hoy = new Date();
            let inicio = new Date();
            if (rango === 'mes') inicio.setDate(hoy.getDate() - 30);
            else if (rango === '3meses') inicio.setDate(hoy.getDate() - 90);
            else if (rango === 'año') inicio = new Date(hoy.getFullYear(), 0, 1);

            document.getElementById('f-inicio').value = inicio.toISOString().split('T')[0];
            document.getElementById('f-fin').value = hoy.toISOString().split('T')[0];
            aplicarFiltros();
        });
    });

    // Exportar PDF
    document.getElementById('btn-export-pdf').addEventListener('click', () => {
        const elemento = document.getElementById('contenedor-pdf');
        const header = document.getElementById('pdf-header');

        header.style.display = 'block';
        html2pdf().set({
            margin: [10, 10],
            filename: 'Resumen_Calidad_Frusan.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2 },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' }
        }).from(elemento).save().then(() => {
            header.style.display = 'none';
        });
    });

    // Exportar Excel (Con estilos)
    document.getElementById('btn-export-excel').addEventListener('click', () => {
        const dataTabla = tablaCrudos.getData();
        const wb = XLSX.utils.book_new();

        // Renombrar las columnas para el Excel
        const excelData = dataTabla.map(row => ({
            "ID API": row.lot_id,
            "Lote": row.nombre,
            "Programa": row.programa,
            "Fecha": row.fecha,
            "Cajas": row.cajas,
            "Brix": row.brix,
            "% BUENO": row.bueno,
            "% CAT2": row.cat2,
            "Calibres": row.calibres,
            "Defectos": row.defectos
        }));

        const ws = XLSX.utils.json_to_sheet(excelData);

        // Aplicar estilos a la cabecera
        const range = XLSX.utils.decode_range(ws['!ref']);
        for (let C = range.s.c; C <= range.e.c; ++C) {
            const address = XLSX.utils.encode_col(C) + "1";
            if (!ws[address]) continue;
            ws[address].s = {
                font: { bold: true, color: { rgb: "FFFFFF" } },
                fill: { fgColor: { rgb: "2E7D32" } }, // Verde Frusan
                alignment: { horizontal: "center" }
            };
        }

        // Ancho de las columnas
        ws['!cols'] = [
            { wch: 10 }, { wch: 20 }, { wch: 20 }, { wch: 15 }, { wch: 10 },
            { wch: 10 }, { wch: 10 }, { wch: 10 }, { wch: 30 }, { wch: 30 }
        ];

        XLSX.utils.book_append_sheet(wb, ws, "Datos Crudos");
        XLSX.writeFile(wb, "Datos_Crudos_Lotes.xlsx");
    });

    // Exportar JSON (Para dashboard externo)
    document.getElementById('btn-export-json').addEventListener('click', () => {
        const dataTabla = tablaCrudos.getData();
        const jsonString = JSON.stringify(dataTabla, null, 2);
        const blob = new Blob([jsonString], { type: "application/json" });
        const url = URL.createObjectURL(blob);

        const a = document.createElement('a');
        a.href = url;
        a.download = "Datos_Crudos_Lotes.json";
        a.click();
        URL.revokeObjectURL(url);
    });

    document.getElementById('btn-export-html').addEventListener('click', exportarDashboardHTML);
});

async function cargarDatos() {
    const r = await fetch(BASE_URL + '/api/analitica.php?accion=datos_dashboard').then(res => res.json());
    if (!r.success) return notificar('Error al cargar datos', 'error');

    datosGlobales = r.data;

    const programas = [...new Set(datosGlobales.datos_lote.map(l => l.programa).filter(p => p))].sort();
    const selectProg = document.getElementById('f-programa');
    programas.forEach(p => selectProg.add(new Option(p, p)));

    const fechas = datosGlobales.datos_lote.map(l => l.fecha_inicio.split(' ')[0]).sort();
    if (fechas.length > 0) {
        document.getElementById('f-inicio').value = fechas[0];
        document.getElementById('f-fin').value = fechas[fechas.length - 1];
    }

    aplicarFiltros();
}

function aplicarFiltros() {
    const fIni = document.getElementById('f-inicio').value;
    const fFin = document.getElementById('f-fin').value;
    const prog = document.getElementById('f-programa').value;

    const checkFecha = (fecha) => {
        if (!fecha) return false;
        const d = fecha.split(' ')[0];
        return (!fIni || d >= fIni) && (!fFin || d <= fFin);
    };

    datosFiltrados.datos_lote = datosGlobales.datos_lote.filter(l => {
        return checkFecha(l.fecha_inicio) && (!prog || l.programa === prog);
    });

    const idsLotes = datosFiltrados.datos_lote.map(l => l.lote_id);
    datosFiltrados.interna = datosGlobales.interna.filter(d => idsLotes.includes(d.lote_id));
    datosFiltrados.clases = datosGlobales.clases.filter(d => idsLotes.includes(d.lote_id));
    datosFiltrados.medidas = datosGlobales.medidas.filter(d => idsLotes.includes(d.lote_id));

    actualizarKPIs();
    renderTablaInterna();
    renderCalibres();
    renderDefectos();
    renderColores();
    renderTablaCrudos();
}

function actualizarKPIs() {
    const totalLotes = datosFiltrados.datos_lote.length;
    const totalMuestras = datosFiltrados.datos_lote.reduce((sum, l) => sum + parseInt(l.paquetes || 0), 0);

    let buenoSum = 0, cat2Sum = 0;
    datosFiltrados.medidas.forEach(d => {
        if (d.criterio === 'PercColor') {
            if (d.rango.toUpperCase() === 'PRIMERO') buenoSum += parseFloat(d.porcentaje);
            if (d.rango.toUpperCase() === 'CAT2') cat2Sum += parseFloat(d.porcentaje);
        }
    });

    const totalCalidad = buenoSum + cat2Sum;
    const avgBueno = totalCalidad > 0 ? ((buenoSum / totalCalidad) * 100).toFixed(2) : "0.00";
    const avgCat2 = totalCalidad > 0 ? ((cat2Sum / totalCalidad) * 100).toFixed(2) : "0.00";

    document.getElementById('kpi-lotes').textContent = totalLotes;
    document.getElementById('kpi-muestras').textContent = totalMuestras;
    document.getElementById('kpi-bueno').textContent = avgBueno + '%';
    document.getElementById('kpi-desecho').textContent = avgCat2 + '%';
}

function renderTablaInterna() {
    const tbody = document.querySelector('#tabla-interna tbody');
    if (datosFiltrados.interna.length === 0) {
        tbody.innerHTML = '<tr><td colspan="2" class="vacio">Sin datos</td></tr>';
        return;
    }

    let brix = 0, brixBajo = 0, acidez = 0, acidezAlta = 0;
    datosFiltrados.interna.forEach(l => {
        brix += parseFloat(l.brix_promedio || 0);
        brixBajo += parseFloat(l.brix_bajo_umbral || 0);
        acidez += parseFloat(l.acidez_promedio || 0);
        acidezAlta += parseFloat(l.acidez_alta_umbral || 0);
    });
    const count = datosFiltrados.interna.length;

    tbody.innerHTML = `
        <tr><td><strong>Brix Medio</strong></td><td>${(brix / count).toFixed(2)}</td></tr>
        <tr><td><strong>Brix Bajo Umbral (%)</strong></td><td>${(brixBajo / count).toFixed(1)}%</td></tr>
        <tr><td><strong>Acidez Media</strong></td><td>${(acidez / count).toFixed(2)}</td></tr>
        <tr><td><strong>Acidez Alta Umbral (%)</strong></td><td>${(acidezAlta / count).toFixed(1)}%</td></tr>
    `;
}

// Función auxiliar MEJORADA para agrupar porcentajes y promediarlos
function agruparPorcentajes(criterio) {
    const agrupado = {};

    datosFiltrados.medidas.forEach(d => {
        // 1. Validación estricta: verificamos que el criterio coincida
        if (d.criterio && d.criterio.toUpperCase() === criterio.toUpperCase()) {

            // 2. Convertimos el porcentaje a número. Si es nulo o inválido, lo ignoramos.
            const valorNumerico = parseFloat(d.porcentaje);

            if (!isNaN(valorNumerico)) {
                if (!agrupado[d.rango]) {
                    agrupado[d.rango] = [];
                }
                agrupado[d.rango].push(valorNumerico);
            }
        }
    });

    // 3. Calculamos el promedio real
    return Object.keys(agrupado).map(rango => {
        const valores = agrupado[rango];
        const suma = valores.reduce((a, b) => a + b, 0);
        return {
            rango: rango,
            valor: suma / valores.length
        };
    });
}

function renderCalibres() {
    const datos = agruparPorcentajes('AvgDiameter');
    const ordenCalibres = { '3J': 1, '2J': 2, 'XL': 3, 'J': 4, 'L': 5, 'M': 6, 'S': 7, 'XS': 8, 'PRECALIBRE': 9 };
    datos.sort((a, b) => (ordenCalibres[a.rango.toUpperCase()] || 99) - (ordenCalibres[b.rango.toUpperCase()] || 99));

    const contenedor = document.querySelector("#chart-calibres");
    if (chartCalibres) chartCalibres.destroy();
    if (datos.length === 0) {
        contenedor.innerHTML = '<p class="vacio" style="text-align:center; padding:50px;">Sin datos de calibres.</p>';
        return;
    }
    contenedor.innerHTML = '';

    chartCalibres = new ApexCharts(contenedor, {
        series: [{ name: '%', data: datos.map(d => d.valor.toFixed(2)) }],
        chart: { type: 'bar', height: 300, toolbar: { show: false } },
        plotOptions: { bar: { horizontal: false, distributed: true, borderRadius: 4 } },
        xaxis: { categories: datos.map(d => d.rango.toUpperCase()) },
        colors: ['#008FFB', '#00E396', '#FEB019', '#775DD0', '#546E7A'],
        dataLabels: { enabled: true, formatter: (val) => val + '%' },
        legend: { show: false }
    });
    chartCalibres.render();
}

function renderDefectos() {
    const datos = agruparPorcentajes('Defects').filter(d => d.valor > 0).sort((a, b) => b.valor - a.valor);
    const contenedor = document.querySelector("#chart-defectos");

    if (chartDefectos) chartDefectos.destroy();
    if (datos.length === 0) {
        contenedor.innerHTML = '<p class="vacio" style="text-align:center; padding:50px;">Sin defectos registrados.</p>';
        return;
    }
    contenedor.innerHTML = '';

    chartDefectos = new ApexCharts(contenedor, {
        series: [{ name: '%', data: datos.map(d => d.valor.toFixed(2)) }],
        chart: { type: 'bar', height: 300, toolbar: { show: false } },
        plotOptions: { bar: { horizontal: true, distributed: true, borderRadius: 4 } },
        xaxis: { categories: datos.map(d => d.rango.toUpperCase()) },
        colors: ['#FF4560', '#FEB019', '#FF9800', '#E91E63'],
        dataLabels: { enabled: true, formatter: (val) => val + '%' },
        legend: { show: false }
    });
    chartDefectos.render();
}

// Renderizar Gráfico de Colores MEJORADO
function renderColores() {
    const contenedor = document.querySelector("#chart-colores");

    if (chartColores) {
        chartColores.destroy();
        chartColores = null;
    }

    // Obtenemos los datos y filtramos los que sean mayores a 0
    const datos = agruparPorcentajes('Weight').filter(d => d.valor > 0);

    if (datos.length === 0) {
        contenedor.innerHTML = '<p class="vacio" style="text-align:center; padding:20px;">Sin datos de color visual.</p>';
        return;
    }
    contenedor.innerHTML = '';

    // ApexCharts requiere que la serie sea de números puros, NO strings.
    const series = datos.map(d => Number(d.valor.toFixed(2)));
    const labels = datos.map(d => String(d.rango).toUpperCase());

    chartColores = new ApexCharts(contenedor, {
        series: series,
        labels: labels,
        chart: {
            type: 'pie',
            height: 300,
            fontFamily: 'inherit'
        },
        colors: ['#9E9E9E', '#4A148C', '#4CAF50', '#E91E63', '#FF9800', '#2196F3'],
        legend: { position: 'bottom' },
        dataLabels: {
            enabled: true, formatter: (val) => val.toFixed(2) + '%', style: {
                colors: ['#000000']
            },
            dropShadow: {
                enabled: false // Se desactiva la sombra por defecto que a veces pone el texto blanco
            }
        },
        tooltip: {
            y: {
                formatter: (val) => val.toFixed(2) + '%'
            }
        }
    });
    chartColores.render();
}

function renderTablaCrudos() {
    if (tablaCrudos) tablaCrudos.destroy();

    const dataTabla = datosFiltrados.datos_lote.map(l => {
        const infoInterna = datosFiltrados.interna.find(i => i.lote_id == l.lote_id) || {};
        const bueno = datosFiltrados.medidas.find(d => d.lote_id == l.lote_id && d.criterio === 'PercColor' && d.rango.toUpperCase() === 'PRIMERO');
        const cat2 = datosFiltrados.medidas.find(d => d.lote_id == l.lote_id && d.criterio === 'PercColor' && d.rango.toUpperCase() === 'CAT2');

        const calibres = datosFiltrados.medidas.filter(d => d.lote_id == l.lote_id && d.criterio === 'AvgDiameter' && parseFloat(d.porcentaje) > 0).map(d => `${d.rango}: ${parseFloat(d.porcentaje).toFixed(1)}%`).join(' | ');
        const defectos = datosFiltrados.medidas.filter(d => d.lote_id == l.lote_id && d.criterio === 'Defects' && parseFloat(d.porcentaje) > 0).map(d => `${d.rango}: ${parseFloat(d.porcentaje).toFixed(1)}%`).join(' | ');

        return {
            lot_id: l.lot_id_api, nombre: l.nombre_lote, programa: l.programa,
            fecha: l.fecha_inicio ? l.fecha_inicio.split(' ')[0] : '',
            cajas: l.paquetes, brix: infoInterna.brix_promedio ?? '—',
            bueno: bueno ? parseFloat(bueno.porcentaje).toFixed(2) : '0.00',
            cat2: cat2 ? parseFloat(cat2.porcentaje).toFixed(2) : '0.00',
            calibres: calibres || '—', defectos: defectos || '—'
        };
    });

    tablaCrudos = new Tabulator("#tabla-datos-crudos", {
        data: dataTabla, layout: "fitColumns", pagination: "local", paginationSize: 10,
        columns: [
            { title: "ID API", field: "lot_id", width: 80 }, { title: "Lote", field: "nombre", width: 120 },
            { title: "Programa", field: "programa", width: 120 }, { title: "Fecha", field: "fecha", width: 100 },
            { title: "Cajas", field: "cajas", width: 70 }, { title: "Brix", field: "brix", width: 70 },
            { title: "% BUENO", field: "bueno", width: 90, formatter: (cell) => cell.getValue() + '%' },
            { title: "% CAT2", field: "cat2", width: 90, formatter: (cell) => cell.getValue() + '%' },
            { title: "Calibres", field: "calibres", width: 200 }, { title: "Defectos", field: "defectos", width: 200 }
        ]
    });
}

// =====================================================================
// FUNCIÓN DE DEBUG
// =====================================================================
function mostrarDebug(chartName) {
    let datosParaMostrar = {};
    let titulo = '';

    if (chartName === 'calibres') {
        titulo = 'Datos: Calibres (AvgDiameter)';
        datosParaMostrar = datosFiltrados.medidas.filter(d => d.criterio === 'AvgDiameter');
    } else if (chartName === 'interna') {
        titulo = 'Datos: Calidad Interna';
        datosParaMostrar = datosFiltrados.interna;
    } else if (chartName === 'defectos') {
        titulo = 'Datos: Defectos (CAT2)';
        datosParaMostrar = datosFiltrados.medidas.filter(d => d.criterio === 'Defects');
    } else if (chartName === 'colores') {
        titulo = 'Datos: Calidad Visual (Weight)';
        datosParaMostrar = datosFiltrados.medidas.filter(d => d.criterio === 'Weight');
    }

    document.getElementById('debug-titulo').textContent = titulo;
    document.getElementById('debug-contenido').textContent = JSON.stringify(datosParaMostrar, null, 2);
    abrirModal('modal-debug');
}

// =====================================================================
// EXPORTAR DASHBOARD COMO HTML AUTOCONTENIDO
// =====================================================================
async function exportarDashboardHTML() {
    // 1) Guardamos el estado actual de los filtros para que el archivo
    //    exportado abra con la misma vista que estaba viendo el usuario.
    const filtrosActuales = {
        fIni: document.getElementById('f-inicio').value,
        fFin: document.getElementById('f-fin').value,
        prog: document.getElementById('f-programa').value
    };

    // 2) Empaquetamos los datos GLOBALES (no los filtrados) para que los
    //    filtros del HTML exportado sigan siendo funcionales.
    const dataStr = JSON.stringify(datosGlobales).replace(/<\//g, '<\\/');

    try {
        // 1) Traemos el contenido de las librerías desde el propio servidor
        //    (ya están en /assets/libs por lo que el fetch es local y rápido).
        const [apexJs, tabulatorJs, apexCss, tabulatorCss] = await Promise.all([
            fetch(BASE_URL + '/assets/libs/apexcharts/apexcharts.min.js').then(r => {
                if (!r.ok) throw new Error('No se pudo leer apexcharts.min.js');
                return r.text();
            }),
            fetch(BASE_URL + '/assets/libs/tabulator/tabulator.min.js').then(r => {
                if (!r.ok) throw new Error('No se pudo leer tabulator.min.js');
                return r.text();
            }),
            fetch(BASE_URL + '/assets/libs/apexcharts/apexcharts.min.css').then(r => r.ok ? r.text() : ''),
            fetch(BASE_URL + '/assets/libs/tabulator/tabulator.min.css').then(r => r.ok ? r.text() : '')
        ]);

        // 2) Escapamos "</script" para que no cierre prematuramente el <script> que lo envuelve
        const safeApex = apexJs.replace(/<\/script/gi, '<\\/script');
        const safeTabulator = tabulatorJs.replace(/<\/script/gi, '<\\/script');

        // 3) Construimos el HTML con TODO embebido (sin CDN)
        const html = [
            '<!DOCTYPE html>',
            '<html lang="es">',
            '<head>',
            '<meta charset="UTF-8">',
            '<meta name="viewport" content="width=device-width, initial-scale=1.0">',
            '<title>Panel de Calidad - SCPC</title>',
            '<style>',
            apexCss,
            tabulatorCss,
            getEstilosDashboardExport(),
            '</style>',
            '</head>',
            '<body>',
            getEstructuraDashboardExport(),
            '<script id="datos-embebidos" type="application/json">', dataStr, '<\/script>',
            '<script>var filtrosIniciales = ' + JSON.stringify(filtrosActuales) + ';<\/script>',
            '<script>', safeApex, '<\/script>',
            '<script>', safeTabulator, '<\/script>',
            '<script>', getLogicaDashboardExport(), '<\/script>',
            '</body>',
            '</html>'
        ].join('\n');

        // 4) Descargamos
        const blob = new Blob([html], { type: 'text/html;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'Dashboard_Calidad_' + new Date().toISOString().split('T')[0] + '.html';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);

        notificar('Dashboard HTML exportado correctamente (autocontenido)', 'exito');
    } catch (err) {
        console.error(err);
        notificar('Error al exportar: ' + err.message, 'error');
    }
}

// Estilos embebidos (equivalente a panel.css, autocontenido)
function getEstilosDashboardExport() {
    return [
        ':root{--verde:#2E7D32;--naranja:#ef6c00;--ink:#1f2937;--ink-soft:#6b7280;',
        '--blanco:#fff;--gris-borde:#dfe6ee;--sombra-suave:0 1px 3px rgba(0,0,0,.06);}',
        '*{box-sizing:border-box;}',
        'body{margin:0;background:#f5f7fa;font-family:Segoe UI,Arial,sans-serif;color:var(--ink);}',
        '.contenedor{max-width:1500px;margin:auto;padding:20px;}',
        'h1{margin:0 0 4px 0;color:var(--verde);font-size:1.5rem;}',
        'h2{margin:0 0 4px 0;font-size:1.4rem;}',
        '.subtitle{color:var(--ink-soft);font-size:.9rem;margin:0 0 20px 0;}',
        '.tarjetas{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:15px;margin-bottom:20px;}',
        '.tarjeta{background:var(--blanco);border-radius:8px;padding:15px;box-shadow:var(--sombra-suave);border-left:4px solid var(--verde);}',
        '.tarjeta.azul{border-left-color:#2563eb;}',
        '.tarjeta.naranja{border-left-color:var(--naranja);}',
        '.tarjeta .etiqueta{font-size:.8rem;color:var(--ink-soft);text-transform:uppercase;margin-bottom:5px;}',
        '.tarjeta .valor{font-size:1.8rem;font-weight:bold;}',
        '.filtros-panel{display:flex;flex-wrap:wrap;gap:15px;align-items:end;background:var(--blanco);padding:15px;border-radius:8px;margin-bottom:20px;box-shadow:var(--sombra-suave);}',
        '.filtros-panel label{display:block;font-size:12px;font-weight:600;margin-bottom:4px;}',
        '.filtros-panel input,.filtros-panel select{padding:8px;border-radius:6px;border:1px solid var(--gris-borde);}',
        '.filtros-panel button{padding:8px 15px;border-radius:6px;border:1px solid var(--gris-borde);background:white;cursor:pointer;font-size:.9rem;}',
        '.filtros-panel button.primario{background:var(--verde);color:white;border-color:var(--verde);}',
        '.grid-dashboard{display:grid;grid-template-columns:repeat(auto-fit,minmax(400px,1fr));gap:20px;margin-bottom:20px;}',
        '.panel-grafico{background:var(--blanco);border-radius:8px;padding:15px;box-shadow:var(--sombra-suave);min-width:0;overflow:hidden;}',
        '.panel-grafico h3{font-size:1rem;color:var(--ink-soft);margin:0 0 15px 0;text-transform:uppercase;font-weight:600;}',
        'div[id^="chart-"]{min-height:320px;width:100%;}',
        '.panel{background:var(--blanco);border-radius:8px;padding:15px;box-shadow:var(--sombra-suave);margin-top:20px;}',
        '.panel h3{font-size:1rem;color:var(--ink-soft);margin:0 0 15px 0;text-transform:uppercase;font-weight:600;}',
        'table.tabla{width:100%;border-collapse:collapse;}',
        'table.tabla th{background:#f3f4f6;padding:10px;text-align:left;font-size:13px;}',
        'table.tabla td{padding:10px;border-bottom:1px solid #e5e7eb;font-size:13px;}',
        '@media(max-width:900px){.grid-dashboard{grid-template-columns:1fr;}}'
    ].join('\n');
}

// Estructura HTML del dashboard (misma que panel.php)
function getEstructuraDashboardExport() {
    return [
        '<div class="contenedor">',
        '  <h1>FRUSAN OLMOS</h1>',
        '  <h2>Panel de Análisis - Calidad</h2>',
        '  <p class="subtitle">Reporte exportado el ' + new Date().toLocaleString('es-PE') + '</p>',
        '',
        '  <div class="filtros-panel">',
        '    <div><label>Desde</label><input type="date" id="f-inicio"></div>',
        '    <div><label>Hasta</label><input type="date" id="f-fin"></div>',
        '    <div><label>Programa</label><select id="f-programa"><option value="">Todos</option></select></div>',
        '    <div><button class="primario" id="btn-aplicar">Aplicar filtros</button></div>',
        '  </div>',
        '',
        '  <div class="tarjetas">',
        '    <div class="tarjeta"><div class="etiqueta">Lotes Analizados</div><div class="valor" id="kpi-lotes">—</div></div>',
        '    <div class="tarjeta azul"><div class="etiqueta">Muestras (Cajas)</div><div class="valor" id="kpi-muestras">—</div></div>',
        '    <div class="tarjeta"><div class="etiqueta">% Exportable (BUENO)</div><div class="valor" id="kpi-bueno" style="color:var(--verde);">—</div></div>',
        '    <div class="tarjeta naranja"><div class="etiqueta">% Desecho (CAT2)</div><div class="valor" id="kpi-desecho" style="color:var(--naranja);">—</div></div>',
        '  </div>',
        '',
        '  <div class="grid-dashboard">',
        '    <div class="panel-grafico"><h3>Análisis de Calibres</h3><div id="chart-calibres"></div></div>',
        '    <div class="panel-grafico"><h3>Calidad Interna Promedio</h3>',
        '      <table class="tabla" id="tabla-interna"><thead><tr><th>Métrica</th><th>Valor</th></tr></thead>',
        '      <tbody><tr><td colspan="2">Cargando...</td></tr></tbody></table>',
        '    </div>',
        '  </div>',
        '',
        '  <div class="grid-dashboard">',
        '    <div class="panel-grafico"><h3>Desglose de Defectos (CAT2)</h3><div id="chart-defectos"></div></div>',
        '    <div class="panel-grafico"><h3>Calidad Visual (Color)</h3><div id="chart-colores"></div></div>',
        '  </div>',
        '',
        '  <div class="panel"><h3>Datos Crudos (Lotes Filtrados)</h3><div id="tabla-datos-crudos"></div></div>',
        '</div>'
    ].join('\n');
}

// Lógica JS adaptada (versión vanilla que corre dentro del archivo exportado)
function getLogicaDashboardExport() {
    return [
        '(function(){',
        '  "use strict";',
        '  var datosGlobales = JSON.parse(document.getElementById("datos-embebidos").textContent);',
        '  var datosFiltrados = { datos_lote: [], interna: [], clases: [], medidas: [] };',
        '  var tablaCrudos = null, chartCalibres, chartDefectos, chartColores;',
        '',
        '  function agruparPorcentajes(criterio) {',
        '    var agrupado = {};',
        '    datosFiltrados.medidas.forEach(function(d){',
        '      if (d.criterio && d.criterio.toUpperCase() === criterio.toUpperCase()) {',
        '        var v = parseFloat(d.porcentaje);',
        '        if (!isNaN(v)) { if (!agrupado[d.rango]) agrupado[d.rango] = []; agrupado[d.rango].push(v); }',
        '      }',
        '    });',
        '    return Object.keys(agrupado).map(function(rango){',
        '      var vals = agrupado[rango];',
        '      var suma = vals.reduce(function(a,b){return a+b;},0);',
        '      return { rango: rango, valor: suma / vals.length };',
        '    });',
        '  }',
        '',
        '  function aplicarFiltros() {',
        '    var fIni = document.getElementById("f-inicio").value;',
        '    var fFin = document.getElementById("f-fin").value;',
        '    var prog = document.getElementById("f-programa").value;',
        '    function checkFecha(fecha) {',
        '      if (!fecha) return false;',
        '      var d = fecha.split(" ")[0];',
        '      return (!fIni || d >= fIni) && (!fFin || d <= fFin);',
        '    }',
        '    datosFiltrados.datos_lote = datosGlobales.datos_lote.filter(function(l){',
        '      return checkFecha(l.fecha_inicio) && (!prog || l.programa === prog);',
        '    });',
        '    var ids = datosFiltrados.datos_lote.map(function(l){return l.lote_id;});',
        '    datosFiltrados.interna = datosGlobales.interna.filter(function(d){return ids.indexOf(d.lote_id) !== -1;});',
        '    datosFiltrados.clases  = datosGlobales.clases.filter(function(d){return ids.indexOf(d.lote_id) !== -1;});',
        '    datosFiltrados.medidas = datosGlobales.medidas.filter(function(d){return ids.indexOf(d.lote_id) !== -1;});',
        '    actualizarKPIs(); renderTablaInterna(); renderCalibres(); renderDefectos(); renderColores(); renderTablaCrudos();',
        '  }',
        '',
        '  function actualizarKPIs() {',
        '    var totalLotes = datosFiltrados.datos_lote.length;',
        '    var totalMuestras = datosFiltrados.datos_lote.reduce(function(s,l){return s + parseInt(l.paquetes||0);},0);',
        '    var buenoSum = 0, cat2Sum = 0;',
        '    datosFiltrados.medidas.forEach(function(d){',
        '      if (d.criterio === "PercColor") {',
        '        if (d.rango.toUpperCase() === "PRIMERO") buenoSum += parseFloat(d.porcentaje);',
        '        if (d.rango.toUpperCase() === "CAT2")    cat2Sum  += parseFloat(d.porcentaje);',
        '      }',
        '    });',
        '    var total = buenoSum + cat2Sum;',
        '    var b = total>0 ? ((buenoSum/total)*100).toFixed(2) : "0.00";',
        '    var c = total>0 ? ((cat2Sum/total)*100).toFixed(2)  : "0.00";',
        '    document.getElementById("kpi-lotes").textContent = totalLotes;',
        '    document.getElementById("kpi-muestras").textContent = totalMuestras;',
        '    document.getElementById("kpi-bueno").textContent = b + "%";',
        '    document.getElementById("kpi-desecho").textContent = c + "%";',
        '  }',
        '',
        '  function renderTablaInterna() {',
        '    var tbody = document.querySelector("#tabla-interna tbody");',
        '    if (datosFiltrados.interna.length === 0) { tbody.innerHTML = "<tr><td colspan=\\"2\\">Sin datos</td></tr>"; return; }',
        '    var brix=0,brixBajo=0,acidez=0,acidezAlta=0;',
        '    datosFiltrados.interna.forEach(function(l){',
        '      brix += parseFloat(l.brix_promedio||0);',
        '      brixBajo += parseFloat(l.brix_bajo_umbral||0);',
        '      acidez += parseFloat(l.acidez_promedio||0);',
        '      acidezAlta += parseFloat(l.acidez_alta_umbral||0);',
        '    });',
        '    var n = datosFiltrados.interna.length;',
        '    tbody.innerHTML =',
        '      "<tr><td><strong>Brix Medio</strong></td><td>" + (brix/n).toFixed(2) + "</td></tr>" +',
        '      "<tr><td><strong>Brix Bajo Umbral (%)</strong></td><td>" + (brixBajo/n).toFixed(1) + "%</td></tr>" +',
        '      "<tr><td><strong>Acidez Media</strong></td><td>" + (acidez/n).toFixed(2) + "</td></tr>" +',
        '      "<tr><td><strong>Acidez Alta Umbral (%)</strong></td><td>" + (acidezAlta/n).toFixed(1) + "%</td></tr>";',
        '  }',
        '',
        '  function renderCalibres() {',
        '    var datos = agruparPorcentajes("AvgDiameter");',
        '    var orden = {"3J":1,"2J":2,"XL":3,"J":4,"L":5,"M":6,"S":7,"XS":8,"PRECALIBRE":9};',
        '    datos.sort(function(a,b){return (orden[a.rango.toUpperCase()]||99) - (orden[b.rango.toUpperCase()]||99);});',
        '    var cont = document.querySelector("#chart-calibres");',
        '    if (chartCalibres) chartCalibres.destroy();',
        '    if (datos.length === 0) { cont.innerHTML = "<p style=\\"text-align:center;padding:50px;\\">Sin datos.</p>"; return; }',
        '    cont.innerHTML = "";',
        '    chartCalibres = new ApexCharts(cont, {',
        '      series: [{ name: "%", data: datos.map(function(d){return d.valor.toFixed(2);}) }],',
        '      chart: { type: "bar", height: 300, toolbar: { show: false } },',
        '      plotOptions: { bar: { horizontal: false, distributed: true, borderRadius: 4 } },',
        '      xaxis: { categories: datos.map(function(d){return d.rango.toUpperCase();}) },',
        '      colors: ["#008FFB","#00E396","#FEB019","#775DD0","#546E7A"],',
        '      dataLabels: { enabled: true, formatter: function(v){return v + "%";} },',
        '      legend: { show: false }',
        '    });',
        '    chartCalibres.render();',
        '  }',
        '',
        '  function renderDefectos() {',
        '    var datos = agruparPorcentajes("Defects").filter(function(d){return d.valor>0;}).sort(function(a,b){return b.valor-a.valor;});',
        '    var cont = document.querySelector("#chart-defectos");',
        '    if (chartDefectos) chartDefectos.destroy();',
        '    if (datos.length === 0) { cont.innerHTML = "<p style=\\"text-align:center;padding:50px;\\">Sin defectos.</p>"; return; }',
        '    cont.innerHTML = "";',
        '    chartDefectos = new ApexCharts(cont, {',
        '      series: [{ name: "%", data: datos.map(function(d){return d.valor.toFixed(2);}) }],',
        '      chart: { type: "bar", height: 300, toolbar: { show: false } },',
        '      plotOptions: { bar: { horizontal: true, distributed: true, borderRadius: 4 } },',
        '      xaxis: { categories: datos.map(function(d){return d.rango.toUpperCase();}) },',
        '      colors: ["#FF4560","#FEB019","#FF9800","#E91E63"],',
        '      dataLabels: { enabled: true, formatter: function(v){return v + "%";} },',
        '      legend: { show: false }',
        '    });',
        '    chartDefectos.render();',
        '  }',
        '',
        '  function renderColores() {',
        '    var cont = document.querySelector("#chart-colores");',
        '    if (chartColores) { chartColores.destroy(); chartColores = null; }',
        '    var datos = agruparPorcentajes("Weight").filter(function(d){return d.valor>0;});',
        '    if (datos.length === 0) { cont.innerHTML = "<p style=\\"text-align:center;padding:20px;\\">Sin datos de color.</p>"; return; }',
        '    cont.innerHTML = "";',
        '    chartColores = new ApexCharts(cont, {',
        '      series: datos.map(function(d){return Number(d.valor.toFixed(2));}),',
        '      labels: datos.map(function(d){return String(d.rango).toUpperCase();}),',
        '      chart: { type: "pie", height: 300, fontFamily: "inherit" },',
        '      colors: ["#9E9E9E","#4A148C","#4CAF50","#E91E63","#FF9800","#2196F3"],',
        '      legend: { position: "bottom" },',
        '      dataLabels: { enabled: true, formatter: function(v){return v.toFixed(2)+"%";}, style: { colors: ["#000000"] }, dropShadow: { enabled: false } },',
        '      tooltip: { y: { formatter: function(v){return v.toFixed(2)+"%";} } }',
        '    });',
        '    chartColores.render();',
        '  }',
        '',
        '  function renderTablaCrudos() {',
        '    if (tablaCrudos) tablaCrudos.destroy();',
        '    var data = datosFiltrados.datos_lote.map(function(l){',
        '      var info = datosFiltrados.interna.find(function(i){return i.lote_id == l.lote_id;}) || {};',
        '      var bueno = datosFiltrados.medidas.find(function(d){return d.lote_id == l.lote_id && d.criterio === "PercColor" && d.rango.toUpperCase() === "PRIMERO";});',
        '      var cat2  = datosFiltrados.medidas.find(function(d){return d.lote_id == l.lote_id && d.criterio === "PercColor" && d.rango.toUpperCase() === "CAT2";});',
        '      var calibres = datosFiltrados.medidas.filter(function(d){return d.lote_id==l.lote_id && d.criterio==="AvgDiameter" && parseFloat(d.porcentaje)>0;}).map(function(d){return d.rango+": "+parseFloat(d.porcentaje).toFixed(1)+"%";}).join(" | ");',
        '      var defectos = datosFiltrados.medidas.filter(function(d){return d.lote_id==l.lote_id && d.criterio==="Defects" && parseFloat(d.porcentaje)>0;}).map(function(d){return d.rango+": "+parseFloat(d.porcentaje).toFixed(1)+"%";}).join(" | ");',
        '      return { lot_id: l.lot_id_api, nombre: l.nombre_lote, programa: l.programa,',
        '        fecha: l.fecha_inicio ? l.fecha_inicio.split(" ")[0] : "", cajas: l.paquetes,',
        '        brix: info.brix_promedio != null ? info.brix_promedio : "—",',
        '        bueno: bueno ? parseFloat(bueno.porcentaje).toFixed(2) : "0.00",',
        '        cat2: cat2 ? parseFloat(cat2.porcentaje).toFixed(2) : "0.00",',
        '        calibres: calibres || "—", defectos: defectos || "—" };',
        '    });',
        '    tablaCrudos = new Tabulator("#tabla-datos-crudos", {',
        '      data: data, layout: "fitColumns", pagination: "local", paginationSize: 10,',
        '      columns: [',
        '        { title: "ID API", field: "lot_id", width: 80 },',
        '        { title: "Lote", field: "nombre", width: 120 },',
        '        { title: "Programa", field: "programa", width: 120 },',
        '        { title: "Fecha", field: "fecha", width: 100 },',
        '        { title: "Cajas", field: "cajas", width: 70 },',
        '        { title: "Brix", field: "brix", width: 70 },',
        '        { title: "% BUENO", field: "bueno", width: 90, formatter: function(c){return c.getValue()+"%";} },',
        '        { title: "% CAT2", field: "cat2", width: 90, formatter: function(c){return c.getValue()+"%";} },',
        '        { title: "Calibres", field: "calibres", width: 200 },',
        '        { title: "Defectos", field: "defectos", width: 200 }',
        '      ]',
        '    });',
        '  }',
        '',
        '  function init() {',
        '    var programas = [];',
        '    datosGlobales.datos_lote.forEach(function(l){ if(l.programa && programas.indexOf(l.programa)===-1) programas.push(l.programa); });',
        '    programas.sort();',
        '    var sel = document.getElementById("f-programa");',
        '    programas.forEach(function(p){ sel.add(new Option(p, p)); });',
        '    var fechas = datosGlobales.datos_lote.map(function(l){return l.fecha_inicio.split(" ")[0];}).sort();',
        '    if (fechas.length > 0) { document.getElementById("f-inicio").value = fechas[0]; document.getElementById("f-fin").value = fechas[fechas.length-1]; }',
        '    if (typeof filtrosIniciales !== "undefined") {',
        '      if (filtrosIniciales.fIni) document.getElementById("f-inicio").value = filtrosIniciales.fIni;',
        '      if (filtrosIniciales.fFin) document.getElementById("f-fin").value = filtrosIniciales.fFin;',
        '      if (filtrosIniciales.prog) document.getElementById("f-programa").value = filtrosIniciales.prog;',
        '    }',
        '    document.getElementById("btn-aplicar").addEventListener("click", aplicarFiltros);',
        '    aplicarFiltros();',
        '  }',
        '',
        '  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", init);',
        '  else init();',
        '})();'
    ].join('\n');
}