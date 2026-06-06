<?php

namespace App\Models;

use CodeIgniter\Model;

class CursoDesarrolloEstudianteModel extends Model
{
    protected $table            = 'cursodesarrolloestudiante';
    protected $primaryKey       = 'idCursoDesarrolloEstudiante';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useTimestamps    = false;
    protected $allowedFields    = [
        'idCurso',
        'idUsuario',
        'UltimaCalificacion',
        'FechaInicio',
        'FechaFinalizacion',
        'Aprobo',
        'VariablesSeguimiento',
        'FechaCreacion',
        'FechaModificacion',
    ];
}
