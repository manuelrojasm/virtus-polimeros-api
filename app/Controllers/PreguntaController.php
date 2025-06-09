<?php
namespace App\Controllers;

use App\Models\PreguntaModel;
use App\Models\OpcionRespuestaModel;
use CodeIgniter\RESTful\ResourceController;

class PreguntaController extends ResourceController
{
    protected $format = 'json';

    public function index()
    {
        $preguntaModel = new PreguntaModel();
        $opcionModel = new OpcionRespuestaModel();

        $preguntas = $preguntaModel->findAll();

        foreach ($preguntas as &$pregunta) {
            $pregunta['Opciones'] = $opcionModel->where('idPregunta', $pregunta['idPregunta'])->findAll();
        }

        return $this->respond($preguntas);
    }

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
        'Pregunta' => $data['pregunta'],
        'Descripcion' => $data['descripcion'] ?? '',
        'Tipo' => $data['tipo'],
        'FechaCreacion' => date('Y-m-d H:i:s'),
        'Estado' => $data['estado'] ?? 1,
    ];

    // Si es de tipo 'rango', agregar los valores mínimo y máximo
  
        $preguntaData['RangoMin'] = $data['rango'][0];
        $preguntaData['RangoMax'] = $data['rango'][1];

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
        'Pregunta' => $data['pregunta'],
        'Descripcion' => $data['descripcion'] ?? '',
        'Tipo' => $data['tipo'],
        'Estado' => $data['estado'] ?? 1,
        
    ];

    $datosPregunta['RangoMin'] = $data['rango'][0];
    $datosPregunta['RangoMax'] = $data['rango'][1];

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
