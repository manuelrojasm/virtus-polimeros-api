<?php

namespace App\Controllers\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: "API Virtus Polimeros",
    version: "1.0.0",
    description: <<<DESC
Endpoints de la aplicación Virtus Polimeros.

**Imágenes (foto de perfil y portadas de curso)**  
Se guardan como archivos bajo `public/uploads/`. En la base de datos solo se almacena la ruta relativa (p. ej. `uploads/usuarios/abc.jpg`).  
Para mostrar la imagen en el cliente, concatene la URL base del API con `/` y esa ruta (p. ej. `https://su-dominio.com/uploads/usuarios/abc.jpg`).

| Recurso | Subida | Límite aprox. | Campo multipart |
|---------|--------|---------------|-----------------|
| Foto de perfil | `POST /usuario/perfil/{id}/foto` | 2 MB | `foto` |
| Portada de curso | `POST /cursos/{id}/portada` | 5 MB | `portada` |

Formatos admitidos: JPEG, PNG, WebP, GIF. Tras crear un curso con `POST /cursos`, suba la portada con `POST /cursos/{id}/portada`.
DESC
)]
#[OA\Server(
    url: "http://localhost:8080/",
    description: "Servidor local (ajuste la URL en Swagger UI si usa otro host o puerto)"
)]
#[OA\OpenApi(
    security: [['bearerAuth' => []]],
    tags: [
        new OA\Tag(
            name: 'Autenticación',
            description: 'Registro, login, recuperación de contraseña, perfil y foto de perfil (Bearer JWT en rutas protegidas).'
        ),
        new OA\Tag(
            name: 'Cursos',
            description: 'Alta y edición de cursos (admin). La imagen de portada no va en JSON: use POST /cursos/{id}/portada.'
        ),
    ],
    components: new OA\Components(securitySchemes: [
        new OA\SecurityScheme(
            securityScheme: "bearerAuth",
            type: "http",
            scheme: "bearer",
            bearerFormat: "JWT",
            description: "Token JWT devuelto por POST /login en el campo `token`. En Swagger UI use Authorize y pegue solo el valor del token (sin prefijo Bearer en algunos clientes el campo ya lo añade)."
        )
    ])
)]
class OpenApiInfo
{
}
