<?php
require __DIR__ . '/../vendor/autoload.php';

use OpenApi\Generator;

header('Content-Type: application/json');
$controllersPath = __DIR__ . '/../app/Controllers/';

if (!is_dir($controllersPath)) {
    echo json_encode(['error' => 'Carpeta no encontrada: ' . $controllersPath]);
    exit;
}

$openapi = Generator::scan([$controllersPath]);
echo $openapi->toJson();