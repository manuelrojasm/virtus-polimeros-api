<?php

namespace App\Controllers\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: "API Virtus Polimeros",
    version: "1.0.0",
    description: "Endpoints aplicación Virtus Polimeros"
)]
#[OA\Server(
    url: "http://localhost:8080/",
    description: "Servidor local"
)]
#[OA\OpenApi(
    security: [['bearerAuth' => []]],
    components: new OA\Components(securitySchemes: [
        new OA\SecurityScheme(
            securityScheme: "bearerAuth",
            type: "http",
            scheme: "bearer",
            bearerFormat: "JWT",
            description: "Token JWT. Obtenerlo con POST /login (campo 'token' en la respuesta)."
        )
    ])
)]
class OpenApiInfo
{
}
