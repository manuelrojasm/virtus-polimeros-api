<?php
namespace App\Models;

use CodeIgniter\Model;

class PreguntaModel extends Model
{
    protected $table = 'Pregunta';
    protected $primaryKey = 'idPregunta';
    protected $allowedFields = ['Pregunta', 'Descripcion', 'Tipo', 'FechaCreacion', 'FechaActualizacion', 'Estado','RangoMin','RangoMax'];
    public $useTimestamps = false;
}