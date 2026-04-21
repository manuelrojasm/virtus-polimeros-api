<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use OpenApi\Attributes as OA;
use RuntimeException;

class CursoDesarrolloController extends ResourceController
{
    protected $format = 'json';

    #[OA\Post(
        path: "/cursos/{idCurso}/inicio",
        tags: ["Cursos"],
        summary: "Registrar inicio de curso para el estudiante autenticado",
        description: "Toma la fecha actual como `FechaInicio` para la relación curso-estudiante. Si ya existía registro, reinicia el progreso (FechaFinalizacion, UltimaCalificacion y Aprobo).",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "idCurso",
                in: "path",
                required: true,
                description: "ID del curso",
                schema: new OA\Schema(type: "integer", example: 3)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Inicio registrado correctamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Inicio de curso registrado correctamente."),
                        new OA\Property(
                            property: "data",
                            type: "object",
                            properties: [
                                new OA\Property(property: "idCursoDesarrolloEstudiante", type: "integer", example: 1),
                                new OA\Property(property: "idCurso", type: "integer", example: 3),
                                new OA\Property(property: "idUsuario", type: "integer", example: 10),
                                new OA\Property(property: "UltimaCalificacion", type: "number", format: "float", nullable: true, example: null),
                                new OA\Property(property: "FechaInicio", type: "string", format: "date-time"),
                                new OA\Property(property: "FechaFinalizacion", type: "string", format: "date-time", nullable: true, example: null),
                                new OA\Property(property: "Aprobo", type: "integer", example: 0),
                                new OA\Property(property: "VariablesSeguimiento", type: "string", nullable: true, example: null),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 400, description: "ID de curso inválido o datos de curso/usuario no encontrados"),
            new OA\Response(response: 401, description: "No autenticado"),
        ]
    )]
    public function inicio($idCurso = null)
    {
        $idUsuario = (int) ($this->request->authUser['id'] ?? 0);
        if ($idUsuario <= 0) {
            return $this->failUnauthorized('No autenticado.');
        }

        if (! is_numeric($idCurso) || (int) $idCurso <= 0) {
            return $this->fail('ID de curso inválido.', 400);
        }

        try {
            $resultado = \Config\Services::cursoDesarrollo()->inicioCurso((int) $idCurso, $idUsuario);
        } catch (RuntimeException $e) {
            return $this->fail($e->getMessage(), 400);
        }

        return $this->respond([
            'message' => 'Inicio de curso registrado correctamente.',
            'data'    => $resultado,
        ], 200);
    }

    #[OA\Post(
        path: "/cursos/{idCurso}/finalizacion",
        tags: ["Cursos"],
        summary: "Registrar finalización de curso para el estudiante autenticado",
        description: "Guarda `UltimaCalificacion`, `Aprobo`, `VariablesSeguimiento` (opcional) y toma la fecha actual como `FechaFinalizacion`.",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "idCurso",
                in: "path",
                required: true,
                description: "ID del curso",
                schema: new OA\Schema(type: "integer", example: 3)
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["UltimaCalificacion", "Aprobo"],
                properties: [
                    new OA\Property(property: "UltimaCalificacion", type: "number", format: "float", example: 85.5, description: "Valor permitido de 0 a 100"),
                    new OA\Property(property: "Aprobo", type: "integer", example: 1, description: "0 = no aprobó, 1 = aprobó"),
                    new OA\Property(property: "VariablesSeguimiento", type: "object", nullable: true, description: "Objeto con datos de seguimiento del estudiante"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Finalización registrada correctamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Finalización de curso registrada correctamente."),
                        new OA\Property(
                            property: "data",
                            type: "object",
                            properties: [
                                new OA\Property(property: "idCursoDesarrolloEstudiante", type: "integer", example: 1),
                                new OA\Property(property: "idCurso", type: "integer", example: 3),
                                new OA\Property(property: "idUsuario", type: "integer", example: 10),
                                new OA\Property(property: "UltimaCalificacion", type: "number", format: "float", example: 85.5),
                                new OA\Property(property: "FechaInicio", type: "string", format: "date-time"),
                                new OA\Property(property: "FechaFinalizacion", type: "string", format: "date-time"),
                                new OA\Property(property: "Aprobo", type: "integer", example: 1),
                                new OA\Property(property: "VariablesSeguimiento", type: "string", nullable: true, example: "{\"intentos\":2,\"tiempoMin\":35}"),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 400, description: "ID inválido o validaciones fallidas (UltimaCalificacion 0-100, Aprobo 0/1)"),
            new OA\Response(response: 401, description: "No autenticado"),
        ]
    )]
    public function finalizacion($idCurso = null)
    {
        $idUsuario = (int) ($this->request->authUser['id'] ?? 0);
        if ($idUsuario <= 0) {
            return $this->failUnauthorized('No autenticado.');
        }

        if (! is_numeric($idCurso) || (int) $idCurso <= 0) {
            return $this->fail('ID de curso inválido.', 400);
        }

        $data = $this->request->getJSON(true);
        if (! $data) {
            return $this->fail('Datos inválidos', 400);
        }

        try {
            $resultado = \Config\Services::cursoDesarrollo()->finalizacionCurso((int) $idCurso, $idUsuario, $data);
        } catch (RuntimeException $e) {
            return $this->fail($e->getMessage(), 400);
        }

        return $this->respond([
            'message' => 'Finalización de curso registrada correctamente.',
            'data'    => $resultado,
        ], 200);
    }
}
