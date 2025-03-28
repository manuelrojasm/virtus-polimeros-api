<?php

namespace App\Controllers;

use App\Models\ContactoModel;
use CodeIgniter\RESTful\ResourceController;

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
