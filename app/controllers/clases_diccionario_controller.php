<?php
// =====================================================================
// clases_diccionario_controller.php
// =====================================================================
// Ubicación: app/controllers/clases_diccionario_controller.php
// =====================================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/response.php';
require_once __DIR__ . '/../models/clase_diccionario.php';
require_once __DIR__ . '/../models/registros_auditoria.php';

class ClasesDiccionarioController
{
    private ClaseDiccionario $modelo;
    private RegistroAuditoria $auditoria;

    public function __construct()
    {
        Auth::requiereSesion();
        $this->modelo    = new ClaseDiccionario();
        $this->auditoria = new RegistroAuditoria();
    }

    // -----------------------------------------------------------------
    // LISTAR - GET
    // Parámetros opcionales: ?incluir_inactivas=true
    // -----------------------------------------------------------------
    public function listar(): void
    {
        if (!Auth::tienePermiso('diccionario', 'ver')) {
            Response::error('No tienes permiso para ver el diccionario.', 403);
        }

        $incluirInactivas = isset($_GET['incluir_inactivas']) && $_GET['incluir_inactivas'] === 'true';
        $clases = $incluirInactivas ? $this->modelo->listarTodo() : $this->modelo->listarActivas();
        Response::exito($clases, 'Diccionario de clases');
    }

    // -----------------------------------------------------------------
    // LISTAR FALTANTES - GET
    // -----------------------------------------------------------------
    public function listarFaltantes(): void
    {
        if (!Auth::tienePermiso('diccionario', 'ver')) {
            Response::error('No tienes permiso para ver el diccionario.', 403);
        }
        $faltantes = $this->modelo->listarFaltantes();
        Response::exito($faltantes, 'Clases sin definir');
    }

    // -----------------------------------------------------------------
    // OBTENER POR ID - GET
    // -----------------------------------------------------------------
    public function obtener(int $id): void
    {
        if (!Auth::tienePermiso('diccionario', 'ver')) {
            Response::error('No tienes permiso para ver el diccionario.', 403);
        }
        $clase = $this->modelo->obtenerPorId($id);
        if (!$clase) {
            Response::error('Clase no encontrada.', 404);
        }
        Response::exito($clase, 'Detalle de la clase');
    }

    // -----------------------------------------------------------------
    // CREAR - POST
    // -----------------------------------------------------------------
    public function crear(): void
    {
        if (!Auth::tienePermiso('diccionario', 'editar')) {
            Response::error('No tienes permiso para editar el diccionario.', 403);
        }

        $datos = $this->leerJson();
        $this->validar($datos, ['codigo']);
        $this->validarTipo($datos['tipo'] ?? 'Otro');

        if ($this->modelo->obtenerPorCodigo($datos['codigo'])) {
            Response::error('Ya existe una clase con ese código.', 409);
        }

        $usuario = Auth::usuarioActual();
        $datosCrear = [
            'codigo'     => $datos['codigo'],
            'etiqueta'   => $datos['etiqueta'] ?? $datos['codigo'],
            'grupo'      => $datos['grupo'] ?? null,
            'tipo'       => $datos['tipo'] ?? 'Otro',
            'color'      => $datos['color'] ?? null,
            'orden'      => isset($datos['orden']) ? (int) $datos['orden'] : 100,
            'notas'      => $datos['notas'] ?? null,
            'activo'     => isset($datos['activo']) ? (int) $datos['activo'] : 1,
            'created_by' => $usuario['usuario_id'],
            'updated_by' => $usuario['usuario_id']
        ];

        $id = $this->modelo->crear($datosCrear);
        if (!$id) {
            Response::error('No se pudo crear la clase.', 500);
        }

        $nueva = $this->modelo->obtenerPorId($id);
        $this->auditoria->registrar(
            $usuario['usuario_id'], 'clases_diccionario', $id,
            'INSERT', null, $nueva
        );

        Response::exito($nueva, 'Clase creada correctamente');
    }

    // -----------------------------------------------------------------
    // CREAR DESDE DETECCIÓN (bulk) - POST
    // Body: { clases: [ {codigo, etiqueta?, grupo?, tipo?}, ... ] }
    // Crea múltiples entradas de golpe a partir de las clases faltantes
    // -----------------------------------------------------------------
    public function crearDesdeDeteccion(): void
    {
        if (!Auth::tienePermiso('diccionario', 'editar')) {
            Response::error('No tienes permiso para editar el diccionario.', 403);
        }

        $datos = $this->leerJson();
        if (empty($datos['clases']) || !is_array($datos['clases'])) {
            Response::error('Debes enviar un array de clases.', 400);
        }

        $usuario = Auth::usuarioActual();
        $creadas = 0;
        $omitidas = 0;
        $errores = [];

        foreach ($datos['clases'] as $item) {
            if (empty($item['codigo'])) {
                $omitidas++;
                continue;
            }
            if ($this->modelo->obtenerPorCodigo($item['codigo'])) {
                $omitidas++;
                continue;
            }

            try {
                $id = $this->modelo->crear([
                    'codigo'     => $item['codigo'],
                    'etiqueta'   => $item['etiqueta'] ?? $item['codigo'],
                    'grupo'      => $item['grupo'] ?? null,
                    'tipo'       => $item['tipo'] ?? 'Otro',
                    'color'      => $item['color'] ?? null,
                    'orden'      => isset($item['orden']) ? (int) $item['orden'] : 100,
                    'notas'      => $item['notas'] ?? null,
                    'activo'     => 1,
                    'created_by' => $usuario['usuario_id'],
                    'updated_by' => $usuario['usuario_id']
                ]);
                if ($id) $creadas++;
            } catch (Exception $e) {
                $errores[] = ['codigo' => $item['codigo'], 'error' => $e->getMessage()];
            }
        }

        Response::exito(
            ['creadas' => $creadas, 'omitidas' => $omitidas, 'errores' => $errores],
            "Se crearon {$creadas} clases. {$omitidas} omitidas (ya existían)."
        );
    }

