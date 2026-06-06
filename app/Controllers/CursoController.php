<?php

namespace App\Controllers;

use App\Models\CursoModel;
use App\Services\CursoService;
use App\Services\ImageUploadService;
use CodeIgniter\RESTful\ResourceController;
use OpenApi\Attributes as OA;
use RuntimeException;

class CursoController extends ResourceController
{
    protected $format = 'json';

    #[OA\Get(
        path: "/cursos",
        tags: ["Cursos"],
        summary: "Listar todos los cursos",
        parameters: [
            new OA\Parameter(
                name: "Estado",
                in: "query",
                required: false,
                description: "Si se envía este parámetro, se filtra por Estado IN (0,1). Si no se envía, trae todos los cursos.",
                schema: new OA\Schema(type: "integer", example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de cursos",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        type: "object",
                        properties: [
                            new OA\Property(property: "idCurso", type: "integer", example: 1),
                            new OA\Property(property: "Nombre", type: "string", example: "Introducción a Polímeros"),
                            new OA\Property(property: "Descripcion", type: "string"),
                            new OA\Property(property: "ImagenPortada", type: "string", nullable: true, description: "Ruta relativa bajo public, ej. uploads/cursos/1/xxx.jpg"),
                            new OA\Property(property: "FechaCreacion", type: "string", format: "date-time"),
                            new OA\Property(property: "Estado", type: "integer", example: 1),
                            new OA\Property(property: "PorcentajeAprobacion", type: "integer", example: 70),
                            new OA\Property(property: "CantidadPreguntas", type: "integer", example: 10),
                            new OA\Property(property: "RutaCarpeta", type: "string", nullable: true, example: "d:/app/writable/cursos/1"),
                        ]
                    )
                )
            )
        ]
    )]
    public function index()
    {
        $model = new CursoModel();
        $queryParams = $this->request->getGet();

        if (is_array($queryParams) && array_key_exists('Estado', $queryParams)) {
            $cursos = $model->whereIn('Estado', [0, 1])->findAll();
        } else {
            $cursos = $model->findAll();
        }

        return $this->respond($cursos);
    }

    #[OA\Get(
        path: "/cursos/activos-con-preguntas",
        tags: ["Cursos"],
        summary: "Listar cursos activos con preguntas asociadas y progreso del estudiante autenticado (si existe)",
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de cursos activos con preguntas y progreso opcional",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        type: "object",
                        properties: [
                            new OA\Property(property: "idCurso", type: "integer", example: 1),
                            new OA\Property(property: "Nombre", type: "string", example: "Introducción a Polímeros"),
                            new OA\Property(property: "Descripcion", type: "string"),
                            new OA\Property(property: "ImagenPortada", type: "string", nullable: true),
                            new OA\Property(property: "Estado", type: "integer", example: 1),
                            new OA\Property(property: "PorcentajeAprobacion", type: "integer", example: 70),
                            new OA\Property(property: "CantidadPreguntas", type: "integer", example: 10),
                            new OA\Property(property: "TotalPreguntasAsociadas", type: "integer", example: 12),
                            new OA\Property(property: "UltimaCalificacion", type: "number", format: "float", nullable: true, example: 83.5),
                            new OA\Property(property: "FechaInicio", type: "string", format: "date-time", nullable: true),
                            new OA\Property(property: "FechaFinalizacion", type: "string", format: "date-time", nullable: true),
                            new OA\Property(property: "Aprobo", type: "integer", nullable: true, example: 1),
                            new OA\Property(property: "VariablesSeguimiento", type: "string", nullable: true),
                        ]
                    )
                )
            ),
            new OA\Response(response: 401, description: "No autenticado"),
        ]
    )]
    public function activosConPreguntas()
    {
        $idUsuario = (int) ($this->request->authUser['id'] ?? 0);
        if ($idUsuario <= 0) {
            return $this->failUnauthorized('No autenticado.');
        }

        $model = new CursoModel();
        $builder = $model->builder('curso c');
        $builder
            ->select('
                c.idCurso,
                c.Nombre,
                c.Descripcion,
                c.ImagenPortada,
                c.Estado,
                c.PorcentajeAprobacion,
                c.CantidadPreguntas,
                COUNT(DISTINCT p.idPregunta) AS TotalPreguntasAsociadas,
                cde.UltimaCalificacion,
                cde.FechaInicio,
                cde.FechaFinalizacion,
                cde.Aprobo,
                cde.VariablesSeguimiento
            ')
            ->join('pregunta p', 'p.idCurso = c.idCurso AND p.Estado = 1', 'inner')
            ->join(
                'cursodesarrolloestudiante cde',
                'cde.idCurso = c.idCurso AND cde.idUsuario = ' . $idUsuario,
                'left'
            )
            ->where('c.Estado', 1)
            ->groupBy('
                c.idCurso,
                c.Nombre,
                c.Descripcion,
                c.ImagenPortada,
                c.Estado,
                c.PorcentajeAprobacion,
                c.CantidadPreguntas,
                cde.UltimaCalificacion,
                cde.FechaInicio,
                cde.FechaFinalizacion,
                cde.Aprobo,
                cde.VariablesSeguimiento
            ')
            ->orderBy('c.idCurso', 'ASC');

        $cursos = $builder->get()->getResultArray();

        return $this->respond($cursos, 200);
    }

    #[OA\Get(
        path: "/cursos/{id}",
        tags: ["Cursos"],
        summary: "Obtener un curso por ID",
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer", example: 2))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Curso encontrado",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "idCurso", type: "integer", example: 1),
                        new OA\Property(property: "Nombre", type: "string", example: "Introducción a Polímeros"),
                        new OA\Property(property: "Descripcion", type: "string"),
                        new OA\Property(property: "ImagenPortada", type: "string", nullable: true, description: "Ruta relativa bajo public, ej. uploads/cursos/1/xxx.jpg"),
                        new OA\Property(property: "FechaCreacion", type: "string", format: "date-time"),
                        new OA\Property(property: "FechaModificacion", type: "string", format: "date-time"),
                        new OA\Property(property: "Estado", type: "integer", example: 1),
                        new OA\Property(property: "PorcentajeAprobacion", type: "integer", example: 70),
                        new OA\Property(property: "CantidadPreguntas", type: "integer", example: 10),
                        new OA\Property(property: "RutaCarpeta", type: "string", nullable: true, example: "d:/app/writable/cursos/1"),
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Curso no encontrado"),
        ]
    )]
    public function show($id = null)
    {
        $model = new CursoModel();
        $curso = $model->find($id);

        if (! $curso) {
            return $this->failNotFound('Curso no encontrado.');
        }

        return $this->respond($curso, 200);
    }

    #[OA\Post(
        path: "/cursos",
        tags: ["Cursos"],
        summary: "Crear un nuevo curso (carpeta en servidor para PDFs, etc.)",
        description: "Crea el registro y una carpeta en `writable/cursos/`. Requiere `PorcentajeAprobacion` (1 a 100) y `CantidadPreguntas` (1 a 25). No incluya imagen de portada aquí: después use `POST /cursos/{id}/portada` con multipart (campo `portada`, máx. 5 MB).",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                required: ["Nombre", "PorcentajeAprobacion", "CantidadPreguntas"],
                properties: [
                    new OA\Property(property: "Nombre", type: "string", example: "Introducción a Polímeros"),
                    new OA\Property(property: "Descripcion", type: "string", example: "Curso introductorio", nullable: true),
                    new OA\Property(property: "Estado", type: "integer", example: 1, description: "1 = activo, 0 = inactivo", nullable: true),
                    new OA\Property(property: "PorcentajeAprobacion", type: "integer", example: 70, description: "Porcentaje mínimo de aprobación del curso (1 a 100)"),
                    new OA\Property(property: "CantidadPreguntas", type: "integer", example: 10, description: "Cantidad total de preguntas del curso (1 a 25)"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Curso creado correctamente",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "idCurso", type: "integer", example: 1),
                        new OA\Property(property: "rutaCarpeta", type: "string", example: "d:/app/writable/cursos/1"),
                        new OA\Property(property: "carpetaCursoId", type: "integer", example: 1),
                        new OA\Property(property: "message", type: "string", example: "Curso creado correctamente"),
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Nombre duplicado, nombre vacío, envío de ImagenPortada en JSON o rangos inválidos: PorcentajeAprobacion (1-100), CantidadPreguntas (1-25)"),
        ]
    )]
    public function create()
    {
        $data = $this->request->getJSON(true);

        if (!$data || empty(trim($data['Nombre'] ?? ''))) {
            return $this->fail('El nombre del curso es obligatorio.', 400);
        }

        if (array_key_exists('ImagenPortada', $data) && $data['ImagenPortada'] !== null && $data['ImagenPortada'] !== '') {
            return $this->fail(
                'La portada no se envía en JSON. Cree el curso y luego use POST /cursos/{id}/portada (multipart, campo: portada, máx. 5 MB).',
                400
            );
        }

        if (!array_key_exists('PorcentajeAprobacion', $data)) {
            return $this->fail('El campo PorcentajeAprobacion es obligatorio y debe estar entre 1 y 100.', 400);
        }

        if (!array_key_exists('CantidadPreguntas', $data)) {
            return $this->fail('El campo CantidadPreguntas es obligatorio y debe estar entre 1 y 25.', 400);
        }

        $porcentajeAprobacion = filter_var(
            $data['PorcentajeAprobacion'],
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1, 'max_range' => 100]]
        );
        if ($porcentajeAprobacion === false) {
            return $this->fail('PorcentajeAprobacion debe ser un entero entre 1 y 100.', 400);
        }

        $cantidadPreguntas = filter_var(
            $data['CantidadPreguntas'],
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1, 'max_range' => 25]]
        );
        if ($cantidadPreguntas === false) {
            return $this->fail('CantidadPreguntas debe ser un entero entre 1 y 25.', 400);
        }

        $data['PorcentajeAprobacion'] = $porcentajeAprobacion;
        $data['CantidadPreguntas'] = $cantidadPreguntas;

        try {
            $cursoService = \Config\Services::curso();
            $resultado = $cursoService->crearCurso($data);
        } catch (RuntimeException $e) {
            return $this->fail($e->getMessage(), 400);
        }

        return $this->respondCreated([
            'idCurso'        => $resultado['idCurso'],
            'rutaCarpeta'    => $resultado['rutaCarpeta'],
            'carpetaCursoId' => $resultado['carpetaCursoId'],
            'message'        => 'Curso creado correctamente',
        ]);
    }

    #[OA\Put(
        path: "/cursos/{id}",
        tags: ["Cursos"],
        summary: "Actualizar un curso por ID (solo admin)",
        description: "Para cambiar la imagen de portada use `POST /cursos/{id}/portada`. Para quitarla envíe `ImagenPortada: null`. No envíe URL ni ruta en JSON para una nueva imagen.",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer", example: 2))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "Nombre", type: "string", example: "Curso actualizado", nullable: true),
                    new OA\Property(property: "Descripcion", type: "string", nullable: true),
                    new OA\Property(property: "ImagenPortada", type: "string", nullable: true, description: "Solo null para quitar. Para subir use POST /cursos/{id}/portada."),
                    new OA\Property(property: "Estado", type: "integer", example: 1, nullable: true),
                    new OA\Property(property: "PorcentajeAprobacion", type: "integer", example: 70, nullable: true, description: "Valor permitido entre 1 y 100"),
                    new OA\Property(property: "CantidadPreguntas", type: "integer", example: 10, nullable: true, description: "Valor permitido entre 1 y 25"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Curso actualizado",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Curso actualizado correctamente"),
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Sin campos válidos, nombre duplicado, intento de enviar portada como texto o rangos inválidos: PorcentajeAprobacion (1-100), CantidadPreguntas (1-25)"),
            new OA\Response(response: 404, description: "Curso no encontrado"),
        ]
    )]
    public function update($id = null)
    {
        $model = new CursoModel();
        $curso = $model->find($id);
        if (! $curso) {
            return $this->failNotFound('Curso no encontrado.');
        }

        $data = $this->request->getJSON(true);
        if (! $data) {
            return $this->fail('Datos inválidos', 400);
        }

        $payload = array_intersect_key($data, array_flip(['Nombre', 'Descripcion', 'Estado', 'PorcentajeAprobacion', 'CantidadPreguntas']));

        if (array_key_exists('ImagenPortada', $data)) {
            $valor = $data['ImagenPortada'];
            if ($valor !== null && $valor !== '') {
                return $this->fail(
                    'La portada se sube con POST /cursos/{id}/portada (multipart, campo: portada, máx. 5 MB). Envíe ImagenPortada: null solo para quitarla.',
                    400
                );
            }
            \Config\Services::imageUpload()->removeStoredPublicImage($curso['ImagenPortada'] ?? null);
            $payload['ImagenPortada'] = null;
        }

        if ($payload === []) {
            return $this->fail('No hay campos válidos para actualizar', 400);
        }

        if (isset($payload['Nombre'])) {
            $payload['Nombre'] = trim((string) $payload['Nombre']);
            if ($payload['Nombre'] === '') {
                return $this->fail('El nombre del curso no puede estar vacío.', 400);
            }

            $cursoService = \Config\Services::curso();
            if ($cursoService->existeNombreCurso($payload['Nombre'], (int) $id)) {
                return $this->fail('Ya existe un curso con ese nombre.', 400);
            }
        }

        if (isset($payload['Estado'])) {
            $payload['Estado'] = (int) $payload['Estado'];
        }

        if (isset($payload['PorcentajeAprobacion'])) {
            $payload['PorcentajeAprobacion'] = filter_var(
                $payload['PorcentajeAprobacion'],
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1, 'max_range' => 100]]
            );
            if ($payload['PorcentajeAprobacion'] === false) {
                return $this->fail('PorcentajeAprobacion debe ser un entero entre 1 y 100.', 400);
            }
        }

        if (isset($payload['CantidadPreguntas'])) {
            $payload['CantidadPreguntas'] = filter_var(
                $payload['CantidadPreguntas'],
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1, 'max_range' => 25]]
            );
            if ($payload['CantidadPreguntas'] === false) {
                return $this->fail('CantidadPreguntas debe ser un entero entre 1 y 25.', 400);
            }
        }

        $payload['FechaModificacion'] = date('Y-m-d H:i:s');

        if ($model->update($id, $payload) === false) {
            return $this->fail('No se pudo actualizar el curso', 500);
        }

        return $this->respond([
            'message' => 'Curso actualizado correctamente',
        ], 200);
    }

    #[OA\Post(
        path: "/cursos/{id}/portada",
        tags: ["Cursos"],
        summary: "Subir imagen de portada del curso",
        description: "Multipart con campo `portada`. Máximo 5 MB. JPEG, PNG, WebP o GIF. La ruta guardada en BD es relativa a `public/` (p. ej. `uploads/cursos/3/....webp`). Reemplaza la portada anterior si existía bajo `uploads/`.",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "idCurso",
                schema: new OA\Schema(type: "integer", example: 1)
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    required: ["portada"],
                    properties: [
                        new OA\Property(
                            property: "portada",
                            type: "string",
                            format: "binary",
                            description: "Archivo de imagen"
                        ),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Portada actualizada",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Portada actualizada correctamente"),
                        new OA\Property(property: "ImagenPortada", type: "string", example: "uploads/cursos/1/a1b2c3d4e5f6789012345678abcdef01.jpg"),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Sin archivo, tipo no permitido o supera 5 MB",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "integer", example: 400),
                        new OA\Property(property: "messages", type: "object", nullable: true),
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Curso no encontrado"),
        ]
    )]
    public function uploadPortada($id = null)
    {
        $model = new CursoModel();
        $curso = $model->find($id);

        if (! $curso) {
            return $this->failNotFound('Curso no encontrado.');
        }

        $file = $this->request->getFile('portada');
        if (! $file || ! $file->isValid()) {
            return $this->fail(
                'Envíe una imagen en el campo "portada" (JPEG, PNG, WebP o GIF, máx. 5 MB).',
                400
            );
        }

        try {
            $svc  = \Config\Services::imageUpload();
            $svc->removeStoredPublicImage($curso['ImagenPortada'] ?? null);
            $subdir = 'cursos/' . (int) $id;
            $rel = $svc->saveFromUpload($file, $subdir, ImageUploadService::MAX_CURSO_PORTADA_BYTES);
            $model->update($id, [
                'ImagenPortada'     => $rel,
                'FechaModificacion' => date('Y-m-d H:i:s'),
            ]);
        } catch (RuntimeException $e) {
            return $this->fail($e->getMessage(), 400);
        }

        return $this->respond([
            'message'       => 'Portada actualizada correctamente',
            'ImagenPortada' => $rel,
        ], 200);
    }
}
