/* =====================================================================
   diccionario.js - Lógica del diccionario de clases
   ===================================================================== */

let faltantesActuales = [];

document.addEventListener('DOMContentLoaded', () => {
    cargarFaltantes();
    cargarDiccionario();

    // Pestañas
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('activo'));
            document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('activo'));
            tab.classList.add('activo');
            document.getElementById('panel-' + tab.dataset.tab).classList.add('activo');
        });
    });

    // Refrescos
    document.getElementById('btn-refrescar-faltantes')?.addEventListener('click', cargarFaltantes);
    document.getElementById('btn-refrescar-diccionario')?.addEventListener('click', cargarDiccionario);
    document.getElementById('chk-inactivas')?.addEventListener('change', cargarDiccionario);

    // Nueva clase
    document.getElementById('btn-nueva-clase')?.addEventListener('click', abrirModalCrear);

    // Guardar
    document.getElementById('btn-guardar-clase')?.addEventListener('click', guardarClase);

    // Seleccionar todo en faltantes
    document.getElementById('check-todos-faltantes')?.addEventListener('change', (e) => {
        document.querySelectorAll('.check-faltante').forEach(c => c.checked = e.target.checked);
        actualizarBotonBulk();
    });
});

// =====================================================================
// Cargar clases por definir (faltantes)
// =====================================================================
async function cargarFaltantes() {
    const tbody = document.getElementById('tabla-faltantes');
    tbody.innerHTML = '<tr><td colspan="9" class="vacio">Cargando...</td></tr>';

    const r = await Api.diccionario.faltantes();
    if (!r.ok || !r.data?.success) {
        tbody.innerHTML = `<tr><td colspan="9" class="vacio">${r.data?.message || 'Error'}</td></tr>`;
        return;
    }

    faltantesActuales = r.data.data || [];
    document.getElementById('tab-contador-faltantes').textContent = faltantesActuales.length;
    document.getElementById('tab-contador-faltantes').className = 'contador' + (faltantesActuales.length > 0 ? ' rojo' : '');

    // Badge del sidebar
    const badge = document.getElementById('badge-faltantes');
    if (badge) {
        if (faltantesActuales.length > 0) {
            badge.textContent = faltantesActuales.length;
            badge.style.display = 'inline-block';
        } else {
            badge.style.display = 'none';
        }
    }

    if (!faltantesActuales.length) {
        document.getElementById('aviso-faltantes').innerHTML =
            '<div class="alerta alerta-exito">✔ Todas las clases detectadas en los datos ya tienen definición en el diccionario.</div>';
        tbody.innerHTML = '<tr><td colspan="9" class="vacio">No hay clases pendientes de definir.</td></tr>';
        return;
    }

    document.getElementById('aviso-faltantes').innerHTML = `
        <div class="alerta alerta-aviso">
            ⚠ Hay <strong>${faltantesActuales.length}</strong> clase(s) detectadas en los datos que aún no tienen definición.
            Etiquétalas para que aparezcan correctamente en reportes y dashboards.
        </div>
    `;

    const mostrarAcciones = PUEDE_EDITAR;
    tbody.innerHTML = faltantesActuales.map(f => `
        <tr>
            <td><input type="checkbox" class="check-faltante" data-codigo="${escaparHtml(f.codigo)}" onchange="actualizarBotonBulk()"></td>
            <td><code><strong>${escaparHtml(f.codigo)}</strong></code></td>
            <td>${parseInt(f.total_lotes) || 0}</td>
            <td>${parseInt(f.total_snapshots) || 0}</td>
            <td>${Number(f.total_frutas || 0).toLocaleString()}</td>
            <td>${Number(f.total_kg || 0).toFixed(2)}</td>
            <td>${formatearFecha(f.primera_aparicion, true)}</td>
            <td>${formatearFecha(f.ultima_aparicion, true)}</td>
            ${mostrarAcciones ? `
                <td style="text-align: right;">
                    <button class="boton boton-primario boton-sm" onclick="etiquetarFaltante('${escaparHtml(f.codigo)}')">
                        Etiquetar
                    </button>
                </td>` : ''}
        </tr>
    `).join('');

    actualizarBotonBulk();
}

