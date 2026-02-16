<?php

namespace App\Services;

use App\Models\CursoModel;
use App\Models\SeccionCursoModel;
use CodeIgniter\HTTP\Files\UploadedFile;
use RuntimeException;

class SeccionCursoService
{
    protected SeccionCursoModel $seccionModel;
    protected CursoModel $cursoModel;
    protected CursoService $cursoService;

    public function __construct(
        ?SeccionCursoModel $seccionModel = null,
        ?CursoModel $cursoModel = null,
        ?CursoService $cursoService = null
    ) {
        $this->seccionModel  = $seccionModel ?? new SeccionCursoModel();
        $this->cursoModel    = $cursoModel ?? new CursoModel();
        $this->cursoService  = $cursoService ?? \Config\Services::curso();
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

        $nombreCarpeta = $this->cursoService->nombreCarpetaDesdeNombre($curso['Nombre']);

        if (!$this->cursoService->existeCarpetaCurso($nombreCarpeta)) {
            throw new RuntimeException('La carpeta del curso no existe en el servidor.');
        }

        return $this->cursoService->rutaCarpetaCurso($nombreCarpeta);
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

        $mime = $file->getMimeType();
        if ($mime !== 'application/pdf') {
            throw new RuntimeException('Solo se permiten archivos PDF.');
        }

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
}
