<?php
namespace App\Models;

use CodeIgniter\Model;

class PreguntaModel extends Model
{
    protected $table = 'pregunta';
    protected $primaryKey = 'idPregunta';
    protected $allowedFields = ['idCurso', 'Pregunta', 'Descripcion', 'Tipo', 'FechaCreacion', 'FechaActualizacion', 'Estado', 'RangoMin', 'RangoMax'];
    public $useTimestamps = false;
}