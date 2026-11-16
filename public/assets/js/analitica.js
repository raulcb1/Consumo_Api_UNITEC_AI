let datosGlobales = [];
let datosFiltrados = [];

let filtroLote = null;
let filtroPrograma = null;
let filtroTipo = null;

let chartLotes, chartProgramas, chartTipos, chartEvolucion;

document.addEventListener('DOMContentLoaded', async () => {
    const r = await fetch(BASE_URL + '/api/analitica.php?accion=datos_crossfilter')
        .then(res => res.json());

    if (!r.success) {
        alert('Error al cargar datos');
        return;
    }

    datosGlobales = r.data;
    datosFiltrados = [...datosGlobales];

    renderizarTodo();
    document.getElementById('btn-reset').addEventListener('click', resetFiltros);
});

function resetFiltros() {
    filtroLote = null;
    filtroPrograma = null;
    filtroTipo = null;
    datosFiltrados = [...datosGlobales];
    renderizarTodo();
}

function aplicarFiltros() {
    datosFiltrados = datosGlobales.filter(d => {
        let ok = true;
        if (filtroLote && d.lote_id != filtroLote) ok = false;
        if (filtroPrograma && d.programa !== filtroPrograma) ok = false;
        if (filtroTipo && d.tipo_clase !== filtroTipo) ok = false;
        return ok;
    });
    renderizarTodo();
}

function renderizarTodo() {
    renderLotes();
    renderProgramas();
    renderTipos();
    renderEvolucion();
}

// 1. GRÁFICO DE LOTES
function renderLotes() {
    const agrupado = {};
    datosFiltrados.forEach(d => {
        if (!agrupado[d.nombre_lote]) agrupado[d.nombre_lote] = 0;
        agrupado[d.nombre_lote] += parseFloat(d.kg);
    });

    const categorias = Object.keys(agrupado);
    const series = Object.values(agrupado);

    if (chartLotes) chartLotes.destroy();

    chartLotes = new ApexCharts(document.querySelector("#chart-lotes"), {
        series: [{ name: 'Kg', data: series }],
        chart: {
            type: 'bar', height: 300,
            events: {
                dataPointSelection: function (event, chartContext, config) {
                    const index = config.dataPointIndex;
                    const seleccionado = categorias[index];
                    // Si ya estaba seleccionado, lo quitamos (toggle)
                    filtroLote = (filtroLote === seleccionado) ? null : seleccionado;
                    // Necesitamos el ID, busquemos en los datos
                    if (filtroLote) {
                        const found = datosGlobales.find(d => d.nombre_lote === filtroLote);
                        filtroLote = found ? found.lote_id : null;
                    }
                    aplicarFiltros();
                }
            }
        },
        plotOptions: { bar: { distributed: true, columnWidth: '60%' } },
        xaxis: { categories: categorias, labels: { rotate: -45 } },
        colors: ['#2e7d32', '#388e3c', '#43a047', '#4caf50', '#66bb6a']
    });
    chartLotes.render();
}

// 2. GRÁFICO DE PROGRAMAS
function renderProgramas() {
    const agrupado = {};
    datosFiltrados.forEach(d => {
        if (!agrupado[d.programa]) agrupado[d.programa] = 0;
        agrupado[d.programa] += parseFloat(d.kg);
    });

    if (chartProgramas) chartProgramas.destroy();

    chartProgramas = new ApexCharts(document.querySelector("#chart-programas"), {
        series: Object.values(agrupado),
        labels: Object.keys(agrupado),
        chart: {
            type: 'donut', height: 300,
            events: {
                dataPointSelection: function (event, chartContext, config) {
                    const index = config.dataPointIndex;
                    const seleccionado = Object.keys(agrupado)[index];
                    filtroPrograma = (filtroPrograma === seleccionado) ? null : seleccionado;
                    aplicarFiltros();
                }
            }
        },
        legend: { position: 'bottom' }
    });
    chartProgramas.render();
}

// 3. GRÁFICO DE TIPOS (Calibre, Defecto, etc)
function renderTipos() {
    const agrupado = {};
    datosFiltrados.forEach(d => {
        if (!agrupado[d.tipo_clase]) agrupado[d.tipo_clase] = 0;
        agrupado[d.tipo_clase] += parseFloat(d.kg);
    });

    if (chartTipos) chartTipos.destroy();

    chartTipos = new ApexCharts(document.querySelector("#chart-tipos"), {
        series: Object.values(agrupado),
        labels: Object.keys(agrupado),
        chart: {
            type: 'pie', height: 300,
            events: {
                dataPointSelection: function (event, chartContext, config) {
                    const index = config.dataPointIndex;
                    const seleccionado = Object.keys(agrupado)[index];
                    filtroTipo = (filtroTipo === seleccionado) ? null : seleccionado;
                    aplicarFiltros();
                }
            }
        },
        legend: { position: 'bottom' }
    });
    chartTipos.render();
}

// 4. GRÁFICO DE EVOLUCIÓN TEMPORAL (Clases)
// --- 4. GRÁFICO DE EVOLUCIÓN (Sin Brush, solo líneas) ---
function renderEvolucion() {
    const fechasUnicas = [...new Set(datosFiltrados.map(d => d.consultado_en))].sort();
    const clases = [...new Set(datosFiltrados.map(d => d.nombre_clase))];

    const timestampMap = {};
    fechasUnicas.forEach(f => {
        timestampMap[f] = new Date(f.replace(' ', 'T')).getTime();
    });

    const series = clases.map(clase => {
        const data = fechasUnicas.map(fecha => {
            const filas = datosFiltrados.filter(d => d.nombre_clase === clase && d.consultado_en === fecha);
            const kg = filas.reduce((sum, f) => sum + parseFloat(f.kg), 0);
            return [timestampMap[fecha], kg];
        });
        return { name: clase, data: data };
    });

    if (chartEvolucion) chartEvolucion.destroy();

    chartEvolucion = new ApexCharts(document.querySelector("#chart-evolucion"), {
        series: series,
        chart: {
            id: 'chart-evolucion',
            type: 'line',
            height: 320,
            zoom: { enabled: false },
            toolbar: { show: false }
        },
        stroke: { curve: 'smooth', width: 2 },
        xaxis: { type: 'datetime' },
        legend: { position: 'bottom' },
        tooltip: { shared: true, intersect: false },
        colors: ['#2e7d32', '#388e3c', '#43a047', '#4caf50', '#66bb6a', '#81c784', '#a5d6a7', '#c8e6c9']
    });
    chartEvolucion.render();
}