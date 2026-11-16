/* =====================================================================
   api.js - Funciones genéricas para llamar a los endpoints del backend
   =====================================================================
   Centraliza todas las peticiones AJAX del sistema. Los módulos usan
   estas funciones en lugar de llamar a fetch() directamente.
   ===================================================================== */

// BASE_URL es definida en cada página PHP antes de cargar este script
// Ejemplo: <script>const BASE_URL = '<?= APP_URL ?>';</script>

const Api = (() => {

    /**
     * Petición HTTP genérica. Devuelve { ok, status, data }.
     */
    async function peticion(url, opciones = {}) {
        const config = {
            method: opciones.method || 'GET',
            headers: {
                'Accept': 'application/json',
                ...(opciones.headers || {})
            }
        };

        if (opciones.body !== undefined) {
            config.headers['Content-Type'] = 'application/json';
            config.body = JSON.stringify(opciones.body);
        }

        try {
            const respuesta = await fetch(BASE_URL + url, config);
            let datos = null;

            if (respuesta.status !== 204) {
                const texto = await respuesta.text();
                try {
                    datos = texto ? JSON.parse(texto) : null;
                } catch (e) {
                    datos = { success: false, message: 'Respuesta no válida del servidor.', raw: texto };
                }
            }

            return {
                ok: respuesta.ok,
                status: respuesta.status,
                data: datos
            };
        } catch (error) {
            return {
                ok: false,
                status: 0,
                data: {
                    success: false,
                    message: 'Error de conexión con el servidor: ' + error.message
                }
            };
        }
    }

    // =================================================================
    // Métodos genéricos
    // =================================================================
    function listar(recurso, queryParams = {}) {
        const qs = new URLSearchParams(queryParams).toString();
        const url = `/api/${recurso}.php${qs ? '?' + qs : ''}`;
        return peticion(url, { method: 'GET' });
    }

    function obtener(recurso, id) {
        return peticion(`/api/${recurso}.php?id=${id}`, { method: 'GET' });
    }

    function crear(recurso, datos) {
        return peticion(`/api/${recurso}.php`, { method: 'POST', body: datos });
    }

    function actualizar(recurso, id, datos) {
        return peticion(`/api/${recurso}.php?accion=actualizar&id=${id}`, {
            method: 'POST',
            body: datos
        });
    }

    function eliminar(recurso, id) {
        return peticion(`/api/${recurso}.php?accion=eliminar&id=${id}`, {
            method: 'POST'
        });
    }

    // =================================================================
    // Métodos específicos (se irán agregando según los módulos)
    // =================================================================

    // Panel
    const panel = {
        resumen: () => peticion('/api/panel.php?accion=resumen')
    };

    // Líneas
    const lineas = {
        listar: () => listar('lineas'),
        obtener: (id) => obtener('lineas', id),
        crear: (datos) => crear('lineas', datos),
        actualizar: (id, datos) => actualizar('lineas', id, datos),
        eliminar: (id) => eliminar('lineas', id)
    };

    // Programas
    const programas = {
        listar: (lineaId = null) => listar('programas', lineaId ? { linea_id: lineaId } : {}),
        obtener: (id) => obtener('programas', id),
        crear: (datos) => crear('programas', datos),
        actualizar: (id, datos) => actualizar('programas', id, datos),
        eliminar: (id) => eliminar('programas', id)
    };

    // Lotes
    const lotes = {
        listar: (filtros = {}) => listar('lotes', filtros),
        obtener: (id) => obtener('lotes', id),
        crear: (datos) => crear('lotes', datos),
        actualizar: (id, datos) => actualizar('lotes', id, datos),
        eliminar: (id) => eliminar('lotes', id),
        iniciar: (id) => peticion(`/api/lotes.php?accion=iniciar&id=${id}`, { method: 'POST' }),
        detener: (id) => peticion(`/api/lotes.php?accion=detener&id=${id}`, { method: 'POST' }),
        estadisticas: (id) => peticion(`/api/lotes.php?accion=estadisticas&id=${id}`),
        estadisticasGuardadas: (id) => peticion(`/api/lotes.php?accion=estadisticas_guardadas&id=${id}`)
    };

    // Usuarios
    const usuarios = {
        listar: () => listar('usuarios'),
        obtener: (id) => obtener('usuarios', id),
        crear: (datos) => crear('usuarios', datos),
        actualizar: (id, datos) => actualizar('usuarios', id, datos),
        eliminar: (id) => eliminar('usuarios', id),
        roles: () => peticion('/api/usuarios.php?accion=roles'),
        empleados: () => peticion('/api/usuarios.php?accion=empleados')
    };

    // Sincronización
    const sincronizacion = {
        lineas: () => peticion('/api/sincronizacion.php?accion=lineas', { method: 'POST' }),

        programas: (lineaId) => peticion('/api/sincronizacion.php?accion=programas', {
            method: 'POST',
            body: { linea_id: lineaId }
        }),

        lotes: (lineaId, soloActual = false) => peticion('/api/sincronizacion.php?accion=lotes', {
            method: 'POST',
            body: { linea_id: lineaId, solo_actual: soloActual }
        }),

        estadisticas: (lineaId = null, forzar = false) =>
            peticion('/api/sincronizacion.php?accion=estadisticas', {
                method: 'POST',
                body: {
                    linea_id: lineaId || 0,
                    forzar: forzar
                }
            }),

        // Si se pasa lineaId, sincroniza esa línea; si no, sincroniza todo el sistema
        todo: (lineaId = null) => peticion('/api/sincronizacion.php?accion=todo', {
            method: 'POST',
            body: lineaId ? { linea_id: lineaId } : {}
        }),

        logs: () => peticion('/api/sincronizacion.php?accion=logs'),

        listarLineas: () => peticion('/api/sincronizacion.php?accion=listar_lineas')
    };

    // Diccionario de clases
    const diccionario = {
        listar: (incluirInactivas = false) =>
            peticion(`/api/clases_diccionario.php${incluirInactivas ? '?incluir_inactivas=true' : ''}`),
        faltantes: () => peticion('/api/clases_diccionario.php?accion=listar_faltantes'),
        obtener: (id) => peticion(`/api/clases_diccionario.php?id=${id}`),
        crear: (datos) => peticion('/api/clases_diccionario.php', { method: 'POST', body: datos }),
        actualizar: (id, datos) => peticion(`/api/clases_diccionario.php?accion=actualizar&id=${id}`, { method: 'POST', body: datos }),
        eliminar: (id) => peticion(`/api/clases_diccionario.php?accion=eliminar&id=${id}`, { method: 'POST' }),
        restaurar: (id) => peticion(`/api/clases_diccionario.php?accion=restaurar&id=${id}`, { method: 'POST' }),
        crearDesdeDeteccion: (clases) => peticion('/api/clases_diccionario.php?accion=crear_desde_deteccion', {
            method: 'POST',
            body: { clases }
        })
    };

    return {
        peticion,
        listar, obtener, crear, actualizar, eliminar,
        panel, lineas, programas, lotes, usuarios, sincronizacion, diccionario
    };
})();