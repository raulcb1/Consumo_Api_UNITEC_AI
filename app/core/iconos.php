<?php
// =====================================================================
// iconos.php - Helper para insertar iconos SVG inline
// =====================================================================
// Ubicación: app/core/iconos.php
//
// Uso:
/*   <?= icono('panel') ?>
**   <?= icono('editar', 'icono-boton') ?>
**   <?= icono('lotes', 'icono-nav icono-lg') ?>
*/
// Los SVG viven en public/assets/icons/*.svg y usan currentColor para
// heredar el color del texto. No dependen de internet.
// =====================================================================

/**
 * Devuelve el HTML de un icono SVG inline.
 *
 * @param string $nombre Nombre del archivo sin extensión (ej. "panel")
 * @param string $clase  Clases CSS opcionales
 * @return string
 */
function icono(string $nombre, string $clase = ''): string
{
    // Sanitizar el nombre para evitar directory traversal
    $nombre = preg_replace('/[^a-z0-9_\-]/i', '', $nombre);
    if ($nombre === '') {
        return '';
    }

    $ruta = __DIR__ . '/../../public/assets/icons/' . $nombre . '.svg';
    if (!file_exists($ruta)) {
        return '';
    }

    $svg = file_get_contents($ruta);
    if ($svg === false) {
        return '';
    }

    // Inyectar la clase CSS en la etiqueta <svg>
    if (!empty($clase)) {
        $clase = htmlspecialchars($clase, ENT_QUOTES, 'UTF-8');
        $svg = preg_replace('/<svg\b/', '<svg class="' . $clase . '"', $svg, 1);
    }

    return $svg;
}

/**
 * Devuelve un icono dentro de un <span> contenedor (útil para botones con
 * texto o para centrar el icono en cajas).
 */
function icono_span(string $nombre, string $clase = '', string $claseContenedor = ''): string
{
    $svg = icono($nombre, $clase);
    if ($svg === '') {
        return '';
    }
    $claseContenedor = htmlspecialchars($claseContenedor, ENT_QUOTES, 'UTF-8');
    return '<span class="icono-wrap ' . $claseContenedor . '">' . $svg . '</span>';
}