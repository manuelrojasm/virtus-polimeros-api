<?php

namespace App\Models;

use CodeIgniter\Model;

class CursoModel extends Model
{
    protected $table            = 'Curso';
    protected $primaryKey       = 'idCurso';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useTimestamps    = false;
    protected $dateFormat       = 'datetime';
    protected $allowedFields    = [
        'Nombre',
        'Descripcion',
        'ImagenPortada',
        'FechaCreacion',
        'FechaModificacion',
        'Estado',
    ];
}
