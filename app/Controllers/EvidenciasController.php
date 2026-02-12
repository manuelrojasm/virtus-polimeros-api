<?php

namespace App\Controllers;

use App\Models\EvidenciasModel;
use CodeIgniter\RESTful\ResourceController;
use OpenApi\Attributes as OA;

class EvidenciasController extends ResourceController
{
    protected $modelName = 'App\Models\EvidenciasModel';
    protected $format = 'json';

    #[OA\Get(
        path: "/evidencias",
        tags: ["Evidencias"],
        summary: "Listar todas las evidencias",
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de evidencias",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        type: "object",
                        properties: [
                            new OA\Property(property: "idEvidencia", type: "integer", example: 1),
                            new OA\Property(property: "Titulo", type: "string", example: "Título de la evidencia"),
                            new OA\Property(property: "Descripcion", type: "string"),
                            new OA\Property(property: "Estado", type: "integer", example: 1),
                        ]
                    )
                )
            )
        ]
    )]
    public function index()
    {
        $evidencias = $this->model->findAll();
        return $this->respond($evidencias, 200);
    }

    #[OA\Post(
        path: "/evidencias",
        tags: ["Evidencias"],
        summary: "Crear una nueva evidencia",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["Seccion", "Titular", "Cuerpo"],
                properties: [
                    new OA\Property(property: "Seccion", type: "string", example: "Contaminación"),
                    new OA\Property(property: "Titular", type: "string", example: "Título de la evidencia"),
                    new OA\Property(property: "SubTitulo", type: "string", example: "Subtítulo opcional", nullable: true),
                    new OA\Property(property: "Cuerpo", type: "string", example: "Contenido o descripción de la evidencia"),
                    new OA\Property(property: "Fuente", type: "string", example: "https://ejemplo.com", nullable: true),
                    new OA\Property(property: "Imagen", type: "string", example: "ruta/imagen.jpg", nullable: true),
                    new OA\Property(property: "Fecha", type: "string", format: "date", example: "2025-02-12", nullable: true),
                    new OA\Property(property: "Estado", type: "integer", example: 1, description: "1 = activo, 0 = inactivo", nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Evidencia creada correctamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "idevidencias", type: "integer", example: 1),
                        new OA\Property(property: "message", type: "string", example: "Evidencia creada correctamente"),
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Datos inválidos o faltan campos requeridos"),
        ]
    )]
    public function create()
    {
        $data = $this->request->getJSON(true);
        if (!$data) {
            return $this->fail('Datos inválidos', 400);
        }

        $required = ['Seccion', 'Titular', 'Cuerpo'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return $this->fail("El campo {$field} es obligatorio", 400);
            }
        }

        $now = date('Y-m-d H:i:s');
        $payload = [
            'Seccion'          => $data['Seccion'],
            'Titular'           => $data['Titular'],
            'SubTitulo'         => $data['SubTitulo'] ?? null,
            'Cuerpo'            => $data['Cuerpo'],
            'Fuente'            => $data['Fuente'] ?? null,
            'Imagen'            => $data['Imagen'] ?? null,
            'Fecha'             => $data['Fecha'] ?? date('Y-m-d'),
            'Estado'            => isset($data['Estado']) ? (int) $data['Estado'] : 1,
            'FechaCreacion'     => $now,
            'FechaActualizacion'=> $now,
        ];

        $id = $this->model->insert($payload);
        if ($id === false) {
            return $this->fail('No se pudo crear la evidencia', 500);
        }

        return $this->respondCreated([
            'idevidencias' => $id,
            'message'      => 'Evidencia creada correctamente',
        ]);
    }

    #[OA\Get(
        path: "/evidencias/{id}",
        tags: ["Evidencias"],
        summary: "Obtener una evidencia por ID",
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer", example: 1))
        ],
        responses: [
            new OA\Response(response: 200, description: "Evidencia encontrada"),
            new OA\Response(response: 404, description: "Evidencia no encontrada")
        ]
    )]
    public function show($id = null)
    {
        $evidencia = $this->model->find($id);
        if (!$evidencia) {
            return $this->failNotFound('Noticias no encontradas.');
        }
        return $this->respond($evidencia, 200);
    }

    #[OA\Put(
        path: "/evidencias/{id}",
        tags: ["Evidencias"],
        summary: "Actualizar una evidencia",
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "ID de la evidencia", schema: new OA\Schema(type: "integer", example: 1))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "Seccion", type: "string", example: "Contaminación", nullable: true),
                    new OA\Property(property: "Titular", type: "string", example: "Título actualizado", nullable: true),
                    new OA\Property(property: "SubTitulo", type: "string", nullable: true),
                    new OA\Property(property: "Cuerpo", type: "string", nullable: true),
                    new OA\Property(property: "Fuente", type: "string", nullable: true),
                    new OA\Property(property: "Imagen", type: "string", nullable: true),
                    new OA\Property(property: "Fecha", type: "string", format: "date", nullable: true),
                    new OA\Property(property: "Estado", type: "integer", example: 1, description: "1 = activo, 0 = inactivo", nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Evidencia actualizada correctamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Evidencia actualizada correctamente"),
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Evidencia no encontrada"),
            new OA\Response(response: 400, description: "Datos inválidos"),
        ]
    )]
    public function update($id = null)
    {
        $evidencia = $this->model->find($id);
        if (!$evidencia) {
            return $this->failNotFound('Evidencia no encontrada.');
        }

        $data = $this->request->getJSON(true);
        if (!$data) {
            return $this->fail('Datos inválidos', 400);
        }

        $allowed = ['Seccion', 'Titular', 'SubTitulo', 'Cuerpo', 'Fuente', 'Imagen', 'Fecha', 'Estado'];
        $payload = array_intersect_key($data, array_flip($allowed));
        if (empty($payload)) {
            return $this->fail('No hay campos válidos para actualizar', 400);
        }

        $payload['FechaActualizacion'] = date('Y-m-d H:i:s');

        if ($this->model->update($id, $payload) === false) {
            return $this->fail('No se pudo actualizar la evidencia', 500);
        }

        return $this->respond([
            'message' => 'Evidencia actualizada correctamente',
        ]);
    }

    #[OA\Get(
        path: "/evidencias/activas",
        tags: ["Evidencias"],
        summary: "Listar evidencias activas",
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de evidencias con Estado = 1",
                content: new OA\JsonContent(type: "array", items: new OA\Items(type: "object"))
            )
        ]
    )]
    public function activas()
    {
        $evidencias = $this->model->where('Estado', 1)->findAll();
        return $this->respond($evidencias, 200);
    }
}
