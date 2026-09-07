<?php

namespace App\Services;

use App\Models\CursoModel;
use App\Models\SeccionCursoModel;
use CodeIgniter\HTTP\Files\UploadedFile;
use RuntimeException;

class SeccionCursoService
{
    /** Tamaño máximo del PDF en bytes (50 MB límite típico de la IA; usamos 20 MB por seguridad). */
    private const MAX_BYTES_PDF = 20 * 1024 * 1024;

    protected SeccionCursoModel $seccionModel;
    protected CursoModel $cursoModel;
    protected CursoService $cursoService;
    protected ResumenPdfService $resumenPdfService;

    public function __construct(
        ?SeccionCursoModel $seccionModel = null,
        ?CursoModel $cursoModel = null,
        ?CursoService $cursoService = null,
        ?ResumenPdfService $resumenPdfService = null
    ) {
        $this->seccionModel      = $seccionModel ?? new SeccionCursoModel();
        $this->cursoModel        = $cursoModel ?? new CursoModel();
        $this->cursoService      = $cursoService ?? \Config\Services::curso();
        $this->resumenPdfService = $resumenPdfService ?? \Config\Services::resumenPdf();
    }

    /**
     * Obtiene la ruta absoluta de la carpeta de un curso por ID.
     *
     * @throws RuntimeException Si el curso no existe o no tiene carpeta
     */
    public function rutaCarpetaCursoPorId(int $idCurso): string
    {
        $curso = $this->cursoModel->find($idCurso);

        if (!$curso) {
            throw new RuntimeException('El curso no existe.');
        }

        $idCurso = (int) $curso['idCurso'];

        if ($this->cursoService->existeCarpetaCurso($idCurso)) {
            return $this->cursoService->rutaCarpetaCurso($idCurso);
        }

        // Compatibilidad con cursos existentes creados antes del esquema por ID.
        $rutaLegacy = isset($curso['RutaCarpeta']) ? (string) $curso['RutaCarpeta'] : '';
        if ($rutaLegacy !== '' && is_dir($rutaLegacy)) {
            return $rutaLegacy;
        }

        throw new RuntimeException('La carpeta del curso no existe en el servidor.');
    }

    /**
     * Genera un nombre de archivo seguro y único para el PDF.
     */
    public function nombreArchivoUnico(UploadedFile $file): string
    {
        $extension = $file->getClientExtension() ?: 'pdf';
        if (strtolower($extension) !== 'pdf') {
            $extension = 'pdf';
        }
        $base = preg_replace('/[^\p{L}\p{N}_\-]/u', '_', pathinfo($file->getClientName(), PATHINFO_FILENAME));
        $base = $base ?: 'seccion';
        return $base . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    }

    /**
     * Guarda el archivo PDF en la carpeta del curso y crea el registro de sección.
     *
     * @param int   $idCurso    ID del curso
     * @param array $data       Datos: Nombre, Orden (opc), Estado (opc)
     * @param UploadedFile $file Archivo PDF subido (CodeIgniter\HTTP\Files\UploadedFile)
     * @return array ['idSeccionCurso' => int, 'rutaArchivo' => string]
     * @throws RuntimeException Si falla validación o guardado
     */
    public function crearSeccion(int $idCurso, array $data, UploadedFile $file): array
    {
        $nombre = isset($data['Nombre']) ? trim((string) $data['Nombre']) : '';

        if ($nombre === '') {
            throw new RuntimeException('El nombre de la sección es obligatorio.');
        }

        if (!$file->isValid()) {
            throw new RuntimeException($file->getErrorString() ?: 'El archivo no es válido.');
        }

        if ($file->getSize() === 0) {
            throw new RuntimeException('El archivo está vacío.');
        }

        $this->validarPdf($file);

        // Extraer y resumir el texto antes de mover el archivo, para fallar rápido
        // (PDF sin texto legible o error de la IA) sin dejar archivos huérfanos.
        $resumen = $this->resumenPdfService->procesarPdf($file->getTempName());

        $rutaCarpeta = $this->rutaCarpetaCursoPorId($idCurso);
        $nombreArchivo = $this->nombreArchivoUnico($file);
        $rutaDestino = $rutaCarpeta . DIRECTORY_SEPARATOR . $nombreArchivo;

        if (!$file->move($rutaCarpeta, $nombreArchivo)) {
            throw new RuntimeException('No se pudo guardar el archivo en el servidor.');
        }

        $now = date('Y-m-d H:i:s');
        $orden = isset($data['Orden']) ? (int) $data['Orden'] : $this->proximoOrden($idCurso);
        $estado = isset($data['Estado']) ? (int) $data['Estado'] : 1;

        $payload = [
            'idCurso'           => $idCurso,
            'Nombre'            => $nombre,
            'RutaArchivo'       => $nombreArchivo,
            'Resumen'           => $resumen,
            'Orden'             => $orden,
            'FechaCreacion'     => $now,
            'FechaModificacion' => $now,
            'Estado'            => $estado,
        ];

        $id = $this->seccionModel->insert($payload);

        if ($id === false) {
            @unlink($rutaDestino);
            throw new RuntimeException('No se pudo guardar la sección en la base de datos.');
        }

        return [
            'idSeccionCurso' => (int) $id,
            'rutaArchivo'    => $nombreArchivo,
            'rutaAbsoluta'   => $rutaDestino,
        ];
    }