// =====================================================================
// Cargar diccionario completo
// =====================================================================
async function cargarDiccionario() {
    const tbody = document.getElementById('tabla-diccionario');
    tbody.innerHTML = '<tr><td colspan="7" class="vacio">Cargando...</td></tr>';

    const incluirInactivas = document.getElementById('chk-inactivas')?.checked || false;
    const r = await Api.diccionario.listar(incluirInactivas);

    if (!r.ok || !r.data?.success) {
        tbody.innerHTML = `<tr><td colspan="7" class="vacio">${r.data?.message || 'Error'}</td></tr>`;
        return;
    }

    const clases = r.data.data || [];
    document.getElementById('tab-contador-diccionario').textContent = clases.length;

    if (!clases.length) {
        tbody.innerHTML = '<tr><td colspan="7" class="vacio">El diccionario está vacío.</td></tr>';
        return;
    }

    const mostrarAcciones = PUEDE_EDITAR;
    tbody.innerHTML = clases.map(c => {
        const inactiva = parseInt(c.activo) === 0;
        const badgeTipo = `<span class="badge-tipo tipo-${escaparHtml(c.tipo)}">${escaparHtml(c.tipo)}</span>`;
        const estado = inactiva
            ? '<span style="color: var(--ink-soft);">Inactiva</span>'
            : '<span style="color: var(--verde); font-weight: 600;">● Activa</span>';

        let acciones = '';
        if (mostrarAcciones) {
            acciones = `
                <button class="boton boton-borde boton-sm" onclick="editarClase(${c.id})" title="Editar">Editar</button>
                ${inactiva
                    ? `<button class="boton boton-info boton-sm" onclick="restaurarClase(${c.id})" title="Restaurar">Restaurar</button>`
                    : `<button class="boton boton-peligro boton-sm" onclick="eliminarClase(${c.id})" title="Desactivar">Desactivar</button>`
                }
            `;
        }

        return `
            <tr style="${inactiva ? 'opacity: 0.55;' : ''}">
                <td>${c.orden}</td>
                <td><code><strong>${escaparHtml(c.codigo)}</strong></code></td>
                <td>${escaparHtml(c.etiqueta) || '—'}</td>
                <td>${escaparHtml(c.grupo) || '—'}</td>
                <td>${badgeTipo}</td>
                <td>${estado}</td>
                ${mostrarAcciones ? `<td style="text-align:right;"><div class="acciones">${acciones}</div></td>` : ''}
            </tr>
        `;
    }).join('');
}

// =====================================================================
// Selección múltiple (bulk)
// =====================================================================
function actualizarBotonBulk() {
    const seleccionados = document.querySelectorAll('.check-faltante:checked').length;
    const btn = document.getElementById('btn-bulk-etiquetar');
    const barra = document.getElementById('barra-bulk');

    if (seleccionados === 0) {
        if (barra) barra.remove();
        return;
    }

    if (!barra) {
        const nuevaBarra = document.createElement('div');
        nuevaBarra.id = 'barra-bulk';
        nuevaBarra.className = 'alerta alerta-info';
        nuevaBarra.style.cssText = 'display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;';
        nuevaBarra.innerHTML = `
            <span><strong>${seleccionados}</strong> clase(s) seleccionada(s)</span>
            <button class="boton boton-primario boton-sm" id="btn-bulk-etiquetar">
                Etiquetar en lote
            </button>
        `;
        document.getElementById('aviso-faltantes').appendChild(nuevaBarra);

        document.getElementById('btn-bulk-etiquetar').addEventListener('click', async () => {
            const codigos = Array.from(document.querySelectorAll('.check-faltante:checked'))
                .map(c => c.dataset.codigo);
            if (!codigos.length) return;

            const grupo = prompt('Grupo para las clases seleccionadas (ej. Defectos):', '');
            if (grupo === null) return;
            const tipo = prompt('Tipo (Calibre / Calidad / Defecto / Granel / Descarte / Otro):', 'Otro');
            if (tipo === null) return;

            const clases = codigos.map(codigo => ({
                codigo,
                etiqueta: codigo,
                grupo: grupo || null,
                tipo: tipo || 'Otro'
            }));

            const r = await Api.diccionario.crearDesdeDeteccion(clases);
            if (r.ok && r.data?.success) {
                notificar(r.data.message, 'exito');
                cargarFaltantes();
                cargarDiccionario();
            } else {
                notificar(r.data?.message || 'Error', 'error');
            }
        });
    } else {
        barra.querySelector('span').innerHTML = `<strong>${seleccionados}</strong> clase(s) seleccionada(s)`;
    }
}

