<?php

namespace App\Controllers;
use CodeIgniter\RESTful\ResourceController;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\UserModel;

class AuthController extends ResourceController
{
    private $key;

    public function __construct()
    {
        $this->key = getenv('JWT_SECRET'); 
    }

    /**
     * Registro de usuario
     */
    public function register()
    {
        $json = $this->request->getJSON();
        
        // Validar que todos los campos requeridos estén presentes
        if (!isset($json->Correo) || empty($json->Correo)) {
            return $this->respond(['success' => false, 'message' => 'El campo Correo es obligatorio'], 200);
        }
        if (!isset($json->Contraseña) || empty($json->Contraseña)) {
            return $this->respond(['success' => false, 'message' => 'El campo Contraseña es obligatorio'], 200);
        }
        if (!isset($json->idRol) || !in_array($json->idRol, [1, 2])) {
            return $this->respond(['success' => false, 'message' => 'El campo idRol es obligatorio y debe ser 1 (estudiante) o 2 (admin)'], 200);
        }
        if (!isset($json->PrimerNombre) || empty($json->PrimerNombre)) {
            return $this->respond(['success' => false, 'message' => 'El campo PrimerNombre es obligatorio'], 200);
        }
        if (!isset($json->PrimerApellido) || empty($json->PrimerApellido)) {
            return $this->respond(['success' => false, 'message' => 'El campo PrimerApellido es obligatorio'], 200);
        }
        if (!isset($json->Celular) || empty($json->Celular)) {
            return $this->respond(['success' => false, 'message' => 'El campo Celular es obligatorio'], 200);
        }
        if (!isset($json->Edad) || empty($json->Edad) || !is_numeric($json->Edad)) {
            return $this->respond(['success' => false, 'message' => 'El campo Edad es obligatorio y debe ser un número'], 200);
        }
        if (!isset($json->Genero) || empty($json->Genero)) {
            return $this->respond(['success' => false, 'message' => 'El campo Genero es obligatorio'], 200);
        }
        
        // Validar que el formato del correo sea correcto
        if (!filter_var($json->Correo, FILTER_VALIDATE_EMAIL)) {
            return $this->respond(['success' => false, 'message' => 'El formato del Correo es inválido'], 200);
        }
    
        // Validar que la contraseña tenga al menos 6 caracteres
        if (strlen($json->Contraseña) < 6) {
            return $this->respond(['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres'], 200);
        }
        
        $userModel = new UserModel();

        // Verificar si el usuario ya existe por correo o documento
        $existingUser = $userModel->where('Correo', $json->Correo)
        ->orWhere('Documento', $json->Documento)
        ->first();
        
        if ($existingUser) {
        return $this->respond(['success' => false, 'message' => 'El usuario ya existe'], 200);
        }
        
        // Encriptar contraseña
        $hashedPassword = password_hash($json->Contraseña, PASSWORD_DEFAULT);
        
        // Guardar usuario en la base de datos
        $userModel->insert([
            'idRol' => $json->idRol,
            'PrimerNombre' => $json->PrimerNombre,
            'PrimerApellido' => $json->PrimerApellido,
            'Celular' => $json->Celular,
            'Edad' => $json->Edad,
            'Genero' => $json->Genero,
            'Correo' => $json->Correo,
            'Contraseña' => $hashedPassword,
            'Certificado' => $json->Certificado ?? null, // Opcional
            'Estado' => 1, // 1 = activo, 0 = inactivo
            'FechaCreacion' => date('Y-m-d H:i:s')
        ]);
        
        return $this->respond(['success' => true, 'message' => 'Usuario registrado con éxito'], 201);
    }
    
    /**
     * Inicio de sesión con JWT
     */
    public function login()
    {
        $json = $this->request->getJSON();
    
        // Validar que los campos requeridos estén presentes
        if (!isset($json->Correo) || empty($json->Correo)) {
            return $this->respond(['success' => false, 'message' => 'El campo Correo es obligatorio'], 200);
        }
        if (!isset($json->Contraseña) || empty($json->Contraseña)) {
            return $this->respond(['success' => false, 'message' => 'El campo Contraseña es obligatorio'], 200);
        }
    
        // Buscar el usuario por correo
        $userModel = new UserModel();
        $user = $userModel->where('Correo', $json->Correo)->first();
    
        if (!$user) {
            return $this->respond(['success' => false, 'message' => 'Usuario no encontrado'], 200);
        }
    
        // Verificar la contraseña
        if (!password_verify($json->Contraseña, $user['Contraseña'])) {
            return $this->respond(['success' => false, 'message' => 'Contraseña incorrecta'], 200);
        }
    
        $payload = [
            'id' => $user['idUsuario'],
            'idRol' => $user['idRol'],
            'iat' => time(),
            'exp' => time() + 3600
        ];
        
        $token = JWT::encode($payload, $this->key, 'HS256');
    
        return $this->respond([
            'success' => true,
            'message' => 'Login exitoso',
            'token' => $token
        ], 200);
    }
}