    /**
     * Actualiza una sección existente (nombre/orden/estado) y opcionalmente reemplaza su PDF.
     *
     * @param array $data Campos opcionales: Nombre, Orden, Estado
     * @throws RuntimeException Si la sección no existe, no pertenece al curso o los datos son inválidos
     */
    public function actualizarSeccion(int $idCurso, int $idSeccionCurso, array $data, ?UploadedFile $file = null): array
    {
        if ($idCurso <= 0 || $idSeccionCurso <= 0) {
            throw new RuntimeException('ID de curso o sección inválido.');
        }

        $seccion = $this->seccionModel->find($idSeccionCurso);
        if (!$seccion || (int) $seccion['idCurso'] !== $idCurso) {
            throw new RuntimeException('La sección no existe para el curso indicado.');
        }

        $payload = [];

        if (array_key_exists('Nombre', $data)) {
            $nombre = trim((string) $data['Nombre']);
            if ($nombre === '') {
                throw new RuntimeException('El nombre de la sección no puede estar vacío.');
            }
            $payload['Nombre'] = $nombre;
        }

        if (array_key_exists('Orden', $data) && $data['Orden'] !== null && $data['Orden'] !== '') {
            $payload['Orden'] = (int) $data['Orden'];
        }

        if (array_key_exists('Estado', $data) && $data['Estado'] !== null && $data['Estado'] !== '') {
            $payload['Estado'] = (int) $data['Estado'];
        }

        $nuevaRutaArchivo = null;
        $rutaAnterior = null;
        $rutaDestinoNuevo = null;

        if ($file && $file->isValid() && $file->getError() !== UPLOAD_ERR_NO_FILE) {
            if ($file->getSize() === 0) {
                throw new RuntimeException('El archivo está vacío.');
            }

            $this->validarPdf($file);

            // Extraer y resumir antes de mover, igual que en la creación.
            $resumen = $this->resumenPdfService->procesarPdf($file->getTempName());

            $rutaCarpeta = $this->rutaCarpetaCursoPorId($idCurso);
            $nombreArchivo = $this->nombreArchivoUnico($file);
            $rutaDestinoNuevo = $rutaCarpeta . DIRECTORY_SEPARATOR . $nombreArchivo;

            if (!$file->move($rutaCarpeta, $nombreArchivo)) {
                throw new RuntimeException('No se pudo guardar el archivo en el servidor.');
            }

            $nuevaRutaArchivo = $nombreArchivo;
            $payload['RutaArchivo'] = $nombreArchivo;
            $payload['Resumen'] = $resumen;

            if (!empty($seccion['RutaArchivo'])) {
                $rutaAnterior = $rutaCarpeta . DIRECTORY_SEPARATOR . $seccion['RutaArchivo'];
            }
        }

        if ($payload === []) {
            throw new RuntimeException('No hay campos válidos para actualizar.');
        }

        $payload['FechaModificacion'] = date('Y-m-d H:i:s');

        if ($this->seccionModel->update($idSeccionCurso, $payload) === false) {
            if ($nuevaRutaArchivo !== null && $rutaDestinoNuevo && is_file($rutaDestinoNuevo)) {
                @unlink($rutaDestinoNuevo);
            }
            throw new RuntimeException('No se pudo actualizar la sección en la base de datos.');
        }

        if ($rutaAnterior && is_file($rutaAnterior)) {
            @unlink($rutaAnterior);
        }

        return [
            'idSeccionCurso' => $idSeccionCurso,
            'rutaArchivo'    => $payload['RutaArchivo'] ?? $seccion['RutaArchivo'],
        ];
    }

