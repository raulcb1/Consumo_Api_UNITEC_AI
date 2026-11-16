/* =====================================================================
   reportes_ver.js - Visor de reporte con Tabulator y ApexCharts
   ===================================================================== */

let definicion = null;
let filtrosActivos = {};
let tablaTabulator = null;
let graficoSales = null;
let graficoEvolucion = null;

document.addEventListener('DOMContentLoaded', async () => {
    await cargarDefinicion();
    document.getElementById('btn-ejecutar')?.addEventListener('click', ejecutar);
    document.getElementById('btn-limpiar-filtros')?.addEventListener('click', limpiarFiltros);
});

async function cargarDefinicion() {
    const r = await Api.peticion(`/api/reportes.php?accion=definicion&codigo=${REPORTE_CODIGO}`);
    if (!r.ok || !r.data?.success) {
        notificar(r.data?.message || 'Error al cargar reporte', 'error');
        return;
    }
    definicion = r.data.data;
    document.getElementById('titulo-reporte').textContent = definicion.nombre;
    document.getElementById('descripcion-reporte').textContent = definicion.descripcion || '';

    if (definicion.config_usuario?.filtros_guardados) {
        filtrosActivos = definicion.config_usuario.filtros_guardados;
    }
    renderFiltros();
}

function renderFiltros() {
    const cont = document.getElementById('filtros-contenedor');
    const filtros = normalizarLista(definicion.filtros);

    if (!filtros.length) {
        cont.innerHTML = '<p style="color: var(--ink-soft);">Este reporte no tiene filtros.</p>';
        return;
    }

    cont.innerHTML = filtros.map(f => {
        const val = filtrosActivos[f.codigo] ?? '';
        let input = '';

        switch (f.tipo) {
            case 'select':
                input = `<select id="filtro-${f.codigo}">
                    <option value="">— Todos —</option>
                    ${(f.opciones || []).map(o => `
                        <option value="${escaparHtml(o.valor)}" ${String(val) === String(o.valor) ? 'selected' : ''}>
                            ${escaparHtml(o.etiqueta)}
                        </option>
                    `).join('')}
                </select>`;
                break;
            case 'rango_fecha':
                input = `<input type="date" id="filtro-${f.codigo}" value="${escaparHtml(val)}">`;
                break;
            case 'booleano':
                input = `<label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                    <input type="checkbox" id="filtro-${f.codigo}" ${val ? 'checked' : ''}>
                    <span>Activar</span>
                </label>`;
                break;
            default:
                input = `<input type="text" id="filtro-${f.codigo}" value="${escaparHtml(val)}" placeholder="...">`;
        }

        return `<div class="campo">
            <label for="filtro-${f.codigo}">${escaparHtml(f.etiqueta)}</label>
            ${input}
        </div>`;
    }).join('');
}

function recolectarFiltros() {
    const filtros = {};
    const listaFiltros = normalizarLista(definicion.filtros);

    listaFiltros.forEach(f => {
        const el = document.getElementById(`filtro-${f.codigo}`);
        if (!el) return;
        if (f.tipo === 'booleano') {
            if (el.checked) filtros[f.codigo] = 1;
        } else {
            const v = el.value.trim();
            if (v !== '') filtros[f.codigo] = v;
        }
    });
    return filtros;
}

async function ejecutar() {
    const filtros = recolectarFiltros();
    const btn = document.getElementById('btn-ejecutar');
    const original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span>Ejecutando...</span>';

    const params = new URLSearchParams({
        accion: 'ejecutar',
        codigo: REPORTE_CODIGO,
        filtros: JSON.stringify(filtros)
    });

    const r = await Api.peticion('/api/reportes.php?' + params.toString());

    btn.disabled = false;
    btn.innerHTML = original;

    if (!r.ok || !r.data?.success) {
        notificar(r.data?.message || 'Error', 'error');
        return;
    }

    const resultado = r.data.data;
    document.getElementById('meta-resultado').textContent =
        `${resultado.meta.total_filas} fila(s) · límite ${resultado.meta.limite}`;

    if (!resultado.filas.length) {
        document.getElementById('contenedor-grafico').style.display = 'none';
        document.getElementById('tabla-tabulator').innerHTML = '<p class="vacio">El reporte no devolvió resultados.</p>';
        return;
    }

    renderGrafico(resultado);
    renderTabla(resultado);
}