    // -----------------------------------------------------------------
    // ACTUALIZAR - POST
    // -----------------------------------------------------------------
    public function actualizar(int $id): void
    {
        if (!Auth::tienePermiso('diccionario', 'editar')) {
            Response::error('No tienes permiso para editar el diccionario.', 403);
        }

        $clase = $this->modelo->obtenerPorId($id);
        if (!$clase) {
            Response::error('Clase no encontrada.', 404);
        }

        $datos = $this->leerJson();
        if (isset($datos['tipo'])) {
            $this->validarTipo($datos['tipo']);
        }

        // Campos editables (el código no se toca una vez creado, para no romper el histórico)
        $permitidos = ['etiqueta', 'grupo', 'tipo', 'color', 'orden', 'notas', 'activo'];
        $actualizables = [];
        foreach ($permitidos as $campo) {
            if (array_key_exists($campo, $datos)) {
                $actualizables[$campo] = $datos[$campo];
            }
        }
        if (empty($actualizables)) {
            Response::error('No hay campos válidos para actualizar.', 400);
        }

        $usuario = Auth::usuarioActual();
        $actualizables['updated_by'] = $usuario['usuario_id'];

        if (!$this->modelo->actualizar($id, $actualizables)) {
            Response::error('No se pudo actualizar la clase.', 500);
        }

        $actualizada = $this->modelo->obtenerPorId($id);
        $this->auditoria->registrar(
            $usuario['usuario_id'], 'clases_diccionario', $id,
            'UPDATE', $clase, $actualizada
        );

        Response::exito($actualizada, 'Clase actualizada correctamente');
    }

    // -----------------------------------------------------------------
    // ELIMINAR (soft delete) - POST
    // -----------------------------------------------------------------
    public function eliminar(int $id): void
    {
        if (!Auth::tienePermiso('diccionario', 'editar')) {
            Response::error('No tienes permiso para editar el diccionario.', 403);
        }

        $clase = $this->modelo->obtenerPorId($id);
        if (!$clase) {
            Response::error('Clase no encontrada.', 404);
        }

        if (!$this->modelo->eliminar($id)) {
            Response::error('No se pudo eliminar la clase.', 500);
        }

        $usuario = Auth::usuarioActual();
        $this->auditoria->registrar(
            $usuario['usuario_id'], 'clases_diccionario', $id,
            'SOFT_DELETE', $clase, null
        );

        Response::exito(null, 'Clase eliminada correctamente');
    }

    // -----------------------------------------------------------------
    // RESTAURAR - POST
    // -----------------------------------------------------------------
    public function restaurar(int $id): void
    {
        if (!Auth::tienePermiso('diccionario', 'editar')) {
            Response::error('No tienes permiso para editar el diccionario.', 403);
        }

        if (!$this->modelo->restaurar($id)) {
            Response::error('No se pudo restaurar la clase.', 500);
        }

        $usuario = Auth::usuarioActual();
        $restaurada = $this->modelo->obtenerPorId($id);
        $this->auditoria->registrar(
            $usuario['usuario_id'], 'clases_diccionario', $id,
            'RESTORE', null, $restaurada
        );

        Response::exito($restaurada, 'Clase restaurada correctamente');
    }

    // -----------------------------------------------------------------
    // Helpers privados
    // -----------------------------------------------------------------
    private function leerJson(): array
    {
        $cuerpo = file_get_contents('php://input');
        $datos = json_decode($cuerpo, true);
        if (!is_array($datos)) {
            Response::error('Datos inválidos en la petición.', 400);
        }
        return $datos;
    }

    private function validar(array $datos, array $requeridos): void
    {
        foreach ($requeridos as $campo) {
            if (empty($datos[$campo])) {
                Response::error("El campo '$campo' es obligatorio.", 400);
            }
        }
    }

    private function validarTipo(string $tipo): void
    {
        $validos = ['Calibre', 'Calidad', 'Defecto', 'Granel', 'Descarte', 'Otro'];
        if (!in_array($tipo, $validos, true)) {
            Response::error("El tipo '$tipo' no es válido.", 400);
        }
    }
}