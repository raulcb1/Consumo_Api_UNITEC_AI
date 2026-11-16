# 📁 SCPCFrusan - Project Structure

*Generated on: 11/13/2026, 5:39:04 PM*

## 📋 Quick Overview

| Metric | Value |
|--------|-------|
| 📄 Total Files | 153 |
| 📁 Total Folders | 29 |
| 🌳 Max Depth | 4 levels |
| 🛠️ Tech Stack | CSS |

## 📊 File Statistics

### By File Type

- 📄 **.php** (Other files): 80 files (52.3%)
- 🎨 **.svg** (SVG images): 43 files (28.1%)
- 📜 **.js** (JavaScript files): 21 files (13.7%)
- 🎨 **.css** (Stylesheets): 3 files (2.0%)
- 📖 **.md** (Markdown files): 1 files (0.7%)
- 📄 **.** (Other files): 1 files (0.7%)
- 🖼️ **.png** (PNG images): 1 files (0.7%)
- ⚙️ **.json** (JSON files): 1 files (0.7%)
- 📄 **.lock** (Other files): 1 files (0.7%)
- 📄 **.xlsx** (Other files): 1 files (0.7%)

### By Category

- **Other**: 83 files (54.2%)
- **Assets**: 44 files (28.8%)
- **JavaScript**: 21 files (13.7%)
- **Styles**: 3 files (2.0%)
- **Docs**: 1 files (0.7%)
- **Config**: 1 files (0.7%)

### 📁 Largest Directories

- **root**: 153 files
- **public**: 105 files
- **public\assets**: 70 files
- **app**: 47 files
- **public\assets\icons**: 43 files

## 🌳 Directory Structure

