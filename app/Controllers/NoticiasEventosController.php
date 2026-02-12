<?php

namespace App\Controllers;

use App\Models\NoticiasEventosModel;
use CodeIgniter\RESTful\ResourceController;
use OpenApi\Attributes as OA;

class NoticiasEventosController extends ResourceController
{
    protected $modelName = 'App\Models\NoticiasEventosModel';
    protected $format    = 'json';

    #[OA\Get(
        path: "/noticias-eventos",
        tags: ["Noticias y Eventos"],
        summary: "Listar todas las noticias y eventos",
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de noticias y eventos",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        type: "object",
                        properties: [
                            new OA\Property(property: "Id", type: "integer", example: 1),
                            new OA\Property(property: "Titulo", type: "string"),
                            new OA\Property(property: "Resumen", type: "string"),
                            new OA\Property(property: "Tipo", type: "string", example: "Noticia"),
                            new OA\Property(property: "FechaPublicacion", type: "string", format: "date-time"),
                            new OA\Property(property: "Estado", type: "string", example: "Publicado"),
                        ]
                    )
                )
            )
        ]
    )]
    public function index()
    {
        $registros = $this->model->findAll();
        return $this->respond($registros, 200);
    }

    #[OA\Get(
        path: "/noticias-eventos/{id}",
        tags: ["Noticias y Eventos"],
        summary: "Obtener una noticia o evento por ID",
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer", example: 1))
        ],
        responses: [
            new OA\Response(response: 200, description: "Noticia/evento encontrado"),
            new OA\Response(response: 404, description: "Noticia/evento no encontrado")
        ]
    )]
    public function show($id = null)
    {
        $registro = $this->model->find($id);
        if (!$registro) {
            return $this->failNotFound('Noticia/evento no encontrado.');
        }
        return $this->respond($registro, 200);
    }

    #[OA\Post(
        path: "/noticias-eventos",
        tags: ["Noticias y Eventos"],
        summary: "Crear noticia o evento (solo admin)",
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["Titulo", "Contenido", "Tipo", "FechaPublicacion"],
                properties: [
                    new OA\Property(property: "Titulo", type: "string", example: "Título de la noticia"),
                    new OA\Property(property: "Resumen", type: "string", nullable: true),
                    new OA\Property(property: "Contenido", type: "string", example: "Contenido completo..."),
                    new OA\Property(property: "ImagenUrl", type: "string", nullable: true),
                    new OA\Property(property: "Tipo", type: "string", enum: ["Noticia", "Evento", "Anuncio"]),
                    new OA\Property(property: "CategoriaId", type: "integer", nullable: true),
                    new OA\Property(property: "FechaEventoInicio", type: "string", format: "date-time", nullable: true),
                    new OA\Property(property: "FechaEventoFin", type: "string", format: "date-time", nullable: true),
                    new OA\Property(property: "Ubicacion", type: "string", nullable: true),
                    new OA\Property(property: "Modalidad", type: "string", enum: ["Presencial", "Virtual", "Mixto"], nullable: true),
                    new OA\Property(property: "EnlaceEvento", type: "string", nullable: true),
                    new OA\Property(property: "FechaPublicacion", type: "string", format: "date-time", example: "2025-02-12 10:00:00"),
                    new OA\Property(property: "FechaExpiracion", type: "string", format: "date-time", nullable: true),
                    new OA\Property(property: "EsDestacado", type: "integer", example: 0, description: "0 o 1", nullable: true),
                    new OA\Property(property: "EsPublico", type: "integer", example: 1, description: "0 o 1", nullable: true),
                    new OA\Property(property: "Estado", type: "string", enum: ["Borrado", "Publicado", "Archivado"], nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Noticia/evento creado correctamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "Id", type: "integer", example: 1),
                        new OA\Property(property: "message", type: "string", example: "Noticia/evento creado correctamente"),
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Datos inválidos o faltan campos requeridos"),
            new OA\Response(response: 401, description: "No autorizado"),
            new OA\Response(response: 403, description: "Se requiere rol de administrador"),
        ]
    )]
    public function create()
    {
        $data = $this->request->getJSON(true);
        if (!$data) {
            return $this->fail('Datos inválidos', 400);
        }

        $required = ['Titulo', 'Contenido', 'Tipo', 'FechaPublicacion'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return $this->fail("El campo {$field} es obligatorio", 400);
            }
        }

        $validTipos = ['Noticia', 'Evento', 'Anuncio'];
        if (!in_array($data['Tipo'], $validTipos)) {
            return $this->fail("Tipo debe ser uno de: " . implode(', ', $validTipos), 400);
        }

        $validModalidades = ['Presencial', 'Virtual', 'Mixto'];
        if (!empty($data['Modalidad']) && !in_array($data['Modalidad'], $validModalidades)) {
            return $this->fail("Modalidad debe ser uno de: " . implode(', ', $validModalidades), 400);
        }

        $validEstados = ['Borrado', 'Publicado', 'Archivado'];
        if (!empty($data['Estado']) && !in_array($data['Estado'], $validEstados)) {
            return $this->fail("Estado debe ser uno de: " . implode(', ', $validEstados), 400);
        }

        $now = date('Y-m-d H:i:s');
        $payload = [
            'Titulo'             => $data['Titulo'],
            'Resumen'            => $data['Resumen'] ?? null,
            'Contenido'          => $data['Contenido'],
            'ImagenUrl'          => $data['ImagenUrl'] ?? null,
            'Tipo'               => $data['Tipo'],
            'CategoriaId'        => isset($data['CategoriaId']) ? (int) $data['CategoriaId'] : null,
            'FechaEventoInicio'  => $data['FechaEventoInicio'] ?? null,
            'FechaEventoFin'     => $data['FechaEventoFin'] ?? null,
            'Ubicacion'          => $data['Ubicacion'] ?? null,
            'Modalidad'          => $data['Modalidad'] ?? null,
            'EnlaceEvento'       => $data['EnlaceEvento'] ?? null,
            'FechaPublicacion'   => $data['FechaPublicacion'],
            'FechaExpiracion'    => $data['FechaExpiracion'] ?? null,
            'EsDestacado'        => isset($data['EsDestacado']) ? (int) $data['EsDestacado'] : 0,
            'EsPublico'          => isset($data['EsPublico']) ? (int) $data['EsPublico'] : 1,
            'Estado'             => $data['Estado'] ?? 'Borrado',
            'FechaCreacion'      => $now,
            'FechaModificacion'  => null,
        ];

        $id = $this->model->insert($payload);
        if ($id === false) {
            return $this->fail('No se pudo crear el registro', 500);
        }

        return $this->respondCreated([
            'Id'      => (int) $id,
            'message' => 'Noticia/evento creado correctamente',
        ]);
    }
}
