<?php

namespace App\Controllers;

use App\Models\CursoModel;
use App\Services\CursoService;
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
                            new OA\Property(property: "ImagenPortada", type: "string", nullable: true),
                            new OA\Property(property: "FechaCreacion", type: "string", format: "date-time"),
                            new OA\Property(property: "Estado", type: "integer", example: 1),
                            new OA\Property(property: "RutaCarpeta", type: "string", nullable: true, example: "d:/app/writable/cursos/Introduccion_a_Polimeros"),
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

    #[OA\Post(
        path: "/cursos",
        tags: ["Cursos"],
        summary: "Crear un nuevo curso (crea también una carpeta con el nombre del curso en el servidor)",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                required: ["Nombre"],
                properties: [
                    new OA\Property(property: "Nombre", type: "string", example: "Introducción a Polímeros"),
                    new OA\Property(property: "Descripcion", type: "string", example: "Curso introductorio", nullable: true),
                    new OA\Property(property: "ImagenPortada", type: "string", nullable: true),
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
                        new OA\Property(property: "rutaCarpeta", type: "string", example: "d:/app/writable/cursos/Introduccion_a_Polimeros"),
                        new OA\Property(property: "nombreCarpeta", type: "string", example: "Introduccion_a_Polimeros"),
                        new OA\Property(property: "message", type: "string", example: "Curso creado correctamente"),
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Nombre duplicado o datos inválidos"),
        ]
    )]
    public function create()
    {
        $data = $this->request->getJSON(true);

        if (!$data || empty(trim($data['Nombre'] ?? ''))) {
            return $this->fail('El nombre del curso es obligatorio.', 400);
        }

        try {
            $cursoService = \Config\Services::curso();
            $resultado = $cursoService->crearCurso($data);
        } catch (RuntimeException $e) {
            return $this->fail($e->getMessage(), 400);
        }

        return $this->respondCreated([
            'idCurso'       => $resultado['idCurso'],
            'rutaCarpeta'   => $resultado['rutaCarpeta'],
            'nombreCarpeta' => $resultado['nombreCarpeta'],
            'message'       => 'Curso creado correctamente',
        ]);
    }
}
