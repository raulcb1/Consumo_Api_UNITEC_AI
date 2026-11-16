<?php
// =====================================================================
// reportes_predefinidos.php - Catálogo de reportes del sistema
// =====================================================================
// Estructura:
//   'codigo_reporte' => [
//       'nombre'      => string,
//       'descripcion' => string,
//       'categoria'   => string,
//       'icono'       => string (nombre del SVG en assets/icons),
//       'orden'       => int,
//       'permiso'     => ['seccion', 'accion'] para validar acceso,
//       'sql'         => SELECT base con :filtros_dinamicos,
//       'columnas'    => [ 'codigo' => ['etiqueta','tipo','formula'?,...] ],
//       'filtros'     => [ 'codigo' => ['etiqueta','tipo','operador','campo_sql'?,...] ],
//   ]
// =====================================================================

return [

    // =================================================================
    // REPORTE: Pallets procesados
    // =================================================================
    'pallets_procesados' => [
        'nombre'      => 'Pallets procesados',
        'descripcion' => 'Listado de pallets con su análisis de clasificación resumido.',
        'categoria'   => 'Trazabilidad',
        'icono'       => 'lotes',
        'orden'       => 10,
        'permiso'     => ['reportes', 'ver'],

        // SQL base con JOINS. Los filtros se añaden dinámicamente por el motor.
        'sql' => "
            SELECT 
                l.id,
                l.nombre_lote,
                l.codigo_externo,
                l.fecha_inicio,
                l.fecha_fin,
                l.descripcion_productor,
                l.codigo_productor,
                l.descripcion_variedad,
                l.codigo_variedad,
                l.peso AS peso_declarado,
                ln.linea AS linea_codigo,
                (SELECT COUNT(*) FROM detalles_clase dc 
                    INNER JOIN conteos_lote cl ON cl.id = dc.conteo_lote_id 
                    WHERE cl.lote_id = l.id AND dc.tipo_programa = 'production') AS num_clases_prod,
                (SELECT IFNULL(SUM(dc.total_frutas),0) FROM detalles_clase dc 
                    INNER JOIN conteos_lote cl ON cl.id = dc.conteo_lote_id 
                    WHERE cl.lote_id = l.id AND dc.tipo_programa = 'production') AS frutas_procesadas,
                (SELECT IFNULL(SUM(dc.peso_total_kg),0) FROM detalles_clase dc 
                    INNER JOIN conteos_lote cl ON cl.id = dc.conteo_lote_id 
                    WHERE cl.lote_id = l.id AND dc.tipo_programa = 'production') AS kg_procesados,
                (SELECT IFNULL(SUM(cd.peso_total_kg),0) FROM conteos_desecho cd 
                    INNER JOIN conteos_lote cl ON cl.id = cd.conteo_lote_id 
                    WHERE cl.lote_id = l.id) AS kg_desecho,
                (SELECT COUNT(*) FROM procesamientos_lotes pl 
                    WHERE pl.lote_id = l.id AND pl.es_actual = 1) AS activo,
                (SELECT MAX(cl.consultado_en) FROM conteos_lote cl 
                    WHERE cl.lote_id = l.id) AS ultima_consulta_stats
            FROM lotes l
            LEFT JOIN lineas ln ON ln.id = l.linea_id
            WHERE l.deleted_at IS NULL
        ",

        'columnas' => [
            'codigo_externo'     => ['etiqueta' => 'Código pallet',    'tipo' => 'texto',   'orden' => 1,  'ancho' => 140],
            'nombre_lote'        => ['etiqueta' => 'Nombre UNITEC',    'tipo' => 'texto',   'orden' => 2,  'ancho' => 180],
            'linea_codigo'       => ['etiqueta' => 'Línea',            'tipo' => 'texto',   'orden' => 3,  'ancho' => 100],
            'descripcion_productor' => ['etiqueta' => 'Módulo',        'tipo' => 'texto',   'orden' => 4,  'ancho' => 180],
            'descripcion_variedad'  => ['etiqueta' => 'Variedad',      'tipo' => 'texto',   'orden' => 5,  'ancho' => 160],
            'fecha_inicio'       => ['etiqueta' => 'Inicio',           'tipo' => 'fecha',   'orden' => 6,  'ancho' => 140, 'con_hora' => true],
            'fecha_fin'          => ['etiqueta' => 'Fin',              'tipo' => 'fecha',   'orden' => 7,  'ancho' => 140, 'con_hora' => true],
            'peso_declarado'     => ['etiqueta' => 'Peso declarado',   'tipo' => 'texto',   'orden' => 8,  'ancho' => 110],
            'num_clases_prod'    => ['etiqueta' => 'Clases',           'tipo' => 'entero',  'orden' => 9,  'ancho' => 80],
            'frutas_procesadas'  => ['etiqueta' => 'Frutas',           'tipo' => 'entero',  'orden' => 10, 'ancho' => 100, 'formato_miles' => true],
            'kg_procesados'      => ['etiqueta' => 'Kg procesados',    'tipo' => 'decimal', 'orden' => 11, 'ancho' => 120],
            'kg_desecho'         => ['etiqueta' => 'Kg desecho',       'tipo' => 'decimal', 'orden' => 12, 'ancho' => 110],
            'porcentaje_merma'   => ['etiqueta' => '% Merma',          'tipo' => 'decimal', 'orden' => 13, 'ancho' => 100, 'formula' => 'porcentaje_merma'],
            'activo'             => ['etiqueta' => 'Estado',           'tipo' => 'booleano', 'orden' => 14, 'ancho' => 100, 'formula' => 'estado_lote'],
            'ultima_consulta_stats' => ['etiqueta' => 'Última consulta stats', 'tipo' => 'fecha', 'orden' => 15, 'ancho' => 150, 'con_hora' => true],
        ],

        'filtros' => [
            'linea_id' => [
                'etiqueta'   => 'Línea',
                'tipo'       => 'select',
                'operador'   => '=',
                'campo_sql'  => 'l.linea_id',
                'opciones_sql' => "SELECT id AS valor, CONCAT(linea, ' — ', IFNULL(descripcion,'')) AS etiqueta FROM lineas WHERE deleted_at IS NULL ORDER BY linea"
            ],
            'fecha_inicio' => [
                'etiqueta'   => 'Desde',
                'tipo'       => 'rango_fecha',
                'operador'   => '>=',
                'campo_sql'  => 'l.fecha_inicio'
            ],
            'fecha_fin' => [
                'etiqueta'   => 'Hasta',
                'tipo'       => 'rango_fecha',
                'operador'   => '<=',
                'campo_sql'  => 'l.fecha_fin'
            ],
            'codigo_externo' => [
                'etiqueta'   => 'Código pallet',
                'tipo'       => 'texto',
                'operador'   => 'LIKE',
                'campo_sql'  => 'l.codigo_externo'
            ],
            'variedad' => [
                'etiqueta'   => 'Variedad',
                'tipo'       => 'texto',
                'operador'   => 'LIKE',
                'campo_sql'  => 'l.descripcion_variedad'
            ],
            'solo_con_stats' => [
                'etiqueta'   => 'Solo con estadísticas',
                'tipo'       => 'booleano',
                'operador'   => 'EXISTS',
                'sql_extra'  => "EXISTS (SELECT 1 FROM conteos_lote cl2 WHERE cl2.lote_id = l.id)"
            ],
        ],

        'orden_default' => ['fecha_inicio' => 'DESC'],
        'limite_default' => 500,
    ],

    // =================================================================
    // REPORTE: Detalle de clases por pallet
    // =================================================================
    'detalle_clases_pallet' => [
        'nombre'      => 'Detalle de clases por pallet',
        'descripcion' => 'Desglose de las clases (producción y contabilidad) que se generaron en el último procesamiento de cada pallet.',
        'categoria'   => 'Trazabilidad',
        'icono'       => 'etiqueta',
        'orden'       => 20,
        'permiso'     => ['reportes', 'ver'],

        // SQL base
        'sql' => "
            SELECT 
                l.codigo_externo,
                l.nombre_lote,
                dc.nombre_clase,
                IFNULL(cd.etiqueta, dc.nombre_clase) AS etiqueta_clase,
                dc.nombre_grupo_clase,
                dc.tipo_programa,
                dc.total_frutas,
                dc.peso_total_kg,
                cl.consultado_en AS fecha_consulta
            FROM detalles_clase dc
            INNER JOIN conteos_lote cl ON cl.id = dc.conteo_lote_id
            INNER JOIN lotes l ON l.id = cl.lote_id
            LEFT JOIN clases_diccionario cd ON cd.codigo = dc.nombre_clase AND cd.deleted_at IS NULL
            INNER JOIN (
                -- Subconsulta para traer solo el ÚLTIMO snapshot de cada lote
                SELECT MAX(id) AS id FROM conteos_lote GROUP BY lote_id
            ) ultimo ON ultimo.id = cl.id
            WHERE l.deleted_at IS NULL
        ",

        'columnas' => [
            'codigo_externo'     => ['etiqueta' => 'Código pallet', 'tipo' => 'texto',   'orden' => 1,  'ancho' => 140],
            'nombre_lote'        => ['etiqueta' => 'Nombre UNITEC', 'tipo' => 'texto',   'orden' => 2,  'ancho' => 180],
            'etiqueta_clase'     => ['etiqueta' => 'Clase',         'tipo' => 'texto',   'orden' => 3,  'ancho' => 180],
            'nombre_grupo_clase' => ['etiqueta' => 'Grupo',         'tipo' => 'texto',   'orden' => 4,  'ancho' => 150],
            'tipo_programa'      => ['etiqueta' => 'Programa',      'tipo' => 'texto',   'orden' => 5,  'ancho' => 120],
            'total_frutas'       => ['etiqueta' => 'Frutas',        'tipo' => 'entero',  'orden' => 6,  'ancho' => 100, 'formato_miles' => true],
            'peso_total_kg'      => ['etiqueta' => 'Peso (kg)',     'tipo' => 'decimal', 'orden' => 7,  'ancho' => 110],
            'fecha_consulta'     => ['etiqueta' => 'Fecha consulta', 'tipo' => 'fecha',   'orden' => 8,  'ancho' => 140, 'con_hora' => true],
        ],

        'filtros' => [
            'linea_id' => [
                'etiqueta'   => 'Línea',
                'tipo'       => 'select',
                'operador'   => '=',
                'campo_sql'  => 'l.linea_id',
                'opciones_sql' => "SELECT id AS valor, CONCAT(linea, ' — ', IFNULL(descripcion,'')) AS etiqueta FROM lineas WHERE deleted_at IS NULL ORDER BY linea"
            ],
            'codigo_externo' => [
                'etiqueta'   => 'Código pallet',
                'tipo'       => 'texto', // Este sí puede quedar como texto por si buscan un código específico
                'operador'   => 'LIKE',
                'campo_sql'  => 'l.codigo_externo'
            ],
            'tipo_programa' => [
                'etiqueta'   => 'Tipo de programa',
                'tipo'       => 'select',
                'operador'   => '=',
                'campo_sql'  => 'dc.tipo_programa',
                'opciones'   => [
                    ['valor' => 'production', 'etiqueta' => 'Producción'],
                    ['valor' => 'accounting', 'etiqueta' => 'Contabilidad']
                ]
            ],
            'nombre_clase' => [
                'etiqueta'   => 'Clase',
                'tipo'       => 'select',
                'operador'   => '=',
                'campo_sql'  => 'dc.nombre_clase',
                'opciones_sql' => "SELECT codigo AS valor, CONCAT(codigo, ' - ', IFNULL(etiqueta, 'Sin definir')) AS etiqueta 
                                   FROM clases_diccionario 
                                   WHERE deleted_at IS NULL 
                                   ORDER BY orden ASC, codigo ASC"
            ]
        ],

        'grafico' => [
            'tipo'          => 'bar',
            'categoria'     => 'etiqueta_clase',
            'valor'         => 'peso_total_kg',
            'titulo_serie'  => 'Peso (kg)',
            'titulo'        => 'Top 10 Clases por Peso Acumulado (Último snapshot)'
        ],

        'orden_default' => ['peso_total_kg' => 'DESC'],
        'limite_default' => 500,
    ],

    // =================================================================
    // REPORTE: Rendimiento por Variedad
    // =================================================================
    'rendimiento_variedad' => [
        'nombre'      => 'Rendimiento por Variedad',
        'descripcion' => 'Resumen de kilos procesados y desechos agrupados por variedad de arándano.',
        'categoria'   => 'Comparativas',
        'icono'       => 'estadisticas',
        'orden'       => 30,
        'permiso'     => ['reportes', 'ver'],

        // SQL con subconsultas para evitar duplicar kilos al hacer JOINs
        'sql' => "
            SELECT 
                l.descripcion_variedad AS variedad,
                COUNT(DISTINCT l.id) AS total_lotes,
                
                -- Sumamos kilos procesados (solo del último snapshot de cada lote)
                SUM(COALESCE(c.kg_procesados, 0)) AS kg_procesados,
                
                -- Sumamos kilos de desecho
                SUM(COALESCE(d.kg_desecho, 0)) AS kg_desecho

            FROM lotes l
            -- Subconsulta: kilos procesados por lote (último snapshot)
            LEFT JOIN (
                SELECT cl.lote_id, SUM(dc.peso_total_kg) AS kg_procesados
                FROM conteos_lote cl
                INNER JOIN detalles_clase dc ON dc.conteo_lote_id = cl.id
                INNER JOIN (SELECT MAX(id) as id FROM conteos_lote GROUP BY lote_id) ult ON ult.id = cl.id
                WHERE dc.tipo_programa = 'production'
                GROUP BY cl.lote_id
            ) c ON c.lote_id = l.id
            
            -- Subconsulta: kilos de desecho por lote (último snapshot)
            LEFT JOIN (
                SELECT cl.lote_id, SUM(cd.peso_total_kg) AS kg_desecho
                FROM conteos_lote cl
                INNER JOIN conteos_desecho cd ON cd.conteo_lote_id = cl.id
                INNER JOIN (SELECT MAX(id) as id FROM conteos_lote GROUP BY lote_id) ult ON ult.id = cl.id
                GROUP BY cl.lote_id
            ) d ON d.lote_id = l.id
            
            WHERE l.deleted_at IS NULL 
              AND l.descripcion_variedad IS NOT NULL 
              AND l.descripcion_variedad != ''
            GROUP BY l.descripcion_variedad
        ",

        'columnas' => [
            'variedad'          => ['etiqueta' => 'Variedad',        'tipo' => 'texto',   'orden' => 1,  'ancho' => 200],
            'total_lotes'       => ['etiqueta' => 'Lotes',           'tipo' => 'entero',  'orden' => 2,  'ancho' => 90],
            'kg_procesados'     => ['etiqueta' => 'Kg Procesados',   'tipo' => 'decimal', 'orden' => 3,  'ancho' => 130],
            'kg_desecho'        => ['etiqueta' => 'Kg Desecho',      'tipo' => 'decimal', 'orden' => 4,  'ancho' => 120],
            'porcentaje_merma'  => ['etiqueta' => '% Merma',         'tipo' => 'decimal', 'orden' => 5,  'ancho' => 100, 'formula' => 'porcentaje_merma_variedad'],
            'kg_totales'        => ['etiqueta' => 'Kg Totales',      'tipo' => 'decimal', 'orden' => 6,  'ancho' => 120, 'formula' => 'suma_kg_totales'],
        ],

        'filtros' => [
            // Si deseas filtrar por campaña o fechas, se puede añadir aquí
        ],

        'grafico' => [
            'tipo'          => 'bar',
            'categoria'     => 'variedad',
            'valor'         => 'kg_procesados',
            'titulo_serie'  => 'Kg Procesados',
            'titulo'        => 'Variedades con Mayor Volumen de Producción'
        ],

        'orden_default' => ['kg_procesados' => 'DESC'],
        'limite_default' => 100,
    ],

    // =================================================================
    // REPORTE: Explorador Jerárquico (Variedad -> Lote -> Evolución)
    // =================================================================
    'explorador_jerarquico' => [
        'nombre'      => 'Explorador de Producción',
        'descripcion' => 'Análisis de kilos procesados desde lo general (Variedad) hasta la evolución de clases por lote.',
        'categoria'   => 'Comparativas',
        'icono'       => 'estadisticas',
        'orden'       => 5, // Ponlo arriba
        'permiso'     => ['reportes', 'ver'],

        'sql' => "
            SELECT 
                l.id AS lote_id,
                l.descripcion_variedad AS variedad,
                l.nombre_lote AS lote,
                l.codigo_externo,
                SUM(dc.peso_total_kg) AS kg_procesados
            FROM lotes l
            INNER JOIN conteos_lote cl ON cl.lote_id = l.id
            INNER JOIN detalles_clase dc ON dc.conteo_lote_id = cl.id
            INNER JOIN (SELECT MAX(id) as id FROM conteos_lote GROUP BY lote_id) ult ON ult.id = cl.id
            WHERE l.deleted_at IS NULL 
              AND dc.tipo_programa = 'production'
              AND l.descripcion_variedad IS NOT NULL
            GROUP BY l.id, l.descripcion_variedad, l.nombre_lote, l.codigo_externo
        ",

        'columnas' => [
            'variedad'      => ['etiqueta' => 'Variedad',    'tipo' => 'texto',   'orden' => 1, 'ancho' => 200],
            'lote'          => ['etiqueta' => 'Lote UNITEC', 'tipo' => 'texto',   'orden' => 2, 'ancho' => 180],
            'codigo_externo' => ['etiqueta' => 'Código Pallet', 'tipo' => 'texto',  'orden' => 3, 'ancho' => 150],
            'kg_procesados' => ['etiqueta' => 'Kg Procesados', 'tipo' => 'decimal', 'orden' => 4, 'ancho' => 130],
            // Columna virtual para el botón
            'accion'        => ['etiqueta' => 'Acciones',    'tipo' => 'html',    'orden' => 5, 'ancho' => 150]
        ],

        'filtros' => [
            'variedad' => [
                'etiqueta'   => 'Variedad',
                'tipo'       => 'select',
                'operador'   => '=',
                'campo_sql'  => 'l.descripcion_variedad',
                'opciones_sql' => "SELECT DISTINCT descripcion_variedad AS valor, descripcion_variedad AS etiqueta 
                                   FROM lotes 
                                   WHERE descripcion_variedad IS NOT NULL 
                                     AND descripcion_variedad != '' 
                                     AND deleted_at IS NULL 
                                   ORDER BY descripcion_variedad ASC"
            ]
        ],

        'grafico' => [
            'tipo'          => 'bar',
            'categoria'     => 'variedad',
            'valor'         => 'kg_procesados',
            'titulo_serie'  => 'Kg',
            'titulo'        => 'Volumen por Variedad'
        ],

        'orden_default' => ['variedad' => 'ASC', 'kg_procesados' => 'DESC'],
        'limite_default' => 1000,
    ],

    // =================================================================
    // REPORTE: Análisis de Calidad (Promedios)
    // =================================================================
    'analisis_calidad' => [
        'nombre'      => 'Análisis de Calidad (Promedios)',
        'descripcion' => 'Muestra los promedios de calibre, color, defectos y NIR extraídos por el sorter para cada lote.',
        'categoria'   => 'Calidad',
        'icono'       => 'calidad', // Asegúrate de tener este icono, o usa 'reportes'
        'orden'       => 25,
        'permiso'     => ['reportes', 'ver'],

        // Usamos GROUP_CONCAT para pivotar los criterios en columnas
        'sql' => "
            SELECT 
                l.codigo_externo,
                l.nombre_lote,
                l.descripcion_variedad AS variedad,
                cl.consultado_en AS fecha_analisis,
                
                -- Extraemos valores específicos de la tabla de promedios
                MAX(CASE WHEN pl.criterio = 'AvgDiameter' THEN pl.puntaje_promedio END) AS diametro_promedio,
                MAX(CASE WHEN pl.criterio = 'AvgColor' THEN pl.puntaje_promedio END) AS color_promedio,
                MAX(CASE WHEN pl.criterio = 'Defects' THEN pl.puntaje_promedio END) AS defectos_promedio,
                MAX(CASE WHEN pl.criterio = 'Weight' THEN pl.puntaje_promedio END) AS peso_promedio,
                MAX(CASE WHEN pl.criterio = 'Nir1' THEN pl.puntaje_promedio END) AS nir1,
                
                -- Contamos cuántos criterios se midieron en total
                COUNT(pl.id) AS total_criterios_medidos
                
            FROM lotes l
            INNER JOIN conteos_lote cl ON cl.lote_id = l.id
            LEFT JOIN promedios_lote pl ON pl.conteo_lote_id = cl.id
            INNER JOIN (SELECT MAX(id) as id FROM conteos_lote GROUP BY lote_id) ult ON ult.id = cl.id
            WHERE l.deleted_at IS NULL
            
            GROUP BY l.id, l.codigo_externo, l.nombre_lote, l.descripcion_variedad, cl.consultado_en
        ",

        'columnas' => [
            'codigo_externo'         => ['etiqueta' => 'Código pallet',    'tipo' => 'texto',   'orden' => 1,  'ancho' => 130],
            'nombre_lote'            => ['etiqueta' => 'Nombre UNITEC',    'tipo' => 'texto',   'orden' => 2,  'ancho' => 160],
            'variedad'               => ['etiqueta' => 'Variedad',         'tipo' => 'texto',   'orden' => 3,  'ancho' => 150],
            'diametro_promedio'      => ['etiqueta' => 'Calibre (mm)',     'tipo' => 'decimal', 'orden' => 4,  'ancho' => 120],
            'color_promedio'         => ['etiqueta' => 'Color (Score)',    'tipo' => 'decimal', 'orden' => 5,  'ancho' => 120],
            'defectos_promedio'      => ['etiqueta' => '% Defectos',       'tipo' => 'decimal', 'orden' => 6,  'ancho' => 120],
            'peso_promedio'          => ['etiqueta' => 'Peso Fruta (g)',   'tipo' => 'decimal', 'orden' => 7,  'ancho' => 120],
            'nir1'                   => ['etiqueta' => 'NIR 1 (Azúcar)',   'tipo' => 'decimal', 'orden' => 8,  'ancho' => 120],
            'fecha_analisis'         => ['etiqueta' => 'Fecha Análisis',   'tipo' => 'fecha',   'orden' => 9,  'ancho' => 140, 'con_hora' => true],
        ],

        'filtros' => [
            'linea_id' => [
                'etiqueta'   => 'Línea',
                'tipo'       => 'select',
                'operador'   => '=',
                'campo_sql'  => 'l.linea_id',
                'opciones_sql' => "SELECT id AS valor, CONCAT(linea, ' — ', IFNULL(descripcion,'')) AS etiqueta FROM lineas WHERE deleted_at IS NULL ORDER BY linea"
            ],
            'variedad' => [
                'etiqueta'   => 'Variedad',
                'tipo'       => 'select',
                'operador'   => '=',
                'campo_sql'  => 'l.descripcion_variedad',
                'opciones_sql' => "SELECT DISTINCT descripcion_variedad AS valor, descripcion_variedad AS etiqueta 
                                   FROM lotes 
                                   WHERE descripcion_variedad IS NOT NULL 
                                     AND descripcion_variedad != '' 
                                     AND deleted_at IS NULL 
                                   ORDER BY descripcion_variedad ASC"
            ]
        ],

        'grafico' => [
            'tipo'          => 'scatter', // Gráfico de dispersión
            'categoria'     => 'diametro_promedio',
            'valor'         => 'color_promedio',
            'titulo_serie'  => 'Color promedio',
            'titulo'        => 'Dispersión: Calibre vs Color (Por lote)'
        ],

        'orden_default' => ['diametro_promedio' => 'DESC'],
        'limite_default' => 500,
    ],

    // =================================================================
    // REPORTE: Análisis de Desechos por Variedad
    // =================================================================
    'analisis_desechos' => [
        'nombre'      => 'Análisis de Desechos y Mermas',
        'descripcion' => 'Resumen de kilos descartados (Pre y Post selección) agrupados por variedad.',
        'categoria'   => 'Calidad',
        'icono'       => 'reportes',
        'orden'       => 35,
        'permiso'     => ['reportes', 'ver'],

        'sql' => "
            SELECT 
                l.descripcion_variedad AS variedad,
                
                -- Sumamos kilos por tipo de desecho
                SUM(CASE WHEN cd.tipo_desecho = 'PreSelection' THEN cd.peso_total_kg ELSE 0 END) AS kg_pre_seleccion,
                SUM(CASE WHEN cd.tipo_desecho = 'PostSelection' THEN cd.peso_total_kg ELSE 0 END) AS kg_post_seleccion,
                
                -- Total de desecho
                SUM(cd.peso_total_kg) AS kg_total_desecho,
                
                -- Contamos cuántos lotes diferentes tuvieron desecho
                COUNT(DISTINCT cl.lote_id) AS lotes_afectados
                
            FROM conteos_desecho cd
            INNER JOIN conteos_lote cl ON cl.id = cd.conteo_lote_id
            INNER JOIN lotes l ON l.id = cl.lote_id
            INNER JOIN (SELECT MAX(id) as id FROM conteos_lote GROUP BY lote_id) ult ON ult.id = cl.id
            WHERE l.deleted_at IS NULL 
              AND l.descripcion_variedad IS NOT NULL
            GROUP BY l.descripcion_variedad
        ",

        'columnas' => [
            'variedad'          => ['etiqueta' => 'Variedad',         'tipo' => 'texto',   'orden' => 1, 'ancho' => 200],
            'lotes_afectados'   => ['etiqueta' => 'Lotes afectados',  'tipo' => 'entero',  'orden' => 2, 'ancho' => 130],
            'kg_pre_seleccion'  => ['etiqueta' => 'Kg Pre-Selección', 'tipo' => 'decimal', 'orden' => 3, 'ancho' => 150],
            'kg_post_seleccion' => ['etiqueta' => 'Kg Post-Selección', 'tipo' => 'decimal', 'orden' => 4, 'ancho' => 150],
            'kg_total_desecho'  => ['etiqueta' => 'Total Kg Desecho', 'tipo' => 'decimal', 'orden' => 5, 'ancho' => 140],
        ],

        'filtros' => [
            'variedad' => [
                'etiqueta'   => 'Variedad',
                'tipo'       => 'select',
                'operador'   => '=',
                'campo_sql'  => 'l.descripcion_variedad',
                'opciones_sql' => "SELECT DISTINCT descripcion_variedad AS valor, descripcion_variedad AS etiqueta 
                                   FROM lotes 
                                   WHERE descripcion_variedad IS NOT NULL 
                                     AND descripcion_variedad != '' 
                                     AND deleted_at IS NULL 
                                   ORDER BY descripcion_variedad ASC"
            ]
        ],

        'grafico' => [
            'tipo'          => 'pie', // Gráfico circular ideal para proporciones de desecho
            'categoria'     => 'variedad',
            'valor'         => 'kg_total_desecho',
            'titulo_serie'  => 'Kg Desecho',
            'titulo'        => 'Distribución de Mermas por Variedad'
        ],

        'orden_default' => ['kg_total_desecho' => 'DESC'],
        'limite_default' => 100,
    ],

];
