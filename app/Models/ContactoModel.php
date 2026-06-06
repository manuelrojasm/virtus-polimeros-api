<?php

namespace App\Models;

use CodeIgniter\Model;

class ContactoModel extends Model
{
    protected $table      = 'contacto';
    protected $primaryKey = 'idContacto';
    
    protected $allowedFields = ['Nombre', 'Correo', 'Mensaje', 'FechaCreación', 'Estado'];
    
    // Configuración para evitar la inyección de SQL
    protected $returnType     = 'array';
    protected $useTimestamps  = false;
    protected $dateFormat     = 'datetime';
}
