<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'Usuario'; // Nombre de la tabla en la base de datos
    protected $primaryKey = 'idUsuario';

    protected $allowedFields = [
        'idRol',
        'PrimerNombre',
        'PrimerApellido',
        'Celular',
        'Edad',
        'Genero',
        'Correo',
        'Contraseña',
        'Certificado',
        'FechaCreacion',
        'TipoDoc',
        'Documento',
        'Estado'
    ];

    protected $createdField  = 'FechaCreacion';

    protected $returnType = 'array';
}