```
SCPCFrusan/
├── 🚀 app/
│   ├── ⚙️ config/
│   │   ├── 📄 config.php
│   │   ├── 📄 database.php
│   │   ├── 📄 permisos.php
│   │   ├── 📄 reportes_predefinidos.php
│   │   └── 📄 secrets.php
│   ├── 📂 controllers/
│   │   ├── 📄 analiticacontroller.php
│   │   ├── 📄 authcontroller.php
│   │   ├── 📄 clases_diccionario_controller.php
│   │   ├── 📄 configuracioncontroller.php
│   │   ├── 📄 exportarcontroller.php
│   │   ├── 📄 lineascontroller.php
│   │   ├── 📄 lotescontroller.php
│   │   ├── 📄 panelcontroller.php
│   │   ├── 📄 programascontroller.php
│   │   ├── 📄 reportes_controller.php
│   │   ├── 📄 sincronizacioncontroller.php
│   │   ├── 📄 usuarioscontroller.php
│   │   └── 📄 visordatoscontroller.php
│   ├── 📂 core/
│   │   ├── 📄 auth.php
│   │   ├── 📄 exportador_csv.php
│   │   ├── 📄 iconos.php
│   │   ├── 📄 modelo.php
│   │   ├── 📄 motor_reportes.php
│   │   ├── 📄 response.php
│   │   └── 📄 unitec_api_client.php
│   ├── 📂 models/
│   │   ├── 📄 clase_diccionario.php
│   │   ├── 📄 configuracion_api.php
│   │   ├── 📄 conteo_desecho.php
│   │   ├── 📄 conteo_lote.php
│   │   ├── 📄 detalle_clase.php
│   │   ├── 📄 distribucion_lote.php
│   │   ├── 📄 empleado.php
│   │   ├── 📄 equipo_api.php
│   │   ├── 📄 linea.php
│   │   ├── 📄 lote.php
│   │   ├── 📄 procesamiento_lote.php
│   │   ├── 📄 programa.php
│   │   ├── 📄 promedio_clase.php
│   │   ├── 📄 promedio_lote.php
│   │   ├── 📄 registro_sincronizacion.php
│   │   ├── 📄 registros_auditoria.php
│   │   ├── 📄 reporte_usuario_config.php
│   │   ├── 📄 rol.php
│   │   ├── 📄 usuario_sistema.php
│   │   └── 📄 usuario.php
│   └── 📂 views/
│   │   └── 📂 partials/
│   │   │   ├── 📄 sidebar.php
│   │   │   └── 📄 topbar.php
├── 📖 project_structure.md
└── 🌐 public/
│   ├── 📄 _debug_sync.php
│   ├── 📄 .htaccess
│   ├── 📄 404.php
│   ├── 📂 analitica/
│   │   └── 📄 index.php
│   ├── 🔌 api/
│   │   ├── 📄 analitica.php
│   │   ├── 📄 clases_diccionario.php
│   │   ├── 📄 configuracion.php
│   │   ├── 📄 exportar_excel.php
│   │   ├── 📄 lineas.php
│   │   ├── 📄 lotes.php
│   │   ├── 📄 panel.php
│   │   ├── 📄 programas.php
│   │   ├── 📄 reportes.php
│   │   ├── 📄 sincronizacion.php
│   │   └── 📄 visordatos.php
│   ├── 📦 assets/
│   │   ├── 🎨 css/
│   │   │   └── 🎨 panel.css
│   │   ├── 📂 icons/
│   │   │   ├── 🎨 archivo.svg
│   │   │   ├── 🎨 atras.svg
│   │   │   ├── 🎨 auditoria.svg
│   │   │   ├── 🎨 aviso.svg
│   │   │   ├── 🎨 buscar.svg
│   │   │   ├── 🎨 calendario.svg
│   │   │   ├── 🎨 campana.svg
│   │   │   ├── 🎨 candado_abierto.svg
│   │   │   ├── 🎨 candado.svg
│   │   │   ├── 🎨 carpeta.svg
│   │   │   ├── 🎨 cerrar.svg
│   │   │   ├── 🎨 check_circulo.svg
│   │   │   ├── 🎨 check.svg
│   │   │   ├── 🎨 configuracion.svg
│   │   │   ├── 🎨 descargar.svg
│   │   │   ├── 🎨 detener.svg
│   │   │   ├── 🎨 editar.svg
│   │   │   ├── 🎨 eliminar.svg
│   │   │   ├── 🎨 error.svg
│   │   │   ├── 🎨 estadisticas.svg
│   │   │   ├── 🎨 etiqueta.svg
│   │   │   ├── 🎨 filtro.svg
│   │   │   ├── 🎨 grafico.svg
│   │   │   ├── 🎨 guardar.svg
│   │   │   ├── 🎨 info.svg
│   │   │   ├── 🎨 iniciar.svg
│   │   │   ├── 🎨 lineas.svg
│   │   │   ├── 🎨 lotes.svg
│   │   │   ├── 🎨 mas_circulo.svg
│   │   │   ├── 🎨 mas.svg
│   │   │   ├── 🎨 ojo_cerrado.svg
│   │   │   ├── 🎨 panel.svg
│   │   │   ├── 🎨 planta.svg
│   │   │   ├── 🎨 programas.svg
│   │   │   ├── 🎨 refrescar.svg
│   │   │   ├── 🎨 reloj.svg
│   │   │   ├── 🎨 reportes.svg
│   │   │   ├── 🎨 salir.svg
│   │   │   ├── 🎨 sincronizacion.svg
│   │   │   ├── 🎨 subir.svg
│   │   │   ├── 🎨 usuario.svg
│   │   │   ├── 🎨 usuarios.svg
│   │   │   └── 🎨 ver.svg
│   │   ├── 📂 img/
│   │   │   └── 🖼️ logo_frusan.png
│   │   ├── 📂 js/
│   │   │   ├── 📜 analitica.js
│   │   │   ├── 📜 api.js
│   │   │   ├── 📜 app.js
│   │   │   ├── 📜 configuracion.js
│   │   │   ├── 📜 diccionario.js
│   │   │   ├── 📜 lote_detalle.js
│   │   │   ├── 📜 lotes.js
│   │   │   ├── 📜 panel_old.js
│   │   │   ├── 📜 panel.js
│   │   │   ├── 📜 reportes_ver.js
│   │   │   ├── 📜 reportes.js
│   │   │   ├── 📜 sincronizacion.js
│   │   │   ├── 📜 visor_datos_old.js
│   │   │   └── 📜 visor_datos.js
│   │   └── 📂 libs/
│   │   │   ├── 📂 apexcharts/
│   │   │   │   ├── 🎨 apexcharts.css
│   │   │   │   ├── 📜 apexcharts.full.min.js
│   │   │   │   ├── 📜 apexcharts.min - copia.js
│   │   │   │   ├── 📜 apexcharts.min.js
│   │   │   │   └── 📜 streamgraph.js
│   │   │   ├── 📂 html2pdf/
│   │   │   │   └── 📜 html2pdf.bundle.min.js
│   │   │   ├── 📂 phpspreadsheet/
│   │   │   │   ├── ⚙️ composer.json
│   │   │   │   └── 📄 composer.lock
│   │   │   ├── 📂 sheetjs/
│   │   │   │   └── 📜 xlsx.full.min.js
│   │   │   └── 📂 tabulator/
│   │   │   │   ├── 🎨 tabulator.min.css
│   │   │   │   └── 📜 tabulator.min.js
│   ├── 📂 configuracion/
│   │   └── 📄 index.php
│   ├── 📄 dashboard.php
│   ├── 📂 diccionario/
│   │   └── 📄 index.php
│   ├── 📄 generar_hash.php
│   ├── 📄 index.php
│   ├── 📄 login.php
│   ├── 📄 logout.php
│   ├── 📂 lotes/
│   │   ├── 📄 detalle.php
│   │   └── 📄 listar.php
│   ├── 📄 panel_old.php
│   ├── 📄 panel.php
│   ├── 📂 pruebas/
│   │   ├── 📄 _scan_api.php
│   │   ├── 📄 _test_nueva_api_v2.php
│   │   ├── 📄 _test_nueva_api.php
│   │   └── 📄 php_version.php
│   ├── 📂 reportes/
│   │   ├── 📄 index.php
│   │   └── 📄 ver.php
│   ├── 📂 sincronizacion/
│   │   └── 📄 index.php
│   ├── 📂 templates/
│   │   └── 📄 plantilla_reporte.xlsx
│   └── 📂 visor/
│   │   └── 📄 index.php
```

## 📖 Legend

### File Types
- 📄 Other: Other files
- 📖 Docs: Markdown files
- 🎨 Styles: Stylesheets
- 🎨 Assets: SVG images
- 🖼️ Assets: PNG images
- 📜 JavaScript: JavaScript files
- ⚙️ Config: JSON files

### Importance Levels
- 🔴 Critical: Essential project files
- 🟡 High: Important configuration files
- 🔵 Medium: Helpful but not essential files
