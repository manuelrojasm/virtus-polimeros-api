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
