<?php

namespace App\Models;

use CodeIgniter\Model;

class EvidenciasModel extends Model
{
    protected $table = 'evidencias';
    protected $primaryKey = 'idevidencias';
    protected $allowedFields = [
        'Seccion',
        'Titular',
        'SubTitulo',
        'Cuerpo',
        'Fuente',
        'Imagen',
        'Fecha',
        'Estado',
        'FechaCreacion',
        'FechaActualizacion'
    ];
    protected $useTimestamps = false; 
}