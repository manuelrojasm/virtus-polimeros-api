<?php
namespace App\Models;

use CodeIgniter\Model;

class OpcionRespuestaModel extends Model
{
    protected $table = 'opcionrespuesta';
    protected $primaryKey = 'idOpcionRespuesta';
    protected $allowedFields = ['idPregunta', 'Respuesta', 'Correcta', 'FechaCreacion', 'Estado'];
}