<?php

namespace App\Controllers;

use App\Models\ContactoModel;
use CodeIgniter\RESTful\ResourceController;

/**
 * @OA\Tag(
 *     name="Contacto",
 *     description="API para gestión de contactos"
 * )
 */

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

        /**
     * @OA\Post(
     *     path="/contacto",
     *     tags={"Contacto"},
     *     summary="Crear un nuevo contacto",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"Nombre", "Correo", "Mensaje"},
     *             @OA\Property(property="Nombre", type="string", maxLength=100, example="Juan Pérez"),
     *             @OA\Property(property="Correo", type="string", format="email", example="juan@example.com"),
     *             @OA\Property(property="Mensaje", type="string", maxLength=250, example="Mensaje de contacto")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Contacto creado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="Nombre", type="string"),
     *             @OA\Property(property="Correo", type="string"),
     *             @OA\Property(property="Mensaje", type="string"),
     *             @OA\Property(property="FechaCreación", type="string", format="date-time"),
     *             @OA\Property(property="Estado", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Errores de validación"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor"
     *     )
     * )
     */

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

    /**
     * @OA\Get(
     *     path="/contacto",
     *     tags={"Contacto"},
     *     summary="Obtener todos los contactos",
     *     @OA\Response(
     *         response=200,
     *         description="Lista de contactos",
     *         @OA\JsonContent(type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="Nombre", type="string"),
     *                 @OA\Property(property="Correo", type="string"),
     *                 @OA\Property(property="Mensaje", type="string"),
     *                 @OA\Property(property="FechaCreación", type="string", format="date-time"),
     *                 @OA\Property(property="Estado", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No se encontraron contactos"
     *     )
     * )
     */

    // Obtener todos los contactos
    public function index()
    {
        $this->setCorsHeaders();
        $contactoModel = new ContactoModel();
        $contactos = $contactoModel->findAll();
        
        if ($contactos) {
            return $this->respond($contactos);
        } else {
            return $this->failNotFound('No se encontraron contactos');
        }
    }

    /**
     * @OA\Get(
     *     path="/contacto/{id}",
     *     tags={"Contacto"},
     *     summary="Obtener un contacto por ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del contacto",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalle del contacto",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="Nombre", type="string"),
     *             @OA\Property(property="Correo", type="string"),
     *             @OA\Property(property="Mensaje", type="string"),
     *             @OA\Property(property="FechaCreación", type="string", format="date-time"),
     *             @OA\Property(property="Estado", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Contacto no encontrado"
     *     )
     * )
     */

    // Obtener un contacto por ID
    public function show($id = null)
    {
        $this->setCorsHeaders();
        $contactoModel = new ContactoModel();
        $contacto = $contactoModel->find($id);
        
        if ($contacto) {
            return $this->respond($contacto);
        } else {
            return $this->failNotFound('Contacto no encontrado');
        }
    }

        /**
     * @OA\Put(
     *     path="/contacto/{id}",
     *     tags={"Contacto"},
     *     summary="Actualizar un contacto por ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del contacto a actualizar",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"Nombre", "Correo", "Mensaje", "FechaCreación"},
     *             @OA\Property(property="Nombre", type="string", maxLength=100, example="Juan Pérez"),
     *             @OA\Property(property="Correo", type="string", format="email", example="juan@example.com"),
     *             @OA\Property(property="Mensaje", type="string", maxLength=250, example="Mensaje actualizado"),
     *             @OA\Property(property="FechaCreación", type="string", format="date-time", example="2023-01-01T12:00:00Z")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Contacto actualizado",
     *         @OA\JsonContent(
     *             @OA\Property(property="Nombre", type="string"),
     *             @OA\Property(property="Correo", type="string"),
     *             @OA\Property(property="Mensaje", type="string"),
     *             @OA\Property(property="FechaCreación", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Errores de validación"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor"
     *     )
     * )
     */

    // Actualizar un contacto por ID
    public function update($id = null)
    {
        $this->setCorsHeaders();
        $validation = \Config\Services::validation();
        
        $validation->setRules([
            'Nombre'   => 'required|min_length[3]|max_length[100]',
            'Correo'   => 'required|valid_email|max_length[100]',
            'Mensaje'  => 'required|max_length[250]',
            'FechaCreación' => 'required|valid_date',
        ]);
        
        if (!$this->validate($validation->getRules())) {
            return $this->failValidationErrors($validation->getErrors());
        }

        $data = $this->request->getRawInput();
        $data['FechaCreación'] = date('Y-m-d H:i:s');

        $contactoModel = new ContactoModel();
        if ($contactoModel->update($id, $data)) {
            return $this->respondUpdated($data);
        } else {
            return $this->failServerError('No se pudo actualizar el contacto');
        }
    }

     /**
     * @OA\Delete(
     *     path="/contacto/{id}",
     *     tags={"Contacto"},
     *     summary="Eliminar un contacto por ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del contacto a eliminar",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Contacto eliminado"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Contacto no encontrado"
     *     )
     * )
     */

    // Eliminar un contacto por ID
    public function delete($id = null)
    {
        $this->setCorsHeaders();
        $contactoModel = new ContactoModel();
        $contacto = $contactoModel->find($id);
        
        if ($contacto) {
            $contactoModel->delete($id);
            return $this->respondDeleted('Contacto eliminado');
        } else {
            return $this->failNotFound('Contacto no encontrado');
        }
    }
}
