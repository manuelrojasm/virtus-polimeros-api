<?php
namespace App\Controllers;

use App\Models\PreguntaModel;
use App\Models\OpcionRespuestaModel;
use CodeIgniter\RESTful\ResourceController;
use OpenApi\Attributes as OA;

class PreguntaController extends ResourceController
{
    protected $format = 'json';

    #[OA\Get(
    path: "/preguntas",
    tags: ["Preguntas"],
    summary: "Obtener todas las preguntas con sus opciones de respuesta",
    parameters: [
        new OA\Parameter(
            name: "idCurso",
            in: "query",
            required: false,
            description: "Filtra preguntas por curso",
            schema: new OA\Schema(type: "integer", example: 3)
        )
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: "Listado de preguntas con opciones",
            content: new OA\JsonContent(
                type: "array",
                items: new OA\Items(
                    type: "object",
                    properties: [
                        new OA\Property(property: "idPregunta", type: "integer", example: 1),
                        new OA\Property(property: "Texto", type: "string", example: "¿Cuál es la capital de Francia?"),
                        new OA\Property(property: "Tipo", type: "string", example: "seleccion_unica"),
                        new OA\Property(
                            property: "Opciones",
                            type: "array",
                            items: new OA\Items(
                                type: "object",
                                properties: [
                                    new OA\Property(property: "idOpcion", type: "integer", example: 5),
                                    new OA\Property(property: "Texto", type: "string", example: "París"),
                                    new OA\Property(property: "EsCorrecta", type: "boolean", example: true),
                                ]
                            )
                        )
                    ]
                )
            )
        )
    ]
)]

    public function index()
    {
        $preguntaModel = new PreguntaModel();
        $opcionModel = new OpcionRespuestaModel();
        $idCurso = $this->request->getGet('idCurso');

        if ($idCurso !== null && $idCurso !== '') {
            if (!ctype_digit((string) $idCurso)) {
                return $this->failValidationErrors('El parámetro idCurso debe ser numérico.');
            }
            $preguntas = $preguntaModel->where('idCurso', (int) $idCurso)->findAll();
        } else {
            $preguntas = $preguntaModel->findAll();
        }

        foreach ($preguntas as &$pregunta) {
            $pregunta['Opciones'] = $opcionModel->where('idPregunta', $pregunta['idPregunta'])->findAll();
        }

        return $this->respond($preguntas);
    }

    #[OA\Post(
    path: "/preguntas",
    tags: ["Preguntas"],
    summary: "Crear una nueva pregunta con opciones de respuesta",
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: "object",
            required: ["idCurso", "pregunta", "tipo"],
            properties: [
                new OA\Property(property: "idCurso", type: "integer", example: 3),
                new OA\Property(property: "pregunta", type: "string", example: "¿Cuál es la capital de Francia?"),
                new OA\Property(property: "descripcion", type: "string", example: "Pregunta de geografía", nullable: true),
                new OA\Property(property: "tipo", type: "string", example: "seleccion_unica"),
                new OA\Property(
                    property: "rango",
                    type: "array",
                    items: new OA\Items(type: "integer"),
                    example: [1, 10],
                    nullable: true
                ),
                new OA\Property(property: "estado", type: "integer", example: 1, nullable: true),
                new OA\Property(
                    property: "opciones",
                    type: "array",
                    items: new OA\Items(
                        type: "object",
                        required: ["respuesta", "correcta"],
                        properties: [
                            new OA\Property(property: "respuesta", type: "string", example: "París"),
                            new OA\Property(property: "correcta", type: "boolean", example: true)
                        ]
                    ),
                    nullable: true
                )
            ]
        )
    ),
    responses: [
        new OA\Response(
            response: 201,
            description: "Pregunta creada exitosamente",
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "idPregunta", type: "integer", example: 123)
                ]
            )
        ),
        new OA\Response(
            response: 400,
            description: "Error de validación"
        )
    ]
)]

public function create()
{
    $data = $this->request->getJSON(true);

    // Validación básica
    if (empty($data['pregunta']) || empty($data['tipo'])) {
        return $this->failValidationErrors('La pregunta y el tipo son obligatorios.');
    }

    $preguntaModel = new PreguntaModel();
    $opcionModel = new OpcionRespuestaModel();

    // Preparar datos base de la pregunta
    $preguntaData = [
        'idCurso' => isset($data['idCurso']) ? (int) $data['idCurso'] : null,
        'Pregunta' => $data['pregunta'],
        'Descripcion' => $data['descripcion'] ?? '',
        'Tipo' => $data['tipo'],
        'FechaCreacion' => date('Y-m-d H:i:s'),
        'Estado' => $data['estado'] ?? 1,
    ];

    if (
        isset($data['tipo']) && $data['tipo'] === 'rango'
        && isset($data['rango']) && is_array($data['rango']) && count($data['rango']) >= 2
    ) {
        $preguntaData['RangoMin'] = (int) $data['rango'][0];
        $preguntaData['RangoMax'] = (int) $data['rango'][1];
    }

    // Insertar la pregunta
    $id = $preguntaModel->insert($preguntaData);

    // Si hay opciones, insertarlas
    if (isset($data['opciones']) && is_array($data['opciones'])) {
        foreach ($data['opciones'] as $opcion) {
            if (!isset($opcion['respuesta'])) continue;

            $opcionModel->insert([
                'idPregunta' => $id,
                'Respuesta' => $opcion['respuesta'],
                'Correcta' => $opcion['correcta'] ? 1 : 0,
                'FechaCreacion' => date('Y-m-d H:i:s'),
                'Estado' => 1
            ]);
        }
    }

    return $this->respondCreated(['idPregunta' => $id]);
}