// =====================================================================
// APEXCHARTS - Gráfico de apoyo (Top 10 pallets por Kg procesados)
// =====================================================================
// =====================================================================
// APEXCHARTS - Gráfico dinámico basado en la definición del reporte
// =====================================================================
function renderGrafico(resultado) {
    const contenedor = document.getElementById('contenedor-grafico');
    const divGrafico = document.getElementById('grafico-reporte'); // Corregido el ID
    const defGrafico = definicion.grafico;

    if (!defGrafico || !defGrafico.categoria || !defGrafico.valor) {
        contenedor.style.display = 'none';
        return;
    }

    contenedor.style.display = 'block';

    if (graficoSales) {
        graficoSales.destroy();
    }

    let options = {};

    // --- CONFIGURACIÓN PARA SCATTER (Dispersión) ---
    if (defGrafico.tipo === 'scatter') {
        // FILTRO DE SEGURIDAD: Solo graficar filas que tengan datos válidos (>0)
        const datosValidos = resultado.filas.filter(f => {
            const x = Number(f[defGrafico.categoria]);
            const y = Number(f[defGrafico.valor]);
            return x > 0 && y > 0;
        });

        if (datosValidos.length === 0) {
            divGrafico.innerHTML = '<div class="vacio"><p>No hay datos de promedios (calibre/color) guardados para este reporte.</p><p style="font-size:0.85rem;">Recuerda que el entorno de prueba de UNITEC no envía estos valores.</p></div>';
            return;
        }

        const datosScatter = datosValidos.map(f => ({
            x: Number(f[defGrafico.categoria]),
            y: Number(f[defGrafico.valor]),
            meta: f
        }));

        options = {
            series: [{
                name: defGrafico.titulo_serie || 'Datos',
                data: datosScatter
            }],
            chart: {
                type: 'scatter',
                height: 400,
                fontFamily: 'inherit',
                zoom: { enabled: true, type: 'xy' }
            },
            xaxis: {
                title: { text: 'Calibre (mm)' },
                tickAmount: 10
            },
            yaxis: {
                title: { text: 'Color (Score)' }
            },
            tooltip: {
                custom: function ({ series, seriesIndex, dataPointIndex, w }) {
                    const data = w.globals.initialSeries[seriesIndex].data[dataPointIndex].meta;
                    return `<div class="apexcharts-tooltip-box" style="padding:10px;">
                        <strong>${data.nombre_lote}</strong><br>
                        Calibre: ${data.diametro_promedio} mm<br>
                        Color: ${data.color_promedio}<br>
                        Defectos: ${data.defectos_promedio}%
                    </div>`;
                }
            },
            title: { text: defGrafico.titulo || 'Dispersión', align: 'left' }
        };
    }
    // --- CONFIGURACIÓN PARA PIE / DONUT ---
    else if (defGrafico.tipo === 'pie' || defGrafico.tipo === 'donut') {
        const topData = [...resultado.filas].slice(0, 10);
        options = {
            series: topData.map(f => Number(f[defGrafico.valor] || 0)),
            labels: topData.map(f => f[defGrafico.categoria] || '—'),
            chart: { type: defGrafico.tipo, height: 350, fontFamily: 'inherit' },
            title: { text: defGrafico.titulo || 'Distribución', align: 'left' },
            legend: { position: 'bottom' }
        };
    }
    // --- CONFIGURACIÓN PARA BAR, LINE, AREA ---
    else {
        const topData = [...resultado.filas]
            .sort((a, b) => Number(b[defGrafico.valor] || 0) - Number(a[defGrafico.valor] || 0))
            .slice(0, 10);

        const categorias = topData.map(f => f[defGrafico.categoria] || '—');
        const valores = topData.map(f => Number(f[defGrafico.valor] || 0));

        options = {
            series: [{ name: defGrafico.titulo_serie || 'Valor', data: valores }],
            chart: {
                type: defGrafico.tipo || 'bar',
                height: 300,
                toolbar: { show: false },
                fontFamily: 'inherit'
            },
            plotOptions: { bar: { borderRadius: 4, horizontal: false, distributed: true } },
            colors: ['#2e7d32', '#388e3c', '#43a047', '#4caf50', '#66bb6a'],
            dataLabels: { enabled: false },
            xaxis: { categories: categorias, labels: { rotate: -45, rotateAlways: true } },
            title: { text: defGrafico.titulo || 'Top 10', align: 'left' }
        };
    }

    // CORREGIDO: el ID es #grafico-reporte
    graficoSales = new ApexCharts(document.querySelector("#grafico-reporte"), options);
    graficoSales.render();
}

