<?php

namespace App\Controllers;

use App\Models\EvidenciasModel;
use CodeIgniter\RESTful\ResourceController;

class EvidenciasController extends ResourceController
{
    protected $modelName = 'App\Models\EvidenciasModel';
    protected $format = 'json';

    public function index()
    {
        $evidencias = $this->model->findAll();
        return $this->respond($evidencias, 200);
    }

    public function show($id = null)
    {
        $evidencia = $this->model->find($id);
        if (!$evidencia) {
            return $this->failNotFound('Noticias no encontradas.');
        }
        return $this->respond($evidencia, 200);
    }

    public function activas()
    {
        $evidencias = $this->model->where('Estado', 1)->findAll();
        return $this->respond($evidencias, 200);
    }
}
