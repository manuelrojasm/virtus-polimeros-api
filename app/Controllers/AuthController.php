<?php

namespace App\Controllers;
use App\Models\UserModel;
use App\Services\ImageUploadService;
use CodeIgniter\Email\Email;
use CodeIgniter\RESTful\ResourceController;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use OpenApi\Attributes as OA;
use RuntimeException;

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

    #[OA\Post(
    path: "/api/register",
    tags: ["Autenticación"],
    summary: "Registrar un nuevo usuario",
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: [
                "Correo", "Contraseña", "idRol", "PrimerNombre", "PrimerApellido",
                "Celular", "FechaNacimiento", "Genero", "Documento", "TipoDoc"
            ],
            properties: [
                new OA\Property(property: "Correo", type: "string", format: "email", example: "usuario@example.com"),
                new OA\Property(property: "Contraseña", type: "string", minLength: 6, example: "secreta123"),
                new OA\Property(property: "idRol", type: "integer", example: 1, description: "1 = estudiante, 2 = admin"),
                new OA\Property(property: "PrimerNombre", type: "string", example: "Carlos"),
                new OA\Property(property: "PrimerApellido", type: "string", example: "Gómez"),
                new OA\Property(property: "Celular", type: "string", example: "3123456789"),
                new OA\Property(property: "FechaNacimiento", type: "string", format: "date", example: "2000-05-15"),
                new OA\Property(property: "Genero", type: "string", example: "Masculino"),
                new OA\Property(property: "Documento", type: "string", example: "12345678"),
                new OA\Property(property: "TipoDoc", type: "string", example: "CC"),
                new OA\Property(property: "Certificado", type: "string", example: "nombre_certificado.pdf", nullable: true),
            ]
        )
    ),
    responses: [
        new OA\Response(
            response: 201,
            description: "Usuario registrado con éxito",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "success", type: "boolean", example: true),
                    new OA\Property(property: "message", type: "string", example: "Usuario registrado con éxito")
                ]
            )
        ),
        new OA\Response(
            response: 200,
            description: "Error de validación o usuario ya existente",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "success", type: "boolean", example: false),
                    new OA\Property(property: "message", type: "string", example: "El usuario ya existe")
                ]
            )
        )
    ]
)]

    /**
     * Registro de usuario
     */
    public function register()
    {
        $json = $this->request->getJSON();
        log_message('info', 'Datos recibidos en registro: ' . json_encode($json));
        
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
        if (!isset($json->FechaNacimiento) || empty($json->FechaNacimiento)) {
            return $this->respond(['success' => false, 'message' => 'El campo FechaNacimiento es obligatorio'], 200);
        }

        // Validar formato de fecha
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $json->FechaNacimiento)) {
            return $this->respond(['success' => false, 'message' => 'El formato de FechaNacimiento es inválido (YYYY-MM-DD)'], 200);
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
            'Genero' => $json->Genero,
            'Correo' => $json->Correo,
            'Contraseña' => $hashedPassword,
            'Documento' => $json->Documento,  // Agregar el Documento
            'TipoDoc' => $json->TipoDoc, // Agregar el tipoDoc
            'Certificado' => $json->Certificado ?? null, // Opcional
            'Estado' => 1, // 1 = activo, 0 = inactivo
            'FechaCreacion' => date('Y-m-d H:i:s'),
            'FechaNacimiento' => $json->FechaNacimiento,
        ]);

        log_message('info', 'Datos a insertar: ' . json_encode([
            'idRol' => $json->idRol,
            'PrimerNombre' => $json->PrimerNombre,
            'PrimerApellido' => $json->PrimerApellido,
            'Celular' => $json->Celular,
            'Genero' => $json->Genero,
            'Correo' => $json->Correo,
            'Contraseña' => $hashedPassword,
            'Documento' => $json->Documento,  // Agregar el Documento
            'TipoDoc' => $json->TipoDoc, // Agregar el tipoDoc
            'Certificado' => $json->Certificado ?? null, // Opcional
            'Estado' => 1, // 1 = activo, 0 = inactivo
            'FechaCreacion' => date('Y-m-d H:i:s'),
            'FechaNacimiento' => $json->FechaNacimiento,
        ]));
        
        return $this->respond(['success' => true, 'message' => 'Usuario registrado con éxito'], 201);
    }
    

    #[OA\Post(
    path: "/login",
    tags: ["Autenticación"],
    summary: "Iniciar sesión",
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "Correo", type: "string", example: "usuario@ejemplo.com"),
                new OA\Property(property: "Contraseña", type: "string", example: "123456")
            ]
        )
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: "Respuesta del login",
            content: new OA\JsonContent(
                oneOf: [
                    new OA\Schema( // Éxito
                        properties: [
                            new OA\Property(property: "success", type: "boolean", example: true),
                            new OA\Property(property: "message", type: "string", example: "Login exitoso"),
                            new OA\Property(property: "token", type: "string", example: "eyJhbGciOiJIUzI1NiIs...")
                        ]
                    ),
                    new OA\Schema( // Fallo
                        properties: [
                            new OA\Property(property: "success", type: "boolean", example: false),
                            new OA\Property(property: "message", type: "string", example: "Contraseña incorrecta")
                        ]
                    )
                ]
            )
        )
    ]
)]

    
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

    #[OA\Post(
    path: "/recoverPassword",
    tags: ["Autenticación"],
    summary: "Enviar recordatorio de acceso con nueva contraseña",
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["Correo"],
            properties: [
                new OA\Property(property: "Correo", type: "string", format: "email", example: "usuario@example.com"),
            ]
        )
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: "Correo enviado con éxito o errores de validación",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "success", type: "boolean", example: true),
                    new OA\Property(property: "message", type: "string", example: "Correo enviado con éxito")
                ]
            )
        )
    ]
)]


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

    #[OA\Put(
    path: "/usuario/perfil/{id}",
    tags: ["Autenticación"],
    summary: "Actualizar perfil de usuario",
    description: "Solo el propio usuario (id del token = id de la ruta). Para subir foto use POST /usuario/perfil/{id}/foto. Para quitar foto envíe `FotoPerfil: null`.",
    security: [["bearerAuth" => []]],
    parameters: [
        new OA\Parameter(
            name: "id",
            in: "path",
            required: true,
            description: "ID del usuario a actualizar",
            schema: new OA\Schema(type: "integer", example: 1)
        )
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "PrimerNombre", type: "string", example: "Juan"),
                new OA\Property(property: "PrimerApellido", type: "string", example: "Pérez"),
                new OA\Property(property: "Correo", type: "string", format: "email", example: "juan@example.com"),
                new OA\Property(property: "Celular", type: "string", example: "3001234567"),
                new OA\Property(property: "FotoPerfil", type: "string", nullable: true, description: "Solo null para quitar la foto. Para subir imagen use POST /usuario/perfil/{id}/foto (multipart, campo foto)."),
            ]
        )
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: "Perfil actualizado correctamente",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "success", type: "boolean", example: true),
                    new OA\Property(property: "message", type: "string", example: "Perfil actualizado correctamente")
                ]
            )
        ),
        new OA\Response(
            response: 400,
            description: "Sin campos válidos, JSON inválido o intento de enviar URL de foto (usar POST /foto)",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "success", type: "boolean", example: false),
                    new OA\Property(property: "message", type: "string", example: "No hay campos válidos para actualizar"),
                ]
            )
        ),
        new OA\Response(
            response: 403,
            description: "El id de la ruta no coincide con el usuario del token",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "success", type: "boolean", example: false),
                    new OA\Property(property: "message", type: "string", example: "No autorizado"),
                ]
            )
        ),
        new OA\Response(
            response: 404,
            description: "Usuario no encontrado",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "status", type: "integer", example: 404),
                    new OA\Property(property: "messages", type: "object", nullable: true),
                ]
            )
        ),
    ]
)]


    public function updateProfile($id = null)
    {
        $authId = (int) ($this->request->authUser['id'] ?? 0);
        if ($authId !== (int) $id) {
            return $this->respond(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $userModel = new UserModel();
        $user = $userModel->find($id);
        if (! $user) {
            return $this->failNotFound('Usuario no encontrado');
        }

        $data = $this->request->getJSON(true);
        if (! $data) {
            return $this->fail('Datos inválidos');
        }

        $fields = [
            'PrimerNombre',
            'PrimerApellido',
            'Correo',
            'Celular',
        ];

        $datosActualizados = array_intersect_key($data, array_flip($fields));

        if (array_key_exists('FotoPerfil', $data)) {
            $valor = $data['FotoPerfil'];
            if ($valor !== null && $valor !== '') {
                return $this->respond([
                    'success' => false,
                    'message' => 'La foto de perfil se sube con POST usuario/perfil/{id}/foto (multipart, campo: foto, máx. 2 MB).',
                ], 400);
            }
            \Config\Services::imageUpload()->removeStoredPublicImage($user['FotoPerfil'] ?? null);
            $datosActualizados['FotoPerfil'] = null;
        }

        if ($datosActualizados === []) {
            return $this->respond([
                'success' => false,
                'message' => 'No hay campos válidos para actualizar',
            ], 400);
        }

        if (! $userModel->update($id, $datosActualizados)) {
            return $this->fail('No se pudo actualizar el usuario');
        }

        return $this->respond([
            'success' => true,
            'message' => 'Perfil actualizado correctamente',
        ]);
    }

    #[OA\Post(
        path: "/usuario/perfil/{id}/foto",
        tags: ["Autenticación"],
        summary: "Subir foto de perfil",
        description: "Multipart con campo `foto`. Tamaño máximo 2 MB. Tipos: JPEG, PNG, WebP, GIF. Sustituye la imagen anterior si existía (ruta bajo `uploads/usuarios/`). Requiere que el id de la ruta sea el del usuario autenticado.",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Debe coincidir con el claim `id` del JWT",
                schema: new OA\Schema(type: "integer", example: 1)
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    required: ["foto"],
                    properties: [
                        new OA\Property(
                            property: "foto",
                            type: "string",
                            format: "binary",
                            description: "Archivo de imagen"
                        ),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Foto guardada; `FotoPerfil` es la ruta relativa respecto a `public/`",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Foto de perfil actualizada"),
                        new OA\Property(property: "FotoPerfil", type: "string", example: "uploads/usuarios/a1b2c3d4e5f6789012345678abcdef01.jpg"),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Sin archivo, tipo no permitido o supera 2 MB",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "La imagen supera el tamaño máximo permitido (2 MB)."),
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: "No autorizado",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "No autorizado"),
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Usuario no encontrado"),
        ]
    )]
    public function uploadProfilePhoto($id = null)
    {
        $authId = (int) ($this->request->authUser['id'] ?? 0);
        if ($authId !== (int) $id) {
            return $this->respond(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $userModel = new UserModel();
        $user = $userModel->find($id);
        if (! $user) {
            return $this->failNotFound('Usuario no encontrado');
        }

        $file = $this->request->getFile('foto');
        if (! $file || ! $file->isValid()) {
            return $this->respond([
                'success' => false,
                'message' => 'Envíe un archivo de imagen en el campo "foto" (JPEG, PNG, WebP o GIF, máx. 2 MB).',
            ], 400);
        }

        try {
            $svc = \Config\Services::imageUpload();
            $svc->removeStoredPublicImage($user['FotoPerfil'] ?? null);
            $rel = $svc->saveFromUpload($file, 'usuarios', ImageUploadService::MAX_PERFIL_BYTES);
            $userModel->update($id, ['FotoPerfil' => $rel]);
        } catch (RuntimeException $e) {
            return $this->respond(['success' => false, 'message' => $e->getMessage()], 400);
        }

        return $this->respond([
            'success'       => true,
            'message'       => 'Foto de perfil actualizada',
            'FotoPerfil'    => $rel,
        ]);
    }

    #[OA\Put(
    path: "/usuario/cambiar-clave/{id}",
    tags: ["Autenticación"],
    summary: "Cambiar la contraseña del usuario",
    parameters: [
        new OA\Parameter(
            name: "id",
            in: "path",
            required: true,
            description: "ID del usuario",
            schema: new OA\Schema(type: "integer", example: 1)
        )
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "clave_actual", type: "string", example: "actual123"),
                new OA\Property(property: "nueva_clave", type: "string", example: "nueva456")
            ]
        )
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: "Contraseña actualizada correctamente",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "success", type: "boolean", example: true),
                    new OA\Property(property: "message", type: "string", example: "Contraseña actualizada correctamente")
                ]
            )
        ),
        new OA\Response(
            response: 400,
            description: "Datos incompletos o contraseña actual incorrecta",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "success", type: "boolean", example: false),
                    new OA\Property(property: "message", type: "string", example: "La contraseña actual es incorrecta")
                ]
            )
        ),
        new OA\Response(
            response: 404,
            description: "Usuario no encontrado",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "success", type: "boolean", example: false),
                    new OA\Property(property: "message", type: "string", example: "Usuario no encontrado")
                ]
            )
        )
    ]
)]


    public function changePassword($id = null)
    {
        $authId = (int) ($this->request->authUser['id'] ?? 0);
        if ($authId !== (int) $id) {
            return $this->respond(['success' => false, 'message' => 'No autorizado'], 403);
        }

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

    #[OA\Get(
    path: "/usuario/estudiantes",
    tags: ["Usuarios"],
    summary: "Obtener lista de estudiantes",
    responses: [
        new OA\Response(
            response: 200,
            description: "Lista de estudiantes",
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "success", type: "boolean", example: true),
                    new OA\Property(
                        property: "data",
                        type: "array",
                        items: new OA\Items(
                            type: "object",
                            properties: [
                                new OA\Property(property: "idUsuario", type: "integer", example: 5),
                                new OA\Property(property: "PrimerNombre", type: "string", example: "Juan"),
                                new OA\Property(property: "PrimerApellido", type: "string", example: "Pérez"),
                                new OA\Property(property: "Correo", type: "string", example: "juan.perez@example.com"),
                                new OA\Property(property: "Celular", type: "string", example: "3001234567"),
                                new OA\Property(property: "FotoPerfil", type: "string", example: "foto.jpg"),
                                new OA\Property(property: "idRol", type: "integer", example: 1),
                                new OA\Property(property: "Estado", type: "integer", example: 1)
                            ]
                        )
                    )
                ]
            )
        )
    ]
)]

    public function getStudents()
    {
        $userModel = new UserModel();
        $students = $userModel->where('idRol', 1)->findAll();

        return $this->respond([
            'success' => true,
            'data' => $students
        ]);
    }

    #[OA\Put(
    path: "/usuario/estado-usuario-estudiante/{id}",
    tags: ["Usuarios"],
    summary: "Activar o desactivar un usuario",
    parameters: [
        new OA\Parameter(
            name: "id",
            in: "path",
            required: true,
            description: "ID del usuario a activar o desactivar",
            schema: new OA\Schema(type: "integer", example: 3)
        )
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: "Estado del usuario actualizado correctamente",
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "success", type: "boolean", example: true),
                    new OA\Property(property: "message", type: "string", example: "Estado del usuario actualizado"),
                    new OA\Property(property: "nuevo_estado", type: "integer", example: 0)
                ]
            )
        ),
        new OA\Response(
            response: 404,
            description: "Usuario no encontrado",
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "success", type: "boolean", example: false),
                    new OA\Property(property: "message", type: "string", example: "Usuario no encontrado")
                ]
            )
        )
    ]
)]

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
