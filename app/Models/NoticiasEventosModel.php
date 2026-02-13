<?php

namespace App\Models;

use CodeIgniter\Model;

class NoticiasEventosModel extends Model
{
    protected $table            = 'noticias_eventos';
    protected $primaryKey       = 'Id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useTimestamps    = false;
    protected $dateFormat       = 'datetime';
    protected $allowedFields    = [
        'Titulo',
        'Resumen',
        'Contenido',
        'ImagenUrl',
        'Tipo',
        'CategoriaId',
        'FechaEventoInicio',
        'FechaEventoFin',
        'Ubicacion',
        'Modalidad',
        'EnlaceEvento',
        'FechaPublicacion',
        'FechaExpiracion',
        'EsDestacado',
        'EsPublico',
        'Estado',
        'FechaCreacion',
        'FechaModificacion',
    ];
}