// =====================================================================
// Modal crear / editar
// =====================================================================
function abrirModalCrear() {
    document.getElementById('modal-clase-titulo').textContent = 'Nueva clase';
    document.getElementById('form-clase').reset();
    document.getElementById('clase-id').value = '';
    document.getElementById('clase-codigo').disabled = false;
    document.getElementById('clase-orden').value = 100;
    document.getElementById('clase-tipo').value = 'Otro';
    document.getElementById('clase-activo').checked = true;
    abrirModal('modal-clase');
}

function etiquetarFaltante(codigo) {
    document.getElementById('modal-clase-titulo').textContent = 'Etiquetar clase';
    document.getElementById('form-clase').reset();
    document.getElementById('clase-id').value = '';
    document.getElementById('clase-codigo').value = codigo;
    document.getElementById('clase-codigo').disabled = true;
    document.getElementById('clase-etiqueta').value = codigo;
    document.getElementById('clase-orden').value = 100;
    document.getElementById('clase-tipo').value = 'Otro';
    document.getElementById('clase-activo').checked = true;
    abrirModal('modal-clase');
    setTimeout(() => document.getElementById('clase-etiqueta').focus(), 200);
}

async function editarClase(id) {
    const r = await Api.diccionario.obtener(id);
    if (!r.ok || !r.data?.success) {
        notificar('No se pudo cargar la clase', 'error');
        return;
    }
    const c = r.data.data;
    document.getElementById('modal-clase-titulo').textContent = 'Editar clase';
    document.getElementById('clase-id').value = c.id;
    document.getElementById('clase-codigo').value = c.codigo;
    document.getElementById('clase-codigo').disabled = true;
    document.getElementById('clase-etiqueta').value = c.etiqueta || '';
    document.getElementById('clase-grupo').value = c.grupo || '';
    document.getElementById('clase-tipo').value = c.tipo || 'Otro';
    document.getElementById('clase-color').value = c.color || '';
    document.getElementById('clase-orden').value = c.orden || 100;
    document.getElementById('clase-notas').value = c.notas || '';
    document.getElementById('clase-activo').checked = parseInt(c.activo) === 1;
    abrirModal('modal-clase');
}

// =====================================================================
// Guardar
// =====================================================================
async function guardarClase() {
    const id = document.getElementById('clase-id').value;
    const datos = {
        codigo: document.getElementById('clase-codigo').value.trim(),
        etiqueta: document.getElementById('clase-etiqueta').value.trim() || null,
        grupo: document.getElementById('clase-grupo').value.trim() || null,
        tipo: document.getElementById('clase-tipo').value,
        color: document.getElementById('clase-color').value.trim() || null,
        orden: parseInt(document.getElementById('clase-orden').value || 100),
        notas: document.getElementById('clase-notas').value.trim() || null,
        activo: document.getElementById('clase-activo').checked ? 1 : 0
    };

    if (!datos.codigo) {
        notificar('El código es obligatorio', 'aviso');
        return;
    }

    const btn = document.getElementById('btn-guardar-clase');
    btn.disabled = true;

    let r;
    if (id) {
        // No enviar el código en edición
        const { codigo, ...datosEdit } = datos;
        r = await Api.diccionario.actualizar(id, datosEdit);
    } else {
        r = await Api.diccionario.crear(datos);
    }

    btn.disabled = false;

    if (r.ok && r.data?.success) {
        notificar(r.data.message || 'Guardado', 'exito');
        cerrarModal('modal-clase');
        cargarFaltantes();
        cargarDiccionario();
    } else {
        notificar(r.data?.message || 'Error al guardar', 'error');
    }
}

// =====================================================================
// Eliminar / restaurar
// =====================================================================
async function eliminarClase(id) {
    if (!confirmar('¿Desactivar esta clase? Seguirá en la BD pero no aparecerá en reportes.')) return;
    const r = await Api.diccionario.eliminar(id);
    if (r.ok && r.data?.success) {
        notificar('Clase desactivada', 'exito');
        cargarDiccionario();
    } else {
        notificar(r.data?.message || 'Error', 'error');
    }
}

async function restaurarClase(id) {
    const r = await Api.diccionario.restaurar(id);
    if (r.ok && r.data?.success) {
        notificar('Clase restaurada', 'exito');
        cargarDiccionario();
    } else {
        notificar(r.data?.message || 'Error', 'error');
    }
}