// =====================================================================
// TABULATOR - Tabla interactiva
// =====================================================================
function renderTabla(resultado) {

    // 1. Destruir la tabla anterior y limpiar el contenedor (AL INICIO)
    const contenedor = document.getElementById('tabla-tabulator');
    contenedor.innerHTML = '';
    if (tablaTabulator) {
        tablaTabulator.destroy();
    }

    // 2. Definir las columnas
    const columnasTabulator = resultado.columnas.map(col => {
        // Columna especial de Acciones (Botón Evolución)
        if (col.codigo === 'accion') {
            return {
                title: col.etiqueta,
                field: col.codigo,
                hozAlign: "center",
                width: col.ancho || 150,
                headerSort: false, // No ordenar esta columna
                formatter: function (cell) {
                    const data = cell.getRow().getData();
                    // Verificamos que existan los datos antes de crear el botón
                    if (data.lote_id && data.lote) {
                        const loteSeguro = escaparHtml(data.lote).replace(/'/g, "\\'"); // Evitar problemas con comillas
                        return `<button class="boton boton-info boton-sm" onclick="verEvolucion(${data.lote_id}, '${loteSeguro}')">📈 Ver Evolución</button>`;
                    }
                    return '';
                }
            };
        }

        // Columnas normales
        let colDef = {
            title: col.etiqueta,
            field: col.codigo,
            width: col.ancho || 150,
            headerFilter: true,
            sorter: (col.tipo === 'entero' || col.tipo === 'decimal') ? 'number' : 'string',
            formatter: function (cell) {
                const val = cell.getValue();
                if (val === null || val === undefined || val === '') return '—';

                if (col.tipo === 'fecha') return formatearFecha(val, col.con_hora);
                if (col.tipo === 'decimal') return Number(val).toFixed(2);
                if (col.tipo === 'entero') return Number(val).toLocaleString('es-PE');
                return escaparHtml(val);
            }
        };
        return colDef;
    });

    // 3. Configurar opciones dinámicas (Agrupación jerárquica)
    let configAgrupacion = {};
    if (REPORTE_CODIGO === 'explorador_jerarquico') {
        configAgrupacion = {
            groupBy: ["variedad"], // Agrupa por la columna 'variedad'
            groupHeader: function (value, count, data, group) {
                // Sumar los kg del grupo para mostrarlos en la cabecera
                let totalKg = data.reduce((sum, row) => sum + Number(row.kg_procesados || 0), 0);
                return `<strong>Variedad: ${escaparHtml(value)}</strong> 
                        <span style="color:var(--ink-soft); font-size:0.85rem; margin-left:15px;">
                            (${count} lote(s) - Total: ${totalKg.toFixed(2)} Kg)
                        </span>`;
            }
        };
    }

    // 4. Inicializar Tabulator
    tablaTabulator = new Tabulator("#tabla-tabulator", {
        data: resultado.filas,
        columns: columnasTabulator,
        layout: "fitDataFill",
        pagination: "local",
        paginationSize: 25,
        paginationSizeSelector: [10, 25, 50, 100, true],
        movableColumns: true,
        locale: "es",
        langs: {
            "es": {
                "page_size": "Filas por página",
                "pagination": {
                    "first": "Primera",
                    "last": "Última",
                    "prev": "Anterior",
                    "next": "Siguiente"
                }
            }
        },
        ...configAgrupacion // Inyecta el groupBy si aplica
    });

    // 5. Inyectar botones de exportación en el header (Limpiamos los anteriores)
    const header = document.querySelector('.panel-encabezado');
    const oldBtns = header.querySelector('.acciones-exportar');
    if (oldBtns) oldBtns.remove();

    if (PUEDE_EXPORTAR) {
        const btnContainer = document.createElement('div');
        btnContainer.className = 'acciones-exportar';
        btnContainer.style.display = 'flex';
        btnContainer.style.gap = '8px';

        const btnCsv = document.createElement('button');
        btnCsv.className = 'boton boton-secundario boton-sm';
        btnCsv.innerHTML = 'Descargar CSV';
        btnCsv.onclick = () => tablaTabulator.download("csv", `reporte_${REPORTE_CODIGO}.csv`);

        const btnXlsx = document.createElement('button');
        btnXlsx.className = 'boton boton-primario boton-sm';
        btnXlsx.innerHTML = 'Descargar Excel';
        btnXlsx.onclick = () => tablaTabulator.download("xlsx", `reporte_${REPORTE_CODIGO}.xlsx`, { sheetName: "Reporte" });

        btnContainer.appendChild(btnCsv);
        btnContainer.appendChild(btnXlsx);
        header.appendChild(btnContainer);
    }
}

function normalizarLista(valor) {
    if (Array.isArray(valor)) return valor;
    if (valor && typeof valor === 'object') {
        return Object.entries(valor).map(([codigo, data]) => ({
            codigo,
            ...(typeof data === 'object' ? data : {})
        }));
    }
    return [];
}

function limpiarFiltros() {
    filtrosActivos = {};
    normalizarLista(definicion.filtros).forEach(f => {
        const el = document.getElementById(`filtro-${f.codigo}`);
        if (!el) return;
        if (f.tipo === 'booleano') el.checked = false;
        else el.value = '';
    });
}

async function verEvolucion(loteId, nombreLote) {
    document.getElementById('titulo-evolucion').textContent = 'Evolución: ' + nombreLote;
    abrirModal('modal-evolucion');

    const contenedor = document.getElementById('grafico-evolucion');
    contenedor.innerHTML = '<p style="text-align:center; padding:50px;">Cargando datos históricos...</p>';

    const r = await Api.peticion(`/api/reportes.php?accion=evolucion_lote&id=${loteId}`);

    if (!r.ok || !r.data?.success) {
        contenedor.innerHTML = '<p style="text-align:center; color:red;">Error al cargar datos.</p>';
        return;
    }

    const datos = r.data.data;

    if (datos.fechas.length === 0) {
        contenedor.innerHTML = '<p style="text-align:center;">No hay snapshots históricos para este lote.</p>';
        return;
    }

    contenedor.innerHTML = ''; // Limpiar

    if (graficoEvolucion) {
        graficoEvolucion.destroy();
    }

    // Opciones de ApexCharts para el gráfico de evolución temporal
    const options = {
        series: datos.series,
        chart: {
            type: 'line',
            height: 400,
            fontFamily: 'inherit',
            toolbar: { show: true }
        },
        stroke: {
            curve: 'smooth',
            width: 3
        },
        xaxis: {
            categories: datos.fechas.map(f => new Date(f).toLocaleString('es-PE')),
            labels: {
                rotate: -45,
                style: { fontSize: '11px' }
            }
        },
        yaxis: {
            title: { text: 'Peso Acumulado (Kg)' }
        },
        title: {
            text: 'Crecimiento de Clases en el Tiempo',
            align: 'left'
        },
        legend: {
            position: 'bottom'
        },
        tooltip: {
            shared: true,
            intersect: false
        }
    };

    graficoEvolucion = new ApexCharts(document.querySelector("#grafico-evolucion"), options);
    graficoEvolucion.render();
}