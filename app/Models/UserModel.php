<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'usuario'; // Nombre de la tabla en la base de datos
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
        'AutoTraDatos',
        'FechaCreacion',
        'TipoDoc',
        'Documento',
        'Estado',
        'FotoPerfil',
        'FechaNacimiento'
    ];

    protected $createdField  = 'FechaCreacion';

    protected $returnType = 'array';
}
