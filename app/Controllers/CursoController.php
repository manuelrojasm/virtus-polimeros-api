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
        $cursos = $model->findAll();
        return $this->respond($cursos);
    }

    #[OA\Get(
        path: "/cursos/{id}",
        tags: ["Cursos"],
        summary: "Obtener un curso por ID",
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer", example: 2))
        ],
        responses: [
            new OA\Response(response: 200, description: "Curso encontrado"),
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
        description: "Crea el registro y una carpeta en `writable/cursos/`. No incluya imagen de portada aquí: después use `POST /cursos/{id}/portada` con multipart (campo `portada`, máx. 5 MB).",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                required: ["Nombre"],
                properties: [
                    new OA\Property(property: "Nombre", type: "string", example: "Introducción a Polímeros"),
                    new OA\Property(property: "Descripcion", type: "string", example: "Curso introductorio", nullable: true),
                    new OA\Property(property: "Estado", type: "integer", example: 1, description: "1 = activo, 0 = inactivo", nullable: true),
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
            new OA\Response(response: 400, description: "Nombre duplicado, nombre vacío o envío de ImagenPortada en JSON"),
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
            new OA\Response(response: 400, description: "Sin campos válidos, nombre duplicado o intento de enviar portada como texto"),
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

        $payload = array_intersect_key($data, array_flip(['Nombre', 'Descripcion', 'Estado']));

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
