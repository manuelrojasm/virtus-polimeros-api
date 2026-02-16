<?php

namespace App\Controllers;

use App\Services\SeccionCursoService;
use CodeIgniter\RESTful\ResourceController;
use OpenApi\Attributes as OA;
use RuntimeException;

class SeccionCursoController extends ResourceController
{
    protected $format = 'json';

    #[OA\Get(
        path: "/cursos/{idCurso}/secciones",
        tags: ["Secciones de Curso"],
        summary: "Listar secciones de un curso",
        parameters: [
            new OA\Parameter(name: "idCurso", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de secciones",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        type: "object",
                        properties: [
                            new OA\Property(property: "idSeccionCurso", type: "integer"),
                            new OA\Property(property: "idCurso", type: "integer"),
                            new OA\Property(property: "Nombre", type: "string"),
                            new OA\Property(property: "RutaArchivo", type: "string"),
                            new OA\Property(property: "Orden", type: "integer"),
                            new OA\Property(property: "FechaCreacion", type: "string"),
                            new OA\Property(property: "Estado", type: "integer"),
                        ]
                    )
                )
            ),
        ]
    )]
    public function index($idCurso = null)
    {
        $idCurso = (int) $idCurso;
        if ($idCurso <= 0) {
            return $this->fail('ID de curso inválido.', 400);
        }

        $seccionService = \Config\Services::seccionCurso();
        $secciones = $seccionService->listarPorCurso($idCurso);

        return $this->respond($secciones);
    }

    #[OA\Post(
        path: "/cursos/{idCurso}/secciones",
        tags: ["Secciones de Curso"],
        summary: "Crear una sección con archivo PDF",
        parameters: [
            new OA\Parameter(name: "idCurso", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    required: ["Nombre", "archivo"],
                    properties: [
                        new OA\Property(property: "Nombre", type: "string", example: "Introducción"),
                        new OA\Property(property: "Orden", type: "integer", example: 0, nullable: true),
                        new OA\Property(property: "Estado", type: "integer", example: 1, nullable: true),
                        new OA\Property(property: "archivo", type: "string", format: "binary", description: "Archivo PDF"),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Sección creada correctamente",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "idSeccionCurso", type: "integer"),
                        new OA\Property(property: "rutaArchivo", type: "string"),
                        new OA\Property(property: "message", type: "string", example: "Sección creada correctamente"),
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Datos inválidos o archivo no PDF"),
        ]
    )]
    public function create($idCurso = null)
    {
        $idCurso = (int) $idCurso;
        if ($idCurso <= 0) {
            return $this->fail('ID de curso inválido.', 400);
        }

        $nombre = trim($this->request->getPost('Nombre') ?? '');
        $orden = $this->request->getPost('Orden');
        $estado = $this->request->getPost('Estado');

        $data = [
            'Nombre' => $nombre,
            'Orden'  => $orden !== null ? (int) $orden : null,
            'Estado' => $estado !== null ? (int) $estado : null,
        ];

        $file = $this->request->getFile('archivo');

        if (!$file || !$file->isValid()) {
            return $this->fail('Debe subir un archivo PDF válido.', 400);
        }

        try {
            $seccionService = \Config\Services::seccionCurso();
            $resultado = $seccionService->crearSeccion($idCurso, $data, $file);
        } catch (RuntimeException $e) {
            return $this->fail($e->getMessage(), 400);
        }

        return $this->respondCreated([
            'idSeccionCurso' => $resultado['idSeccionCurso'],
            'rutaArchivo'    => $resultado['rutaArchivo'],
            'message'        => 'Sección creada correctamente',
        ]);
    }
}
