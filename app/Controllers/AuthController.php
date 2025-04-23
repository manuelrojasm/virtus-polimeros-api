<?php

namespace App\Controllers;
use CodeIgniter\RESTful\ResourceController;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\UserModel;
use CodeIgniter\Email\Email;

class AuthController extends ResourceController
{
    private $key;

    public function __construct()
    {
        $this->key = getenv('JWT_SECRET'); 
        helper(['email']);
        
        // Agregar cabeceras CORS para permitir solicitudes desde otros dominios
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
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
    
        // Validar que el campo Documento no esté vacío
        if (!isset($json->Documento) || empty($json->Documento)) {
            return $this->respond(['success' => false, 'message' => 'El campo Documento es obligatorio'], 200);
        }
    
        // Validar que el campo tipoDoc no esté vacío
        if (!isset($json->TipoDoc) || empty($json->TipoDoc)) {
            return $this->respond(['success' => false, 'message' => 'El campo tipoDoc es obligatorio'], 200);
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
        
        // Guardar usuario en la base de datos, incluyendo el Documento y tipoDoc
        $userModel->insert([
            'idRol' => $json->idRol,
            'PrimerNombre' => $json->PrimerNombre,
            'PrimerApellido' => $json->PrimerApellido,
            'Celular' => $json->Celular,
            'Edad' => $json->Edad,
            'Genero' => $json->Genero,
            'Correo' => $json->Correo,
            'Contraseña' => $hashedPassword,
            'Documento' => $json->Documento,  // Agregar el Documento
            'TipoDoc' => $json->TipoDoc, // Agregar el tipoDoc
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

        // Validar que el usuario esté activo
        if ($user['Estado'] != 1) {
            return $this->respond([
                'success' => false,
                'message' => 'Usuario inactivo. Contacta con el administrador.'
            ], 200);
        }
    
        $payload = [
            'id' => $user['idUsuario'],
            'idRol' => $user['idRol'],
            'Nombre' => $user['PrimerNombre'],
            'Apellido' => $user['PrimerApellido'],
            'Celular' => $user['Celular'],
            'Correo' => $user['Correo'],
            'FotoPerfil' => $user['FotoPerfil'],
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

    public function sendLoginReminder()
    {
        $json = $this->request->getJSON();
        
        if (!isset($json->Correo) || empty($json->Correo)) {
            return $this->respond(['success' => false, 'message' => 'El campo Correo es obligatorio'], 200);
        }

        $userModel = new UserModel();
        $user = $userModel->where('Correo', $json->Correo)->first();

        if (!$user) {
            return $this->respond(['success' => false, 'message' => 'Usuario no encontrado'], 200);
        }

        $newPassword = $this->generateRandomPassword();
        
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $userModel->update($user['idUsuario'], ['Contraseña' => $hashedPassword]);

        $emailSubject = 'Recordatorio de acceso a tu cuenta';
        $emailBody = "
            <p>Hola {$user['PrimerNombre']} {$user['PrimerApellido']},</p>
            <p>Este es un recordatorio de tus datos de acceso:</p>
            <p><strong>Correo:</strong> {$user['Correo']}</p>
            <p><strong>Contraseña:</strong> {$newPassword}</p>
            <p>¡Recuerda que debes mantener tu contraseña segura!</p>
            <p>Si necesitas cambiar tu contraseña, puedes hacerlo en la página de configuración de tu cuenta.</p>
            <p>¡Gracias!</p>
        ";

        $email = \Config\Services::email();
        $email->setTo($user['Correo']);
        $email->setSubject($emailSubject);
        $email->setMessage($emailBody);
        
        // Enviar el correo
        if ($email->send()) {
            return $this->respond(['success' => true, 'message' => 'Correo enviado con éxito'], 200);
        } else {
            return $this->respond(['success' => false, 'message' => 'Error al enviar el correo'], 200);
        }
    }

    private function generateRandomPassword($length = 8)
    {
        // Generar una contraseña aleatoria con letras y números
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $password;
    }

    public function updateProfile($id = null)
    {
        $userModel = new UserModel();
        $data = $this->request->getJSON(true); // true = return as array

        if (!$data) {
            return $this->fail('Datos inválidos');
        }

        // Solo actualizamos campos permitidos
        $fields = [
            'PrimerNombre',
            'PrimerApellido',
            'Correo',
            'Celular',
            'FotoPerfil'
        ];

        $datosActualizados = array_intersect_key($data, array_flip($fields));

        if (!$userModel->update($id, $datosActualizados)) {
            return $this->fail('No se pudo actualizar el usuario');
        }

        return $this->respond([
            'success' => true,
            'message' => 'Perfil actualizado correctamente'
        ]);
    }

    public function changePassword($id = null)
    {
        $userModel = new UserModel();
        $data = $this->request->getJSON(true);

        if (!isset($data['clave_actual']) || !isset($data['nueva_clave'])) {
            return $this->respond([
                'success' => false,
                'message' => 'Datos incompletos'
            ], 400);
        }

        $usuario = $userModel->find($id);
        if (!$usuario) {
            return $this->respond([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        if (!password_verify($data['clave_actual'], $usuario['Contraseña'])) {
            return $this->respond([
                'success' => false,
                'message' => 'La contraseña actual es incorrecta'
            ], 400);
        }

        $nuevaClaveHasheada = password_hash($data['nueva_clave'], PASSWORD_DEFAULT);

        $userModel->update($id, ['Contraseña' => $nuevaClaveHasheada]);

        return $this->respond([
            'success' => true,
            'message' => 'Contraseña actualizada correctamente'
        ]);
    }

    public function getStudents()
    {
        $userModel = new UserModel();
        $students = $userModel->where('idRol', 1)->findAll();

        return $this->respond([
            'success' => true,
            'data' => $students
        ]);
    }

    public function toggleUserStatus($id = null)
    {
        $userModel = new UserModel();
        $user = $userModel->find($id);

        if (!$user) {
            return $this->respond([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        $newStatus = $user['Estado'] == 1 ? 0 : 1;
        $userModel->update($id, ['Estado' => $newStatus]);

        return $this->respond([
            'success' => true,
            'message' => 'Estado del usuario actualizado',
            'nuevo_estado' => $newStatus
        ]);
    }


}