#[OA\Put(
    path: "/preguntas/update/{id}",
    tags: ["Preguntas"],
    summary: "Actualizar una pregunta y sus opciones de respuesta",
    parameters: [
        new OA\Parameter(
            name: "id",
            in: "path",
            required: true,
            description: "ID de la pregunta a actualizar",
            schema: new OA\Schema(type: "integer", example: 123)
        )
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: "object",
            required: ["idCurso", "pregunta", "tipo", "opciones"],
            properties: [
                new OA\Property(property: "idCurso", type: "integer", example: 3),
                new OA\Property(property: "pregunta", type: "string", example: "¿Cuál es la capital de Alemania?"),
                new OA\Property(property: "descripcion", type: "string", example: "Pregunta sobre Europa", nullable: true),
                new OA\Property(property: "tipo", type: "string", example: "seleccion_unica"),
                new OA\Property(property: "estado", type: "integer", example: 1, nullable: true),
                new OA\Property(
                    property: "rango",
                    type: "array",
                    items: new OA\Items(type: "integer"),
                    example: [1, 5],
                    nullable: true
                ),
                new OA\Property(
                    property: "opciones",
                    type: "array",
                    items: new OA\Items(
                        type: "object",
                        required: ["respuesta", "correcta"],
                        properties: [
                            new OA\Property(property: "respuesta", type: "string", example: "Berlín"),
                            new OA\Property(property: "correcta", type: "boolean", example: true)
                        ]
                    )
                )
            ]
        )
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: "Pregunta actualizada correctamente",
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "success", type: "boolean", example: true),
                    new OA\Property(property: "message", type: "string", example: "Pregunta actualizada correctamente")
                ]
            )
        ),
        new OA\Response(
            response: 400,
            description: "Datos inválidos o error en la actualización"
        )
    ]
)]

 public function update($id = null)
{
    $data = $this->request->getJSON(true); // true = array

    if (!$data) {
        return $this->fail('Datos inválidos');
    }

    if (!isset($data['pregunta']) || !isset($data['tipo']) || !isset($data['opciones'])) {
        return $this->fail('Faltan campos requeridos');
    }

    $preguntaModel = new PreguntaModel();
    $opcionModel = new OpcionRespuestaModel();

    // Actualizar pregunta
    $datosPregunta = [
        'idCurso' => isset($data['idCurso']) ? (int) $data['idCurso'] : null,
        'Pregunta' => $data['pregunta'],
        'Descripcion' => $data['descripcion'] ?? '',
        'Tipo' => $data['tipo'],
        'Estado' => $data['estado'] ?? 1,
        
    ];

    // Si llega rango en el payload, lo actualizamos sin depender del literal exacto de "tipo".
    if (isset($data['rango']) && is_array($data['rango']) && count($data['rango']) >= 2) {
        $datosPregunta['RangoMin'] = (int) $data['rango'][0];
        $datosPregunta['RangoMax'] = (int) $data['rango'][1];
    } else {
        // Si no llega rango, limpiamos valores previos para evitar datos inconsistentes.
        $datosPregunta['RangoMin'] = null;
        $datosPregunta['RangoMax'] = null;
    }

    if (!$preguntaModel->update($id, $datosPregunta)) {
        return $this->fail('Error al actualizar la pregunta');
    }

    // Eliminar opciones anteriores
    $opcionModel->where('idPregunta', $id)->delete();

    // Insertar nuevas opciones
    foreach ($data['opciones'] as $opcion) {
        $opcionModel->insert([
            'idPregunta' => $id,
            'Respuesta' => $opcion['respuesta'],
            'Correcta' => $opcion['correcta'] ? 1 : 0,
        ]);
    }

    return $this->respond([
        'success' => true,
        'message' => 'Pregunta actualizada correctamente'
    ]);
}

#[OA\Delete(
    path: "/preguntas/{id}",
    tags: ["Preguntas"],
    summary: "Activar o desactivar una pregunta (eliminación lógica)",
    parameters: [
        new OA\Parameter(
            name: "id",
            in: "path",
            required: true,
            description: "ID de la pregunta",
            schema: new OA\Schema(type: "integer", example: 123)
        )
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: "Estado de la pregunta actualizado",
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "idPregunta", type: "integer", example: 123),
                    new OA\Property(property: "nuevoEstado", type: "integer", example: 0)
                ]
            )
        ),
        new OA\Response(
            response: 404,
            description: "Pregunta no encontrada"
        )
    ]
)]

public function delete($id = null)
{
    $preguntaModel = new PreguntaModel();

    // Obtener el estado actual
    $pregunta = $preguntaModel->find($id);
    if (!$pregunta) {
        return $this->failNotFound("Pregunta no encontrada");
    }

    $nuevoEstado = $pregunta['Estado'] == 1 ? 0 : 1;

    $preguntaModel->update($id, ['Estado' => $nuevoEstado]);

    return $this->respond([
        'idPregunta' => $id,
        'nuevoEstado' => $nuevoEstado
    ]);
}
}
