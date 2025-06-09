<?php

namespace App\Controllers\test;

use App\Models\ContactoModel;
use CodeIgniter\RESTful\ResourceController;
use OpenApi\Attributes as OA;


/**
 * @OA\Tag(
 *     name="Contacto",
 *     description="API para gestión de contactos"
 * )
 */


#[OA\Info(title: "Mi API", version: "1.0.0", description: "Documentación con atributos PHP 8+")]
class ContactoController extends ResourceController
{
    protected $modelName = 'App\Models\ContactoModel';
    protected $format    = 'json';

    // Manejo de CORS en todas las respuestas
    private function setCorsHeaders()
    {
        $this->response->setHeader('Access-Control-Allow-Origin', '*');
        $this->response->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, DELETE');
        $this->response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    }

    // Manejo de solicitudes OPTIONS para CORS
    public function options()
    {
        $this->setCorsHeaders();
        return $this->response->setStatusCode(200);
    }

    #[OA\Post(
        path: "/contacto",
        tags: ["Contacto"],
        summary: "Crear un nuevo contacto",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["Nombre", "Correo", "Mensaje"],
                properties: [
                    new OA\Property(property: "Nombre", type: "string", maxLength: 100, example: "Juan Pérez"),
                    new OA\Property(property: "Correo", type: "string", format: "email", example: "juan@example.com"),
                    new OA\Property(property: "Mensaje", type: "string", maxLength: 250, example: "Mensaje de contacto"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Contacto creado correctamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "Nombre", type: "string"),
                        new OA\Property(property: "Correo", type: "string"),
                        new OA\Property(property: "Mensaje", type: "string"),
                        new OA\Property(property: "FechaCreación", type: "string", format: "date-time"),
                        new OA\Property(property: "Estado", type: "integer", example: 1),
                    ]
                )
            ),
            new OA\Response(response: 422, description: "Errores de validación"),
            new OA\Response(response: 500, description: "Error interno del servidor"),
        ]
    )]
    // Crear un contacto
    public function create()
    {
        $this->setCorsHeaders(); // Agregar cabeceras CORS

        $validation = \Config\Services::validation();

        $validation->setRules([
            'Nombre'   => 'required|min_length[3]|max_length[100]',
            'Correo'   => 'required|valid_email|max_length[100]',
            'Mensaje'  => 'required|max_length[250]',
        ]);

        if (!$this->validate($validation->getRules())) {
            return $this->failValidationErrors($validation->getErrors());
        }

        $data = $this->request->getJSON(true);
        $data['FechaCreación'] = date('Y-m-d H:i:s');
        $data['Estado'] = 1;

        $contactoModel = new ContactoModel();
        if ($contactoModel->save($data)) {
            return $this->respondCreated($data);
        } else {
            return $this->failServerError('No se pudo guardar el contacto');
        }
    }

}
