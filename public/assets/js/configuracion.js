let equiposGlobal = [];

document.addEventListener('DOMContentLoaded', () => {
    cargarEquipos();
    document.getElementById('btn-nuevo').addEventListener('click', abrirModalCrear);
    document.getElementById('btn-guardar-equipo').addEventListener('click', guardarEquipo);
});

async function cargarEquipos() {
    const tbody = document.getElementById('tabla-equipos');
    tbody.innerHTML = '<tr><td colspan="5" class="vacio">Cargando...</td></tr>';

    const r = await Api.peticion('/api/configuracion.php?accion=listar');
    if (!r.ok || !r.data?.success) {
        tbody.innerHTML = `<tr><td colspan="5" class="vacio">${r.data?.message || 'Error'}</td></tr>`;
        return;
    }

    const { equipos, equipo_actual } = r.data.data;
    equiposGlobal = equipos;

    tbody.innerHTML = equipos.map(eq => {
        const esActivo = eq.id == equipo_actual;
        const filaClase = esActivo ? 'equipo-activo' : '';
        const badge = esActivo ? '<span class="badge-activo">En Uso</span>' : '';

        let botones = '';
        if (!esActivo) {
            botones += `<button class="boton boton-info boton-sm" onclick="seleccionarEquipo(${eq.id})">Usar este equipo</button> `;
        }
        botones += `<button class="boton boton-borde boton-sm" onclick="abrirModalEditar(${eq.id})">Editar</button>`;

        if (equipos.length > 1) {
            botones += ` <button class="boton boton-peligro boton-sm" onclick="eliminarEquipo(${eq.id})">Eliminar</button>`;
        }

        return `
            <tr class="${filaClase}">
                <td><strong>${escaparHtml(eq.nombre)}</strong> ${badge}</td>
                <td><code>${escaparHtml(eq.url_base)}</code></td>
                <td>${escaparHtml(eq.linea_por_defecto) || '—'}</td>
                <td><span style="color:var(--verde);">● Activo</span></td>
                <td><div class="acciones">${botones}</div></td>
            </tr>
        `;
    }).join('');
}

async function seleccionarEquipo(id) {
    if (!confirmar('¿Desea cambiar el equipo de trabajo al ID ' + id + '?')) return;

    const r = await Api.peticion(`/api/configuracion.php?accion=seleccionar&id=${id}`, { method: 'POST' });
    if (r.ok && r.data?.success) {
        notificar(r.data.message, 'exito');
        cargarEquipos(); // Recargar para mover el sombreado
    } else {
        notificar(r.data?.message || 'Error', 'error');
    }
}

function abrirModalCrear() {
    document.getElementById('modal-titulo').textContent = 'Nuevo Equipo';
    document.getElementById('form-equipo').reset();
    document.getElementById('equipo-id').value = '';
    document.getElementById('api_key').disabled = false;
    abrirModal('modal-equipo');
}

async function abrirModalEditar(id) {
    const r = await Api.peticion(`/api/configuracion.php?id=${id}`);
    if (!r.ok || !r.data?.success) {
        notificar('Error al cargar equipo', 'error');
        return;
    }
    const eq = r.data.data;

    document.getElementById('modal-titulo').textContent = 'Editar: ' + eq.nombre;
    document.getElementById('equipo-id').value = eq.id;
    document.getElementById('nombre').value = eq.nombre;
    document.getElementById('url_base').value = eq.url_base;
    document.getElementById('api_key').value = eq.api_key;
    document.getElementById('linea_por_defecto').value = eq.linea_por_defecto || '';
    document.getElementById('tiempo_espera_segundos').value = eq.tiempo_espera_segundos;

    abrirModal('modal-equipo');
}

async function guardarEquipo() {
    const id = document.getElementById('equipo-id').value;
    const datos = {
        nombre: document.getElementById('nombre').value.trim(),
        url_base: document.getElementById('url_base').value.trim(),
        api_key: document.getElementById('api_key').value.trim(),
        linea_por_defecto: document.getElementById('linea_por_defecto').value.trim(),
        tiempo_espera_segundos: document.getElementById('tiempo_espera_segundos').value.trim()
    };

    if (!datos.nombre || !datos.url_base || !datos.api_key) {
        notificar('Complete los campos obligatorios', 'aviso');
        return;
    }

    const btn = document.getElementById('btn-guardar-equipo');
    btn.disabled = true;

    const accion = id ? 'actualizar' : 'crear';
    const url = id
        ? `/api/configuracion.php?accion=actualizar&id=${id}`
        : `/api/configuracion.php?accion=crear`;

    const r = await Api.peticion(url, { method: 'POST', body: datos });

    btn.disabled = false;

    if (r.ok && r.data?.success) {
        notificar(r.data.message, 'exito');
        cerrarModal('modal-equipo');
        cargarEquipos();
    } else {
        notificar(r.data?.message || 'Error al guardar', 'error');
    }
}

async function eliminarEquipo(id) {
    if (!confirmar('¿Eliminar este equipo? Los datos de sus lotes seguirán en la BD.')) return;
    const r = await Api.peticion(`/api/configuracion.php?accion=eliminar&id=${id}`, { method: 'POST' });
    if (r.ok && r.data?.success) {
        notificar('Equipo eliminado', 'exito');
        cargarEquipos();
    } else {
        notificar(r.data?.message || 'Error', 'error');
    }
}