<?php

namespace App\Services;

use App\Models\CursoModel;
use RuntimeException;

class CursoService
{
    protected CursoModel $cursoModel;
    protected string $cursosBasePath;

    public function __construct(?CursoModel $cursoModel = null)
    {
        $this->cursoModel   = $cursoModel ?? new CursoModel();
        $this->cursosBasePath = rtrim(WRITEPATH . 'cursos', DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    /**
     * Verifica si ya existe un curso con el mismo nombre (case-insensitive).
     */
    public function existeNombreCurso(string $nombre, ?int $excluirIdCurso = null): bool
    {
        $builder = $this->cursoModel->builder();
        $builder->where('LOWER(Nombre)', strtolower($nombre));

        if ($excluirIdCurso !== null) {
            $builder->where($this->cursoModel->primaryKey . ' !=', $excluirIdCurso);
        }

        return $builder->countAllResults(false) > 0;
    }

    /**
     * Obtiene el nombre de la carpeta a partir del ID del curso.
     */
    public function nombreCarpetaDesdeId(int $idCurso): string
    {
        if ($idCurso <= 0) {
            throw new RuntimeException('ID de curso inválido para crear carpeta.');
        }

        return (string) $idCurso;
    }

    /**
     * Ruta absoluta de la carpeta de un curso por ID.
     */
    public function rutaCarpetaCurso(int $idCurso): string
    {
        return $this->cursosBasePath . $this->nombreCarpetaDesdeId($idCurso);
    }

    /**
     * Comprueba si la carpeta del curso ya existe en el servidor.
     */
    public function existeCarpetaCurso(int $idCurso): bool
    {
        $ruta = $this->rutaCarpetaCurso($idCurso);
        return is_dir($ruta);
    }

    /**
     * Crea la carpeta del curso en el servidor. Crea la base 'cursos' si no existe.
     *
     * @return string Ruta absoluta de la carpeta creada
     * @throws RuntimeException Si no se puede crear la carpeta
     */
    public function crearCarpetaCurso(int $idCurso): string
    {
        if (!is_dir($this->cursosBasePath)) {
            if (!@mkdir($this->cursosBasePath, 0755, true)) {
                throw new RuntimeException('No se pudo crear el directorio base de cursos.');
            }
        }

        $ruta = $this->rutaCarpetaCurso($idCurso);

        if (is_dir($ruta)) {
            throw new RuntimeException('La carpeta del curso ya existe en el servidor.');
        }

        if (!@mkdir($ruta, 0755, true)) {
            throw new RuntimeException('No se pudo crear la carpeta del curso.');
        }

        return $ruta;
    }

    /**
     * Crea un nuevo curso: valida nombre único, inserta en BD y crea la carpeta.
     *
     * @param array $data Datos del curso: Nombre, Descripcion, Estado, PorcentajeAprobacion y CantidadPreguntas (opc). La portada se sube con POST /cursos/{id}/portada.
     * @return array ['idCurso' => int, 'rutaCarpeta' => string]
     * @throws RuntimeException Si la validación falla o no se puede crear la carpeta
     */
    public function crearCurso(array $data): array
    {
        $nombre = isset($data['Nombre']) ? trim((string) $data['Nombre']) : '';

        if ($nombre === '') {
            throw new RuntimeException('El nombre del curso es obligatorio.');
        }

        if ($this->existeNombreCurso($nombre)) {
            throw new RuntimeException('Ya existe un curso con ese nombre.');
        }

        $now = date('Y-m-d H:i:s');
        $payload = [
            'Nombre'           => $nombre,
            'Descripcion'      => $data['Descripcion'] ?? '',
            'ImagenPortada'    => null,
            'FechaCreacion'    => $now,
            'FechaModificacion'=> $now,
            'Estado'           => isset($data['Estado']) ? (int) $data['Estado'] : 1,
            'PorcentajeAprobacion' => isset($data['PorcentajeAprobacion']) ? (int) $data['PorcentajeAprobacion'] : 1,
            'CantidadPreguntas'    => isset($data['CantidadPreguntas']) ? (int) $data['CantidadPreguntas'] : 1,
        ];

        $id = $this->cursoModel->insert($payload);

        if ($id === false) {
            throw new RuntimeException('No se pudo guardar el curso en la base de datos.');
        }

        try {
            $rutaCarpeta = $this->crearCarpetaCurso((int) $id);
        } catch (RuntimeException $e) {
            $this->cursoModel->delete($id);
            throw $e;
        }

        $this->cursoModel->update($id, ['RutaCarpeta' => $rutaCarpeta]);

        return [
            'idCurso'        => (int) $id,
            'rutaCarpeta'    => $rutaCarpeta,
            'carpetaCursoId' => (int) $id,
        ];
    }
}