    /**
     * Elimina lógicamente una sección (Estado = 0) sin borrar archivo ni registro.
     *
     * @throws RuntimeException Si la sección no existe para el curso
     */
    public function inactivarSeccion(int $idCurso, int $idSeccionCurso): array
    {
        return $this->actualizarSeccion(
            $idCurso,
            $idSeccionCurso,
            ['Estado' => 0],
            null
        );
    }

    /**
     * Obtiene el siguiente valor de Orden para una nueva sección.
     */
    protected function proximoOrden(int $idCurso): int
    {
        $builder = $this->seccionModel->builder();
        $builder->selectMax('Orden');
        $builder->where('idCurso', $idCurso);
        $result = $builder->get()->getRowArray();
        return isset($result['Orden']) && $result['Orden'] !== null
            ? (int) $result['Orden'] + 1
            : 0;
    }

    /**
     * Lista las secciones de un curso.
     */
    public function listarPorCurso(int $idCurso): array
    {
        return $this->seccionModel->seccionesPorCurso($idCurso);
    }

    /**
     * Resuelve el archivo de una sección por curso y nombre/ruta de archivo.
     *
     * @return array{rutaAbsoluta:string,nombreDescarga:string,mime:string}
     * @throws RuntimeException Si el curso, la sección o el archivo no son válidos
     */
    public function resolverArchivoDescarga(int $idCurso, string $archivoUrl): array
    {
        if ($idCurso <= 0) {
            throw new RuntimeException('ID de curso inválido.');
        }

        $archivoUrl = trim($archivoUrl);
        if ($archivoUrl === '') {
            throw new RuntimeException('El parámetro archivoUrl es obligatorio.');
        }

        // Admite nombre simple o una URL/ruta y extrae solo el nombre de archivo.
        $archivoUrl = str_replace('\\', '/', $archivoUrl);
        $archivoUrl = urldecode($archivoUrl);
        $archivo    = basename($archivoUrl);

        if ($archivo === '' || $archivo === '.' || $archivo === '..') {
            throw new RuntimeException('El nombre de archivo es inválido.');
        }

        $seccion = $this->seccionModel
            ->where('idCurso', $idCurso)
            ->where('RutaArchivo', $archivo)
            ->first();

        if (!$seccion) {
            throw new RuntimeException('No existe una sección para ese curso con el archivo indicado.');
        }

        $carpetaCurso = $this->rutaCarpetaCursoPorId($idCurso);
        $rutaAbsoluta = $carpetaCurso . DIRECTORY_SEPARATOR . $archivo;

        if (!is_file($rutaAbsoluta) || !is_readable($rutaAbsoluta)) {
            throw new RuntimeException('El archivo no existe o no se puede leer en el servidor.');
        }

        return [
            'rutaAbsoluta'   => $rutaAbsoluta,
            'nombreDescarga' => $archivo,
            'mime'           => 'application/pdf',
        ];
    }

    private function validarPdf(UploadedFile $file): void
    {
        $mime = $file->getMimeType();
        if ($mime !== 'application/pdf') {
            throw new RuntimeException('Solo se permiten archivos PDF.');
        }

        if ($file->getSize() > self::MAX_BYTES_PDF) {
            $maxMb = self::MAX_BYTES_PDF / 1024 / 1024;
            throw new RuntimeException("El PDF supera el tamaño máximo permitido ({$maxMb} MB).");
        }
    }
}
