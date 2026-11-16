/* =====================================================================
   app.js - Interacciones generales de la interfaz
   =====================================================================
   - Sidebar colapsable en móvil
   - Notificaciones flotantes (toasts)
   - Modales genéricos
   - Utilidades: formateo de fechas, confirmaciones, etc.
   - NOTA: la marca del enlace activo del sidebar la hace el servidor (PHP).
   ===================================================================== */

document.addEventListener('DOMContentLoaded', () => {

    // -----------------------------------------------------------------
    // 1. Sidebar colapsable (móvil)
    // -----------------------------------------------------------------
    const botonMenu = document.querySelector('.boton-menu');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay');

    if (botonMenu && sidebar) {
        botonMenu.addEventListener('click', () => {
            sidebar.classList.toggle('abierto');
            if (overlay) overlay.classList.toggle('activo');
        });
    }

    if (overlay) {
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('abierto');
            overlay.classList.remove('activo');
        });
    }

    // -----------------------------------------------------------------
    // 2. Marcar enlace activo del sidebar (según URL actual)
    // -----------------------------------------------------------------
    const rutaActual = window.location.pathname;
    document.querySelectorAll('.sidebar-nav a').forEach(enlace => {
        const href = enlace.getAttribute('href') || '';
        if (href && rutaActual.includes(href.split('/').slice(-2).join('/'))) {
            enlace.classList.add('activo');
        }
    });

    // -----------------------------------------------------------------
    // 2. Cerrar sidebar al hacer clic en un enlace (móvil)
    // -----------------------------------------------------------------
    document.querySelectorAll('.sidebar-nav a').forEach(enlace => {
        enlace.addEventListener('click', () => {
            if (window.innerWidth <= 900 && sidebar) {
                sidebar.classList.remove('abierto');
                if (overlay) overlay.classList.remove('activo');
            }
        });
    });
});

// =====================================================================
// Funciones globales de utilidad
// =====================================================================

/**
 * Muestra una notificación flotante.
 * @param {string} mensaje Texto de la notificación
 * @param {string} tipo    'exito' | 'error' | 'aviso' | 'info'
 * @param {string} titulo  Título opcional
 */
function notificar(mensaje, tipo = 'exito', titulo = '') {
    let zona = document.getElementById('zona-notificaciones');
    if (!zona) {
        zona = document.createElement('div');
        zona.id = 'zona-notificaciones';
        document.body.appendChild(zona);
    }

    const notif = document.createElement('div');
    notif.className = 'notificacion ' + (tipo === 'exito' ? '' : tipo);

    const titulos = {
        exito: '✔ Listo',
        error: '✖ Error',
        aviso: '⚠ Aviso',
        info: 'ℹ Información'
    };

    notif.innerHTML = `
        <strong>${titulo || titulos[tipo] || 'Mensaje'}</strong>
        ${mensaje}
    `;

    zona.appendChild(notif);

    setTimeout(() => {
        notif.style.opacity = '0';
        notif.style.transition = 'opacity 0.3s';
        setTimeout(() => notif.remove(), 300);
    }, 4500);
}

/**
 * Abre un modal por su ID (sin el #).
 */
function abrirModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.classList.add('activo');
}

/**
 * Cierra un modal por su ID.
 */
function cerrarModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.classList.remove('activo');
}

/**
 * Cierra el modal al hacer clic en el fondo (fuera de .modal).
 * Se registra automáticamente para todos los .modal-fondo.
 */
document.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal-fondo')) {
        e.target.classList.remove('activo');
    }
});

/**
 * Formatea una fecha ISO a formato local legible.
 * @param {string} fechaISO
 * @param {boolean} conHora
 */
function formatearFecha(fechaISO, conHora = false) {
    if (!fechaISO) return '—';
    try {
        const fecha = new Date(fechaISO);
        if (isNaN(fecha.getTime())) return fechaISO;
        const opciones = {
            day: '2-digit', month: '2-digit', year: 'numeric'
        };
        if (conHora) {
            opciones.hour = '2-digit';
            opciones.minute = '2-digit';
        }
        return fecha.toLocaleDateString('es-PE', opciones);
    } catch (e) {
        return fechaISO;
    }
}

/**
 * Escapa texto para insertarlo seguro en HTML.
 */
function escaparHtml(texto) {
    if (texto === null || texto === undefined) return '';
    return String(texto)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

/**
 * Pide confirmación con un modal sencillo (usa window.confirm por ahora).
 */
function confirmar(mensaje) {
    return window.confirm(mensaje);
}