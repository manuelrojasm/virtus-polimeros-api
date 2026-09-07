<?php

namespace App\Models;

use CodeIgniter\Model;

class SeccionCursoModel extends Model
{
    protected $table            = 'seccioncurso';
    protected $primaryKey       = 'idSeccionCurso';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useTimestamps    = false;
    protected $dateFormat       = 'datetime';
    protected $allowedFields    = [
        'idCurso',
        'Nombre',
        'RutaArchivo',
        'Resumen',
        'Orden',
        'FechaCreacion',
        'FechaModificacion',
        'Estado',
    ];

    /**
     * Obtiene las secciones de un curso ordenadas por Orden.
     */
    public function seccionesPorCurso(int $idCurso): array
    {
        return $this->where('idCurso', $idCurso)
            ->orderBy('Orden', 'ASC')
            ->findAll();
    }
}
