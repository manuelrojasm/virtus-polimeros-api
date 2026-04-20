<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use OpenApi\Attributes as OA;
use RuntimeException;

/**
 * Endpoint que devuelve preguntas sugeridas por cada PDF (sección) del curso.
 * El front puede mostrar este JSON para que el usuario edite y luego guarde con POST /preguntas.
 */
class PreguntasSugeridasController extends ResourceController
{
    protected $format = 'json';

    #[OA\Get(
        path: "/cursos/{idCurso}/preguntas-sugeridas",
        tags: ["Preguntas sugeridas"],
        summary: "Obtener preguntas sugeridas por cada PDF (sección) del curso",
        description: "Por cada sección (PDF) del curso extrae el texto, llama a OpenAI y devuelve al menos 10 preguntas sugeridas. El JSON puede mostrarse en el front para que el usuario las edite y luego guarde con POST /preguntas asociándolas al curso.",
        parameters: [
            new OA\Parameter(name: "idCurso", in: "path", required: true, schema: new OA\Schema(type: "integer"), description: "ID del curso"),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Curso con secciones y preguntas sugeridas por cada PDF",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "idCurso", type: "integer", example: 1),
                        new OA\Property(
                            property: "secciones",
                            type: "array",
                            items: new OA\Items(
                                type: "object",
                                properties: [
                                    new OA\Property(property: "idSeccionCurso", type: "integer"),
                                    new OA\Property(property: "Nombre", type: "string"),
                                    new OA\Property(property: "rutaArchivo", type: "string"),
                                    new OA\Property(property: "error", type: "string", nullable: true, description: "Mensaje si falló extracción o OpenAI"),
                                    new OA\Property(
                                        property: "preguntas",
                                        type: "array",
                                        description: "Preguntas listas para enviar a POST /preguntas (el front puede añadir idCurso)",
                                        items: new OA\Items(
                                            type: "object",
                                            properties: [
                                                new OA\Property(property: "pregunta", type: "string"),
                                                new OA\Property(property: "descripcion", type: "string"),
                                                new OA\Property(property: "tipo", type: "string", example: "única"),
                                                new OA\Property(
                                                    property: "opciones",
                                                    type: "array",
                                                    items: new OA\Items(
                                                        type: "object",
                                                        properties: [
                                                            new OA\Property(property: "respuesta", type: "string"),
                                                            new OA\Property(property: "correcta", type: "boolean"),
                                                        ]
                                                    )
                                                ),
                                            ]
                                        )
                                    ),
                                ]
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 400, description: "ID de curso inválido"),
            new OA\Response(response: 401, description: "No autorizado"),
            new OA\Response(response: 403, description: "Solo administradores"),
            new OA\Response(response: 500, description: "OPENAI_API_KEY no configurada o error del servicio"),
        ]
    )]
    public function index($idCurso = null)
    {
        $idCurso = (int) $idCurso;
        if ($idCurso <= 0) {
            return $this->fail('ID de curso inválido.', 400);
        }

        try {
            $service = \Config\Services::preguntasSugeridas();
            $result  = $service->getPreguntasSugeridasPorCurso($idCurso);
            return $this->respond($result);
        } catch (RuntimeException $e) {
            $message = $e->getMessage();
            if (strpos($message, 'OPENAI_API_KEY') !== false) {
                return $this->fail('Servicio de preguntas sugeridas no configurado. Configure OPENAI_API_KEY.', 500);
            }
            return $this->fail($message, 500);
        } catch (\Throwable $e) {
            return $this->fail('Error al generar preguntas sugeridas: ' . $e->getMessage(), 500);
        }
    }
}
