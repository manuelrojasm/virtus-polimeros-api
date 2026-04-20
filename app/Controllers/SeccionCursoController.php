<?php

namespace App\Controllers;

use App\Services\SeccionCursoService;
use CodeIgniter\HTTP\Exceptions\HTTPException;
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

    #[OA\Put(
        path: "/cursos/{idCurso}/secciones/{idSeccionCurso}",
        tags: ["Secciones de Curso"],
        summary: "Editar una sección de curso (permite reemplazar PDF)",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "idCurso", in: "path", required: true, schema: new OA\Schema(type: "integer", example: 1)),
            new OA\Parameter(name: "idSeccionCurso", in: "path", required: true, schema: new OA\Schema(type: "integer", example: 10)),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "Nombre", type: "string", example: "Introducción actualizada", nullable: true),
                        new OA\Property(property: "Orden", type: "integer", example: 1, nullable: true),
                        new OA\Property(property: "Estado", type: "integer", example: 1, nullable: true),
                        new OA\Property(property: "archivo", type: "string", format: "binary", nullable: true, description: "PDF opcional para reemplazar el actual"),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Sección actualizada"),
            new OA\Response(response: 400, description: "Datos inválidos"),
            new OA\Response(response: 404, description: "Sección no encontrada"),
        ]
    )]
    public function update($idCurso = null, $idSeccionCurso = null)
    {
        $idCurso = (int) $idCurso;
        $idSeccionCurso = (int) $idSeccionCurso;

        if ($idCurso <= 0 || $idSeccionCurso <= 0) {
            return $this->fail('ID de curso o sección inválido.', 400);
        }

        $data = [];
        $contentType = strtolower((string) $this->request->getHeaderLine('Content-Type'));

        // Solo intentamos parsear JSON cuando el Content-Type es JSON para evitar
        // excepciones en requests multipart/form-data (reemplazo de PDF).
        if (str_contains($contentType, 'application/json')) {
            try {
                $json = $this->request->getJSON(true);
                if (is_array($json)) {
                    $data = $json;
                }
            } catch (HTTPException $e) {
                return $this->fail('JSON inválido.', 400);
            }
        } else {
            // Prioridad 1: campos de form-data (POST o method override).
            $nombre = $this->request->getPost('Nombre');
            $orden = $this->request->getPost('Orden');
            $estado = $this->request->getPost('Estado');
            if ($nombre !== null || $orden !== null || $estado !== null) {
                $data = [
                    'Nombre' => $nombre,
                    'Orden'  => $orden,
                    'Estado' => $estado,
                ];
            } else {
                // Prioridad 2: body urlencoded/raw en PUT/PATCH.
                $raw = $this->request->getRawInput();
                if (is_array($raw)) {
                    $data = $raw;
                }
            }
        }

        $file = $this->request->getFile('archivo');
        if ($file && !$file->isValid() && $file->getError() !== UPLOAD_ERR_NO_FILE) {
            return $this->fail($file->getErrorString() ?: 'Archivo inválido.', 400);
        }

        try {
            $seccionService = \Config\Services::seccionCurso();
            $resultado = $seccionService->actualizarSeccion($idCurso, $idSeccionCurso, $data, $file);
        } catch (RuntimeException $e) {
            $code = str_contains(strtolower($e->getMessage()), 'no existe')
                || str_contains(strtolower($e->getMessage()), 'no encontrada')
                ? 404
                : 400;
            return $this->fail($e->getMessage(), $code);
        }

        return $this->respond([
            'idSeccionCurso' => $resultado['idSeccionCurso'],
            'rutaArchivo'    => $resultado['rutaArchivo'],
            'message'        => 'Sección actualizada correctamente',
        ]);
    }

    #[OA\Put(
        path: "/cursos/{idCurso}/secciones/{idSeccionCurso}/eliminar",
        tags: ["Secciones de Curso"],
        summary: "Eliminar sección (borrado lógico: Estado = 0)",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "idCurso", in: "path", required: true, schema: new OA\Schema(type: "integer", example: 1)),
            new OA\Parameter(name: "idSeccionCurso", in: "path", required: true, schema: new OA\Schema(type: "integer", example: 10)),
        ],
        responses: [
            new OA\Response(response: 200, description: "Sección inactivada"),
            new OA\Response(response: 400, description: "IDs inválidos"),
            new OA\Response(response: 404, description: "Sección no encontrada para el curso"),
        ]
    )]
    public function delete($idCurso = null, $idSeccionCurso = null)
    {
        $idCurso = (int) $idCurso;
        $idSeccionCurso = (int) $idSeccionCurso;

        if ($idCurso <= 0 || $idSeccionCurso <= 0) {
            return $this->fail('ID de curso o sección inválido.', 400);
        }

        try {
            $seccionService = \Config\Services::seccionCurso();
            $resultado = $seccionService->inactivarSeccion($idCurso, $idSeccionCurso);
        } catch (RuntimeException $e) {
            return $this->fail($e->getMessage(), 404);
        }

        return $this->respond([
            'idSeccionCurso' => $resultado['idSeccionCurso'],
            'Estado'         => 0,
            'message'        => 'Sección inactivada correctamente',
        ]);
    }

    #[OA\Get(
        path: "/cursos/{idCurso}/secciones/descargar",
        tags: ["Secciones de Curso"],
        summary: "Descargar archivo PDF de una sección por curso y archivoUrl",
        parameters: [
            new OA\Parameter(name: "idCurso", in: "path", required: true, schema: new OA\Schema(type: "integer", example: 1)),
            new OA\Parameter(
                name: "archivoUrl",
                in: "query",
                required: true,
                description: "Nombre o ruta del archivo (se usa solo el nombre final).",
                schema: new OA\Schema(type: "string", example: "mi_archivo_123.pdf")
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: "Descarga iniciada (application/pdf)"),
            new OA\Response(response: 400, description: "Parámetros inválidos"),
            new OA\Response(response: 404, description: "Curso/sección/archivo no encontrado"),
        ]
    )]
    public function download($idCurso = null)
    {
        $idCurso = (int) $idCurso;
        $archivoUrl = (string) ($this->request->getGet('archivoUrl') ?? '');

        if ($idCurso <= 0) {
            return $this->fail('ID de curso inválido.', 400);
        }

        if (trim($archivoUrl) === '') {
            return $this->fail('El parámetro archivoUrl es obligatorio.', 400);
        }

        try {
            $seccionService = \Config\Services::seccionCurso();
            $resuelto = $seccionService->resolverArchivoDescarga($idCurso, $archivoUrl);
        } catch (RuntimeException $e) {
            $code = str_contains(strtolower($e->getMessage()), 'inválido') ? 400 : 404;
            return $this->fail($e->getMessage(), $code);
        }

        return $this->response
            ->download($resuelto['rutaAbsoluta'], null)
            ->setFileName($resuelto['nombreDescarga']);
    }
}
