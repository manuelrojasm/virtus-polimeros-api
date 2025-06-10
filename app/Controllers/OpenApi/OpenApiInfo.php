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
class OpenApiInfo
{
    // Esta clase está vacía. Solo contiene anotaciones.
}
