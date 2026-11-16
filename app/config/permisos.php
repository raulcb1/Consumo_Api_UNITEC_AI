<?php
// =====================================================================
// permisos.php - Definición de permisos del sistema
// =====================================================================
// Ubicación: app/config/permisos.php
//
// Estructura:
//   $PERMISOS['secciones'][identificador]['roles']    => roles que ven la sección
//   $PERMISOS['secciones'][identificador]['permisos'] => acciones y sus roles
//
// Uso:
//   Auth::puedeVerSeccion('lotes')          // para mostrar en el menú
//   Auth::tienePermiso('lotes', 'crear')    // para validar acciones
// =====================================================================

$PERMISOS = [
    'secciones' => [

        // -----------------------------------------------------------------
        // Panel principal
        // -----------------------------------------------------------------
        'panel' => [
            'roles' => ['admin', 'supervisor', 'operador', 'auditor'],
            'permisos' => [
                'ver' => ['admin', 'supervisor', 'operador', 'auditor']
            ]
        ],

        // -----------------------------------------------------------------
        // Líneas de producción
        // -----------------------------------------------------------------
        'lineas' => [
            'roles' => ['admin', 'supervisor'],
            'permisos' => [
                'ver'      => ['admin', 'supervisor'],
                'crear'    => ['admin'],
                'editar'   => ['admin'],
                'eliminar' => ['admin']
            ]
        ],

        // -----------------------------------------------------------------
        // Programas de procesamiento
        // -----------------------------------------------------------------
        'programas' => [
            'roles' => ['admin', 'supervisor'],
            'permisos' => [
                'ver'      => ['admin', 'supervisor'],
                'crear'    => ['admin'],
                'editar'   => ['admin'],
                'eliminar' => ['admin']
            ]
        ],

        // -----------------------------------------------------------------
        // Lotes
        // -----------------------------------------------------------------
        'lotes' => [
            'roles' => ['admin', 'supervisor', 'operador'],
            'permisos' => [
                'ver'      => ['admin', 'supervisor', 'operador', 'auditor'],
                'crear'    => ['admin', 'supervisor'],
                'editar'   => ['admin', 'supervisor'],
                'eliminar' => ['admin'],
                'iniciar'  => ['admin', 'supervisor', 'operador'],
                'detener'  => ['admin', 'supervisor', 'operador']
            ]
        ],

        // -----------------------------------------------------------------
        // Estadísticas de clasificación
        // -----------------------------------------------------------------
        'estadisticas' => [
            'roles' => ['admin', 'supervisor', 'operador', 'auditor'],
            'permisos' => [
                'ver'      => ['admin', 'supervisor', 'operador', 'auditor'],
                'exportar' => ['admin', 'supervisor', 'auditor']
            ]
        ],

        // -----------------------------------------------------------------
        // Gestión de usuarios del sistema
        // -----------------------------------------------------------------
        'usuarios' => [
            'roles' => ['admin'],
            'permisos' => [
                'ver'      => ['admin'],
                'crear'    => ['admin'],
                'editar'   => ['admin'],
                'eliminar' => ['admin']
            ]
        ],

        // -----------------------------------------------------------------
        // Sincronización con la API UNITEC
        // -----------------------------------------------------------------
        'sincronizacion' => [
            'roles' => ['admin', 'supervisor'],
            'permisos' => [
                'ver'      => ['admin', 'supervisor'],
                'ejecutar' => ['admin']
            ]
        ],

        // -----------------------------------------------------------------
        // Auditoría del sistema
        // -----------------------------------------------------------------
        'auditoria' => [
            'roles' => ['admin', 'auditor'],
            'permisos' => [
                'ver' => ['admin', 'auditor']
            ]
        ],

        // -----------------------------------------------------------------
        // Reportes
        // -----------------------------------------------------------------
        'reportes' => [
            'roles' => ['admin', 'supervisor', 'auditor'],
            'permisos' => [
                'ver'      => ['admin', 'supervisor', 'auditor'],
                'exportar' => ['admin', 'supervisor', 'auditor']
            ]
        ],

        // -----------------------------------------------------------------
        // Configuración de la API (URL, API Key)
        // -----------------------------------------------------------------
        'configuracion_api' => [
            'roles' => ['admin'],
            'permisos' => [
                'ver'    => ['admin'],
                'editar' => ['admin']
            ]
        ],

        // -----------------------------------------------------------------
        // Diccionario de clases
        // -----------------------------------------------------------------
        'diccionario' => [
            'roles' => ['admin', 'supervisor', 'auditor'],
            'permisos' => [
                'ver'    => ['admin', 'supervisor', 'auditor'],
                'editar' => ['admin', 'supervisor']
            ]
        ]
    ]
];

return $PERMISOS;
